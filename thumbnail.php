<?php
include(__DIR__.'/init.php');

if ((array_key_exists('sns',$_GET) && $_GET['sns'] !== '1') || !array_key_exists('id',$_GET) || !array_key_exists('otp',$_GET)) {
  header("HTTP/1.1 403 Forbidden");
  include($includes_dir.'403.php');
  exit(0);
}

if (!verifyotp($_GET['otp'], $expire_image)) {
  header("HTTP/1.1 403 Forbidden");
  include($includes_dir.'403.php');
  exit(0);
}

$header_string=boxauth();
$box_cache=boxcache();
$info = getexif($_GET['id']);
$folder_id = $info['parent_id'];
$file_list=getfilelist($folder_id);
if ($file_list == 'error') {
  header("Status: 404 Not Found");
  include($includes_dir.'404.php');
  exit(0);
}

$access = getaccess($folder_id);
if (!$access) {
  $auth=auth(array($username,'id-'.$folder_id));
}
if (isset($auth) && ($auth !== 'pass')) {
  header('Content-type: image/png');
  header("Cache-Control: no-cache, must-revalidate");
  header("Pragma: no-cache");
  header('Expires: '.gmdate('D, d M Y H:i:s', time()).' GMT');
  readfile($content_dir.'lock.png');
  exit(0);
}

$files = $file_list['item_collection'];
$seq_id = $files['id-'.$_GET['id']]['sequence_id'];

if (isset($_GET['sns']) && $_GET['sns'] == '1') {
  $nw = $w_sns;
  $nh = $h_sns;
} else {
  $nw = $w;
  $nh = $h;
}

$file=getthumb($_GET['id'].'-'.$seq_id,$nw,$nh);
if ($file && file_exists($file)) {
  header('Content-type: image/jpeg');
  if ($file == $content_dir.'na.jpg') {
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    header('Expires: '.gmdate('D, d M Y H:i:s', time()).' GMT');
  } else {
    header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)).' GMT');
    header('Expires: '.gmdate('D, d M Y H:i:s', filemtime($file) + max($expire_image, 3600)).' GMT');
    header('Cache-Control: max-age='.max($expire_image, 3600));
  }
  header('X-Robots-Tag: noindex,nofollow,noarchive');
  readfile($file);
} else {
  header("Status: 404 Not Found");
  include($includes_dir.'404.php');
}
?>
