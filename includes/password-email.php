<?php
declare(strict_types=1);
require_once __DIR__.'/email-layout.php';
function reset_email_html(string $url): string {
    return email_layout('Volvé a tu cuenta','<p>Recibimos una solicitud para cambiar tu contraseña. Tocá el botón para elegir una nueva.</p>'.email_button($url,'Cambiar contraseña').'<p>Este enlace vence en <strong>30 minutos</strong> y funciona una sola vez.</p><p>Si no pediste el cambio, ignorá este correo. Tu contraseña seguirá igual.</p><p>Al guardar una nueva contraseña se cerrarán las sesiones anteriores en todos tus dispositivos.</p><p style="font-size:12px;word-break:break-all">Si el botón no funciona: <a href="'.email_escape($url).'">'.email_escape($url).'</a></p>');
}
function account_security_email(string $email): bool {
    $key=getenv('RESEND_API_KEY')?:'';$from=getenv('MAIL_FROM')?:'';$origin=rtrim(getenv('APP_URL')?:'','/');
    if (!$key || !$from || !preg_match('~^https://[^\s]+$~D',$origin)) return false;
    $html=account_security_email_html($origin);
    $curl=curl_init('https://api.resend.com/emails');curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode(['from'=>$from,'to'=>[$email],'subject'=>'Se cambió tu contraseña de Repotools','html'=>$html,'text'=>'Se cambió tu contraseña de Repotools. Las sesiones anteriores en otros dispositivos ya no tienen acceso. Si no hiciste este cambio, recupera tu cuenta: '.$origin.'/recuperar.php'])]);curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);return $status>=200&&$status<300;
}

function account_security_email_html(string $origin): string {
    return email_layout('Contraseña actualizada','<p style="color:#17633d;background:#eaf7ef;padding:12px;border-radius:10px">&#10003; Se cambió la contraseña de tu cuenta. Las sesiones anteriores en otros dispositivos ya no tienen acceso.</p><p>Si no hiciste este cambio, recuperá tu cuenta cuanto antes.</p>'.email_button($origin.'/recuperar.php','Recuperar mi cuenta'));
}
