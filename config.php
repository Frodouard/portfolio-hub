<?php
declare(strict_types=1);

/**
 * Minimal .env loader. Parses KEY=VALUE lines from a .env file in the project
 * root and registers them via putenv()/getenv() so configuration stays out of
 * version control. Existing environment variables always win.
 */

function load_env(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_starts_with($line, 'export ')) {
            $line = substr($line, 7);
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (strlen($value) >= 2) {
            $first = $value[0];
            if (($first === '"' || $first === "'") && str_ends_with($value, $first)) {
                $value = substr($value, 1, -1);
            }
        }

        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

load_env(__DIR__ . '/.env');

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);

    return ($value === false || $value === '') ? $default : $value;
}
