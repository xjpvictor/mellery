<?php
define('includeauth',true);
include(__DIR__.'/init.php');

if (isset($_GET['fid'])) {
  $folder_id=$_GET['fid'];
  if (isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p'] > 0)
    $p=$_GET['p'];
  else
    $p='1';
} else {
  $folder_id=$box_root_folder_id;
  $p='1';
}

$header_string=boxauth();
$box_cache=boxcache();
$url=getpageurl();
$file_list=getfilelist($folder_id,$limit,$p - 1,$order);
if ($file_list == 'error' || ($p !== '1' && empty($file_list))) {
  header("Status: 404 Not Found");
  include($includes_dir.'404.php');
  exit(0);
}

$access = getaccess($folder_id);
if (!$access) {
  $auth=auth(array($username,'id-'.$folder_id));
  if ($auth !== 'pass') {
    header("HTTP/1.1 401 Unauthorized");
    $redirect_url = $base_url.'access.php?fid='.$folder_id.'&ref='.$url;
    $redirect_message = 'Access restricted';
    include($includes_dir.'redirect.php');
    exit(0);
  }
}

$otp=getotp($expire_image);
$auth_admin = auth($username);

if (!empty($_SESSION) && isset($_SESSION['message']) && !empty($_SESSION['message'])) {
  $session_str = '<div id="delaymessage">'.$_SESSION['message'].'</div><script type="text/javascript">$(document).ready( function(){$("#delaymessage").show("fast");var to=setTimeout("hideDiv()",5000);});function hideDiv(){$("#delaymessage").hide("fast");}</script>';
  $session_message = true;
} else {
  $session_message = false;
}

$sharetable = '<table><tr><td><a href="https://twitter.com/share" class="twitter-share-button"></a></td><td><div class="fb-like" data-send="false" data-layout="button_count" data-width="450" data-show-faces="false"></div></td></tr></table>';

if ($auth_admin !== 'pass') {
  $page_cache=$cache_dir.$folder_id.'-'.$p.'.html';
  if (file_exists($page_cache)) {
    $age = filemtime($page_cache);
    if ($age >= $box_cache && $age >= filemtime($data_dir.'config.php') && $age >= filemtime($access_file) && (!file_exists($data_dir.'my_page.php') || $age >= filemtime($data_dir.'my_page.php'))) {
      $output = file_get_contents($page_cache);
      $output = str_replace(array('#OTP#','#SHARE_TABLE#'), array($otp,$sharetable), $output);
      if (isset($show_viewcount) && $show_viewcount) {
        preg_match_all('/#VIEW_COUNT_CHANGE_(\d+)#/', $output, $matches);
        foreach ($matches[1] as $match) {
          if (file_exists($stat_dir.$match))
            $c = file_get_contents($stat_dir.$match, true);
          else
            $c = '0';
          $output = str_replace('#VIEW_COUNT_CHANGE_'.$match.'#', $c, $output);
        }
      }
      echo $output;
      if ($session_message) {
        echo $session_str;
        $_SESSION['message'] = '';
      }
      echo '</body></html>';
      exit(0);
    }
  }
}

if ($auth_admin && isset($_GET['cover']) && $_GET['cover'] == '0') {
  changeconf(array('cover_id' => ''));
  session_regenerate_id(true);
  header("Location: $base_url");
  exit(0);
} elseif (isset($_GET['cover'])) {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.$base_url;
  $redirect_message = 'Access restricted';
  include($includes_dir.'redirect.php');
  exit(0);
}

ob_start();

if (file_exists($data_dir.'my_page.php'))
  $my_page = include($data_dir.'my_page.php');
include($base_dir.'head.php');
?>

<body>
<div id="main" class="clearfix">
<div id="ss">&nbsp;</div>

<div class="logo<?php echo ($folder_id == $box_root_folder_id && isset($cover_id) && $cover_id ? ' cover-logo" style="background: url(\''.getcontenturl($folder_id).'cover.php?fid='.$folder_id.'&sns='.((isset($theme) && $theme == 2) ? '2' : '1').'&otp=#OTP#'.'\') left top repeat;' : ''); ?>">
<h1><a href="<?php echo $base_url; ?>" title="<?php echo $site_name; ?>"><?php echo $site_name; ?></a></h1>
<p><?php echo $site_description; ?></p>
<?php echo ($auth_admin == 'pass' && $folder_id == $box_root_folder_id && isset($cover_id) && $cover_id ? '<div id="cover-set"><a href="/?cover=0">Remove Cover</a></div>' : ''); ?>
</div>

<div class="wrap clearfix">
<div class="content-wrap">
<div id="content" class="clearfix">

<?php
$folder_count=$file_list['total_count'];
if ($folder_id !== $box_root_folder_id) {
?>
<div id="info">
<div id="parent"><a href="<?php echo $base_url; if ($file_list['parent']['id'] !== $box_root_folder_id) echo 'index.php?fid=',$file_list['parent']['id']; ?>">&lt;&lt;&nbsp;<?php if ($file_list['parent']['id'] !== $box_root_folder_id) echo $file_list['parent']['name']; else echo 'My Albums'; ?></a></div>
<div id="foldername"><?php echo $folder_name; ?> (<?php echo $folder_count; ?> items)</div>
</div>
<?php
}
?>

<?php if ($folder_id !== $box_root_folder_id && !empty($file_list['description'])) { ?>
<div id="description"><?php echo $file_list['description']; ?></div>
<?php } ?>

<?php if ($auth_admin == 'pass') { ?>
<div id="edit"><a href="<?php echo $base_url; ?>admin/folder.php?fid=<?php echo $folder_id; ?>">Edit</a></div>
<?php } ?>

<?php if ($folder_id !== $box_root_folder_id) { ?>
<?php if (isset($show_viewcount) && $show_viewcount) { ?>
<div class="view-count"><script src="<?php echo $base_url; ?>utils/view.php?id=<?php echo $folder_id; ?>&amp;update=#OTP#"></script></div>
<?php } ?>

<div id="sharetop">
<?php if ($access) : ?>
#SHARE_TABLE#
<?php endif; ?>
</div>

<?php } ?>

<div id="content-shadow">
<?php
$style=array('rotateleft1','rotateleft2','rotateleft3','rotateright1','rotateright2','rotateright3');
$time='';
foreach ($file_list['item_collection'] as $key => $entry) {
  $class=$style[rand(0,count($style) - 1)];
  if (isset($entry['type']) && $entry['type'] == 'file') {
    $name = substr($entry['name'], 0, strrpos($entry['name'], '.', -1));
?>
  <div style="z-index:<?php echo rand(1,6); ?>" class="<?php echo $class; ?> container thumbnail tipTip masony" title="<?php echo $name; if (!empty($entry['description'])) echo '<br/><br/>',$entry['description']; ?>">
<?php
    if (isset($theme) && $theme == 2 && $time !== ($time_n = date('M, Y', strtotime($entry['created_at']))))
      echo '<div class="time">'.($time = $time_n).'</div>';
?>
  <a href="<?php echo $base_url; ?>image.php?id=<?php echo $entry['id']; ?>">
    <img src="<?php echo getcontenturl($folder_id); ?>thumbnail.php?id=<?php echo $entry['id']; ?>&amp;otp=#OTP#" class="thumb tipTip" alt="<?php echo $name; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" title="<?php echo $name; ?>" />
<?php if (!isset($theme) || $theme == 1) { ?>
    <span class="thumbtitle"><?php echo cut($name,18); if (isset($show_viewcount) && $show_viewcount) { ?><br/><br/>#VIEW_COUNT_CHANGE_<?php echo $entry['id']; ?># views<?php } ?></span>
<?php } ?>
  </a>
  </div>
<?php
  } elseif (isset($entry['type']) && $entry['type'] == 'folder') {
    if ($auth_admin == 'pass' || $show_private || getaccess($entry['id'])) {
?>
  <div style="z-index:<?php echo rand(1,6); ?>" class="<?php echo $class; ?> container album tipTip masony" title="<?php echo $entry['name']; if (!empty($entry['description'])) echo '<br/><br/>',$entry['description'];?>">
<?php
      if (isset($theme) && $theme == 2 && $time !== ($time_n = date('M, Y', strtotime($entry['created_at']))))
        echo '<div class="time">'.($time = $time_n).'</div>';
?>
  <a href="?fid=<?php echo $entry['id']; ?>">
    <img src="<?php echo getcontenturl($entry['id']); ?>cover.php?fid=<?php echo $entry['id']; ?>&amp;otp=#OTP#" class="thumb tipTip" alt="<?php echo $entry['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" title="<?php echo $entry['name']; ?>" />
<?php if (!isset($theme) || $theme == 1) { ?>
    <span class="albumtitle"><?php echo cut($entry['name'],18); ?><br/><br/><?php echo getcount($entry['id']); ?> items<?php if (isset($show_viewcount) && $show_viewcount) { ?> (#VIEW_COUNT_CHANGE_<?php echo $entry['id']; ?># views)<?php } ?></span>
<?php } ?>
  </a>
  </div>
<?php
    }
  }
}
?>
</div>

<a class="next_page" href="?fid=<?php echo $folder_id; ?>&amp;p=<?php echo $p + 1; ?>"></a>

<noscript><div class="nav">
<?php if ($p > 1) { ?>
<a class="left" href="?fid=<?php echo $folder_id; echo ($p == 2 ? '' : '&amp;p='.($p - 1)); ?>">← Previous page</a>
<?php } ?>
<?php if ($p * $limit < $folder_count) { ?>
<a class="right" href="?fid=<?php echo $folder_id; ?>&amp;p=<?php echo $p + 1; ?>">Next page →</a>
<?php } ?>
</div></noscript>

</div>

<div class="clearfix"></div>

<div id="sharebottom">
<?php
if ($folder_id !== $box_root_folder_id) {
  echo '<span id="meta-border"></span>';
  echo '<p id="meta">Uploaded ',date('d. F Y', strtotime($file_list['created_at'])),' by ',$username,'.';
  if (!($cl = getaccess($folder_id, null, null, 'cl'))) {
    if (!isset($license) || !$license)
      echo ' All rights reserved.';
    elseif ($license == '-1')
      echo ' No right reserved.';
    elseif ($license == '1') {
      $cc_str = 'by';
      if ($nc)
        $cc_str .= '-nc';
      if ($sa == '0')
        $cc_str .= '-nd';
      elseif ($sa == '2')
        $cc_str .= '-sa';
      echo ' <a href="',$cc_url,$cc_str,'/',$cc_ver,'" target="_blank" rel="license">CC ',strtoupper($cc_str),' ',$cc_ver,'</a>.';
    } elseif ($license == '2' && isset($custom_license) && $custom_license) {
      echo ' '.(isset($custom_license_url) && $custom_license_url ? '<a href="'.$custom_license_url.'" target="_blank" rel="license"> ' : '').$custom_license.(isset($custom_license_url) && $custom_license_url ? '</a>' : '').'.';
    }
  } else {
    echo ' '.(($cl_url = getaccess($folder_id, null, null, 'cl_url')) ? '<a href="'.$cl_url.'" target="_blank" rel="license"> ' : '').$cl.($cl_url ? '</a>' : '').'.';
  }
  if ($access) {
    echo '</p><br/><p>#SHARE_TABLE#';
  } else
    echo '<br/>Private album. DO NOT share.</p>';
}
?>
</div></div>

<?php if (!isset($theme) || $theme == 1) { ?>
<div id="sidebar" class="sidebar">

<?php if ($folder_id == $box_root_folder_id) { ?>
<div class="widget-container">
<div id="shareside">
#SHARE_TABLE#
</div>
</div>

<?php } ?>

<?php if (isset($home_page) && !empty($home_page)) { ?>
<div class="widget-container">
<h3 class="widget-title">About me</h3>
<div><a href="<?php echo $home_page; ?>" target="_blank">My Home Page</a></div>
</div>
<?php } ?>

<?php
if ($folder_id == $box_root_folder_id) {
  echo '<div class="widget-container">';
  echo '<h3 class="widget-title">License</h3>';
  echo '<div>';
  if (!isset($license) || !$license)
    echo ' All rights reserved';
  elseif ($license == '-1')
    echo ' No right reserved';
  elseif ($license == '1') {
    $cc_str = 'by';
    if ($nc)
      $cc_str .= '-nc';
    if ($sa == '0')
      $cc_str .= '-nd';
    elseif ($sa == '2')
      $cc_str .= '-sa';
    echo ' <a href="',$cc_url,$cc_str,'/',$cc_ver,'" target="_blank" rel="license">CC ',strtoupper($cc_str),' ',$cc_ver,'</a>';
  } elseif ($license == '2' && isset($custom_license) && $custom_license) {
    echo ' '.(isset($custom_license_url) && $custom_license_url ? '<a href="'.$custom_license_url.'" target="_blank" rel="license"> ' : '').$custom_license.(isset($custom_license_url) && $custom_license_url ? '</a>' : '');
  }
  echo '</div>';
  echo '</div>';
}
?>

<?php if ($folder_id !== $box_root_folder_id) { ?>
<div class="widget-container" id="album-list">
<h3 class="widget-title">Albums</h3>
<div class="content-area">
<div>
<?php
  $folder_l1 = getfilelist($box_root_folder_id);
  foreach ($folder_l1['item_collection'] as $key => $entry) {
    if ($entry['type'] == 'folder') {
      if ($entry !== 'error') {
        if ($auth_admin == 'pass' || getaccess($entry['id'])) {
?>
      <div class="albumlist tipTip" title="<?php echo $entry['name']; if (!empty($entry['description'])) echo '<br/><br/>',$entry['description']; ?>">
      <a href="<?php echo $base_url; ?>?fid=<?php echo $entry['id']; ?>">
        <img src="<?php echo getcontenturl($entry['id']); ?>cover.php?fid=<?php echo $entry['id']; ?>&amp;otp=#OTP#" alt="<?php echo $entry['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" />
        <span class="albumtitle"><?php echo cut($entry['name'],18); ?><br/><br/><?php echo getcount($entry['id']); ?> items<?php if (isset($show_viewcount) && $show_viewcount) { ?> (#VIEW_COUNT_CHANGE_<?php echo $entry['id']; ?># views)<?php } ?></span>
      </a>
      </div>
<?php
        }
      }
    }
  }
?>
</div>
</div>
</div>
<?php } ?>

<?php
if (!isset($my_page) && file_exists($data_dir.'my_page.php')) $my_page = include($data_dir.'my_page.php');
if (isset($my_page) && isset($my_page['widget'])) {
  foreach ($my_page['widget'] as $widget) {
?>
  <div class="widget-container">
  <h3 class="widget-title"><?php echo $widget['title']; ?></h3>
  <?php echo $widget['content']; ?>
  </div>
<?php
  }
}
?>

<div class="widget-container">
<h3 class="widget-title">Meta</h3>
<div>
<div><a href="<?php echo $base_url; ?>admin/">Dashboard</a></div>
<?php if (auth($username) !== 'pass') { ?>
<div><a href="<?php echo $base_url; ?>admin/login.php?ref=<?php echo $url; ?>">Log in</a></div>
<?php } else { ?>
<div><a href="<?php echo $base_url; ?>admin/logout.php?ref=<?php echo $url; ?>">Log out</a></div>
<?php } ?>
</div>
</div>

</div>
<?php } ?>

</div></div>

<div id="footer">
<div id="footer-content">
<?php include($base_dir.'foot.php'); ?>
</div>
</div>

<?php
$output = ob_get_contents();
ob_clean();
if ($auth_admin !== 'pass') {
  file_put_contents($page_cache,$output);
}

$output = str_replace(array('#OTP#','#SHARE_TABLE#'), array($otp,$sharetable), $output);
if (isset($show_viewcount) && $show_viewcount) {
  preg_match_all('/#VIEW_COUNT_CHANGE_(\d+)#/', $output, $matches);
  foreach ($matches[1] as $match) {
    if (file_exists($stat_dir.$match))
      $c = file_get_contents($stat_dir.$match, true);
    else
      $c = '0';
    $output = str_replace('#VIEW_COUNT_CHANGE_'.$match.'#', $c, $output);
  }
}
echo $output;

ob_end_flush();

if ($session_message) {
  echo $session_str;
  $_SESSION['message'] = '';
}
?>

</body></html>
