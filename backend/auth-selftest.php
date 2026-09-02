<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/bootstrap.php';

$checks = [];
$checks['user permissions isolated'] = rolePermissions('user') === ['portal.account','portal.profile','portal.history','portal.request','portal.complaint'];
$checks['superadmin manages citizens'] = in_array('citizens.manage', rolePermissions('superadmin'), true);
$checks['admin cannot manage citizens'] = !in_array('citizens.manage', rolePermissions('admin'), true);
$checks['operator cannot manage citizens'] = !in_array('citizens.manage', rolePermissions('operator'), true);
$checks['user cannot access admin dashboard'] = !in_array('dashboard', rolePermissions('user'), true);

$schema = file_get_contents(__DIR__ . '/database/schema.sql') ?: '';
$api = file_get_contents(__DIR__ . '/api.php') ?: '';
$sw = file_get_contents(dirname(__DIR__) . '/sw.js') ?: '';
$checks['users table exists'] = str_contains($schema, 'CREATE TABLE IF NOT EXISTS users');
$checks['service request user relation'] = str_contains($schema, 'fk_service_user');
$checks['complaint user relation'] = str_contains($schema, 'fk_complaint_user');
$checks['register route exists'] = str_contains($api, "case 'auth.register'");
$checks['unified login route exists'] = str_contains($api, "case 'auth.login'");
$checks['public account route exists'] = str_contains($api, "case 'user.account'");
$checks['citizen admin guard exists'] = substr_count($api, "requirePermission('citizens.manage')") >= 4;
$checks['service worker bypasses api'] = str_contains($sw, 'url.pathname.endsWith("/api.php")');

$failed = false;
foreach ($checks as $name => $ok) {
    printf("%-42s %s\n", $name, $ok ? 'OK' : 'FAILED');
    if (!$ok) $failed = true;
}
exit($failed ? 1 : 0);
