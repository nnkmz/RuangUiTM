<?php
declare(strict_types=1);
namespace Fasiliti;
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../src/bootstrap.php';require __DIR__.'/../src/Geometry.php';require __DIR__.'/../src/Rooms.php';
try {
 if(!(int)query("SELECT GET_LOCK('fasiliti_setup',30)")->fetchColumn())throw new \RuntimeException('Setup sedang dijalankan.');
 db()->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
 query("INSERT IGNORE INTO migrations(version) VALUES('001_initial')");
 if(!query("SELECT version FROM migrations WHERE version='002_operations'")->fetch()){db()->exec(file_get_contents(__DIR__.'/../database/002_operations.sql'));query("INSERT INTO migrations(version) VALUES('002_operations')");}
 query("INSERT IGNORE INTO campuses(code,name) VALUES('SA','Kampus Shah Alam')");
 query("INSERT IGNORE INTO buildings(code,campus_code,name) VALUES('AKD','SA','Blok Akademik A')");
 query("INSERT IGNORE INTO floors(building_code,code,name,width_m,height_m) VALUES('AKD','01','Aras 1',42,21)");
 if((int)query('SELECT COUNT(*) FROM users')->fetchColumn()===0){
  $email=strtolower(env('ADMIN_EMAIL'));$password=env('ADMIN_PASSWORD');$name=env('ADMIN_NAME','Pentadbir Sistem');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12||strlen($password)>72||strlen($name)>120)throw new \RuntimeException('Tetapkan ADMIN_EMAIL sah, ADMIN_NAME dan ADMIN_PASSWORD 12–72 bait dalam persekitaran server.');
  query('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),'admin']);audit('setup','system','initial',null,['admin_email'=>$email]);
 }
 if(in_array('--demo',$argv,true)){
  query("INSERT IGNORE INTO buildings(code,campus_code,name) VALUES('PTD','SA','Blok Pentadbiran')");
  foreach(['AKD','PTD'] as $bl)foreach(['G','01','02'] as $fl)query('INSERT IGNORE INTO floors(building_code,code,name,width_m,height_m) VALUES(?,?,?,?,?)',[$bl,$fl,$fl==='G'?'Aras bawah':'Aras '.(int)$fl,42,21]);
  $rows=json_decode(file_get_contents(__DIR__.'/../database/demo-rooms.json'),true,64,JSON_THROW_ON_ERROR);
  foreach($rows as $r){if(query('SELECT id FROM rooms WHERE building_code=? AND floor_code=? AND room_code=?',[$r['building'],$r['floor'],$r['code']])->fetch())continue;writeRoom($r);}
 }
 query("SELECT RELEASE_LOCK('fasiliti_setup')");echo "Setup selesai. Data sedia ada dikekalkan.\n";
}catch(\Throwable $e){fwrite(STDERR,'Setup gagal: '.$e->getMessage()."\n");exit(1);}
