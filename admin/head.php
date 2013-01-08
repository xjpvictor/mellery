<?php
include_once("../config.php");
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
<link rel="stylesheet" href="<?php echo $base_url; ?>my_style.css" type="text/css" media="all" />
<link rel="shortcut icon" href="/favicon.ico" />
<?php include($base_dir.'my_head.php'); ?>
</head>
<body id="admin-body">
<div id="wrap" class="clearfix">
<div id="main">
<div class="logo">
<h1><a href="<?php echo $base_url; ?>" title="<?php echo $site_name; ?>"><?php echo $site_name; ?></a></h1><p><?php echo $site_description; ?></p>
</div>
<div id="content-admin">
<?php
echo '<div id="admin-nav"><span><a href="'.$base_url.'admin/">Dashboard</a></span><span><a href="'.$base_url.'admin/folder.php">Albums</a></span><span><a href="'.$base_url.'admin/customize.php">Customization</a></span><span><a href="'.$base_url.'admin/configure.php">Configuration</a></span><span id="logout"><a href="'.$base_url.'admin/logout.php">Log Out</a></span></div>';
?>
