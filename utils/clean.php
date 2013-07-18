<?php
include_once('../data/config.php');
include_once($base_dir.'functions.php');

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
session_write_close();

$header_string=boxauth();
$box_cache=boxcache();
$folder_list = getfolderlist();

$all_files = getfiles(null);

$dir = $data_dir.'stat/';
$files = scandir($dir);
foreach ($files as $file) {
  if (preg_match('/^\d+$/', $file))
    if (!array_key_exists('id-'.$file, $all_files) && !array_key_exists('id-'.$file, $folder_list))
      unlink($dir.$file);
  } elseif (!preg_match('/^\./', $file)) {
    unlink($dir.$file);
  }
}
?>
