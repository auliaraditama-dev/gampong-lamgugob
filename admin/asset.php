<?php
declare(strict_types=1);
require __DIR__ . '/../backend/bootstrap.php';
requireAdmin();
$allowed = [
    'admin.css' => ['path' => __DIR__ . '/assets/admin.css', 'type' => 'text/css; charset=utf-8'],
    'redesign.css' => ['path' => __DIR__ . '/assets/redesign.css', 'type' => 'text/css; charset=utf-8'],
    'admin.js' => ['path' => __DIR__ . '/assets/admin.js', 'type' => 'application/javascript; charset=utf-8'],
];
$file = (string) ($_GET['file'] ?? '');
if (!isset($allowed[$file])) {
    http_response_code(404);
    exit;
}
header('Content-Type: ' . $allowed[$file]['type']);
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
readfile($allowed[$file]['path']);
