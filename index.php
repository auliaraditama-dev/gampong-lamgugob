<?php
declare(strict_types=1);
require __DIR__ . '/backend/bootstrap.php';
sendHtmlSecurityHeaders(false);
header('Content-Type: text/html; charset=utf-8');
$settings = [];
try { $settings = publicSettingsMap(); } catch (Throwable $e) { $settings = []; }
$village = trim((string)($settings['village_name'] ?? '')) ?: 'Portal Gampong';
$title = trim((string)($settings['seo_title'] ?? '')) ?: $village;
$description = trim((string)($settings['seo_description'] ?? '')) ?: ('Portal informasi dan layanan ' . $village . '.');
$keywords = trim((string)($settings['seo_keywords'] ?? ''));
$canonical = portalUrl();
$imagePath = trim((string)($settings['seo_image_url'] ?? $settings['logo_url'] ?? $settings['hero_background_url'] ?? ''));
$image = $imagePath !== '' ? (preg_match('~^https?://~i', $imagePath) ? $imagePath : portalUrl($imagePath)) : portalUrl('assets/images/icon-512.png');
$location = implode(', ', array_values(array_filter([$settings['district'] ?? '', $settings['city'] ?? '', $settings['province'] ?? ''])));
$logoPath = trim((string)($settings['logo_url'] ?? ''));
$logo = $logoPath !== '' ? (preg_match('~^https?://~i', $logoPath) ? $logoPath : portalUrl($logoPath)) : portalUrl('assets/images/icon-512.png');
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'GovernmentOrganization',
    'name' => $village,
    'url' => $canonical,
    'description' => $description,
    'logo' => $logo,
    'image' => $image,
];
if ($location !== '') $schema['address'] = ['@type'=>'PostalAddress','addressLocality'=>$location,'streetAddress'=>(string)($settings['office_address'] ?? '')];
if (!empty($settings['office_phone'])) $schema['telephone'] = (string)$settings['office_phone'];
if (!empty($settings['office_email'])) $schema['email'] = (string)$settings['office_email'];
$seoHead = '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) . '</script>';
$html = file_get_contents(__DIR__ . '/index.html') ?: '';
$replacements = [
    '<title>Portal Gampong</title>' => '<title>' . htmlEscape($title) . '</title>',
    '<meta id="metaDescription" name="description" content="Portal informasi dan layanan gampong.">' => '<meta id="metaDescription" name="description" content="' . htmlEscape($description) . '">',
    '<meta id="metaKeywords" name="keywords" content="">' => '<meta id="metaKeywords" name="keywords" content="' . htmlEscape($keywords) . '">',
    '<meta id="ogTitle" property="og:title" content="Portal Gampong">' => '<meta id="ogTitle" property="og:title" content="' . htmlEscape($title) . '">',
    '<meta id="ogUrl" property="og:url" content="">' => '<meta id="ogUrl" property="og:url" content="' . htmlEscape($canonical) . '">',
    '<meta id="ogSiteName" property="og:site_name" content="Portal Gampong">' => '<meta id="ogSiteName" property="og:site_name" content="' . htmlEscape($village) . '">',
    '<meta id="ogDescription" property="og:description" content="Portal informasi dan layanan gampong.">' => '<meta id="ogDescription" property="og:description" content="' . htmlEscape($description) . '">',
    '<meta id="ogImage" property="og:image" content="">' => '<meta id="ogImage" property="og:image" content="' . htmlEscape($image) . '">',
    '<meta id="twitterTitle" name="twitter:title" content="Portal Gampong">' => '<meta id="twitterTitle" name="twitter:title" content="' . htmlEscape($title) . '">',
    '<meta id="twitterDescription" name="twitter:description" content="Portal informasi dan layanan gampong.">' => '<meta id="twitterDescription" name="twitter:description" content="' . htmlEscape($description) . '">',
    '<meta id="twitterImage" name="twitter:image" content="">' => '<meta id="twitterImage" name="twitter:image" content="' . htmlEscape($image) . '">',
    '<link id="canonicalLink" rel="canonical" href="">' => '<link id="canonicalLink" rel="canonical" href="' . htmlEscape($canonical) . '">',
    '<!--SEO_HEAD-->' => $seoHead,
];
$html = str_replace(array_keys($replacements), array_values($replacements), $html);
echo $html;
