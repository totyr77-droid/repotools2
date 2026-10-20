<?php
require __DIR__.'/../includes/email-verification.php';
function mail_check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$url='https://repotools.online/verificar-correo.php#verificar='.str_repeat('a',64);
$mail=welcome_email('Nombre <prueba>',$url);$inline=email_embed_logo($mail);
mail_check(substr_count($inline['html'],'href="'.$url.'"')===2,'button and fallback preserve exact fragment/token');
mail_check(str_contains($inline['html'],'src="cid:repotools-official-logo"'),'inline logo');
$attachment=$inline['attachments'][0];$png=base64_decode($attachment['content'],true);
mail_check($png===file_get_contents(__DIR__.'/../img/brand/repotools-horizontal-blanco-1024w.png'),'official bytes unchanged');
$size=getimagesizefromstring($png);mail_check($size[0]===1024&&$size[1]===175,'valid horizontal proportions');
mail_check($attachment['content_id']==='repotools-official-logo'&&$attachment['content_type']==='image/png','correct inline MIME');
mail_check(str_contains($inline['html'],'width="180" height="31"'),'explicit compact dimensions');
mail_check(!str_contains($inline['html'],'Nombre <prueba>'),'escaped account name');
putenv('APP_URL=javascript:invalid');mail_check(email_origin()==='https://repotools.online','invalid origin fallback');
echo "OK: official inline PNG, matching CID/MIME, fixed size, unchanged action/fallback links, escaped name and safe origin.\n";
