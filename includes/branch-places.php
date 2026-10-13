<?php
declare(strict_types=1);
require_once __DIR__.'/branch-address.php';

function branch_point_distance(array $a,array $b): float {
    return 6371000*2*asin(min(1,sqrt(sin(deg2rad($b['latitude']-$a['latitude'])/2)**2+cos(deg2rad($a['latitude']))*cos(deg2rad($b['latitude']))*sin(deg2rad($b['longitude']-$a['longitude'])/2)**2)));
}
function branch_places_parse(array $json,array $point): array {
    $items=[];
    foreach(array_slice($json['features']??[],0,10) as $feature){
        $p=$feature['properties']??[];$c=$feature['geometry']['coordinates']??[];
        if(!is_array($p)||count($c)<2||!is_numeric($c[0])||!is_numeric($c[1]))continue;
        $where=['latitude'=>(float)$c[1],'longitude'=>(float)$c[0]];
        if(abs($where['latitude'])>90||abs($where['longitude'])>180||branch_point_distance($point,$where)>250)continue;
        $name=catalog_text($p['name']??'',100);if($name==='')continue;
        $items[]=['name'=>$name,'address'=>trim(catalog_text($p['street']??'',130).' '.catalog_text($p['housenumber']??'',25)),
            'locality'=>catalog_text($p['city']??$p['town']??$p['village']??'',100),'province'=>catalog_text($p['state']??'',80),
            ...$where,'distance'=>(int)round(branch_point_distance($point,$where))];
    }
    return $items;
}
function branch_places(array $point,?callable $fetcher=null,?string $directory=null): array {
    $key=trim(getenv('GEOAPIFY_API_KEY')?:'');
    if(!$fetcher&&$key==='')return ['places'=>[],'configured'=>false];
    $dir=$directory??sys_get_temp_dir().'/repotools-branch-places';
    if(!is_dir($dir)&&!@mkdir($dir,0700,true)&&!is_dir($dir))throw new CatalogException('Puedes completar el local manualmente.',503);
    $file=$dir.'/'.hash('sha256',sprintf('%.4f,%.4f',$point['latitude'],$point['longitude'])).'.json';
    $lock=fopen($dir.'/lookup.lock','c+');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new CatalogException('Espera un momento antes de buscar otro local.',429);
    try{
        if(is_file($file)&&filemtime($file)>time()-604800){$cached=json_decode(file_get_contents($file),true);if(is_array($cached))return ['places'=>$cached,'configured'=>true];}
        $state=json_decode(stream_get_contents($lock),true)?:[];$today=gmdate('Y-m-d');
        $count=($state['day']??'')===$today?(int)($state['count']??0):0;
        if(microtime(true)-(float)($state['last']??0)<1||$count>=2800)throw new CatalogException('La búsqueda está ocupada. Puedes cargar el local manualmente.',429);
        rewind($lock);ftruncate($lock,0);fwrite($lock,json_encode(['day'=>$today,'count'=>$count+1,'last'=>microtime(true)]));fflush($lock);
        if($fetcher)$json=$fetcher($point);
        else{
            if(!function_exists('curl_init'))throw new CatalogException('Puedes completar el local manualmente.',503);
            $url='https://api.geoapify.com/v2/places?'.http_build_query(['categories'=>'commercial','filter'=>'circle:'.$point['longitude'].','.$point['latitude'].',200','bias'=>'proximity:'.$point['longitude'].','.$point['latitude'],'limit'=>10,'lang'=>'es','apiKey'=>$key]);
            $curl=curl_init($url);$body='';
            curl_setopt_array($curl,[CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>8,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_HTTPHEADER=>['Accept: application/json'],CURLOPT_WRITEFUNCTION=>static function($curl,string $chunk)use(&$body):int{if(strlen($body)+strlen($chunk)>200000)return 0;$body.=$chunk;return strlen($chunk);}]);
            $ok=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
            if($ok===false||$status!==200)throw new CatalogException('No pudimos buscar el local. Puedes completarlo manualmente.',503);
            $json=json_decode($body,true);if(!is_array($json))throw new CatalogException('Puedes completar el local manualmente.',503);
        }
        $items=branch_places_parse($json,$point);file_put_contents($file,json_encode($items),LOCK_EX);
        $files=glob($dir.'/*.json')?:[];if(count($files)>1000){usort($files,fn($a,$b)=>filemtime($a)<=>filemtime($b));foreach(array_slice($files,0,count($files)-1000) as $old)@unlink($old);}
        return ['places'=>$items,'configured'=>true];
    }finally{flock($lock,LOCK_UN);fclose($lock);}
}
