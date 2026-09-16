<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require __DIR__ . '/bootstrap.php';
$pdo = db();
foreach ([__DIR__ . '/database/schema.sql', __DIR__ . '/database/seed.sql'] as $path) {
    $sql = file_get_contents($path) ?: '';
    foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
        $statement = trim($statement);
        if ($statement !== '') $pdo->exec($statement);
    }
}
echo "Master data Gampong Lamgugob berhasil dimasukkan atau diperbarui.\n";
