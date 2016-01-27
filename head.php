<?php
include(__DIR__.'/init.php');

if (!defined('includeauth')) {
  header('Location: '.$base_url);
  exit(0);
}

$site_name = htmlentities($site_name);
$site_description = htmlentities($site_description);
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0" />
<?php if (!$allow_se) : ?>
<meta name='robots' content='noindex,nofollow' />
<?php endif; ?>
<?php if (!isset($file_list)) $file_list = getfilelist($folder_id); ?>
<title><?php $folder_name = htmlentities($file_list['name']); echo (!defined('isimage') ? ($folder_id !== $box_root_folder_id ? $folder_name.' | '.$site_name : $site_name) : $name.' | '.$site_name); ?> </title>
<meta name="description" content="<?php echo($folder_id !== $box_root_folder_id ? (!empty($file_list['description']) ? htmlentities($file_list['description']) : (!defined('isimage') ? 'Album' : 'Image')) : $site_description); ?>" />

<?php
unset($g);
if (!defined('isimage') && $folder_id !== $box_root_folder_id)
  $g = 0;
if (isset($g)) {
  $ids = array();
  if (!empty($file_list['item_collection'])) {
    foreach ($file_list['item_collection'] as $file) {
      if ($file['type'] == 'file') {
        $ids[] = $file['id'];
        if (++$g == 4)
          break;
      }
    }
  }
}
?>

<meta property="og:site_name" content="<?php echo $site_name; ?>" />
<meta property="og:url" content="<?php echo urldecode($url); ?>" />
<meta property="og:title" content="<?php echo (!defined('isimage') ? ($folder_id !== $box_root_folder_id ? $folder_name : $site_name) : $name); ?>" />
<meta property="og:description" content="<?php echo($folder_id !== $box_root_folder_id ? (!empty($file_list['description']) ? htmlentities($file_list['description']) : (!defined('isimage') ? 'Album' : 'Image')) : $site_description); ?>" />
<?php if ((!isset($g) || $g) && (isset($g) || defined('isimage') || $cover_id)) { ?>
<meta property="og:image" content="<?php echo getcontenturl($folder_id).(!defined('isimage') ? 'cover.php?fid='.$folder_id.($folder_id !== $box_root_folder_id ? '' : '&sns='.((isset($theme) && $theme == 2) ? '2' : '1')) : 'thumbnail.php?id='.$id.'&sns=1').'&otp=#OTP#'; ?>" />
<?php } ?>
<meta property="og:type" content="website" />

<meta name="twitter:url" content="<?php echo urldecode($url); ?>" />
<meta name="twitter:creator" content="<?php echo $username; ?>" />
<meta name="twitter:title" content="<?php echo (!defined('isimage') ? ($folder_id !== $box_root_folder_id ? $folder_name : $site_name) : $name); ?>" />
<meta name="twitter:description" content="<?php echo($folder_id !== $box_root_folder_id ? (!empty($file_list['description']) ? htmlentities($file_list['description']) : (!defined('isimage') ? 'Album' : 'Image')) : $site_description); ?>" />
<?php if ((!isset($g) || $g) && (isset($g) || defined('isimage') || $cover_id)) { ?>
<meta name="twitter:image<?php echo (isset($g) && $g > 1 ? '0' : ''); ?>" content="<?php echo getcontenturl($folder_id).(defined('isimage') ? 'thumbnail.php?id='.$id : (isset($g) ? 'thumbnail.php?id='.$ids[0] : 'cover.php?fid='.$folder_id)).'&sns='.((isset($theme) && $theme == 2) ? '2' : '1').'&otp=#OTP#'; ?>" />
<?php
  if (isset($g) && $g > 1) {
    for ($i = 1; $i < $g; $i++)
      echo '<meta name="twitter:image'.$i.'" content="'.getcontenturl($folder_id).'thumbnail.php?id='.$ids[$i].'&sns=1&otp=#OTP#" />';
  }
}
?>
<meta name="twitter:domain" content="<?php echo $base_url; ?>" />
<meta name="twitter:card" content="<?php echo ((!isset($g) || $g) && (isset($g) || defined('isimage') || $cover_id) ? (isset($g) && $g > 1 ? 'gallery' : 'photo') : 'summary'); ?>" />

<link rel="profile" href="http://gmpg.org/xfn/11" />
<?php if (isset($theme) && $theme == 2) { ?>
<link rel="stylesheet" href="<?php $cu = getcontenturl(null); echo $cu; ?>content/style.2.css<?php if ($cu !== $base_url) echo '?ver=',filemtime($content_dir.'style.2.css'); ?>" type="text/css" media="all" />
<?php } else { ?>
<link rel="stylesheet" href="<?php $cu = getcontenturl(null); echo $cu; ?>content/style.1.css<?php if ($cu !== $base_url) echo '?ver=',filemtime($content_dir.'style.1.css'); ?>" type="text/css" media="all" />
<?php } ?>
<link rel="stylesheet" href="<?php $cu = getcontenturl(null); echo $cu; ?>content/style.css<?php if ($cu !== $base_url) echo '?ver=',filemtime($content_dir.'style.css'); ?>" type="text/css" media="all" />
<?php if (file_exists($data_dir.'my_style.css')) echo '<link rel="stylesheet" href="'.$base_url.'my_style.css" type="text/css" media="all" />'; ?>
<link rel="shortcut icon" href="favicon.ico" />
<?php if (!isset($my_page) && file_exists($data_dir.'my_page.php')) {$my_page = include($data_dir.'my_page.php'); if(isset($my_page['head'])) echo $my_page['header'];} ?>
</head>
