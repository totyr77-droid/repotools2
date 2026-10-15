<?php
declare(strict_types=1);
require_once __DIR__.'/public-names.php';
function tops_legacy_categories(): array {
    return ['codigos'=>['Códigos','Cada EAN diferente escaneado en el período cuenta una vez.'], 'escaneos'=>['Escaneos','Cuenta cada escaneo registrado, también los repetidos.'], 'reportes'=>['Reportes','Solo cuentan correcciones aprobadas; enviar reportes no suma puntos.']];
}
function tops_legacy_ranking(PDO $db,string $category,string $period,?int $viewer=null,?DateTimeImmutable $now=null): array {
    if (!isset(tops_legacy_categories()[$category])||!in_array($period,['semana','mes','historico'],true)) throw new InvalidArgumentException('Filtro no válido.');
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

function tops_categories(): array {return ['referidos'=>['Referidos','Solo cuentan invitaciones con correo confirmado, en la fecha de verificación.'],'actividad'=>['Actividad','Cada operación real confirmada cuenta una vez, sin importar las unidades.'],'sucursales'=>['Sucursales','Unidades ingresadas o retiradas. En un traslado, se cuenta solo el destino.']];}
function tops_ranking(PDO $db,string $category,string $period,?int $viewer=null,?DateTimeImmutable $now=null): array {
    if(isset(tops_legacy_categories()[$category]))return tops_legacy_ranking($db,$category,$period,$viewer,$now);
    if(!isset(tops_categories()[$category])||!in_array($period,['semana','mes','historico'],true))throw new InvalidArgumentException('Filtro no válido.');
    $now=($now??new DateTimeImmutable())->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'));
    $start=$period==='semana'?$now->modify('monday this week')->setTime(0,0):($period==='mes'?$now->modify('first day of this month')->setTime(0,0):null);
    if($category==='referidos'){$where="state='validated' AND email_verified_at IS NOT NULL";$params=[];if($start){$where.=' AND email_verified_at>=?';$params[]=$start->getTimestamp();}$where.=' AND email_verified_at<=?';$params[]=$now->getTimestamp();$scores='SELECT referrer_id AS subject_id,COUNT(*) AS value FROM referrals WHERE '.$where.' GROUP BY referrer_id';}
    else{$kind=$category==='actividad'?'activity':'branches';$where='kind=?';$params=[$kind];if($start){$where.=' AND day>=?';$params[]=$start->format('Y-m-d');}$where.=' AND day<=?';$params[]=$now->format('Y-m-d');$scores='SELECT subject_id,SUM(value) AS value FROM ranking_daily WHERE '.$where.' GROUP BY subject_id';}
    $cte='WITH scores AS ('.$scores.'),ranked AS (SELECT subject_id,value,RANK() OVER(ORDER BY value DESC) AS position FROM scores) ';
    $branch=$category==='sucursales';$join=$branch?'stock_branches':'users';$name=$branch?'u.name':'u.public_name';$select='SELECT r.*,'.$name.' AS name FROM ranked r LEFT JOIN '.$join.' u ON u.id=r.subject_id ';
    $q=$db->prepare($cte.$select.'ORDER BY value DESC,subject_id LIMIT 10');$q->execute($params);$rows=$q->fetchAll();$own=null;
    if($viewer&&!$branch){foreach($rows as $r)if((int)$r['subject_id']===$viewer){$own=$r;break;}if(!$own){$q=$db->prepare($cte.$select.'WHERE subject_id=?');$q->execute([...$params,$viewer]);$own=$q->fetch()?:null;}}
    $safe=static fn($r)=>['name'=>$r['name']?:($branch?'Sucursal eliminada':'Usuario eliminado'),'value'=>(int)$r['value'],'position'=>(int)$r['position'],'me'=>!$branch&&$viewer!==null&&(int)$r['subject_id']===$viewer];
    return ['leaders'=>array_map($safe,$rows),'own'=>$own?$safe($own):($viewer&&!$branch?['position'=>0,'value'=>0,'name'=>'Vos','me'=>true]:null)];
}
