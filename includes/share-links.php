<?php
declare(strict_types=1);

function stock_share_token(): string {
    return rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
}

function stock_share_token_valid(string $token): bool {
    return preg_match('/^(?:[A-Za-z0-9_-]{22}|[a-f0-9]{64})$/D', $token) === 1;
}

function stock_share_insert(PDO $db, int $owner, ?int $branch, string $title, ?callable $factory = null): array {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $token = ($factory ?? 'stock_share_token')();
        if (!is_string($token) || !preg_match('/^[A-Za-z0-9_-]{22}$/D', $token)) throw new RuntimeException('No pudimos crear el enlace.');
        $q = $db->prepare('INSERT INTO stock_shares(owner_id,token,branch_id,title) VALUES(?,?,?,?) ON CONFLICT(token) DO NOTHING RETURNING id');
        $q->execute([$owner,$token,$branch,$title]);
        if ($id = $q->fetchColumn()) return ['id'=>(int)$id,'token'=>$token];
    }
    throw new RuntimeException('No pudimos crear el enlace. Intenta nuevamente.');
}
