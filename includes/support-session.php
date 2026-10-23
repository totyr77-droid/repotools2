<?php
declare(strict_types=1);
// A context chooses a separate cookie; it is never an authentication credential.
function support_context(): string {
    $value=$_GET['support']??$_SERVER['HTTP_X_REPO_SUPPORT']??null;
    if($value===null){
        $ref=parse_url($_SERVER['HTTP_REFERER']??'');
        if(is_array($ref)&&strtolower($ref['host']??'')===strtolower(explode(':',$_SERVER['HTTP_HOST']??'')[0])){parse_str($ref['query']??'',$query);$value=$query['support']??null;}
    }
    if($value!==null&&$value!=='exit'&&(!is_string($value)||!preg_match('/^[a-f0-9]{32}$/D',$value)))support_reject('El acceso como usuario no es válido.',401);
    return is_string($value)&&preg_match('/^[a-f0-9]{32}$/D',$value)?$value:'';
}
function support_active(): bool {return support_context()!=='';}
function support_effective_user(array $actor): ?array {
    if(!support_active())return $actor;
    $state=$_SESSION['support']??null;
    if(!$state||$actor['role']!=='manager'||(int)$state['until']<time())return null;
    $q=database()->prepare('SELECT id,name,public_name,email,company,role,auth_version FROM users WHERE id=?');$q->execute([$state['target']]);$target=$q->fetch(PDO::FETCH_ASSOC);
    if(!$target||(int)$target['auth_version']!==(int)$state['version'])return null;
    $GLOBALS['repo_support_actor']=$actor;$GLOBALS['repo_support_target']=$target;
    return $target;
}
function support_reject(string $message,int $status=403): never {
    http_response_code($status);header('Cache-Control: no-store');
    if($status===401&&!str_contains($_SERVER['SCRIPT_NAME']??'','/api/')){header('Content-Type: text/html; charset=utf-8');echo '<!doctype html><html lang="es"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="css/library.css"><main class="library-main"><h1>Acceso finalizado</h1><p>'.htmlspecialchars($message,ENT_QUOTES,'UTF-8').'</p><a class="library-secondary" href="reportes.php?queue=1&amp;view=users&amp;support=exit">Volver a mi cuenta</a></main></html>';exit;}
    header('Content-Type: application/json; charset=utf-8');echo json_encode(['error'=>$message],JSON_UNESCAPED_UNICODE);exit;
}
