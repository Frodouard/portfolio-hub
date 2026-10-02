<?php
require_once __DIR__ . '/auth.php';
redirect_if_logged_in();

$error = '';
$role = 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'customer';

    if (!in_array($role, ['customer', 'admin'])) {
        $role = 'customer';
    }

    if ($fullName === '' || $email === '' || $phone === '' || $location === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please complete all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $existingUser = find_user_by_email($email);

        if ($existingUser) {
            $error = 'This email is already registered.';
        } else {
            create_user($fullName, $email, $password, $role, $phone, $location);
            $newUser = find_user_by_email($email);

            $_SESSION['user'] = $newUser;

            if ($role === 'admin') {
                header('Location: admin-dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register | Portfolio Hub</title>
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
          <p class="eyebrow">Register</p>
          <h1>Create your account</h1>
        </div>
      </div>

      <?php if ($error !== ''): ?>
        <div class="message error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <form method="POST" class="auth-form">
        <label>
          Full name
          <input type="text" name="full_name" placeholder="Your full name" value="<?= htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required />
        </label>

        <label>
          Email address
          <input type="email" name="email" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required />
        </label>

        <label>
          Phone number
          <input type="tel" name="phone" placeholder="+250 7XX XXX XXX" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required />
        </label>

        <label>
          Current location
          <input type="text" name="location" placeholder="City, Country" value="<?= htmlspecialchars($_POST['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required />
        </label>

        <label>
          Password
          <input type="password" name="password" placeholder="Minimum 6 characters" required />
        </label>

        <label>
          Confirm password
          <input type="password" name="confirm_password" placeholder="Re-enter password" required />
        </label>

        <label>
          Account type
          <div class="role-selector">
            <label class="role-option">
              <input type="radio" name="role" value="customer" checked />
              <div class="role-card">
                <span class="role-card-icon">&#128100;</span>
                <strong>Customer</strong>
                <small>Access client portal &amp; services</small>
              </div>
            </label>
            <label class="role-option">
              <input type="radio" name="role" value="admin" <?= ($role ?? '') === 'admin' ? 'checked' : '' ?> />
              <div class="role-card">
                <span class="role-card-icon">&#9881;</span>
                <strong>Admin</strong>
                <small>Manage users &amp; system</small>
              </div>
            </label>
          </div>
        </label>

        <button type="submit" class="primary-btn">Create account</button>
      </form>

      <p class="switch-link">
        Already have an account?
        <a href="login.php">Sign in</a>
      </p>
      <p class="switch-link secondary">
        <a href="index.php">&#8592; Back to home</a> &nbsp;|&nbsp; <a href="team.php">Our Team</a>
      </p>
    </div>
  </div>
</body>
</html>
