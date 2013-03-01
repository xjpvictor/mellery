<?php
include_once('./data/config.php');
include_once($base_dir.'functions.php');

ob_end_clean();
header('HTTP/1.1 200 Ok');
header("Connection: close");
ob_start();
$size=ob_get_length();
header("Content-Length: $size");
ob_end_flush();
flush();

boxauth();
?>
