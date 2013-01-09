<?php
define('includeauth',true);
include_once("functions.php");
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
  if (!$auth || $auth == 'fail') {
    header("HTTP/1.1 401 Unauthorized");
    $redirect_url = $base_url.'access.php?id='.$folder_id.'&ref='.$url;
    $redirect_message = 'Access restricted';
    include($base_dir."library/redirect.php");
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
    if ($box_cache == 1 && $age >= filemtime($data_dir.'folder.php') && $age >= filemtime($base_dir.'config.php')) {
      $output = file_get_contents($page_cache);
      $output = preg_replace('/#OTP#/', $otp, $output);
      preg_match_all('/#VIEW_COUNT_CHANGE_(\d+)#/', $output, $matches);
      foreach ($matches[1] as $match) {
        if (file_exists($data_dir.$match))
          $c = file_get_contents($data_dir.$match, true);
        else
          $c = '0';
        $output = preg_replace('/#VIEW_COUNT_CHANGE_'.$match.'#/', $c, $output);
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
  include($base_dir."library/404.php");
  exit(0);
}

ob_start();

$my_page = include($data_dir.'my_page.php');
include($base_dir.'head.php');
?>

<body>
<div id="main" class="clearfix">

<?php
echo '<div class="logo">'."\n".'<h1><a href="'. $base_url.'" title="'. $site_name.'">'. $site_name.'</a></h1><p>'. $site_description.'</p>'."\n".'</div>'."\n";
?>

<div id="content">
<?php
if ($folder_id !== $box_root_folder_id) {
  if ($folder_list['id-'.$folder_id] !== 'error')
    $folder_count=$folder_list['id-'.$folder_id]['total_count'];
  else
    $folder_count='null';
  if ($folder_list['id-'.$folder_id]['parent']['id'] !== $box_root_folder_id)
    echo '<div id="info"><div id="parent"><a href="'.$base_url.'index.php?id='.$folder_list['id-'.$folder_id]['parent']['id'].'">&lt;&lt;&nbsp;'.$folder_list['id-'.$folder_id]['parent']['name'].'</a></div><div id="foldername">'.$folder_name.' ('.$folder_count.' items)</div></div>'."\n";
  else
    echo '<div id="info"><div id="parent"><a href="'.$base_url.'">&lt;&lt;&nbsp;Home</a></div><div id="foldername">'.$folder_name.' ('.$folder_count.' items)</div></div>'."\n";
}
if ($folder_id !== $box_root_folder_id && !empty($folder_list['id-'.$folder_id]['description']))
  echo '<div id="description">'.$folder_list['id-'.$folder_id]['description'].'</div>'."\n";
echo '<div id="sharetop"><table><tr>'."\n";
if ($folder_id !== $box_root_folder_id)
  echo '<td class="view-count"><script src="'.$base_url.'stat.php?id='.$folder_id.'&amp;update=#OTP#"></script> Views</td>'."\n";
echo '<td>'."\n".'<a href="https://twitter.com/share" class="twitter-share-button">Tweet</a>'."\n".'</td><td>'."\n".'<div class="fb-like" data-send="false" data-layout="button_count" data-width="450" data-show-faces="false"></div>'."\n".'</td>'."\n".'<td><div class="g-plusone" data-size="medium"></div></td>'."\n".'</tr></table></div>'."\n";
if ($folder_id !== $box_root_folder_id && $auth_admin == 'pass')
  echo '<div id="edit"><a href="'.$base_url.'admin/folder.php?id='.$folder_id.'">Edit</a></div>';

$style=array('rotateleft1','rotateleft2','rotateleft3','rotateright1','rotateright2','rotateright3');
foreach ($file_list as $entry) {
  $class=$style[rand(0,count($style) - 1)];
  if (array_key_exists('type',$entry) && $entry['type'] == 'file') {
    $name = substr($entry['name'], 0, strrpos($entry['name'], '.', -1));
    echo '<div style="z-index:'.rand(1,6).'" class="'.$class.' container thumbnail" title="'.$name.'"><a href="'.$base_url.'image.php?id='.$entry['id'].'&amp;fid='.$folder_id.'" title="'.$name.'"><img src="'.$base_url.'thumbnail.php?id='.$entry['id'].'-'.$entry['sequence_id'].'&amp;fid='.$folder_id.'&amp;w='.$w.'&amp;h='.$h.'&amp;otp=#OTP#" alt="'.$name.'" title="'.$name.'" width="'.$w.'" height="'.$h.'" /><span class="thumbtitle">'.$name.'<br/><br/>#VIEW_COUNT_CHANGE_'.$entry['id'].'# views</span></a></div>'."\n";
  } elseif (array_key_exists('type',$entry) && $entry['type'] == 'folder') {
    $folder=$folder_list['id-'.$entry['id']];
    if ($folder !== 'error')
      $count=$folder['total_count'];
    else
      $count='null';
    echo '<div style="z-index:'.rand(1,6).'" class="'.$class.' container album" title="'.$entry['name'].'"><a href="?id='.$entry['id'].'"><img src="'.$base_url.'cover.php?id='.$entry['id'].'-'.$entry['sequence_id'].'&amp;w='.$w.'&amp;h='.$h.'&amp;otp=#OTP#" alt="'.$entry['name'].'" title="'.$entry['name'].'" width="'.$w.'" height="'.$h.'" /><span class="albumtitle">'.$entry['name'].'<br/><br/>'.$count.' items (#VIEW_COUNT_CHANGE_'.$entry['id'].'# views)</span></a></div>'."\n";
  }
}
$np = $p + 1;
echo '<a class="next_page" href="?id='.$folder_id.'&amp;p='.$np.'"></a>'."\n";
?>
</div>

<div id="sidebar" class="sidebar">
<?php
if (isset($home_page) && !empty($home_page))
  echo '<div class="widget-container"><h3 class="widget-title">About me</h3>'."\n".'<div><a href="'.$home_page.'" target="_blank">My Home Page</a></div>'."\n".'</div>';
if ($folder_id !== $box_root_folder_id) {
  echo '<div class="widget-container"><h3 class="widget-title">Albums</h3><div class="content-area"><div>'."\n";
  if (array_key_exists('id-'.$box_root_folder_id, $folder_list))
    unset($folder_list['id-'.$box_root_folder_id]);
  foreach ($folder_list as $folder) {
    if ($folder !== 'error') {
      $count=$folder['total_count'];
      $string='<div class="albumlist"><a href="'.$base_url.'?id='.$folder['id'].'"><img src="'.$base_url.'cover.php?id='.$folder['id'].'-'.$folder['sequence_id'].'&amp;w='.$w.'&amp;h='.$h.'&amp;otp=#OTP#" alt="'.$folder['name'].'" title="'.$folder['name'].'" width="'.$w.'" height="'.$h.'" /><span class="albumtitle">'.$folder['name'].'<br/><br/>'.$count.' images (#VIEW_COUNT_CHANGE_'.$folder['id'].'# views)</span></a></div>'."\n";
      echo $string;
    }
  }
  echo '</div></div></div>'."\n";
}
if (!isset($my_page)) $my_page = include($data_dir.'my_page.php');
foreach ($my_page['widget'] as $widget) {
  echo '<div class="widget-container"><h3 class="widget-title">'.$widget['title'].'</h3>'."\n";
  echo $widget['content']."\n";
  echo '</div>'."\n";
}
echo '<div class="widget-container"><h3 class="widget-title">Admin</h3><div>'."\n";
echo '<div><a href="'.$base_url.'admin/">Dashboard</a></div>';
if (auth($username) !== 'pass') {
  echo '<div><a href="'.$base_url.'admin/login.php?ref='.getpageurl().'">Log in</a></div>';
} else {
  echo '<div><a href="'.$base_url.'admin/logout.php?ref='.getpageurl().'">Log out</a></div>';
}
echo '</div></div>'."\n";
?>
</div>

<div id="sharebottom"><table>
<tr>
<td>
<a href="https://twitter.com/share" class="twitter-share-button">Tweet</a>
</td>
<td>
<div class="fb-like" data-send="false" data-layout="button_count" data-width="450" data-show-faces="false"></div>
</td>
<td>
<div class="g-plusone" data-size="medium"></div>
</td>
</tr>
</table></div>
</div>

<div id="footer">
<div id="footer-content">
<?php
include($base_dir.'foot.php');
?>
</div>
</div>

<?php
$output = ob_get_contents();
ob_clean();
if ($auth_admin !== 'pass') {
  file_put_contents($page_cache,$output);
}

$output = preg_replace('/#OTP#/', $otp, $output);
preg_match_all('/#VIEW_COUNT_CHANGE_(\d+)#/', $output, $matches);
foreach ($matches[1] as $match) {
  if (file_exists($data_dir.$match))
    $c = file_get_contents($data_dir.$match, true);
  else
    $c = '0';
  $output = preg_replace('/#VIEW_COUNT_CHANGE_'.$match.'#/', $c, $output);
}
echo $output;

ob_end_flush();

if ($session_message) {
  echo $session_str;
  $_SESSION['message'] = '';
}
?>

</body></html>
