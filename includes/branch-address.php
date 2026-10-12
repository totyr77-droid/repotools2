<?php
declare(strict_types=1);
require_once __DIR__.'/catalog.php';

function branch_coordinates(array $input): array {
    $result=[];
    foreach(['latitude'=>90,'longitude'=>180] as $field=>$limit){
        $value=$input[$field]??null;
        if(!is_string($value)||!is_numeric($value)||!is_finite((float)$value)||abs((float)$value)>$limit)throw new CatalogException('Selecciona un punto válido en el mapa.',422);
        $result[$field]=(float)$value;
    }
    return $result;
}
function branch_address_suggestion(array $json,array $point): array {
    $feature=$json['features'][0]??[];$p=$feature['properties']??[];$c=$feature['geometry']['coordinates']??[];
    if(!is_array($p)||!is_array($c)||count($c)<2||!is_numeric($c[0])||!is_numeric($c[1]))return [];
    $distance=6371000*2*asin(min(1,sqrt(sin(deg2rad((float)$c[1]-$point['latitude'])/2)**2+cos(deg2rad($point['latitude']))*cos(deg2rad((float)$c[1]))*sin(deg2rad((float)$c[0]-$point['longitude'])/2)**2)));
    if($distance>1000)return [];
    $name=($p['osm_key']??'')==='shop'?catalog_text($p['name']??'',100):'';
    return ['name'=>$name,'address'=>trim(catalog_text($p['street']??'',130).' '.catalog_text($p['housenumber']??'',25)),'locality'=>catalog_text($p['city']??$p['town']??$p['village']??$p['district']??'',100),'province'=>catalog_text($p['state']??'',80)];
}
function branch_reverse_address(array $point,?callable $fetcher=null,?string $cacheDirectory=null): array {
    $dir=$cacheDirectory??sys_get_temp_dir().'/repotools-branch-address';
    if(!is_dir($dir)&&!@mkdir($dir,0700,true)&&!is_dir($dir))throw new CatalogException('Completa la dirección manualmente.',503);
    $key=hash('sha256',sprintf('%.5f,%.5f',$point['latitude'],$point['longitude']));$file=$dir.'/'.$key.'.json';
    $lock=fopen($dir.'/lookup.lock','c+');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new CatalogException('Espera un momento o completa la dirección manualmente.',429);
    try{
        if(is_file($file)&&filemtime($file)>time()-86400){$cached=json_decode(file_get_contents($file),true);if(is_array($cached))return $cached;}
        $last=(float)stream_get_contents($lock);if(microtime(true)-$last<2)throw new CatalogException('Espera unos segundos para consultar otra dirección.',429);
        rewind($lock);ftruncate($lock,0);fwrite($lock,(string)microtime(true));fflush($lock);
        if($fetcher)$json=$fetcher($point);
        else{
            $base=getenv('PHOTON_REVERSE_URL')?:'https://photon.komoot.io/reverse';$parts=parse_url($base);
            if(($parts['scheme']??'')!=='https'||!isset($parts['host'])||isset($parts['user'])||isset($parts['query']))throw new CatalogException('Completa la dirección manualmente.',503);
            if(!function_exists('curl_init'))throw new CatalogException('Completa la dirección manualmente.',503);
            $curl=curl_init($base.'?'.http_build_query(['lat'=>$point['latitude'],'lon'=>$point['longitude'],'lang'=>'default','limit'=>1,'radius'=>1]));$body='';
            curl_setopt_array($curl,[CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>7,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_USERAGENT=>'Repotools/1.0 (https://repotools.online)',CURLOPT_HTTPHEADER=>['Accept: application/json'],CURLOPT_WRITEFUNCTION=>static function($curl,string $chunk)use(&$body):int{if(strlen($body)+strlen($chunk)>200000)return 0;$body.=$chunk;return strlen($chunk);}]);
            $ok=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
            if($ok===false||$status!==200)throw new CatalogException('No pudimos sugerir la dirección. Puedes completarla manualmente.',503);
            $json=json_decode($body,true);if(!is_array($json))throw new CatalogException('Completa la dirección manualmente.',503);
        }
        $result=branch_address_suggestion($json,$point);file_put_contents($file,json_encode($result),LOCK_EX);
        // Bound temporary cache; no user identity is stored.
        $files=glob($dir.'/*.json');if(count($files)>1000){usort($files,fn($a,$b)=>filemtime($a)<=>filemtime($b));foreach(array_slice($files,0,count($files)-1000) as $old)@unlink($old);}
        return $result;
    }finally{flock($lock,LOCK_UN);fclose($lock);}
}
