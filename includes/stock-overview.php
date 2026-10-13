<?php
declare(strict_types=1);
require_once __DIR__.'/stock-export.php';require_once __DIR__.'/pallet-activity.php';
function stock_overview(PDO $db,int $user,?DateTimeImmutable $today=null): array {
    $days=stock_warning_days($db,$user);$today=$today??new DateTimeImmutable('today',new DateTimeZone('America/Buenos_Aires'));
    $date=$today->format('Y-m-d');$limit=$today->modify('+'.$days.' days')->format('Y-m-d');
    $where=stock_access_scope().' AND p.activated=1';
    // One aggregate over accessible pallets. No query or request per metric.
    $counts=stock_query($db,"SELECT COUNT(DISTINCT p.id) AS pallets,COUNT(b.id) AS products,COALESCE(SUM(CASE WHEN b.expires<>'' AND b.expires>=? AND b.expires<=? THEN 1 ELSE 0 END),0) AS upcoming,COALESCE(SUM(CASE WHEN b.expires<>'' AND b.expires<? THEN 1 ELSE 0 END),0) AS expired FROM pallets p LEFT JOIN pallet_products b ON b.pallet_id=p.id WHERE {$where}",[$date,$limit,$date,...stock_access_args($user)])->fetch();
    foreach($counts as &$count)$count=(int)$count;unset($count);
    return ['counts'=>$counts,'warning_days'=>$days,'activity'=>pallet_activity_recent($db,$user)];
}
