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

$my_head = 'my_head.php';
$my_foot = 'my_foot.php';
$my_sidebar = 'my_sidebar.php';
$my_style = 'my_style.css';

if (!empty($_POST) && array_key_exists('my_head',$_POST) && $_POST['my_head'] !== file_get_contents($base_dir.$my_head, true)) {
  file_put_contents($base_dir.$my_head, $_POST['my_head'], LOCK_EX);
}
if (!empty($_POST) && array_key_exists('my_foot',$_POST) && $_POST['my_foot'] !== file_get_contents($base_dir.$my_foot, true)) {
  file_put_contents($base_dir.$my_foot, $_POST['my_foot'], LOCK_EX);
}
if (!empty($_POST) && array_key_exists('my_sidebar',$_POST) && $_POST['my_sidebar'] !== file_get_contents($base_dir.$my_sidebar, true)) {
  file_put_contents($base_dir.$my_sidebar, $_POST['my_sidebar'], LOCK_EX);
}
if (!empty($_POST) && array_key_exists('my_style',$_POST) && $_POST['my_style'] !== file_get_contents($base_dir.$my_style, true)) {
  file_put_contents($base_dir.$my_style, $_POST['my_style'], LOCK_EX);
}

include('head.php');
?>
<div id="wrap-admin">
<div class="site-config">
<p>Header</p>
<form method="post" action="customize.php">
<textarea rows="20" class="custom" name="my_head">
<?php echo htmlentities(file_get_contents($base_dir.$my_head, true)); ?>
</textarea><br/><br/>
<input class="button right" type="submit" value="Update">
<div class="clear"></div>
</form> 
</div>
<div class="site-config">
<p>Footer</p>
<form method="post" action="customize.php">
<textarea rows="20" class="custom" name="my_foot">
<?php echo htmlentities(file_get_contents($base_dir.$my_foot, true)); ?>
</textarea><br/><br/>
<input class="button right" type="submit" value="Update">
<div class="clear"></div>
</form> 
</div>
<div class="site-config">
<p>Sidebar</p>
<form method="post" action="customize.php">
<textarea rows="20" class="custom" name="my_sidebar">
<?php echo htmlentities(file_get_contents($base_dir.$my_sidebar, true)); ?>
</textarea><br/><br/>
<input class="button right" type="submit" value="Update">
<div class="clear"></div>
</form> 
</div>
<div class="site-config">
<p>Style</p>
<form method="post" action="customize.php">
<textarea rows="20" class="custom" name="my_style">
<?php echo htmlentities(file_get_contents($base_dir.$my_style, true)); ?>
</textarea><br/><br/>
<input class="button right" type="submit" value="Update">
<div class="clear"></div>
</form> 
</div>

</div>
<?php
include('foot.php');
?>
