<?php
define('includeauth',true);
include(__DIR__.'/init.php');

header('X-Robots-Tag: noindex,nofollow,noarchive');

if (!empty($_GET) && array_key_exists('fid',$_GET) && array_key_exists('ref',$_GET))
  $url = urldecode($_GET['ref']);
else {
  header("HTTP/1.1 403 Forbidden");
  include($includes_dir.'403.php');
  exit(0);
}

if (ipblock($_SERVER['REMOTE_ADDR'])) {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url;
  $redirect_message = 'Too many failures. Please wait for some time.';
  include($includes_dir.'redirect.php');
  exit(0);
}

$header_string=boxauth();
$box_cache=boxcache();
//$folder_list=getfolderlist();

$folder_id=$_GET['fid'];
$file_list=getfilelist($folder_id);
if ($file_list == 'error') {
  header("Status: 404 Not Found");
  include($includes_dir.'404.php');
  exit(0);
}

$auth=auth(array('id-'.$folder_id,$username));
session_regenerate_id(true);
if ($auth == 'pass') {
  header("Location: $url");
  exit(0);
}

$access = getaccess($folder_id);
if ($access) {
  $pass = true;
} else {
  $pass = false;
  if (isset($_POST['accesscode'])) {
    if (getaccess($folder_id, $_POST['accesscode']))
      $pass = true;
    if (!$pass) {
      session_destroy();
      recordfailure($_SERVER['REMOTE_ADDR']);
    }
  }
}
if ($pass) {
  $_SESSION['time'] = time();
  $_SESSION['id-'.$folder_id] = hash($hash_algro,'id-'.$folder_id);
  $_SESSION['message'] = 'Access granted';
  header("Location: $url");
  exit(0);
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0" />
<title>Access | <?php echo $site_name; ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" href="<?php $cu = getcontenturl(null); echo $cu; ?>content/style.css<?php if ($cu !== $base_url) echo '?ver=',filemtime($content_dir.'/style.css'); ?>" type="text/css" media="all" />
<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body id="login-body">
<div id="access">
<div id="login-back">
<div id="access-form">
<p >You are trying to access restricted zone.<br/>Please enter access code:<br/></p><br/>
<form name="form1" method="post" action="<?php echo $base_url;?>access.php?fid=<?php echo $folder_id; ?>&amp;ref=<?php echo urlencode($url); ?>">
<input required id="accesscode" name="accesscode" type="password" autofocus><br/><br/>
<input class="button" type="submit" value="Submit" >
</form>
<br/>
<p><a href="<?php echo $base_url; ?>">&lt;&lt; Go Back to Homepage</a></p>
</div>
</div>
</div>
</body>
</html>
