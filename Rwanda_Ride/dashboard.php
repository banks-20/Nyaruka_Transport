<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$user = require_auth();
$target = match ($user['role']) {
    'admin' => 'admin-dashboard.php',
    'driver' => 'driver-dashboard.php',
    'agent' => 'agent-dashboard.php',
    default => 'passenger-dashboard.php',
};

header('Location: ' . app_url($target));
exit;

