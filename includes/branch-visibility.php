<?php
declare(strict_types=1);
// Explicit directory IDs reserved for platform-manager testing. Names may change.
const BRANCH_MANAGER_ONLY_IDS = [12];
function branch_visible(PDO $db,int $branch,?int $user): bool {
    return !in_array($branch,BRANCH_MANAGER_ONLY_IDS,true)||($user&&stock_query($db,'SELECT role FROM users WHERE id=?',[$user])->fetchColumn()==='manager');
}
function branch_visibility_sql(PDO $db,?int $user,string $column): string {
    if($user&&stock_query($db,'SELECT role FROM users WHERE id=?',[$user])->fetchColumn()==='manager')return '1=1';
    return '('.$column.' IS NULL OR '.$column.' NOT IN ('.implode(',',BRANCH_MANAGER_ONLY_IDS).'))';
}
function branch_require_visible(PDO $db,int $branch,?int $user): void {
    if(!branch_visible($db,$branch,$user))throw new CatalogException('Esta sucursal no está disponible.',404);
}
