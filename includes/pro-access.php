<?php
declare(strict_types=1);
require_once __DIR__.'/catalog.php';

final class ProRequiredException extends CatalogException {
    public function __construct(public string $feature){parent::__construct('Esta función requiere Pro.',403);}
}
function pro_has_access(PDO $db,int $user): bool {
    $q=$db->prepare('SELECT paid_until FROM pro_subscriptions WHERE user_id=?');$q->execute([$user]);
    return (int)$q->fetchColumn()>time();
}
function pro_require_access(PDO $db,int $user,string $feature): void {
    if(!pro_has_access($db,$user))throw new ProRequiredException($feature);
}
function pro_access_error(Throwable $error): array {
    return $error instanceof ProRequiredException?['code'=>'pro_required','feature'=>$error->feature]:[];
}
