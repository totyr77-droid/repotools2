<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/stock.php';
require_once __DIR__.'/../includes/public-link-aliases.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
try{
    if(!current_user())throw new CatalogException('Iniciá sesión.',401);
    if($_SERVER['REQUEST_METHOD']!=='POST'||!valid_csrf())throw new CatalogException('Solicitud no válida.',403);
    $db=database();$uid=(int)current_user()['id'];$kind=$_POST['kind']??'';$target=$_POST['target']??'';
    if(!is_string($kind)||!is_string($target))throw new CatalogException('Enlace no válido.',422);
    $allowed=false;
    if($kind==='p'&&preg_match('/^[0-9]{8}$/D',$target)){$p=stock_detail($db,$target,$uid);$allowed=empty($p['available'])&&(!empty($p['can_edit'])||!empty($p['can_print']));}
    elseif($kind==='s'){$q=$db->prepare('SELECT id FROM stock_shares WHERE token=? AND owner_id=? AND active=1');$q->execute([$target,$uid]);$allowed=(bool)$q->fetchColumn();}
    elseif($kind==='b'){$q=$db->prepare('SELECT source_library_id FROM library_shares WHERE token=? AND owner_id=?');$q->execute([$target,$uid]);$allowed=(bool)$q->fetchColumn();}
    if($kind==='e'&&preg_match('/^[0-9]{8}(?:,[0-9]{8}){0,99}$/D',$target)){
        $codes=array_values(array_unique(explode(',',$target)));sort($codes,SORT_STRING);$target=implode(',',$codes);$allowed=true;
        foreach($codes as $code){$p=stock_detail($db,$code,$uid);$label=stock_label($db,$code);if(!stock_is_admin($db,$uid)&&empty($p['can_edit'])&&(empty($p['available'])||(int)($label['reserved_by']??0)!==$uid)){$allowed=false;break;}}
    }
    if(!$allowed)throw new CatalogException('No podés compartir este enlace.',403);
    $path=$kind.'/'.public_link_alias($db,$kind,$target);
    $host=strtolower(explode(':',$_SERVER['HTTP_HOST']??'')[0]);
    echo json_encode(['path'=>$path,'url'=>in_array($host,['repotools.online','www.repotools.online'],true)?'https://repotools.online/'.$path:$path]);
}catch(Throwable $e){http_response_code($e instanceof CatalogException?$e->status:500);echo json_encode(['error'=>$e instanceof CatalogException?$e->getMessage():'No pudimos crear el enlace.']);}
