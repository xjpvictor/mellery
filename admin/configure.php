<?php
define('includeauth',true);
include_once('../data/config.php');
include_once($base_dir.'functions.php');

header('X-Robots-Tag: noindex,nofollow,noarchive');

$auth=auth($username);
$url=getpageurl();
if ($auth !== 'pass') {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.$url;
  $redirect_message = 'Access restricted';
  include($includes_dir.'redirect.php');
  exit(0);
}

if (!empty($_POST) && array_key_exists('base_dir',$_POST) && !empty($_POST['base_dir']))
  $base_dir = $_POST['base_dir'];
elseif (!isset($base_dir) || empty($base_dir))
  $base_dir = realpath('../').'/';
if (substr($base_dir, -1) !== '/')
  $base_dir .= '/';

if (!empty($_POST) && array_key_exists('base_url',$_POST) && !empty($_POST['base_url']))
  $base_url = $_POST['base_url'];
elseif (!isset($base_url) || empty($base_url))
  $base_url = realpath('../').'/';
if (substr($base_url, -1) !== '/')
  $base_url .= '/';

if (!empty($_POST)) {
  $config_key = include($base_dir.'admin/config_key.php');
  $config_file = $base_dir.'data/config.php';
  file_put_contents($config_file, '<?php'."\n", LOCK_EX);
  chmod($config_file, 0600);
  $_SESSION['message'] = '';

  $old_password_auth = true;
  foreach ($config_key['mandatory'] as $key) {
    if (array_key_exists($key,$_POST) && ($_POST[$key] == '0' || !empty($_POST[$key])) && $_POST[$key] !== $$key) {
      $_POST[$key] = str_replace('"','\"',$_POST[$key]);
      if ($key == 'password') {
        if ($old_password_auth && array_key_exists('old_password',$_POST) && $_POST['old_password'] == $password) {
          if ($_POST[$key] == $_POST[$key.'_1'])
            $$key = $_POST[$key];
          else {
            $_SESSION['message'] = 'Password not matched<br/>';
          }
        } else {
          $old_password_auth = false;
          $_SESSION['message'] = 'Wrong password<br/>';
        }
      } elseif ($key == 'google_auth') {
        if ($old_password_auth && array_key_exists('old_password',$_POST) && $_POST['old_password'] == $password) {
          if ($_POST[$key] == '1') {
            $secret_key = generaterandomstring(16);
            $otp_recovery_code = generaterandomstring(8);
            $_SESSION['showotpqr'] = true;
            $_SESSION['otp_recovery_code'] = $otp_recovery_code;
          }
          $$key = $_POST[$key];
        } else {
          $old_password_auth = false;
          $_SESSION['message'] = 'Wrong password<br/>';
        }
      } else
        $$key = $_POST[$key];
      if (($key == 'base_url' || $key == 'base_dir') && substr($$key, -1) !== '/') {
        $$key .= '/';
      }
      if ($key == 'client_id' || $key == 'client_secret') {
        $_SESSION['message'] = 'Please re-authenticate with box.com<br/>';
      }
      if (!isset($$key) || ($$key !== '0' && empty($$key))) {
        $notify = true;
      }
    }
    file_put_contents($config_file, '$'.$key.' = "'.$$key.'";'."\n", FILE_APPEND | LOCK_EX);
  }
  foreach ($config_key['optional'] as $key => $value) {
    if (array_key_exists($key,$_POST) && ($_POST[$key] == '0' || !empty($_POST[$key])) && $_POST[$key] !== $$key) {
      $$key = str_replace('"','\"',$_POST[$key]);
    }
    if ($key == 'cdn_url' && substr($$key, -1) !== '/') {
      $$key .= '/';
    }
    file_put_contents($config_file, '$'.$key.' = "'.$$key.'";'."\n", FILE_APPEND | LOCK_EX);
  }
  foreach ($config_key['preset'] as $key => $value) {
    if (array_key_exists($key,$_POST) && ($_POST[$key] == '0' || !empty($_POST[$key])) && $_POST[$key] !== $$key) {
      $$key = str_replace('"','\"',$_POST[$key]);
      if ($key == 'allow_se') {
        $robots = file($base_dir.'robots.txt', FILE_IGNORE_NEW_LINES);
        if ($$key) {
          $robots = preg_replace('/^(Disallow: \/)$/','#\1',$robots);
        } else {
          $robots = preg_replace('/^#(Disallow: \/)$/','\1',$robots);
        }
        unlink($base_dir.'robots.txt');
        foreach ($robots as $line)
          file_put_contents($base_dir.'robots.txt',$line."\n", FILE_APPEND | LOCK_EX);
      }
    }
    file_put_contents($config_file, '$'.$key.' = "'.$$key.'";'."\n", FILE_APPEND | LOCK_EX);
  }
  foreach ($config_key['fix'] as $key => $value) {
    file_put_contents($config_file, '$'.$key.' = "'.$value.'";'."\n", FILE_APPEND | LOCK_EX);
  }

  if (isset($notify) && $notify)
    $_SESSION['message'] = $_SESSION['message'].'Please fill up the highlighted parts';
  else
    $_SESSION['message'] = $_SESSION['message'].'Configuration saved';
  $_SESSION[$username] = hash('sha256',$secret_key.$username);
  session_regenerate_id(true);
  header("Location: ".$base_url."admin/configure.php");
  exit(0);
}

if (file_exists($data_dir.'my_page.php'))
  $my_page = include($data_dir.'my_page.php');
include('head.php');

$otp_session=getkey($expire_session);
?>

<form action="configure.php" name="form1" method="POST">
<div class="site-config clearfix">
<p class="config-title" onclick="show('conf-basic')">Basic</p>
<div id="conf-basic" style="display:block;">
<table>
<tr><td><p<?php if (!isset($site_name) || ($site_name !== '0' && empty($site_name))) {echo ' class="notset"'; $notify = true;} ?>>Site name:</p></td><td><input required name="site_name" value="<?php if (isset($site_name)) echo htmlentities($site_name); ?>"></td></tr>
<tr><td><p>Site description (Optional):</p></td><td><input name="site_description" value="<?php if (isset($site_description)) echo htmlentities($site_description); ?>"></td></tr>
<tr><td><p<?php if (!isset($base_url) || ($base_url !== '0' && empty($base_url))) {echo ' class="notset"'; $notify = true;} ?>>Base url for Mellery:</p></td><td><input required name="base_url" value="<?php if (isset($base_url)) echo htmlentities($base_url); ?>"></td></tr>
<tr><td><p<?php if (!isset($base_dir) || ($base_dir !== '0' && empty($base_dir))) {echo ' class="notset"'; $notify = true;} ?>>Installation directory for Mellery:</p></td><td><input required name="base_dir" value="<?php if (isset($base_dir)) echo htmlentities($base_dir); ?>"></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<p class="config-title" onclick="show('conf-appearance')">Appearance</p>
<div id="conf-appearance" style="display:none;">
<table>
<tr><td><p<?php if (!isset($w_max) || ($w_max !== '0' && empty($w_max))) {echo ' class="notset"'; $notify = true;} ?>>Maximum width of thumbnails:</p></td><td><input required name="w_max" value="<?php if (isset($w_max)) echo htmlentities($w_max); ?>"></td></tr>
<tr><td><p<?php if (!isset($h_max) || ($h_max !== '0' && empty($h_max))) {echo ' class="notset"'; $notify = true;} ?>>Maximum height of thumbnails:</p></td><td><input required name="h_max" value="<?php if (isset($h_max)) echo htmlentities($h_max); ?>"></td></tr>
<tr><td><p<?php if (!isset($limit) || ($limit !== '0' && empty($limit))) {echo ' class="notset"'; $notify = true;} ?>>Thumbnails shown per page:</p></td><td><input required name="limit" value="<?php if (isset($limit)) echo htmlentities($limit); ?>"></td></tr>
<tr><td><p<?php if (!isset($usemap) || ($usemap !== '0' && empty($usemap))) {echo ' class="notset"'; $notify = true;} ?>>Show map if geolocation is available:</p></td><td><input type="hidden" name="usemap" value="0"><input class="checkbox" type="checkbox" name="usemap" value="1"<?php if (isset($usemap) && $usemap == '1') echo " checked"; ?>></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<p class="config-title" onclick="show('conf-comm')">Comments</p>
<div id="conf-comm" style="display:none;">
<table>
<tr><td><p>Disqus shortname (Optional):</p></td><td><input name="disqus_shortname" value="<?php if (isset($disqus_shortname)) echo htmlentities($disqus_shortname); ?>"></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<p class="config-title" onclick="show('conf-privacy')">Privacy</p>
<div id="conf-privacy" style="display:none;">
<table>
<tr><td><p<?php if (!isset($default_public) || ($default_public !== '0' && empty($default_public))) {echo ' class="notset"'; $notify = true;} ?>>Default album access:</p></td><td><label><input class="radio" type="radio" name="default_public" value="0"<?php if (isset($default_public) && $default_public == '0') echo " checked"; ?>>Private</label><br/><label><input class="radio" type="radio" name="default_public" value="1"<?php if (isset($default_public) && $default_public == '1') echo " checked"; ?>>Public</label></td></tr>
<tr><td><p<?php if (!isset($allow_se) || ($allow_se !== '0' && empty($allow_se))) {echo ' class="notset"'; $notify = true;} ?>>Allow search engine to index:</p></td><td><input type="hidden" name="allow_se" value="0"><input class="checkbox" type="checkbox" name="allow_se" value="1"<?php if (isset($allow_se) && $allow_se == '1') echo " checked"; ?>></td></tr>
<tr><td></td><td><p class="small">* Only applicable when installed in site root directory and if search engine follows robots.txt standard</p></td></tr>
<tr><td><p<?php if (!isset($nolicense) || ($nolicense !== '0' && empty($nolicense))) {echo ' class="notset"'; $notify = true;} ?>>All rights reserved:</p></td><td><input type="hidden" name="nolicense" value="0"><input class="checkbox" type="checkbox" name="nolicense" value="1"<?php if (isset($nolicense) && $nolicense == '1') echo " checked"; ?> onclick="showTable('cc')"></td></tr>
</table>
<table id="cc" <?php if (isset($nolicense) && $nolicense == '1') echo 'style="display:none;"'; elseif (isset($nolicense) && $nolicense == '0') echo 'style="display:table;"'; ?>>
<tr><td><p>Creative Common License</p></td><td><label><input class="radio" type="radio" name="sa" value="0" <?php if (isset($sa) && $sa == '0') echo " checked"; ?> />Disallow Modification</label><br/><label><input class="radio" type="radio" name="sa" value="1" <?php if (isset($sa) && $sa == '1') echo " checked"; ?> />Allow Modification</label><br/><label><input class="radio" type="radio" name="sa" value="2" <?php if (isset($sa) && $sa == '2') echo " checked"; ?> />Allow Modification only if shared alike</label><br/><input type="hidden" name="nc" value="0" /><label><input class="checkbox" type="checkbox" name="nc" value="1" <?php if (isset($nc) && $nc == '1') echo " checked"; ?> />No Commercial uses</label></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<p class="config-title" onclick="show('conf-sec')">Security</p>
<div id="conf-sec" style="display:none;">
<table>
<tr><td><p<?php if (!isset($expire_image) || ($expire_image !== '0' && empty($expire_image))) {echo ' class="notset"'; $notify = true;} ?>>Thumbnail expiration time (s, 0 for not expiring):</p></td><td><input required name="expire_image" value="<?php if (isset($expire_image)) echo htmlentities($expire_image); ?>"></td></tr>
<tr><td><p<?php if (!isset($expire_session) || ($expire_session !== '0' && empty($expire_session))) {echo ' class="notset"'; $notify = true;} ?>>Session expiration time (s):</p></td><td><input required name="expire_session" value="<?php if (isset($expire_session)) echo htmlentities($expire_session); ?>"></td></tr>
<tr><td><p<?php if (!isset($https) || ($https !== '0' && empty($https))) {echo ' class="notset"'; $notify = true;} ?>>Use cookie on HTTPS only:</p></td><td><input type="hidden" name="https" value="0"><input class="checkbox" type="checkbox" name="https" value="1"<?php if (isset($https) && $https == '1') echo " checked"; ?>></td></tr>
<tr><td><p<?php if (!isset($retry) || ($retry !== '0' && empty($retry))) {echo ' class="notset"'; $notify = true;} ?>>Maximum access retry in one minutes (0 for unlimited):</p></td><td><input required name="retry" value="<?php if (isset($retry)) echo htmlentities($retry); ?>"></td></tr>
<tr><td><p<?php if (!isset($lock_timeout) || ($lock_timeout !== '0' && empty($lock_timeout))) {echo ' class="notset"'; $notify = true;} ?>>Exessive access retry lock down period (s, 0 for always locked):</p></td><td><input required name="lock_timeout" value="<?php if (isset($lock_timeout)) echo htmlentities($lock_timeout); ?>"></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<p class="config-title" onclick="show('conf-perf')">Performance</p>
<div id="conf-perf" style="display:none;">
<table>
<tr><td><p<?php if (!isset($cache_expire) || ($cache_expire !== '0' && empty($cache_expire))) {echo ' class="notset"'; $notify = true;} ?>>Cache expiration time (days):</p></td><td><input required name="cache_expire" value="<?php if (isset($cache_expire)) echo htmlentities($cache_expire); ?>"></td></tr>
<tr><td><p<?php if (!isset($cache_clean) || ($cache_clean !== '0' && empty($cache_clean))) {echo ' class="notset"'; $notify = true;} ?>>Expired cache clean frequency (days, 0 for manually cleaning):</p></td><td><input required name="cache_clean" value="<?php if (isset($cache_clean)) echo htmlentities($cache_clean); ?>"></td></tr>
<tr><td></td><td><p class="button" style="width:200px;"><a href="<?php echo $base_url; ?>utils/cache.php?option=all&amp;ref=<?php echo $url; ?>">Clean all cache files now</a></p></td></tr>
<tr><td><p<?php if (!isset($use_cdn) || ($use_cdn !== '0' && empty($use_cdn))) {echo ' class="notset"'; $notify = true;} ?>>Use CDN:</p></td><td><input type="hidden" name="use_cdn" value="0"><input class="checkbox" type="checkbox" name="use_cdn" value="1"<?php if (isset($use_cdn) && $use_cdn == '1') echo " checked"; ?> onclick="showTable('cdn')"></td></tr>
</table>
<table id="cdn" <?php if (isset($use_cdn) && $use_cdn == '0') echo 'style="display:none;"'; ?>>
<tr><td><p>CDN url:</p></td><td><input name="cdn_url" value="<?php if (isset($cdn_url)) echo htmlentities($cdn_url); ?>"></td></tr>
<tr><td><p<?php if (!isset($thumb_cdn) || ($thumb_cdn !== '0' && empty($thumb_cdn))) {echo ' class="notset"'; $notify = true;} ?>>Use CDN for thumbnails:</p></td><td><input type="hidden" name="thumb_cdn" value="0"><input class="checkbox" type="checkbox" name="thumb_cdn" value="1"<?php if (isset($thumb_cdn) && $thumb_cdn == '1') echo " checked"; ?>></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<p class="config-title" onclick="show('conf-user')">User</p>
<div id="conf-user" style="display:none;">
<table>
<tr><td><p<?php if (!isset($username) || ($username !== '0' && empty($username))) {echo ' class="notset"'; $notify = true;} ?>>Username:</p></td><td><input required name="username" value="<?php if (isset($username)) echo htmlentities($username); ?>"></td></tr>
<tr><td><p<?php if (!isset($email) || ($email !== '0' && empty($email))) {echo ' class="notset"'; $notify = true;} ?>>Email:</p></td><td><input required name="email" value="<?php if (isset($email)) echo htmlentities($email); ?>"></td></tr>
<tr><td><p>Your homepage (Optional):</p></td><td><input name="home_page" value="<?php if (isset($home_page)) echo htmlentities($home_page); ?>"></td></tr>
</table>
<div id="password-verify">
<p>Please verify your current password before you modify the following items:</p>
<input id="old_password" type="password" name="old_password">
</div>
<table>
<tr><td><p>New password:</p></td><td><input id="password" type="password" name="password"></td></tr>
<tr><td><p>Re-type new password:</p></td><td><input id="password-1" type="password" name="password_1"></td></tr>
<tr><td><p<?php if (!isset($google_auth) || ($google_auth !== '0' && empty($google_auth))) {echo ' class="notset"'; $notify = true;} ?>>Use Google 2-step authentication:</p></td><td><input type="hidden" name="google_auth" value="0"><input class="checkbox" type="checkbox" name="google_auth" value="1"<?php if (isset($google_auth) && $google_auth == '1') echo " checked"; ?>></td></tr>
<?php if (!empty($_SESSION) && array_key_exists('showotpqr', $_SESSION) && $_SESSION['showotpqr']) { ?>
<tr><td style="vertical-align:top;"><p>Scan the QR code with Google Authenticator (This will only be shown once):</p></td><td><img width="200" height="200" src="https://chart.googleapis.com/chart?chs=200x200&amp;chld=L|0&amp;cht=qr&amp;chl=<?php echo urlencode('otpauth://totp/'); if (isset($site_name)) echo urlencode($site_name); echo urlencode('?secret='.$secret_key); ?>" alt="qr-code" /></td></tr>
<tr><td style="vertical-align:top;"><p>One time recovery code for Google 2-step authentication (This will only be shown once):</p></td><td><?php echo $_SESSION['otp_recovery_code']; ?></td></tr>
<?php
  $_SESSION['showotpqr'] = false;
  $_SESSION['otp_recovery_code'] = '';
}
?>
<tr><td><p<?php if (!isset($general_access_code) || ($general_access_code !== '0' && empty($general_access_code))) {echo ' class="notset"'; $notify = true;} ?>>General album access code (Leave blank if not changed):</p></td><td><input type="password" name="general_access_code"></td></tr>
<tr><td></td><td><p class="button" style="width:200px;"><a href="<?php echo $base_url; ?>admin/folder.php?code=1&amp;fid=general&amp;otp=<?php echo $otp_session; ?>" target="_blank">Show current access code</a></p></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<p class="config-title" onclick="show('conf-box')">Box.com Configuration</p>
<div id="conf-box" style="display:none;">
<table>
<tr><td><p<?php if (!isset($client_id) || ($client_id !== '0' && empty($client_id))) {echo ' class="notset"'; $notify = true;} ?>>Client ID (Leave blank if not changed):</p></td><td><input name="client_id"></td></tr>
<tr><td><p<?php if (!isset($client_secret) || ($client_secret !== '0' && empty($client_secret))) {echo ' class="notset"'; $notify = true;} ?>>Client secret (Leave blank if not changed):</p></td><td><input name="client_secret"></td></tr>
<tr><td><p<?php if (!isset($box_root_folder_id) || ($box_root_folder_id !== '0' && empty($box_root_folder_id))) {echo ' class="notset"'; $notify = true;} ?>>Box.com root folder ID:</p></td><td><input required name="box_root_folder_id" value="<?php if (isset($box_root_folder_id)) echo htmlentities($box_root_folder_id); ?>"></td></tr>
<tr><td></td><td><p class="small">* If all albums are in the folder named "photo" on Box.com,  get the url of this folder from Box.com: https://www.box.com/files/0/f/xxxxxxxxx/photo , xxxxxxxxx is the ID. Set as 0 if albums are not in a specific folder.</p></td></tr>
<tr><td></td><td><p class="button" style="width:200px;"><a href="<?php echo $base_url; ?>admin/authbox.php" target="_blank">Authenticate with Box.com</a></p></td><td></td></tr>
</table>
</div>
</div>

<div style="width:80px;padding-top:20px;margin:0px auto 0px auto;">
<input class="button" style="width:80px;" type="submit" value="Save" onclick="SubmitForm();">
</div>
</form>
</div>
<script type="text/javascript" src="<?php echo $base_url; ?>content/sha256.js"></script>
<script type="text/javascript">
    function SubmitForm() {
      if (document.getElementById("password").value) {
        document.getElementById("password").value = Sha256.hash(document.getElementById("password").value);
      }
      if (document.getElementById("password-1").value) {
        document.getElementById("password-1").value = Sha256.hash(document.getElementById("password-1").value);
      }
      if (document.getElementById("old_password").value) {
        document.getElementById("old_password").value = Sha256.hash(document.getElementById("old_password").value);
      }
      document.form1.submit
    }
function show(id) {
  var bb = document.getElementById(id);
  if (bb.style.display == "block") {
    bb.style.display = "none";
  } else {
    bb.style.display = "block";
  }
};
function showTable(id) {
  var bb = document.getElementById(id);
  if (bb.style.display == "table") {
    bb.style.display = "none";
  } else {
    bb.style.display = "table";
  }
};
</script>
<?php
if (isset($notify) && $notify && (!array_key_exists('message',$_SESSION) || empty($_SESSION['message'])))
  $_SESSION['message'] = 'Please fill up the highlighted parts';
include('foot.php');
?>
