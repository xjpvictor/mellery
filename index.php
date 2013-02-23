<?php
define('includeauth',true);
include_once('./data/config.php');
include_once('./functions.php');
if (array_key_exists('id',$_GET)) {
  $folder_id=$_GET['id'];
  if (array_key_exists('p',$_GET))
    $p=$_GET['p'];
  else
    $p='0';
} else {
  $folder_id=$box_root_folder_id;
  $p='0';
}

$header_string=boxauth();
$box_cache=boxcache();
$folder_list = getfolderlist();
$url=getpageurl();

if ($folder_id !== $box_root_folder_id && $folder_list['id-'.$folder_id]['access']['public'][0] !== '1') {
  $auth=auth(array($username,'id-'.$folder_id));
  if ($auth !== 'pass') {
    header("HTTP/1.1 401 Unauthorized");
    $redirect_url = $base_url.'access.php?id='.$folder_id.'&ref='.$url;
    $redirect_message = 'Access restricted';
    include($base_dir.'library/redirect.php');
    exit(0);
  }
}

$otp=getkey($expire_image);
$auth_admin = auth($username);

if (!empty($_SESSION) && array_key_exists('message',$_SESSION) && !empty($_SESSION['message'])) {
  $session_str = '<div id="delaymessage">'.$_SESSION['message'].'</div><script type="text/javascript">$(document).ready( function(){$("#delaymessage").show("fast");var to=setTimeout("hideDiv()",5000);});function hideDiv(){$("#delaymessage").hide("fast");}</script>';
  $session_message = true;
} else {
  $session_message = false;
}

if ($auth_admin !== 'pass') {
  $page_cache=$cache_dir.$folder_id.'-'.$p.'.html';
  if (file_exists($page_cache)) {
    $age = filemtime($page_cache);
    if ($box_cache == 1 && $age >= filemtime($data_dir.'folder.php') && $age >= filemtime($data_dir.'config.php') && (!file_exists($data_dir.'my_page.php') || $age >= filemtime($data_dir.'my_page.php'))) {
      $output = file_get_contents($page_cache);
      $output = str_replace('#OTP#', $otp, $output);
      preg_match_all('/#VIEW_COUNT_CHANGE_(\d+)#/', $output, $matches);
      foreach ($matches[1] as $match) {
        if (file_exists($data_dir.$match))
          $c = file_get_contents($data_dir.$match, true);
        else
          $c = '0';
        $output = str_replace('#VIEW_COUNT_CHANGE_'.$match.'#', $c, $output);
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

$file_list=getfilelist($folder_id,$limit,$p);
if (!array_key_exists('id-'.$folder_id,$folder_list) || $file_list == 'error' || ($p !== '0' && empty($file_list))) {
  header("Status: 404 Not Found");
  include($base_dir.'library/404.php');
  exit(0);
}

ob_start();

if (file_exists($data_dir.'my_page.php'))
  $my_page = include($data_dir.'my_page.php');
include($base_dir.'head.php');
?>

<body>
<div id="main" class="clearfix">

<div class="logo">
<h1><a href="<?php echo $base_url; ?>" title="<?php echo $site_name; ?>"><?php echo $site_name; ?></a></h1>
<p><?php echo $site_description; ?></p>
</div>

<div class="wrap clearfix">
<div id="content">

<?php
if ($folder_list['id-'.$folder_id] !== 'error')
  $folder_count=$folder_list['id-'.$folder_id]['total_count'];
else
  $folder_count='0';
if ($folder_id !== $box_root_folder_id) {
?>
<div id="info">
<div id="parent"><a href="<?php echo $base_url; if ($folder_list['id-'.$folder_id]['parent']['id'] !== $box_root_folder_id) echo 'index.php?id=',$folder_list['id-'.$folder_id]['parent']['id']; ?>">&lt;&lt;&nbsp;<?php if ($folder_list['id-'.$folder_id]['parent']['id'] !== $box_root_folder_id) echo $folder_list['id-'.$folder_id]['parent']['name']; else echo 'Home'; ?></a></div>
<div id="foldername"><?php echo $folder_name; ?> (<?php echo $folder_count; ?> items)</div>
</div>
<?php
}
?>

<?php if ($folder_id !== $box_root_folder_id && !empty($folder_list['id-'.$folder_id]['description'])) { ?>
<div id="description"><?php echo $folder_list['id-'.$folder_id]['description']; ?></div>
<?php } ?>

<?php if ($auth_admin == 'pass') { ?>
<div id="edit"><a href="<?php echo $base_url; ?>admin/folder.php?id=<?php echo $folder_id; ?>">Edit</a></div>
<?php } ?>

<?php if ($folder_id !== $box_root_folder_id) { ?>
<div class="view-count"><script src="<?php echo $base_url; ?>stat.php?id=<?php echo $folder_id; ?>&amp;update=#OTP#"></script></div>

<div id="sharetop"><table>
<tr>
<td><a href="https://twitter.com/share" class="twitter-share-button"></a></td>
<td><div class="fb-like" data-send="false" data-layout="button_count" data-width="450" data-show-faces="false"></div></td>
<td><div class="g-plusone" data-size="medium"></div></td>
</tr>
</table></div>

<?php } ?>

<?php
$style=array('rotateleft1','rotateleft2','rotateleft3','rotateright1','rotateright2','rotateright3');
foreach ($file_list as $entry) {
  $class=$style[rand(0,count($style) - 1)];
  if (array_key_exists('type',$entry) && $entry['type'] == 'file') {
    $name = substr($entry['name'], 0, strrpos($entry['name'], '.', -1));
?>
  <div style="z-index:<?php echo rand(1,6); ?>" class="<?php echo $class; ?> container thumbnail tipTip" title="<?php echo $name; if (!empty($entry['description'])) echo '<br/><br/>',$entry['description']; ?>">
  <a href="<?php echo $base_url; ?>image.php?id=<?php echo $entry['id']; ?>&amp;fid=<?php echo $folder_id; ?>">
    <img src="<?php echo $base_url; ?>thumbnail.php?id=<?php echo $entry['id']; ?>-<?php echo $entry['sequence_id']; ?>&amp;fid=<?php echo $folder_id; ?>&amp;w=<?php echo $w; ?>&amp;h=<?php echo $h; ?>&amp;otp=#OTP#" alt="<?php echo $name; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" title="<?php echo $name; ?>" />
    <span class="thumbtitle"><?php echo $name; ?><br/><br/>#VIEW_COUNT_CHANGE_<?php echo $entry['id']; ?># views</span>
  </a>
  </div>
<?php
  } elseif (array_key_exists('type',$entry) && $entry['type'] == 'folder') {
    $folder=$folder_list['id-'.$entry['id']];
    if ($folder !== 'error')
      $count=$folder['total_count'];
    else
      $count='0';
?>
  <div style="z-index:<?php echo rand(1,6); ?>" class="<?php echo $class; ?> container album tipTip" title="<?php echo $entry['name']; if (!empty($entry['description'])) echo '<br/><br/>',$entry['description'];?>">
  <a href="?id=<?php echo $entry['id']; ?>">
    <img src="<?php echo $base_url; ?>cover.php?id=<?php echo $entry['id']; ?>-<?php echo $entry['sequence_id']; ?>&amp;w=<?php echo $w; ?>&amp;h=<?php echo $h; ?>&amp;otp=#OTP#" alt="<?php echo $entry['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" />
    <span class="albumtitle"><?php echo $entry['name']; ?><br/><br/><?php echo $count; ?> items (#VIEW_COUNT_CHANGE_<?php echo $entry['id']; ?># views)</span>
  </a>
  </div>
<?php
  }
}
?>

<?php $np = $p + 1; ?>
<a class="next_page" href="?id=<?php echo $folder_id; ?>&amp;p=<?php echo $np; ?>"></a>

<noscript><div class="nav">
<?php if ($p > 0) { ?>
<a class="left" href="?id=<?php echo $folder_id; ?>&amp;p=<?php echo ($p - 1); ?>">← Previous page</a>
<?php } ?>
<?php if ($np * $limit < $folder_count) { ?>
<a class="right" href="?id=<?php echo $folder_id; ?>&amp;p=<?php echo $np; ?>">Next page →</a>
<?php } ?>
</div></noscript>

</div>

<div id="sidebar" class="sidebar">

<?php if ($folder_id == $box_root_folder_id) { ?>
<div class="widget-container">
<div id="shareside">
<a href="https://twitter.com/share" class="twitter-share-button"></a>
<div class="fb-like" data-send="false" data-layout="button_count" data-width="450" data-show-faces="false"></div>
<div class="g-plusone" data-size="medium"></div>
</div>
</div>

<?php } ?>

<?php if (isset($home_page) && !empty($home_page)) { ?>
<div class="widget-container">
<h3 class="widget-title">About me</h3>
<div><a href="<?php echo $home_page; ?>" target="_blank">My Home Page</a></div>
</div>
<?php } ?>

<?php if ($folder_id !== $box_root_folder_id) { ?>
<div class="widget-container">
<h3 class="widget-title">Albums</h3>
<div class="content-area">
<div>
<?php
  if (array_key_exists('id-'.$box_root_folder_id, $folder_list))
    unset($folder_list['id-'.$box_root_folder_id]);
  foreach ($folder_list as $folder) {
    if ($folder !== 'error') {
      $count=$folder['total_count'];
?>
    <div class="albumlist tipTip" title="<?php echo $folder['name']; if (!empty($folder['description'])) echo '<br/><br/>',$folder['description']; ?>">
    <a href="<?php echo $base_url; ?>?id=<?php echo $folder['id']; ?>">
      <img src="<?php echo $base_url; ?>cover.php?id=<?php echo $folder['id']; ?>-<?php echo $folder['sequence_id']; ?>&amp;w=<?php echo $w; ?>&amp;h=<?php echo $h; ?>&amp;otp=#OTP#" alt="<?php echo $folder['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" />
      <span class="albumtitle"><?php echo $folder['name']; ?><br/><br/><?php echo $count; ?> images (#VIEW_COUNT_CHANGE_<?php echo $folder['id']; ?># views)</span>
    </a>
    </div>
<?php
    }
  }
?>
</div>
</div>
</div>
<?php
}
?>

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
<h3 class="widget-title">Admin</h3>
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

<div id="sharebottom">
<?php
if ($folder_id !== $box_root_folder_id) {
  echo '<p>Embed:</p>';
  echo '<input class="name-conf" value="',htmlentities('<iframe src="'.$base_url.'folder.php?id='.$folder['id'].'&limit=6" width="540" height="480" allowtransparency="true" seamless scrolling="auto" frameborder="0">'.$folder['name'].'</iframe>'),'" onclick="this.select()"><br/><br/>';
}
?>
<table>
<tr>
<td><a href="https://twitter.com/share" class="twitter-share-button"></a></td>
<td><div class="fb-like" data-send="false" data-layout="button_count" data-width="450" data-show-faces="false"></div></td>
<td><div class="g-plusone" data-size="medium"></div></td>
</tr>
</table></div>

</div>
</div>

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

$output = str_replace('#OTP#', $otp, $output);
preg_match_all('/#VIEW_COUNT_CHANGE_(\d+)#/', $output, $matches);
foreach ($matches[1] as $match) {
  if (file_exists($data_dir.$match))
    $c = file_get_contents($data_dir.$match, true);
  else
    $c = '0';
  $output = str_replace('#VIEW_COUNT_CHANGE_'.$match.'#', $c, $output);
}
echo $output;

ob_end_flush();

if ($session_message) {
  echo $session_str;
  $_SESSION['message'] = '';
}
?>

</body></html>
