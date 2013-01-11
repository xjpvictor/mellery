<?php
define('includeauth',true);
include('../functions.php');

$url = $base_url.'admin/';

$auth=auth($username);
if ($auth == 'pass') {
  $_SESSION['message'] = 'You have already logged in!';
  header("Location: $url");
  exit(0);
}

$otp=getkey(3600);
$otp_code = hash('sha256',$secret_key.$username.$otp);

if (!empty($_GET) && array_key_exists('otp',$_GET) && $_GET['otp'] == $otp_code)
  $change = true;
else
  $change = false;

if (!empty($_POST)) {
  if (array_key_exists('username',$_POST) && array_key_exists('email',$_POST)) {
    if ($_POST['username'] == $username && $_POST['email'] == $email && ($google_auth == '0' || (array_key_exists('google_auth',$_POST) && (verifykey($_POST['google_auth'],30,1) || $_POST['otp'] == $otp_recovery_code)))) {
      mail($email,$site_name.' password reset',wordwrap('Hi '.$username.",<br/><br/>\r\n".'You are receiving this email from '.$site_name." for password reset<br/>\r\nYou can reset your password via the following url<br/><br/>\r\n".'<a href="'.$base_url.'admin/reset.php?otp='.$otp_code.'" target="_blank">'.$base_url.'admin/reset.php?otp='.$otp_code."</a><br/><br/>\r\nThis link is valid for 1 hour only<br/><br/>\r\nIf you did not request for this, please discard it\r\n", 70, "\r\n"),"MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n");
      $redirect_url = $base_url;
      $redirect_message = 'Link for password reset has been sent to your registered email address';
      include($base_dir.'library/redirect.php');
      exit(0);
    }
  } elseif ($change) {
    if (array_key_exists('password',$_POST) && array_key_exists('password_1',$_POST) && $_POST['password'] == $_POST['password_1']) {
      $_POST['password'] = str_replace('"','\"',$_POST['password']);
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
<link rel="stylesheet" href="<?php echo $base_url; ?>library/style.css" type="text/css" media="all" />
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
<input class="button" type="submit" value="Submit" onclick="SubmitForm();">
</form>
<?php } else { ?>
<form name="form1" method="post" action="reset.php">
<p >Username:</p>
<input required name="username"><br/>
<p >Email address:</p>
<input required name="email"><br/>
<p >Google Authenticator code:</p>
<input id="googleauth" name="google_auth"><br/>
<p class="small">* Leave blank if not enabled</p>
<input class="button" type="submit" value="Submit" onclick="SubmitForm();">
</form>
<?php } ?>

<p><a href="<?php echo $base_url; ?>admin/login.php">&lt;&lt; Login</a></p>
<p><a href="<?php echo $base_url; ?>">&lt;&lt; Go Back to Homepage</a></p>
</div>
</div>
</div>
<script type="text/javascript" src="<?php echo $base_url; ?>library/sha256.js"></script>
<script type="text/javascript">
function SubmitForm() {
  if (document.getElementById("password") && document.getElementById("password").value) {
    document.getElementById("password").value = Sha256.hash(document.getElementById("password").value);
  }
  if (document.getElementById("password1") && document.getElementById("password1").value) {
    document.getElementById("password1").value = Sha256.hash(document.getElementById("password1").value);
  }
  if (document.getElementById("googleauth") && document.getElementById("googleauth").value) {
    document.getElementById("googleauth").value = Sha256.hash(document.getElementById("googleauth").value);
  }
  document.form1.submit
}
</script>
</body>
</html>
