<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        set_flash('error', 'Please log in to continue.');
        redirect('/login.php');
    }
}

function require_admin(): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        set_flash('error', 'Admin access required.');
        redirect('/login.php');
    }
}

function login_user(int $user_id): void
{
    $_SESSION['user_id'] = $user_id;
}

function logout_user(): void
{
    session_destroy();
}
