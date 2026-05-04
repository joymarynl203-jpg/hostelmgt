<?php

/**
 * Stores PHP sessions in the database so CSRF + login survive Render cold starts
 * and multiple web instances (file sessions in /tmp do not).
 */
final class HmsPdoSessionHandler implements SessionHandlerInterface
{
    private string $driver;

    public function __construct(private PDO $pdo)
    {
        $this->driver = strtolower((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare('SELECT data FROM hms_sessions WHERE id = ? AND expire > ?');
        $stmt->execute([$id, time()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return '';
        }
        return (string) $row['data'];
    }

    public function write(string $id, string $data): bool
    {
        $expire = time() + (int) ini_get('session.gc_maxlifetime');
        if ($this->driver === 'pgsql') {
            $sql = 'INSERT INTO hms_sessions (id, expire, data) VALUES (?, ?, ?)
                ON CONFLICT (id) DO UPDATE SET expire = EXCLUDED.expire, data = EXCLUDED.data';
            return $this->pdo->prepare($sql)->execute([$id, $expire, $data]);
        }
        // MySQL / MariaDB
        $sql = 'REPLACE INTO hms_sessions (id, expire, data) VALUES (?, ?, ?)';
        return $this->pdo->prepare($sql)->execute([$id, $expire, $data]);
    }

    public function destroy(string $id): bool
    {
        $this->pdo->prepare('DELETE FROM hms_sessions WHERE id = ?')->execute([$id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $cutoff = time() - $max_lifetime;
        $stmt = $this->pdo->prepare('DELETE FROM hms_sessions WHERE expire < ?');
        $stmt->execute([$cutoff]);
        return $stmt->rowCount();
    }
}
