<?php
include('../init.php');

header('X-Robots-Tag: noindex,nofollow,noarchive');

if (!empty($_GET) && array_key_exists('ref',$_GET))
  $redirect_url = urldecode($_GET['ref']);
else
  $redirect_url = $base_url;

$auth=auth($username);
if ($auth == 'pass') {
  session_destroy();
}
header("HTTP/1.1 302 Found");
$redirect_message = 'You have logged out!';
include($includes_dir.'redirect.php');
?>
