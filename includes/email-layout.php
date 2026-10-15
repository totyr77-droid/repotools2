<?php
declare(strict_types=1);
function email_escape(string $value): string {return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function email_origin(): string {
    $origin=rtrim(getenv('APP_URL')?:'https://repotools.online','/');
    return preg_match('~^https://[^\s<>"\x27]+$~D',$origin)?$origin:'https://repotools.online';
}
function email_button(string $url,string $label): string {
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td bgcolor="#27197F" align="center" style="border-radius:10px;mso-padding-alt:15px 20px"><a href="'.email_escape($url).'" style="display:block;padding:15px 20px;background:#27197F;color:#ffffff;font-weight:bold;text-decoration:none;border-radius:10px">'.email_escape($label).'</a></td></tr></table>';
}
function email_layout(string $title,string $content): string {
    $logo=email_escape(email_origin().'/img/email/logo.png');$heading=email_escape($title);
    return '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><meta name="supported-color-schemes" content="light"><title>'.$heading.'</title></head><body style="margin:0;background:#f5f6fb;color:#172033;font-family:Arial,Helvetica,sans-serif"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f5f6fb"><tr><td align="center" style="padding:20px 12px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px"><tr><td bgcolor="#27197F" style="background:#27197F;padding:22px;border-radius:14px 14px 0 0"><img src="'.$logo.'" width="180" alt="RepoTools" style="display:block;width:180px;max-width:100%;height:auto;border:0;color:#ffffff;font-size:22px;font-weight:bold"></td></tr><tr><td bgcolor="#ffffff" style="background:#ffffff;padding:22px;color:#172033;font-size:15px;line-height:1.6;border-radius:0 0 14px 14px"><h1 style="font-size:23px;line-height:1.3;margin:0 0 16px;color:#27197F">'.$heading.'</h1>'.$content.'</td></tr><tr><td style="padding:16px 8px;text-align:center;font-size:12px;line-height:1.6;color:#626b7a">RepoTools · Escaneá, organizá y gestioná tu stock.<br>No compartas tus enlaces de acceso o verificación.</td></tr></table></td></tr></table></body></html>';
}
