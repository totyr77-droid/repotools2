<?php
declare(strict_types=1);
function public_link_code(): string {$code='';$alphabet='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';for($i=0;$i<8;$i++)$code.=$alphabet[random_int(0,61)];return $code;}
function public_link_alias(PDO $db,string $kind,string $target,?callable $factory=null):string {
    if(!in_array($kind,['p','s','b','e'],true))throw new InvalidArgumentException('Tipo de enlace no válido.');
    $print=$kind==='e';
    $q=$db->prepare($print?'SELECT code FROM label_print_links WHERE target=?':'SELECT code FROM public_link_aliases WHERE kind=? AND target=?');$params=$print?[$target]:[$kind,$target];$q->execute($params);
    if($code=$q->fetchColumn())return $code;
    for($attempt=0;$attempt<10;$attempt++){
        $code=$factory?$factory():'';
        if(!$factory)$code=public_link_code();
        if(!is_string($code)||!preg_match('/^[A-Za-z0-9]{8}$/D',$code))throw new RuntimeException('Alias no válido.');
        $insert=$db->prepare($print?'INSERT INTO label_print_links(code,target) VALUES(?,?) ON CONFLICT DO NOTHING':'INSERT INTO public_link_aliases(code,kind,target) VALUES(?,?,?) ON CONFLICT DO NOTHING');$insert->execute($print?[$code,$target]:[$code,$kind,$target]);
        $q->execute($params);if($stored=$q->fetchColumn())return $stored;
    }
    throw new RuntimeException('No pudimos generar el enlace. Intentá nuevamente.');
}
