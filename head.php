<?php
include_once("config.php");
if (!defined('includeauth')) {
  header('Location: '.$base_url);
  exit(0);
}

if (!isset($folder_list))
  $folder_list=getfolderlist();
if ($folder_id !== $box_root_folder_id) {
  $folder_name=$folder_list['id-'.$folder_id]['name'];
  echo '<!DOCTYPE html>'."\n".'<html>'."\n".'<head>'."\n".'<meta charset="utf-8" />'."\n".'<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0" />'."\n".'<title>'.$folder_name.' | '.$site_name.'</title>'."\n";
} else {
  echo '<!DOCTYPE html>'."\n".'<html>'."\n".'<head>'."\n".'<meta charset="utf-8" />'."\n".'<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0" />'."\n".'<title>'.$site_name.'</title>'."\n";
}
?>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" href="<?php echo $base_url; ?>library/style.css" type="text/css" media="all" />
<?php if (file_exists($data_dir.'my_style.css')) echo '<link rel="stylesheet" href="'.$base_url.'my_style.css" type="text/css" media="all" />'; ?>
<link rel="shortcut icon" href="/favicon.ico" />
<?php if (!isset($my_page)) $my_page = include($data_dir.'my_page.php'); echo $my_page['header']; ?>
</head>
