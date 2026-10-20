<?php
declare(strict_types=1);
// Only new, server-confirmed actions enter this journal. Never backfill historical tables.
function achievement_catalog(): array {
    static $catalog;
    if($catalog!==null)return $catalog;
    $manifest=json_decode(file_get_contents(__DIR__.'/../img/achievements/manifest.json'),true,512,JSON_THROW_ON_ERROR);
    $names=['pallets'=>'Arquitecto de palets','transferencias'=>'Maestro del traslado'];
    $rules=['escaneos'=>'EAN únicos leídos por cámara','pallets'=>'Palets creados con primera carga','productos_agregados'=>'Cargas confirmadas, sin contar unidades','retiros'=>'Retiros confirmados, sin correcciones ni traslados','transferencias'=>'Traslados y transferencias de propiedad completados','referidos'=>'Referidos verificados','actividad'=>'Días distintos con actividad válida','rachas'=>'Semanas seguidas con dos días activos; domingo opcional y válido','listas'=>'EAN únicos guardados en tus listas','qr'=>'Palets y otras personas únicos leídos por QR','busquedas'=>'Búsquedas realizadas, sin repeticiones del día','ediciones'=>'Correcciones aprobadas por otra persona'];
    $catalog=[];foreach($manifest['categorias'] as $c){$id=$c['id'];$c['nombre']=$names[$id]??$c['nombre'];$c['regla']=$rules[$id];if($id==='rachas')$c['metas']=[2,4,8,12,26,52];if($id==='ediciones')$c['metas']=[5,15,40,100,250,500];$c['niveles']=$manifest['niveles'];$catalog[$id]=$c;}return $catalog;
}
function achievement_q(PDO $db,string $sql,array $args=[]): PDOStatement {$q=$db->prepare($sql);$q->execute($args);return $q;}
function achievement_unlock(PDO $db,int $user,string $category,int $value): void {
    $c=achievement_catalog()[$category];$fresh=[];
    foreach($c['metas'] as $i=>$goal){if($value<$goal)break;$n=$i+1;if(achievement_q($db,'INSERT INTO user_achievements(user_id,category,level) VALUES(?,?,?) ON CONFLICT(user_id,category,level) DO NOTHING',[$user,$category,$n])->rowCount())$fresh[]=$c['niveles'][$i];}
    if($fresh){$key='achievement:'.$user.':'.$category.':'.count(array_filter($c['metas'],fn($g)=>$value>=$g));achievement_q($db,'INSERT INTO notifications(user_id,event_key,type,title,body) VALUES(?,?,?,?,?) ON CONFLICT(event_key) DO NOTHING',[$user,$key,'achievement','¡Nueva insignia!',$c['nombre'].' · '.end($fresh)]);}
}
function achievement_increment(PDO $db,int $user,string $category,int $amount=1): void {
    achievement_q($db,'INSERT INTO achievement_progress(user_id,category,value) VALUES(?,?,?) ON CONFLICT(user_id,category) DO UPDATE SET value=achievement_progress.value+excluded.value',[$user,$category,$amount]);
    $value=(int)achievement_q($db,'SELECT value FROM achievement_progress WHERE user_id=? AND category=?',[$user,$category])->fetchColumn();achievement_unlock($db,$user,$category,$value);
}
function achievement_streak(array $days): int {
    $weeks=[];foreach($days as $day){$date=new DateTimeImmutable($day);$monday=$date->modify('monday this week')->format('Y-m-d');$weeks[$monday]=($weeks[$monday]??0)+1;}ksort($weeks);$best=0;$run=0;$last=null;foreach($weeks as $monday=>$count){if($count<2)continue;$run=$last!==null&&(new DateTimeImmutable($last))->modify('+7 days')->format('Y-m-d')===$monday?$run+1:1;$best=max($best,$run);$last=$monday;}return $best;
}
function achievement_record(PDO $db,int $user,string $category,string $key,?string $day=null): void {
    if(!$db->inTransaction())throw new LogicException('Achievements require a business transaction.');
    if(!isset(achievement_catalog()[$category])||in_array($category,['actividad','rachas'],true))throw new InvalidArgumentException('Invalid achievement category');
    // Serialize unlocks/preferences for this user; SQLite serializes the first write.
    if($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql')achievement_q($db,'SELECT id FROM users WHERE id=? FOR UPDATE',[$user]);
    $day=$day??(new DateTimeImmutable('now',new DateTimeZone('America/Argentina/Buenos_Aires')))->format('Y-m-d');
    $inserted=achievement_q($db,'INSERT INTO achievement_events(user_id,category,event_key,day) VALUES(?,?,?,?) ON CONFLICT(user_id,category,event_key) DO NOTHING',[$user,$category,hash('sha256',$key),$day])->rowCount();
    if($inserted)achievement_increment($db,$user,$category);
    achievement_day($db,$user,$day);
}
function achievement_day(PDO $db,int $user,?string $day=null): void {
    $day=$day??(new DateTimeImmutable('now',new DateTimeZone('America/Argentina/Buenos_Aires')))->format('Y-m-d');
    if(achievement_q($db,'INSERT INTO achievement_days(user_id,day) VALUES(?,?) ON CONFLICT(user_id,day) DO NOTHING',[$user,$day])->rowCount()){
        achievement_increment($db,$user,'actividad');$days=achievement_q($db,'SELECT day FROM achievement_days WHERE user_id=? ORDER BY day',[$user])->fetchAll(PDO::FETCH_COLUMN);$streak=achievement_streak($days);
        achievement_q($db,'INSERT INTO achievement_progress(user_id,category,value) VALUES(?,?,?) ON CONFLICT(user_id,category) DO UPDATE SET value=CASE WHEN achievement_progress.value<excluded.value THEN excluded.value ELSE achievement_progress.value END',[$user,'rachas',$streak]);achievement_unlock($db,$user,'rachas',$streak);
    }
}
function achievement_snapshot(PDO $db,int $user,bool $own): array {
    $progress=achievement_q($db,'SELECT category,value FROM achievement_progress WHERE user_id=?',[$user])->fetchAll(PDO::FETCH_KEY_PAIR);
    $awards=achievement_q($db,'SELECT category,level,unlocked_at,featured,seen_at FROM user_achievements WHERE user_id=? ORDER BY category,level',[$user])->fetchAll();
    if(!$own)foreach($awards as &$award)unset($award['seen_at']);unset($award);
    $catalog=achievement_catalog();$categories=[];foreach($catalog as $id=>$c){$rows=array_values(array_filter($awards,fn($a)=>$a['category']===$id));$categories[]=['id'=>$id,'name'=>$c['nombre'],'rule'=>$c['regla'],'goals'=>$c['metas'],'levels'=>$c['niveles'],'progress'=>$own?(int)($progress[$id]??0):null,'awards'=>$rows];}
    return ['categories'=>$categories,'unlocked'=>count($awards),'total'=>72,'pallets'=>(int)($progress['pallets']??0),'days'=>(int)($progress['actividad']??0),'own'=>$own];
}

function achievement_notification(array $item): array {
    if(($item['type']??'')!=='achievement')return $item;
    if(!preg_match('/^achievement:[0-9]+:([a-z_]+):([1-6])$/D',$item['event_key']??'',$match))return $item;
    $category=achievement_catalog()[$match[1]]??null;$level=(int)$match[2];if(!$category)return $item;
    $levels=['bronce','plata','oro','platino','diamante','leyenda'];
    $item['badge']='img/achievements/'.$match[1].'/'.$match[1].'_'.str_pad((string)$level,2,'0',STR_PAD_LEFT).'_'.$levels[$level-1].'.svg';
    $item['body']=$category['nombre'].' · '.$category['niveles'][$level-1];
    $item['achievement_detail']='Meta alcanzada: '.$category['metas'][$level-1].' · '.$category['regla'];
    return $item;
}
