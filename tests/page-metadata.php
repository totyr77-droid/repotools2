<?php
require __DIR__ . '/../includes/page-metadata.php';
function check_metadata(bool $valid): void { if (!$valid) throw new RuntimeException('Metadata assertion failed'); }
putenv('APP_URL=https://repotools.online');
$cases = [
    '/index.php?tracking=example' => 'https://repotools.online/',
    '/cuenta.php?email=private&token=secret' => 'https://repotools.online/cuenta.php',
    '/s/' . str_repeat('A',22) . '?tracking=example' => 'https://repotools.online/s/' . str_repeat('A',22),
    '/stock-compartido.php?token=' . str_repeat('a',64) . '&other=example' => 'https://repotools.online/stock-compartido.php?token=' . str_repeat('a',64),
    '/palet.php?codigo=12345678&email=private' => 'https://repotools.online/palet.php?codigo=12345678',
    '/tops.php?categoria=reportes' => 'https://repotools.online/tops.php?categoria=reportes',
    '/stock-compartido.php?token[]=bad' => 'https://repotools.online/stock-compartido.php',
];
foreach ($cases as $request=>$expected) { $_SERVER['REQUEST_URI']=$request; check_metadata(repotools_page_metadata()['url']===$expected); }
putenv('APP_URL=http://localhost:8080');$_SERVER['REQUEST_URI']='/';$data=repotools_page_metadata();
check_metadata($data['url']==='https://repotools.online/');check_metadata($data['title']==='Repotools - Herramientas para repositores');
check_metadata($data['image']==='https://repotools.online/img/repotools-og-preview.png');
$size=getimagesize(__DIR__.'/../img/repotools-og-preview.png');check_metadata($size[0]===1200&&$size[1]===630&&$size['mime']==='image/png');
echo "OK: metadata origin, canonical filtering, public URLs, privacy and preview dimensions.\n";
