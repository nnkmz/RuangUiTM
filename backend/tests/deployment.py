"""Destructive acceptance on disposable Docker CI only; never run on a live server."""
import os, json, ssl, subprocess, time, hashlib, re, uuid, datetime
import urllib.request, urllib.error, http.cookiejar
from pathlib import Path
import http_client as hc
from http_client import Client, check, ADMIN, PASSWORD

assert os.environ.get('CI') == 'true', 'This destructive test is restricted to disposable CI.'
os.environ['COMPOSE_FILE'] = 'compose.yaml:docker/compose.ci-tls.yaml'
os.environ['SSL_CERT_FILE'] = str(Path('docker/ci/certs/cert.pem').resolve())
hc.BASE = 'https://localhost:8443/api/v1/'
results = []
def mark(ok, message):
    check(ok, message); results.append(message)
def compose(*args, input=None):
    return subprocess.check_output(['docker','compose',*args],input=input,stderr=subprocess.STDOUT)
def wait_for(predicate, message, seconds=90):
    deadline=time.monotonic()+seconds
    while time.monotonic()<deadline:
        try:
            if predicate(): return
        except (urllib.error.URLError,ConnectionError,OSError,AssertionError): pass
        time.sleep(2)
    raise AssertionError(message)
def health():
    with urllib.request.urlopen(hc.BASE+'health',timeout=5) as r:
        return r.status==200 and json.load(r)['status']=='ok'
def sql(query):
    return compose('exec','-T','db','sh','-c',
        'MYSQL_PWD="$MARIADB_PASSWORD" exec mariadb --batch --skip-column-names -u"$MARIADB_USER" "$MARIADB_DATABASE"',input=query.encode())
def hashes():
    tables=sql('SHOW TABLES;').decode().splitlines(); output={}
    for table in tables:
        assert re.fullmatch(r'[a-z_]+',table)
        output[table]=hashlib.sha256(b'\n'.join(sorted(sql(f'SELECT * FROM `{table}`;').splitlines()))).hexdigest()
    return output
def upload_hashes():
    return sorted(compose('exec','-T','php','sh','-c',
        'find storage/uploads -type f -exec sha256sum {} +').decode().splitlines())

compose('up','-d','php','web','tls')
wait_for(health,'TLS endpoint not healthy')
# No -k or ignored TLS errors: the explicit test certificate is trusted and hostname checked.
response=urllib.request.urlopen(hc.BASE+'csrf',timeout=5)
headers=response.headers; response.read()
mark('max-age=' in headers.get('Strict-Transport-Security',''),'HTTPS HSTS header present')
# Reject the test certificate without the explicit CI trust anchor.
try:
    urllib.request.urlopen(hc.BASE+'health',context=ssl.create_default_context(cafile='/etc/ssl/certs/ca-certificates.crt'),timeout=5)
except urllib.error.URLError:
    mark(True,'untrusted TLS certificate rejected')
else: raise AssertionError('Untrusted test certificate accepted')
redirect=urllib.request.urlopen('http://127.0.0.1:8082/api/v1/health',timeout=5)
mark(redirect.geturl()=='https://localhost:8443/api/v1/health','HTTP redirect preserves path and upgrades to HTTPS')

a=Client(); a.login(ADMIN,PASSWORD)
cookies=list(a.jar)
mark(len(cookies)==1 and cookies[0].secure and cookies[0].has_nonstandard_attr('HttpOnly')
     and cookies[0].get_nonstandard_attr('SameSite')=='Strict','session cookie Secure HttpOnly SameSite Strict')
boot=a.req('bootstrap'); room=dict(boot['rooms'][0]); suffix=uuid.uuid4().hex[:8]
room.update(id='',code='DEP-'+suffix,name='Deployment sentinel '+suffix,points=None,manual=12,occupants=0,capacity=2)
sentinel=a.req('rooms','POST',room,201)['room']
ops=a.req('ops'); attachments=ops['attachments']; mark(bool(attachments),'real private attachments available for persistence tests')
attachment=attachments[0]; attachment_path='ops/attachments/'+str(attachment['id'])
original=a.opener.open(hc.BASE+attachment_path).read(); digest=hashlib.sha256(original).hexdigest()
compose('restart','db'); wait_for(health,'Database restart did not recover')
compose('restart','php','web'); wait_for(health,'Web/PHP restart did not recover')
mark(a.req('me')['user']['email']==ADMIN,'existing session survives database PHP and web restart')
mark(any(r['id']==sentinel['id'] and r['name']==sentinel['name'] for r in a.req('bootstrap')['rooms']),'saved room survives container restart')
mark(hashlib.sha256(a.opener.open(hc.BASE+attachment_path).read()).hexdigest()==digest,'private attachment bytes survive container restart')
# Exercise the actual scheduler service loop, including a second cycle and restart.
asset=next(x for x in ops['assets'] if int(x['active']))
tech=next(x for x in ops['people'] if x['role']=='technician')
name='Scheduler restart '+suffix
a.req('ops/plans','POST',{'name':name,'asset_id':int(asset['id']),'assigned_to':int(tech['id']),
    'interval_days':30,'next_due':datetime.datetime.now(datetime.timezone.utc).date().isoformat(),
    'checklist':['Check restart idempotency'],'active':True},201)
def occurrences():return [x for x in a.req('ops')['work'] if x['title']==name]
compose('start','scheduler'); wait_for(lambda:len(occurrences())==1,'Scheduler service did not generate due work')
mark(len(occurrences())==1,'scheduler service generates due work without HTTP trigger')
time.sleep(65)
mark(len(occurrences())==1,'second scheduler loop creates no duplicate work')
cycles_before=compose('logs','--no-color','scheduler').count(b'"generated":')
compose('restart','scheduler')
wait_for(lambda: compose('logs','--no-color','scheduler').count(b'"generated":')>cycles_before,'Scheduler restart did not complete a new cycle')
mark(len(occurrences())==1,'scheduler restart creates no duplicate work')
compose('stop','scheduler')
# Snapshot every table and all attachment files, mutate a known test row and remove one
# referenced file, then prove restore repairs content rather than just returning health.
before=hashes(); files_before=upload_hashes()
compose('exec','-T','php','php','-r',
    '$p="storage/uploads/restore-probe";file_put_contents($p,"restore probe");')
# Include the probe in the snapshot/backup, then remove it to test upload restoration.
files_before=upload_hashes()
subprocess.run(['scripts/backup.sh'],check=True)
backup=sorted(Path('backups').glob('*.sql'))[-1]
sql("UPDATE rooms SET name='Mutated before restore' WHERE code='DEP-"+suffix+"';")
compose('exec','-T','php','php','-r','unlink("storage/uploads/restore-probe");')
mark(hashes()!=before and upload_hashes()!=files_before,'restore test changes database content and removes an upload before recovery')
subprocess.run(['scripts/restore.sh',str(backup)],input=b'RESTORE\n',check=True)
wait_for(health,'Restore endpoint did not recover'); compose('stop','scheduler')
# Health does not mutate application tables. Compare before authenticated API reads.
mark(hashes()==before,'restore matches content hashes of every database table')
mark(upload_hashes()==files_before,'restore matches all attachment files including deleted probe')
mark(a.req('me')['user']['email']==ADMIN,'authenticated session remains usable after restore')
mark(hashlib.sha256(a.opener.open(hc.BASE+attachment_path).read()).hexdigest()==digest,'authorized private attachment download remains byte identical after restore')
# Persistent volumes must also survive container removal/recreation (without -v).
compose('down'); compose('up','-d','db','php','web','tls');wait_for(health,'Recreated stack did not recover')
mark(a.req('me')['user']['email']==ADMIN,'session survives container removal and recreation with volumes')
mark(any(r['id']==sentinel['id'] and r['name']==sentinel['name'] for r in a.req('bootstrap')['rooms']),'room data survives container recreation')
mark(hashlib.sha256(a.opener.open(hc.BASE+attachment_path).read()).hexdigest()==digest,'attachment survives container recreation')
# Effective Compose configuration exposes only loopback web/TLS, no database/PHP port.
config=json.loads(compose('config','--format','json'))
mark(not config['services']['db'].get('ports') and not config['services']['php'].get('ports'),'database and PHP have no published ports')
mark(all(p.get('host_ip')=='127.0.0.1' for n in ['web','tls'] for p in config['services'][n].get('ports',[])),'published web and TLS ports bind to loopback only')
Path('deployment-results.json').write_text(json.dumps({'passed':len(results),'checks':results,'tables_verified':len(before),'upload_files_verified':len(files_before)},indent=2)+'\n')
print(f'{len(results)} deployment checks passed.')
