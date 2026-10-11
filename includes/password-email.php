<?php
declare(strict_types=1);
function reset_email_html(string $url): string {
    $link=htmlspecialchars($url,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    return <<<HTML
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Recuperá tu acceso a Repotools</title></head>
<body style="margin:0;padding:0;background:#f5f6fb;color:#172033;font-family:Arial,Helvetica,sans-serif;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">Elegí una nueva contraseña. Tu enlace vence en 30 minutos.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6fb;"><tr><td align="center" style="padding:32px 16px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;">
<tr><td style="padding:0 0 20px;font-size:21px;font-weight:800;letter-spacing:.6px;color:#27187e;">REPOTOOLS</td></tr>
<tr><td style="background:#ffffff;border:1px solid #e1e5ed;border-radius:16px;padding:28px 24px;">
<p style="margin:0 0 12px;font-size:12px;font-weight:700;letter-spacing:.7px;color:#62577e;">RECUPERAR ACCESO</p>
<h1 style="margin:0 0 14px;font-size:25px;line-height:1.25;color:#27187e;">Volvé a tu cuenta</h1>
<p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#536174;">Recibimos una solicitud para cambiar tu contraseña. Tocá el botón para elegir una nueva.</p>
<table role="presentation" cellpadding="0" cellspacing="0" width="100%"><tr><td align="center" bgcolor="#27187e" style="border-radius:10px;mso-padding-alt:16px 24px;"><a href="$link" style="display:block;padding:16px 24px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;border-radius:10px;">Cambiar contraseña</a></td></tr></table>
<p style="margin:16px 0 24px;font-size:13px;line-height:1.5;text-align:center;color:#62577e;">Este enlace vence en <strong>30 minutos</strong> y funciona una sola vez.</p>
<div style="border-top:1px solid #eeedf3;padding-top:18px;">
<p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#536174;">¿No pediste este cambio? Podés ignorar este correo. Tu contraseña seguirá igual.</p>
<p style="margin:0;font-size:12px;line-height:1.6;color:#626b7a;">Al guardar una nueva contraseña se cerrarán las sesiones anteriores en todos tus dispositivos.</p>
</div>
</td></tr>
<tr><td style="padding:20px 8px 0;font-size:12px;line-height:1.7;color:#626b7a;">
<p style="margin:0 0 5px;">Si el botón no funciona, copiá este enlace en tu navegador:</p>
<a href="$link" style="color:#62577e;text-decoration:underline;word-break:break-all;overflow-wrap:anywhere;">$link</a>
<p style="margin:20px 0 0;text-align:center;color:#818797;">Repotools · Escaneá, organizá y gestioná tu stock.</p>
</td></tr></table>
</td></tr></table></body></html>
HTML;
}
