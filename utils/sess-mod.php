<?php
include_once('../data/config.php');
include_once($base_dir.'functions.php');

if(!array_key_exists('fid',$_GET) || !array_key_exists('option',$_GET) || !array_key_exists('set',$_GET)) {
  header("Status: 404 Not Found");
  include($base_dir.'library/404.php');
  exit(0);
}

$folder_id = $_GET['fid'];
$header_string=boxauth();
$box_cache=boxcache();
$folder_list=getfolderlist();
$url=getpageurl();

$auth=auth(array($username,'id-'.$folder_id));
if ($auth !== 'pass') {
  header("HTTP/1.1 401 Unauthorized");
  exit(0);
}

ob_end_clean();
header('HTTP/1.1 200 Ok');
header("Connection: close");
ob_start();
$size=ob_get_length();
header("Content-Length: $size");
ob_end_flush();
flush();

if ($_GET['option'] == 'fullscreen' || $_GET['option'] == 'slideshow') {
  $option = $_GET['option'];
  switch($_GET['set']) {
  case '1':
    $_SESSION[$option]['id-'.$folder_id] = '3';
    break;
  case '0':
    unset($_SESSION[$option]['id-'.$folder_id]);
    break;
  }
}
?>
