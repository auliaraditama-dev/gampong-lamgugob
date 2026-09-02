<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Installer hanya boleh dijalankan melalui CLI.\n");
}

$config = require __DIR__ . '/config.php';
$options = getopt('', ['admin-name::','admin-email::','admin-password::']);
$name = $options['admin-name'] ?? 'Administrator';
$email = $options['admin-email'] ?? 'admin@example.local';
$password = $options['admin-password'] ?? null;

if (!$password || ($passwordError = (function (string $value) use ($config): ?string { $min=(int)$config['password_min_length']; if (strlen($value)<$min) return "minimal {$min} karakter"; if (strlen($value)>128) return 'maksimal 128 karakter'; return null; })($password)) !== null) {
    fwrite(STDERR, "Gunakan --admin-password yang aman ({$passwordError}).\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Email admin tidak valid.\n");
    exit(1);
}
if (($config['app_key'] ?? '') === 'CHANGE_THIS_TO_A_RANDOM_64_CHAR_SECRET_BEFORE_PRODUCTION' || strlen((string)($config['app_key'] ?? '')) < 32) {
    fwrite(STDERR, "APP_KEY belum aman. Buat random key minimal 32 karakter di backend/.env.\n");
    exit(1);
}

$db = $config['db'];
$serverDsn = "mysql:host={$db['host']};port={$db['port']};charset={$db['charset']}";
$pdo = new PDO($serverDsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $db['name']);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$dbName}`");
$sql = file_get_contents(__DIR__ . '/database/schema.sql') ?: '';
foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
    $statement = trim($statement);
    if ($statement !== '') $pdo->exec($statement);
}

$hasColumn = function (string $table, string $column) use ($pdo, $dbName): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
    $stmt->execute([$dbName, $table, $column]);
    return (int) $stmt->fetchColumn() > 0;
};
$hasIndex = function (string $table, string $index) use ($pdo, $dbName): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=?');
    $stmt->execute([$dbName, $table, $index]);
    return (int) $stmt->fetchColumn() > 0;
};
$hasConstraint = function (string $table, string $constraint) use ($pdo, $dbName): bool {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=? AND TABLE_NAME=? AND CONSTRAINT_NAME=?');
    $stmt->execute([$dbName, $table, $constraint]);
    return (int) $stmt->fetchColumn() > 0;
};

foreach (['service_requests','complaints'] as $table) {
    if (!$hasColumn($table, 'user_id')) $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id");
}
if (!$hasIndex('service_requests', 'idx_service_user')) $pdo->exec('ALTER TABLE service_requests ADD INDEX idx_service_user (user_id)');
if (!$hasIndex('complaints', 'idx_complaint_user')) $pdo->exec('ALTER TABLE complaints ADD INDEX idx_complaint_user (user_id)');
if (!$hasConstraint('service_requests', 'fk_service_user')) $pdo->exec('ALTER TABLE service_requests ADD CONSTRAINT fk_service_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
if (!$hasConstraint('complaints', 'fk_complaint_user')) $pdo->exec('ALTER TABLE complaints ADD CONSTRAINT fk_complaint_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');

$collision = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
$collision->execute([strtolower($email)]);
if ($collision->fetchColumn()) {
    fwrite(STDERR, "Email installer sudah digunakan akun warga. Gunakan email superadmin lain.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO admins (name,email,password_hash,role) VALUES (?,?,?,'superadmin') ON DUPLICATE KEY UPDATE name=VALUES(name),password_hash=VALUES(password_hash),role='superadmin'");
$stmt->execute([$name, strtolower($email), $hash]);

echo "Instalasi selesai.\n";
echo "Database: {$dbName}\n";
echo "Admin: " . strtolower($email) . "\n";
echo "Tidak ada konten dummy yang dimasukkan. Role warga/user dan migrasi user_id juga siap. Login ke /admin/ lalu isi data gampong dari dashboard.\n";
