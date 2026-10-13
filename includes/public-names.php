<?php
declare(strict_types=1);

function generate_public_user_name(string $name, int $id): string {
    if (!mb_check_encoding($name, 'UTF-8') || preg_match('/[\p{C}<>]/u', $name)) return 'Usuario #'.$id;
    $name=trim(preg_replace('/\s+/u', ' ', $name) ?? '');
    if ($name==='' || !preg_match('/\p{L}/u', $name)) return 'Usuario #'.$id;
    $parts=explode(' ', $name);
    return $parts[0].(isset($parts[1]) ? ' '.mb_substr($parts[1],0,1,'UTF-8').'.' : '');
}

function get_public_user_name(array $user): string {
    $public=$user['public_name']??null;
    if (is_string($public) && trim($public)!=='' && mb_check_encoding($public,'UTF-8') && !preg_match('/[\p{C}<>]/u',$public)) return trim($public);
    return generate_public_user_name(is_string($user['name']??null)?$user['name']:'',(int)($user['id']??$user['user_id']??0));
}

function validate_public_user_name(mixed $value): ?string {
    if (!is_string($value) || !mb_check_encoding($value,'UTF-8') || preg_match('/[\p{C}\r\n]/u',$value)) return null;
    $value=trim(preg_replace('/ +/u',' ',$value)??'');
    if (mb_strlen($value,'UTF-8')<3 || mb_strlen($value,'UTF-8')>24 || !preg_match('/^[\p{L}\p{M}\p{N} ._-]+$/uD',$value)) return null;
    return $value;
}

function backfill_public_user_names(PDO $db): int {
    $count=0;
    $query=$db->query("SELECT id,name FROM users WHERE public_name IS NULL OR TRIM(public_name)=''");
    $update=$db->prepare("UPDATE users SET public_name=? WHERE id=? AND (public_name IS NULL OR TRIM(public_name)='')");
    foreach($query->fetchAll(PDO::FETCH_ASSOC) as $user){$update->execute([generate_public_user_name((string)$user['name'],(int)$user['id']),$user['id']]);$count+=$update->rowCount();}
    return $count;
}
