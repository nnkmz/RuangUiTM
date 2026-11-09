"""Cross-module integration against a disposable, seeded MariaDB. Never run on production."""
import datetime,uuid,json,base64,urllib.request,urllib.error
import http_client as hc
from http_client import Client,check,ADMIN,PASSWORD
suffix=uuid.uuid4().hex[:8];a=Client();a.login(ADMIN,PASSWORD)
def post(client,path,data,expected=200):return client.req('ops/'+path,'POST',data,expected)
def data(client=a):return client.req('ops')
def item(kind,id,client=a):return next(x for x in data(client)[kind] if str(x.get('id'))==str(id))
def status(id,action,client,**other):return post(client,'work/'+str(id)+'/status',{'version':int(item('work',id,client)['version']),'action':action,**other})
boot=a.req('bootstrap');rooms=boot['rooms'];r=next(x for x in rooms if x['building']=='AKD' and x['floor']=='01' and x['code']=='01-01');r2=next(x for x in rooms if x['building']=='AKD' and x['floor']=='01' and x['code']=='01-02')
users={}
for role in ['technician','reporter','auditor']:
 email=role+suffix+'@example.test';a.req('users','POST',{'name':role,'email':email,'password':'Module Test Password!2026','role':role},201);c=Client();c.login(email,'Module Test Password!2026');users[role]=c
tech=users['technician'];reporter=users['reporter'];auditor=users['auditor'];people=data()['people'];techid=int(next(u for u in people if u['role']=='technician' and u['name']=='technician' and str(u['id'])==str(max(int(x['id']) for x in people if x['role']=='technician')))['id'])
# Catalogues and material accounting.
sup=post(a,'suppliers',{'code':'SUP-'+suffix,'name':'Test supplier','email':'supplier@example.test','phone':'0100000000','active':True},201)['record']
asset=post(a,'assets',{'code':'AST-'+suffix,'name':'Test AHU','room_id':r['id'],'category':'Mekanikal','serial':'SN-'+suffix,'condition_status':'Baik','purchase_cost':2500.50,'warranty_end':'2030-12-31','supplier_id':int(sup['id']),'active':True},201)['record'];aid=int(asset['id'])
part=post(a,'parts',{'code':'PART-'+suffix,'name':'Filter','unit':'unit','reorder_level':2,'unit_cost':0,'active':True},201)['record'];pid=int(part['id']);post(a,'stock',{'part_id':pid,'quantity':10,'unit_cost':12.5,'reason':'Purchase receipt'},201);check(float(item('parts',pid)['stock'])==10,'goods receipt updates stock')
post(reporter,'stock',{'part_id':pid,'quantity':1,'unit_cost':10,'reason':'Forbidden'},403)
now=datetime.datetime.now(datetime.timezone.utc);today=now.date().isoformat()
contract=post(a,'contracts',{'code':'CTR-'+suffix,'title':'Service contract','supplier_id':int(sup['id']),'starts':(now.date()-datetime.timedelta(days=1)).isoformat(),'ends':(now.date()+datetime.timedelta(days=365)).isoformat(),'amount':5000,'active':True},201)['record']
# Real request -> two work orders -> technician -> manager verification.
req=post(reporter,'requests',{'room_id':r['id'],'asset_id':aid,'subject':'AHU filter fault '+suffix,'description':'Cooling fault','priority':'Tinggi'},201)['id']
request=item('requests',req);check(request['response_due']<request['resolve_due'],'SLA dates captured from policy')
post(a,'requests/'+str(req)+'/review',{'version':int(request['version']),'action':'accept','reason':'Validated'})
wos=[]
for i in range(2):
 wo=post(a,'work',{'request_id':req,'room_id':r['id'],'title':'Repair '+suffix+'-'+str(i),'assigned_to':techid,'contract_id':int(contract['id'])},201)['id'];wos.append(wo)
post(a,'assets/'+str(aid)+'/move',{'version':1,'room_id':r2['id'],'reason':'Move with open works'},422)
post(reporter,'work/'+str(wos[0])+'/status',{'version':1,'action':'start'},403)
status(wos[0],'start',tech);post(tech,'work/'+str(wos[0])+'/logs',{'minutes':60,'hourly_rate':30,'note':'Filter replacement'},201)
post(tech,'stock',{'part_id':pid,'work_order_id':wos[0],'quantity':-2,'unit_cost':0,'reason':'Installed filters'},201)
post(tech,'stock',{'part_id':pid,'work_order_id':wos[0],'quantity':-100,'unit_cost':0,'reason':'Insufficient'},422)
check(float(item('parts',pid)['stock'])==8,'insufficient stock rolls back without negative balance')
status(wos[0],'submit',tech,checks=[]);w=item('work',wos[0],tech);post(tech,'work/'+str(wos[0])+'/status',{'version':int(w['version']),'action':'verify'},403)
status(wos[0],'verify',a);check(item('requests',req)['status']=='Dalam tindakan','first completed work does not prematurely close request')
status(wos[1],'start',tech);post(tech,'work/'+str(wos[1])+'/logs',{'minutes':20,'hourly_rate':60,'note':'Airflow test'},201);status(wos[1],'submit',tech,checks=[]);status(wos[1],'return',a,reason='Retest required');check(item('work',wos[1])['status']=='Dalam pelaksanaan','manager returns work for correction');status(wos[1],'submit',tech,checks=[]);status(wos[1],'verify',a)
check(item('requests',req)['status']=='Ditutup','all verified works close parent request');check(float(item('work',wos[0])['labor_cost'])==30 and float(item('work',wos[0])['material_cost'])==25,'work costs combine stored labor and stock valuation')
post(a,'assets/'+str(aid)+'/move',{'version':int(item('assets',aid)['version']),'room_id':r2['id'],'reason':'Approved relocation'});check(item('assets',aid)['room_id']==r2['id'],'asset relocation persists with history');check(any(int(m['asset_id'])==aid for m in data()['asset_moves']),'asset move history retained')
# Preventive maintenance generation and checklist enforcement.
plan={'name':'Quarterly test '+suffix,'asset_id':aid,'assigned_to':techid,'interval_days':30,'next_due':today,'checklist':['Inspect filter','Measure airflow'],'active':True};post(a,'plans',plan,201)
generated=post(a,'scheduler',{});check(generated['generated']>=1,'scheduler generates due maintenance work');post(a,'scheduler',{});works=[w for w in data()['work'] if w['title']==plan['name']];check(len(works)==1,'scheduler rerun creates no duplicate occurrence');pm=int(works[0]['id']);status(pm,'start',tech)
post(tech,'work/'+str(pm)+'/status',{'version':int(item('work',pm,tech)['version']),'action':'submit','checks':[True,True]},422)
post(tech,'work/'+str(pm)+'/logs',{'minutes':15,'hourly_rate':0,'note':'Checklist inspection'},201)
post(tech,'work/'+str(pm)+'/status',{'version':int(item('work',pm,tech)['version']),'action':'submit','checks':[True,False]},422)
status(pm,'submit',tech,checks=[True,True]);status(pm,'verify',a);check(item('work',pm)['status']=='Selesai','maintenance checklist and verification complete')
# Bookings enforce overlap and capacity on server, including modification of a booked room.
start=(now+datetime.timedelta(days=30)).replace(hour=10,minute=0,second=0,microsecond=0);end=start+datetime.timedelta(hours=1);stamp=lambda x:x.strftime('%Y-%m-%dT%H:%M')
b={'room_id':r['id'],'title':'Team meeting '+suffix,'starts':stamp(start),'ends':stamp(end),'attendees':5};bid=post(reporter,'bookings',b,201)['id'];post(a,'bookings',b,409)
post(a,'bookings',{**b,'starts':stamp(end),'ends':stamp(end+datetime.timedelta(hours=1))},201)
# Cancel adjacent test slot to allow repeatable runs.
adj=next(x for x in data()['bookings'] if x['title']==b['title'] and int(x['id'])!=bid);post(a,'bookings/'+str(adj['id'])+'/status',{'version':int(adj['version']),'action':'cancel','reason':'Test cleanup'})
post(a,'bookings',{**b,'starts':stamp(start+datetime.timedelta(days=1)),'ends':stamp(end+datetime.timedelta(days=1)),'attendees':100000},422)
room_now=next(x for x in a.req('bootstrap')['rooms'] if x['id']==r['id']);a.req('rooms/'+r['id'],'PUT',{**room_now,'capacity':1},422)
bk=item('bookings',bid);post(a,'bookings/'+str(bid)+'/status',{'version':int(bk['version']),'action':'approve','reason':'Approved'});bk=item('bookings',bid,reporter);post(reporter,'bookings/'+str(bid)+'/status',{'version':int(bk['version']),'action':'cancel','reason':'Meeting cancelled'});check(item('bookings',bid)['status']=='Dibatalkan','booking approval and owner cancellation')
# Charges use current area snapshot and enforce uniqueness per room/month.
newroom=a.req('rooms','POST',{**r,'id':'','code':'COST-'+suffix,'name':'Cost test','points':None,'manual':10.25},201)['room'];post(a,'charges',{'room_id':newroom['id'],'period':'2030-01','rate':2.5},201);post(a,'charges',{'room_id':newroom['id'],'period':'2030-01','rate':2.5},409)
charge=next(c for c in data()['charges'] if c['room_id']==newroom['id']);check(float(charge['amount'])==25.63,'room charge uses area snapshot and monetary rounding')
period=now.strftime('%Y-%m');budget=next((b for b in data()['budgets'] if b['period']==period),None);payload={'period':period,'amount':10000,'note':'Operational budget'}
if budget:a.req('ops/budgets/'+str(budget['id']),'PUT',{**payload,'version':int(budget['version'])})
else:post(a,'budgets',payload,201)
report=a.req('ops/reports?period='+period);check(report['spent']>=75 and report['budget']==10000,'financial report aggregates actual work costs and budget');auditor.req('ops/reports');post(auditor,'parts',{'code':'FORBIDDEN'},403)
# Utility readings are monotonic and consumption is derived from consecutive readings.
meter=post(a,'meters',{'code':'MTR-'+suffix,'name':'Energy meter','room_id':r2['id'],'unit':'kWh','active':True},201)['record'];mid=int(meter['id'])
post(tech,'readings',{'meter_id':mid,'recorded_at':stamp(now-datetime.timedelta(hours=2)),'value':100},201);post(tech,'readings',{'meter_id':mid,'recorded_at':stamp(now-datetime.timedelta(hours=1)),'value':110},201);post(tech,'readings',{'meter_id':mid,'recorded_at':stamp(now-datetime.timedelta(minutes=30)),'value':109},422)
reading=next(x for x in data()['readings'] if int(x['meter_id'])==mid);check(float(reading['consumption'])==10,'meter consumption derived from sequential cumulative readings')
# Attachments: MIME check, persistence and ownership protection.
png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=')
def upload(client,content,name,expected):
 boundary='----test'+suffix;body=b''
 for k,v in [('entity_type','request'),('entity_id',str(req))]:body+=f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode()
 body+=f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="{name}"\r\nContent-Type: application/octet-stream\r\n\r\n'.encode()+content+f'\r\n--{boundary}--\r\n'.encode()
 request=urllib.request.Request(hc.BASE+'ops/attachments',data=body,headers={'Content-Type':'multipart/form-data; boundary='+boundary,'X-CSRF-Token':client.csrf},method='POST')
 try:rsp=client.opener.open(request);status=rsp.status;value=json.loads(rsp.read())
 except urllib.error.HTTPError as e:status=e.code;value=json.loads(e.read())
 check(status==expected,'attachment HTTP status '+str(expected));return value
upload(reporter,b'not an image','bad.png',422);attachment=upload(reporter,png,'evidence.png',201)['id'];raw=reporter.opener.open(hc.BASE+'ops/attachments/'+attachment).read();check(raw==png,'authorized attachment download matches uploaded bytes')
other=Client();other.login(ADMIN,PASSWORD); # manager can read all
check(any(n['entity_type']=='request' and str(n['entity_id'])==str(req) for n in data(reporter)['notifications']),'workflow notifications visible to reporter');post(reporter,'notifications/read',{});check(all(n['read_at'] for n in data(reporter)['notifications']),'notifications can be marked read')
post(a,'parts',{'code':'ZZ','name':'Bad precision','unit':'unit','reorder_level':1,'unit_cost':1.001,'active':True},422)
# Room cannot be archived while an active asset is located there.
current_room=next(x for x in a.req('bootstrap')['rooms'] if x['id']==r2['id']);a.req('rooms/'+r2['id'],'DELETE',{'version':current_room['version'],'reason':'Blocked dependency'},422)
# Cross-owner attachment and technician assignment protection.
other_email='other'+suffix+'@example.test';a.req('users','POST',{'name':'Other reporter','email':other_email,'password':'Another Test Password!2026','role':'reporter'},201);stranger=Client();stranger.login(other_email,'Another Test Password!2026');stranger.req('ops/attachments/'+attachment,expected=403);check(all(int(x['id'])!=req for x in data(stranger)['requests']),'another reporter cannot see private requests')
other_tech_email='othertech'+suffix+'@example.test';a.req('users','POST',{'name':'Other technician','email':other_tech_email,'password':'Another Test Password!2026','role':'technician'},201);strangertech=Client();strangertech.login(other_tech_email,'Another Test Password!2026');post(strangertech,'work/'+str(pm)+'/logs',{'minutes':1,'hourly_rate':0,'note':'Unauthorized'},403)
policy=next(x for x in data()['policies'] if x['priority']=='Tinggi');a.req('ops/policies/Tinggi','PUT',{'priority':'Tinggi','response_hours':10,'resolve_hours':2,'version':int(policy['version'])},422)
post(a,'suppliers',{'code':'X'*65,'name':'Too long code','email':'','phone':'','active':True},422)
# Atomic import rolls all rows back on a later duplicate.
payload={**r,'id':'','points':None,'code':'IMPORT-'+suffix,'name':'Imported manual room'}
count_before=len(a.req('rooms')['rooms']);a.req('rooms/import','POST',{'rooms':[payload,payload]},409);check(len(a.req('rooms')['rooms'])==count_before,'failed import is atomic with no partial rows');a.req('rooms/import','POST',{'rooms':[payload]},201);check(any(x['code']==payload['code'] for x in a.req('rooms')['rooms']),'valid batch room import persisted')
from pathlib import Path
fixtures=Path('frontend/tests/fixtures');fixtures.mkdir(parents=True,exist_ok=True)
(fixtures/'suite.json').write_text(json.dumps({'ops':data(),'boot':a.req('bootstrap')}))
print(f'{hc.count} operation assertions passed.')
