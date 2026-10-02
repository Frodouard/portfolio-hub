<?php
/**
 * AUCA STUDENT PORTAL - Global Configuration
 * Edit these values to match your XAMPP / MySQL setup.
 */

// ---------- Database ----------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default MySQL password is empty
define('DB_NAME', 'auca_portal');

// ---------- App ----------
define('APP_NAME', 'AUCA Student Portal');
define('BASE_URL', 'http://localhost/AUCA-STUDENT-PORTAL/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// ---------- Security ----------
// Regenerate this to a random 32+ char string in production
define('CSRF_TOKEN_NAME', 'csrf_token');

// ---------- Error reporting (disable in production) ----------
error_reporting(E_ALL);
if (php_sapi_name() !== 'cli') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
} else {
    ini_set('display_errors', 1);
}
ini_set('log_errors', 1);

// ---------- Timezone ----------
date_default_timezone_set('Africa/Kigali');