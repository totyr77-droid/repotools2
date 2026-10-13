<?php
require __DIR__.'/../includes/branch-places.php';
function expect_places(bool $ok,string $label):void{if(!$ok)throw new RuntimeException($label);}
$point=['latitude'=>-34.6,'longitude'=>-58.4];
$fixture=['features'=>[['properties'=>['name'=>'Local prueba','street'=>'Calle','housenumber'=>'123','city'=>'Ciudad','state'=>'Provincia'],'geometry'=>['coordinates'=>[-58.4,-34.6]]]]];
$items=branch_places_parse($fixture,$point);
expect_places(count($items)===1&&$items[0]['address']==='Calle 123'&&$items[0]['distance']===0,'Local fields and coordinates');
$far=$fixture;$far['features'][0]['geometry']['coordinates']=[0,0];expect_places(branch_places_parse($far,$point)===[],'Distant places ignored');
expect_places(branch_places_parse([],$point)===[],'Missing places');
$dir=sys_get_temp_dir().'/repotools-places-test-'.bin2hex(random_bytes(8));$calls=0;
try{
    $fetcher=function()use(&$calls,$fixture){$calls++;return $fixture;};
    $a=branch_places($point,$fetcher,$dir);$b=branch_places($point,$fetcher,$dir);
    expect_places($a===$b&&$calls===1,'Cached search avoids API credit');
    try{branch_places(['latitude'=>-34.7,'longitude'=>-58.4],$fetcher,$dir);throw new RuntimeException('Throttle failed');}catch(CatalogException $e){expect_places($e->status===429,'Throttle');}
}finally{foreach(glob($dir.'/*')?:[] as $file)unlink($file);if(is_dir($dir))rmdir($dir);}
echo "OK: nearby places, suggested fields, invalid/distant results, cache and throttle.\n";
