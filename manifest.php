<?php
declare(strict_types=1);
require __DIR__ . '/backend/bootstrap.php';
sendHtmlSecurityHeaders(false);
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=600');
$settings=[];
try{$settings=publicSettingsMap();}catch(Throwable $e){}
$name=trim((string)($settings['village_name']??''))?:($config['app_name']??'Portal Gampong');
$short=trim((string)($settings['village_short_name']??''))?:$name;
echo json_encode([
'name'=>$name,'short_name'=>(function_exists('mb_substr')?mb_substr($short,0,24):substr($short,0,24)),'start_url'=>'./','scope'=>'./','display'=>'standalone','background_color'=>'#f7f8f5','theme_color'=>'#087352','lang'=>'id','dir'=>'ltr','description'=>trim((string)($settings['seo_description']??''))?:('Portal informasi dan layanan '.$name.'.'),
'icons'=>[['src'=>'assets/images/icon-192.png','sizes'=>'192x192','type'=>'image/png','purpose'=>'any maskable'],['src'=>'assets/images/icon-512.png','sizes'=>'512x512','type'=>'image/png','purpose'=>'any maskable']]
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
