<?php
include_once('../data/config.php');
include_once($base_dir.'functions.php');

$auth=auth($username);
if ($auth !== 'pass') {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.urlencode($base_url.'admin/');
  $redirect_message = 'Access restricted';
  include($base_dir.'includes/redirect.php');
  exit(0);
}

$_SESSION['message'] = 'Obsolete files deleted successfully';
header("Location: $base_url".'admin/');

$header_string=boxauth();
$box_cache=boxcache();
$folder_list = getfolderlist();

$all_files = getfiles(null);

$files = scandir($cache_dir);
foreach ($files as $file) {
  if ($file !== '.' && $file !== '..' && preg_match('/^\d+$/', $file) && !array_key_exists('id-'.$file, $all_files) && !array_key_exists('id-'.$file, $folder_list)) {
    unlink($cache_dir.$file);
  }
}
?>
