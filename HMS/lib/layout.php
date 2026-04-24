<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

function layout_header(string $title, array $options = []): void
{
    $user = hms_current_user();
    $isAuthenticated = (bool)$user;
    $GLOBALS['hms_layout_authenticated'] = $isAuthenticated;

    $links = [];
    if ($isAuthenticated) {
        if ($user['role'] === 'student') {
            $links = [
                ['label' => 'Dashboard', 'href' => hms_url('dashboard.php')],
                ['label' => 'Notifications', 'href' => hms_url('notifications.php')],
                ['label' => 'Hostels', 'href' => hms_url('hostels.php')],
                ['label' => 'My Bookings', 'href' => hms_url('bookings.php')],
                ['label' => 'My Payments', 'href' => hms_url('my_payments.php')],
                ['label' => 'Maintenance', 'href' => hms_url('maintenance.php')],
                ['label' => 'Change password', 'href' => hms_url('change_password.php')],
                ['label' => 'Logout', 'href' => hms_url('logout.php'), 'confirm' => 'Log out? You will need to sign in again.'],
            ];
        } elseif ($user['role'] === 'warden') {
            $links = [
                ['label' => 'Dashboard', 'href' => hms_url('dashboard.php')],
                ['label' => 'Notifications', 'href' => hms_url('notifications.php')],
                ['label' => 'Manage Hostels', 'href' => hms_url('admin/hostels.php')],
                ['label' => 'Booking Approvals', 'href' => hms_url('admin/bookings.php')],
                ['label' => 'Students by room', 'href' => hms_url('admin/students_by_room.php')],
                ['label' => 'Maintenance', 'href' => hms_url('admin/maintenance.php')],
                ['label' => 'Payments', 'href' => hms_url('admin/payments.php')],
                ['label' => 'Reports', 'href' => hms_url('admin/reports.php')],
                ['label' => 'Change password', 'href' => hms_url('change_password.php')],
                ['label' => 'Logout', 'href' => hms_url('logout.php'), 'confirm' => 'Log out? You will need to sign in again.'],
            ];
        } else {
            $links = [
                ['label' => 'Dashboard', 'href' => hms_url('dashboard.php')],
                ['label' => 'Notifications', 'href' => hms_url('notifications.php')],
                ['label' => 'Manage Hostels', 'href' => hms_url('admin/hostels.php')],
                ['label' => 'Manage Rooms', 'href' => hms_url('admin/rooms.php')],
                ['label' => 'Students by room', 'href' => hms_url('admin/students_by_room.php')],
                ['label' => 'Oversight Reports', 'href' => hms_url('admin/reports.php')],
                ['label' => 'Payments', 'href' => hms_url('admin/payments.php')],
                ['label' => 'User Management', 'href' => hms_url('admin/users.php')],
                ['label' => 'Change password', 'href' => hms_url('change_password.php')],
                ['label' => 'Logout', 'href' => hms_url('logout.php'), 'confirm' => 'Log out? You will need to sign in again.'],
            ];
        }
    }

    $activePath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $activeBase = basename($activePath);
    $bodyClass = isset($options['body_class']) ? trim((string)$options['body_class']) : '';
    $stylesVersion = (string)(@filemtime(__DIR__ . '/../public/styles.css') ?: time());
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?php echo e($title); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo hms_url('styles.css?v=' . $stylesVersion); ?>" rel="stylesheet">
    </head>
    <body<?php echo $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : ''; ?>>
    <?php if ($isAuthenticated): ?>
        <div class="app-shell">
            <aside class="app-sidebar">
                <a class="app-brand fw-bold" href="<?php echo hms_url(); ?>">HOSTEL MANAGEMENT SYSTEM</a>
                <div class="app-user-meta">
                    <div class="fw-semibold"><?php echo e($user['name']); ?></div>
                    <span class="badge bg-light text-primary mt-2"><?php echo e($user['role']); ?></span>
                </div>
                <nav class="app-menu">
                    <?php foreach ($links as $link): ?>
                        <?php $isActive = basename(parse_url($link['href'], PHP_URL_PATH) ?: '') === $activeBase; ?>
                        <a class="app-menu-link <?php echo $isActive ? 'active' : ''; ?>" href="<?php echo $link['href']; ?>"
                            <?php echo !empty($link['confirm']) ? hms_data_confirm((string)$link['confirm']) : ''; ?>>
                            <?php echo e($link['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>
            <main class="app-main">
                <div class="container-fluid py-4 px-lg-4 px-3">
                    <?php flash_render(); ?>
    <?php else: ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
            <div class="container">
                <a class="navbar-brand fw-bold" href="<?php echo hms_url(); ?>">
                    HOSTEL MANAGEMENT SYSTEM
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navMain">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                        <li class="nav-item"><a class="nav-link" href="<?php echo hms_url('login.php'); ?>">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo hms_url('forgot_password.php'); ?>">Forgot password</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo hms_url('register.php'); ?>">Register</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="container py-4">
            <?php flash_render(); ?>
    <?php endif; ?>
    <?php
}

function layout_footer(): void
{
    $isAuthenticated = (bool)($GLOBALS['hms_layout_authenticated'] ?? false);
    ?>
    <?php if ($isAuthenticated): ?>
                </div>
            </main>
        </div>
    <?php else: ?>
        </div>
    <?php endif; ?>
    <script src="<?php echo hms_url('app.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

