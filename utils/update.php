<?php
include('../init.php');

ob_end_clean();
ob_start();
header('HTTP/1.1 200 Ok');
$size=ob_get_length();
header("Content-Length: $size");
header("Connection: close");
ob_end_flush();
flush();
if (function_exists('fastcgi_finish_request'))
  fastcgi_finish_request();
if (session_id())
  session_write_close();

boxauth();
?>
