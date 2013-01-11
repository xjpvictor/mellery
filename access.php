<?php
define('includeauth',true);
include_once('functions.php');

if (!empty($_GET) && array_key_exists('id',$_GET) && array_key_exists('ref',$_GET))
  $url = urldecode($_GET['ref']);
else {
  header("HTTP/1.1 403 Forbidden");
  include($base_dir.'library/403.php');
  exit(0);
}

if (ipblock($_SERVER['REMOTE_ADDR'])) {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url;
  $redirect_message = 'Too many failures. Please wait for some time.';
  include($base_dir.'library/redirect.php');
  exit(0);
}

$otp=getkey(60);

$header_string=boxauth();
$box_cache=boxcache();
$folder_list=getfolderlist();

$folder_id=$_GET['id'];
if (!array_key_exists('id-'.$folder_id,$folder_list)) {
  header("Status: 404 Not Found");
  include($base_dir.'library/404.php');
  exit(0);
}

$auth=auth(array('id-'.$folder_id,$username));
session_regenerate_id(true);
if ($auth == 'pass') {
  header("Location: $url");
  exit(0);
}

if ($folder_list['id-'.$folder_id]['access']['public'][0] == '1') {
  $pass = true;
} else {
  $pass = false;
  if (!empty($_POST) && array_key_exists('accesscode',$_POST)) {
    foreach (array_slice($folder_list['id-'.$folder_id]['access'],1,3) as $key => $access) {
      if ($key == 'general' && $access[0] == 1 ){
        if ($_POST['accesscode'] == hash('sha256',hash('sha256',$general_access_code).$otp) || $_POST['password'] == hash('sha256',hash('sha256',$general_access_code).getprevkey(60))) {
          $pass = true;
          break;
        }
      } elseif ($access[0] == 1 && !empty($access['code'])) {
        if (!array_key_exists('time',$access) || (array_key_exists('time',$access) && $access['time'] > time())) {
          if ($_POST['accesscode'] == hash('sha256',hash('sha256',$access['code']).$otp) || $_POST['accesscode'] == hash('sha256',hash('sha256',$access['code']).getprevkey(60))) {
            $pass = true;
            break;
          }
        }
      }
    }
    if (!$pass) {
      session_destroy();
      recordfailure($_SERVER['REMOTE_ADDR']);
    }
  }
}
if ($pass) {
  $_SESSION['time'] = time();
  if (!$auth) {
    $_SESSION['ip'] = hash('sha256', $secret_key.$_SERVER['REMOTE_ADDR']);
    $_SESSION['ip_ts'] = time();
    $_SESSION['ip_change'] = 0;
  }
  $_SESSION['id-'.$folder_id] = hash('sha256',$secret_key.'id-'.$folder_id);
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
<link rel="stylesheet" href="<?php echo $base_url; ?>library/style.css" type="text/css" media="all" />
<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body id="login-body">
<div id="access">
<div id="login-back">
<div id="access-form">
<p >You are trying to access restricted zone.<br/>Please enter access code:<br/></p>
<form name="form1" method="post" action="<?php echo $base_url;?>access.php?id=<?php echo $folder_id; ?>&amp;ref=<?php echo urlencode($url); ?>">
<input required id="accesscode" name="accesscode" type="text">
<input class="button" type="submit" value="Submit" onclick="SubmitForm();">
</form>
<p class="small">* This page is valid for <span id="count-down"></span> s.</p><br/>
<a href="<?php echo $base_url; ?>"><p>&lt;&lt; Go Back to Homepage</p></a>
</div>
</div>
</div>
<script type="text/javascript" src="<?php echo $base_url; ?>library/sha256.js"></script>
<script type="text/javascript">
function SubmitForm() {
  if (document.getElementById("accesscode").value) {
    document.getElementById("accesscode").value = Sha256.hash(Sha256.hash(document.getElementById("accesscode").value) + "<?php echo $otp; ?>");
  }
  document.form1.submit
}
function Load(){ 
  var stoptime=60;
for(var i=stoptime;i>=0;i--) 
{ 
  window.setTimeout('doUpdate(' + i + ')', (stoptime-i) * 1000); 
}
} 
function doUpdate(num) 
{
  document.getElementById('count-down').innerHTML = num ;
}
Load();
</script>
</body>
</html>
