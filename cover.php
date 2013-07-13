<?php
include_once('./data/config.php');
include_once($base_dir.'functions.php');
if(!array_key_exists('w',$_GET) || !array_key_exists('h',$_GET) || !array_key_exists('fid',$_GET) || !array_key_exists('otp',$_GET)) {
  header("HTTP/1.1 403 Forbidden");
  include($base_dir.'includes/403.php');
  exit(0);
}

if ($_GET['otp'] !== substr(hash('sha256', $secret_key.$_GET['fid']), 13, 15) && !verifykey($_GET['otp'], $expire_image, null)) {
  header("HTTP/1.1 403 Forbidden");
  include($base_dir.'includes/403.php');
  exit(0);
}

$nw = $_GET['w'];
$nh = $_GET['h'];
$folder_id=$_GET['fid'];
$dest='/tmp/'.$folder_id;
$thumb_lock=$base_dir.'content/lock.png';
$lck = false;
$na = false;

$header_string=boxauth();
$box_cache=boxcache();
$folder_list=getfolderlist();

if (!array_key_exists('id-'.$folder_id,$folder_list)) {
  header("Status: 404 Not Found");
  include($base_dir.'includes/404.php');
  exit(0);
}

if ($folder_id !== $box_root_folder_id && $folder_list['id-'.$folder_id]['access']['public'][0] !== '1') {
  $auth=auth(array($username,'id-'.$folder_id));
}
if (isset($auth) && ($auth !== 'pass'))
  $lck = true;

if ($lck)
  $dest = $cache_dir.'lck.png';
else {
  $file_id_hash=substr(hash('sha256',$secret_key.$folder_id),2,10);
  $file=$file_id_hash.'-'.$nw.'-'.$nh;
  $dest = $cache_dir.$file;
}

if (!file_exists($dest) || time() - filemtime($dest) >= $expire_image) {
  $folder=getfilelist($folder_id,null,null);
  if ($folder == 'error') {
    $folder = array();
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
  if ($lck) {
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
          $dimg=coverbordercompose($dimg,$nw,$nh,$ntw,$nth,$border_width,$thumb,$i);
          if ($thumb_file == $base_dir.'content/na.jpg')
            $na = true;
        }
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
}

header('Content-Type: image/png');
header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($dest)).' GMT');
header('Expires: '.gmdate('D, d M Y H:i:s', filemtime($dest) + max($expire_image, 86400)).' GMT');
header('Cache-Control: max-age='.max($expire_image, 86400));
readfile($dest);

if ($na)
  unlink($dest);
?>
