<?php
declare(strict_types=1);
require_once __DIR__.'/catalog.php';
require_once __DIR__.'/public-link-aliases.php';
function scan_list_share(PDO $db,array $codes,?callable $factory=null): string {
    if(!$codes||count($codes)>200||!array_is_list($codes))throw new CatalogException('Compartí entre 1 y 200 códigos.',422);
    foreach($codes as $ean)if(!is_string($ean)||!valid_product_ean($ean))throw new CatalogException('La lista contiene un código no válido.',422);
    $payload=json_encode(array_values(array_unique($codes)),JSON_THROW_ON_ERROR);$hash=hash('sha256',$payload);
    $q=$db->prepare('SELECT code FROM scan_list_shares WHERE payload_hash=?');$q->execute([$hash]);if($code=$q->fetchColumn())return $code;
    for($i=0;$i<10;$i++){$code=$factory?$factory():public_link_code();if(!is_string($code)||!preg_match('/^[A-Za-z0-9]{8}$/D',$code))throw new RuntimeException('Alias inválido.');$db->prepare('INSERT INTO scan_list_shares(code,payload_hash,payload) VALUES(?,?,?) ON CONFLICT DO NOTHING')->execute([$code,$hash,$payload]);$q->execute([$hash]);if($stored=$q->fetchColumn())return $stored;}
    throw new RuntimeException('No pudimos crear el enlace.');
}
