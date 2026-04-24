<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../db.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function get_csrf_token(): string
{
    return $_SESSION['csrf_token'] ?? '';
}

function ensure_flash(): void
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
}

function flash_set(string $type, string $message): void
{
    ensure_flash();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_render(): void
{
    ensure_flash();
    if (empty($_SESSION['flash'])) {
        return;
    }

    foreach ($_SESSION['flash'] as $item) {
        $type = $item['type'] ?? 'info';
        $message = $item['message'] ?? '';
        $class = match ($type) {
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            default => 'alert-info',
        };
        echo '<div class="alert ' . $class . ' alert-dismissible fade show mb-3" role="alert">'
            . e($message)
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }

    $_SESSION['flash'] = [];
}

function require_post(): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo 'Method Not Allowed';
        exit;
    }
}

function require_get(): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'GET') {
        http_response_code(405);
        echo 'Method Not Allowed';
        exit;
    }
}

/** HTML fragment: ` data-hms-confirm="..."` for use on `<a>` or `<form>` (handled by public/app.js). */
function hms_data_confirm(string $message): string
{
    return ' data-hms-confirm="' . e($message) . '"';
}

