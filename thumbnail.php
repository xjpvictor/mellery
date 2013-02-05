<?php
include_once('./data/config.php');
include_once('./functions.php');
if(!array_key_exists('w',$_GET) || !array_key_exists('h',$_GET) || !array_key_exists('id',$_GET) || !array_key_exists('fid',$_GET) || !array_key_exists('otp',$_GET)) {
  header("HTTP/1.1 403 Forbidden");
  include($base_dir.'library/403.php');
  exit(0);
}

if ($_GET['otp'] == substr(hash('sha256', $secret_key.$_GET['id'].'-'.$_GET['fid']), 13, 15)) {
  if (!empty($_SERVER['HTTP_REFERER']) && false === stripos(file_get_contents($referers), parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST))) {
    header("HTTP/1.1 403 Forbidden");
    include($base_dir.'library/403.php');
    exit(0);
  }
} elseif (!verifykey($_GET['otp'], $expire_image, null)) {
  header("HTTP/1.1 403 Forbidden");
  include($base_dir.'library/403.php');
  exit(0);
}

$header_string=boxauth();
$box_cache=boxcache();
$folder_list=getfolderlist();

$folder_id=$_GET['fid'];
if (!array_key_exists('id-'.$folder_id,$folder_list)) {
  header("Status: 404 Not Found");
  include($base_dir.'library/404.php');
  exit(0);
}

if ($folder_id !== $box_root_folder_id && $folder_list['id-'.$folder_id]['access']['public'][0] !== '1') {
  $auth=auth(array($username,'id-'.$folder_id));
}
if (isset($auth) && ($auth !== 'pass')) {
  header('Content-type: image/png');
  readfile($base_dir.'library/lock.png');
  exit(0);
}

$folder=getfilelist($folder_id,null,null);
preg_match('/(\d+)-(\d+)/',$_GET['id'],$match);
if ($folder == 'error' || !array_key_exists('id-'.$match[1],$folder)) {
  header("Status: 404 Not Found");
  include($base_dir.'library/404.php');
  exit(0);
}

$file=getthumb($_GET['id'],$_GET['w'],$_GET['h']);
if ($file) {
  header('Content-type: image/jpeg');
  readfile($file);
} else {
  header("Status: 404 Not Found");
  include($base_dir.'library/404.php');
}
?>
