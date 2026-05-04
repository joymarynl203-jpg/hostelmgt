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

    return $pdo;
}

