<?php
define('includeauth',true);
define('isimage',true);
include(__DIR__.'/init.php');

if(!array_key_exists('id',$_GET)) {
  header("Status: 404 Not Found");
  include($includes_dir.'404.php');
  exit(0);
}
$id=$_GET['id'];

$header_string=boxauth();

$box_cache=boxcache();
$url=getpageurl();
$info = getexif($id);
if (!isset($info['parent_id'])) {
  header("Status: 404 Not Found");
  include($includes_dir.'404.php');
  exit(0);
}
$folder_id = $info['parent_id'];
$file_list=getfilelist($folder_id,null,null,$order);
if ($file_list == 'error') {
  header("Status: 404 Not Found");
  include($includes_dir.'404.php');
  exit(0);
}

$ch=curl_init();
curl_setopt($ch, CURLOPT_URL,$box_url."/files/".$id."/content");
curl_setopt($ch, CURLOPT_HEADER,true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
$data=curl_exec($ch);
curl_close($ch);
preg_match('/Location:\ (\S+)/',$data,$match);
if (empty($match)) {
  header("Status: 404 Not Found");
  include($includes_dir.'404.php');
  exit(0);
}

$access = getaccess($folder_id);
if ($folder_id !== $box_root_folder_id && !$access) {
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

if (!empty($_SESSION) && array_key_exists('message',$_SESSION) && !empty($_SESSION['message'])) {
  $session_str = '<div id="delaymessage">'.$_SESSION['message'].'</div><script type="text/javascript">$(document).ready( function(){$("#delaymessage").show("fast");var to=setTimeout("hideDiv()",5000);});function hideDiv(){$("#delaymessage").hide("fast");}</script>';
  $session_message = true;
} else {
  $session_message = false;
}

$sharetable = '<table><tr><td><a href="https://twitter.com/share" class="twitter-share-button"></a></td><td><div class="fb-like" data-send="false" data-layout="button_count" data-width="450" data-show-faces="false"></div></td></tr></table>';

if ($auth_admin !== 'pass') {
  $page_cache=$cache_dir.$folder_id.'-'.$id.'.html';
  if (file_exists($page_cache)) {
    $age = filemtime($page_cache);
    if ($age >= $box_cache && $age >= filemtime($data_dir.'config.php') && (!file_exists($data_dir.'my_page.php') || $age >= filemtime($data_dir.'my_page.php'))) {
      $output = file_get_contents($page_cache);
      $output = str_replace(array('#OTP#', '#IMGURL#', '##sharetable##'), array($otp, $match[1], $sharetable), $output);
      if (isset($_SESSION['fullscreen']['id-'.$folder_id]))
        $output = str_replace(array('#FULLSCREENCLASS#', '#FULLSCREENSIDEBAR#'), array('fullscreen', 'style="display:none;"'), $output);
      else
        $output = str_replace(array('#FULLSCREENCLASS#', '#FULLSCREENSIDEBAR#'), array('', 'style="display:block;"'), $output);
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

$files = $file_list['item_collection'];
$file_name=$files['id-'.$id]['name'];
$sequence_id=$files['id-'.$id]['sequence_id'];
$name = substr($file_name, 0, strrpos($file_name, '.', -1));

ob_start();

if (file_exists($data_dir.'my_page.php'))
  $my_page = include($data_dir.'my_page.php');
include($base_dir.'head.php');
?>

<body id="body-img">
<div id="main-img">
<div id="ss">&nbsp;</div>

<div id="content-img" class="#FULLSCREENCLASS#">
<div id="imgbox">
<img id="mainimg-img" src="#IMGURL#" alt="<?php echo $name; ?>" style="max-width:95%;max-height:95%;"/>
<a title="Download original image" target="_blank" href="#IMGURL#"><div id="download">&nbsp;</div></a>
<a id="fullscreen-a" title="Fullscreen" href="javascript:;" onclick="togglefull()"><img src="<?php echo $cu; ?>content/fullscreen.png<?php if ($cu !== $base_url) echo '?ver=',filemtime($content_dir.'fullscreen.png'); ?>" alt="fullscreen" id="fullscreen"/></a>

<?php
foreach ($files as $key => $value) {
  if ($value['type'] !== 'file')
    unset($files[$key]);
  else
    break;
}
$description = $files['id-'.$id]['description'];
$k=array_search('id-'.$id,array_keys($files)) + 1;
$seq_num=3;
$seq=getseq($files,'id-'.$id,$seq_num);
if ($seq && count($seq) > 1) {
  $i=0;
  foreach($seq as $item) {
    if ($item['id']==$id) {
      $n = $i;
    }
    $i++;
  }
  if ($n > 0)
    $prev_url=$base_url.'image.php?id='.$seq[$n - 1]['id'];
  if ($n < count($seq) - 1)
    $next_url=$base_url.'image.php?id='.$seq[$n + 1]['id'];
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
  echo '<ul id="exif-ul-1"><li>File name:<span>',$file_name,'</span></li>';
  echo '<li>File size:<span>',$fsize,'</span></li>';
  echo '<li>Type:<span>',$size['mime'],'</span></li>';
  echo '</ul>';
  echo '<ul><li>Dimensions:<span>',$size[0],' x ',$size[1],'</span></li>';
  if (isset($exif) && $exif) {
    if (isset($exif['DateTimeOriginal']))
      echo '<li>Date:<span>',$exif['DateTimeOriginal'],'</span></li>';
    echo '</ul>';
    $str = '';
    if (isset($exif['Make']) && isset($exif['Model']))
      $str .= '<li>Device:<span>'.$exif['Make'].' '.$exif['Model'].'</span></li>';
    if (isset($exif['ExposureTime'])) {
      if (preg_match('/(\d+)\/(\d+)/',$exif['ExposureTime'],$et))
        $str .= '<li>Exposure time:<span>1/'. floor($et[2] / $et[1]) .' s</span></li>';
      elseif (is_numeric($exif['ExposureTime']))
        $str .= '<li>Exposure time:<span>1/'.$exif['ExposureTime'].' s</span></li>';
      else
        $str .= '<li>Exposure time:<span>1/'.$exif['ExposureTime'].'</span></li>';
    }
    if (isset($exif['FNumber'])) {
      if (preg_match('/(\d+)\/(\d+)/',$exif['FNumber'],$fn))
        $str .= '<li>F-Number:<span>F/'. round($fn[1] / $fn[2],1) .'</span></li>';
      elseif (is_numeric($exif['FNumber']))
        $str .= '<li>F-Number:<span>F/'.$exif['FNumber'].'</span></li>';
      else
        $str .= '<li>F-Number:<span>'.$exif['FNumber'].'</span></li>';
    }
    if (isset($exif['FocalLength'])) {
      if (preg_match('/(\d+)\/(\d+)/',$exif['FocalLength'],$fl))
        $str .= '<li>Focal length:<span>'. round($fl[1] / $fl[2],1) .' mm</span></li>';
      elseif (is_numeric($exif['FocalLength']))
        $str .= '<li>Focal length:<span>'.$exif['FocalLength'].' mm</span></li>';
      else
        $str .= '<li>Focal length:<span>'.$exif['FocalLength'].'</span></li>';
    }
    if (isset($exif['ISOSpeedRatings']))
      $str .= '<li>ISO:<span>'.$exif['ISOSpeedRatings'].'</span></li>';
    if (isset($exif['Flash'])) {
      switch($exif['Flash']) {
      case '0':
        $str .= '<li>Flash:<span>No</span></li>';
        break;
      case '1':
        $str .= '<li>Flash:<span>Yes</span></li>';
        break;
      case '5':
        $str .= '<li>Flash:<span>flash fired but strobe return light not detected</span></li>';
        break;
      case '7':
        $str .= '<li>Flash:<span>flash fired and strobe return light detected</span></li>';
        break;
      }
    }
    if (isset($exif['ExposureBiasValue']))
      $str .= '<li>Exposure bias:<span>'.$exif['ExposureBiasValue'].'</span></li>';
    if (isset($exif['WhiteBalance']) && $exif['WhiteBalance'] == '1')
      $str .= '<li>White balance:<span>Manual</span></li>';
    if (isset($exif['WhiteBalance']) && $exif['WhiteBalance'] == '0')
      $str .= '<li>White balance:<span>Auto</span></li>';
    if (isset($exif['MeteringMode']) && $exif['MeteringMode'] == '1')
      $str .= '<li>Metering mode:<span>Manual</span></li>';
    if (isset($exif['MeteringMode']) && $exif['MeteringMode'] == '0')
      $str .= '<li>Metering mode:<span>Auto</span></li>';
    if (isset($exif['ExposureMode']) && $exif['ExposureMode'] == '1')
      $str .= '<li>Exposure mode:<span>Manual</span></li>';
    if (isset($exif['ExposureMode']) && $exif['ExposureMode'] == '0')
      $str .= '<li>Exposure mode:<span>Auto</span></li>';
    if (!empty($str))
      echo '<ul>',$str;
    echo '</ul>';
    if (isset($map) && $map) {
      echo '</div><div id="map"></div>';
    }
  }
  echo '<div id="meta-div"><span id="meta-border"></span><p id="meta">Uploaded ',date('d. F Y', strtotime($info['created_at'])),' by ',$username,'.';
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
  if ($folder_id !== $box_root_folder_id && !$access)
    echo '<br/>Private image. DO NOT share.';
  echo '</p>';
  echo '</div>';
}
?>
</div>

<div id="sidebar-img" class="sidebar" #FULLSCREENSIDEBAR#><div id="sidebar-wrap-img">

<div class="widget-container">
<h1 id="logo-img"><a href="<?php echo $base_url; ?>" title="<?php echo $site_name; ?>"><?php echo $site_name; ?></a></h1>
</div>

<div class="widget-container">
<p id="parent"><a href="<?php echo $base_url; if ($folder_id !== $box_root_folder_id) echo '?fid='.$folder_id; ?>">&lt;&lt;&nbsp;Back to <?php if ($folder_id !== $box_root_folder_id) echo $folder_name; else echo 'homepage'; ?></a></p>
</div>

<div class="widget-container">
<div id="view-count" class="view-count">
<?php if (isset($show_viewcount) && $show_viewcount) { ?>
<script src="<?php echo $base_url; ?>utils/view.php?id=<?php echo $id; ?>&amp;update=#OTP#"></script>
<?php } else { ?>
&nbsp;
<?php } ?>
<?php if ($info) { ?>
<span class="right" id="exif"><a href="javascript:;" onclick="show('image-exif')">Image details</a></span>
<?php } ?>
</div>

<?php if ($folder_id == $box_root_folder_id || $access) : ?>
<div id="shareimg">
##sharetable##
</div>
<?php endif; ?>
</div>

<div class="widget-container">
<p id="description-img"><?php if (!empty($description)) echo $description; else echo 'No description'; ?></p>
</div>

<div class="widget-container">
<div class="edit-img">
<?php if ($auth_admin == 'pass') { ?>
<a href="<?php echo $base_url; ?>admin/folder.php?fid=<?php echo $folder_id; ?>&amp;p=<?php echo floor($k / $admin_folder_limit); ?>#<?php echo $id; ?>">Edit</a></div><div class="edit-img"><a href="<?php echo $base_url; ?>admin">Dashboard</a></div><div class="edit-img right"><a href="<?php echo $base_url; ?>admin/logout.php?ref=<?php echo $url; ?>">Log out</a>
<?php } else { ?>
<a href="<?php echo $base_url; ?>admin/login.php?ref=<?php echo $url; ?>">Log in</a>
<?php } ?>
</div>
</div>

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

<?php if (isset($disqus_shortname) && !empty($disqus_shortname)) { ?>
<div id="disqus_thread"></div>
<?php } ?>

</div></div>

<?php if ($seq && count($seq) > 1) { ?>
  <div id="info-img-nav" class="info-img #FULLSCREENCLASS#"><div id="info-img-content"><div id="imagename"><?php echo $name; ?> (<?php echo $k; ?> of <?php echo count($files); ?> images)</div>
  <div id="nav-img">
<?php
  $i=0;
  foreach($seq as $item) {
    $i++;
    $name = substr($item['name'], 0, strrpos($item['name'], '.', -1));
?>
  <a href="<?php echo $base_url; ?>image.php?id=<?php echo $item['id']; ?>"><img class="item-img" <?php if ($item['id']==$id) { echo 'id="current-img" '; $n = $i; } ?> src="<?php echo getcontenturl($folder_id); ?>thumbnail.php?id=<?php echo $item['id']; ?>&amp;otp=#OTP#" alt="<?php echo $name; ?>" title="<?php echo $name; ?>" width="150" height="150" /></a>
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
  <div id="info-img" class="info-img #FULLSCREENCLASS#"><div id="info-img-content"><div id="imagename"><?php echo $name; ?></div>
<?php } ?>

<div id="message-img">
<?php include($base_dir.'foot.php'); ?>
</div>

</div>
</div>

</div>

<?php
if (isset($map) && $map) {
  echo '<link rel="stylesheet" href="',getcontenturl(null),'content/map/leaflet.css" /><script src="',getcontenturl(null),'content/map/leaflet.js"></script>';
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
  $("#content-img").addClass('fullscreen');
  if (document.getElementById("info-img-nav")) {
    $("#info-img-nav").addClass('fullscreen');
  } else {
    $("#info-img").addClass('fullscreen');
  }
}
function small() {
  show('sidebar-img');
  $("#content-img").removeClass('fullscreen');
  if (document.getElementById("info-img-nav")) {
    $("#info-img-nav").removeClass('fullscreen');
  } else {
    $("#info-img").removeClass('fullscreen');
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
if (window.innerWidth > document.getElementById("ss").offsetWidth) {
  $(document).ready(function () {$(document).bind('keyup', 'f11', function() {togglefull();});});
}
<?php if (!$info) {echo '$(window).load(function(){';} ?>$(window).resize(function(){
  if (window.innerWidth > document.getElementById("ss").offsetWidth) {
    var imgboxW = $('#imgbox').outerWidth();
    var imgboxH = $('#imgbox').outerHeight();
  <?php if ($info) { ?>
    var mainimgImgR = <?php echo $size[0]; ?> / <?php echo $size[1]; ?>;
    if ( mainimgImgR >= imgboxW / imgboxH) {
      var mainimgImgW = Math.min(<?php echo $size[0]; ?>,imgboxW * 0.95);
      var mainimgImgH = mainimgImgW / mainimgImgR;
    } else {
      var mainimgImgH = Math.min(<?php echo $size[1]; ?>,imgboxH * 0.95);
      var mainimgImgW = mainimgImgH * mainimgImgR;
    }
  <?php } ?>
    $('#mainimg-img').css({ 
      position:'absolute', 
      left: ($('#imgbox').outerWidth() - <?php if ($info) {echo 'mainimgImgW';} else {echo '$(\'#mainimg-img\').outerWidth()';} ?>)/2, 
      top: ($('#imgbox').outerHeight() - <?php if ($info) {echo 'mainimgImgH';} else {echo '$(\'#mainimg-img\').outerHeight()';} ?>)/2 + $(document).scrollTop() 
    });
    $('#download').css({ 
      left: ($('#imgbox').outerWidth() - <?php if ($info) {echo 'mainimgImgW';} else {echo '$(\'#mainimg-img\').outerWidth()';} ?>)/2, 
      top: ($('#imgbox').outerHeight() - <?php if ($info) {echo 'mainimgImgH';} else {echo '$(\'#mainimg-img\').outerHeight()';} ?>)/2 + $(document).scrollTop(),
      width: <?php if ($info) {echo 'mainimgImgW';} else {echo '$(\'#mainimg-img\').outerWidth()';} ?>,
      height: <?php if ($info) {echo 'mainimgImgH';} else {echo '$(\'#mainimg-img\').outerHeight()';} ?>
    });
  };
}); 
$(window).resize();<?php if (!$info) {echo '});';} ?>
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
  if (window.innerWidth <= document.getElementById("ss").offsetWidth || (document.getElementById("image-exif").style.display) == "block") {
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

$output = str_replace(array('#OTP#', '#IMGURL#', '##sharetable##'), array($otp, $match[1], $sharetable), $output);
if (isset($_SESSION['fullscreen']['id-'.$folder_id]))
  $output = str_replace(array('#FULLSCREENCLASS#', '#FULLSCREENSIDEBAR#'), array('fullscreen', 'style="display:none;"'), $output);
else
  $output = str_replace(array('#FULLSCREENCLASS#', '#FULLSCREENSIDEBAR#'), array('', 'style="display:block;"'), $output);
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
