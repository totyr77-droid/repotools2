<?php
require_once __DIR__.'/stock.php';
require_once __DIR__.'/reports.php';
require_once __DIR__.'/app-release.php';
function help_manager(PDO $db,int $user): bool {return stock_query($db,'SELECT role FROM users WHERE id=?',[$user])->fetchColumn()==='manager';}
function help_ticket(PDO $db,int $user,int $id): array {
    $t=stock_query($db,'SELECT t.*,u.public_name,u.name FROM support_tickets t JOIN users u ON u.id=t.user_id WHERE t.id=?',[$id])->fetch();
    if(!$t||((int)$t['user_id']!==$user&&!help_manager($db,$user)))throw new CatalogException('No encontramos esa consulta.',404);
    return $t;
}
function help_send(PDO $db,int $user,array $input): int {
    $action=$input['action']??'';$key=$input['request_key']??'';
    if(!is_string($key)||!preg_match('/^[a-f0-9]{32}$/D',$key))throw new CatalogException('Volvé a abrir el formulario.',422);
    $db->beginTransaction();try{report_lock($db);
        $old=stock_query($db,'SELECT ticket_id FROM support_messages WHERE user_id=? AND request_key=?',[$user,$key])->fetchColumn();if($old){$db->commit();return (int)$old;}
        $id=filter_var($input['ticket']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
        if($action==='resolve'){$t=help_ticket($db,$user,$id);stock_query($db,"UPDATE support_tickets SET status='resolved',updated_at=? WHERE id=?",[time(),$id]);$db->commit();return $id;}
        if(!in_array($action,['create','reply'],true))throw new CatalogException('Acción no válida.',422);
        $body=$input['message']??'';if(!is_string($body)||mb_strlen(trim($body))<2||mb_strlen($body)>3000)throw new CatalogException('Escribí un mensaje de 2 a 3000 caracteres.',422);$body=trim($body);
        $recent=(int)stock_query($db,'SELECT COUNT(*) FROM support_messages WHERE user_id=? AND created_at>?',[$user,time()-3600])->fetchColumn();if($recent>=30)throw new CatalogException('Llegaste al límite de mensajes. Volvé a intentar más tarde.',429);
        if($action==='create'){
            $subject=$input['subject']??'';$category=$input['category']??'';
            if(!is_string($subject)||mb_strlen(trim($subject))<3||mb_strlen($subject)>100)throw new CatalogException('Ingresá un asunto de 3 a 100 caracteres.',422);
            if(!is_string($category)||!in_array($category,['stock','account','pro','other'],true))throw new CatalogException('Elegí un tema.',422);
            $n=(int)stock_query($db,'SELECT COUNT(*) FROM support_tickets WHERE user_id=? AND created_at>?',[$user,time()-86400])->fetchColumn();if($n>=5)throw new CatalogException('Ya enviaste 5 consultas hoy. Podés continuar en una existente.',429);
            $q=stock_query($db,"INSERT INTO support_tickets(user_id,subject,category,app_version,created_at,updated_at) VALUES(?,?,?,?,?,?) RETURNING id",[$user,trim($subject),$category,(int)app_release()['version'],time(),time()]);$id=(int)$q->fetchColumn();$q->closeCursor();
        }else help_ticket($db,$user,$id);
        stock_query($db,'INSERT INTO support_messages(ticket_id,user_id,body,request_key,created_at) VALUES(?,?,?,?,?)',[$id,$user,$body,$key,time()]);
        stock_query($db,"UPDATE support_tickets SET status='open',updated_at=? WHERE id=?",[time(),$id]);$db->commit();return $id;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
