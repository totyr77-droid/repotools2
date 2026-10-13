<?php
declare(strict_types=1);

function account_preferences(PDO $db,int $userId): ?array {
    $query=$db->prepare('SELECT u.handedness,u.sound_enabled,u.vibration_enabled,p.expiry_days FROM users u LEFT JOIN stock_preferences p ON p.user_id=u.id WHERE u.id=?');
    $query->execute([$userId]);$row=$query->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    return ['handedness'=>(string)$row['handedness'],'sound'=>(int)($row['sound_enabled']??1)!==0,'vibration'=>(int)($row['vibration_enabled']??1)!==0,'expiry_days'=>$row['expiry_days']===null?30:(int)$row['expiry_days'],'profile_pending'=>$row['handedness']===''];
}

function save_account_preferences(PDO $db,int $userId,array $input): void {
    $updates=[];$values=[];$days=null;
    if (array_key_exists('handedness',$input)) {
        if (!is_string($input['handedness'])||!in_array($input['handedness'],['left','right'],true)) throw new InvalidArgumentException('Elegí Zurdo o Diestro.');
        $updates[]='handedness=?';$values[]=$input['handedness'];
    }
    foreach (['sound'=>'sound_enabled','vibration'=>'vibration_enabled'] as $field=>$column) {
        if (!array_key_exists($field,$input)) continue;
        if (!is_string($input[$field])||!in_array($input[$field],['0','1'],true)) throw new InvalidArgumentException('Revisá la preferencia de sonido o vibración.');
        $updates[]=$column.'=?';$values[]=(int)$input[$field];
    }
    if (array_key_exists('expiry_days',$input)) {
        if (!is_string($input['expiry_days'])) throw new InvalidArgumentException('Elegí entre 0 y 365 días de anticipación.');
        $days=filter_var($input['expiry_days'],FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>365]]);
        if ($days===false) throw new InvalidArgumentException('Elegí entre 0 y 365 días de anticipación.');
    }
    if (!$updates&&$days===null) throw new InvalidArgumentException('Elegí una preferencia para guardar.');
    $db->beginTransaction();
    try {
        if ($updates) {$values[]=$userId;$db->prepare('UPDATE users SET '.implode(',',$updates).' WHERE id=?')->execute($values);}
        if ($days!==null) $db->prepare('INSERT INTO stock_preferences(user_id,expiry_days) VALUES(?,?) ON CONFLICT(user_id) DO UPDATE SET expiry_days=excluded.expiry_days')->execute([$userId,$days]);
        $db->commit();
    } catch (Throwable $error) {if($db->inTransaction())$db->rollBack();throw $error;}
}
