<?php
declare(strict_types=1);
namespace Fasiliti;
final class Geometry {
 public const EPS=0.0000001;
 public static function signed(array $p):float{$s=0.;foreach($p as $i=>$a){$b=$p[($i+1)%count($p)];$s+=$a[0]*$b[1]-$b[0]*$a[1];}return $s/2;}
 public static function area(array $p):float{return abs(self::signed($p));}
 private static function cross(array $a,array $b,array $c):float{return ($b[0]-$a[0])*($c[1]-$a[1])-($b[1]-$a[1])*($c[0]-$a[0]);}
 private static function on(array $p,array $a,array $b):bool{return abs(self::cross($a,$b,$p))<self::EPS&&$p[0]>=min($a[0],$b[0])-self::EPS&&$p[0]<=max($a[0],$b[0])+self::EPS&&$p[1]>=min($a[1],$b[1])-self::EPS&&$p[1]<=max($a[1],$b[1])+self::EPS;}
 private static function intersect(array $a,array $b,array $c,array $d,bool $strict=false):bool {
  $x=self::cross($a,$b,$c);$y=self::cross($a,$b,$d);$z=self::cross($c,$d,$a);$w=self::cross($c,$d,$b);
  if((($x>self::EPS&&$y< -self::EPS)||($x< -self::EPS&&$y>self::EPS))&&(($z>self::EPS&&$w< -self::EPS)||($z< -self::EPS&&$w>self::EPS)))return true;
  return !$strict&&(self::on($c,$a,$b)||self::on($d,$a,$b)||self::on($a,$c,$d)||self::on($b,$c,$d));
 }
 public static function inside(array $p,array $poly):bool {
  $inside=false;$n=count($poly);for($i=0,$j=$n-1;$i<$n;$j=$i++){$a=$poly[$i];$b=$poly[$j];if(self::on($p,$a,$b))return false;if(($a[1]>$p[1])!==($b[1]>$p[1])&&$p[0]<($b[0]-$a[0])*($p[1]-$a[1])/($b[1]-$a[1])+$a[0])$inside=!$inside;}return $inside;
 }
 public static function validate(mixed $p,float $width,float $height):?array {
  if($p===null)return null;
  if(!is_array($p)||!array_is_list($p)||count($p)<3||count($p)>200)fail(422,'Sempadan memerlukan 3 hingga 200 bucu.');
  foreach($p as &$v){if(!is_array($v)||!array_is_list($v)||count($v)!==2)fail(422,'Setiap bucu memerlukan koordinat X dan Y.');foreach($v as $n)if((!is_int($n)&&!is_float($n))||!is_finite((float)$n))fail(422,'Koordinat tidak sah.');$v=[round((float)$v[0],4),round((float)$v[1],4)];if($v[0]<0||$v[1]<0||$v[0]>$width||$v[1]>$height)fail(422,'Sempadan ruang di luar dimensi tingkat.');}unset($v);
  $n=count($p);for($i=0;$i<$n;$i++){if($p[$i]===$p[($i+1)%$n])fail(422,'Bucu berturutan tidak boleh sama.');for($j=$i+1;$j<$n;$j++){if($j===$i+1||($i===0&&$j===$n-1))continue;if(self::intersect($p[$i],$p[($i+1)%$n],$p[$j],$p[($j+1)%$n]))fail(422,'Sempadan bersilang sendiri atau menyentuh bucu bukan bersebelahan.');}}
  if(self::area($p)<0.01)fail(422,'Luas poligon minimum 0.01 m².');return $p;
 }
 public static function overlap(array $a,array $b):bool {
  foreach($a as $i=>$v)foreach($b as $j=>$w)if(self::intersect($v,$a[($i+1)%count($a)],$w,$b[($j+1)%count($b)],true))return true;
  foreach($a as $v)if(self::inside($v,$b))return true;foreach($b as $v)if(self::inside($v,$a))return true;
  // Inward edge samples detect coincident polygons without treating shared walls as overlap.
  foreach([[$a,$b],[$b,$a]] as [$p,$q]){$sign=self::signed($p)>0?1:-1;foreach($p as $i=>$v){$w=$p[($i+1)%count($p)];$dx=$w[0]-$v[0];$dy=$w[1]-$v[1];$len=hypot($dx,$dy);$sample=[($v[0]+$w[0])/2-$sign*$dy/$len*0.00001,($v[1]+$w[1])/2+$sign*$dx/$len*0.00001];if(self::inside($sample,$p)&&self::inside($sample,$q))return true;}}
  return false;
 }
}
