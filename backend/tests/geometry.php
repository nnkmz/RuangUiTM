<?php
declare(strict_types=1);
require __DIR__.'/../src/bootstrap.php';require __DIR__.'/../src/Geometry.php';
use Fasiliti\Geometry as G;
use Fasiliti\HttpError;
$n=0;
function check(bool $ok,string $message):void {global $n;if(!$ok)throw new RuntimeException($message);$n++;echo "PASS $message\n";}
$r=[[0,0],[6,0],[6,9],[0,9]];$l=[[27,12],[36,12],[36,21],[30,21],[30,18],[27,18]];
check(G::area($r)===54.,'rectangle area 54 m2');check(G::area($l)===72.,'concave L area 72 m2');check(G::area(array_reverse($l))===72.,'winding-independent area');
check(!G::overlap($r,[[6,0],[12,0],[12,9],[6,9]]),'shared wall allowed');
check(G::overlap($r,$r),'coincident polygons rejected');check(G::overlap($r,[[1,1],[2,1],[2,2],[1,2]]),'containment rejected');check(G::overlap($r,[[5,2],[7,2],[7,4],[5,4]]),'crossing interiors rejected');
check(!G::overlap($l,[[27,18],[30,18],[30,21],[27,21]]),'concave recess allowed');
foreach(['self-crossing'=>[[0,0],[4,4],[0,4],[4,0]],'outside-floor'=>[[0,0],[50,0],[1,1]],'duplicate-vertex'=>[[0,0],[1,0],[1,0],[0,1]],'zero-area'=>[[0,0],[1,0],[2,0]]] as $name=>$p){try{G::validate($p,42,21);check(false,$name);}catch(HttpError $e){check($e->status===422,$name.' rejected');}}
check(G::validate(null,42,21)===null,'manual-only room allowed');echo "$n geometry assertions passed.\n";
