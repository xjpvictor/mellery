<?php
include('../init.php');

if (!defined('includeauth')) {
  header('Location: '.$base_url);
  exit(0);
}

$site_name = htmlentities($site_name);
if (isset($site_description))
  $site_description = htmlentities($site_description);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0" />
<title>Admin | <?php echo $site_name; ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" href="<?php echo $base_url; ?>content/style.css" type="text/css" media="all" />
<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body id="body-admin">
<div id="main-admin" class="clearfix">
<div id="ss">&nbsp;</div>
<div class="logo">
<h1><a href="<?php echo $base_url; ?>" title="<?php echo $site_name; ?>"><?php echo $site_name; ?></a></h1><p><?php echo $site_description; ?></p>
</div>
<div id="admin-nav"><a href="<?php echo $base_url; ?>admin/">Dashboard</a><a href="<?php echo $base_url; ?>admin/folder.php">Albums</a><a href="<?php echo $base_url; ?>admin/customize.php">Customization</a><a href="<?php echo $base_url; ?>admin/configure.php">Configuration</a><span id="logout" class="right"><a href="<?php echo $base_url; ?>admin/logout.php">Log Out</a></span></div>
<div class="wrap clearfix">
