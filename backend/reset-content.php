<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only.\n");
if (!in_array('--yes', $argv, true)) {
    fwrite(STDERR, "Perintah ini menghapus SEMUA konten publik dan data layanan, tetapi tidak menghapus akun admin maupun akun warga/user.\nJalankan lagi dengan --yes jika yakin.\n");
    exit(1);
}
require __DIR__ . '/bootstrap.php';
$tables = [
    'service_requirements','services','government_officials','institutions','population_statistics','posts','agendas','umkm','galleries',
    'budget_items','development_projects','faqs','quick_links','social_links','external_links','data_sources','service_requests','complaints','settings','rate_limits','audit_logs'
];
$pdo = db();
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($tables as $table) $pdo->exec("TRUNCATE TABLE `{$table}`");
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
echo "Konten berhasil dikosongkan. Database sekarang tidak memiliki data dummy/konten publik.\n";
