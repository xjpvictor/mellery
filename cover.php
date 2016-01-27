<?php
include(__DIR__.'/init.php');

if (!array_key_exists('fid',$_GET) || !array_key_exists('otp',$_GET)) {
  header("HTTP/1.1 403 Forbidden");
  include($includes_dir.'403.php');
  exit(0);
}

if (!verifyotp($_GET['otp'], $expire_image)) {
  header("HTTP/1.1 403 Forbidden");
  include($includes_dir.'403.php');
  exit(0);
}

if (isset($_GET['sns'])) {
  $nw = $w_sns;
  $nh = $h_sns;
} else {
  $nw = $w;
  $nh = $h;
}

$folder_id=$_GET['fid'];
$thumb_lock=$content_dir.'lock.png';
$lck = false;
$na = false;

$header_string=boxauth();
$box_cache=boxcache();

if ($folder_id == $box_root_folder_id) {
  if (isset($cover_id) && $cover_id) {
    $info = getexif($cover_id);
    $parent_id = $info['parent_id'];
    $file_list=getfilelist($parent_id);
    if ($file_list == 'error') {
      header("Status: 404 Not Found");
      include($includes_dir.'404.php');
      exit(0);
    }

    if (isset($theme) && $theme == 2) {
      $cw = '1920';
      $ch = '900';
    } else {
      $cw = '1250';
      $ch = '600';
    }
    $seq_id = $file_list['item_collection']['id-'.$cover_id]['sequence_id'];
    $dest=getthumb($cover_id.'-'.$seq_id,$cw,$ch);
  } else {
    $img = imagecreatetruecolor($nw, $nh);
    $bg = imagecolorallocate($img, 255, 255, 255);
    imagefilledrectangle($img, 0, 0, $nw, $nh, $bg);
    imagepng($img, $dest = $cache_dir.$folder_id);
    $na = true;
  }
} else {
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
  if (isset($auth) && ($auth !== 'pass'))
    $lck = true;

  if ($lck)
    $dest = $cache_dir.'lck.png';
  else {
    $file_id_hash=hash($hash_algro,$secret_key.$folder_id);
    $file=$file_id_hash.'-'.$nw.'-'.$nh;
    $dest = $cache_dir.$file;
  }

  if (!file_exists($dest) || time() - filemtime($dest) >= $expire_image) {
    if ($file_list == 'error')
      $files = array();
    else
      $files = $file_list['item_collection'];

    $dimg = imagecreatetruecolor($nw, $nh);
    imagesavealpha($dimg, true);
    $transparent=imagecolorallocatealpha($dimg,255,255,255,127);
    imagefill($dimg,0,0,$transparent);
    $border_width='1';
    $nbw = $nw * 8 / 10;
    $nbh = $nh * 8 / 10;
    $ntw = $nbw - 2 * $border_width;
    $nth = $nbh - 2 * $border_width;
    if (!empty($files)) {
      shuffle($files);
    }
    $i = 0;
    $thumb = imagecreatetruecolor($ntw, $nth);
    imagefill($thumb,0,0,imagecolorallocate($thumb,255,255,255));
    if ($lck) {
      $thumb=createthumbnail($thumb,$thumb_lock,$ntw,$nth);
      $dimg=coverbordercompose($dimg,$nw,$nh,$ntw,$nth,$border_width,$thumb,$i);
      $i++;
    } else {
      foreach ($files as $file) {
        if ($file['type'] == 'file') {
          $cid=$file['id'];
          $csi=$file['sequence_id'];
          $thumb_file=getthumb($cid.'-'.$csi,$nw,$nh);
          if ($thumb_file) {
            $thumb=createthumbnail($thumb,$thumb_file,$ntw,$nth);
            $dimg=coverbordercompose($dimg,$nw,$nh,$ntw,$nth,$border_width,$thumb,$i);
            if ($thumb_file == $content_dir.'na.jpg')
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
}

header('Content-Type: image/png');
if ($na) {
  header("Cache-Control: no-cache, must-revalidate");
  header("Pragma: no-cache");
  header('Expires: '.gmdate('D, d M Y H:i:s', time()).' GMT');
} else {
  header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($dest)).' GMT');
  header('Expires: '.gmdate('D, d M Y H:i:s', filemtime($dest) + max($expire_image, 3600)).' GMT');
  header('Cache-Control: max-age='.max($expire_image, 3600));
}
header('X-Robots-Tag: noindex,nofollow,noarchive');
readfile($dest);

if ($na)
  unlink($dest);
?>
