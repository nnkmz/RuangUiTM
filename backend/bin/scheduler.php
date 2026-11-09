<?php
declare(strict_types=1);
namespace Fasiliti;
if(PHP_SAPI!=='cli')exit(1);
require __DIR__.'/../src/bootstrap.php';require __DIR__.'/../src/Geometry.php';require __DIR__.'/../src/Rooms.php';require __DIR__.'/../src/Operations.php';
try{echo json_encode(scheduleMaintenance(),JSON_THROW_ON_ERROR)."\n";}catch(\Throwable $e){fwrite(STDERR,'Scheduler gagal: '.$e->getMessage()."\n");exit(1);}
