<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/bootstrap.php';

$pdo = db();
$tables = [
    'government_officials', 'institutions', 'services', 'population_statistics', 'posts', 'agendas', 'umkm', 'galleries',
    'budget_items', 'development_projects', 'faqs', 'quick_links', 'social_links', 'external_links', 'data_sources',
    'village_areas', 'village_boundaries', 'public_facilities', 'village_milestones', 'mosque_management', 'mosque_programs', 'mosque_facilities'
];

$failed = false;
$settings = publicSettingsMap();
printf("%-32s %s\n", 'settings publik', count($settings) . ' key');

foreach ($tables as $table) {
    try {
        $total = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        $published = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}` WHERE is_published=1")->fetchColumn();
        printf("%-32s total=%-4d publik=%-4d %s\n", $table, $total, $published, ($total > 0 && $published === 0) ? '<- cek is_published' : '');
    } catch (PDOException $e) {
        printf("%-32s TABEL/KOLOM TIDAK SIAP\n", $table);
        $failed = true;
    }
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE is_published=1 AND (published_at IS NULL OR published_at<=NOW())");
    printf("%-32s %d\n", 'posts tampil saat ini', (int) $stmt->fetchColumn());
    $stmt = $pdo->query("SELECT COUNT(*) FROM agendas WHERE is_published=1 AND start_at>=DATE_SUB(NOW(),INTERVAL 1 DAY)");
    printf("%-32s %d\n", 'agenda tampil saat ini', (int) $stmt->fetchColumn());
} catch (PDOException $e) {
    $failed = true;
}

exit($failed ? 1 : 0);
