<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/schema.php';

function db_settings(): array
{
    return [
        'server'   => env('DB_HOST', 'localhost') ?? 'localhost',
        'port'     => env('DB_PORT', '3306') ?? '3306',
        'username' => env('DB_USER', 'root') ?? 'root',
        'password' => env('DB_PASS', '') ?? '',
        'database' => env('DB_NAME', 'portfolio_hub') ?? 'portfolio_hub',
    ];
}

/**
 * Connects to MySQL, creating the database and tables on first run.
 */
function get_db(): PDO
{
    ['server' => $server, 'port' => $port, 'username' => $username, 'password' => $password, 'database' => $database] = db_settings();

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $bootstrap = new PDO("mysql:host={$server};port={$port};charset=utf8mb4", $username, $password, $options);
    $bootstrap->exec("CREATE DATABASE IF NOT EXISTS `{$database}`");

    $pdo = new PDO("mysql:host={$server};port={$port};dbname={$database};charset=utf8mb4", $username, $password, $options);

    install_schema($pdo);

    return $pdo;
}
