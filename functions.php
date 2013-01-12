<?php
function boxauth(){
  global $client_id, $client_secret, $box_token_file, $email, $site_name, $username, $base_url;
  if (!file_exists($box_token_file))
    return(false);
  $config=include($box_token_file);
  $expires=$config['expires'];
  $now=time();
  if (!isset($config) || !is_array($config) || empty($config))
    return(false);
  if ($now - $expires >= 3500) {
    $refresh_token=$config['refresh_token'];
    $request_parameter=array(
      'refresh_token' => $refresh_token,
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'grant_type' => 'refresh_token'
    );
    $request_url='https://api.box.com/oauth2/token';
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL,$request_url);
    curl_setopt($ch, CURLOPT_HEADER,false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_POST,true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$request_parameter);
    $response=json_decode(curl_exec($ch),true);
    curl_close($ch);
    if (!isset($response) || !is_array($response) || empty($response) || !array_key_exists('access_token',$response) || !array_key_exists('refresh_token',$response)) {
      return(false);
    } elseif (array_key_exists('error', $response)) {
      if (array_key_exists('error_description', $response) && $response['error_description'] == 'unauthorized_client') {
        file_put_contents($box_token_file, "<?php return 'error' ; ?>", LOCK_EX);
        mail($email,$site_name.' reauthentication needed',wordwrap('Hi '.$username.",<br/><br/>\r\n".'You are receiving this email from '.$site_name." because an error has been detected while trying to authenticate with box.com<br/>\r\nYou'll need to manually reauthenticate with box.com via this url<br/><br/>\r\n".'<a href="'.$base_url.'admin/authbox.php" target="_blank">'.$base_url."admin/authbox.php</a>\r\n", 70, "\r\n"),"MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n");
      }
      return(false);
    } else {
      $access_token=$response['access_token'];
      $config['access_token']=$response['access_token'];
      $config['refresh_token']=$response['refresh_token'];
      $config['expires']=time();
      $config=var_export($config, true);
      file_put_contents($box_token_file, "<?php return $config ; ?>", LOCK_EX);
      $header_string='Authorization: Bearer '.$access_token;
      return($header_string);
    }
  } else {
    $access_token=$config['access_token'];
    $header_string='Authorization: Bearer '.$access_token;
    return($header_string);
  }
}
function boxcache() {
  global $header_string,$box_token_file;
  if (file_exists($box_token_file)) {
    $config=include($box_token_file);
    if (!is_array($config))
      return(1);
    if (isset($config['stream_position']))
      $stream_position=$config['stream_position'];
    else
      $stream_position = 'now';
  } else {
    return(0);
  }
  $request_url='https://api.box.com/2.0/events/';
  $request_method="GET";
  $request_parameter = array(
    "stream_position" => $stream_position,
    "stream_type" => 'changes'
  );
  $url=geturl($request_url,$request_parameter);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
  $event=json_decode(curl_exec($ch),true);
  curl_close($ch);
  if (!isset($event) || !is_array($event) || empty($event)) {
    return('1');
  } elseif (!array_key_exists('type',$event)) {
    if (is_array($config) && isset($config['access_token']) && isset($config['refresh_token'])) {
      $config['stream_position']=$event['next_stream_position'];
      $config=var_export($config, true);
      file_put_contents($box_token_file, "<?php return $config ; ?>", LOCK_EX);
    }
    if (empty($event['entries'])) {
      return('1');
    } else {
      return('0');
    }
  } else {
    return('1');
  }
}
function geturl($request_url,$request_parameter){
  $request_string='';
  if(isset($request_parameter) && !empty($request_parameter)) {
    foreach ($request_parameter as $key => $value) {
      if ( !empty($request_string) )
        $request_string .= '&';
      $request_string .= $key . '=' . $value;
    }
  }
  if ( !empty($request_string) ) {
    return $request_url.'?'.$request_string;
  } else {
    return $request_url;
  }
}
function imagecreatefrombmp($p_sFile) { 
  $file    =    fopen($p_sFile,"rb"); 
  $read    =    fread($file,10); 
  while(!feof($file)&&($read<>"")) 
      $read    .=    fread($file,1024); 
  $temp    =    unpack("H*",$read); 
  $hex    =    $temp[1]; 
  $header    =    substr($hex,0,108); 
  if (substr($header,0,4)=="424d") 
  { 
      $header_parts    =    str_split($header,2); 
      $width            =    hexdec($header_parts[19].$header_parts[18]); 
      $height            =    hexdec($header_parts[23].$header_parts[22]); 
      unset($header_parts); 
  } 
  $x                =    0; 
  $y                =    1; 
  $image            =    imagecreatetruecolor($width,$height); 
  $body            =    substr($hex,108); 
  $body_size        =    (strlen($body)/2); 
  $header_size    =    ($width*$height); 
  $usePadding        =    ($body_size>($header_size*3)+4); 
  for ($i=0;$i<$body_size;$i+=3) 
  { 
      if ($x>=$width) 
      { 
          if ($usePadding) 
              $i    +=    $width%4; 
          $x    =    0; 
          $y++; 
          if ($y>$height) 
              break; 
      } 
      $i_pos    =    $i*2; 
      $r        =    hexdec($body[$i_pos+4].$body[$i_pos+5]); 
      $g        =    hexdec($body[$i_pos+2].$body[$i_pos+3]); 
      $b        =    hexdec($body[$i_pos].$body[$i_pos+1]); 
      $color    =    imagecolorallocate($image,$r,$g,$b); 
      imagesetpixel($image,$x,$height-$y,$color); 
      $x++; 
  } 
  unset($body); 
  return $image; 
}
function createthumbnail($dimg,$source_file,$nw,$nh) {
  $size = getimagesize($source_file);
  $w = $size[0];
  $h = $size[1];
  $type=$size['mime'];
  $stype = explode("/", $type);
  $stype = $stype[count($stype)-1];
  switch($stype) {
  case 'gif':
    $simg = imagecreatefromgif($source_file);
    break;
  case 'jpeg':
    $simg = imagecreatefromjpeg($source_file);
    break;
  case 'png':
    $simg = imagecreatefrompng($source_file);
    break;
  case 'bmp':
    $simg = imagecreatefrombmp($source_file);
    break;
  case 'x-ms-bmp':
    $simg = imagecreatefrombmp($source_file);
    break;
  case 'vnd.wap.wbmp':
    $simg = imagecreatefrombmp($source_file);
    break;
  }
  if (!$simg)
    return(false);
  $wm = $w/$nw;
  $hm = $h/$nh;
  $h_height = $nh/2;
  $w_height = $nw/2;
  if($w> $h) {
      $adjusted_width = $w / $hm;
      $half_width = $adjusted_width / 2;
      $int_width = $half_width - $w_height;
      imagecopyresampled($dimg,$simg,-$int_width,0,0,0,$adjusted_width,$nh,$w,$h);
  } elseif(($w <$h) || ($w == $h)) {
      $adjusted_height = $h / $wm;
      $half_height = $adjusted_height / 2;
      $int_height = $half_height - $h_height;
      imagecopyresampled($dimg,$simg,0,-$int_height,0,0,$nw,$adjusted_height,$w,$h);
  } else {
      imagecopyresampled($dimg,$simg,0,0,0,0,$nw,$nh,$w,$h);
  }
  return($dimg);
}
function getthumb($file_name,$nw,$nh) {
  global $header_string,$secret_key,$cache_dir,$base_dir;
  $thumb_na=$base_dir.'library/na.jpg';
  preg_match('/(\d+)-(\d+)/',$file_name,$match);
  $id=$match[1];
  $file=substr(hash('sha256',$secret_key.$file_name),2,10);
  if (!file_exists($cache_dir.$file)) {
    $tmp_file='/tmp/'.$id;
    downloadfile($tmp_file,$id);
    if ((file_exists($tmp_file) && filesize($tmp_file) !== 0)) {
      $dimg = imagecreatetruecolor($nw, $nh);
      imagefill($dimg,0,0,imagecolorallocate($dimg,255,255,255));
      $finfo=finfo_open(FILEINFO_MIME_TYPE);
      $finfo=finfo_file($finfo,$tmp_file);
      $finfo = explode("/", $finfo);
      $finfo = $finfo[count($finfo)-1];
      if ($finfo == 'gif' || $finfo == 'jpeg' || $finfo == 'png' || $finfo == 'bmp' || $finfo == 'x-ms-bmp' || $finfo == 'vnd.wap.wbmp') {
        $dimg=createthumbnail($dimg,$tmp_file,$nw,$nh);
        if (!$dimg)
          return($thumb_na);
        imagejpeg($dimg,$cache_dir.$file,100);
        if (file_exists($tmp_file))
          unlink($tmp_file);
        return($cache_dir.$file);
      } else {
        if (file_exists($tmp_file))
          unlink($tmp_file);
        return($thumb_na);
      }
    } else
      return($thumb_na);
  } else
    return($cache_dir.$file);
}
function getlist($folder_id) {
  global $header_string;
  $request_parameter = array(
    "fields" => "name,sequence_id,description,parent"
  );
  $request_method="GET";
  $request_url='https://api.box.com/2.0/folders/'.$folder_id;
  $url=geturl($request_url,$request_parameter);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
  $data=json_decode(curl_exec($ch),true);
  curl_close($ch);
  if (!isset($data) || empty($data) || (array_key_exists('type',$data) && $data['type'] == 'error'))
    return('error');

  $limit = 100;
  $request_url='https://api.box.com/2.0/folders/'.$folder_id.'/items';
  $n = 1;
  $item_collection = array();
  for ($i = 0; $i * $limit <= $n; $i++) {
    $request_parameter = array(
      "fields" => "name,sequence_id,description,parent",
      "limit" => $limit,
      "offset" => $i * $limit
    );
    $url=geturl($request_url,$request_parameter);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $items=json_decode(curl_exec($ch),true);
    curl_close($ch);
    if (isset($items) && !empty($items) && (!array_key_exists('type',$items) || $items['type'] !== 'error')) {
      $item_collection = array_merge_recursive($item_collection,$items['entries']);
      $n = $items['total_count'];
    }
  }
  $data=array_merge($data,array('item_collection' => array('total_count' => $n, 'entries' => $item_collection)));

  return($data);
}
function getfilelist($folder_id,$limit,$p) {
  global $cache_dir,$box_cache,$data_dir;
  $data_file=$cache_dir.$folder_id.'.php';
  if ($box_cache == 1 && file_exists($data_file) && file_exists($data_dir.'folder.php') && filemtime($data_file) >= filemtime($data_dir.'folder.php')) {
    $file_list=include($data_file);
    if (isset($limit) && isset($p) && !empty($file_list)) {
      $offset=$p * $limit;
      $file_list=array_slice($file_list,$offset,$limit);
    }
    return($file_list);
  }
  $list=getlist($folder_id);
  if (empty($list) || $list == 'error')
    return($list);
  $file_list=array();
  $entries=$list['item_collection']['entries'];
  if (!empty($entries)) {
    foreach($entries as $entry) {
      if (preg_match('/^\.\S+/',$entry['name']) !== '1')
        $file_list=array_merge($file_list,array('id-'.$entry['id'] => $entry));
    }
  }
  if (!empty($file_list))
    file_put_contents($data_file, "<?php return ".var_export($file_list,true). "; ?>", LOCK_EX);
  if (isset($limit) && isset($p) && !empty($file_list)) {
    $offset=$p * $limit;
    $file_list=array_slice($file_list,$offset,$limit);
  }
  return($file_list);
}
function getfiles($folder_id) {
  global $box_root_folder_id;
  if (!isset($folder_id))
    $folder_id = $box_root_folder_id;
  $files=array();
  $list=getlist($folder_id);
  if ($list == 'error')
    return($files);
  $entries=$list['item_collection']['entries'];
  if (!empty($entries)) {
    foreach($entries as $entry) {
      if ($entry['type'] == 'folder') {
        $list=getfiles($entry['id']);
        $files=array_merge($files,$list);
      } else
        $files=array_merge($files,array('id-'.$entry['id'] => $entry['parent']['id']));
    }
  }
  return($files);
}
function getfolders($folder_id) {
  $folder_list=array();
  $list=getlist($folder_id);
  if ($list == 'error')
    return(array('id-'.$folder_id => $list));
  $entries=$list['item_collection']['entries'];
  $total_count=$list['item_collection']['total_count'];
  array_pop($list);
  $list=array_merge($list,array('total_count' => $total_count));
  $folder_list=array_merge($folder_list,array('id-'.$folder_id => $list));
  if (!empty($entries)) {
    foreach($entries as $entry) {
      if ($entry['type'] == 'folder') {
        $list=getfolders($entry['id']);
        $folder_list=array_merge($folder_list,$list);
      }
    }
  }
  return($folder_list);
}
function getfolderlist() {
  global $data_dir,$box_cache,$box_root_folder_id;
  $data_file=$data_dir.'folder.php';
  if ($box_cache == 1 && file_exists($data_file)) {
    $folder_list=include($data_file);
    return($folder_list);
  }
  $folder_list=getfolders($box_root_folder_id);
  if (file_exists($data_file)) {
    $ori = include($data_file);
  }
  foreach ($folder_list as $key => $folder) {
    if (isset($ori) && is_array($ori) && !empty($ori) && array_key_exists($key,$ori) && $ori[$key] !== 'error') {
      if ($folder !== 'error') {
        $folder = array_merge($ori[$key],$folder);
      } else {
        $folder = $ori[$key];
      }
    } else {
      if ($folder !== 'error') {
        $access = array('public' => array('0'), 'general' => array('0'), 'specific' => array('0','code' => ''), 'temporary' => array('0','code' => '', 'time' => ''));
        $folder = array_merge($folder,array('new' => '1', 'access' => $access));
      }
    }
    $folder_list=array_merge($folder_list,array($key => $folder));
  }
  file_put_contents($data_file, "<?php return ".var_export($folder_list,true). "; ?>", LOCK_EX);
  chmod($data_file, 0600);
  return($folder_list);
}
function coverbordercompose($dimg,$nw,$nh,$nbw,$nbh,$border_width,$thumb,$i) {
  $ntw = $nbw - 2 * $border_width;
  $nth = $nbh - 2 * $border_width;
  $shiftw=$nw / 20;
  $shifth=$nh / 20;
  $border = imagecreatetruecolor($nbw, $nbh);
  $border_color=imagecolorallocate($border,0,0,0);
  if (isset($thumb)) {
    imagefill($border,0,0,$border_color);
    imagecopy($border,$thumb,$border_width,$border_width,0,0,$ntw,$nth);
  } else {
    $fillcolor=imagecolorallocate($border,255,255,255);
    imagefilledrectangle($border,$border_width,$border_width,$ntw,$nth,$fillcolor);
  }
  $newlayer = imagecreatetruecolor($nw, $nh);
  imagesavealpha($newlayer, true);
  $transparent=imagecolorallocatealpha($newlayer,255,255,255,127);
  imagefill($newlayer,0,0,$transparent);
  imagecopy($newlayer,$border,$shiftw * $i,$nh - $nbh - $shifth * $i,0,0,$nbw,$nbh);
  imagecopy($newlayer,$dimg,0,0,0,0,$nw,$nh);
  return($newlayer);
}
function downloadfile($file,$id) {
  global $header_string;
  $ch=curl_init();
  curl_setopt($ch, CURLOPT_URL,"https://api.box.com/2.0/files/".$id."/content");
  curl_setopt($ch, CURLOPT_HEADER,true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
  $data=curl_exec($ch);
  curl_close($ch);
  preg_match('/Location:\ (\S+)/',$data,$match);
  if (!empty($match[0]) && isset($file)) {
    $f=fopen($file,'w');
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL,$match[1]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,false);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER ,1);
    curl_setopt($ch, CURLOPT_HEADER,false);
    curl_setopt($ch, CURLOPT_FILE, $f);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
    curl_exec($ch);
    curl_close($ch);
    fclose($f);
    return(true);
  } elseif (!empty($match[0])) {
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL,$match[1]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_HEADER,false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
    $data=curl_exec($ch);
    curl_close($ch);
    return($data);
  } else
    return(false);
}
function generaterandomstring($length) {
  $characters = '234567ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, strlen($characters) - 1)];
  }
  return $randomString;
}
function gettimestamp($expire_time) {
  return floor(microtime(true)/$expire_time);
}
function oathhotp($timestamp) {
  global $secret_key;
  $otpLength = 6;
  $secret_key = strtoupper($secret_key);
  if (!preg_match('/^[ABCDEFGHIJKLMNOPQRSTUVWXYZ234567]+$/',$secret_key,$match))
    throw new Exception('Invalid characters in the base32 string.');
  $l = strlen($secret_key);
  $n = 0;
  $j = 0;
  $binary_key = "";
  $lut = array(
           "A" => 0,       "B" => 1,
           "C" => 2,       "D" => 3,
           "E" => 4,       "F" => 5,
           "G" => 6,       "H" => 7,
           "I" => 8,       "J" => 9,
           "K" => 10,      "L" => 11,
           "M" => 12,      "N" => 13,
           "O" => 14,      "P" => 15,
           "Q" => 16,      "R" => 17,
           "S" => 18,      "T" => 19,
           "U" => 20,      "V" => 21,
           "W" => 22,      "X" => 23,
           "Y" => 24,      "Z" => 25,
           "2" => 26,      "3" => 27,
           "4" => 28,      "5" => 29,
           "6" => 30,      "7" => 31
  );
  for ($i = 0; $i < $l; $i++) {
    $n = $n << 5;
    $n = $n + $lut[$secret_key[$i]];
    $j = $j + 5;
    if ($j >= 8) {
      $j = $j - 8;
      $binary_key .= chr(($n & (0xFF << $j)) >> $j);
    }
  }
  if (strlen($binary_key) < 8)
    throw new Exception('Secret key is too short. Must be at least 16 base 32 characters');
  $bin_timestamp = pack('N*', 0) . pack('N*', $timestamp);
  $hash = hash_hmac ('sha1', $bin_timestamp, $binary_key, true);
  $key = str_pad(oathtruncate($hash,$otpLength), $otpLength, '0', STR_PAD_LEFT);
  $key = substr(hash('sha256',$key),13,15);
  return($key);
}
function getkey($expire_time) {
  if ($expire_time == 0)
    return('notexpire');
  $timeStamp = gettimestamp($expire_time);
  return(oathhotp($timeStamp));
}
function getprevkey($expire_time) {
  if ($expire_time == 0)
    return('notexpire');
  $timeStamp = gettimestamp($expire_time) - 1;
  return(oathhotp($timeStamp));
}
function verifykey($key, $expire_time, $login) {
  if (!isset($key))
    return false;
  if ($expire_time == 0)
    return true;
  if (isset($login) && $login == 1) {
    $key = substr($key,13,15);
    $window=4;
  } else
    $window=1;
  $timeStamp = gettimestamp($expire_time);
  for ($ts = $timeStamp - $window; $ts <= $timeStamp + $window; $ts++) {
    if (oathhotp($ts) == $key)
      return true;
  }
  return false;
}
function oathtruncate($hash,$otpLength) {
  $offset = ord($hash[19]) & 0xf;
  return (
       ((ord($hash[$offset+0]) & 0x7f) << 24 ) |
       ((ord($hash[$offset+1]) & 0xff) << 16 ) |
       ((ord($hash[$offset+2]) & 0xff) << 8 ) |
       (ord($hash[$offset+3]) & 0xff)
  ) % pow(10, $otpLength);
}
function getprev($array, $key, $n) {
  $keys = array_keys($array);
  if ( (false !== ($p = array_search($key, $keys))) && ($p > $n - 1) && ($p - $n < count($keys)))
    return $array[$keys[$p - $n]];
  else
    return false;
}
function getseq($array,$key,$number) {
  $keys = array_keys($array);
  if (false !== ($p = array_search($key, $keys))) {
    $shift=$number - 1;
    $seq=array();
    $n=0;
    for ($i=0;$i<(2 * $shift + 1);$i++) {
      $item=getprev($array,$key,$shift - $i);
      if ($item) {
        $seq=array_merge($seq,array($item));
        $n++;
      } else {
        $seq=array_merge($seq,array('err'=>'noitem'));
      }
    }
    if ($n > $number)
      $seq=array_slice($seq,$shift - floor(($number - 1) / 2),$number);
    $result=array();
    foreach ($seq as $item) {
      if ($item !== 'noitem')
        $result=array_merge($result,array($item));
    }
    return $result;
  } else
    return false;
}
function auth($string) {
  global $secret_key, $https, $expire_session;
  session_set_cookie_params(0,'/','',$https,1);
  session_name('_mellery');
  if(session_status() !== PHP_SESSION_ACTIVE)
    session_start();
  if (empty($_SESSION) || !array_key_exists('time',$_SESSION) || !array_key_exists('ip',$_SESSION) || !array_key_exists('ip_ts',$_SESSION) || !array_key_exists('ip_change',$_SESSION)) {
    return(false);
  } elseif (time() - $_SESSION['time'] >= $expire_session) {
    return(false);
  } elseif ($_SESSION['ip'] !== hash('sha256', $secret_key.$_SERVER['REMOTE_ADDR'])) {
    if (time() - $_SESSION['ip_ts'] <= 60) {
      if ($_SESSION['ip_change'] >= 5) {
        return(false);
      } else {
        $_SESSION['ip_change'] ++;
      }
    } else {
      $_SESSION['ip_change'] = 0;
    }
    $_SESSION['ip_ts'] = time();
    $_SESSION['ip'] = hash('sha256', $secret_key.$_SERVER['REMOTE_ADDR']);
  }

  if (is_array($string)) {
    foreach ($string as $str) {
      if (array_key_exists($str,$_SESSION) && $_SESSION[$str] == hash('sha256',$secret_key.$str)) {
        $_SESSION['time'] = time();
        return('pass');
      }
    }
  } else {
    if (array_key_exists($string,$_SESSION) && $_SESSION[$string] == hash('sha256',$secret_key.$string)) {
      $_SESSION['time'] = time();
      return('pass');
    }
  }

  return('fail');
}
function getpageurl() {
  if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off')
    $proto='http://';
  else
    $proto='https://';
  if (!empty($_SERVER['QUERY_STRING']))
    $uri = $proto.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING'];
  else
    $uri = $proto.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
  return(urlencode($uri));
}
function changeconf($newSettings) {
  global $data_dir;
  include($data_dir.'config.php');
  $fileSettings = get_defined_vars();
  $fileSettings = array_merge($fileSettings, $newSettings);
  $newFileStr = "<?php\n";
  foreach ($fileSettings as $name => $val) {
    if ($name !== 'newSettings')
      $newFileStr .= "$".$name." = ".var_export($val, TRUE).";\n";
  }
  $newFileStr .= '?>';
  file_put_contents($data_dir.'config.php', $newFileStr);
}
function ipblock($ip) {
  global $lock_timeout, $retry, $cache_dir;
  if ($retry == '0')
    return false;
  $file = $cache_dir.$ip.'-block';
  if (file_exists($file)) {
    $timestamp = file_get_contents($file, true);
    if ($lock_timeout == '0' || time() - $timestamp <= $lock_timeout)
      return true;
  }
  return false;
}
function recordfailure($ip) {
  global $retry, $cache_dir;
  if ($retry == '0')
    return true;
  $file = $cache_dir.$ip;
  $timestamp = floor(time() / 60);
  file_put_contents($file, $timestamp."\n", FILE_APPEND | LOCK_EX);
  $n = preg_match_all("/$timestamp/", file_get_contents($file, true));
  if ($n >= $retry) {
    file_put_contents($file.'-block', time(), LOCK_EX);
  }
}
function updatedetail($id, $details, $type) {
  global $header_string;
  $postfield = json_encode($details);
  $url = 'https://api.box.com/2.0/'.$type.'s/'.$id;
  $ch=curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_HEADER,false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
  curl_setopt($ch, CURLOPT_POSTFIELDS,$postfield);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
  $response=json_decode(curl_exec($ch), true);
  curl_close($ch);
  return($response);
}
function deletefile($id, $type) {
  global $header_string, $data_dir;
  $url = 'https://api.box.com/2.0/'.$type.'s/'.$id;
  if ($type == 'folder')
    $url .= '?recursive=true';
  $ch=curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_HEADER,false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
  curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
  $response=json_decode(curl_exec($ch), true);
  curl_close($ch);
  if (file_exists($data_dir.$id))
    unlink($data_dir.$id);
  return($response);
}
function newfolder($name, $dest) {
  global $header_string;
  $url='https://api.box.com/2.0/folders';
  $postfield=json_encode(array('name' => $name, 'parent' => array('id' => $dest)));
  $ch=curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_HEADER,false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch, CURLOPT_POST,true);
  curl_setopt($ch, CURLOPT_POSTFIELDS,$postfield);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
  $response=json_decode(curl_exec($ch), true);
  curl_close($ch);
  return($response);
}
function movefile($id, $dest, $type) {
  global $header_string, $data_dir;
  $url = 'https://api.box.com/2.0/'.$type.'s'.$id;
  $postfield = json_encode(array('parent' => array('id' => $dest)));
  $ch=curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_HEADER,false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
  curl_setopt($ch, CURLOPT_POSTFIELDS,$postfield);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
  $response=json_decode(curl_exec($ch), true);
  curl_close($ch);
  if (empty($response) || $response['type'] == 'error')
    return(false);
  else {
    $new_id = $response['id'];
    if (file_exists($data_dir.$id)) {
      rename($data_dir.$id, $data_dir.$new_id);
      chmod($data_dir.$new_id, 0600);
    }
    if ($type == 'folder') {
      $folder_list = getfolderlist();
      $folder_list['id-'.$new_id] = $folder_list['id-'.$id];
      unset($folder_list['id-'.$id]);
      file_put_contents($data_dir.'folder.php', "<?php return ".var_export($folder_list,true). "; ?>", LOCK_EX);
    }
    return($new_id);
  }
}
?>
