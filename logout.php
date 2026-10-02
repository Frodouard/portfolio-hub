<?php
require_once __DIR__ . '/auth.php';

$user = current_user();
$_SESSION = [];
session_destroy();

header('Location: ' . (($user['role'] ?? 'customer') === 'admin' ? 'admin-login.php' : 'login.php'));
exit;
