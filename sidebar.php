<?php
include_once("config.php");
if (!defined('includeauth')) {
  header('Location: '.$base_url);
  exit(0);
}
?>
</div>
<div id="sidebar-secondary">
<ul class="sidebar">
<?php
if (isset($home_page) && !empty($home_page))
  echo '<li class="widget-container"><h3 class="widget-title">About me</h3><ul>'."\n".'<li><a href="'.$home_page.'" target="_blank">My Home Page</a></li>'."\n".'</ul>';
if ($folder_id !== $box_root_folder_id) {
  echo '<li class="widget-container"><h3 class="widget-title">Albums</h3><div class="content-area"><ul>'."\n";
  if (array_key_exists('id-'.$box_root_folder_id, $folder_list))
    unset($folder_list['id-'.$box_root_folder_id]);
  foreach ($folder_list as $folder) {
    if ($folder !== 'error') {
      $count=$folder['total_count'];
      $string='<li class="albumlist"><a href="'.$base_url.'?id='.$folder['id'].'"><img src="'.$base_url.'cover.php?id='.$folder['id'].'-'.$folder['sequence_id'].'&amp;w='.$w.'&amp;h='.$h.'&amp;otp=#OTP#" alt="'.$folder['name'].'" title="'.$folder['name'].'" width="'.$w.'" height="'.$h.'" /><span class="albumtitle">'.$folder['name'].'<br/><br/>'.$count.' images (#VIEW_COUNT_CHANGE_'.$folder['id'].'# views)</span></a></li>'."\n";
      echo $string;
    }
  }
  echo '</ul></div></li>'."\n";
}
if (!isset($my_page)) $my_page = include($data_dir.'my_page.php');
foreach ($my_page['widget'] as $widget) {
  echo '<li class="widget-container"><h3 class="widget-title">'.$widget['title'].'</h3>'."\n";
  echo $widget['content']."\n";
  echo '</li>'."\n";
}
echo '<li class="widget-container"><h3 class="widget-title">Admin</h3><ul>'."\n";
echo '<li><a href="'.$base_url.'admin/">Dashboard</a></li>';
if (auth($username) !== 'pass') {
  echo '<li><a href="'.$base_url.'admin/login.php?ref='.getpageurl().'">Log in</a></li>';
} else {
  echo '<li><a href="'.$base_url.'admin/logout.php?ref='.getpageurl().'">Log out</a></li>';
}
echo '</ul></li>'."\n";
?>
</ul>
</div>
<div id="sharebottom"><table>
<tr>
<td>
<a href="https://twitter.com/share" class="twitter-share-button">Tweet</a>
</td>
<td>
<div class="fb-like" data-send="false" data-layout="button_count" data-width="450" data-show-faces="false"></div>
</td>
</tr>
</table></div>
</div>
</div>
