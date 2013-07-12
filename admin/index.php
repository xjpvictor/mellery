<?php
define('includeauth',true);
include_once('../data/config.php');
include_once($base_dir.'functions.php');

$auth=auth($username);
$url=getpageurl();
if ($auth !== 'pass') {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.$url;
  $redirect_message = 'Access restricted';
  include($base_dir.'library/redirect.php');
  exit(0);
}

if (file_exists($data_dir.'my_page.php'))
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
    elseif (preg_match('/^[a-z0-9\-]+$/', $file))
      $thumbnail += 1;
  }
}
$size = getsize($size);
?>

<p>You have <b><?php echo count($folder_list); ?></b> albums, and <b><?php echo $image_count; ?></b> images in the albums<span class="edit-admin"><a href="<?php echo $base_url; ?>admin/folder.php">Manage albums</a></span></p>

<?php if ($new_count > 0) { ?>
<p><b class="new"><?php echo $new_count; ?></b> new albums recently uploaded. New albums are kept private by default.</p>
<?php } ?>

<?php if (count($folder_list) > 0) { ?>
<p>Recent albums:</p>
<div id="recent-list">
<?php
  $otp=getkey($expire_image);
  $i = 0;
  foreach ($folder_list as $folder) {
?>
<div>
<a href="<?php echo $base_url; ?>admin/folder.php?fid=<?php echo $folder['id']; ?>">
<img class="admin-album" src="<?php echo $base_url; ?>cover.php?fid=<?php echo $folder['id']; ?>&amp;w=<?php echo $w; ?>&amp;h=<?php echo $h; ?>&amp;otp=<?php echo $otp; ?>" alt="<?php echo $folder['name']; ?>" title="<?php echo $folder['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" />
</a>
</div>
<?php
    $i ++;
    if ($i >= 4)
      break;
  }
?>
<span class="edit-admin"><a href="<?php echo $base_url; ?>admin/folder.php">More..</a></span>
</div>
<?php } ?>

</div>

<div class="site-config clearfix">
<p>Cache files taking up <b><?php echo $size; ?></b> disk space<span class="button button-right"><a href="<?php echo $base_url; ?>utils/cache.php?option=all&amp;ref=<?php echo $url; ?>">Clean all cache files now</a></span></p>
<p style="padding-left:10px;"><b><?php echo $thumbnail; ?></b> cached thumbnail images<span class="button button-right"><a href="<?php echo $base_url; ?>utils/cache.php?option=thumbnail&amp;ref=<?php echo $url; ?>">Clean</a></span></p>
<p style="padding-left:10px;"><b><?php echo $html; ?></b> cached html pages<span class="button button-right"><a href="<?php echo $base_url; ?>utils/cache.php?option=html&amp;ref=<?php echo $url; ?>">Clean</a></span></p>
<p style="padding-left:10px;"><b><?php echo $box; ?></b> cached box.com file list<span class="button button-right"><a href="<?php echo $base_url; ?>utils/cache.php?option=box&amp;ref=<?php echo $url; ?>">Clean</a></span></p>
<p class="small">* This may take some time.</p><br/>
<p>Clean obsolete files<span class="button button-right"><a href="<?php echo $base_url; ?>admin/clean.php">Clean</a></span></p>
<p class="small">* Data files are leftover when images are deleted from Box.com directly. Cleaning may take some time.</p>
</div>

</div>
<?php
include('foot.php');
?>
