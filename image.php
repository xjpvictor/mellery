<?php
define('includeauth',true);
define('isimage',true);
include_once("functions.php");
if(!array_key_exists('id',$_GET) || !array_key_exists('fid',$_GET)) {
  header("Status: 404 Not Found");
  include($base_dir."library/404.php");
  exit(0);
}
$id=$_GET['id'];
$folder_id = $_GET['fid'];

$header_string=boxauth();

$ch=curl_init();
curl_setopt($ch, CURLOPT_URL,"https://api.box.com/2.0/files/".$id."/content");
curl_setopt($ch, CURLOPT_HEADER,true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
$data=curl_exec($ch);
curl_close($ch);
preg_match('/Location:\ (\S+)/',$data,$match);
if (empty($match)) {
  header("Status: 404 Not Found");
  include($base_dir."library/404.php");
  exit(0);
}

$box_cache=boxcache();
$folder_list=getfolderlist();
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
  $page_cache=$cache_dir.$folder_id.'-'.$id.'.html';
  if (file_exists($page_cache)) {
    $age = filemtime($page_cache);
    if ($box_cache == 1 && $age >= filemtime($data_dir.'folder.php') && $age >= filemtime($base_dir.'config.php') && $age >= filemtime($data_dir.'my_page.php')) {
      $output = file_get_contents($page_cache);
      $output = preg_replace(array('/#OTP#/', '/#IMGURL#/'), array($otp, $match[1]), $output);
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

$file_list=getfilelist($folder_id,null,null);
if ($file_list == 'error' || !array_key_exists('id-'.$id,$file_list) || !array_key_exists('id-'.$folder_id,$folder_list)) {
  header("Status: 404 Not Found");
  include($base_dir."library/404.php");
  exit(0);
}

ob_start();

$my_page = include($data_dir.'my_page.php');
include($base_dir."head.php");
?>

<body id="body-img">
<div id="main-img">

<div id="content-img">
<div id="imgbox">
<?php
$name=$file_list['id-'.$id]['name'];
$name = substr($name, 0, strrpos($name, '.', -1));
echo '<img id="mainimg-img" src="#IMGURL#" alt="'.$name.'"/><a title="Download original image" target="_blank" href="#IMGURL#"><div id="download">&nbsp;</div></a>';

foreach ($file_list as $key => $value) {
  if ($file_list[$key]['type'] !== 'file')
    unset($file_list[$key]);
}
$description = $file_list['id-'.$id]['description'];
$k=array_search('id-'.$id,array_keys($file_list)) + 1;
$seq_num=3;
$seq=getseq($file_list,'id-'.$id,$seq_num);
if ($seq && count($seq) > 1) {
  $i=0;
  foreach($seq as $item) {
    if ($item['id']==$id) {
      $n = $i;
    }
    $i++;
  }
  if ($n > 0)
    $prev_url=$base_url.'image.php?id='.$seq[$n - 1]['id'].'&amp;fid='.$folder_id;
  if ($n < count($seq) - 1)
    $next_url=$base_url.'image.php?id='.$seq[$n + 1]['id'].'&amp;fid='.$folder_id;
  if (isset($prev_url))
    echo '<a title="Previous" href="'.$prev_url.'"><div id="left">&nbsp;</div></a>';
  if (isset($next_url))
    echo '<a title="Next" href="'.$next_url.'"><div id="right">&nbsp;</div></a>';
}
?>
</div>
</div>

<div id="sidebar-img" class="sidebar"><div id="sidebar-wrap-img">

<div class="widget-container">
<p id="parent"><a href="<?php echo $base_url; ?>?id=<?php echo $folder_id; ?>">&lt;&lt;&nbsp;Back to <?php echo $folder_name; ?></a></p>
</div>

<div class="widget-container">
<div id="shareimg"><table>
<tr>
<td id="view-count" class="view-count"><script src="<?php echo $base_url; ?>stat.php?id=<?php echo $id; ?>&amp;update=#OTP#"></script> Views</td><td></td>
</tr>
<tr>
<td><a href="https://twitter.com/share" class="twitter-share-button">Tweet</a></td>
<td><div class="fb-like" data-send="false" data-layout="button_count" data-width="450" data-show-faces="false"></div></td>
<td><div class="g-plusone" data-size="medium"></div></td>
</tr>
</table></div>
</div>

<div class="widget-container">
<p id="description-img"><?php if (!empty($description)) echo $description; else echo 'No description'; ?></p>
</div>

<div class="widget-container">
<?php
if ($auth_admin == 'pass')
  echo '<div class="edit-img"><a href="'.$base_url.'admin/folder.php?id='.$folder_id.'&amp;p='.floor($k / $admin_folder_limit).'#'.$id.'">Edit</a></div><div class="edit-img"><a href="'.$base_url.'admin">Dashboard</a></div><div class="edit-img right"><a href="'.$base_url.'admin/logout.php?ref='.$url.'">Log out</a></div>';
else
  echo '<div class="edit-img"><a href="'.$base_url.'admin/login.php?ref='.$url.'">Log in</a></div>';
?>
</div>

<?php
if (isset($disqus_shortname) && !empty($disqus_shortname)) {
  echo '<div id="disqus_thread"></div>'."\n";
  echo '<script type="text/javascript">'."\n";
  echo '  /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */'."\n";
  echo '  var disqus_shortname = \''.$disqus_shortname.'\'; // required: replace example with your forum shortname'."\n";
  echo '  var disqus_identifier = \''.$_GET['id'].'\';'."\n";
  echo '  var disqus_title = \''.$name.'\';'."\n";
  echo '  /* * * DON\'T EDIT BELOW THIS LINE * * */'."\n";
  echo '  (function() {'."\n";
  echo '    var dsq = document.createElement(\'script\'); dsq.type = \'text/javascript\'; dsq.async = true;'."\n";
  echo '    dsq.src = \'https://\' + disqus_shortname + \'.disqus.com/embed.js\';'."\n";
  echo '    (document.getElementsByTagName(\'head\')[0] || document.getElementsByTagName(\'body\')[0]).appendChild(dsq);'."\n";
  echo '  })();'."\n";
  echo '</script>'."\n";
  echo '<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>'."\n";
  echo '<a href="http://disqus.com" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>'."\n";
}
?>

</div></div>

<?php
if ($seq && count($seq) > 1) {
  echo '<div id="info-img-nav" class="info-img"><div id="imagename">'.$name.' ('.$k.' of '.count($file_list).' images)</div>';
  $i=0;
  echo '<div id="nav-img">';
  foreach($seq as $item) {
    echo '<a href="'.$base_url.'image.php?id='.$item['id'].'&amp;fid='.$folder_id.'"><img class="item-img" ';
    if ($item['id']==$id) {
      echo 'id="current-img" ';
      $n = $i;
    }
    $i++;
    $name = substr($item['name'], 0, strrpos($item['name'], '.', -1));
    echo 'src="'.$base_url.'thumbnail.php?id='.$item['id'].'-'.$item['sequence_id'].'&amp;fid='.$folder_id.'&amp;w='.$w.'&amp;h='.$h.'&amp;otp=#OTP#" alt="'.$name.'" title="'.$name.'" width="150" height="150" /></a>'."\n";
  }
  echo '</div>';
  echo '<div id="shortcut-img">';
  if (isset($prev_url))
    echo '<div id="prev"><a title="Previous" href="'.$prev_url.'">← Previous</a></div>';
  if (isset($next_url))
    echo '<div id="next"><a title="Next" href="'.$next_url.'">Next →</a></div>';
  echo '</div>';
} else
  echo '<div id="info-img" class="info-img">><div id="imagename">'.$name.'</div>';
echo '<div id="message-img">';
include($base_dir."foot.php");
echo '</div>'."\n";
echo '</div>'."\n";
?>

</div>

<script type="text/javascript"> 
(function(){ 
  var o = document.getElementById("description-img"); 
  var s = o.innerHTML; 
  var p = document.createElement("span"); 
  var n = document.createElement("a"); 
  p.innerHTML = s.substring(0,30); 
  n.innerHTML = s.length > 30 ? "(More)" : ""; 
  n.href = "javascript:;"; 
  n.onclick = function(){ 
    if (n.innerHTML == "(More)"){ 
      n.innerHTML = "(Less)"; 
      p.innerHTML = s; 
    }else{ 
      n.innerHTML = "(More)"; 
      p.innerHTML = s.substring(0,30); 
    } 
  } 
  o.innerHTML = ""; 
  o.appendChild(p); 
  o.appendChild(n); 
})(); 
$(window).load(function(){$(window).resize(function(){
  if($(window).width()>480){
    $('#mainimg-img').css({ 
      position:'absolute', 
      left: ($('#imgbox').outerWidth() - $('#mainimg-img').outerWidth())/2, 
      top: ($('#imgbox').outerHeight() - $('#mainimg-img').outerHeight())/2 + $(document).scrollTop() 
    });
  };
}); 
$(window).resize();});
</script> 

<?php
$output = ob_get_contents();
ob_clean();
if ($auth_admin !== 'pass') {
  file_put_contents($page_cache,$output);
}

$output = preg_replace(array('/#OTP#/', '/#IMGURL#/'), array($otp, $match[1]), $output);
echo $output;

ob_end_flush();

if ($session_message) {
  echo $session_str;
  $_SESSION['message'] = '';
}
?>

</body></html>
