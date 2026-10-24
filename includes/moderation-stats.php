<?php
declare(strict_types=1);
require_once __DIR__.'/audit.php';
require_once __DIR__.'/audit-records.php';
function moderation_period(array $input,?DateTimeImmutable $now=null):array {
 $zone=new DateTimeZone('America/Buenos_Aires');$now=($now??new DateTimeImmutable('now',$zone))->setTimezone($zone);$today=$now->setTime(0,0);$period=$input['period']??'7';if(!is_string($period)||!in_array($period,['today','7','30','custom'],true))throw new CatalogException('Período no válido.',422);
 if($period==='custom'){$dates=[];foreach(['from','to'] as $key){$raw=$input[$key]??'';if(!is_string($raw)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$raw))throw new CatalogException('Completá las dos fechas.',422);$date=DateTimeImmutable::createFromFormat('!Y-m-d',$raw,$zone);if(!$date||$date->format('Y-m-d')!==$raw)throw new CatalogException('Revisá las fechas.',422);$dates[]=$date;}[$start,$last]=$dates;if($start>$last||$last>$today||$start->diff($last)->days>365)throw new CatalogException('Elegí hasta un año, sin fechas futuras.',422);}else{$last=$today;$start=$today->modify('-'.($period==='today'?0:(int)$period-1).' days');}
 $end=$last->modify('+1 day');return ['period'=>$period,'from'=>$start->format('Y-m-d'),'to'=>$last->format('Y-m-d'),'start'=>$start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),'end'=>$end->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')];
}
function moderation_activity(PDO $db,array $range):array {
 $args=[$range['start'],$range['end']];$operation=audit_operation_sql($db);$section=audit_section_sql($db);
 $rows=audit_q($db,"SELECT section,COUNT(*) AS total FROM (SELECT e.actor_id,$operation AS operation_group,$section AS section FROM audit_events e WHERE e.occurred_at>=? AND e.occurred_at<? AND e.result='confirmed' GROUP BY e.actor_id,$operation,$section) activity GROUP BY section",$args)->fetchAll();$activity=['goods'=>0,'users'=>0,'moderation'=>0,'management'=>0];foreach($rows as $row)$activity[$row['section']]=(int)$row['total'];
 $registered=(int)audit_q($db,'SELECT COUNT(*) FROM users WHERE created_at>=? AND created_at<?',$args)->fetchColumn();
 $active=(int)audit_q($db,"SELECT COUNT(DISTINCT e.actor_id) FROM audit_events e JOIN users u ON u.id=e.actor_id WHERE e.occurred_at>=? AND e.occurred_at<? AND e.result='confirmed'",$args)->fetchColumn();
 $seconds=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'?"EXTRACT(EPOCH FROM (reviewed_at-created_at))":"(julianday(reviewed_at)-julianday(created_at))*86400";
 $review=audit_q($db,"SELECT COUNT(*) AS resolved,AVG(CASE WHEN reviewed_at>=created_at THEN $seconds ELSE NULL END) AS seconds FROM product_reports WHERE status IN ('approved','rejected') AND reviewed_at>=? AND reviewed_at<?",$args)->fetch();
 return ['registered'=>$registered,'active_users'=>$active,'resolved'=>(int)$review['resolved'],'resolution_hours'=>$review['seconds']===null?null:round((float)$review['seconds']/3600,1),'sections'=>$activity];
}
