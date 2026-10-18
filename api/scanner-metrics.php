<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/catalog.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
try {
    $user=current_user();
    if(!$user)throw new CatalogException('Iniciá sesión.',401);
    $db=database();$uid=(int)$user['id'];
    $roleQuery=$db->prepare('SELECT role FROM users WHERE id=?');$roleQuery->execute([$uid]);$role=$roleQuery->fetchColumn();
    if(!$role)throw new CatalogException('Iniciá sesión.',401);
    if($_SERVER['REQUEST_METHOD']==='POST'){
        if(!valid_csrf()||($_POST['user']??'')!==(string)$uid)throw new CatalogException('Solicitud no válida.',403);
        if((int)($_SERVER['CONTENT_LENGTH']??0)>32768)throw new CatalogException('Registro demasiado grande.',413);
        $raw=$_POST['records']??'';
        if(!is_string($raw)||strlen($raw)>28000)throw new CatalogException('Registro no válido.',422);
        $records=json_decode($raw,true);
        if(!is_array($records)||!array_is_list($records)||count($records)<1||count($records)>20)throw new CatalogException('Registro no válido.',422);
        $window=$_SESSION['scanner_metrics_rate']??['at'=>time(),'count'=>0];
        if(time()-(int)$window['at']>=60)$window=['at'=>time(),'count'=>0];
        if((int)$window['count']+count($records)>300)throw new CatalogException('Esperá un momento para enviar más registros.',429);
        $clean=[];
        foreach($records as $r){
            if(!is_array($r)||!is_string($r['event_id']??null)||!preg_match('/^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/D',$r['event_id']))throw new CatalogException('Registro no válido.',422);
            foreach(['mode'=>['qr','ean'],'context'=>['home','stock','library','users','relay','offline','other'],'outcome'=>['confirmed','cancelled','error'],'source'=>['camera','background','ocr','none'],'browser'=>['safari','chrome','edge','firefox','other'],'device'=>['mobile','tablet','desktop']] as $key=>$values){if(!in_array($r[$key]??null,$values,true))throw new CatalogException('Registro no válido.',422);}
            if(!is_string($r['app_version']??null)||!preg_match('/^\d{1,3}\.\d{2}$/D',$r['app_version']))throw new CatalogException('Versión no válida.',422);
            foreach(['scan_ms'=>600000,'total_ms'=>1200000,'width'=>8192,'height'=>8192,'rapid'=>1] as $key=>$max){if(!is_int($r[$key]??null)||$r[$key]<0||$r[$key]>$max)throw new CatalogException('Tiempo no válido.',422);}
            foreach(['open_ms','confirm_ms'] as $key){if(!array_key_exists($key,$r)||($r[$key]!==null&&(!is_int($r[$key])||$r[$key]<0||$r[$key]>600000)))throw new CatalogException('Tiempo no válido.',422);}
            if(($r['outcome']==='confirmed')===($r['source']==='none')||($r['mode']==='ean'&&$r['source']==='ocr')||$r['total_ms']!==$r['scan_ms']+($r['open_ms']??0)||($r['confirm_ms']!==null&&$r['confirm_ms']>$r['scan_ms']+1))throw new CatalogException('Registro inconsistente.',422);
            $clean[]=[$r['event_id'],$uid,$r['mode'],$r['context'],$r['outcome'],$r['source'],$r['app_version'],$r['browser'],$r['device'],$r['rapid'],$r['open_ms'],$r['scan_ms'],$r['confirm_ms'],$r['total_ms'],$r['width'],$r['height']];
        }
        $db->beginTransaction();
        $insert=$db->prepare('INSERT INTO scanner_metrics(event_id,user_id,mode,context,outcome,source,app_version,browser,device,rapid,open_ms,scan_ms,confirm_ms,total_ms,width,height) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON CONFLICT(event_id) DO NOTHING');
        foreach($clean as $row)$insert->execute($row);
        $db->commit();$window['count']+=count($clean);$_SESSION['scanner_metrics_rate']=$window;
        echo json_encode(['ok'=>true,'manager'=>$role==='manager']);exit;
    }
    if($_SERVER['REQUEST_METHOD']!=='GET')throw new CatalogException('Solicitud no válida.',405);
    if($role!=='manager')throw new CatalogException('Solo managers pueden consultar Sistema.',403);
    $rawDays=$_GET['days']??'7';if(!is_string($rawDays)||!in_array($rawDays,['1','7','30'],true))throw new CatalogException('Período no válido.',422);$days=(int)$rawDays;if(!in_array($days,[1,7,30],true))throw new CatalogException('Período no válido.',422);
    $page=filter_var($_GET['page']??0,FILTER_VALIDATE_INT);if($page===false||$page<0||$page>200)throw new CatalogException('Página no válida.',422);
    $cutoff=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'?gmdate('c',time()-$days*86400):gmdate('Y-m-d H:i:s',time()-$days*86400);
    $where='recorded_at>=?';$args=[$cutoff];
    foreach(['mode'=>['qr','ean'],'browser'=>['safari','chrome','edge','firefox','other'],'source'=>['camera','background','ocr','none'],'device'=>['mobile','tablet','desktop'],'rapid'=>['0','1']] as $key=>$values){$value=$_GET[$key]??'';if($value!==''){if(!is_string($value)||!in_array($value,$values,true))throw new CatalogException('Filtro no válido.',422);$where.=' AND '.$key.'=?';$args[]=$value;}}
    $version=$_GET['version']??'';if($version!==''){if(!is_string($version)||!preg_match('/^\d{1,3}\.\d{2}$/D',$version))throw new CatalogException('Versión no válida.',422);$where.=' AND app_version=?';$args[]=$version;}
    $aggregate="COUNT(*) AS attempts, SUM(CASE WHEN outcome='confirmed' THEN 1 ELSE 0 END) AS confirmed, SUM(CASE WHEN outcome='cancelled' THEN 1 ELSE 0 END) AS cancelled, SUM(CASE WHEN outcome='error' THEN 1 ELSE 0 END) AS errors, AVG(CASE WHEN outcome='confirmed' THEN scan_ms END) AS avg_scan_ms, AVG(CASE WHEN outcome='confirmed' THEN confirm_ms END) AS avg_confirm_ms, AVG(open_ms) AS avg_open_ms";
    $q=$db->prepare('SELECT '.$aggregate.' FROM scanner_metrics WHERE '.$where);$q->execute($args);$summary=$q->fetch();
    $q=$db->prepare('SELECT mode,app_version,'.$aggregate.' FROM scanner_metrics WHERE '.$where.' GROUP BY mode,app_version ORDER BY app_version DESC,mode LIMIT 100');$q->execute($args);$groups=$q->fetchAll();
    $q=$db->prepare("SELECT mode,app_version,AVG(CASE WHEN rn IN ((cnt+1)/2,(cnt+2)/2) THEN scan_ms END) AS median_scan_ms,MAX(CASE WHEN rn=(cnt*9+9)/10 THEN scan_ms END) AS p90_scan_ms FROM (SELECT mode,app_version,scan_ms,ROW_NUMBER() OVER(PARTITION BY mode,app_version ORDER BY scan_ms) AS rn,COUNT(*) OVER(PARTITION BY mode,app_version) AS cnt FROM scanner_metrics WHERE ".$where." AND outcome='confirmed') distribution GROUP BY mode,app_version");$q->execute($args);$distributions=[];foreach($q->fetchAll() as $row)$distributions[$row['mode'].'|'.$row['app_version']]=$row;foreach($groups as &$group){$distribution=$distributions[$group['mode'].'|'.$group['app_version']]??[];$group['median_scan_ms']=$distribution['median_scan_ms']??null;$group['p90_scan_ms']=$distribution['p90_scan_ms']??null;}unset($group);
    $q=$db->prepare('SELECT event_id,recorded_at,mode,context,outcome,source,app_version,browser,device,rapid,open_ms,scan_ms,confirm_ms,total_ms,width,height FROM scanner_metrics WHERE '.$where.' ORDER BY recorded_at DESC,event_id DESC LIMIT 31 OFFSET '.($page*30));$q->execute($args);$items=$q->fetchAll();$more=count($items)>30&&$page<200;
    $q=$db->prepare('SELECT DISTINCT app_version FROM scanner_metrics WHERE recorded_at>=? ORDER BY app_version DESC LIMIT 30');$q->execute([$cutoff]);
    echo json_encode(['summary'=>$summary,'groups'=>$groups,'items'=>array_slice($items,0,30),'more'=>$more,'page'=>$page,'versions'=>$q->fetchAll(PDO::FETCH_COLUMN),'csrf'=>csrf_token()],JSON_UNESCAPED_UNICODE);
} catch(Throwable $e){
    if(isset($db)&&$db->inTransaction())$db->rollBack();
    http_response_code($e instanceof CatalogException?$e->status:500);
    echo json_encode(['error'=>$e instanceof CatalogException?$e->getMessage():'No pudimos consultar los registros del escáner.'],JSON_UNESCAPED_UNICODE);
}
