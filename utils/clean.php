<?php
include('../init.php');

header('X-Robots-Tag: noindex,nofollow,noarchive');

$auth=auth($username);
if ($auth !== 'pass') {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.urlencode($base_url.'admin/');
  $redirect_message = 'Access restricted';
  include($includes_dir.'redirect.php');
  exit(0);
}

$_SESSION['message'] = 'Obsolete files will be deleted';
ob_end_clean();
ob_start();
header("Location: $base_url".'admin/');
$size=ob_get_length();
header("Content-Length: $size");
header("Connection: close");
ob_end_flush();
flush();
if (function_exists('fastcgi_finish_request'))
  fastcgi_finish_request();
if (session_id())
  session_write_close();

$header_string=boxauth();
$box_cache=boxcache();

$clean_dir = $cache_dir.'clean_f';
$dir_file = $cache_dir.'clean_f';

if (!file_exists($clean_dir) || filemtime($clean_dir)) {
  $fid = $box_root_folder_id;
} else {
  $folders = include($clean_dir);
  $fid = $folders[0];
  unset($folders[0]);
/*  file_put_contents('<?php return '.var_export($folders, true).'; ?>', LOCK_EX);*/
}

$items = getfilelist($fid);

foreach ($items['item_collection'] as $id => $item) {
  $type = $item['type'];
}
?>
