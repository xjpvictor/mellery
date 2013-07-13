<?php
include_once('../data/config.php');
include_once($base_dir.'functions.php');

if (empty($_GET)) {
  ob_end_clean();
  header('HTTP/1.1 200 Ok');
  header("Connection: close");
  ob_start();
  $size=ob_get_length();
  header("Content-Length: $size");
  ob_end_flush();
  flush();
} else {
  if (array_key_exists('ref', $_GET))
    $url = urldecode($_GET['ref']);
  else
    $url = $base_url.'admin/';
  $auth=auth($username);
  if ($auth !== 'pass') {
    header("HTTP/1.1 401 Unauthorized");
    $redirect_url = $base_url.'admin/login.php?ref='.urlencode($url);
    $redirect_message = 'Access restricted';
    include($base_dir.'includes/redirect.php');
    exit(0);
  }
  $_SESSION['message'] = 'Cache cleaned successfully';
  header("Location: $url");
  $cache_expire = '0';
  $cache_clean = 'always';
}

$timestamp_file = $cache_dir.'cache_timestamp';
if (file_exists($timestamp_file))
  $timestamp = file_get_contents($timestamp_file, true);
else
  $timestamp = 0;
if ($cache_clean !== 'always' && ($cache_clean == '0' || time() - $timestamp < $cache_clean * 86400))
  exit(0);

$files = scandir($cache_dir);
foreach ($files as $file) {
  if ($file !== '.' && $file !== '..' && time() - filemtime($cache_dir.$file) >= $cache_expire * 86400) {
    if (!empty($_GET) && array_key_exists('option', $_GET) && $_GET['option'] == 'all' && preg_match('/^[a-z0-9][a-z0-9\-\.]+$/',$file))
      unlink($cache_dir.$file);
    elseif (!empty($_GET) && array_key_exists('option', $_GET) && $_GET['option'] == 'thumbnail' && preg_match('/^[a-z0-9\-]+$/',$file))
      unlink($cache_dir.$file);
    elseif (!empty($_GET) && array_key_exists('option', $_GET) && $_GET['option'] == 'html' && preg_match('/\.html/',$file))
      unlink($cache_dir.$file);
    elseif (!empty($_GET) && array_key_exists('option', $_GET) && $_GET['option'] == 'box' && preg_match('/\.php/',$file))
      unlink($cache_dir.$file);
    elseif (empty($_GET))
      unlink($cache_dir.$file);
  }
}
file_put_contents($timestamp_file, time());
?>
