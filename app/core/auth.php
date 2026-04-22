<?php
// app/core/auth.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function auth_init(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function current_user(): ?array
{
    auth_init();
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    $u = current_user();

    if (!$u) {
        redirect('/public/login.php');
    }

    $stmt = db()->prepare("SELECT id, full_name, email, role, status FROM users WHERE id=? LIMIT 1");
    $stmt->execute([(int)($u['id'] ?? 0)]);
    $freshUser = $stmt->fetch();

    if (!$freshUser || ($freshUser['status'] ?? 'active') !== 'active') {
        logout_user();
        redirect('/public/login.php');
    }

    $_SESSION['user'] = [
        'id' => $freshUser['id'],
        'full_name' => $freshUser['full_name'],
        'email' => $freshUser['email'],
        'role' => $freshUser['role'],
        'status' => $freshUser['status'],
    ];
}

function require_role(string $role): void
{
    $u = current_user();
    if (!$u || ($u['role'] ?? '') !== $role) {
        http_response_code(403);
        die('Access denied');
    }
}

function logout_user(): void
{
    auth_init();
    session_destroy();
}
