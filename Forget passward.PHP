<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

$errors = [];
$success = '';
$mode = 'request'; // request | reset
$token = $_GET['token'] ?? '';

if ($token !== '') {
    $mode = 'reset';
}

// ---------- Step 1: request a reset link ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE email = ?');
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            // Always show the same message whether or not the email exists (avoid account enumeration)
            $success = 'If that email is registered, a password reset link has been generated.';

            if ($user = mysqli_fetch_assoc($result)) {
                $rawToken = bin2hex(random_bytes(32));
                $hashedToken = hash('sha256', $rawToken);
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $uStmt = mysqli_prepare($conn, 'UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE user_id = ?');
                mysqli_stmt_bind_param($uStmt, 'ssi', $hashedToken, $expires, $user['user_id']);
                mysqli_stmt_execute($uStmt);
                mysqli_stmt_close($uStmt);

                // NOTE: In production, email this link to the user instead of displaying it.
                $resetLink = BASE_URL . 'forgot-password.php?token=' . $rawToken;
                $success .= '<br><br><strong>Demo mode</strong> — since no mail server is configured, here is your reset link:<br>' .
                            '<a href="' . e($resetLink) . '">' . e($resetLink) . '</a>';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// ---------- Step 2: reset the password using the token ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $hashedToken = hash('sha256', $token);

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()');
            mysqli_stmt_bind_param($stmt, 's', $hashedToken);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $uStmt = mysqli_prepare($conn, 'UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE user_id = ?');
                mysqli_stmt_bind_param($uStmt, 'si', $newHash, $user['user_id']);
                mysqli_stmt_execute($uStmt);
                mysqli_stmt_close($uStmt);

                header('Location: index.php?reset=1');
                exit;
            } else {
                $errors[] = 'This reset link is invalid or has expired. Please request a new one.';
                $mode = 'request';
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | AUCA Student Portal</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-logo-circle">AUCA</div>
            <h1><?= $mode === 'reset' ? 'Reset Password' : 'Forgot Password' ?></h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><p><?= $success /* contains safe generated HTML link */ ?></p></div>
        <?php endif; ?>

        <?php if ($mode === 'reset' && !$success): ?>
            <form method="POST" action="forgot-password.php" class="auth-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
            </form>
        <?php else: ?>
            <p class="auth-subtitle">Enter your email and we'll generate a password reset link.</p>
            <form method="POST" action="forgot-password.php" class="auth-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="request">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
            </form>
        <?php endif; ?>

        <p class="auth-footer"><a href="index.php">&larr; Back to Login</a></p>
    </div>
</div>

</body>
</html>