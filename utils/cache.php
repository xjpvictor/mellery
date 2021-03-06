<?php
include('../init.php');

header('X-Robots-Tag: noindex,nofollow,noarchive');

if (empty($_GET)) {
  $img = $content_dir.'logo.ico';
  ob_end_clean();
  ob_start();
  header('HTTP/1.1 200 Ok');
  $size=ob_get_length();
  header('Content-Type: image/png');
  header("Cache-Control: no-cache, must-revalidate");
  header("Pragma: no-cache");
  header('Expires: '.gmdate('D, d M Y H:i:s', time()).' GMT');
  header("Content-Length: ".($size + filesize($img)));
  header("Connection: close");
  readfile($img);
  ob_end_flush();
  flush();
  if (function_exists('fastcgi_finish_request'))
    fastcgi_finish_request();
  if (session_id())
    session_write_close();
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
    include($includes_dir.'redirect.php');
    exit(0);
  }
  $_SESSION['message'] = 'Cache files will be cleaned';
  ob_end_clean();
  ob_start();
  header("Location: $url");
  $size=ob_get_length();
  header("Content-Length: $size");
  header("Connection: close");
  ob_end_flush();
  flush();
  if (function_exists('fastcgi_finish_request'))
    fastcgi_finish_request();
  if (session_id())
    session_write_close();
  $cache_expire = '0';
  $cache_clean = 'always';
}

$timestamp_file = $cache_dir.'.cache_timestamp';
if (file_exists($timestamp_file))
  $timestamp = file_get_contents($timestamp_file, true);
else
  $timestamp = 0;

if ($cache_clean !== 'always' && ($cache_clean == '0' || time() - $timestamp < $cache_clean * 86400))
  exit(0);

file_put_contents($timestamp_file, time());

if (isset($_GET['option']) && $_GET['option'] == 'thumbnail') {
  foreach (glob($cache_dir . "[^.]*.thumb", GLOB_NOSORT) as $file) {
    if (time() - filemtime($file) >= $cache_expire * 86400)
      unlink($file);
  }
} elseif (isset($_GET['option']) && $_GET['option'] == 'html') {
  foreach (glob($cache_dir . "[^.]*.html", GLOB_NOSORT) as $file) {
    if (time() - filemtime($file) >= $cache_expire * 86400)
      unlink($file);
  }
} elseif (isset($_GET['option']) && $_GET['option'] == 'box') {
  foreach (glob($cache_dir . "[^.]*.php", GLOB_NOSORT) as $file) {
    if (time() - filemtime($file) >= $cache_expire * 86400)
      unlink($file);
  }
} else {
  foreach (glob($cache_dir . "[^.]*", GLOB_NOSORT) as $file) {
    if (time() - filemtime($file) >= $cache_expire * 86400)
      unlink($file);
  }
}
?>
