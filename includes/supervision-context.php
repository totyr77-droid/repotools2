<?php
declare(strict_types=1);
require_once __DIR__.'/stock.php';

/** Request-local scope. Every request binds its own team; nothing is stored in a shared session. */
final class SupervisionContext {
    private static ?WeakMap $scopes=null;
    public static function bind(PDO $db,int $user,int $team): void {
        if($team<1||!stock_query($db,'SELECT 1 FROM supervisor_teams WHERE id=? AND owner_id=?',[$team,$user])->fetchColumn())throw new CatalogException('Este equipo no está disponible para tu cuenta.',403);
        self::$scopes??=new WeakMap();$scopes=self::$scopes[$db]??[];$scopes[$user]=$team;self::$scopes[$db]=$scopes;
    }
    public static function selected(PDO $db,int $user): int {return self::$scopes[$db][$user]??0;}
}
function supervision_owned(PDO $db,int $user): array {
    return stock_query($db,'SELECT t.id,t.name,t.active,t.name_pending,t.billing_required,t.revision,s.status,s.paid_until,(SELECT COUNT(*) FROM supervisor_members m WHERE m.team_id=t.id AND m.status IN (\'pending\',\'accepted\')) AS occupied FROM supervisor_teams t LEFT JOIN supervision_subscriptions s ON s.team_id=t.id AND s.user_id=t.owner_id WHERE t.owner_id=? ORDER BY t.id',[$user])->fetchAll();
}
function supervision_billing_team(PDO $db,int $user,int $team=0): int {
    if($team){if(!stock_query($db,'SELECT 1 FROM supervisor_teams WHERE id=? AND owner_id=?',[$team,$user])->fetchColumn())throw new CatalogException('Este equipo no está disponible para tu cuenta.',403);return $team;}
    $teams=supervision_owned($db,$user);if(count($teams)>1)throw new CatalogException('Elegí el equipo que querés consultar.',409);
    return (int)($teams[0]['id']??0);
}
/** Prepare one reusable unpaid draft. This action never contacts the payment provider. */
function supervision_prepare_team(PDO $db,int $user): int {
    $ownTransaction=!$db->inTransaction();if($ownTransaction)$db->beginTransaction();try{
        stock_query($db,'UPDATE users SET name=name WHERE id=?',[$user]);
        $draft=stock_query($db,"SELECT t.id FROM supervisor_teams t LEFT JOIN supervision_subscriptions s ON s.team_id=t.id WHERE t.owner_id=? AND t.billing_required=1 AND t.name_pending=1 AND (s.reference IS NULL OR s.status IN ('new','creating','pending','authorized')) AND COALESCE(s.paid_until,0)=0 ORDER BY t.id LIMIT 1",[$user])->fetchColumn();
        if(!$draft)$draft=stock_query($db,"INSERT INTO supervisor_teams(owner_id,name,billing_required,name_pending) VALUES(?,'Mi equipo',1,1) RETURNING id",[$user])->fetchColumn();
        if($ownTransaction)$db->commit();return (int)$draft;
    }catch(Throwable $e){if($ownTransaction&&$db->inTransaction())$db->rollBack();throw $e;}
}
