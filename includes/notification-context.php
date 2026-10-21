<?php
declare(strict_types=1);
// Resolve names from their records, never by splitting a translated notification body.
function notification_branch_context(PDO $db,array $items,int $user): array {
    $requests=[];$invites=[];
    foreach($items as $item){
        if($item['type']==='team_pallet_request'&&preg_match('/^team-pallet-request:([0-9]+)$/',$item['event_key'],$m))$requests[]=(int)$m[1];
        if($item['type']==='team_invitation'&&preg_match('/^team-invite:([a-f0-9]{64})$/',$item['event_key'],$m))$invites[]=$m[1];
    }
    $context=[];
    foreach([['team-pallet-request:', 'team_pallet_requests','id',$requests],['team-invite:','branch_team_invites','token_hash',$invites]] as [$prefix,$table,$key,$ids]){
        if(!$ids)continue;
        $extra=$table==='team_pallet_requests'?',(SELECT code FROM pallets p WHERE p.id=t.pallet_id) AS code':',NULL AS code';
        $marks=implode(',',array_fill(0,count($ids),'?'));
        foreach(stock_query($db,"SELECT t.{$key} AS context_key,b.id,b.name{$extra} FROM {$table} t JOIN stock_branches b ON b.id=t.branch_id WHERE t.{$key} IN ({$marks})",$ids)->fetchAll() as $row)$context[$prefix.$row['context_key']]=$row;
    }
    $result=[];
    foreach($items as $item){
        if($branch=$context[$item['event_key']]??null){
            if(!branch_visible($db,(int)$branch['id'],$user))continue;
            $item['branch_name']=$branch['name'];
            if($item['type']==='team_pallet_request')$item['body']='Solicita editar el palet '.($branch['code']??'').'. Revisá los cambios antes de aceptar.';
            else $item['body']='Te invitaron al equipo. Revisá la invitación para aceptar.';
        }
        $result[]=$item;
    }
    return $result;
}

function notification_visibility_sql(PDO $db,int $user): string {
    $request=branch_visibility_sql($db,$user,'r.branch_id');$invite=branch_visibility_sql($db,$user,'i.branch_id');
    return "NOT EXISTS(SELECT 1 FROM team_pallet_requests r WHERE n.type='team_pallet_request' AND n.event_key='team-pallet-request:' || CAST(r.id AS TEXT) AND NOT ({$request})) AND NOT EXISTS(SELECT 1 FROM branch_team_invites i WHERE n.type='team_invitation' AND n.event_key='team-invite:' || i.token_hash AND NOT ({$invite}))";
}
