<?php
/**
 * Session bootstrap + auth helper functions.
 * Include this at the TOP of every protected page (before any HTML output).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

require_once __DIR__ . '/config.php';

/**
 * True if a user is currently logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login if not authenticated. Call at the top of protected pages.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'index.php?error=session_expired');
        exit;
    }
}

/**
 * Redirect to login if not authenticated AND not an admin.
 */
function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ' . BASE_URL . 'dashboard.php?error=unauthorized');
        exit;
    }
}

/**
 * Redirect to login if not authenticated AND not a HOD.
 */
function requireHod(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'hod') {
        header('Location: ' . BASE_URL . 'dashboard.php?error=unauthorized');
        exit;
    }
}

/**
 * Redirect to login if not authenticated AND not a lecturer.
 */
function requireLecturer(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'lecturer') {
        header('Location: ' . BASE_URL . 'dashboard.php?error=unauthorized');
        exit;
    }
}

/**
 * Redirect to login if not authenticated AND not a student.
 */
function requireStudent(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'student') {
        header('Location: ' . BASE_URL . 'dashboard.php?error=unauthorized');
        exit;
    }
}

/**
 * Generate (or reuse) a CSRF token for the current session.
 */
function csrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify a submitted CSRF token against the session token.
 */
function verifyCsrf(?string $token): bool {
    return !empty($token) && !empty($_SESSION[CSRF_TOKEN_NAME])
        && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Escape output for safe HTML display.
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}