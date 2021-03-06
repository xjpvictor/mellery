<?php
define('includeauth',true);
include('../init.php');

header('X-Robots-Tag: noindex,nofollow,noarchive');

if (!empty($_GET) && array_key_exists('ref',$_GET))
  $url = urldecode($_GET['ref']);
else
  $url = $base_url.'admin/';

if (ipblock($_SERVER['REMOTE_ADDR'])) {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url;
  $redirect_message = 'Too many failures. Please wait for some time.';
  include($includes_dir.'redirect.php');
  exit(0);
}

$auth=auth($username);
if ($auth == 'pass') {
  session_regenerate_id(true);
  $_SESSION['message'] = 'You have logged in!';
  header("Location: $url");
  exit(0);
}

if (!empty($_POST) && array_key_exists('username',$_POST) && array_key_exists('password',$_POST)) {
  if ($_POST['username'] == $username && password_verify($_POST['password'], $password)) {
    if ($otp_auth == '1' && array_key_exists('otp',$_POST) && $_POST['otp'] == $otp_recovery_code) {
      $otp_auth = '0';
      changeconf(array('otp_auth' => '0'));
      $otp_recovery = true;
    }
    if ($otp_auth == '0' || (array_key_exists('otp',$_POST) && verifyotp($_POST['otp'],30,'',0))) {
      $_SESSION['time'] = time();
      if (!$auth) {
        $_SESSION['ip'] = hash($hash_algro, $_SERVER['REMOTE_ADDR']);
        $_SESSION['ip_ts'] = time();
        $_SESSION['ip_change'] = 0;
      }
      $_SESSION[$username] = hash($hash_algro,$username);
      if (isset($otp_recovery) && $otp_recovery) {
        $_SESSION['message'] = '2-step authentication has been disabled';
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
<link rel="stylesheet" href="<?php echo $base_url; ?>content/style.css" type="text/css" media="all" />
<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body id="login-body">
<div id="login">
<div id="login-back">
<div id="login-form">
<p><b>Please log in</b></p><br/>
<form name="form1" method="post" action="login.php?ref=<?php echo urlencode($url); ?>">
<p>Username:</p>
<input required id="username" name="username" autofocus><br/>
<p>Password:</p>
<input required id="password" name="password" type="password"><br/>
<?php if (isset($otp_auth) && $otp_auth == '1') { ?>
<p>2-Step Authenticator code:</p>
<input id="otp" name="otp" type="password"><br/>
<?php } ?>
<br/>
<input class="button" type="submit" value="Log in" >
</form>
<br/>
<p><a href="<?php echo $base_url; ?>admin/reset.php">Reset password</a></p>
<br/><a href="<?php echo $base_url; ?>">&lt;&lt; Go Back to Homepage</a>
</div>
</div>
</div>
<script type="text/javascript" src="<?php echo $base_url; ?>content/jquery.js"></script>
<?php if (!empty($_SESSION) && array_key_exists('message',$_SESSION) && !empty($_SESSION['message'])) { ?>
<div id="delaymessage">
<?php echo $_SESSION['message']; $_SESSION['message'] = ''; ?>
</div>
<script type="text/javascript">
  $(document).ready(function(){
    $("#delaymessage").show("fast");
    var to=setTimeout("hideDiv()",5000);
  });
  function hideDiv()
  {
    $("#delaymessage").hide("fast");
  }
</script>
<?php } ?>
</body>
</html>
