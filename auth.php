<?php
session_start();
require_once __DIR__ . '/db.php';

function get_users(): array
{
    $db = get_db();
    $stmt = $db->query("SELECT id, name, email, password, role, created_at FROM users ORDER BY id");
    return $stmt->fetchAll();
}

function find_user_by_email(string $email): ?array
{
    $db = get_db();
    $stmt = $db->prepare("SELECT id, name, email, password, role, created_at FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => strtolower(trim($email))]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function create_user(string $name, string $email, string $password, string $role = 'customer', string $phone = '', string $location = ''): void
{
    $db = get_db();
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role, phone, location) VALUES (:name, :email, :password, :role, :phone, :location)");
    $stmt->execute([
        'name' => trim($name),
        'email' => strtolower(trim($email)),
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'phone' => $phone,
        'location' => $location,
    ]);
}

/**
 * Seeds the first administrator account.
 *
 * Credentials come from ADMIN_EMAIL / ADMIN_PASSWORD in .env. When no password
 * is configured nothing is created, so the account can never be reached with a
 * publicly known default credential.
 */
function ensure_default_admin(): void
{
    $password = env('ADMIN_PASSWORD');

    if ($password === null) {
        return;
    }

    $email = env('ADMIN_EMAIL', 'admin@portfoliohub.com') ?? 'admin@portfoliohub.com';
    $db = get_db();

    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();

    if ($stmt->fetch()) {
        return;
    }

    $db->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'admin')")
        ->execute([
            'name' => 'System Admin',
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
}

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;
    return is_array($user) ? $user : null;
}

function redirect_if_logged_in(string $defaultPath = 'dashboard.php'): void
{
    $user = current_user();
    if ($user) {
        $target = ($user['role'] ?? 'customer') === 'admin' ? 'admin-dashboard.php' : $defaultPath;
        header('Location: ' . $target);
        exit;
    }
}

function require_login(string $role = 'customer'): void
{
    $user = current_user();

    if (!$user) {
        header('Location: login.php');
        exit;
    }

    if (($user['role'] ?? 'customer') !== $role) {
        // Send the user to the dashboard matching THEIR OWN role
        $target = (($user['role'] ?? 'customer') === 'admin') ? 'admin-dashboard.php' : 'dashboard.php';
        header('Location: ' . $target);
        exit;
    }
}
