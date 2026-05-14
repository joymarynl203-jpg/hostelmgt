<?php

require_once __DIR__ . '/config.php';

function hms_db_config_from_url(string $url): ?array
{
    if ($url === '') {
        return null;
    }

    $parts = parse_url($url);
    if (!is_array($parts) || !isset($parts['scheme'], $parts['host'], $parts['path'])) {
        return null;
    }

    $scheme = strtolower((string) $parts['scheme']);
    $driver = ($scheme === 'postgres' || $scheme === 'postgresql') ? 'pgsql' : (($scheme === 'mysql') ? 'mysql' : '');
    if ($driver === '') {
        return null;
    }

    $dbName = ltrim((string) $parts['path'], '/');
    if ($dbName === '') {
        return null;
    }

    $query = [];
    if (isset($parts['query'])) {
        parse_str((string) $parts['query'], $query);
    }

    return [
        'driver' => $driver,
        'host' => (string) $parts['host'],
        'port' => isset($parts['port']) ? (int) $parts['port'] : (($driver === 'pgsql') ? 5432 : 3306),
        'dbname' => $dbName,
        'user' => isset($parts['user']) ? urldecode((string) $parts['user']) : '',
        'pass' => isset($parts['pass']) ? urldecode((string) $parts['pass']) : '',
        'sslmode' => isset($query['sslmode']) ? (string) $query['sslmode'] : HMS_DB_SSLMODE,
    ];
}

function hms_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = hms_db_config_from_url(HMS_DATABASE_URL);
    if ($cfg === null) {
        $cfg = [
            'driver' => HMS_DB_DRIVER,
            'host' => HMS_DB_HOST,
            'port' => HMS_DB_PORT,
            'dbname' => HMS_DB_NAME,
            'user' => HMS_DB_USER,
            'pass' => HMS_DB_PASS,
            'sslmode' => HMS_DB_SSLMODE,
        ];
    }

    if ($cfg['driver'] === 'pgsql') {
        $dsn = 'pgsql:host=' . $cfg['host']
            . ';port=' . (int) $cfg['port']
            . ';dbname=' . $cfg['dbname']
            . ';sslmode=' . $cfg['sslmode'];
    } else {
        $dsn = 'mysql:host=' . $cfg['host']
            . ';port=' . (int) $cfg['port']
            . ';dbname=' . $cfg['dbname']
            . ';charset=utf8mb4';
    }

    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Database connection failed. Please check configuration.';
        exit;
    }

    hms_ensure_pgsql_users_role_allows_super_admin($pdo);

    return $pdo;
}

/**
 * PostgreSQL on Render (free tier): no shell/psql required. Older HMS DBs may have a users.role CHECK
 * that omits super_admin, which blocks role updates. This runs once per request, is idempotent, and
 * does not change password hashes. Set env HMS_DISABLE_PG_ROLE_SUPER_ADMIN_AUTO_FIX=1 to skip.
 */
function hms_ensure_pgsql_users_role_allows_super_admin(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (hms_env('HMS_DISABLE_PG_ROLE_SUPER_ADMIN_AUTO_FIX', '') === '1') {
        return;
    }

    if (!hms_db_is_pgsql($pdo)) {
        return;
    }

    try {
        $hasUsers = $pdo->query(
            "SELECT EXISTS (
                SELECT 1 FROM information_schema.tables
                WHERE table_schema = 'public' AND table_name = 'users'
            )"
        )->fetchColumn();
        if (!$hasUsers || $hasUsers === 'f') {
            return;
        }

        $alreadyOk = $pdo->query(
            "SELECT EXISTS (
                SELECT 1
                FROM pg_constraint c
                JOIN pg_class r ON r.oid = c.conrelid
                JOIN pg_namespace n ON n.oid = r.relnamespace
                WHERE c.contype = 'c'
                  AND n.nspname = 'public'
                  AND r.relname = 'users'
                  AND pg_get_constraintdef(c.oid) ILIKE '%super_admin%'
            )"
        )->fetchColumn();
        if ($alreadyOk && $alreadyOk !== 'f') {
            return;
        }

        try {
            $pdo->exec(
                <<<'SQL'
DO $$
DECLARE
  conname text;
BEGIN
  FOR conname IN
    SELECT c.conname::text
    FROM pg_constraint c
    JOIN pg_class rel ON rel.oid = c.conrelid
    JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
    WHERE c.contype = 'c'
      AND nsp.nspname = 'public'
      AND rel.relname = 'users'
      AND pg_get_constraintdef(c.oid) ILIKE '%role%'
  LOOP
    EXECUTE format('ALTER TABLE users DROP CONSTRAINT IF EXISTS %I', conname);
  END LOOP;
END $$;

ALTER TABLE users
  ADD CONSTRAINT users_role_check
  CHECK (role IN ('student', 'warden', 'university_admin', 'super_admin'));
SQL
            );
        } catch (PDOException $e) {
            // Duplicate from concurrent workers or partial run; migration 013 is the manual fallback.
        }

        try {
            $pdo->exec(
                "UPDATE users SET role = 'super_admin'
                 WHERE LOWER(email) = 'joymarynl203@gmail.com'
                   AND role IS DISTINCT FROM 'super_admin'"
            );
        } catch (PDOException $e) {
        }
    } catch (PDOException $e) {
        // Table missing or permission issue; manual migration 013 still available.
    }
}

function hms_db_is_pgsql(PDO $db): bool
{
    return strtolower((string) $db->getAttribute(PDO::ATTR_DRIVER_NAME)) === 'pgsql';
}

/** SQL expression: timestamp N days before now (WHERE col >= expr). */
function hms_sql_days_ago(PDO $db, int $days): string
{
    $d = max(0, min(3650, $days));
    if (hms_db_is_pgsql($db)) {
        return "(NOW() - INTERVAL '" . $d . " days')";
    }
    return 'DATE_SUB(NOW(), INTERVAL ' . $d . ' DAY)';
}

/** SQL expression: timestamp N minutes before now (WHERE col < expr). */
function hms_sql_minutes_ago(PDO $db, int $minutes): string
{
    $m = max(0, min(525600, $minutes));
    if (hms_db_is_pgsql($db)) {
        return "(NOW() - INTERVAL '" . $m . " minutes')";
    }
    return 'DATE_SUB(NOW(), INTERVAL ' . $m . ' MINUTE)';
}

