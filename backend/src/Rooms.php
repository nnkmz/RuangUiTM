<?php
declare(strict_types=1);
namespace Fasiliti;
function roomFromRow(array $r):array {
 $a=json_decode($r['attributes'],true,64,JSON_THROW_ON_ERROR);
 return array_merge($a,['id'=>$r['id'],'building'=>$r['building_code'],'floor'=>$r['floor_code'],'code'=>$r['room_code'],'name'=>$r['name'],'type'=>$r['room_type'],'category'=>$r['category'],'department'=>$r['department'],'area'=>$r['area_polygon']===null?null:(float)$r['area_polygon'],'manual'=>(float)$r['area_manual'],'capacity'=>(int)$r['capacity'],'occupants'=>(int)$r['occupants'],'status'=>$r['status'],'points'=>$r['polygon']===null?null:json_decode($r['polygon'],true,64,JSON_THROW_ON_ERROR),'source'=>$r['polygon']===null?'Manual':'Poligon','version'=>(int)$r['version'],'archived'=>$r['archived_at'],'updated'=>$r['updated_at']]);
}
function rooms():array{return array_map(__NAMESPACE__.'\\roomFromRow',query('SELECT * FROM rooms WHERE archived_at IS NULL ORDER BY building_code,floor_code,room_code')->fetchAll());}
function validateRoom(array $d):array {
 $r=[];foreach(['building'=>32,'floor'=>32,'code'=>64,'name'=>150,'type'=>64,'department'=>100] as $k=>$max)$r[$k]=text($d,$k,$max);
 $r['category']=choice($d,'category',['OCCUPIABLE','SERV','CIRC','VERT']);$r['status']=choice($d,'status',['Digunakan','Tidak digunakan']);
 $r['manual']=number($d,'manual',0,10000000);$r['capacity']=integer($d,'capacity');$r['occupants']=integer($d,'occupants');$r['height']=number($d,'height',0,100);
 foreach(['division'=>120,'standard'=>64,'finish'=>100,'ceiling'=>100,'pic'=>120,'notes'=>2000] as $k=>$max)$r[$k]=text($d,$k,$max,false);
 $r['prorate']=choice($d,'prorate',['Jabatan','Guna sama tingkat','Guna sama bangunan']);$r['ac']=boolean($d,'ac');$r['accessible']=boolean($d,'accessible');$r['survey']=text($d,'survey',10,false);
 if($r['survey']!==''){$dt=\DateTimeImmutable::createFromFormat('!Y-m-d',$r['survey']);if(!$dt||$dt->format('Y-m-d')!==$r['survey'])fail(422,'Tarikh ukur tidak sah.');}
 $f=query('SELECT * FROM floors WHERE building_code=? AND code=? FOR UPDATE',[$r['building'],$r['floor']])->fetch();if(!$f)fail(422,'Bangunan atau tingkat tidak wujud.');
 $r['points']=Geometry::validate($d['points']??null,(float)$f['width_m'],(float)$f['height_m']);return $r;
}
function writeRoom(array $d,?string $id=null):array {
 return transaction(function()use($d,$id){
  // Floor lock serializes geometry edits and prevents concurrent overlapping inserts.
  $r=validateRoom($d);$old=null;
  if($id!==null){$row=query('SELECT * FROM rooms WHERE id=? AND archived_at IS NULL FOR UPDATE',[$id])->fetch();if(!$row)fail(404,'Ruang tidak ditemui.');$old=roomFromRow($row);if($old['building']!==$r['building']||$old['floor']!==$r['floor'])fail(422,'Pemindahan ruang antara tingkat tidak disokong.');if(integer($d,'version',1)!==$old['version'])fail(409,'Rekod telah dikemas kini pengguna lain. Muat semula data dahulu.');if(query("SELECT id FROM bookings WHERE room_id=? AND status IN ('Menunggu','Diluluskan') AND ends>UTC_TIMESTAMP() AND (attendees>? OR ?<>'Digunakan' OR ?<>'OCCUPIABLE') LIMIT 1",[$id,$r['capacity'],$r['status'],$r['category']])->fetch())fail(422,'Perubahan ruang bercanggah dengan tempahan aktif.');}
  if($r['points']!==null){$rows=query('SELECT id,room_code,polygon FROM rooms WHERE building_code=? AND floor_code=? AND archived_at IS NULL AND polygon IS NOT NULL',[$r['building'],$r['floor']])->fetchAll();foreach($rows as $other){if($other['id']===$id)continue;if(Geometry::overlap($r['points'],json_decode($other['polygon'],true,64,JSON_THROW_ON_ERROR)))fail(422,'Sempadan bertindih dengan ruang '.$other['room_code'].'.');}}
  $id??='rm_'.bin2hex(random_bytes(12));$a=array_diff_key($r,array_flip(['building','floor','code','name','type','category','department','manual','capacity','occupants','status','points']));
  $values=[$r['building'],$r['floor'],$r['code'],$r['name'],$r['type'],$r['category'],$r['department'],$r['points']===null?null:Geometry::area($r['points']),$r['manual'],$r['capacity'],$r['occupants'],$r['status'],$r['points']===null?null:json_encode($r['points'],JSON_THROW_ON_ERROR),json_encode($a,JSON_THROW_ON_ERROR)];
  if($old){$values[]=$id;query('UPDATE rooms SET building_code=?,floor_code=?,room_code=?,name=?,room_type=?,category=?,department=?,area_polygon=?,area_manual=?,capacity=?,occupants=?,status=?,polygon=?,attributes=?,version=version+1 WHERE id=?',$values);}else{$values[]=$id;query('INSERT INTO rooms(building_code,floor_code,room_code,name,room_type,category,department,area_polygon,area_manual,capacity,occupants,status,polygon,attributes,id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',$values);}
  $saved=roomFromRow(query('SELECT * FROM rooms WHERE id=?',[$id])->fetch());audit($old?'update':'create','room',$id,$old,$saved);return $saved;
 });
}
function locations():array {
 return ['campuses'=>query('SELECT code,name FROM campuses ORDER BY code')->fetchAll(),'buildings'=>array_map(fn($b)=>['code'=>$b['code'],'name'=>$b['name'],'campus'=>$b['campus_code']],query('SELECT * FROM buildings ORDER BY code')->fetchAll()),'floors'=>array_map(fn($f)=>['building'=>$f['building_code'],'code'=>$f['code'],'name'=>$f['name'],'width'=>(float)$f['width_m'],'height'=>(float)$f['height_m']],query('SELECT * FROM floors ORDER BY building_code,code')->fetchAll())];
}
