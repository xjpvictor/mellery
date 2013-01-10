<?php
define('includeauth',true);
include('../functions.php');

if (!empty($_GET) && array_key_exists('ref',$_GET))
  $url = urldecode($_GET['ref']);
else
  $url = $base_url.'admin/';

if (ipblock($_SERVER['REMOTE_ADDR'])) {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url;
  $redirect_message = 'Too many failures. Please wait for some time.';
  include($base_dir."library/redirect.php");
  exit(0);
}

$auth=auth($username);
if ($auth == 'pass') {
  session_regenerate_id(true);
  $_SESSION['message'] = 'You have logged in!';
  header("Location: $url");
  exit(0);
}

$otp=getkey(60);
if (!empty($_POST) && array_key_exists('username',$_POST) && array_key_exists('password',$_POST)) {
  if ($_POST['username'] == $username && ($_POST['password'] == hash('sha256',$password.$otp) || $_POST['password'] == hash('sha256',$password.getprevkey(60)))) {
    if ($google_auth == '1' && array_key_exists('otp',$_POST) && $_POST['otp'] == $otp_recovery_code) {
      $google_auth = '0';
      changeconf(array('google_auth' => '0'));
      $otp_recovery = true;
    }
    if ($google_auth == '0' || (array_key_exists('otp',$_POST) && verifykey($_POST['otp'],30,1))) {
      $_SESSION['time'] = time();
      if (!$auth) {
        $_SESSION['ip'] = hash('sha256', $secret_key.$_SERVER['REMOTE_ADDR']);
        $_SESSION['ip_ts'] = time();
        $_SESSION['ip_change'] = 0;
      }
      $_SESSION[$username] = hash('sha256',$secret_key.$username);
      if (isset($otp_recovery) && $otp_recovery) {
        $_SESSION['message'] = 'Google 2-step authentication has been disabled';
        header ("Location: ".$base_url."admin/configure.php");
      } else {
        $_SESSION['message'] = 'You have logged in!';
        header("Location: $url");
      }
      exit(0);
    }
  }
  session_destroy();
  recordfailure($_SERVER['REMOTE_ADDR']);
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0" />
<title>Log in | <?php echo $site_name; ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" href="<?php echo $base_url; ?>library/style.css" type="text/css" media="all" />
<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body id="login-body">
<div id="login">
<div id="login-back">
<script type="text/javascript">
function SubmitForm() {
  if (document.getElementById("password").value) {
<?php
echo 'document.getElementById("password").value = Sha256.hash(Sha256.hash(document.getElementById("password").value) + "'.$otp.'");'."\n";
?>
  }
  if (document.getElementById("otp").value) {
    document.getElementById("otp").value = Sha256.hash(document.getElementById("otp").value);
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
<div id="login-form">
<p><b>Please log in</b></p>
<form name="form1" method="post" action="login.php?ref=<?php echo urlencode($url); ?>">
<p>Username:</p>
<input required id="username" name="username"><br/>
<p>Password:</p>
<input required id="password" name="password" type="password"><br/>
<p>Google Authenticator code:</p>
<input id="otp" name="otp"><br/>
<p class="small">* Leave blank if not enabled</p>
<input class="button" type="submit" value="Log in" onclick="SubmitForm();">
</form>
<p class="small">* This page is valid for <span id="count-down"></span> s.</p>
<p><a href="<?php echo $base_url; ?>admin/reset.php">Reset password</a></p>
<br/><a href="<?php echo $base_url; ?>">&lt;&lt; Go Back to Homepage</a>
</div>
</div>
</div>
</body>
<script type="text/javascript" src="<?php echo $base_url; ?>library/sha256.js"></script>
<script type="text/javascript" src="<?php echo $base_url; ?>library/jquery.js"></script>
<?php
if (!empty($_SESSION) && array_key_exists('message',$_SESSION) && !empty($_SESSION['message'])) {
  echo '<div id="delaymessage">';
  echo $_SESSION['message'];
  echo '</div>';
  echo '<script type="text/javascript">'."\n";
  echo '  $(document).ready( function(){'."\n";
  echo '    $("#delaymessage").show("fast");'."\n";
  echo '    var to=setTimeout("hideDiv()",5000);'."\n";
  echo '  });'."\n";
  echo '  function hideDiv()'."\n";
  echo '  {'."\n";
  echo '    $("#delaymessage").hide("fast");'."\n";
  echo '  }'."\n";
  echo '</script>';
  $_SESSION['message'] = '';
}
?>
</html>
