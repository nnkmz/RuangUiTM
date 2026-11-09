import uuid
import http_client as hc
from http_client import Client,check,ADMIN,PASSWORD
a=Client();a.req('rooms',expected=401);a.req('csrf');a.req('login','POST',{'email':ADMIN,'password':PASSWORD},expected=419,csrf=False);a.req('login','POST',{'email':ADMIN,'password':'wrong password'},expected=401)
a.login(ADMIN,PASSWORD);boot=a.req('bootstrap');check(len(boot['rooms'])>=96,'demo seeded in MariaDB')
r=next(x for x in boot['rooms'] if x['building']=='AKD' and x['floor']=='01' and x['code']=='01-11');check(r['area']==72,'server computes L-shaped room area')
suffix=uuid.uuid4().hex[:8]
for role in ['viewer','manager']:
 a.req('users','POST',{'name':role,'email':role+suffix+'@example.test','password':'Role Test Password!2026','role':role},201)
v=Client();v.login('viewer'+suffix+'@example.test','Role Test Password!2026');v.req('rooms','POST',r,403);v.req('audit',expected=403);v.req('users',expected=403)
m=Client();m.login('manager'+suffix+'@example.test','Role Test Password!2026');m.req('users',expected=403);m.req('buildings','POST',{'code':'NO','name':'Forbidden','campus':'SA'},403)
new={**r,'code':'TEST-'+suffix,'name':'Integration manual room','points':None,'manual':25,'capacity':4,'occupants':0,'id':''};new=m.req('rooms','POST',new,201)['room'];check(new['area'] is None and new['manual']==25,'manual-only room persisted')
id=new['id'];old={**new};updated=m.req('rooms/'+id,'PUT',{**new,'name':'Updated server room','manual':30})['room'];check(updated['version']==2,'version increments on update');m.req('rooms/'+id,'PUT',old,409)
a.req('rooms','POST',{**new,'id':'','points':[[0,0],[1,0],[1,1],[0,1]]},422)
a.req('rooms','POST',{**new,'code':'OUT-'+suffix,'points':[[0,0],[100,0],[1,1]]},422)
a.req('rooms','POST',{**new,'code':'BOW-'+suffix,'points':[[0,0],[4,4],[0,4],[4,0]]},422)
# Connect an actual boundary in the 9m² recess of the L-shaped room.
updated=m.req('rooms/'+id,'PUT',{**updated,'points':[[27,18],[30,18],[30,21],[27,21]]})['room'];check(updated['area']==9,'new room geometry and area persisted')
check(any(x['id']==id and x['area']==9 for x in a.req('bootstrap')['rooms']),'record shared across independent sessions')
m.req('rooms/'+id,'DELETE',{'version':updated['version'],'reason':'Integration cleanup'});check(all(x['id']!=id for x in a.req('rooms')['rooms']),'archived room hidden from active list')
entries=a.req('audit')['entries'];check(any(x['entity_id']==id and x['action']=='archive' for x in entries),'archive audit committed')
check(all('password_hash' not in (x['after_data'] or '') for x in entries),'audit omits password hashes')
# Password whitespace is significant and invalidates sessions after change.
a.req('password','POST',{'current':PASSWORD,'password':'  New Integration Pass!2026  '});a.req('me');a.req('password','POST',{'current':'  New Integration Pass!2026  ','password':PASSWORD})
users=a.req('users')['users'];viewer=next(u for u in users if u['email']=='viewer'+suffix+'@example.test');a.req('users/'+str(viewer['id']),'PATCH',{'role':'viewer','active':False});v.req('rooms',expected=401)
a.req('users/'+str(boot['user']['id']),'PATCH',{'role':'viewer','active':True},422)
a.req('floors','POST',{'building':'AKD','code':'T'+suffix,'name':'Integration floor','width':20,'height':10},201)
a.req('logout','POST',{});a.req('rooms',expected=401)
print(f'{hc.count} integration assertions passed.')
