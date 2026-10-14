<?php
declare(strict_types=1);
require_once __DIR__.'/public-names.php';
function tops_categories(): array {
    return ['codigos'=>['Códigos','Cada EAN diferente escaneado en el período cuenta una vez.'], 'escaneos'=>['Escaneos','Cuenta cada escaneo registrado, también los repetidos.'], 'reportes'=>['Reportes','Solo cuentan correcciones aprobadas; enviar reportes no suma puntos.']];
}
function tops_ranking(PDO $db,string $category,string $period,?int $viewer=null,?DateTimeImmutable $now=null): array {
    if (!isset(tops_categories()[$category])||!in_array($period,['semana','mes','historico'],true)) throw new InvalidArgumentException('Filtro no válido.');
    $now=($now??new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('America/Buenos_Aires'));
    $start=$period==='semana'?$now->modify('monday this week')->setTime(0,0):($period==='mes'?$now->modify('first day of this month')->setTime(0,0):null);
    $params=[];$where='';
    if ($start) {$where=' WHERE e.created_at >= ? AND e.created_at <= ?';foreach([$start,$now] as $date)$params[]=$date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}
    $table=$category==='reportes'?'report_rewards':'scan_events';$metric=$category==='codigos'?'COUNT(DISTINCT e.code)':'COUNT(*)';
    $cte="WITH scores AS (SELECT e.user_id, {$metric} AS value FROM {$table} e{$where} GROUP BY e.user_id), ranked AS (SELECT user_id,value,RANK() OVER(ORDER BY value DESC) AS position FROM scores) ";
    $select='SELECT r.*,u.public_name FROM ranked r JOIN users u ON u.id=r.user_id ';
    $stmt=$db->prepare($cte.$select.'ORDER BY value DESC,r.user_id ASC LIMIT 50');$stmt->execute($params);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
    $safe=static function(array $row)use($viewer):array {return ['position'=>(int)$row['position'],'value'=>(int)$row['value'],'name'=>get_public_user_name(['public_name'=>$row['public_name'],'name'=>'Usuario','id'=>0]),'me'=>$viewer!==null&&(int)$row['user_id']===$viewer];};
    $own=null;
    if ($viewer!==null&&!array_filter($rows,fn($row)=>(int)$row['user_id']===$viewer)) {$stmt=$db->prepare($cte.$select.'WHERE r.user_id=?');$stmt->execute([...$params,$viewer]);$row=$stmt->fetch(PDO::FETCH_ASSOC);if($row)$own=$safe($row);}
    return ['leaders'=>array_map($safe,$rows),'own'=>$own];
}
