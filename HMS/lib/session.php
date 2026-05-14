<?php

require_once __DIR__ . '/../config.php';

function hms_should_use_database_sessions(): bool
{
    $store = HMS_SESSION_STORE;
    if ($store === 'file') {
        return false;
    }
    if ($store === 'database') {
        return true;
    }
    // auto: PostgreSQL (typical on Render) needs durable sessions for CSRF/login
    if (HMS_DB_DRIVER === 'pgsql') {
        return true;
    }
    $url = HMS_DATABASE_URL;
    return $url !== '' && (bool) preg_match('/^\s*(postgres|postgresql):\/\//i', $url);
}

/** Ensure session storage exists (avoids CSRF failures when migrations were skipped). */
function hms_ensure_sessions_table(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;
    $driver = strtolower((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    if ($driver === 'pgsql') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS hms_sessions (
                id VARCHAR(128) NOT NULL PRIMARY KEY,
                expire BIGINT NOT NULL,
                data TEXT NOT NULL
            )'
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_hms_sessions_expire ON hms_sessions (expire)');
        return;
    }
    if ($driver === 'mysql') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS hms_sessions (
                id VARCHAR(128) NOT NULL PRIMARY KEY,
                expire INT NOT NULL,
                data MEDIUMTEXT NOT NULL,
                KEY idx_hms_sessions_expire (expire)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }
}

// Centralized session configuration for the entire app.
if (session_status() !== PHP_SESSION_ACTIVE) {
    if (hms_should_use_database_sessions()) {
        require_once __DIR__ . '/../db.php';
        $pdo = hms_db();
        hms_ensure_sessions_table($pdo);
        require_once __DIR__ . '/session_handler_pdo.php';
        session_set_save_handler(new HmsPdoSessionHandler($pdo), true);
    }

    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $forwardedProto === 'https'
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

    $sameSite = HMS_SESSION_COOKIE_SAMESITE;
    // SameSite=None requires Secure=true (browser rule).
    $secure = $https || ($sameSite === 'None');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => $sameSite,
    ]);

    session_start();
}
