<?php
declare(strict_types=1);

// Store only a digest of the browser credential. A bounded lease serializes
// requests without holding a transaction across the application's own queries.
final class DatabaseSessions implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface {
    public const LIFETIME = 30 * 86400;
    private array $locks = [];
    public function __construct(private PDO $db) {}
    public function open(string $path, string $name): bool { return true; }
    private function key(string $id): string { return hash('sha256', $id); }
    public function validateId(string $id): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM app_sessions WHERE id_hash=? AND expires_at>?');
        $stmt->execute([$this->key($id), time()]);
        return (bool)$stmt->fetchColumn();
    }
    public function read(string $id): string|false {
        $key = $this->key($id);
        $token = bin2hex(random_bytes(16));
        $deadline = microtime(true) + 5;
        do {
            $now = time();
            $this->db->prepare('INSERT INTO app_sessions(id_hash,expires_at) VALUES(?,?) ON CONFLICT(id_hash) DO NOTHING')->execute([$key, $now + self::LIFETIME]);
            $stmt = $this->db->prepare('UPDATE app_sessions SET lock_token=?,locked_until=? WHERE id_hash=? AND locked_until<=? AND expires_at>?');
            $stmt->execute([$token, $now + 120, $key, $now, $now]);
            if ($stmt->rowCount() === 1) {
                $this->locks[$key] = $token;
                $stmt = $this->db->prepare('SELECT payload FROM app_sessions WHERE id_hash=? AND lock_token=?');
                $stmt->execute([$key, $token]);
                $payload = $stmt->fetchColumn();
                return $payload === false ? false : (base64_decode($payload, true) ?: '');
            }
            // An expired/revoked identifier must never recover authentication.
            if (!$this->validateId($id)) return '';
            usleep(25000);
        } while (microtime(true) < $deadline);
        throw new RuntimeException('Session storage busy');
    }
    public function write(string $id, string $data): bool {
        $key = $this->key($id);
        if (!isset($this->locks[$key])) return false;
        $stmt = $this->db->prepare('UPDATE app_sessions SET payload=? WHERE id_hash=? AND lock_token=? AND locked_until>? AND expires_at>?');
        $stmt->execute([base64_encode($data), $key, $this->locks[$key], time(), time()]);
        return $stmt->rowCount() === 1;
    }
    public function updateTimestamp(string $id, string $data): bool { return $this->write($id, $data); }
    public function destroy(string $id): bool {
        $key = $this->key($id);
        if (isset($this->locks[$key])) {
            $this->db->prepare('DELETE FROM app_sessions WHERE id_hash=? AND lock_token=?')->execute([$key, $this->locks[$key]]);
            unset($this->locks[$key]);
        }
        return true;
    }
    public function close(): bool {
        foreach ($this->locks as $key => $token) {
            $this->db->prepare("UPDATE app_sessions SET lock_token='',locked_until=0 WHERE id_hash=? AND lock_token=?")->execute([$key, $token]);
        }
        $this->locks = [];
        return true;
    }
    public function gc(int $max_lifetime): int|false {
        $stmt = $this->db->prepare('DELETE FROM app_sessions WHERE expires_at<=? AND locked_until<=?');
        $stmt->execute([time(), time()]);
        return $stmt->rowCount();
    }
}
