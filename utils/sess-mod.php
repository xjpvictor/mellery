<?php
include('../init.php');

header('X-Robots-Tag: noindex,nofollow,noarchive');

if(!array_key_exists('fid',$_GET) || !array_key_exists('option',$_GET) || !array_key_exists('set',$_GET)) {
  header("Status: 404 Not Found");
  include($includes_dir.'404.php');
  exit(0);
}

$folder_id = $_GET['fid'];
$header_string=boxauth();
$box_cache=boxcache();
$url=getpageurl();

$auth=auth(array($username,'id-'.$folder_id));
if (getaccess($folder_id) < '8') {
  if ($auth !== 'pass') {
    header("HTTP/1.1 401 Unauthorized");
    exit(0);
  }
}

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
