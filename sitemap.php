<?php
declare(strict_types=1);
require __DIR__ . '/backend/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=1800');
$urls=[['loc'=>portalUrl(),'lastmod'=>date('c')]];
try {
    $rows=db()->query("SELECT slug,COALESCE(updated_at,published_at,created_at) AS changed_at FROM posts WHERE is_published=1 AND (published_at IS NULL OR published_at<=NOW()) ORDER BY COALESCE(published_at,created_at) DESC LIMIT 1000")->fetchAll();
    foreach($rows as $r)$urls[]=['loc'=>portalUrl('post.php?slug='.rawurlencode((string)$r['slug'])),'lastmod'=>date('c',strtotime((string)$r['changed_at'])?:time())];
} catch(Throwable $e) {}
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach($urls as $u)echo '  <url><loc>'.htmlEscape($u['loc']).'</loc><lastmod>'.htmlEscape($u['lastmod'])."</lastmod></url>\n";
echo "</urlset>\n";
