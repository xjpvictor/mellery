<?php
define('includeauth',true);
include('../init.php');

header('X-Robots-Tag: noindex,nofollow,noarchive');

$auth=auth($username);
$url=getpageurl();
if ($auth !== 'pass') {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.$url;
  $redirect_message = 'Login required';
  include($includes_dir.'redirect.php');
  exit(0);
}

if (file_exists($data_dir.'my_page.php'))
  $my_page = include($data_dir.'my_page.php');
include('head.php');
$header_string=boxauth();
$box_cache=boxcache();
$file_list = getfilelist($box_root_folder_id, null, null, 2);
?>

<div class="site-config clearfix">

<p>Hello <?php echo $username; ?>,</p>
<?php
$files = scandir($cache_dir);
$size = 0;
$thumbnail = 0;
$html = 0;
$box = 0;
foreach ($files as $file) {
  if (!preg_match('/^\./', $file)) {
    $size += filesize($cache_dir.$file);
    if (preg_match('/\.html$/', $file))
      $html += 1;
    elseif (preg_match('/\.php$/', $file))
      $box += 1;
    else
      $thumbnail += 1;
  }
}
$size = getsize($size);
?>

<p>You have <b><?php echo $file_list['total_count']; ?></b> albums<span class="edit-admin"><a href="<?php echo $base_url; ?>admin/folder.php">Manage albums</a></span></p>

<?php if ($file_list['total_count']) { ?>
<p>Recent albums:</p>
<div id="recent-list">
<?php
  $otp=getotp($expire_image);
  $i = 0;
  foreach ($file_list['item_collection'] as $folder) {
?>
<div>
<?php if ($folder['type'] == 'folder') { ?>
<a href="<?php echo $base_url; ?>admin/folder.php?fid=<?php echo $folder['id']; ?>">
<?php } ?>
<img class="admin-album" src="<?php echo $base_url; echo ($folder['type'] == 'folder' ? 'cover.php?f' : 'thumbnail.php?'); ?>id=<?php echo $folder['id']; ?>&amp;otp=<?php echo $otp; ?>" alt="<?php echo $folder['name']; ?>" title="<?php echo $folder['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" />
<?php if ($folder['type'] == 'folder') { ?>
</a>
<?php } ?>
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
<br/>
<p>Clean obsolete files<span class="button button-right"><a href="<?php echo $base_url; ?>utils/clean.php">Clean</a></span></p>
<p class="small">* Data files are leftover when images are deleted from Box.com directly. Cleaning may take some time.</p>
</div>

</div>
<?php
include('foot.php');
?>
