<?php
include_once(__DIR__ . '/functions.php');

$base_dir = __DIR__ . '/';
$w = '150';
$h = '150';
$w_sns = '600';
$h_sns = '300';
$cache_dir = $base_dir.'cache/';
$includes_dir = $base_dir.'includes/';
$content_dir = $base_dir.'content/';
$data_dir = $base_dir.'data/'.hash('md5', getpageurl(1)).'/';
$base_url = getpageurl(1,1);
$stat_dir = $data_dir.'stat/';
$comment_dir = $data_dir.'comment/';
$access_file = $data_dir.'access.php';
$box_token_file = $data_dir.'box_token.php';
$box_cache_file = $cache_dir.'.box_cache_timestamp.'.hash('md5', $base_url);
$cc_url = 'http://creativecommons.org/licenses/';
$cc_ver = '4.0';
$hash_algro = 'sha256';
$hash_cost = 12;
$otp_length = 15;
$otp_init = 3;
$box_url = 'https://api.box.com/2.0';
$box_url_auth = 'https://api.box.com';

if (!file_exists($php_file = $data_dir.'config.php'))
  exit('Please setup first by accessing /admin/setup.php');
else {
  if (function_exists('opcache_invalidate'))
    opcache_invalidate($php_file,true);
  include($php_file);
}
?>
