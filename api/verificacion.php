<?php
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/email-verification.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
try{$u=current_user();if(!$u)throw new ReferralError('Ingresá para continuar.',401);if($_SERVER['REQUEST_METHOD']!=='POST')throw new ReferralError('Usá el formulario de Perfil.',405);if(!valid_csrf())throw new ReferralError('Recargá la página.',403);
    if(isset($_POST['email'])&&!is_string($_POST['email']))throw new ReferralError('El formulario no es válido.',422);
    $email=isset($_POST['email'])&&is_string($_POST['email'])?$_POST['email']:null;
    if($email!==null){
        $db=database();$db->beginTransaction();try{referral_lock($db);referral_limit($db,'email-reauth:'.(int)$u['id'],5,900);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
        $q=$db->prepare('SELECT password_hash FROM users WHERE id=?');$q->execute([$u['id']]);$hash=$q->fetchColumn();$password=is_string($_POST['current_password']??null)?$_POST['current_password']:'';
        if(strlen($password)>72||!$hash||!password_verify($password,$hash))throw new ReferralError('No pudimos confirmar tu identidad. Revisá la contraseña actual.',403);
    }
    $result=email_request(database(),(int)$u['id'],$email);
    if($email!==null){echo json_encode(['accepted'=>true,'new_address'=>true]);exit;}
    if($result['needed']&&!$result['sent'])throw new ReferralError('No pudimos enviar el correo. Tu cuenta sigue disponible; intentá más tarde.',503);
    echo json_encode($result,JSON_UNESCAPED_UNICODE);
}catch(ReferralError $e){http_response_code($e->status);echo json_encode(['error'=>$e->getMessage()]);}catch(Throwable $e){http_response_code(503);echo json_encode(['error'=>'No pudimos preparar el correo. Tu cuenta sigue disponible.']);}
