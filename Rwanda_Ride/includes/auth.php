<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function default_avatar_color_for_role(string $role): string
{
    return match ($role) {
        'driver' => '#22a06b',
        'agent' => '#f59f0b',
        'admin' => '#8250df',
        default => '#1f6feb',
    };
}

function set_authenticated_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
        'avatar_color' => (string) $user['avatar_color'],
    ];
}

function get_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT id, full_name, email, role, password_hash, avatar_color FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function create_user_account(string $fullName, string $email, string $password, string $role): bool
{
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare('
        INSERT INTO users (full_name, email, role, password_hash, avatar_color)
        VALUES (:full_name, :email, :role, :password_hash, :avatar_color)
    ');

    return $stmt->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':role' => $role,
        ':password_hash' => $passwordHash,
        ':avatar_color' => default_avatar_color_for_role($role),
    ]);
}

function login_user(string $email, string $password, string $role): bool
{
    $user = get_user_by_email($email);

    if (!$user) {
        return false;
    }

    if ((string) $user['role'] !== $role) {
        return false;
    }

    $passwordMatches = false;
    if (is_string($user['password_hash']) && str_starts_with($user['password_hash'], '$2y$')) {
        $passwordMatches = password_verify($password, $user['password_hash']);
    } else {
        $passwordMatches = hash_equals((string) $user['password_hash'], $password);
    }

    if (!$passwordMatches) {
        return false;
    }

    set_authenticated_user($user);

    return true;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_auth(?string $role = null): array
{
    $user = current_user();
    if (!$user) {
        header('Location: ' . app_url('login.php'));
        exit;
    }

    if ($role !== null && $user['role'] !== $role) {
        header('Location: ' . app_url('dashboard.php'));
        exit;
    }

    return $user;
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}

function set_flash(string $message): void
{
    $_SESSION['flash_message'] = $message;
}

function get_flash(): ?string
{
    if (!isset($_SESSION['flash_message'])) {
        return null;
    }
    $message = (string) $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    return $message;
}

