<?php
require_once __DIR__.'/branch-team.php';
function team_inventory_page(PDO $db,int $user,int $branch,string $view,array $input,bool $personal=false): array {
    if($personal){if(!$user||!in_array($view,['mercaderia','fechas','lotes'],true))throw new CatalogException('Sección no válida.',422);}else{team_require($db,$user,$branch,true);}
    if(!in_array($view,['palets','movimientos','administracion','mercaderia','fechas','lotes'],true))throw new CatalogException('Sección no válida.',422);
    foreach($input as $value)if(!is_string($value)||strlen($value)>2000)throw new CatalogException('Filtro no válido.',422);
    $offset=filter_var($input['offset']??'0',FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100000]]);if($offset===false)throw new CatalogException('Página no válida.',422);
    $query=static function(string $sql,array $args=[])use($db,$branch,$personal):PDOStatement{
        if($personal)return stock_query($db,$sql,$args);
        $prefix=team_inventory_projection($branch);
        $sql=preg_replace(['/\b(FROM|JOIN) pallet_products\b/','/\b(FROM|JOIN) pallets\b/'],['$1 team_products','$1 team_pallets'],$sql);
        return stock_query($db,$prefix.$sql,$args);
    };
    $q=catalog_text($input['q']??'',100);$like='%'.str_replace(['!','%','_'],['!!','!%','!_'],mb_strtolower($q)).'%';$where=$personal?["(p.owner_id=? OR EXISTS(SELECT 1 FROM pallet_access a WHERE a.pallet_id=p.id AND a.user_id=? AND a.status='accepted'))"]:['p.branch_id=?'];$params=$personal?[$user,$user]:[$branch];$today=(new DateTimeImmutable('today',new DateTimeZone('America/Buenos_Aires')))->format('Y-m-d');$near=(new DateTimeImmutable($today))->modify('+'.stock_warning_days($db,$user).' days')->format('Y-m-d');
    $sort=$input['sort']??'';if(!in_array($sort,['','expiry_asc','expiry_desc'],true))throw new CatalogException('Orden no válido.',422);$direction=$sort==='expiry_desc'?'DESC':'ASC';
    $expiry=$input['expiry']??'all';if(!in_array($expiry,['all','expired','upcoming','none','due'],true))throw new CatalogException('Vencimiento no válido.',422);
    if($personal){$filter=$input['branch']??'all';if($filter==='none')$where[]='p.branch_id IS NULL';elseif($filter!=='all'){if(!preg_match('/^[1-9][0-9]{0,17}$/D',$filter))throw new CatalogException('Sucursal no válida.',422);$where[]='p.branch_id=?';$params[]=$filter;}}
    $tag=catalog_text($input['tag']??'',40);
    if($tag!==''&&in_array($view,['palets','mercaderia','fechas','lotes'],true)){$where[]="EXISTS(SELECT 1 FROM pallet_user_tags t WHERE t.pallet_id=p.id AND t.user_id=".($personal?'?':'p.owner_id')." AND t.name=?)";if($personal)$params[]=$user;$params[]=$tag;}
    $dateCondition=static function(string $alias)use($expiry,$today,$near):array{return match($expiry){'due'=>["$alias.expires<>'' AND $alias.expires<=?",[$near]],'expired'=>["$alias.expires<>'' AND $alias.expires<?",[$today]],'upcoming'=>["$alias.expires>=? AND $alias.expires<=?",[$today,$near]],'none'=>["$alias.expires=''",[]],default=>['1=1',[]]};};
    if(in_array($view,['palets','mercaderia','fechas','lotes'],true))$where[]='p.released=0';
    if($view==='palets'){
        if($q!==''){$where[]="(p.code LIKE ? ESCAPE '!' OR LOWER(p.description) LIKE ? ESCAPE '!' OR LOWER(u.public_name) LIKE ? ESCAPE '!' OR EXISTS(SELECT 1 FROM pallet_products x LEFT JOIN products c ON c.ean=x.ean WHERE x.pallet_id=p.id AND (x.ean LIKE ? ESCAPE '!' OR LOWER(x.name) LIKE ? ESCAPE '!' OR LOWER(c.name) LIKE ? ESCAPE '!')))";array_push($params,...array_fill(0,6,$like));}
        if($expiry!=='all'){[$condition,$args]=$dateCondition('x');$where[]='EXISTS(SELECT 1 FROM pallet_products x WHERE x.pallet_id=p.id AND '.$condition.')';array_push($params,...$args);}
        $order=$sort?"CASE WHEN MIN(NULLIF(b.expires,'')) IS NULL THEN 1 ELSE 0 END,MIN(NULLIF(b.expires,'')) $direction,p.id":'p.id';
        $rows=$query("SELECT p.id,p.code,p.description,p.updated_at,u.public_name AS owner_name,MIN(NULLIF(b.expires,'')) AS nearest_expiry,COALESCE(SUM(CASE WHEN b.expires<>'' AND b.expires<=? THEN 1 ELSE 0 END),0) AS expiry_count,COALESCE(SUM(b.units),0) AS units,COUNT(CASE WHEN b.units>0 THEN b.id END) AS products FROM pallets p JOIN users u ON u.id=p.owner_id LEFT JOIN pallet_products b ON b.pallet_id=p.id WHERE ".implode(' AND ',$where)." GROUP BY p.id,p.code,p.description,p.updated_at,u.public_name ORDER BY $order LIMIT 31 OFFSET $offset",[$near,...$params])->fetchAll();
        $ids=array_column($rows,'id');$tags=$ids?array_column($query('SELECT t.pallet_id,t.name FROM pallet_user_tags t JOIN pallets p ON p.id=t.pallet_id WHERE p.branch_id=? AND t.user_id=p.owner_id AND p.id IN ('.implode(',',array_fill(0,count($ids),'?')).')',[$branch,...$ids])->fetchAll(),'name','pallet_id'):[];foreach($rows as &$row){$row['tag']=$tags[$row['id']]??'';$row['expiry']=$row['nearest_expiry']?stock_expiry_state($row['nearest_expiry'],stock_warning_days($db,$user)):null;}unset($row);
    }elseif(in_array($view,['mercaderia','fechas','lotes'],true)){
        [$condition,$args]=$dateCondition('b');$where[]=$condition;array_push($params,...$args);
        if($view==='fechas'){$ean=$input['ean']??'';if(!preg_match('/^(?:[0-9]{8}|[0-9]{13})$/D',$ean))throw new CatalogException('Producto no válido.',422);$where[]='b.ean=?';$params[]=$ean;}
        if($view==='lotes'){$ean=$input['ean']??'';$expires=$input['expires']??'';if(!preg_match('/^(?:[0-9]{8}|[0-9]{13})$/D',$ean)||($expires!==''&&!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D',$expires)))throw new CatalogException('Producto no válido.',422);$where[]='b.ean=? AND b.expires=?';array_push($params,$ean,$expires);}
        if($q!==''){$where[]="(b.ean LIKE ? ESCAPE '!' OR LOWER(b.name) LIKE ? ESCAPE '!' OR LOWER(c.name) LIKE ? ESCAPE '!' OR p.code LIKE ? ESCAPE '!')";array_push($params,...array_fill(0,4,$like));}
        $select=$view==='mercaderia'?"b.ean,b.expires,COALESCE(MIN(NULLIF(b.name,'')),MIN(c.name),'Producto sin nombre') AS name,SUM(b.units) AS units,COUNT(DISTINCT p.id) AS pallets":"b.id,b.ean,b.name,b.expires,b.units,b.kind,b.pack_size,b.inner_pack_size,p.code,p.owner_id,p.description AS pallet_description";
        if($view==='lotes'){
            $labelUser=$personal?(string)$user:'p.owner_id';
            $select.=',(SELECT sb.name FROM stock_branches sb WHERE sb.id=p.branch_id AND '.branch_visibility_sql($db,$user,'sb.id').') AS branch_name,(SELECT t.name FROM pallet_user_tags t WHERE t.pallet_id=p.id AND t.user_id='.$labelUser.') AS tag';
        }
        $order=$sort?"CASE WHEN b.expires='' THEN 1 ELSE 0 END,b.expires $direction,b.ean":'b.ean,b.expires';$group=$view==='mercaderia'?' GROUP BY b.ean,b.expires ORDER BY '.$order:' ORDER BY p.id,b.id';
        if($view==='mercaderia'){
            $select="b.ean,COALESCE(MIN(NULLIF(b.name,'')),MIN(c.name),'Producto sin nombre') AS name,SUM(b.units) AS units,COUNT(DISTINCT p.id) AS pallets,MIN(NULLIF(b.expires,'')) AS nearest_expiry,COUNT(DISTINCT b.expires) AS date_count,MIN(CASE WHEN b.expires>='$today' THEN b.expires END) AS nearest_active_expiry,COUNT(DISTINCT CASE WHEN b.expires>='$today' AND b.expires<='$near' THEN b.expires END) AS upcoming_dates,COUNT(DISTINCT CASE WHEN b.expires>'$near' THEN b.expires END) AS safe_dates,COUNT(DISTINCT CASE WHEN b.expires<>'' AND b.expires<'$today' THEN b.expires END) AS expired_dates,MAX(CASE WHEN b.expires='' THEN 1 ELSE 0 END) AS undated";
            $order=$sort?"CASE WHEN MIN(NULLIF(b.expires,'')) IS NULL THEN 1 ELSE 0 END,MIN(NULLIF(b.expires,'')) $direction,b.ean":'MAX(p.updated_at) DESC,b.ean';
            $group=' GROUP BY b.ean ORDER BY '.$order;
        }elseif($view==='fechas'){
            $select="b.ean,b.expires,COALESCE(MIN(NULLIF(b.name,'')),MIN(c.name),'Producto sin nombre') AS name,SUM(b.units) AS units,COUNT(DISTINCT p.id) AS pallets";
            $dateOrder=$expiry==='due'&&$sort?"b.expires $direction,b.ean":"CASE WHEN b.expires='' THEN 1 WHEN b.expires<'$today' THEN 2 ELSE 0 END,b.expires $direction,b.ean";
            $group=" GROUP BY b.ean,b.expires ORDER BY $dateOrder";
        }
        $rows=$query('SELECT '.$select.' FROM pallet_products b JOIN pallets p ON p.id=b.pallet_id LEFT JOIN products c ON c.ean=b.ean WHERE '.implode(' AND ',$where).$group." LIMIT 31 OFFSET $offset",$params)->fetchAll();
    }else{
        $admin=$view==='administracion';$alias=$admin?'e':'a';$where=[$admin?'e.branch_id=?':'p.branch_id=?'];$params=[$branch];
        if($admin)$where[]="e.action<>'pallet_pending_edited'";
        else{
            $pendingBranch=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'?"COALESCE(a.metadata::jsonb->>'branch_id','0')":"COALESCE(json_extract(a.metadata,'$.branch_id'),0)";
            $where=["((a.action='team_pending_edited' AND CAST($pendingBranch AS BIGINT)=?) OR (a.action<>'team_pending_edited' AND p.branch_id=?))"];$params=[$branch,$branch];
        }

        if(isset($input['record'])){if(!preg_match('/^[1-9][0-9]{0,14}$/D',$input['record']))throw new CatalogException('Movimiento no válido.',422);$where[]="$alias.id=?";$params[]=$input['record'];}
        if(($input['action']??'')!==''){$where[]="$alias.action=?";$params[]=catalog_text($input['action'],60);}
        foreach(['from'=>'>=','to'=>'<'] as $key=>$operator)if(($input[$key]??'')!==''){$date=DateTimeImmutable::createFromFormat('!Y-m-d',$input[$key],new DateTimeZone('America/Buenos_Aires'));if(!$date||$date->format('Y-m-d')!==$input[$key])throw new CatalogException('Fecha no válida.',422);if($key==='to')$date=$date->modify('+1 day');$where[]="$alias.created_at $operator ?";$params[]=$date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}
        if($q!==''){
            $reference=$db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'?"COALESCE(a.metadata::jsonb->>'reference','')":"COALESCE(json_extract(a.metadata,'$.reference'),'')";
            $where[]=$admin?"(LOWER(u.public_name) LIKE ? ESCAPE '!' OR LOWER(t.public_name) LIKE ? ESCAPE '!' OR p.code LIKE ? ESCAPE '!')":"(LOWER(u.public_name) LIKE ? ESCAPE '!' OR LOWER(a.metadata) LIKE ? ESCAPE '!' OR p.code LIKE ? ESCAPE '!' OR LOWER($reference) LIKE ? ESCAPE '!')";
            array_push($params,...array_fill(0,$admin?3:4,$like));
        }
        if($admin)$rows=$query('SELECT e.id,e.action,e.created_at,e.metadata,u.public_name AS actor,t.public_name AS target,p.code FROM branch_team_events e JOIN users u ON u.id=e.actor_id LEFT JOIN users t ON t.id=e.target_id LEFT JOIN pallets p ON p.id=e.pallet_id WHERE '.implode(' AND ',$where)." ORDER BY e.id DESC LIMIT 31 OFFSET $offset",$params)->fetchAll();
        else{require_once __DIR__.'/pallet-activity.php';$raw=$query('SELECT a.id,a.action,a.metadata,a.created_at,a.user_id AS actor_id,u.public_name,p.code FROM pallet_activity a JOIN pallets p ON p.id=a.pallet_id LEFT JOIN users u ON u.id=a.user_id WHERE '.implode(' AND ',$where)." ORDER BY a.id DESC LIMIT 31 OFFSET $offset",$params)->fetchAll();$rows=pallet_activity_present($db,$raw);foreach($rows as $i=>&$row)$row['code']=$raw[$i]['code'];unset($row);}
    }
    if($view==='administracion')foreach($rows as &$row)$row['metadata']=json_decode($row['metadata'],true)??[];unset($row);
    $tagOptions=!$personal&&in_array($view,['palets','mercaderia'],true)?$query('SELECT DISTINCT t.name FROM pallet_user_tags t JOIN pallets p ON p.id=t.pallet_id WHERE p.branch_id=? AND p.released=0 AND t.user_id=p.owner_id ORDER BY t.name',[$branch])->fetchAll(PDO::FETCH_COLUMN):[];
    if(!$personal&&$rows&&in_array($view,['palets','mercaderia','fechas','lotes'],true)){
        $pending=[];$original=[];$keys=array_fill(0,count($rows),'?');
        if($view==='palets'){
            $ids=array_column($rows,'id');
            foreach(stock_query($db,"SELECT r.pallet_id,COUNT(b.id) AS products,COALESCE(SUM(b.units),0) AS units FROM team_pallet_requests r JOIN pallets p ON p.id=r.pallet_id AND p.owner_id=r.owner_id LEFT JOIN pallet_products b ON b.pallet_id=r.pallet_id WHERE r.branch_id=? AND r.status='pending' AND r.pallet_id IN (".implode(',',$keys).') GROUP BY r.pallet_id',[$branch,...$ids])->fetchAll() as $r)$pending[$r['pallet_id']]=$r;
            foreach($rows as &$row){$row['pending_approval']=isset($pending[$row['id']]);if($row['pending_approval'])$row['original']=$pending[$row['id']];}unset($row);
        }elseif($view==='lotes'){
            foreach(stock_query($db,"SELECT p.code,b.ean,b.expires,b.units,b.kind,b.pack_size,b.inner_pack_size FROM team_pallet_requests r JOIN pallets p ON p.id=r.pallet_id AND p.owner_id=r.owner_id LEFT JOIN pallet_products b ON b.pallet_id=p.id WHERE r.branch_id=? AND r.status='pending' AND p.code IN (".implode(',',$keys).')',[$branch,...array_column($rows,'code')])->fetchAll() as $r){$pending[$r['code']]=true;$original[$r['code'].'|'.$r['ean'].'|'.$r['expires']]=$r;}
            foreach($rows as &$row){$row['pending_approval']=isset($pending[$row['code']]);if($row['pending_approval']){$r=$original[$row['code'].'|'.$row['ean'].'|'.$row['expires']]??[];$row['original_units']=(int)($r['units']??0);$row['original_kind']=$r['kind']??'units';$row['original_pack_size']=$r['pack_size']??1;$row['original_inner_pack_size']=$r['inner_pack_size']??0;$row['pending_delta']=(int)$row['units']-$row['original_units'];}}unset($row);
        }else{
            foreach($query("SELECT DISTINCT b.ean,b.expires FROM team_pallet_requests r JOIN team_products b ON b.pallet_id=r.pallet_id WHERE r.branch_id=? AND r.status='pending' AND b.ean IN (".implode(',',$keys).')',[$branch,...array_column($rows,'ean')])->fetchAll() as $r)$pending[$view==='mercaderia'?$r['ean']:$r['ean'].'|'.$r['expires']]=true;
            foreach($rows as &$row)$row['pending_approval']=isset($pending[$view==='mercaderia'?$row['ean']:$row['ean'].'|'.$row['expires']]);unset($row);
        }
    }
    $more=count($rows)>30;return ['tags'=>$tagOptions,'items'=>array_slice($rows,0,30),'next_offset'=>$more?$offset+30:null,'view'=>$view,'warning_days'=>stock_warning_days($db,$user)];
}

// SQL projection is read-only and confined to the requested team. Personal Stock uses real tables.
function team_inventory_projection(int $branch):string {
    return "WITH pending AS (SELECT r.* FROM team_pallet_requests r JOIN pallets p ON p.id=r.pallet_id AND p.owner_id=r.owner_id AND p.released=0 WHERE r.branch_id=$branch AND r.status='pending'),
    team_pallets AS (SELECT p.id,p.code,p.owner_id,p.description,p.prepared_until,p.activated,p.released,p.revision,p.updated_by,p.created_at,p.updated_at,CAST(CASE WHEN r.id IS NOT NULL THEN r.branch_id ELSE p.branch_id END AS BIGINT) AS branch_id FROM pallets p LEFT JOIN pending r ON r.pallet_id=p.id),
    team_products AS (
        SELECT b.id,b.pallet_id,b.ean,b.name,b.expires,b.units,b.kind,b.pack_size,b.inner_pack_size,b.revision FROM pallet_products b WHERE NOT EXISTS(SELECT 1 FROM pending r WHERE r.pallet_id=b.pallet_id)
        UNION ALL SELECT b.id,b.pallet_id,b.ean,b.name,b.expires,b.units,b.kind,b.pack_size,b.inner_pack_size,b.revision FROM team_pallet_products b JOIN pending r ON r.id=b.request_id
        UNION ALL SELECT b.id,b.pallet_id,b.ean,b.name,b.expires,0 AS units,b.kind,b.pack_size,b.inner_pack_size,b.revision FROM pallet_products b JOIN pending r ON r.pallet_id=b.pallet_id WHERE NOT EXISTS(SELECT 1 FROM team_pallet_products x WHERE x.request_id=r.id AND x.ean=b.ean AND x.expires=b.expires)
    ) ";
}
