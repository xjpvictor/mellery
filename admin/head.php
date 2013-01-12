<?php
include_once('../data/config.php');
if (!defined('includeauth')) {
  header('Location: '.$base_url);
  exit(0);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0" />
<title>Admin | <?php echo $site_name; ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" href="<?php echo $base_url; ?>library/style.css" type="text/css" media="all" />
<?php if (file_exists($data_dir.'my_style.css')) echo '<link rel="stylesheet" href="',$base_url.'my_style.css" type="text/css" media="all" />'; ?>
<link rel="shortcut icon" href="/favicon.ico" />
<?php if (!isset($my_page) && file_exists($data_dir.'my_page.php')) $my_page = include($data_dir.'my_page.php'); echo $my_page['header']; ?>
</head>
<body id="body-admin">
<div id="main" class="clearfix">
<div class="logo">
<h1><a href="<?php echo $base_url; ?>" title="<?php echo $site_name; ?>"><?php echo $site_name; ?></a></h1><p><?php echo $site_description; ?></p>
</div>
<div id="admin-nav"><a href="<?php echo $base_url; ?>admin/">Dashboard</a><a href="<?php echo $base_url; ?>admin/folder.php">Albums</a><a href="<?php echo $base_url; ?>admin/customize.php">Customization</a><a href="<?php echo $base_url; ?>admin/configure.php">Configuration</a><span id="logout" class="right"><a href="<?php echo $base_url; ?>admin/logout.php">Log Out</a></span></div>
