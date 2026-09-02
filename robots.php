<?php
declare(strict_types=1);
require __DIR__ . '/backend/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');
$base=portalBaseUrl();
$path=rtrim((string)(parse_url($base,PHP_URL_PATH)??''),'/');
$prefix=$path===''?'':$path;
echo "User-agent: *\nAllow: {$prefix}/\nDisallow: {$prefix}/admin/\nDisallow: {$prefix}/backend/\nDisallow: {$prefix}/api.php\nDisallow: {$prefix}/profile.html\nDisallow: {$prefix}/login.html\nDisallow: {$prefix}/register.html\nDisallow: {$prefix}/manifest.php\nSitemap: {$base}/sitemap.xml\n";
