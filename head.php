<?php
include_once('./data/config.php');
if (!defined('includeauth')) {
  header('Location: '.$base_url);
  exit(0);
}

if (!isset($folder_list))
  $folder_list=getfolderlist();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0" />
<title><?php if ($folder_id !== $box_root_folder_id) { $folder_name=$folder_list['id-'.$folder_id]['name']; echo $folder_name,' | '; } echo $site_name; ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" href="<?php echo $base_url; ?>library/style.css" type="text/css" media="all" />
<?php if (file_exists($data_dir.'my_style.css')) echo '<link rel="stylesheet" href="'.$base_url.'my_style.css" type="text/css" media="all" />'; ?>
<link rel="shortcut icon" href="/favicon.ico" />
<?php if (!isset($my_page) && file_exists($data_dir.'my_page.php')) $my_page = include($data_dir.'my_page.php'); echo $my_page['header']; ?>
<style type="text/css" media="all">
  <!--
#FULLSCREENSTYLE#
  -->
</style>
</head>
