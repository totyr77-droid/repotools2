<?php
require __DIR__.'/../includes/branch-address.php';
function check_address(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$point=branch_coordinates(['latitude'=>'-34.6','longitude'=>'-58.4']);
foreach([['latitude'=>[],'longitude'=>'0'],['latitude'=>'91','longitude'=>'0'],['latitude'=>'0','longitude'=>'181'],['latitude'=>'x','longitude'=>'0']] as $invalid){try{branch_coordinates($invalid);throw new RuntimeException('Invalid point accepted');}catch(CatalogException $e){check_address($e->status===422,'Invalid coordinates rejected');}}
$fixture=['features'=>[['properties'=>['osm_key'=>'shop','name'=>'Makro','street'=>'Av. Siempre Viva','housenumber'=>'123','city'=>'Buenos Aires','state'=>'Ciudad Autónoma de Buenos Aires'],'geometry'=>['coordinates'=>[-58.4,-34.6]]]]];
$suggestion=branch_address_suggestion($fixture,$point);check_address($suggestion['address']==='Av. Siempre Viva 123'&&$suggestion['name']==='Makro','Address and local name');
$fixture['features'][0]['properties']['osm_key']='highway';check_address(branch_address_suggestion($fixture,$point)['name']==='','Street name is not supermarket name');
$fixture['features'][0]['geometry']['coordinates']=[0,0];check_address(branch_address_suggestion($fixture,$point)===[],'Distant result ignored');check_address(branch_address_suggestion([],$point)===[],'Missing address supports manual entry');
$dir=sys_get_temp_dir().'/repotools-address-test-'.bin2hex(random_bytes(8));$calls=0;
try{
    $fixture['features'][0]['geometry']['coordinates']=[-58.4,-34.6];
    $fetcher=function($point)use(&$calls,$fixture){$calls++;return $fixture;};
    $first=branch_reverse_address($point,$fetcher,$dir);$second=branch_reverse_address($point,$fetcher,$dir);check_address($first===$second&&$calls===1,'Repeated points use cache');
    try{branch_reverse_address(['latitude'=>-34.61,'longitude'=>-58.4],$fetcher,$dir);throw new RuntimeException('Rate limit bypassed');}catch(CatalogException $e){check_address($e->status===429,'Shared throttle protects provider');}
}finally{foreach(glob($dir.'/*')?:[] as $file)unlink($file);if(is_dir($dir))rmdir($dir);}
echo "OK: coordinates, suggested fields, distant/empty results, cache and rate limit.\n";
