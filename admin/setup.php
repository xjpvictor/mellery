<?php

if (file_exists('../config.php')) {
  include('../config.php');
  header("Location: ".$base_url."admin/configure.php");
  exit(0);
}

session_set_cookie_params(0,'/','',1,1);
session_name('_mellery_setup');
session_start();

function getpageurl() {
  if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off')
    $proto='http://';
  else
    $proto='https://';
  if (!empty($_SERVER['QUERY_STRING']))
    $uri = $proto.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING'];
  else
    $uri = $proto.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
  return($uri);
}
function generaterandomstring($length) {
  $characters = '234567ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, strlen($characters) - 1)];
  }
  return $randomString;
}

if (!empty($_POST)) {
  $url = getpageurl();
  if (!array_key_exists('base_dir',$_POST) || empty($_POST['base_dir'])) {
    $_SESSION['message'] = 'Please fill up all fields'.'<br/>';
    header("Location: ".$url);
    exit(0);
  } else {
    if (substr($_POST['base_dir'], -1) !== '/') {
      $base_dir = $_POST['base_dir'].'/';
    } else {
      $base_dir = $_POST['base_dir'];
    }
  }
  $config_key = include($base_dir.'admin/config_key.php');
  $config_file = $base_dir.'config.php';
  file_put_contents($config_file, '<?php'."\n", LOCK_EX);
  chmod($config_file, 0600);

  foreach ($config_key as $key) {
    if (($key == 'home_page' || $key == 'site_description' || $key == 'disqus_shortname') && array_key_exists($key,$_POST)) {
      $$key = str_replace('"','\"',$_POST[$key]);
    } elseif (array_key_exists($key,$_POST) && ($_POST[$key] == '0' || !empty($_POST[$key]))) {
      $_POST[$key] = str_replace('"','\"',$_POST[$key]);
      if ($key == 'password') {
        if ($_POST[$key] == $_POST[$key.'_1'])
          $$key = $_POST[$key];
        else {
          unlink($config_file);
          $_SESSION['message'] = 'Password not matched'.'<br/>';
          header("Location: ".$url);
          exit(0);
        }
      } elseif ($key == 'google_auth') {
        $secret_key = generaterandomstring(16);
        $otp_recovery_code = generaterandomstring(8);
        if ($_POST[$key] == '1') {
          $_SESSION['showotpqr'] = true;
          $_SESSION['otp_recovery_code'] = $otp_recovery_code;
        }
        $$key = $_POST[$key];
      } else
        $$key = $_POST[$key];
      if (($key == 'base_url' || $key == 'base_dir') && substr($$key, -1) !== '/') {
        $$key .= '/';
      }
    } else {
      unlink($config_file);
      $_SESSION['message'] = 'Please fill up all fields'.'<br/>';
      header("Location: ".$url);
      exit(0);
    }
    file_put_contents($config_file, '$'.$key.' = "'.$$key.'";'."\n", FILE_APPEND | LOCK_EX);
  }

  file_put_contents($config_file, '$admin_folder_limit = \'25\';'."\n".'$secret_key = \''.$secret_key.'\';'."\n".'$otp_recovery_code = \''.hash('sha256',$otp_recovery_code).'\';'."\n".'$w = \'150\';'."\n".'$h = \'150\';'."\n".'$cache_dir = $base_dir.\'cache/\';'."\n".'$data_dir = $base_dir.\'data/\';'."\n".'$box_token_file = $base_dir.\'box_token.php\';'."\n".'?>', FILE_APPEND | LOCK_EX);
  session_destroy();
  session_name('_mellery');
  session_start();
  $_SESSION[$username] = hash('sha256', $secret_key.$username);
  $_SESSION['time'] = time();
  $_SESSION['ip'] = hash('sha256', $secret_key.$_SERVER['REMOTE_ADDR']);
  $_SESSION['ip_ts'] = time();
  $_SESSION['ip_change'] = 0;
  $_SESSION['message'] = 'Setup finished';
  header("Location: ".$base_url.'admin/authbox.php');
  exit(0);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0" />
<title>Setup | Mellery</title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="stylesheet" href="../library/style.css" type="text/css" media="all" />
<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body>
<div id="wrap" class="clearfix">
<div id="main">
<div class="logo">
<h1>Mellery Setup</h1>
</div>
<div id="content-admin">
<div id="wrap-admin">
<form action="setup.php" name="form1" method="POST">
<div class="site-config">
<p class="config-title">Site Configuration</p>
<table>
<tr><td><p>Site name:</p></td><td><input required name="site_name"></td></tr>
<tr><td><p>Site description (Optional):</p></td><td><input name="site_description"></td></tr>
<tr><td><p>Base url for Mellery:</p></td><td><input required name="base_url" value="<?php echo $_SERVER['SERVER_NAME']; ?>"></td></tr>
<tr><td><p>Installation directory for Mellery:</p></td><td><input required name="base_dir" value="<?php echo realpath('../').'/'; ?>"></td></tr>
<tr><td><p>Disqus shortname (Optional):</p></td><td><input name="disqus_shortname"></td></tr>
<tr><td><p>Thumbnails shown per page:</p></td><td><input required name="limit" value="25"></td></tr>
<tr><td><p>Image expiration time (s, 0 for not expiring):</p></td><td><input required name="expire_image" value="86400"></td></tr>
<tr><td><p>Cache expiration time (days):</p></td><td><input required name="cache_expire" value="1"></td></tr>
<tr><td><p>Expired cache clean frequency (days, 0 for manually cleaning):</p></td><td><input required name="cache_clean" value="1"</td></tr>
</table>
</div>

<div class="site-config">
<p class="config-title">Box.com Configuration</p>
<table>
<tr><td><p>Client ID:</p></td><td><input required name="client_id"></td></tr>
<tr><td><p>Client secret:</p></td><td><input required name="client_secret"></td></tr>
<tr><td><p>Box.com root folder ID:</p></td><td><input required name="box_root_folder_id" value="0"></td></tr>
<tr><td></td><td><p class="small">* If all albums are in the folder named "photo" on Box.com,  get the url of this folder from Box.com: https://www.box.com/files/0/f/xxxxxxxxx/photo , xxxxxxxxx is the ID. Set as 0 if albums are not in a specific folder.</p></td></tr>
</table>
</div>

<div class="site-config">
<p class="config-title">User</p>
<table>
<tr><td><p>Username:</p></td><td><input required name="username"></td></tr>
<tr><td><p>Email:</p></td><td><input required name="email"></td></tr>
<tr><td><p>Your homepage (Optional):</p></td><td><input name="home_page"></td></tr>
<tr><td><p>Password:</p></td><td><input required id="password" type="password" name="password"></td></tr>
<tr><td><p>Re-type password:</p></td><td><input required id="password-1" type="password" name="password_1"></td></tr>
<tr><td><p>Use Google 2-step authentication:</p></td><td><input type="hidden" name="google_auth" value="0"><input class="checkbox" type="checkbox" name="google_auth" value="1" checked></td></tr>
<tr><td><p>General album access code:</p></td><td><input required type="password" name="general_access_code"></td></tr>
</table>
</div>

<div class="site-config">
<p class="config-title">Security</p>
<table>
<tr><td><p>Session expiration time (s):</p></td><td><input required name="expire_session" value="1800"></td></tr>
<tr><td><p>Use cookie on HTTPS only:</p></td><td><input type="hidden" name="https" value="0"><input class="checkbox" type="checkbox" name="https" value="1" checked></td></tr>
<tr><td><p>Maximum access retry in one minutes (0 for unlimited):</p></td><td><input required name="retry" value="5"></td></tr>
<tr><td><p>Exessive access retry lock down period (s, 0 for always locked):</p></td><td><input required name="lock_timeout" value="60"></td></tr>
</table>
</div>

<div style="width:80px;padding-top:20px;margin:0px auto 0px auto;">
<input class="button" style="width:80px;" type="submit" value="Save" onclick="SubmitForm();">
</div>
</form>
</div>
</div>
</div>
</div>
<script type="text/javascript" src="../library/sha256.js"></script>
<script type="text/javascript" src="../library/jquery.js"></script>
<script type="text/javascript">
    function SubmitForm() {
      if (document.getElementById("password").value) {
        document.getElementById("password").value = Sha256.hash(document.getElementById("password").value);
      }
      if (document.getElementById("password-1").value) {
        document.getElementById("password-1").value = Sha256.hash(document.getElementById("password-1").value);
      }
      document.form1.submit
    }
</script>
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
