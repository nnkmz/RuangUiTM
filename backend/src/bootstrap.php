<?php
declare(strict_types=1);
namespace Fasiliti;
function env(string $name, string $default = ''): string {
 static $file=null;if($file===null){$file=[];$path=dirname(__DIR__,2).'/.env';if(is_file($path)){foreach(file($path,FILE_IGNORE_NEW_LINES) as $line){$line=trim($line);if($line===''||str_starts_with($line,'#')||!str_contains($line,'='))continue;[$key,$value]=explode('=',$line,2);$value=trim($value);if(strlen($value)>=2&&(($value[0]==='"'&&str_ends_with($value,'"'))||($value[0]==="'"&&str_ends_with($value,"'"))))$value=substr($value,1,-1);$file[trim($key)]=$value;}}}
 $v=getenv($name);return $v===false?($file[$name]??$default):$v;
}
function db(): \PDO {
 static $db;
 if (!$db) {
  $db = new \PDO('mysql:host='.env('DB_HOST','127.0.0.1').';port='.env('DB_PORT','3306').';dbname='.env('DB_DATABASE','fasiliti').';charset=utf8mb4',env('DB_USERNAME','fasiliti'),env('DB_PASSWORD'),[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION,\PDO::ATTR_DEFAULT_FETCH_MODE=>\PDO::FETCH_ASSOC,\PDO::ATTR_EMULATE_PREPARES=>false]);
  $db->exec("SET time_zone = '+00:00'");
 }
 return $db;
}
function query(string $sql, array $params=[]): \PDOStatement { $s=db()->prepare($sql);$s->execute($params);return $s; }
final class HttpError extends \RuntimeException { public function __construct(public int $status,string $message,public array $details=[]) {parent::__construct($message);} }
function fail(int $status,string $message,array $details=[]): never {throw new HttpError($status,$message,$details);}
function json(mixed $data,int $status=200): never {http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);exit;}
function body(): array {
 $raw=file_get_contents('php://input');if(strlen($raw)>262144)fail(413,'Saiz permintaan melebihi 256 KB.');
 try{$d=json_decode($raw,true,64,JSON_THROW_ON_ERROR);}catch(\JsonException){fail(422,'JSON tidak sah.');}
 if(!is_array($d)||array_is_list($d)&&$d!==[])fail(422,'Objek JSON diperlukan.');return $d;
}
function text(array $d,string $key,int $max,bool $required=true,string $default=''): string {
 if(isset($d[$key])&&!is_string($d[$key]))fail(422,"Medan $key mesti teks.");$v=trim($d[$key]??$default);
 if(($required&&$v==='')||strlen($v)>$max)fail(422,"Medan $key diperlukan / maksimum $max bait.");return $v;
}
function number(array $d,string $key,float $min=0,float $max=1000000): float {
 $v=$d[$key]??0;if(!is_int($v)&&!is_float($v))fail(422,"Medan $key mesti nombor.");
 if(!is_finite((float)$v)||$v<$min||$v>$max)fail(422,"Nilai $key di luar julat.");return(float)$v;
}
function integer(array $d,string $key,int $min=0,int $max=100000): int {$v=number($d,$key,$min,$max);if(floor($v)!==$v)fail(422,"Medan $key mesti integer.");return(int)$v;}
function choice(array $d,string $key,array $choices): string {$v=text($d,$key,100);if(!in_array($v,$choices,true))fail(422,"Pilihan $key tidak sah.");return $v;}
function boolean(array $d,string $key):bool {if(!isset($d[$key])||!is_bool($d[$key]))fail(422,"Medan $key mesti boolean.");return $d[$key];}
function audit(string $action,string $entity,string $id,mixed $before=null,mixed $after=null): void {
 query('INSERT INTO audit_logs(actor_id,action,entity_type,entity_id,before_data,after_data) VALUES(?,?,?,?,?,?)',[$_SESSION['uid']??null,$action,$entity,$id,$before===null?null:json_encode($before,JSON_THROW_ON_ERROR),$after===null?null:json_encode($after,JSON_THROW_ON_ERROR)]);
}
function transaction(callable $fn): mixed {if(db()->inTransaction())return $fn();db()->beginTransaction();try{$r=$fn();db()->commit();return $r;}catch(\Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}}

function passwordValue(array $d,string $key):string { $v=$d[$key]??null;if(!is_string($v)||$v===''||strlen($v)>72)fail(422,"Medan $key diperlukan, maksimum 72 bait.");return $v;}
