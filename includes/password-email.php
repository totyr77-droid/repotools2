<?php
declare(strict_types=1);
function reset_email_html(string $url): string {
    $link=htmlspecialchars($url,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    return <<<HTML
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><meta name="supported-color-schemes" content="light"><title>Recuperá tu acceso a Repotools</title></head>
<body style="margin:0;padding:0;background:#f4f7fb;color:#172033;font-family:Arial,Helvetica,sans-serif;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">Elegí una nueva contraseña. Tu enlace vence en 30 minutos.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fb;"><tr><td align="center" style="padding:32px 16px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;">
<tr><td style="padding:0 4px 16px;font-size:21px;font-weight:800;letter-spacing:.6px;color:#27187e;">REPOTOOLS</td></tr>
<tr><td style="background:#ffffff;border:1px solid #e1e5ed;border-radius:16px;padding:24px 20px;">
<p style="margin:0 0 12px;font-size:12px;font-weight:700;letter-spacing:.7px;color:#27187e;background:#e9e6f5;display:inline-block;padding:6px 9px;border-radius:6px;">RECUPERAR ACCESO</p>
<h1 style="margin:0 0 14px;font-size:25px;line-height:1.25;color:#27187e;">Volvé a tu cuenta</h1>
<p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#536174;">Recibimos una solicitud para cambiar tu contraseña. Tocá el botón para elegir una nueva.</p>
<table role="presentation" cellpadding="0" cellspacing="0" width="100%"><tr><td align="center" bgcolor="#27187e" style="border-radius:10px;mso-padding-alt:16px 24px;"><a href="$link" style="display:block;padding:14px 20px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;border-radius:10px;">Cambiar contraseña</a></td></tr></table>
<p style="margin:16px 0 24px;font-size:13px;line-height:1.5;text-align:center;color:#626b7a;">Este enlace vence en <strong>30 minutos</strong> y funciona una sola vez.</p>
<div style="border-top:1px solid #eeedf3;padding-top:18px;">
<p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#536174;">¿No pediste este cambio? Podés ignorar este correo. Tu contraseña seguirá igual.</p>
<p style="margin:0;font-size:12px;line-height:1.6;color:#626b7a;">Al guardar una nueva contraseña se cerrarán las sesiones anteriores en todos tus dispositivos.</p>
</div>
</td></tr>
<tr><td style="padding:20px 8px 0;font-size:12px;line-height:1.7;color:#626b7a;">
<p style="margin:0 0 5px;">Si el botón no funciona, copiá este enlace en tu navegador:</p>
<a href="$link" style="color:#626b7a;text-decoration:underline;word-break:break-all;overflow-wrap:anywhere;">$link</a>
<p style="margin:20px 0 0;text-align:center;color:#818797;">Repotools · Escaneá, organizá y gestioná tu stock.</p>
</td></tr></table>
</td></tr></table></body></html>
HTML;
}

function account_security_email(string $email): bool {
    $key=getenv('RESEND_API_KEY')?:'';$from=getenv('MAIL_FROM')?:'';$origin=rtrim(getenv('APP_URL')?:'','/');
    if (!$key || !$from || !preg_match('~^https://[^\s]+$~D',$origin)) return false;
    $html=account_security_email_html($origin);
    $curl=curl_init('https://api.resend.com/emails');curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode(['from'=>$from,'to'=>[$email],'subject'=>'Se cambió tu contraseña de Repotools','html'=>$html,'text'=>'Se cambió tu contraseña de Repotools. Las sesiones anteriores en otros dispositivos ya no tienen acceso. Si no hiciste este cambio, recupera tu cuenta: '.$origin.'/recuperar.php'])]);curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);return $status>=200&&$status<300;
}

function account_security_email_html(string $origin): string {
    $url=htmlspecialchars($origin.'/recuperar.php',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    return '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><meta name="supported-color-schemes" content="light"></head><body style="margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#172033"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fb"><tr><td align="center" style="padding:32px 16px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px"><tr><td style="color:#27187e;font-size:21px;font-weight:bold;padding:0 4px 16px">REPOTOOLS</td></tr><tr><td style="background:white;border:1px solid #e1e5ed;border-radius:16px;padding:24px 20px"><h1 style="font-size:24px;color:#27187e;margin-top:0">Contraseña actualizada</h1><p style="line-height:1.6;color:#17633d;background:#eaf7ef;border:1px solid #c6e8d3;border-radius:10px;padding:12px 14px">&#10003; Se cambió la contraseña de tu cuenta. Las sesiones anteriores en otros dispositivos ya no tienen acceso.</p><p style="line-height:1.6">Si no hiciste este cambio, recupera tu cuenta cuanto antes.</p><a href="'.$url.'" style="display:block;background:#27187e;color:white;text-align:center;padding:15px;border-radius:10px;text-decoration:none;font-weight:bold">Recuperar mi cuenta</a></td></tr></table></td></tr></table></body></html>';
}
