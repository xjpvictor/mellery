<?php
include('../init.php');

header('X-Robots-Tag: noindex,nofollow,noarchive');

if (array_key_exists('dnt', $_GET) && $_GET['dnt'] == '1') {
  $login = auth($username);
  $_SESSION['message'] = 'Your pageview will not be tracked';
  setcookie('_mellery_dnt', '1', time() + 365 * 24 * 60 * 60, '/', '');
  if (array_key_exists('ref', $_GET) && !empty($_GET['ref']))
    $redirect_url = urldecode($_GET['ref']);
  else
    $redirect_url = $base_url;
  header("Location: $redirect_url");
  exit(0);
}

if (!isset($_GET['id'])) {
  header("HTTP/1.0 403 Forbidden");
  exit('403');
}

if (!is_dir($stat_dir))
  mkdir($stat_dir);

if (file_exists($stat_dir.$_GET['id']))
  $ori = (int)file_get_contents($stat_dir.$_GET['id']);
else
  $ori = 0;

if (array_key_exists('update', $_GET) && verifyotp($_GET['update'], $expire_image) && preg_match('/^\d+$/', $_GET['id']) && auth($username) !== 'pass' && (empty($_COOKIE) || !array_key_exists('_mellery_dnt', $_COOKIE) || $_COOKIE['_mellery_dnt'] !== '1') && (!array_key_exists('HTTP_DNT', $_SERVER) || !isset($_SERVER['HTTP_DNT']) || $_SERVER['HTTP_DNT'] !== 1)) {
  $ori ++;
  file_put_contents($stat_dir.$_GET['id'], $ori, LOCK_EX);
}

echo 'document.write("'.$ori.' Views")';

?>
