<?php
include_once('./data/config.php');
include_once($base_dir.'functions.php');
if(!array_key_exists('w',$_GET) || !array_key_exists('h',$_GET) || !array_key_exists('id',$_GET) || !array_key_exists('otp',$_GET)) {
  header("HTTP/1.1 403 Forbidden");
  include($base_dir.'includes/403.php');
  exit(0);
}

if ($_GET['otp'] !== substr(hash('sha256', $secret_key.$_GET['id']), 13, 15) && !verifykey($_GET['otp'], $expire_image, null)) {
  header("HTTP/1.1 403 Forbidden");
  include($base_dir.'includes/403.php');
  exit(0);
}

$header_string=boxauth();
$box_cache=boxcache();
$folder_list=getfolderlist();
$info = getexif($_GET['id']);
$folder_id = $info['parent_id'];

if (!array_key_exists('id-'.$folder_id,$folder_list)) {
  header("Status: 404 Not Found");
  include($base_dir.'includes/404.php');
  exit(0);
}

if ($folder_id !== $box_root_folder_id && $folder_list['id-'.$folder_id]['access']['public'][0] !== '1') {
  $auth=auth(array($username,'id-'.$folder_id));
}
if (isset($auth) && ($auth !== 'pass')) {
  header('Content-type: image/png');
  readfile($base_dir.'content/lock.png');
  exit(0);
}

$folder=getfilelist($folder_id,null,null);
if ($folder == 'error' || !array_key_exists('id-'.$_GET['id'],$folder)) {
  header("Status: 404 Not Found");
  include($base_dir.'includes/404.php');
  exit(0);
}

$seq_id = $folder['id-'.$_GET['id']]['sequence_id'];
$file=getthumb($_GET['id'].'-'.$seq_id,$_GET['w'],$_GET['h']);
if ($file && file_exists($file)) {
  header('Content-type: image/jpeg');
  header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)).' GMT');
  header('Expires: '.gmdate('D, d M Y H:i:s', filemtime($file) + max($expire_image, 86400)).' GMT');
  header('Cache-Control: max-age='.max($expire_image, 86400));
  readfile($file);
} else {
  header("Status: 404 Not Found");
  include($base_dir.'includes/404.php');
}
?>
