<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('login.php'));
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$role = (string) ($_POST['role'] ?? 'passenger');
$fullName = trim((string) ($_POST['full_name'] ?? ''));
$allowedRoles = ['passenger', 'driver', 'agent', 'admin'];
$role = in_array($role, $allowedRoles, true) ? $role : 'passenger';

if ($email === '' || $password === '') {
    $_SESSION['auth_error'] = 'Please provide both email and password.';
    header('Location: ' . app_url('login.php?role=' . urlencode($role)));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['auth_error'] = 'Please enter a valid email address.';
    header('Location: ' . app_url('login.php?role=' . urlencode($role)));
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['auth_error'] = 'Password must be at least 6 characters.';
    header('Location: ' . app_url('login.php?role=' . urlencode($role)));
    exit;
}

try {
    $ok = login_user($email, $password, $role);

    if (!$ok) {
        $existingUser = get_user_by_email($email);

        if ($existingUser === null) {
            if ($role === 'driver') {
                $_SESSION['auth_error'] = 'Driver accounts are created by agents. Please contact your agent first.';
                header('Location: ' . app_url('login.php?role=' . urlencode($role)));
                exit;
            }
            if ($fullName === '') {
                $namePart = explode('@', $email)[0] ?? 'User';
                $fullName = ucwords(str_replace(['.', '_', '-'], ' ', $namePart));
            }
            create_user_account($fullName, $email, $password, $role);
            $ok = login_user($email, $password, $role);
        } elseif ((string) $existingUser['role'] !== $role) {
            $_SESSION['auth_error'] = 'This email is already registered as ' . ucfirst((string) $existingUser['role']) . '. Please switch role.';
            header('Location: ' . app_url('login.php?role=' . urlencode($role)));
            exit;
        }
    }
} catch (Throwable $e) {
    $_SESSION['auth_error'] = 'Database connection issue. Import schema.sql and verify credentials in includes/config.php.';
    header('Location: ' . app_url('login.php?role=' . urlencode($role)));
    exit;
}

if (!$ok) {
    $_SESSION['auth_error'] = 'Invalid password for this account.';
    header('Location: ' . app_url('login.php?role=' . urlencode($role)));
    exit;
}

header('Location: ' . app_url('dashboard.php'));
exit;

