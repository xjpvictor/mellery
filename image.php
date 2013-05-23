<?php
define('includeauth',true);
define('isimage',true);
include_once('./data/config.php');
include_once($base_dir.'functions.php');
if(!array_key_exists('id',$_GET) || !array_key_exists('fid',$_GET)) {
  header("Status: 404 Not Found");
  include($base_dir.'library/404.php');
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
  include($base_dir.'library/404.php');
  exit(0);
}

$box_cache=boxcache();
$folder_list=getfolderlist();
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

$fullscreen_style = '@media screen and (min-width: 480px) {#sidebar-img{display:none;}'."\n".'#content-img{width:100%;}'."\n".'.info-img{width:100%;border-width:1px 0px 0px 0px;}}';

if ($auth_admin !== 'pass') {
  $page_cache=$cache_dir.$folder_id.'-'.$id.'.html';
  if (file_exists($page_cache)) {
    $age = filemtime($page_cache);
    if ($box_cache == 1 && $age >= filemtime($data_dir.'folder.php') && $age >= filemtime($data_dir.'config.php') && (!file_exists($data_dir.'my_page.php') || $age >= filemtime($data_dir.'my_page.php'))) {
      $output = file_get_contents($page_cache);
      $output = str_replace(array('#OTP#', '#IMGURL#'), array($otp, $match[1]), $output);
      if (isset($_SESSION['fullscreen']['id-'.$folder_id]))
        $output = str_replace('#FULLSCREENSTYLE#', $fullscreen_style, $output);
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
  include($base_dir.'library/404.php');
  exit(0);
}

ob_start();

if (file_exists($data_dir.'my_page.php'))
  $my_page = include($data_dir.'my_page.php');
include($base_dir.'head.php');
?>

<body id="body-img">
<div id="main-img">

<div id="content-img">
<div id="imgbox">
<?php
$file_name=$file_list['id-'.$id]['name'];
$sequence_id=$file_list['id-'.$id]['sequence_id'];
$name = substr($file_name, 0, strrpos($file_name, '.', -1));
?>
<img id="mainimg-img" src="#IMGURL#" alt="<?php echo $name; ?>"/>
<a title="Download original image" target="_blank" href="#IMGURL#"><div id="download">&nbsp;</div></a>
<a id="fullscreen-a" title="Fullscreen" href="javascript:;" onclick="togglefull()"><img src="<?php echo $base_url; ?>library/fullscreen.png" alt="fullscreen" id="fullscreen"/></a>

<?php
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

<div id="image-exif"><a class="close" href="javascript:;" onclick="show('image-exif')">[Close]</a><br/>
<?php
$info = getexif($id);
if ($info) {
  $size = $info['size'];
  $fsize = $info['fsize'];
  if (isset($info['exif'])) {
    $exif = $info['exif'];
    if (isset($usemap) && $usemap && isset($exif['GPSLongitude']) && isset($exif['GPSLongitudeRef']) && isset($exif['GPSLatitude']) && isset($exif['GPSLatitude'])) {
      $lng = getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
      $lat = getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
      $coordinates=array($lng,$lat);
      $geometry=array("type" => "Point", "coordinates" => $coordinates);
      $place=array("type" => "Feature", "geometry" => $geometry, "properties" => array("name" => 'Latitude: '.round($lat,1).' | Longitude: '.round($lng,1)));
      echo '<div id="exif-m">';
      $map = true;
    }
  }
  echo '<ul id="exif-ul-1"><li>File name:<span class="right">',$file_name,'</span></li>';
  echo '<li>File size:<span class="right">',$fsize,'</span></li>';
  echo '<li>Type:<span class="right">',$size['mime'],'</span></li>';
  echo '</ul>';
  echo '<ul><li>Dimensions:<span class="right">',$size[0],' x ',$size[1],'</span></li>';
  if (isset($exif) && $exif) {
    if (isset($exif['DateTimeOriginal']))
      echo '<li>Date:<span class="right">',$exif['DateTimeOriginal'],'</span></li>';
    echo '</ul>';
    $str = '';
    if (isset($exif['Make']) && isset($exif['Model']))
      $str .= '<li>Device:<span class="right">'.$exif['Make'].' '.$exif['Model'].'</span></li>';
    if (isset($exif['ExposureTime'])) {
      if (preg_match('/(\d+)\/(\d+)/',$exif['ExposureTime'],$et))
        $str .= '<li>Exposure time:<span class="right">1/'. floor($et[2] / $et[1]) .' s</span></li>';
      elseif (is_numeric($exif['ExposureTime']))
        $str .= '<li>Exposure time:<span class="right">1/'.$exif['ExposureTime'].' s</span></li>';
      else
        $str .= '<li>Exposure time:<span class="right">1/'.$exif['ExposureTime'].'</span></li>';
    }
    if (isset($exif['FNumber'])) {
      if (preg_match('/(\d+)\/(\d+)/',$exif['FNumber'],$fn))
        $str .= '<li>F-Number:<span class="right">F/'. round($fn[1] / $fn[2],1) .'</span></li>';
      elseif (is_numeric($exif['FNumber']))
        $str .= '<li>F-Number:<span class="right">F/'.$exif['FNumber'].'</span></li>';
      else
        $str .= '<li>F-Number:<span class="right">'.$exif['FNumber'].'</span></li>';
    }
    if (isset($exif['FocalLength'])) {
      if (preg_match('/(\d+)\/(\d+)/',$exif['FocalLength'],$fl))
        $str .= '<li>Focal length:<span class="right">'. round($fl[1] / $fl[2],1) .' mm</span></li>';
      elseif (is_numeric($exif['FocalLength']))
        $str .= '<li>Focal length:<span class="right">'.$exif['FocalLength'].' mm</span></li>';
      else
        $str .= '<li>Focal length:<span class="right">'.$exif['FocalLength'].'</span></li>';
    }
    if (isset($exif['ISOSpeedRatings']))
      $str .= '<li>ISO:<span class="right">'.$exif['ISOSpeedRatings'].'</span></li>';
    if (isset($exif['Flash'])) {
      switch($exif['Flash']) {
      case '0':
        $str .= '<li>Flash:<span class="right">No</span></li>';
        break;
      case '1':
        $str .= '<li>Flash:<span class="right">Yes</span></li>';
        break;
      case '5':
        $str .= '<li>Flash:<span class="right">flash fired but strobe return light not detected</span></li>';
        break;
      case '7':
        $str .= '<li>Flash:<span class="right">flash fired and strobe return light detected</span></li>';
        break;
      }
    }
    if (isset($exif['ExposureBiasValue']))
      $str .= '<li>Exposure bias:<span class="right">'.$exif['ExposureBiasValue'].'</span></li>';
    if (isset($exif['WhiteBalance']) && $exif['WhiteBalance'] == '1')
      $str .= '<li>White balance:<span class="right">Manual</span></li>';
    if (isset($exif['WhiteBalance']) && $exif['WhiteBalance'] == '0')
      $str .= '<li>White balance:<span class="right">Auto</span></li>';
    if (isset($exif['MeteringMode']) && $exif['MeteringMode'] == '1')
      $str .= '<li>Metering mode:<span class="right">Manual</span></li>';
    if (isset($exif['MeteringMode']) && $exif['MeteringMode'] == '0')
      $str .= '<li>Metering mode:<span class="right">Auto</span></li>';
    if (isset($exif['ExposureMode']) && $exif['ExposureMode'] == '1')
      $str .= '<li>Exposure mode:<span class="right">Manual</span></li>';
    if (isset($exif['ExposureMode']) && $exif['ExposureMode'] == '0')
      $str .= '<li>Exposure mode:<span class="right">Auto</span></li>';
    if (!empty($str))
      echo '<ul>',$str;
    echo '</ul>';
    if (isset($map) && $map) {
      echo '</div><div id="map"></div>';
    }
  }
}
?>
</div>

<div id="sidebar-img" class="sidebar"><div id="sidebar-wrap-img">

<div class="widget-container">
<p id="parent"><a href="<?php echo $base_url; ?>?id=<?php echo $folder_id; ?>">&lt;&lt;&nbsp;Back to <?php if ($folder_id !== $box_root_folder_id) echo $folder_name; else echo 'homepage'; ?></a></p>
</div>

<div class="widget-container">
<div id="view-count" class="view-count"><script src="<?php echo $base_url; ?>utils/stat.php?id=<?php echo $id; ?>&amp;update=#OTP#"></script>
<span class="right" id="exif"><a href="javascript:;" onclick="show('image-exif')">Image details</a></span>
</div>

<div id="shareimg"><table>
<tr>
<td><a href="https://twitter.com/share" class="twitter-share-button"></a></td>
<td><div class="fb-like" data-send="false" data-layout="button_count" data-width="450" data-show-faces="false"></div></td>
<td><div class="g-plusone" data-size="medium"></div></td>
</tr>
</table></div>
</div>

<div class="widget-container">
<p id="description-img"><?php if (!empty($description)) echo $description; else echo 'No description'; ?></p>
</div>

<div class="widget-container">
<div class="edit-img">
<?php if ($auth_admin == 'pass') { ?>
<a href="<?php echo $base_url; ?>admin/folder.php?id=<?php echo $folder_id; ?>&amp;p=<?php echo floor($k / $admin_folder_limit); ?>#<?php echo $id; ?>">Edit</a></div><div class="edit-img"><a href="<?php echo $base_url; ?>admin">Dashboard</a></div><div class="edit-img right"><a href="<?php echo $base_url; ?>admin/logout.php?ref=<?php echo $url; ?>">Log out</a>
<?php } else { ?>
<a href="<?php echo $base_url; ?>admin/login.php?ref=<?php echo $url; ?>">Log in</a>
<?php } ?>
</div>
</div>

<?php if (isset($disqus_shortname) && !empty($disqus_shortname)) { ?>
<div id="disqus_thread"></div>
<?php } ?>

</div></div>

<?php if ($seq && count($seq) > 1) { ?>
  <div id="info-img-nav" class="info-img"><div id="imagename"><?php echo $name; ?> (<?php echo $k; ?> of <?php echo count($file_list); ?> images)</div>
  <div id="nav-img">
<?php
  $i=0;
  foreach($seq as $item) {
    $i++;
    $name = substr($item['name'], 0, strrpos($item['name'], '.', -1));
?>
  <a href="<?php echo $base_url; ?>image.php?id=<?php echo $item['id']; ?>&amp;fid=<?php echo $folder_id; ?>"><img class="item-img" <?php if ($item['id']==$id) { echo 'id="current-img" '; $n = $i; } ?> src="<?php echo $base_url; ?>thumbnail.php?id=<?php echo $item['id']; ?>&amp;fid=<?php echo $folder_id; ?>&amp;w=<?php echo $w; ?>&amp;h=<?php echo $h; ?>&amp;otp=#OTP#" alt="<?php echo $name; ?>" title="<?php echo $name; ?>" width="150" height="150" /></a>
<?php
  }
?>
  </div>
  <div id="shortcut-img">
<?php
  if (isset($prev_url))
    echo '<div id="prev"><a title="Previous" href="'.$prev_url.'">← Previous</a></div>';
  if (isset($next_url))
    echo '<div id="next"><a title="Next" href="'.$next_url.'">Next →</a></div>';
?>
  </div>
<?php } else { ?>
  <div id="info-img" class="info-img"><div id="imagename"><?php echo $name; ?></div>
<?php } ?>

<div id="message-img">
<?php include($base_dir.'foot.php'); ?>
</div>

</div>

</div>

<?php
if (isset($map) && $map) {
  echo '<link rel="stylesheet" href="',$base_url,'library/map/leaflet.css" /><script src="',$base_url,'library/map/leaflet.js"></script>';
}
?>

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
function full() {
  show('sidebar-img');
  document.getElementById("content-img").style.width = "100%";
  if (document.getElementById("info-img-nav")) {
    document.getElementById("info-img-nav").style.width = "100%";
    document.getElementById("info-img-nav").style.borderWidth = "1px 0px 0px 0px";
  } else {
    document.getElementById("info-img").style.width = "100%";
    document.getElementById("info-img").style.borderWidth = "1px 0px 0px 0px";
  }
}
function small() {
  show('sidebar-img');
  document.getElementById("content-img").style.width = "60%";
  if (document.getElementById("info-img-nav")) {
    document.getElementById("info-img-nav").style.width = "60%";
    document.getElementById("info-img-nav").style.borderWidth = "1px 1px 0px 0px";
  } else {
    document.getElementById("info-img").style.width = "60%";
    document.getElementById("info-img").style.borderWidth = "1px 1px 0px 0px";
  }
}
function togglefull() {
  if ((document.getElementById("sidebar-img").style.display) == "block") {
    full();
    $.get("<?php echo $base_url.'utils/sess-mod.php?fid='.$folder_id.'&option=fullscreen&set=1'; ?>");
  } else {
    small();
    $.get("<?php echo $base_url.'utils/sess-mod.php?fid='.$folder_id.'&option=fullscreen&set=0'; ?>");
  }
  $(window).resize();
}
$(document).ready(function () {$(document).bind('keydown', 'shift+f', function() {togglefull();});});
$(window).load(function(){$(window).resize(function(){
  if($(window).width()>480){
    $('#mainimg-img').css({ 
      position:'absolute', 
      left: ($('#imgbox').outerWidth() - $('#mainimg-img').outerWidth())/2, 
      top: ($('#imgbox').outerHeight() - $('#mainimg-img').outerHeight())/2 + $(document).scrollTop() 
    });
    $('#download').css({ 
      left: ($('#imgbox').outerWidth() - $('#mainimg-img').outerWidth())/2, 
      top: ($('#imgbox').outerHeight() - $('#mainimg-img').outerHeight())/2 + $(document).scrollTop(),
      width: $('#mainimg-img').outerWidth(),
      height: $('#mainimg-img').outerHeight()
    });
  };
}); 
$(window).resize();});
function show(id) {
  if ((document.getElementById(id).style.display) == "block") {
    document.getElementById(id).style.display = "none";
  } else {
    document.getElementById(id).style.display = "block";
  }
<?php if (isset($map) && $map) echo 'if (id == "image-exif") {showmap();}'; ?>
}
<?php if (isset($map) && $map) { ?>
showmap();
function showmap() {
  if (window.innerWidth <= 480 || (document.getElementById("image-exif").style.display) == "block") {
    var mapquestosmUrl='https://{s}-s.mqcdn.com/tiles/1.0.0/osm/{z}/{x}/{y}.png',mapquestsatUrl='https://{s}-s.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.png',subDomains=['otile1','otile2','otile3','otile4'],mapquestAttrib='&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors | &copy; Tiles Courtesy of  <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img style="vertical-align:middle;" src="https://developer.mapquest.com/content/osm/mq_logo.png"> | &copy; Portions Courtesy NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency';
    var osm=L.tileLayer(mapquestosmUrl,{attribution:mapquestAttrib,subdomains:subDomains,maxZoom:18}),sat=L.tileLayer(mapquestsatUrl,{attribution:mapquestAttrib,subdomains:subDomains,maxZoom:11});
    var map=L.map('map',{center: new L.LatLng(<?php echo $lat; ?>,<?php echo $lng; ?>),zoom:14,layers:[sat,osm]});
    var baseMaps={"Satellite View":sat,"Map":osm};
    L.control.layers(baseMaps).addTo(map);
    var geojsonFeature=<?php echo json_encode(array("type" => "FeatureCollection", "features" => array($place))); ?>;
    var geojsonLayer=L.geoJson().addTo(map);
    geojsonLayer.addData(geojsonFeature);
    function onEachFeature(feature,layer){if(feature.properties && feature.properties.name){layer.bindPopup(feature.properties.name);}}
    L.geoJson(geojsonFeature,{onEachFeature:onEachFeature}).addTo(map);
  }
}
<?php } ?>
<?php if (isset($disqus_shortname) && !empty($disqus_shortname)) { ?>
var disqus_shortname = '<?php echo $disqus_shortname; ?>';
var disqus_identifier = '<?php echo $_GET['id']; ?>';
var disqus_title = '<?php echo $name; ?>';
(function () {
  var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
  dsq.src = 'https://' + disqus_shortname + '.disqus.com/embed.js';
  (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
})();
<?php } ?>
</script>

<?php
$output = ob_get_contents();
ob_clean();
if ($auth_admin !== 'pass') {
  file_put_contents($page_cache,$output);
}

$output = str_replace(array('#OTP#', '#IMGURL#'), array($otp, $match[1]), $output);
if (isset($_SESSION['fullscreen']['id-'.$folder_id]))
  $output = str_replace('#FULLSCREENSTYLE#', $fullscreen_style, $output);
echo $output;

ob_end_flush();

if ($session_message) {
  echo $session_str;
  $_SESSION['message'] = '';
}
$tmp_file='/tmp/'.$id;
if (file_exists($tmp_file))
  unlink($tmp_file);
?>

</body></html>
