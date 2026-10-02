<?php
declare(strict_types=1);

/**
 * Schema definition and idempotent migrations.
 *
 * Keeping DDL out of db.php means the connection layer stays focused on
 * connecting, and the table shape is readable in one place.
 */

function schema_statements(): array
{
    return [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
            status ENUM('active','suspended') NOT NULL DEFAULT 'active',
            phone VARCHAR(50) DEFAULT NULL,
            location VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS inquiries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            service VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('pending','read','responded','closed') NOT NULL DEFAULT 'pending',
            admin_response TEXT DEFAULT NULL,
            responded_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            address VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
    ];
}

/**
 * Columns added after the initial release. Safe to re-run: each ALTER is only
 * issued when the target column is absent.
 */
function schema_migrations(): array
{
    return [
        'inquiries' => [
            'user_id'        => 'ADD COLUMN user_id INT DEFAULT NULL AFTER id',
            'status'         => "ADD COLUMN status ENUM('pending','read','responded','closed') NOT NULL DEFAULT 'pending' AFTER created_at",
            'admin_response' => 'ADD COLUMN admin_response TEXT DEFAULT NULL AFTER status',
            'responded_at'   => 'ADD COLUMN responded_at DATETIME DEFAULT NULL AFTER admin_response',
            'phone'          => 'ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER email',
        ],
        'users' => [
            'status'   => "ADD COLUMN status ENUM('active','suspended') NOT NULL DEFAULT 'active' AFTER role",
            'phone'    => 'ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER status',
            'location' => 'ADD COLUMN location VARCHAR(255) DEFAULT NULL AFTER phone',
        ],
    ];
}

function table_columns(PDO $pdo, string $table): array
{
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    $columns = [];

    foreach ($stmt->fetchAll() as $column) {
        $columns[] = $column['Field'];
    }

    return $columns;
}

function migrate(PDO $pdo): void
{
    foreach (schema_migrations() as $table => $additions) {
        $existing = table_columns($pdo, $table);

        foreach ($additions as $column => $alter) {
            if (!in_array($column, $existing, true)) {
                $pdo->exec("ALTER TABLE `{$table}` {$alter}");
            }
        }
    }
}

function install_schema(PDO $pdo): void
{
    foreach (schema_statements() as $statement) {
        $pdo->exec($statement);
    }

    migrate($pdo);
}
