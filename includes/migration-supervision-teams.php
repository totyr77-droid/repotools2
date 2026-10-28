<?php
declare(strict_types=1);
function migrate_supervision_teams(PDO $db,string $ref): void {
    if($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql'){
        $db->exec('ALTER TABLE supervisor_teams DROP CONSTRAINT supervisor_teams_owner_id_key');
        $db->exec('ALTER TABLE supervision_subscriptions DROP CONSTRAINT supervision_subscriptions_user_id_key');
        $db->exec("ALTER TABLE supervision_subscriptions ADD COLUMN team_id {$ref} UNIQUE REFERENCES supervisor_teams(id)");
    }else{
        // Keep original IDs and table names so all existing foreign keys remain valid.
        $db->exec('PRAGMA defer_foreign_keys=ON');
        foreach(['supervisor_teams','supervision_subscriptions'] as $table){
            $sql=$db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();
            $sql=str_replace('CREATE TABLE '.$table,'CREATE TABLE '.$table.'_next',$sql);
            $column=$table==='supervisor_teams'?'owner_id':'user_id';
            $sql=preg_replace('/('.$column.'\s+INTEGER\s+NOT NULL)\s+UNIQUE/i','$1',$sql);
            if($table==='supervision_subscriptions')$sql=substr($sql,0,-1).',team_id INTEGER UNIQUE REFERENCES supervisor_teams(id))';
            $db->exec($sql);$columns=array_column($db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC),'name');$names=implode(',',$columns);
            $db->exec("INSERT INTO {$table}_next($names) SELECT $names FROM $table");
            $db->exec("DROP TABLE $table");$db->exec("ALTER TABLE {$table}_next RENAME TO $table");
        }
    }
    // Before this migration each owner had at most one subscription and one team.
    $db->exec("INSERT INTO supervisor_teams(owner_id,name,billing_required,name_pending) SELECT s.user_id,'Mi equipo',1,1 FROM supervision_subscriptions s WHERE NOT EXISTS(SELECT 1 FROM supervisor_teams t WHERE t.owner_id=s.user_id)");
    $db->exec('UPDATE supervision_subscriptions SET team_id=(SELECT t.id FROM supervisor_teams t WHERE t.owner_id=supervision_subscriptions.user_id)');
    $db->exec('CREATE INDEX supervisor_teams_owner ON supervisor_teams(owner_id,id)');
    $db->exec('CREATE INDEX supervision_subscriptions_owner ON supervision_subscriptions(user_id,team_id)');
    if($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite'){
        if($db->query('PRAGMA foreign_key_check')->fetch())throw new RuntimeException('Team migration foreign key check failed');
        // Rebuilding a referenced table leaves SQLite's deferred DROP counter set,
        // even after every original key has been restored. Clear it only after checking all keys.
        $db->exec('PRAGMA defer_foreign_keys=OFF');
    }
}
