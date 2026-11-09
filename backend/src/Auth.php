<?php
declare(strict_types=1);
namespace Fasiliti;
function startSession():void {
 ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.gc_maxlifetime','28800');session_name('fasiliti_session');session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>env('COOKIE_SECURE','1')==='1','httponly'=>true,'samesite'=>'Strict']);if(!session_start())fail(503,'Storan sesi server tidak tersedia.');
 $_SESSION['csrf']??=bin2hex(random_bytes(32));
}
function csrf():void {$v=$_SERVER['HTTP_X_CSRF_TOKEN']??'';if(!is_string($v)||!hash_equals($_SESSION['csrf'],$v))fail(419,'Sesi borang tamat. Muat semula halaman.');}
function user():array {
 if(empty($_SESSION['uid']))fail(401,'Sila log masuk.');
 if(time()-($_SESSION['last']??0)>3600||time()-($_SESSION['created']??0)>28800){unset($_SESSION['uid']);fail(401,'Sesi tamat. Sila log masuk semula.');}
 $u=query('SELECT id,name,email,role,active,auth_version FROM users WHERE id=?',[$_SESSION['uid']])->fetch();
 if(!$u||!$u['active']||(int)$u['auth_version']!==($_SESSION['auth_version']??0)){unset($_SESSION['uid']);fail(401,'Sesi tidak sah.');}$_SESSION['last']=time();unset($u['active'],$u['auth_version']);$u['id']=(int)$u['id'];return $u;
}
function allow(array $roles):array {$u=user();if(!in_array($u['role'],$roles,true))fail(403,'Peranan anda tidak dibenarkan melakukan tindakan ini.');return $u;}
function login(array $d):array {
 $email=strtolower(text($d,'email',190));$password=passwordValue($d,'password');$fingerprint=hash('sha256',($_SERVER['REMOTE_ADDR']??'local').'|'.$email);
 $count=query('SELECT COUNT(*) FROM login_attempts WHERE fingerprint=? AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 MINUTE)',[$fingerprint])->fetchColumn();if($count>=10)fail(429,'Terlalu banyak cubaan. Cuba lagi selepas 15 minit.');
 query('INSERT INTO login_attempts(fingerprint) VALUES(?)',[$fingerprint]);
 $u=query('SELECT * FROM users WHERE email=?',[$email])->fetch();$dummy='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
 $valid=password_verify($password,$u['password_hash']??$dummy);if(!$u||!$valid||!$u['active'])fail(401,'E-mel atau kata laluan tidak sah.');
 session_regenerate_id(true);$_SESSION=['uid'=>(int)$u['id'],'auth_version'=>(int)$u['auth_version'],'created'=>time(),'last'=>time(),'csrf'=>bin2hex(random_bytes(32))];
 query('DELETE FROM login_attempts WHERE fingerprint=?',[$fingerprint]);query('DELETE FROM login_attempts WHERE created_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY)');audit('login','user',(string)$u['id']);return ['user'=>user(),'csrf'=>$_SESSION['csrf']];
}
