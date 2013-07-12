<?php
include_once('./data/config.php');
include_once($base_dir.'functions.php');
if(!array_key_exists('fid',$_GET) || !array_key_exists('limit',$_GET)) {
  header("HTTP/1.1 403 Forbidden");
  include($base_dir.'library/403.php');
  exit(0);
}

$folder_id=$_GET['fid'];

$header_string=boxauth();
$box_cache=boxcache();
$folder_list=getfolderlist();

if ($folder_id !== $box_root_folder_id && $folder_list['id-'.$folder_id]['access']['public'][0] !== '1') {
  $auth=auth(array($username,'id-'.$folder_id));
  if ($auth !== 'pass') {
    header("HTTP/1.1 401 Unauthorized");
    $redirect_url = $base_url.'access.php?fid='.$folder_id.'&ref='.$url;
    $redirect_message = 'Access restricted';
    include($base_dir.'library/redirect.php');
    exit(0);
  }
}

$otp=getkey($expire_image);

$page_cache=$cache_dir.$folder_id.'-'.$_GET['limit'].'-embed.html';
if (file_exists($page_cache)) {
  $age = filemtime($page_cache);
  if ($box_cache == 1 && $age >= filemtime($data_dir.'folder.php') && $age >= filemtime($data_dir.'config.php')) {
    $output = file_get_contents($page_cache);
    $output = str_replace('#OTP#', $otp, $output);
    echo $output;
    exit(0);
  }
}

$file_list=getfilelist($folder_id,$_GET['limit'],'0');
if (!array_key_exists('id-'.$folder_id,$folder_list) || $file_list == 'error') {
  header("Status: 404 Not Found");
  include($base_dir.'library/404.php');
  exit(0);
}

ob_start();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title><?php if ($folder_id !== $box_root_folder_id) { $folder_name=$folder_list['id-'.$folder_id]['name']; echo $folder_name,' | '; } echo $site_name; ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="shortcut icon" href="/favicon.ico" />
<style type="text/css" media="all">
<!--
html, body, div, span, p, a {font-family:"Lucida Sans Unicode","Lucida Grande",FreeSans,Arial,Helvetica,sans-serif;}
body{background:rgba(255,255,255,0.8);padding:0px;margin:0px;font-size:16px;line-height:1.0em;}
#main{padding:10px 15px;border:1px solid #999;}
.thumb{margin:5px;border: 1px solid rgba(255,255,255,0.25);border-radius: 3px;box-shadow: 0 0 3px #555;}
.thumb:hover{border: 1px solid rgba(255,255,255,0.75);box-shadow: 0 0 6px #555;}
a{text-decoration:none;color:#32cd32;}
#title a:hover{text-decoration:underline;}
#more a{color:#999;}
#more a:hover{text-decoration:underline;color:#32cd32;}
#footer{text-align:right;color:#999;font-size:12px;}
#footer a{color:#999;}
#footer a:hover{text-decoration:underline;}
-->
</style>
</head>
<body>
<div id="main">
<p id="title">
<a href="<?php echo $base_url,'?fid=',$folder_id; ?>" target="_blank"><?php echo $folder_name; ?></a>
</p>
<?php
foreach ($file_list as $entry) {
  if (array_key_exists('type',$entry) && $entry['type'] == 'file') {
    $name = substr($entry['name'], 0, strrpos($entry['name'], '.', -1));
?>
  <a href="<?php echo $base_url; ?>image.php?id=<?php echo $entry['id']; ?>" target="_blank">
    <img class="thumb" src="<?php echo $base_url; ?>thumbnail.php?id=<?php echo $entry['id']; ?>&amp;w=<?php echo $w; ?>&amp;h=<?php echo $h; ?>&amp;otp=#OTP#" alt="<?php echo $name; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" title="<?php echo $name; ?>" />
  </a>
<?php
  } elseif (array_key_exists('type',$entry) && $entry['type'] == 'folder') {
?>
  <a href="?fid=<?php echo $entry['id']; ?>" target="_blank">
    <img class="thumb" src="<?php echo $base_url; ?>cover.php?fid=<?php echo $entry['id']; ?>&amp;w=<?php echo $w; ?>&amp;h=<?php echo $h; ?>&amp;otp=#OTP#" alt="<?php echo $entry['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" />
  </a>
<?php
  }
}
?>
<p id="more">
<a href="<?php echo $base_url,'?fid=',$folder_id; ?>" target="_blank">More...</a>
</p>
<p id="footer">
Powered by <a href="https://github.com/xjpvictor/mellery" target="_blank">mellery</a> and <a href="https://www.box.com/" target="_blank">box</a>
</p>
</div>
</body>
</html>

<?php
$output = ob_get_contents();
ob_clean();
file_put_contents($page_cache,$output);
$output = str_replace('#OTP#', $otp, $output);
echo $output;
ob_end_flush();
?>
