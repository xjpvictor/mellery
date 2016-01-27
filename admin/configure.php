<?php
define('includeauth',true);
include('../init.php');

header('X-Robots-Tag: noindex,nofollow,noarchive');

$auth=auth($username);
$url=getpageurl();
if ($auth !== 'pass') {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.$url;
  $redirect_message = 'Login required';
  include($includes_dir.'redirect.php');
  exit(0);
}

$header_string=boxauth();

if (!empty($_POST)) {
  if (isset($_POST['otp']) && verifyotp($_POST['otp'],30,'',0)) {
    changeconf(array('otp_auth' => '1'));
  } else {
    $config_key = include($base_dir.'admin/config_key.php');
    $config_file = $data_dir.'config.php';
    file_put_contents($config_file, '<?php'."\n", LOCK_EX);
    chmod($config_file, 0600);
    $_SESSION['message'] = '';

    $old_password_auth = true;
    foreach ($config_key['mandatory'] as $key) {
      if (array_key_exists($key,$_POST) && ($_POST[$key] == '0' || !empty($_POST[$key])) && $_POST[$key] !== $$key) {
        if ($key == 'password') {
          if ($old_password_auth && ((array_key_exists('old_password',$_POST) && password_verify($_POST['old_password'], $password)) || !isset($password) || !$password)) {
            if ($_POST[$key] == $_POST[$key.'_1'])
              $$key = password_hash($_POST[$key], PASSWORD_DEFAULT, ['cost' => $hash_cost]);
            else {
              $_SESSION['message'] = 'Password not matched<br/>';
            }
          } else {
            $old_password_auth = false;
            $_SESSION['message'] = 'Wrong password<br/>';
          }
        } elseif ($key == 'otp_auth') {
          if ($old_password_auth && array_key_exists('old_password',$_POST) && password_verify($_POST['old_password'], $password)) {
            if ($_POST[$key] == '1') {
              $secret_key = generaterandomstring(16);
              $otp_recovery_code = generaterandomstring(8);
              $_SESSION['showotpqr'] = true;
              $_SESSION['otp_recovery_code'] = $otp_recovery_code;
            } else
              $$key = $_POST[$key];
          } else {
            $old_password_auth = false;
            $_SESSION['message'] = 'Wrong password<br/>';
          }
        } else
          $$key = $_POST[$key];
        if ($key == 'client_id' || $key == 'client_secret') {
          $_SESSION['message'] = 'Please re-authenticate with box.com<br/>';
        }
        if (!isset($$key) || ($$key !== '0' && empty($$key))) {
          $notify = true;
        }
      }
      file_put_contents($config_file, '$'.$key.' = '.var_export($$key, TRUE).';'."\n", FILE_APPEND | LOCK_EX);
    }
    foreach ($config_key['optional'] as $key => $value) {
      if (array_key_exists($key,$_POST) && ($_POST[$key] == '0' || !empty($_POST[$key])) && $_POST[$key] !== $$key) {
        $$key = $_POST[$key];
      }
      if ($key == 'cdn_url' && substr($$key, -1) !== '/') {
        $$key .= '/';
      }
      file_put_contents($config_file, '$'.$key.' = '.var_export($$key, TRUE).';'."\n", FILE_APPEND | LOCK_EX);
    }
    foreach ($config_key['preset'] as $key => $value) {
      if (array_key_exists($key,$_POST) && ($_POST[$key] == '0' || !empty($_POST[$key])) && $_POST[$key] !== $$key) {
        $$key = $_POST[$key];
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
      file_put_contents($config_file, '$'.$key.' = '.var_export($$key, TRUE).';'."\n", FILE_APPEND | LOCK_EX);
    }
  }


  if (isset($notify) && $notify)
    $_SESSION['message'] = $_SESSION['message'].'Please fill up the highlighted parts';
  else
    $_SESSION['message'] = $_SESSION['message'].'Configuration saved';
  $_SESSION[$username] = hash($hash_algro,$username);
  session_regenerate_id(true);
  header("Location: ".$base_url."admin/configure.php".(isset($_SESSION['showotpqr']) && $_SESSION['showotpqr'] ? '#otp' : ''));
  exit(0);
}

ob_start();
if (file_exists($data_dir.'my_page.php'))
  $my_page = include($data_dir.'my_page.php');
include('head.php');

$display = array();
$i = 0;
?>

<form action="configure.php" name="form1" method="POST">
<div class="site-config clearfix">
<?php $display[] = 'none'; ?>
<p class="config-title" onclick="toggleShow('conf-basic');">Basic</p>
<div id="conf-basic" style="display:#DISPLAY<?php echo $i; ?>#;">
<table>
<tr><td><p<?php if (!isset($site_name) || ($site_name !== '0' && empty($site_name))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Site name:</p></td><td><input required name="site_name" value="<?php if (isset($site_name)) echo $site_name; ?>"></td></tr>
<tr><td><p>Site description (Optional):</p></td><td><input name="site_description" value="<?php if (isset($site_description)) echo $site_description; ?>"></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<?php $i++; $display[] = 'none'; ?>
<p class="config-title" onclick="toggleShow('conf-appearance');">Appearance</p>
<div id="conf-appearance" style="display:#DISPLAY<?php echo $i; ?>#;">
<table>
<tr><td><p>Theme:</p></td><td><label><input class="radio" type="radio" name="theme" value="1"<?php if (isset($theme) && $theme == 1) echo ' checked'; ?>>Album and photo</label><br/><label><input class="radio" type="radio" name="theme" value="2"<?php if (isset($theme) && $theme == 2) echo ' checked'; ?>>Timeline</label</td></tr>
<tr><td><p<?php if (!isset($limit) || ($limit !== '0' && empty($limit))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Thumbnails shown per page:</p></td><td><input required name="limit" value="<?php if (isset($limit)) echo htmlentities($limit); ?>"></td></tr>
<tr><td><p>Order of images:</p></td><td><label><input class="radio" type="radio" name="order" value="1"<?php if (isset($order) && $order == 1) echo ' checked'; ?>>Name Ascending</label><br/><label><input class="radio" type="radio" name="order" value="2"<?php if (isset($order) && $order == 2) echo ' checked'; ?>>Time Descending</label</td></tr>
<tr><td><p>Show private albums:</p></td><td><input type="hidden" name="show_private" value="0"><input class="checkbox" type="checkbox" name="show_private" value="1"<?php if (isset($show_private) && $show_private == '1') echo " checked"; ?>></td></tr>
<tr><td><p>Show view count:</p></td><td><input type="hidden" name="show_viewcount" value="0"><input class="checkbox" type="checkbox" name="show_viewcount" value="1"<?php if (isset($show_viewcount) && $show_viewcount == '1') echo " checked"; ?>></td></tr>
<tr><td><p<?php if (!isset($usemap) || ($usemap !== '0' && empty($usemap))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Show map if geolocation is available:</p></td><td><input type="hidden" name="usemap" value="0"><input class="checkbox" type="checkbox" name="usemap" value="1"<?php if (isset($usemap) && $usemap == '1') echo " checked"; ?>></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<?php $i++; $display[] = 'none'; ?>
<p class="config-title" onclick="toggleShow('conf-comm');">Comments</p>
<div id="conf-comm" style="display:#DISPLAY<?php echo $i; ?>#;">
<table>
<tr><td><p>Disqus shortname (Optional):</p></td><td><input name="disqus_shortname" value="<?php if (isset($disqus_shortname)) echo htmlentities($disqus_shortname); ?>"></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<?php $i++; $display[] = 'none'; ?>
<p class="config-title" onclick="toggleShow('conf-privacy');">Privacy</p>
<div id="conf-privacy" style="display:#DISPLAY<?php echo $i; ?>#;">
<table>
<tr><td><p<?php if (!isset($default_public) || ($default_public !== '0' && empty($default_public))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Default album access:</p></td><td><label><input class="radio" type="radio" name="default_public" value="0"<?php if (isset($default_public) && $default_public == '0') echo " checked"; ?>>Private</label><br/><label><input class="radio" type="radio" name="default_public" value="8"<?php if (isset($default_public) && $default_public == '8') echo " checked"; ?>>Public</label><br/><label><input class="radio" type="radio" name="default_public" value="4"<?php if (isset($default_public) && $default_public == '4') echo " checked"; ?>>With general access code</label></td></tr>
<tr><td><p<?php if (!isset($general_access_code) || ($general_access_code !== '0' && empty($general_access_code))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>General album access code (Leave blank if not changed):</p></td><td><input type="password" name="general_access_code"></td></tr>
<tr><td></td><td><p class="button" style="width:200px;"><a href="<?php echo $base_url; ?>admin/folder.php?code=4" target="_blank">Show current access code</a></p></td></tr>
<tr><td><p<?php if (!isset($expire_access_code) || ($expire_access_code !== '0' && empty($expire_access_code))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Default temporary access validity (hours):</p></td><td><input required name="expire_access_code" value="<?php if (isset($expire_access_code)) echo htmlentities($expire_access_code); ?>"></td></tr>
<tr><td><p<?php if (!isset($allow_se) || ($allow_se !== '0' && empty($allow_se))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Allow search engine to index:</p></td><td><input type="hidden" name="allow_se" value="0"><input class="checkbox" type="checkbox" name="allow_se" value="1"<?php if (isset($allow_se) && $allow_se == '1') echo " checked"; ?>></td></tr>
<tr><td></td><td><p class="small">* Depending on the support for robots.txt by search engines</p></td></tr>
<tr><td><p>License:</p></td><td><label><input class="radio" type="radio" name="license" value="0"<?php if (!isset($license) || !$license) echo " checked"; ?> onclick="hide('cc');hide('cl');">All rights reserved</label></td></tr>
<tr><td></td><td><label><input class="radio" type="radio" name="license" value="1"<?php if (isset($license) && $license == '1') echo " checked"; ?> onclick="showTable('cc');hide('cl');">Creative Common</label></td></tr>
</table>
<table id="cc" <?php echo 'style="display:'.(isset($license) && $license == '1' ? 'table' : 'none').'"'; ?>>
<tr><td></td><td><label><input class="radio" type="radio" name="sa" value="0" <?php if (isset($sa) && $sa == '0') echo " checked"; ?> />Disallow Modification</label><br/><label><input class="radio" type="radio" name="sa" value="1" <?php if (isset($sa) && $sa == '1') echo " checked"; ?> />Allow Modification</label><br/><label><input class="radio" type="radio" name="sa" value="2" <?php if (isset($sa) && $sa == '2') echo " checked"; ?> />Allow Modification only if shared alike</label><br/><input type="hidden" name="nc" value="0" /><label><input class="checkbox" type="checkbox" name="nc" value="1" <?php if (isset($nc) && $nc == '1') echo " checked"; ?> />No Commercial uses</label></td></tr>
</table>
<table>
<tr><td></td><td><label><input class="radio" type="radio" name="license" value="-1"<?php if (isset($license) && $license == '-1') echo " checked"; ?> onclick="hide('cc');hide('cl');">No rights reserved</label></td></tr>
<tr><td></td><td><label><input class="radio" type="radio" name="license" value="2"<?php if (isset($license) && $license == '2') echo " checked"; ?> onclick="showTable('cl');hide('cc');">Other license</label></td></tr>
</table>
<table id="cl" class="cl" <?php echo 'style="display:'.(isset($license) && $license == '2' ? 'table' : 'none').'"'; ?>>
<tr><td></td><td>Name:<br/><input name="custom_license" <?php echo (isset($custom_license) ? 'value='.htmlentities($custom_license) : ''); ?>><br/>Link:<br/><input name="custom_license_url" <?php echo (isset($custom_license_url) ? 'value='.htmlentities($custom_license_url) : ''); ?>></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<?php $i++; $display[] = 'none'; ?>
<p class="config-title" onclick="toggleShow('conf-sec');">Security</p>
<div id="conf-sec" style="display:#DISPLAY<?php echo $i; ?>#;">
<table>
<tr><td><p<?php if (!isset($embed_max) || ($embed_max !== '0' && empty($embed_max))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Maximum number of images in embeded iframe (0 for unlimited):</p></td><td><input required name="embed_max" value="<?php if (isset($embed_max)) echo htmlentities($embed_max); ?>"></td></tr>
<tr><td><p<?php if (!isset($expire_image) || ($expire_image !== '0' && empty($expire_image))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Thumbnail expiration time (s, 0 for not expiring):</p></td><td><input required name="expire_image" value="<?php if (isset($expire_image)) echo htmlentities($expire_image); ?>"></td></tr>
<tr><td><p<?php if (!isset($expire_session) || ($expire_session !== '0' && empty($expire_session))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Session expiration time (s):</p></td><td><input required name="expire_session" value="<?php if (isset($expire_session)) echo htmlentities($expire_session); ?>"></td></tr>
<tr><td><p<?php if (!isset($https) || ($https !== '0' && empty($https))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Use cookie on HTTPS only:</p></td><td><input type="hidden" name="https" value="0"><input class="checkbox" type="checkbox" name="https" value="1"<?php if (isset($https) && $https == '1') echo " checked"; ?>></td></tr>
<tr><td><p<?php if (!isset($retry) || ($retry !== '0' && empty($retry))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Maximum access retry in one minutes (0 for unlimited):</p></td><td><input required name="retry" value="<?php if (isset($retry)) echo htmlentities($retry); ?>"></td></tr>
<tr><td><p<?php if (!isset($lock_timeout) || ($lock_timeout !== '0' && empty($lock_timeout))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Exessive access retry lock down period (s, 0 for always locked):</p></td><td><input required name="lock_timeout" value="<?php if (isset($lock_timeout)) echo htmlentities($lock_timeout); ?>"></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<?php $i++; $display[] = 'none'; ?>
<p class="config-title" onclick="toggleShow('conf-perf');">Performance</p>
<div id="conf-perf" style="display:#DISPLAY<?php echo $i; ?>#;">
<table>
<tr><td><p<?php if (!isset($cache_expire) || ($cache_expire !== '0' && empty($cache_expire))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Cache expiration time (days):</p></td><td><input required name="cache_expire" value="<?php if (isset($cache_expire)) echo htmlentities($cache_expire); ?>"></td></tr>
<tr><td><p<?php if (!isset($cache_clean) || ($cache_clean !== '0' && empty($cache_clean))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Expired cache clean frequency (days, 0 for manually cleaning):</p></td><td><input required name="cache_clean" value="<?php if (isset($cache_clean)) echo htmlentities($cache_clean); ?>"></td></tr>
<tr><td></td><td><p class="button" style="width:200px;"><a href="<?php echo $base_url; ?>utils/cache.php?option=all&amp;ref=<?php echo $url; ?>">Clean all cache files now</a></p></td></tr>
<tr><td><p<?php if (!isset($use_cdn) || ($use_cdn !== '0' && empty($use_cdn))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Use CDN:</p></td><td><input type="hidden" name="use_cdn" value="0"><input class="checkbox" type="checkbox" name="use_cdn" value="1"<?php if (isset($use_cdn) && $use_cdn == '1') echo " checked"; ?> onclick="toggleShowTable('cdn');"></td></tr>
</table>
<table id="cdn" <?php echo (isset($use_cdn) && $use_cdn == '1' ? 'style="display:table;"' : 'style="display:none;"'); ?>>
<tr><td><p>CDN url:</p></td><td><input name="cdn_url" value="<?php if (isset($cdn_url)) echo htmlentities($cdn_url); ?>"></td></tr>
<tr><td><p<?php if (!isset($thumb_cdn) || ($thumb_cdn !== '0' && empty($thumb_cdn))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Use CDN for thumbnails:</p></td><td><input type="hidden" name="thumb_cdn" value="0"><input class="checkbox" type="checkbox" name="thumb_cdn" value="1"<?php if (isset($thumb_cdn) && $thumb_cdn == '1') echo " checked"; ?>></td></tr>
</table>
</div>
</div>

<div class="site-config clearfix">
<?php $i++; $display[] = 'none'; ?>
<p class="config-title" onclick="toggleShow('conf-user');">User</p>
<div id="conf-user" style="display:<?php echo (isset($_SESSION['showotpqr']) && $_SESSION['showotpqr'] ? 'block' : '#DISPLAY'.$i.'#'); ?>;">
<table>
<tr><td><p<?php if (!isset($username) || ($username !== '0' && empty($username))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Username:</p></td><td><input required name="username" value="<?php if (isset($username)) echo htmlentities($username); ?>"></td></tr>
<tr><td><p<?php if (!isset($email) || ($email !== '0' && empty($email))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Email:</p></td><td><input required name="email" value="<?php if (isset($email)) echo htmlentities($email); ?>"></td></tr>
<tr><td><p>Your homepage (Optional):</p></td><td><input name="home_page" value="<?php if (isset($home_page)) echo htmlentities($home_page); ?>"></td></tr>
</table>
<?php if (isset($password) && $password) { ?>
<div id="password-verify">
<p>Please verify your current password before you modify the following items:</p>
<input id="old_password" type="password" name="old_password" oninput="if(this.value){document.getElementById('password').disabled=false;document.getElementById('password-1').disabled=false;document.getElementById('otp').disabled=false;}else{document.getElementById('password').disabled=true;document.getElementById('password-1').disabled=true;document.getElementById('otp').disabled=true;}">
</div>
<?php } ?>
<table>
<tr><td><p<?php if (!isset($password) || !$password) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>New password:</p></td><td><input id="password" type="password" name="password" <?php echo (isset($password) && $password ? 'disabled' : ''); ?>></td></tr>
<tr><td><p>Re-type new password:</p></td><td><input id="password-1" type="password" name="password_1" <?php echo (isset($password) && $password ? 'disabled' : ''); ?>></td></tr>
<tr><td><p<?php if (!isset($otp_auth) || ($otp_auth !== '0' && empty($otp_auth))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Use 2-Step Authentication:</p></td><td><input type="hidden" name="otp_auth" value="0"><input id="otp" class="checkbox" type="checkbox" name="otp_auth" value="1"<?php if (isset($otp_auth) && $otp_auth == '1') echo " checked"; ?> disabled></td></tr>
<?php if (!empty($_SESSION) && array_key_exists('showotpqr', $_SESSION) && $_SESSION['showotpqr']) { ?>
<tr><td style="vertical-align:top;"><p>Scan the QR code with any Authenticator (This will only be shown once):</p></td><td><div id="qrcode"></div></td></tr>
<tr><td><p>Please verify the code generated by the authenticator:</p></td><td><input name="otp"></td></tr>
<tr><td style="vertical-align:top;"><p>One time recovery code (This will only be shown once):</p></td><td><?php echo $_SESSION['otp_recovery_code']; ?></td></tr>
<?php } ?>
</table>
</div>
</div>

<div class="site-config clearfix">
<?php $i++; $display[] = 'none'; ?>
<p class="config-title" onclick="toggleShow('conf-box');">Box.com Configuration</p>
<div id="conf-box" style="display:#DISPLAY<?php echo $i; ?>#;">
<table>
<tr><td><p<?php if (!isset($client_id) || ($client_id !== '0' && empty($client_id))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Client ID (Leave blank if not changed):</p></td><td><input name="client_id"></td></tr>
<tr><td><p<?php if (!isset($client_secret) || ($client_secret !== '0' && empty($client_secret))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Client secret (Leave blank if not changed):</p></td><td><input name="client_secret"></td></tr>
<tr><td><p<?php if (!isset($box_root_folder_id) || ($box_root_folder_id !== '0' && empty($box_root_folder_id))) {echo ' class="notset"'; $notify = true; $display[$i] = 'block';} ?>>Location of albums:</p></td><td><div class="dropdown"><select required name="box_root_folder_id">
<?php
$list = getfilelist('0');
echo '<option value="0"'.(!isset($box_root_folder_id) || $box_root_folder_id == 0 ? ' selected' : '').'>Box.com</option>';
foreach ($list['item_collection'] as $item) {
  echo '<option value="'.$item['id'].'"'.(isset($box_root_folder_id) && $box_root_folder_id == $item['id'] ? ' selected' : '').'>'.htmlentities($item['name']).(isset($item['description']) && $item['description'] ? ' - '.htmlentities($item['description']) : '').'</option>';
}
?>
</select></div></td></tr>
<tr><td></td><td><p class="button" style="width:250px;"><a href="<?php echo $base_url; ?>admin/authbox.php" target="_blank">Authenticate with Box.com</a></p></td><td></td></tr>
</table>
</div>
</div>

<div style="width:80px;padding-top:20px;margin:0px auto 0px auto;">
<input class="button" style="width:80px;" type="submit" value="Save" >
</div>
</form>
</div>
<script type="text/javascript">
function hide(id) {
  var bb = document.getElementById(id);
  bb.style.display = "none";
};
function toggleShow(id) {
  var bb = document.getElementById(id);
  if (bb.style.display == "block") {
    bb.style.display = "none";
  } else {
    bb.style.display = "block";
  }
};
function showTable(id) {
  var bb = document.getElementById(id);
  bb.style.display = "table";
};
function toggleShowTable(id) {
  var bb = document.getElementById(id);
  if (bb.style.display == "table") {
    bb.style.display = "none";
  } else {
    bb.style.display = "table";
  }
};
</script>
<?php if (!empty($_SESSION) && array_key_exists('showotpqr', $_SESSION) && $_SESSION['showotpqr']) { ?>
<script src="<?php echo $base_url; ?>content/qrcode.js"></script>
<script>new QRCode(document.getElementById("qrcode"),{text:"otpauth://totp/<?php echo rawurlencode($username); ?>?secret=<?php echo $secret_key; echo ($site_name ? '&issuer='.rawurlencode($site_name) : ''); ?>",width:200,height:200});</script>
<?php
  $_SESSION['showotpqr'] = false;
  $_SESSION['otp_recovery_code'] = '';
}
if (isset($notify) && $notify && (!array_key_exists('message',$_SESSION) || empty($_SESSION['message'])))
  $_SESSION['message'] = 'Please fill up the highlighted parts';
include('foot.php');

$output = ob_get_contents();
ob_clean();

if (!isset($notify) || !$notify)
  $display[0] = 'block';
$output = str_replace(array_map(function($n) {return '#DISPLAY'.$n.'#';}, range(0, ++$i)), $display, $output);

echo $output;

ob_end_flush();
?>
