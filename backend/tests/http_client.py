"""HTTP integration tests. Run against an isolated database with demo seed. Never production."""
import os,json,urllib.request,urllib.error,http.cookiejar,uuid
BASE=os.environ.get('TEST_BASE_URL','http://127.0.0.1:8081')+'/api/v1/'
ADMIN=os.environ['TEST_ADMIN_EMAIL'];PASSWORD=os.environ['TEST_ADMIN_PASSWORD']
count=0
class Client:
 def __init__(self):
  self.jar=http.cookiejar.CookieJar();self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar));self.csrf=''
 def req(self,path,method='GET',data=None,expected=200,csrf=True):
  global count
  headers={'Accept':'application/json'}
  if data is not None:headers['Content-Type']='application/json'
  if csrf and method!='GET':headers['X-CSRF-Token']=self.csrf
  r=urllib.request.Request(BASE+path,data=json.dumps(data).encode() if data is not None else None,headers=headers,method=method)
  try:
   response=self.opener.open(r);status=response.status;body=response.read()
  except urllib.error.HTTPError as e:status=e.code;body=e.read()
  try:result=json.loads(body)
  except Exception:raise AssertionError((path,status,body[:300]))
  assert status==expected,(path,method,status,expected,result)
  if 'csrf' in result:self.csrf=result['csrf']
  count+=1;return result
 def login(self,email,password):self.req('csrf');return self.req('login','POST',{'email':email,'password':password})
def check(ok,msg):
 global count
 assert ok,msg
 count+=1;print('PASS',msg)
