<?php
declare(strict_types=1);
namespace Fasiliti;
require __DIR__.'/../src/bootstrap.php';
$source=env('DB_DATABASE','fasiliti');$target=env('RESTORE_DATABASE','fasiliti_restore');foreach([$source,$target] as $name)if(!preg_match('/^[A-Za-z0-9_]+$/D',$name))throw new \RuntimeException('Invalid test database name.');
$tables=query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=? ORDER BY TABLE_NAME',[$source])->fetchAll(\PDO::FETCH_COLUMN);if(!$tables)throw new \RuntimeException('Source database empty.');
foreach($tables as $table){if(!preg_match('/^[A-Za-z0-9_]+$/D',$table))throw new \RuntimeException('Invalid table name.');$a=query("SELECT * FROM `$source`.`$table`")->fetchAll();$b=query("SELECT * FROM `$target`.`$table`")->fetchAll();$hash=static function(array $rows):array{$out=array_map(fn($r)=>hash('sha256',json_encode($r,JSON_THROW_ON_ERROR)),$rows);sort($out);return $out;};if($hash($a)!==$hash($b))throw new \RuntimeException('Restored contents mismatch: '.$table);}
echo json_encode(['verified_tables'=>count($tables),'all_row_contents_match'=>true],JSON_THROW_ON_ERROR);
