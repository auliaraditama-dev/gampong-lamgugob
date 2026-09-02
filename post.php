<?php
declare(strict_types=1);
require __DIR__ . '/backend/bootstrap.php';
sendHtmlSecurityHeaders(false);
header('Content-Type: text/html; charset=utf-8');
$slug = trim((string)($_GET['slug'] ?? ''));
if (!preg_match('/^[\pL\pN-]{1,240}$/u', $slug)) { http_response_code(404); $slug=''; }
$settings=[]; $post=null;
try {
    $settings=publicSettingsMap();
    if ($slug!=='') {
        $stmt=db()->prepare("SELECT id,type,title,slug,excerpt,content,category,image_url,source_url,published_at,updated_at FROM posts WHERE slug=? AND is_published=1 AND (published_at IS NULL OR published_at<=NOW()) LIMIT 1");
        $stmt->execute([$slug]); $post=$stmt->fetch() ?: null;
    }
} catch (Throwable $e) { $post=null; }
$village=trim((string)($settings['village_name']??''))?:'Portal Gampong';
if (!$post) { http_response_code(404); $title='Informasi tidak ditemukan'; $description='Informasi yang diminta tidak tersedia.'; }
else { $title=(string)$post['title']; $description=trim((string)($post['excerpt']??'')) ?: (function_exists('mb_substr')?mb_substr(trim(strip_tags((string)($post['content']??''))),0,180):substr(trim(strip_tags((string)($post['content']??''))),0,180)); if($description==='')$description='Informasi publik dari '.$village.'.'; }
$canonical=portalUrl('post.php?slug='.rawurlencode($slug));
$imagePath=$post ? trim((string)($post['image_url']??'')) : '';
$imageValid=$imagePath!=='' && (preg_match('~^https?://~i',$imagePath) || preg_match('~^(?:\./)?assets/[A-Za-z0-9_./-]+$~',$imagePath));
$image=$imageValid?(preg_match('~^https?://~i',$imagePath)?$imagePath:portalUrl($imagePath)):portalUrl('assets/images/icon-512.png');
$schema=$post?['@context'=>'https://schema.org','@type'=>$post['type']==='news'?'NewsArticle':'Article','headline'=>$title,'description'=>$description,'datePublished'=>$post['published_at']?:null,'dateModified'=>$post['updated_at']?:$post['published_at'],'image'=>[$image],'mainEntityOfPage'=>$canonical,'publisher'=>['@type'=>'GovernmentOrganization','name'=>$village,'url'=>portalUrl()]]:null;
function articleParagraphs(string $text): string { $parts=preg_split('/\R{2,}/u',trim($text))?:[]; if(!$parts && trim($text)!=='')$parts=[trim($text)]; return implode('',array_map(fn($p)=>'<p>'.nl2br(htmlEscape(trim($p)),false).'</p>',array_filter($parts,fn($p)=>trim($p)!==''))); }
?><!doctype html>
<html lang="id" data-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#087352"><meta name="color-scheme" content="light dark">
<script>(()=>{try{const s=localStorage.getItem('gampong-theme');const t=s==='dark'||s==='light'?s:(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.dataset.theme=t}catch(_){}})();</script>
<title><?=htmlEscape($title)?> · <?=htmlEscape($village)?></title>
<meta name="description" content="<?=htmlEscape($description)?>"><meta name="robots" content="<?= $post?'index,follow,max-image-preview:large':'noindex,follow' ?>">
<link rel="canonical" href="<?=htmlEscape($canonical)?>"><meta property="og:type" content="article"><meta property="og:locale" content="id_ID"><meta property="og:site_name" content="<?=htmlEscape($village)?>"><meta property="og:title" content="<?=htmlEscape($title)?>"><meta property="og:description" content="<?=htmlEscape($description)?>"><meta property="og:url" content="<?=htmlEscape($canonical)?>"><meta property="og:image" content="<?=htmlEscape($image)?>">
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="<?=htmlEscape($title)?>"><meta name="twitter:description" content="<?=htmlEscape($description)?>"><meta name="twitter:image" content="<?=htmlEscape($image)?>">
<link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32.png"><link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"><link rel="stylesheet" href="assets/css/redesign.css"><link rel="stylesheet" href="assets/css/article.css">
<?php if($schema):?><script type="application/ld+json"><?=json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?></script><?php endif;?>
</head>
<body class="article-page">
<header class="article-header"><div class="container article-nav"><a class="brand" href="./"><span class="brand-mark"><?=htmlEscape(strtoupper(function_exists('mb_substr')?mb_substr($village,0,2):substr($village,0,2)))?></span><span><strong><?=htmlEscape($village)?></strong><small>Portal informasi gampong</small></span></a><div class="article-nav-actions"><a class="btn btn-outline" href="./#berita">Kembali ke Berita</a><button class="icon-button" id="articleTheme" aria-label="Ubah tema">◐</button></div></div></header>
<main class="article-main"><article class="container article-shell">
<?php if(!$post):?><div class="article-empty"><span>404</span><h1>Informasi tidak ditemukan.</h1><p>Konten mungkin belum dipublikasikan atau sudah tidak tersedia.</p><a class="btn btn-primary" href="./">Kembali ke beranda</a></div>
<?php else:?><div class="article-breadcrumb"><a href="./">Beranda</a><span>/</span><a href="./#berita"><?=htmlEscape($post['type']==='announcement'?'Pengumuman':'Berita')?></a></div><header class="article-title"><span class="eyebrow"><?=htmlEscape($post['category']?:($post['type']==='announcement'?'Pengumuman':'Berita'))?></span><h1><?=htmlEscape($post['title'])?></h1><div class="article-meta"><span><?=htmlEscape($post['published_at']?:'')?></span><span><?=htmlEscape($village)?></span></div></header><?php if($imageValid):?><figure class="article-hero"><img src="<?=htmlEscape($image)?>" alt="<?=htmlEscape($post['title'])?>"></figure><?php endif;?><div class="article-body"><?=articleParagraphs((string)($post['content']?:$post['excerpt']?:''))?></div><?php if(!empty($post['source_url'])&&preg_match('~^https?://~i', (string)$post['source_url'])):?><div class="article-source"><a class="btn btn-outline" href="<?=htmlEscape($post['source_url'])?>" target="_blank" rel="noopener noreferrer">Buka sumber eksternal</a></div><?php endif;?><?php endif;?>
</article></main>
<footer class="article-footer"><div class="container">© <?=date('Y')?> <?=htmlEscape($village)?> · <a href="./">Portal Gampong</a></div></footer>
<script>const b=document.getElementById('articleTheme');if(b)b.addEventListener('click',()=>{const n=document.documentElement.dataset.theme==='dark'?'light':'dark';document.documentElement.dataset.theme=n;localStorage.setItem('gampong-theme',n)});</script>
</body></html>
