<?php

declare(strict_types=1);

function loadEnv(string $path): void
{
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if (getenv($key) === false) putenv("{$key}={$value}");
    }
}

loadEnv(__DIR__ . '/.env');

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

return [
    'app_name' => env('APP_NAME', 'Portal Gampong'),
    'app_env' => env('APP_ENV', 'production'),
    'app_key' => env('APP_KEY', 'CHANGE_THIS_TO_A_RANDOM_64_CHAR_SECRET_BEFORE_PRODUCTION'),
    'base_url' => rtrim((string) env('BASE_URL', ''), '/'),
    'session_idle_seconds' => max(900, (int) env('SESSION_IDLE_SECONDS', '7200')),
    'session_rotate_seconds' => max(300, (int) env('SESSION_ROTATE_SECONDS', '1800')),
    'password_min_length' => max(12, (int) env('PASSWORD_MIN_LENGTH', '12')),
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'name' => env('DB_NAME', 'gampong_lamgugob'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],
    'upload_max' => 5 * 1024 * 1024,
    'public_image_max' => 4 * 1024 * 1024,
    'public_image_max_pixels' => 40_000_000,
    'allowed_uploads' => [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ],
    'allowed_public_images' => [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ],
];
