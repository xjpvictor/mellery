<?php
define('includeauth',true);
include('../init.php');

header('X-Robots-Tag: noindex,nofollow,noarchive');

$url = $base_url.'admin/';

$auth=auth($username);
if ($auth == 'pass') {
  $_SESSION['message'] = 'You have already logged in!';
  header("Location: $url");
  exit(0);
}

$otp=getotp(3600);
$otp_code = getotp(null, $username.$otp);

if (!empty($_GET) && array_key_exists('otp',$_GET) && $_GET['otp'] == $otp_code)
  $change = true;
else
  $change = false;

if (!empty($_POST)) {
  if (array_key_exists('username',$_POST) && array_key_exists('email',$_POST)) {
    if ($_POST['username'] == $username && $_POST['email'] == $email && ($otp_auth == '0' || (array_key_exists('otp_auth',$_POST) && (verifyotp($_POST['otp_auth'],30,'',0) || $_POST['otp'] == $otp_recovery_code)))) {
      mail($email,$site_name.' password reset',wordwrap('Hi '.$username.",<br/><br/>\r\n".'You are receiving this email from '.$site_name." for password reset<br/>\r\nYou can reset your password via the following url<br/><br/>\r\n".'<a href="'.$base_url.'admin/reset.php?otp='.$otp_code.'" target="_blank">'.$base_url.'admin/reset.php?otp='.$otp_code."</a><br/><br/>\r\nThis link is valid for 1 hour only<br/><br/>\r\nIf you did not request for this, please discard it\r\n", 70, "\r\n"),"From: \"Admin\" <admin@".preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME'])).">\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n");
      $_SESSION['message'] = 'Link for password reset has been sent to your registered email address';
      header("Location: $base_url");
      exit(0);
    }
  } elseif ($change) {
    if (array_key_exists('password',$_POST) && array_key_exists('password_1',$_POST) && $_POST['password'] == $_POST['password_1']) {
      $_POST['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT, ['cost' => $hash_cost]);
      changeconf(array('password' => $_POST['password']));
      $_SESSION['message'] = 'You have changed the password! Please log in';
      header("Location: $url".'login.php');
      exit(0);
    }
  }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0" />
<title>Password reset | <?php echo $site_name; ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" href="<?php echo $base_url; ?>content/style.css" type="text/css" media="all" />
<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body id="login-body">
<div id="reset">
<div id="login-back">
<div id="reset-form">

<?php if ($change) { ?>
<form name="form1" method="post" action="reset.php?otp=<?php echo $otp_code; ?>">
<p >New password:</p>
<input id="password" name="password" type="password"><br/>
<p >Verify new password:</p>
<input id="password1" name="password_1" type="password"><br/>
<input class="button" type="submit" value="Submit">
</form>
<?php } else { ?>
<form name="form1" method="post" action="reset.php">
<p >Username:</p>
<input required name="username"><br/>
<p >Email address:</p>
<input required name="email"><br/>
<?php if (isset($otp_auth) && $otp_auth == '1') { ?>
<p >2-Step Authenticator code:</p>
<input name="otp_auth" type="password"><br/>
<?php } ?>
<br/>
<input class="button" type="submit" value="Submit">
</form>
<?php } ?>

<br/>
<p><a href="<?php echo $base_url; ?>admin/login.php">&lt;&lt; Login</a></p>
<br/>
<p><a href="<?php echo $base_url; ?>">&lt;&lt; Go Back to Homepage</a></p>
</div>
</div>
</div>
</body>
</html>
