<?php
require_once __DIR__ . '/auth.php';
redirect_if_logged_in();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $user = find_user_by_email($email);

        if ($user && password_verify($password, $user['password'] ?? '')) {
            $_SESSION['user'] = $user;

            if (($user['role'] ?? 'customer') === 'admin') {
                header('Location: admin-dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        }

        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In | Portfolio Hub</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/auth.css" />
</head>
<body>
  <div class="auth-shell">
    <div class="auth-card">
      <div class="auth-header">
        <img src="assets/images/logo.png" alt="Portfolio Hub" class="logo" />
        <div>
          <p class="eyebrow">Welcome back</p>
          <h1>Sign in to your account</h1>
        </div>
      </div>

      <?php if ($error !== ''): ?>
        <div class="message error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <form method="POST" class="auth-form">
        <label>
          Email address
          <input type="email" name="email" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required />
        </label>

        <label>
          Password
          <input type="password" name="password" placeholder="Enter your password" required />
        </label>

        <button type="submit" class="primary-btn">Sign in</button>
      </form>

      <p class="switch-link">
        Don't have an account?
        <a href="register.php">Create one</a>
      </p>
      <p class="switch-link secondary">
        <a href="index.php">&larr; Back to home</a> &nbsp;|&nbsp; <a href="team.php">Our Team</a>
      </p>
    </div>
  </div>
</body>
</html>
