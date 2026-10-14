<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';

// Same scope as Stock: owned pallets and accepted collaborations, never every
// user's stock just because the requester has an administrative role.
function stock_access_scope(): string {
    return "p.released=0 AND (p.activated=1 OR p.prepared_until>?) AND (p.owner_id=? OR EXISTS(SELECT 1 FROM pallet_access a WHERE a.pallet_id=p.id AND a.user_id=? AND a.status='accepted'))";
}
function stock_access_args(int $user): array {return [time(),$user,$user];}
function stock_export_rows(PDO $db,int $user,string $type,?int $pallet=null): PDOStatement {
    if(!in_array($type,['stock','palets','vencimientos'],true))throw new CatalogException('Exportación no válida.',422);
    $where=stock_access_scope();$args=stock_access_args($user);
    if($pallet!==null){
        if(!stock_query($db,'SELECT p.id FROM pallets p WHERE '.$where.' AND p.id=?',[...$args,$pallet])->fetchColumn())throw new CatalogException('No tenés acceso a este palet.',403);
        $where.=' AND p.id=?';$args[]=$pallet;
    }
    if($type==='palets')return stock_query($db,"SELECT p.code,p.activated,p.created_at,s.name AS branch,u.public_name,(SELECT COUNT(*) FROM pallet_products b WHERE b.pallet_id=p.id) AS products FROM pallets p LEFT JOIN stock_branches s ON s.id=p.branch_id JOIN users u ON u.id=p.owner_id WHERE {$where} ORDER BY p.code",$args);
    if($type==='vencimientos')$where.=" AND b.expires<>''";
    return stock_query($db,"SELECT b.ean,b.name,b.units,b.expires,p.code,s.name AS branch,c.name AS catalog_product,c.brand AS catalog_brand,c.quantity AS catalog_quantity FROM pallet_products b JOIN pallets p ON p.id=b.pallet_id LEFT JOIN stock_branches s ON s.id=p.branch_id LEFT JOIN products c ON c.ean=b.ean WHERE {$where} ORDER BY p.code,b.ean,b.expires",$args);
}
function stock_csv_cell($value): string {
    if(is_int($value)||is_float($value))return (string)$value;
    $text=(string)($value??'');
    // Prevent spreadsheet formulas in user-provided names and preserve codes.
    return preg_match('/^[\s]*[=+@\-]/u',$text)?"'".$text:$text;
}
function stock_write_csv($output,PDO $db,int $user,string $type,PDOStatement $rows): void {
    $headers=['stock'=>['EAN','Producto','Cantidad','Palet','Sucursal','Vencimiento'],'palets'=>['Palet','Sucursal','Estado','Cantidad de productos','Creación','Propietario público'],'vencimientos'=>['Producto','EAN','Palet','Sucursal','Fecha de vencimiento','Días restantes','Estado']];
    fwrite($output,"\xEF\xBB\xBF");fputcsv($output,$headers[$type],';','"','');$days=stock_warning_days($db,$user);
    while($row=$rows->fetch()){
        $date=$row['expires']??'';$formatted=$date?(new DateTimeImmutable($date))->format('d/m/Y'):'';
        if($type==='palets')$cells=[$row['code'],$row['branch'],$row['activated']?'Activo':'Preparado',$row['products'],(new DateTimeImmutable($row['created_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Buenos_Aires'))->format('d/m/Y H:i'),get_public_user_name(['public_name'=>$row['public_name'],'name'=>'Usuario'])];
        else{$row=stock_product_names($row);if($type==='stock')$cells=[$row['ean'],$row['display_name']?:'Producto sin nombre',$row['units'],$row['code'],$row['branch'],$formatted];
        else{$expiry=stock_expiry_state($date,$days);$cells=[$row['display_name']?:'Producto sin nombre',$row['ean'],$row['code'],$row['branch'],$formatted,$expiry['days'],$expiry['label']];}}
        fputcsv($output,array_map('stock_csv_cell',$cells),';','"','');
    }
}
