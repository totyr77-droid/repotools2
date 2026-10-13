<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/catalog.php';
require_once __DIR__.'/../includes/relay-photo.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function relay_response(int $status,array $body): never {http_response_code($status);echo json_encode($body,JSON_UNESCAPED_UNICODE);exit;}
$user=current_user();if(!$user)relay_response(401,['error'=>'Inicia sesión para conectar tus dispositivos.']);
$method=$_SERVER['REQUEST_METHOD'];if(!in_array($method,['GET','POST'],true))relay_response(405,['error'=>'Solicitud no válida.']);if($method==='POST'&&!valid_csrf())relay_response(403,['error'=>'Recarga la página para conectar.']);
$uid=(int)$user['id'];$kind=$_SESSION['device_kind'];$hash=hash('sha256',$_SESSION['device_token']);$csrf=csrf_token();session_write_close();
try {
 $db=database();$db->beginTransaction();$db->prepare('DELETE FROM scan_relay_photos WHERE expires_at<=?')->execute([time()]);if($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'){$q=$db->prepare('SELECT auth_version FROM users WHERE id=? FOR UPDATE');$q->execute([$uid]);}
 $q=$db->prepare('SELECT token_hash FROM account_devices WHERE user_id=? AND kind=? AND expires_at>? AND auth_version=(SELECT auth_version FROM users WHERE id=?)');$q->execute([$uid,$kind,time(),$uid]);$active=$q->fetchColumn();if(!$active||!hash_equals($active,$hash))throw new RuntimeException('Tu sesión cambió. Ingresa nuevamente.');
 $q=$db->prepare('SELECT * FROM scan_relays WHERE user_id=?');$q->execute([$uid]);$row=$q->fetch();
 if($row){$q=$db->prepare('SELECT kind,token_hash FROM account_devices WHERE user_id=? AND expires_at>? AND auth_version=?');$q->execute([$uid,time(),$user['auth_version']]);$slots=array_column($q->fetchAll(),'token_hash','kind');if($row['expires_at']<time()||!hash_equals($slots['desktop']??'', $row['desktop_hash'])||($row['mobile_hash']!==''&&!hash_equals($slots['mobile']??'', $row['mobile_hash']))){$db->prepare('DELETE FROM scan_relays WHERE user_id=?')->execute([$uid]);$row=false;relay_drop_photo($db,$uid);}}
 if($method==='POST') {
  $action=$_POST['action']??'';if(!is_string($action))relay_response(422,['error'=>'Solicitud no válida.']);
  if($action==='request') {
   if($kind!=='desktop')throw new RuntimeException('Esta acción se inicia desde la computadora.');$mode=$_POST['mode']??'ean';$context=$_POST['context']??'Escaneo de productos';$flow=$_POST['flow']??'';if(!is_string($flow)||!preg_match('/^[a-f0-9-]{36}$/D',$flow))throw new RuntimeException('Abre nuevamente el escáner.');if(!is_string($mode)||!in_array($mode,['ean','qr','photo_ean','photo_product'],true)||!is_string($context)||mb_strlen($context)>100)throw new RuntimeException('El escaneo no es válido.');
   relay_drop_photo($db,$uid);if($row&&hash_equals($row['desktop_hash'],$hash)){$db->prepare("UPDATE scan_relays SET mode=?,context=?,context_key=?,ready=1,code='',sequence=sequence+1,expires_at=? WHERE user_id=?")->execute([$mode,$context,$flow,time()+1800,$uid]);}
   else {$db->prepare("INSERT INTO scan_relays(user_id,desktop_hash,mobile_hash,mode,context,context_key,ready,code,sequence,expires_at) VALUES(?,?,'',?,?,?,1,'',0,?) ON CONFLICT(user_id) DO UPDATE SET desktop_hash=excluded.desktop_hash,mobile_hash='',mode=excluded.mode,context=excluded.context,context_key=excluded.context_key,ready=1,code='',sequence=0,expires_at=excluded.expires_at")->execute([$uid,$hash,$mode,$context,$flow,time()+1800]);}
  } elseif($action==='accept') {
   if($kind!=='mobile'||!$row)throw new RuntimeException('No hay una computadora esperando.');$db->prepare('UPDATE scan_relays SET mobile_hash=?,expires_at=? WHERE user_id=?')->execute([$hash,time()+1800,$uid]);
  } elseif($action==='disconnect') {
   if($row&&hash_equals($kind==='desktop'?$row['desktop_hash']:$row['mobile_hash'],$hash)){$db->prepare('DELETE FROM scan_relays WHERE user_id=?')->execute([$uid]);relay_drop_photo($db,$uid);}
  } elseif($action==='cancel_photo') {
   $photoFlow=$_POST['flow']??null;
   if(!$row||!is_string($photoFlow)||!hash_equals($row['context_key'],$photoFlow)||!hash_equals($kind==='desktop'?$row['desktop_hash']:$row['mobile_hash'],$hash)||!in_array($row['mode'],['photo_ean','photo_product'],true))throw new RuntimeException('La solicitud de foto ya terminó.');
   relay_drop_photo($db,$uid);
   if($row['mobile_hash']==='')$db->prepare('DELETE FROM scan_relays WHERE user_id=?')->execute([$uid]);
   else $db->prepare("UPDATE scan_relays SET ready=0,code='',mode='ean',context='' WHERE user_id=?")->execute([$uid]);
  } elseif($action==='pause'||$action==='ack') {
   if($kind!=='desktop'||!$row||!hash_equals($row['desktop_hash'],$hash))throw new RuntimeException('La conexión ya no está disponible.');
   if(!is_string($_POST['flow']??null)||!hash_equals($row['context_key'],$_POST['flow']))throw new RuntimeException('Este escaneo ya terminó.');
   if($action==='pause'){relay_drop_photo($db,$uid);$db->prepare("UPDATE scan_relays SET ready=0,code='' WHERE user_id=?")->execute([$uid]);}
   else{$sequence=filter_var($_POST['sequence']??null,FILTER_VALIDATE_INT);if($sequence===false)throw new RuntimeException('Lectura no válida.');$db->prepare('DELETE FROM scan_relay_photos WHERE user_id=? AND sequence=?')->execute([$uid,$sequence]);$db->prepare("UPDATE scan_relays SET code='' WHERE user_id=? AND sequence=?")->execute([$uid,$sequence]);}
  } elseif($action==='send') {
   if($kind!=='mobile'||!$row||!hash_equals($row['mobile_hash'],$hash)||!$row['ready']||$row['code']!=='')throw new RuntimeException('Espera a que la computadora esté lista.');
   $photoMode=in_array($row['mode'],['photo_ean','photo_product'],true);
   if(isset($_POST['flow'])&&(!is_string($_POST['flow'])||!hash_equals($row['context_key'],$_POST['flow'])))throw new RuntimeException('Esta solicitud ya terminó.');
   if(($photoMode||isset($_FILES['photo']))&&!isset($_POST['flow']))throw new RuntimeException('Esta solicitud ya terminó.');
   $photo=relay_uploaded_photo();if($photoMode&&!$photo)throw new RuntimeException('Toma una foto para enviarla a la computadora.');if($row['mode']==='qr'&&$photo)throw new RuntimeException('Este lector espera un QR.');
   $code=$photoMode?'photo':($_POST['code']??'');if(!is_string($code)||strlen($code)>300)throw new RuntimeException('Código no válido.');
   if($row['mode']==='ean'&&!valid_product_ean($code))throw new RuntimeException('El EAN no es válido.');if($row['mode']==='qr'){if(!preg_match('/^[0-9]{8}$/D',$code)){ $parts=parse_url($code);parse_str($parts['query']??'',$query);$expected=parse_url(getenv('APP_URL')?:'https://repotools.online',PHP_URL_HOST);if(!in_array(strtolower($parts['host']??''),[strtolower($expected),strtolower($expected)==='repotools.online'||strtolower($expected)==='www.repotools.online'?'repotools2.onrender.com':strtolower($expected),str_starts_with(strtolower($expected),'www.')?substr(strtolower($expected),4):'www.'.strtolower($expected)],true)||!in_array(strtolower($parts['scheme']??''),['http','https'],true)||isset($parts['user'])||isset($parts['pass'])||basename($parts['path']??'')!=='palet.php'||(!is_string($query['codigo']??null)||!preg_match('/^[0-9]{8}$/D',$query['codigo'])))throw new RuntimeException('Este QR no corresponde a un palet.');$code=$query['codigo'];}}
   relay_drop_photo($db,$uid);
   if($photo)$db->prepare('INSERT INTO scan_relay_photos(user_id,flow,sequence,kind,photo,expires_at) VALUES(?,?,?,?,?,?)')->execute([$uid,$row['context_key'],(int)$row['sequence']+1,$row['mode']==='photo_product'?'product':'ean',$photo,time()+300]);
   $db->prepare('UPDATE scan_relays SET code=?,sequence=sequence+1,expires_at=? WHERE user_id=?')->execute([$code,time()+1800,$uid]);
  } else throw new RuntimeException('Solicitud no válida.');
 }
 $q=$db->prepare('SELECT * FROM scan_relays WHERE user_id=?');$q->execute([$uid]);$row=$q->fetch();$result=['csrf'=>$csrf,'kind'=>$kind,'connected'=>false,'waiting'=>false];
 if($row){$mine=hash_equals($kind==='desktop'?$row['desktop_hash']:$row['mobile_hash'],$hash);$result+=['flow'=>$row['context_key'],'context'=>$row['context'],'mode'=>$row['mode'],'ready'=>(bool)$row['ready']&&($kind==='desktop'||$row['code']===''),'sequence'=>(int)$row['sequence']];$result['waiting']=$kind==='mobile'&&$row['mobile_hash']==='';$result['connected']=$mine&&$row['mobile_hash']!=='';if($kind==='desktop'&&$mine){$result['requested']=true;$result['code']=$row['code'];$photoQuery=$db->prepare('SELECT kind FROM scan_relay_photos WHERE user_id=? AND flow=? AND sequence=? AND expires_at>?');$photoQuery->execute([$uid,$row['context_key'],$row['sequence'],time()]);$photoKind=$photoQuery->fetchColumn();$result['has_photo']=(bool)$photoKind;$result['photo_kind']=$photoKind?:null;}}
 $db->commit();relay_response(200,$result);
}catch(Throwable $e){if(isset($db)&&$db->inTransaction())$db->rollBack();relay_response(($e instanceof RuntimeException&&!$e instanceof PDOException)?409:503,['error'=>($e instanceof RuntimeException&&!$e instanceof PDOException)?$e->getMessage():'No pudimos conectar los dispositivos.']);}
