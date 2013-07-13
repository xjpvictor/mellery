<?php

if (file_exists('../data/config.php') && file_exists('../box_token.php')) {
  include_once('../data/config.php');
  header("Location: ".$base_url."admin/configure.php");
  exit(0);
} elseif (file_exists('../data/config.php')) {
  include_once('../data/config.php');
  header("Location: ".$base_url.'admin/authbox.php');
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
  $secret_key = generaterandomstring(16);
  $config_key = include_once($base_dir.'admin/config_key.php');
  $config_file = $base_dir.'data/config.php';
  file_put_contents($config_file, '<?php'."\n", LOCK_EX);
  chmod($config_file, 0600);

  foreach ($config_key['mandatory'] as $key) {
    if (array_key_exists($key,$_POST) && ($_POST[$key] == '0' || !empty($_POST[$key]))) {
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
        $$key = '0';
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
  foreach ($config_key['optional'] as $key => $value) {
    file_put_contents($config_file, '$'.$key.' = "'.$value.'";'."\n", FILE_APPEND | LOCK_EX);
  }
  foreach ($config_key['preset'] as $key => $value) {
    file_put_contents($config_file, '$'.$key.' = "'.$value.'";'."\n", FILE_APPEND | LOCK_EX);
  }
  foreach ($config_key['fix'] as $key => $value) {
    file_put_contents($config_file, '$'.$key.' = "'.$value.'";'."\n", FILE_APPEND | LOCK_EX);
  }

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
<tr><td><p>Base url for Mellery:</p></td><td><input required name="base_url" value="<?php echo $_SERVER['SERVER_NAME']; ?>"></td></tr>
<tr><td><p>Installation directory for Mellery:</p></td><td><input required name="base_dir" value="<?php echo realpath('../').'/'; ?>"></td></tr>
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
<tr><td><p>Password:</p></td><td><input required id="password" type="password" name="password"></td></tr>
<tr><td><p>Re-type password:</p></td><td><input required id="password-1" type="password" name="password_1"></td></tr>
<tr><td><p>General album access code:</p></td><td><input required type="password" name="general_access_code"></td></tr>
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
