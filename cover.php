<?php
include_once("functions.php");
if(!array_key_exists('w',$_GET) || !array_key_exists('h',$_GET) || !array_key_exists('id',$_GET)) {
  header("HTTP/1.1 403 Forbidden");
  include($base_dir."library/403.php");
  exit(0);
}
if (!array_key_exists('otp',$_GET) || !verifykey($_GET['otp'], $expire_image, null)) {
  header("HTTP/1.1 403 Forbidden");
  include($base_dir."library/403.php");
  exit(0);
}
$nw = $_GET['w'];
$nh = $_GET['h'];
$id=$_GET['id'];
preg_match('/(\d+)-(\d+)/',$id,$match);
$folder_id=$match[1];
$dest='/tmp/'.$folder_id;
$thumb_na=$base_dir.'library/na.jpg';
$thumb_lock=$base_dir.'library/lock.png';

$header_string=boxauth();
$box_cache=boxcache();
$folder_list=getfolderlist();

if (!array_key_exists('id-'.$folder_id,$folder_list)) {
  header("Status: 404 Not Found");
  include($base_dir."library/404.php");
  exit(0);
}
$folder=getfilelist($folder_id,null,null);
if ($folder == 'error') {
  header("Status: 404 Not Found");
  include($base_dir."library/404.php");
  exit(0);
}

if ($folder_id !== $box_root_folder_id && $folder_list['id-'.$folder_id]['access']['public'][0] !== '1') {
  $auth=auth(array($username,'id-'.$folder_id));
}

$dimg = imagecreatetruecolor($nw, $nh);
imagesavealpha($dimg, true);
$transparent=imagecolorallocatealpha($dimg,255,255,255,127);
imagefill($dimg,0,0,$transparent);
$border_width='1';
$nbw = $nw * 8 / 10;
$nbh = $nh * 8 / 10;
$ntw = $nbw - 2 * $border_width;
$nth = $nbh - 2 * $border_width;
if (!empty($folder)) {
  shuffle($folder);
}
$i = 0;
$thumb = imagecreatetruecolor($ntw, $nth);
imagefill($thumb,0,0,imagecolorallocate($thumb,255,255,255));
if (isset($auth) && (!$auth || $auth == 'fail')) {
  $thumb=createthumbnail($thumb,$thumb_lock,$ntw,$nth);
  $dimg=coverbordercompose($dimg,$nw,$nh,$ntw,$nth,$border_width,$thumb,$i);
  $i++;
} else {
  foreach ($folder as $file) {
    if ($file['type'] == 'file') {
      $cid=$file['id'];
      $csi=$file['sequence_id'];
      $thumb_file=getthumb($cid.'-'.$csi,$nw,$nh);
      if ($thumb_file) {
        $thumb=createthumbnail($thumb,$thumb_file,$ntw,$nth);
      } else {
        $thumb=createthumbnail($thumb,$thumb_na,$ntw,$nth);
      }
      $dimg=coverbordercompose($dimg,$nw,$nh,$ntw,$nth,$border_width,$thumb,$i);
      $i++;
      if ($i >= 5) {
        break;
      }
    }
  }
}
for ($i; $i < 5; $i++) {
  $dimg=coverbordercompose($dimg,$nw,$nh,$ntw,$nth,$border_width,null,$i);
}
imagepng($dimg,$dest,0);
header('Content-Type: image/png');
if (file_exists($dest)) {
  readfile($dest);
  unlink($dest);
}
?>
