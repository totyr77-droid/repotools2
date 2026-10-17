<?php
require_once __DIR__.'/includes/auth.php';
$kind=$_GET['kind']??'';$alias=$_GET['alias']??'';
header('Cache-Control: no-store');
if(!is_string($kind)||!in_array($kind,['p','s','b','e'],true)||!is_string($alias)||!preg_match('/^[A-Za-z0-9]{8}$/D',$alias)){http_response_code(404);exit('Enlace no disponible.');}
$q=database()->prepare($kind==='e'?'SELECT target FROM label_print_links WHERE code=?':'SELECT target FROM public_link_aliases WHERE kind=? AND code=?');$q->execute($kind==='e'?[$alias]:[$kind,$alias]);$target=$q->fetchColumn();
if(!$target){http_response_code(404);exit('Enlace no disponible.');}
if($kind==='e'){
    $single=$_GET['codigo']??null;
    if($single!==null){if(!is_string($single)||!in_array($single,explode(',',$target),true)){http_response_code(404);exit('Enlace no disponible.');}$target=$single;}
    $publicPrintCodes=$target;
}
// Resolve only to existing application entry points. Their access checks remain authoritative.
$destinations=['p'=>['palet.php','codigo'],'s'=>['stock-compartido.php','token'],'b'=>['biblioteca.php','share'],'e'=>['etiqueta.php','codigos']];
$base=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/enlace.php')),'/');
[$file,$parameter]=$destinations[$kind];
$_GET=[$parameter=>$target];
$_SERVER['SCRIPT_NAME']=$base.'/'.$file;
$publicShareBase=$base.'/';
if($kind==='e')$publicPrintUrl=$base.'/e/'.$alias.(isset($single)?'?codigo='.rawurlencode($single):'');
require __DIR__.'/'.$file;
exit;
