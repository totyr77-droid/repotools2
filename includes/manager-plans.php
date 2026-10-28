<?php
declare(strict_types=1);
require_once __DIR__.'/pro-subscriptions.php';
require_once __DIR__.'/audit.php';

function manager_plan_rows(PDO $db,int $user): array {
    $rows=[];
    foreach(['pro'=>'pro_subscriptions','supervision'=>'supervision_subscriptions'] as $product=>$table){
        $all=stock_query($db,"SELECT * FROM $table WHERE user_id=? ORDER BY reference",[$user])->fetchAll();$rows[$product]=array_shift($all)?:null;foreach($all as $row)$rows[$product.':'.$row['reference']]=$row;
    }
    return $rows;
}
function manager_plan_version(int $user,array $rows): string {
    $identity=[];
    foreach($rows as $product=>$row)$identity[$product]=$row?[$row['reference'],$row['provider_id'],(int)$row['amount_cents'],(int)($row['team_id']??0)]:null;
    return hash('sha256',json_encode([$user,$identity],JSON_THROW_ON_ERROR));
}
function manager_plan_controls(PDO $db,array $actor,int $target): array {
    if($actor['role']!=='manager'||(function_exists('support_active')&&support_active())||!pro_config()['enabled'])return ['can_reset_plans'=>false];
    $rows=manager_plan_rows($db,$target);$plans=[];
    foreach($rows as $product=>$row)if($row)$plans[]=['team_name'=>!empty($row['team_id'])?(stock_query($db,'SELECT name FROM supervisor_teams WHERE id=? AND owner_id=?',[$row['team_id'],$target])->fetchColumn()?:''):'','product'=>explode(':',$product)[0],'status'=>$row['status'],'active'=>(int)$row['paid_until']>time()];
    return ['can_reset_plans'=>true,'plans'=>$plans,'plans_version'=>manager_plan_version($target,$rows)];
}
function manager_plan_require(PDO $db,int $actor): void {
    if(stock_query($db,'SELECT role FROM users WHERE id=?',[$actor])->fetchColumn()!=='manager'||(function_exists('support_active')&&support_active()))throw new CatalogException('Solo un manager puede quitar planes.',403);
}
/** Cancel existing renewals, then archive only billing records. Never edits a team or stock. */
function manager_remove_plans(PDO $db,int $actor,int $target,string $version,?array $settings=null,?callable $transport=null): bool {
    manager_plan_require($db,$actor);
    $settings??=['pro'=>pro_config(),'supervision'=>pro_config('supervision')];
    if(empty($settings['pro']['enabled'])||($settings['pro']['url']??'')!=='https://repotools-desarrollo.onrender.com')throw new CatalogException('Esta acción está disponible en beta con pagos de prueba configurados.',503);
    if($target<1||!stock_query($db,'SELECT id FROM users WHERE id=?',[$target])->fetchColumn())throw new CatalogException('No encontramos esa cuenta.',404);
    if(!preg_match('/^[a-f0-9]{64}$/D',$version))throw new CatalogException('Volvé a abrir la cuenta antes de quitar sus planes.',422);
    $rows=manager_plan_rows($db,$target);
    if(!array_filter($rows))return false;
    if(!hash_equals(manager_plan_version($target,$rows),$version))throw new CatalogException('Los planes cambiaron. Volvé a abrir la cuenta.',409);
    // Preflight every product before making any remote cancellation.
    foreach($rows as $product=>$row)if($row){
        if($row['provider_id']===null&&!in_array($row['status'],['new','beta_trial','creating'],true))throw new CatalogException('Hay un intento en verificación. Comprobá el pago antes de quitar los planes.',409);
        if(($row['provider_id']!==null||$row['status']==='creating')&&empty($settings[explode(':',$product)[0]]['enabled']))throw new CatalogException('Falta configurar la cancelación de uno de los planes.',503);
    }
    $original=$rows;
    foreach($rows as $product=>$row)if($row&&$row['provider_id']===null&&$row['status']==='creating'){
        (new ProSubscriptions($db,[...$settings[explode(':',$product)[0]],'team_id'=>(int)($row['team_id']??0)],$transport))->reconcile($target);
    }
    $rows=manager_plan_rows($db,$target);
    foreach($rows as $product=>$row){
        if(($row['reference']??null)!==($original[$product]['reference']??null))throw new CatalogException('Los planes cambiaron. Volvé a abrir la cuenta.',409);
        if($row&&$row['provider_id']===null&&$row['status']==='creating')throw new CatalogException('El proveedor todavía no identificó el intento. No quitamos los planes para evitar una renovación sin cancelar.',409);
    }
    $resolvedVersion=manager_plan_version($target,$rows);
    try{foreach($rows as $product=>$row)if($row&&$row['provider_id']!==null)(new ProSubscriptions($db,[...$settings[explode(':',$product)[0]],'team_id'=>(int)($row['team_id']??0)],$transport))->cancel($target,$row['reference']);}
    catch(CatalogException $e){if($e->status===409)throw $e;throw new CatalogException('No pudimos confirmar todas las cancelaciones. Los planes todavía no se quitaron. Volvé a intentar.',503);}
    $db->beginTransaction();
    try{
        // Lock identity as well as billing rows so concurrent checkout cannot replace this account's plans.
        stock_query($db,'SELECT id FROM users WHERE id=?'.($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'?' FOR UPDATE':''),[$target])->fetch();
        manager_plan_require($db,$actor);
        foreach(['pro_subscriptions','supervision_subscriptions'] as $table)stock_query($db,"UPDATE $table SET updated_at=updated_at WHERE user_id=?",[$target]);
        $current=manager_plan_rows($db,$target);
        if(!array_filter($current)){$db->commit();return false;}
        if(!hash_equals(manager_plan_version($target,$current),$resolvedVersion))throw new CatalogException('Los planes cambiaron durante la cancelación. Volvé a abrir la cuenta.',409);
        $before=[];
        foreach($current as $key=>$row){
            if(!$row)continue;$product=explode(':',$key)[0];[$subscriptions,$payments]=$product==='pro'?['pro_subscriptions','pro_payments']:['supervision_subscriptions','supervision_payments'];
            if($row['provider_id']!==null&&$row['status']!=='cancelled')throw new CatalogException('La cancelación todavía no está confirmada.',409);
            $history=stock_query($db,"SELECT * FROM $payments WHERE reference=?",[$row['reference']])->fetchAll();
            stock_query($db,'INSERT INTO billing_cancelled_attempts(reference,user_id,product,snapshot,archived_at) VALUES(?,?,?,?,?)',[$row['reference'],$target,$product,json_encode(['reason'=>'manager_requested_plan_reset','actor_id'=>$actor,'subscription'=>$row,'payments'=>$history],JSON_THROW_ON_ERROR),time()]);
            $before[$key]=['status'=>$row['status'],'active'=>(int)$row['paid_until']>time()];
            stock_query($db,"DELETE FROM $payments WHERE reference=?",[$row['reference']]);
            stock_query($db,"DELETE FROM $subscriptions WHERE reference=? AND user_id=?",[$row['reference'],$target]);
        }
        audit_event($db,$actor,'plans_removed','plans:'.$target.':'.$version,['category'=>'users','target_user'=>$target],$before,['plans'=>[]]);
        $db->commit();return true;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
