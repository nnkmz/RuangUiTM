<?php
declare(strict_types=1);
namespace Fasiliti;
require __DIR__.'/../src/bootstrap.php';require __DIR__.'/../src/Auth.php';require __DIR__.'/../src/Geometry.php';require __DIR__.'/../src/Rooms.php';require __DIR__.'/../src/Operations.php';
header('X-Content-Type-Options: nosniff');header('Cache-Control: no-store');header('Referrer-Policy: same-origin');
try {
 startSession();$method=$_SERVER['REQUEST_METHOD'];$path=rtrim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),'/');
 if($method==='GET'&&$path==='/api/v1/health'){query('SELECT 1');json(['status'=>'ok']);}
 if($method==='GET'&&$path==='/api/v1/csrf')json(['csrf'=>$_SESSION['csrf']]);
 if(!in_array($method,['GET','HEAD'],true))csrf();
 if($method==='POST'&&$path==='/api/v1/login')json(login(body()));
 if($method==='POST'&&$path==='/api/v1/logout'){if(isset($_SESSION['uid']))audit('logout','user',(string)$_SESSION['uid']);$_SESSION=[];session_destroy();setcookie(session_name(),'',time()-3600,'/', '',env('COOKIE_SECURE','1')==='1',true);json(['ok'=>true]);}
 if($method==='GET'&&$path==='/api/v1/me')json(['user'=>user(),'csrf'=>$_SESSION['csrf']]);
 $u=user();
 operationRoutes($path,$method,$u);
 if($method==='GET'&&$path==='/api/v1/bootstrap')json(['user'=>$u,'locations'=>locations(),'rooms'=>rooms()]);
 if($method==='GET'&&$path==='/api/v1/rooms')json(['rooms'=>rooms()]);
 if($method==='POST'&&$path==='/api/v1/rooms/import'){allow(['admin','manager']);$payload=body();$rows=$payload['rooms']??null;if(!is_array($rows)||!array_is_list($rows)||count($rows)<1||count($rows)>100)fail(422,'Import memerlukan 1–100 rekod setiap kelompok.');foreach($rows as $r)if(!is_array($r))fail(422,'Rekod import tidak sah.');usort($rows,fn($a,$b)=>strcmp(($a['building']??'').'/'.($a['floor']??'').'/'.($a['code']??''),($b['building']??'').'/'.($b['floor']??'').'/'.($b['code']??'')));$count=transaction(function()use($rows){foreach($rows as $r)writeRoom($r);return count($rows);});json(['imported'=>$count],201);}
 if($method==='POST'&&$path==='/api/v1/rooms'){allow(['admin','manager']);json(['room'=>writeRoom(body())],201);}
 if(preg_match('~^/api/v1/rooms/([A-Za-z0-9_-]{1,100})$~',$path,$m)){
  if($method==='PUT'){allow(['admin','manager']);json(['room'=>writeRoom(body(),$m[1])]);}
  if($method==='DELETE'){allow(['admin','manager']);$d=body();$reason=text($d,'reason',500);transaction(function()use($d,$m,$reason){$row=query('SELECT * FROM rooms WHERE id=? AND archived_at IS NULL FOR UPDATE',[$m[1]])->fetch();if(!$row)fail(404,'Ruang tidak ditemui.');if(integer($d,'version',1)!==(int)$row['version'])fail(409,'Rekod berubah. Muat semula.');if(query("SELECT id FROM assets WHERE room_id=? AND active=1 LIMIT 1",[$m[1]])->fetch()||query("SELECT id FROM work_orders WHERE room_id=? AND status NOT IN ('Selesai','Dibatalkan') LIMIT 1",[$m[1]])->fetch()||query("SELECT id FROM bookings WHERE room_id=? AND status IN ('Menunggu','Diluluskan') AND ends>UTC_TIMESTAMP() LIMIT 1",[$m[1]])->fetch())fail(422,'Ruang masih digunakan oleh aset, kerja atau tempahan aktif.');query('UPDATE rooms SET archived_at=UTC_TIMESTAMP(),version=version+1 WHERE id=?',[$m[1]]);audit('archive','room',$m[1],roomFromRow($row),['reason'=>$reason]);});json(['ok'=>true]);}
 }
 if($method==='GET'&&$path==='/api/v1/audit'){allow(['admin','manager','auditor']);$cursor=max(0,(int)($_GET['before']??PHP_INT_MAX));$rows=query('SELECT a.*,u.name AS actor FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_id WHERE a.id<? ORDER BY a.id DESC LIMIT 100',[$cursor])->fetchAll();json(['entries'=>$rows,'next'=>count($rows)===100?(int)end($rows)['id']:null]);}
 if($method==='POST'&&in_array($path,['/api/v1/campuses','/api/v1/buildings','/api/v1/floors'],true)){
  allow(['admin']);$d=body();$code=text($d,'code',32);$name=text($d,'name',150);if(!preg_match('/^[A-Za-z0-9_-]+$/D',$code))fail(422,'Kod lokasi hanya huruf, nombor, - dan _.');
  transaction(function()use($path,$d,$code,$name){if($path==='/api/v1/campuses'){query('INSERT INTO campuses(code,name) VALUES(?,?)',[$code,$name]);$id=$code;}elseif($path==='/api/v1/buildings'){$parent=text($d,'campus',32);query('INSERT INTO buildings(code,name,campus_code) VALUES(?,?,?)',[$code,$name,$parent]);$id=$code;}else{$parent=text($d,'building',32);query('INSERT INTO floors(building_code,code,name,width_m,height_m) VALUES(?,?,?,?,?)',[$parent,$code,$name,number($d,'width',1,1000),number($d,'height',1,1000)]);$id=$parent.'-'.$code;}audit('create','location',$id,null,$d);});json(['locations'=>locations()],201);
 }
 if($method==='GET'&&$path==='/api/v1/users'){allow(['admin']);json(['users'=>query('SELECT id,name,email,role,active FROM users ORDER BY id')->fetchAll()]);}
 if($method==='POST'&&$path==='/api/v1/users'){
  allow(['admin']);$d=body();$name=text($d,'name',120);$email=strtolower(text($d,'email',190));if(!filter_var($email,FILTER_VALIDATE_EMAIL))fail(422,'E-mel tidak sah.');$password=passwordValue($d,'password');if(strlen($password)<12)fail(422,'Kata laluan minimum 12 aksara.');$role=choice($d,'role',['admin','manager','viewer','technician','reporter','auditor']);transaction(function()use($name,$email,$password,$role){query('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);audit('create','user',(string)db()->lastInsertId(),null,['name'=>$name,'email'=>$email,'role'=>$role]);});json(['ok'=>true],201);
 }
 if($method==='PATCH'&&preg_match('~^/api/v1/users/(\d+)$~',$path,$m)){
  allow(['admin']);$d=body();$role=choice($d,'role',['admin','manager','viewer','technician','reporter','auditor']);$active=boolean($d,'active');if((int)$m[1]===$u['id']&&(!$active||$role!=='admin'))fail(422,'Tidak boleh menyahaktif atau menurunkan peranan akaun sendiri.');transaction(function()use($d,$role,$active,$m){$old=query('SELECT id,name,email,role,active FROM users WHERE id=? FOR UPDATE',[$m[1]])->fetch();if(!$old)fail(404,'Pengguna tidak ditemui.');query('UPDATE users SET role=?,active=?,auth_version=auth_version+1 WHERE id=?',[$role,(int)$active,$m[1]]);audit('update','user',$m[1],$old,['role'=>$role,'active'=>$active]);});json(['ok'=>true]);
 }
 if($method==='POST'&&$path==='/api/v1/password'){
  $d=body();$old=passwordValue($d,'current');$new=passwordValue($d,'password');if(strlen($new)<12)fail(422,'Kata laluan minimum 12 aksara.');$row=query('SELECT password_hash FROM users WHERE id=?',[$u['id']])->fetch();if(!password_verify($old,$row['password_hash']))fail(422,'Kata laluan semasa salah.');transaction(function()use($new,$u){query('UPDATE users SET password_hash=?,auth_version=auth_version+1 WHERE id=?',[password_hash($new,PASSWORD_DEFAULT),$u['id']]);audit('password_change','user',(string)$u['id']);});$_SESSION['auth_version']++;session_regenerate_id(true);json(['ok'=>true]);
 }
 fail(404,'Endpoint tidak ditemui.');
}catch(HttpError $e){json(['error'=>$e->getMessage(),'details'=>$e->details],$e->status);}catch(\PDOException $e){$code=$e->errorInfo[1]??null;if($code===1062)json(['error'=>'Kod atau e-mel telah digunakan.'],409);if($code===1452)json(['error'=>'Lokasi induk tidak wujud.'],422);error_log('Database error: '.$e->getCode());json(['error'=>'Ralat pangkalan data. Semak log server.'],503);}catch(\Throwable $e){error_log('Application error: '.get_class($e).': '.$e->getMessage());json(['error'=>'Ralat dalaman server.'],500);}
