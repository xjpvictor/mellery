<?php
define('includeauth',true);
include_once('../functions.php');

$auth=auth($username);
session_regenerate_id(true);
$url=getpageurl();
if (!$auth || $auth == 'fail') {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.$url;
  $redirect_message = 'Access restricted';
  include($base_dir."library/redirect.php");
  exit(0);
}

$my_page = include($data_dir.'my_page.php');
include('head.php');
$header_string=boxauth();
$box_cache=boxcache();
$folder_list = getfolderlist();

$otp_session=getkey($expire_session);
?>

<div class="site-config clearfix">
<p>Hello <?php echo $username; ?>,</p>
<?php
if (array_key_exists('id-'.$box_root_folder_id, $folder_list))
  unset($folder_list['id-'.$box_root_folder_id]);
$new = array();
foreach ($folder_list as $id => $folder) {
  if ($folder['new'] == 1)
    $new = array_merge($new, array($id => $folder));
}
$new_count = count($new);
$folder_list = array_merge($new, $folder_list);

$image_count = 0;
foreach ($folder_list as $id => $folder) {
  $image_count += $folder['total_count'];
  if ($folder['parent']['id'] !== $box_root_folder_id)
    $image_count--;
}
$files = scandir($cache_dir);
$size = 0;
$thumbnail = 0;
$html = 0;
$box = 0;
foreach ($files as $file) {
  if ($file !== '.' && $file !== '..' && $file !== 'cache_timestamp') {
    $size += filesize($cache_dir.$file);
    if (preg_match('/\.html/', $file))
      $html += 1;
    elseif (preg_match('/\.php/', $file))
      $box += 1;
    elseif (preg_match('/^[a-z0-9]+$/', $file))
      $thumbnail += 1;
  }
}
if ($size < 1024) {
    $size = $size .' B';
} elseif ($size < 1048576) {
    $size = round($size / 1024, 2) .' KiB';
} elseif ($size < 1073741824) {
    $size = round($size / 1048576, 2) . ' MiB';
} elseif ($size < 1099511627776) {
    $size = round($size / 1073741824, 2) . ' GiB';
} elseif ($size < 1125899906842624) {
    $size = round($size / 1099511627776, 2) .' TiB';
}
?>

<p>You have <b><?php echo count($folder_list); ?></b> albums, and <b><?php echo $image_count; ?></b> images in the albums<span class="edit-admin"><a href="<?php echo $base_url; ?>admin/folder.php">Manage albums</a></span></p>
<?php
if ($new_count > 0) {
  echo '<p><b class="new">'.$new_count.'</b> new albums recently uploaded. New albums are kept private by default.</p>';
}
if (count($folder_list) > 0) {
  echo '<p>Recent albums:</p>'."\n";
  echo '<div id="recent-list">'."\n";
  $otp=getkey($expire_image);
  $i = 0;
  foreach ($folder_list as $folder) {
    echo '<div><a href="'.$base_url.'admin/folder.php?id='.$folder['id'].'"><img class="admin-album" src="'.$base_url.'cover.php?id='.$folder['id'].'-'.$folder['sequence_id'].'&amp;w='.$w.'&amp;h='.$h.'&amp;otp='.$otp.'" alt="'.$folder['name'].'" title="'.$folder['name'].'" width="'.$w.'" height="'.$h.'" /></a></div>';
    $i ++;
    if ($i >= 4)
      break;
  }
  echo '<span class="edit-admin"><a href="'.$base_url.'admin/folder.php">More..</a></span>'."\n";
  echo '</div>'."\n";
}
?>
</div>

<div class="site-config clearfix">
<p>Cache files taking up <b><?php echo $size; ?></b> disk space<span class="button button-right"><a href="<?php echo $base_url; ?>admin/cache.php?option=all&amp;ref=<?php echo $url; ?>">Clean all cache files now</a></span></p>
<p style="padding-left:10px;"><b><?php echo $thumbnail; ?></b> cached thumbnail images<span class="button button-right"><a href="<?php echo $base_url; ?>admin/cache.php?option=thumbnail&amp;ref=<?php echo $url; ?>">Clean</a></span></p>
<p style="padding-left:10px;"><b><?php echo $html; ?></b> cached html pages<span class="button button-right"><a href="<?php echo $base_url; ?>admin/cache.php?option=html&amp;ref=<?php echo $url; ?>">Clean</a></span></p>
<p style="padding-left:10px;"><b><?php echo $box; ?></b> cached box.com file list<span class="button button-right"><a href="<?php echo $base_url; ?>admin/cache.php?option=box&amp;ref=<?php echo $url; ?>">Clean</a></span></p>
<p class="small">* This may take some time.</p><br/>
<p>Clean obsolete files<span class="button button-right"><a href="<?php echo $base_url; ?>admin/clean.php">Clean</a></span></p>
<p class="small">* Data files are leftover when images are deleted from Box.com directly. Cleaning may take some time.</p>
</div>

</div>
<?php
include('foot.php');
?>
