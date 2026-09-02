<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/bootstrap.php';

$expected = [
    'superadmin' => ['dashboard','workflow.read','workflow.update','workflow.download','cms.read','cms.write','settings.read','settings.write','media.upload','export.public','users.manage','citizens.manage','audit.read','account.password'],
    'admin' => ['dashboard','workflow.read','workflow.update','workflow.download','cms.read','cms.write','settings.read','settings.write','media.upload','export.public','account.password'],
    'operator' => ['dashboard','workflow.read','workflow.update','workflow.download','account.password'],
    'user' => ['portal.account','portal.profile','portal.history','portal.request','portal.complaint'],
];

$failed = false;
foreach ($expected as $role => $permissions) {
    $actual = rolePermissions($role);
    sort($permissions);
    sort($actual);
    $ok = $permissions === $actual;
    echo sprintf("%-10s %s\n", $role, $ok ? 'OK' : 'FAILED');
    if (!$ok) {
        $failed = true;
        echo ' expected: ' . implode(', ', $permissions) . PHP_EOL;
        echo ' actual:   ' . implode(', ', $actual) . PHP_EOL;
    }
}

if (rolePermissions('invalid-role') !== []) {
    $failed = true;
    echo "invalid-role FAILED\n";
} else {
    echo "invalid-role OK\n";
}

exit($failed ? 1 : 0);
