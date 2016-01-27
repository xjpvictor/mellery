<?php
function boxauth(){
  global $client_id, $client_secret, $box_token_file, $email, $site_name, $username, $base_url, $box_url_auth;
  $lock_file = __DIR__.'/lock_file';
  if (!@filemtime($box_token_file))
    return(false);
  if (function_exists('opcache_invalidate'))
    opcache_invalidate($box_token_file,true);
  $config=include($box_token_file);
  $expires=$config['expires'];
  $now=time();
  if (!isset($config) || !is_array($config) || empty($config))
    return(false);
  if ($now - $expires >= 3500) {
    if (file_exists($lock_file))
      return(false);
    else
      touch($lock_file);
    $refresh_token=$config['refresh_token'];
    $request_parameter=array(
      'refresh_token' => $refresh_token,
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'grant_type' => 'refresh_token'
    );
    $request_url=$box_url_auth.'/oauth2/token';
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL,$request_url);
    curl_setopt($ch, CURLOPT_HEADER,false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_POST,true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$request_parameter);
    $response=json_decode(curl_exec($ch),true);
    curl_close($ch);
    if (!isset($response) || !is_array($response) || empty($response) || !isset($response['access_token']) || !isset($response['refresh_token'])) {
      if (file_exists($lock_file))
        unlink($lock_file);
      return(false);
    } elseif (isset($response['error'])) {
      if (isset($response['error_description']) && $response['error_description'] == 'unauthorized_client') {
        mail($email,$site_name.' reauthentication needed',wordwrap('Hi '.$username.",<br/><br/>\r\n".'You are receiving this email from '.$site_name." because an error has been detected while trying to authenticate with box.com<br/>\r\nYou'll need to manually reauthenticate with box.com via this url<br/><br/>\r\n".'<a href="'.$base_url.'admin/authbox.php" target="_blank">'.$base_url."admin/authbox.php</a>\r\n", 70, "\r\n"),"From: \"Admin\" <admin@".preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME'])).">\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n");
      }
      if (file_exists($lock_file))
        unlink($lock_file);
      return(false);
    } else {
      $access_token=$response['access_token'];
      $config['access_token']=$response['access_token'];
      $config['refresh_token']=$response['refresh_token'];
      $config['expires']=time();
      $config=var_export($config, true);
      file_put_contents($box_token_file, "<?php return $config ; ?>", LOCK_EX);
      $header_string='Authorization: Bearer '.$access_token;
      if (file_exists($lock_file))
        unlink($lock_file);
      return($header_string);
    }
  } else {
    $access_token=$config['access_token'];
    $header_string='Authorization: Bearer '.$access_token;
    return($header_string);
  }
}
function boxcache() {
  global $header_string,$box_token_file,$box_cache_file,$box_url;
  if (@filemtime($box_token_file)) {
    if (function_exists('opcache_invalidate'))
      opcache_invalidate($box_token_file,true);
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
  $request_url=$box_url.'/events/';
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
    $cache = true;
  } elseif (!isset($event['type'])) {
    if (is_array($config) && isset($config['access_token']) && isset($config['refresh_token'])) {
      $config['stream_position']=$event['next_stream_position'];
      $config=var_export($config, true);
      file_put_contents($box_token_file, "<?php return $config ; ?>", LOCK_EX);
    }
    if (empty($event['entries'])) {
      $cache = true;
    } else {
      $cache = false;
    }
  } else {
    $cache = true;
  }
  if (file_exists($box_cache_file) && $cache) {
    return(include($box_cache_file));
  } else {
    $time = time();
    file_put_contents($box_cache_file, '<?php return '.$time.'; ?>', LOCK_EX);
    return $time;
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
  global $cache_dir;
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
  case 'tiff':
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
  if (!isset($simg) || !$simg)
    return(false);
  $wm = max($w,$nw)/$nw;
  $hm = max($h,$nh)/$nh;
  $h_height = $nh/2;
  $w_height = $nw/2;
  $adjusted_width = $w / min($hm,$wm);
  $adjusted_height = $h / min($hm,$wm);
  $half_width = $adjusted_width / 2;
  $half_height = $adjusted_height / 2;
  $int_width = $half_width - $w_height;
  $int_height = $half_height - $h_height;
  imagecopyresampled($dimg,$simg,-$int_width,-$int_height,0,0,$adjusted_width,$adjusted_height,$w,$h);
  return($dimg);
}
function getsize($fsize) {
  if ($fsize < 1024) {
      $fsize = $fsize .' B';
  } elseif ($fsize < 1048576) {
      $fsize = round($fsize / 1024, 2) .' KiB';
  } elseif ($fsize < 1073741824) {
      $fsize = round($fsize / 1048576, 2) . ' MiB';
  } elseif ($fsize < 1099511627776) {
      $fsize = round($fsize / 1073741824, 2) . ' GiB';
  } elseif ($fsize < 1125899906842624) {
      $fsize = round($fsize / 1099511627776, 2) .' TiB';
  }
  return $fsize;
}
function getexif($id) {
  global $box_cache,$cache_dir,$secret_key,$hash_algro;
  if (!is_numeric($id))
    return false;
  $exif_file=$cache_dir.hash($hash_algro,$secret_key.$id).'-exif';
  if (file_exists($exif_file) && filemtime($exif_file) >= $box_cache)
    $info = include($exif_file);
  else {
    $tmp_file='/tmp/'.$id;
    downloadfile($tmp_file,$id);
    $parent=getfileparent($id);
    if (!isset($parent['parent']['id']))
      return false;
    $parent_id=$parent['parent']['id'];
    $created_at = $parent['created_at'];
    $modified_at = $parent['modified_at'];
    if (file_exists($tmp_file) && filesize($tmp_file) !== 0) {
      $size = getimagesize($tmp_file);
      $type=$size['mime'];
      $stype = explode("/", $type);
      $stype = $stype[count($stype)-1];
      switch($stype) {
      case 'jpeg':
        $exif = exif_read_data($tmp_file,'FILE,COMPUTED,ANY_TAG,IFD0,COMMENT,EXIF',0);
        break;
      case 'tiff':
        $exif = exif_read_data($tmp_file,'FILE,COMPUTED,ANY_TAG,IFD0,COMMENT,EXIF',0);
        break;
      }
      $fsize = filesize($tmp_file);
      $fsize = getsize($fsize);
      if (isset($exif) && $exif) {
        $info = array('size' => $size, 'fsize' => $fsize, 'exif' => $exif, 'parent_id' => $parent_id, 'created_at' => $created_at, 'modified_at' => $modified_at);
      } else {
        $info = array('size' => $size, 'fsize' => $fsize, 'parent_id' => $parent_id, 'created_at' => $created_at, 'modified_at' => $modified_at);
      }
      file_put_contents($exif_file, '<?php return '.var_export($info, true).';');
    } else
      return false;
  }
  return $info;
}
function getGps($exifCoord, $hemi) {
    $degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
    $minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
    $seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;
    $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;
    return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
}
function gps2Num($coordPart) {
    $parts = explode('/', $coordPart);
    if (count($parts) <= 0)
        return 0;
    if (count($parts) == 1)
        return $parts[0];
    return floatval($parts[0]) / floatval($parts[1]);
}
function getthumb($file_id,$nw,$nh) {
  global $header_string,$secret_key,$cache_dir,$content_dir,$hash_algro;
  $thumb_na=$content_dir.'na.jpg';
  preg_match('/(\d+)-(\d+)/',$file_id,$match);
  $id=$match[1];
  $file_id_hash=hash($hash_algro,$secret_key.$file_id);
  $file=$file_id_hash.'-'.$nw.'-'.$nh.'.thumb';
  if (!file_exists($cache_dir.$file)) {
    $tmp_file='/tmp/'.$id;
    downloadfile($tmp_file,$id);
    getexif($id);
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
  global $header_string,$box_url;
  if (!is_numeric($folder_id))
    return false;
  $request_parameter = array(
    "fields" => "name,etag,sequence_id,description,parent,created_at,modified_at"
  );
  $request_method="GET";
  $request_url=$box_url.'/folders/'.$folder_id;
  $url=geturl($request_url,$request_parameter);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  $data=curl_exec($ch);
  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $header = substr($data, 0, $header_size);
  $data=json_decode(substr($data, $header_size), true);
  curl_close($ch);
  if (!isset($data) || empty($data))
    return false;
  if (isset($data['type']) && $data['type'] == 'error') {
    if ($data['status'] == '404') {
      cleandata($folder_id);
    }
    return false;
  }

  $limit = 500;
  $request_url=$box_url.'/folders/'.$folder_id.'/items';
  $n = 1;
  $item_collection = array();
  for ($i = 0; $i * $limit <= $n; $i++) {
    $request_parameter = array(
      "fields" => "name,etag,sequence_id,description,parent,created_at,modified_at",
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
    if (isset($items) && !empty($items) && (!isset($items['type']) || $items['type'] !== 'error')) {
      $item_collection = array_merge_recursive($item_collection,$items['entries']);
      $n = $items['total_count'];
    }
  }
  $data=array_merge($data,array('item_collection' => array('total_count' => $n, 'entries' => $item_collection)));

  return($data);
}
function getfilelist($folder_id,$limit = null,$p = null,$order = null) {
  global $cache_dir,$box_cache,$data_dir,$default_public;
  if (!is_numeric($folder_id))
    return 'error';
  $data_file = $cache_dir.$folder_id.'.php';
  if (file_exists($data_file) && filemtime($data_file) >= $box_cache) {
    $file_list=include($data_file);

    if (isset($order) && $order == '2') {
      $time = array();
      $item = array();
      foreach ($file_list['item_collection'] as $key => $entry) {
        $time[$key] = $entry['created_at'];
        $item[$key] = $entry['type'];
      }
      array_multisort($time, SORT_DESC, $item, SORT_STRING, SORT_DESC, $file_list['item_collection']);
    }

    if (isset($limit) && isset($p) && !empty($file_list['item_collection'])) {
      $offset=$p * $limit;
      $file_list['item_collection']=array_slice($file_list['item_collection'],$offset,$limit);
    }
    return($file_list);
  }
  $list=getlist($folder_id);
  if (!$list)
    return 'error';
  $file_list=array();
  $entries=$list['item_collection']['entries'];
  $list['total_count'] = $list['item_collection']['total_count'];
  if (!empty($entries)) {
    foreach($entries as $entry) {
      if (!preg_match('/^\./',$entry['name']))
        $file_list=array_merge($file_list,array('id-'.$entry['id'] => $entry));
    }
  }
  $list['item_collection'] = $file_list;
  unset($file_list);
  file_put_contents($data_file, "<?php return ".var_export($list,true). "; ?>", LOCK_EX);

  if (isset($order) && $order == '-1')
    $list['item_collection'] = array_reverse($list['item_collection']);

  if (isset($limit) && isset($p) && !empty($list['item_collection'])) {
    $offset=$p * $limit;
    $list['item_collection']=array_slice($list['item_collection'],$offset,$limit);
  }

  return $list;
}
function getfileparent($id) {
  global $header_string,$box_url;
  if (!is_numeric($id))
    return 'error';
  $request_method="GET";
  $url=$box_url.'/files/'.$id;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
  $data=json_decode(curl_exec($ch),true);
  curl_close($ch);
  if (!isset($data) || empty($data))
    return('error');
  if (isset($data['type']) && $data['type'] == 'error') {
    if ($data['status'] == '404') {
      cleandata($id);
    }
    return 'error';
  }
  return($data);
}
function getaccess($folder_id, $code = null, $mode = null, $get = 0, $value = null) {
  global $access_file, $default_public, $general_access_code, $box_root_folder_id;
  if (!is_numeric($folder_id))
    return false;
  if (file_exists($access_file))
    $folder_info = include($access_file);
  else
    $folder_info = array();
  if (!isset($folder_info['id-'.$folder_id]) && ($folder = getlist($folder_id))) {
    if (isset($folder['parent']['id']) && $folder['parent']['id'] !== $box_root_folder_id) {
      if (isset($folder_info['id-'.$folder['parent']['id']]))
        $folder_info['id-'.$folder_id] = array_merge(array('new' => '0'), $folder_info['id-'.$folder['parent']['id']]);
      else
        $folder_info['id-'.$folder_id] = array('new' => '0', 'access' => array($default_public, '', '', 'time' => ''));
    } else
      $folder_info['id-'.$folder_id] = array('new' => '1', 'access' => array($default_public, '', '', 'time' => ''));
    file_put_contents($access_file, "<?php return ".var_export($folder_info,true). "; ?>", LOCK_EX);
    chmod($access_file, 0600);
  }

  if (!isset($folder_info['id-'.$folder_id]))
    return false;

  if (isset($get) && $get) {
    if (!isset($value))
      return (isset($folder_info['id-'.$folder_id][$get]) ? $folder_info['id-'.$folder_id][$get] : false);
    else {
      $folder_info['id-'.$folder_id][$get] = $value;
      file_put_contents($access_file, "<?php return ".var_export($folder_info,true). "; ?>", LOCK_EX);
      chmod($access_file, 0600);
      return true;
    }
  }

  $access = $folder_info['id-'.$folder_id]['access'];
  $access[4] = $general_access_code;
  $m = $access[0];

  $set = array('8', '4', '2', '1');
  if (!isset($code) && isset($mode)) {
    if ($mode == 0 && ($access[0] <= 0 || ($access[0] < 4 && !$access[1] && !$access[2])))
      return true;
    foreach ($set as $p) {
      if ($m - $p >= 0) {
        if ($p == $mode) {
          if ($p == 8 || (isset($access[$p]) && $access[$p]))
            return true;
          else
            return false;
        }
        $m = $m - $p;
      } else {
        if ($p == $mode)
          return false;
      }
    }
  } elseif (isset($code)) {
    foreach ($set as $p) {
      if ($m - $p >= 0) {
        if ($p == 8)
          return true;
        elseif (isset($access[$p]) && $access[$p] && $access[$p] == $code ) {
          if ($p > 1 || (isset($access['time']) && !empty($access['time']) && $access['time'] >= time()))
            return true;
        }
        $m = $m - $p;
      }
    }
    return false;
  } elseif ($access[0] >= 8)
    return true;
  return false;
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
  global $header_string,$box_url;
  if (!is_numeric($id))
    return false;
  if (isset($file) && file_exists($file) && filesize($file) !== 0)
    return true;
  $ch=curl_init();
  curl_setopt($ch, CURLOPT_URL,$box_url."/files/".$id."/content");
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
  return($key);
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
function otphash($otp, $hash = 1) {
  global $hash_algro,$otp_init,$otp_length;
  if ($hash)
    $otp = substr(hash($hash_algro,$otp),$otp_init,$otp_length);
  return $otp;
}
function getotp($time = null, $otp = '') {
  if (!$otp && !isset($time))
    return false;
  if (isset($time) && $time == '0')
    return 1;
  if (!$otp) {
    $timeStamp = gettimestamp($time);
    $otp = oathhotp($timeStamp);
    return otphash($otp);
  }
  return otphash($otp);
}
function verifyotp($otp, $time = null, $str = '', $hash = 1) {
  if ((!$str && !isset($time)) || !isset($otp))
    return false;
  if (isset($time) && $time == '0')
    return true;
  if ($str)
    return (otphash($str) == $otp);
  $window = ($hash ? 1 : 4);
  $timeStamp = gettimestamp($time);
  for ($ts = $timeStamp - $window; $ts <= $timeStamp + $window; $ts++) {
    if (otphash(oathhotp($ts), $hash) == $otp)
      return true;
  }
  return false;
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
  global $https, $expire_session, $hash_algro;
  session_set_cookie_params(0,'/','',$https,1);
  session_name('_mellery');
  if(session_status() !== PHP_SESSION_ACTIVE)
    session_start();
  if (!isset($_SESSION['time']) || time() - $_SESSION['time'] >= $expire_session) {
    return(false);
  }

  if (is_array($string)) {
    foreach ($string as $str) {
      if (isset($_SESSION[$str]) && $_SESSION[$str] == hash($hash_algro,$str)) {
        $_SESSION['time'] = time();
        return('pass');
      }
    }
  } else {
    if (isset($_SESSION[$string]) && $_SESSION[$string] == hash($hash_algro,$string)) {
      $_SESSION['time'] = time();
      return('pass');
    }
  }

  return('fail');
}
function getpageurl($dn = 0, $pr = 0) {
  if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off' || empty($_SERVER['HTTPS']))
    $proto='http://';
  else
    $proto='https://';
  if ($dn)
    return ($pr ? $proto : '') . strtolower($_SERVER['SERVER_NAME']).strtr('/'.explode('/',strtolower(dirname($_SERVER['PHP_SELF'])))[1].'/', array('/admin/' => '/','/utils/' => '/','//' => '/'));
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
  global $header_string,$box_url;
  if (!is_numeric($id))
    return false;
  $postfield = json_encode($details);
  $url = $box_url.'/'.$type.'s/'.$id;
  $ch=curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_HEADER,false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
  curl_setopt($ch, CURLOPT_POSTFIELDS,$postfield);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array($header_string));
  $response=json_decode(curl_exec($ch), true);
  curl_close($ch);
  if (isset($response) && !empty($response) && $response['type'] !== 'error')
    return true;
  else
    return false;
}
function deletefile($ids, $types) {
  global $header_string, $data_dir, $access_file, $box_url;
  if (is_array($ids) && is_array($types) && count($ids) !== count($types))
    return false;
  if (!is_array($ids) && !is_array($types)) {
    $ids = array($ids);
    $types = array($types);
  }
  if (is_array($ids)) {
    $mh = curl_multi_init();
    $ch = array();
    $n = count($ids);
    $m = $n;
    $c = 50;
    $j = 0;
    $error = false;
    if (file_exists($access_file))
      $access = include($access_file);
    for ($i = 0; $i < $n; $i ++) {
      $id = $ids[$i];
      if (preg_match('/^\d+$/', $id)) {
        if (is_array($types))
          $type = $types[$i];
        else
          $type = $types;
        $url = $box_url.'/'.$type.'s/'.$id;
        if ($type == 'folder')
          $url .= '?recursive=true';
        $ch[$i]=curl_init();
        curl_setopt($ch[$i], CURLOPT_URL,$url);
        curl_setopt($ch[$i], CURLOPT_HEADER,false);
        curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch[$i], CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch[$i], CURLOPT_HTTPHEADER, array($header_string));
        curl_multi_add_handle($mh, $ch[$i]);
        $j ++;
        if ($j >= min($c, $m)) {
          $responses = array();
          $active = null;
          do {
            $mrc = curl_multi_exec($mh, $active);
          } while ($mrc == CURLM_CALL_MULTI_PERFORM);
          while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) === -1) {
              usleep(100);
            }
            do {
              $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
          }
          foreach($ch as $rm) {
            $responses[]  = curl_multi_getcontent($rm);
            curl_multi_remove_handle($mh, $rm);
            curl_close($rm);
          }
          curl_multi_close($mh);
          $mh = curl_multi_init();
          foreach ($responses as $response) {
            if (isset($response) && !empty($response))
              $error = true;
          }
          $m = $m - $j;
          $j = 0;
          $ch = array();
        }
        foreach (glob($data_dir."[^.]+", GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
          if (file_exists($dir.$id))
            unlink($dir.$id);
        }
        if (isset($access['id-'.$id]) && $type == 'folder')
          unset($access['id-'.$id]);
      }
    }
    if (isset($access)) {
      file_put_contents($access_file, "<?php return ".var_export($access,true). "; ?>", LOCK_EX);
      chmod($access_file, 0600);
    }
    if ($error)
      return false;
    return true;
  }
  return false;
}
function newfolder($name, $dest) {
  global $header_string,$box_url;
  if (!preg_match('/^\d+$/', $dest))
    return false;
  $url=$box_url.'/folders';
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
  if (isset($response) && !empty($response) && $response['type'] !== 'error')
    return($response);
  else
    return false;
}
function movefile($ids, $dest, $types) {
  global $header_string,$box_url;
  if (!is_array($ids) && !is_array($types)) {
    $ids = array($ids);
    $types = array($types);
  }
  if (is_array($ids) && is_array($types)) {
    if (count($ids) !== count($types) || !preg_match('/^\d+$/', $dest) || is_array($dest))
      return false;
    $mh = curl_multi_init();
    $ch = array();
    $n = count($ids);
    $m = $n;
    $c = 50;
    $j = 0;
    $error = false;
    for ($i = 0; $i < $n; $i ++) {
      $id = $ids[$i];
      if (preg_match('/^\d+$/', $id) && $id !== $dest) {
        if (is_array($types))
          $type = $types[$i];
        else
          $type = $types;
        $url = $box_url.'/'.$type.'s/'.$id;
        if ($type == 'folder')
          $url .= '?recursive=true';
        $postfield = json_encode(array('parent' => array('id' => $dest)));
        $ch[$i]=curl_init();
        curl_setopt($ch[$i], CURLOPT_URL,$url);
        curl_setopt($ch[$i], CURLOPT_HEADER,false);
        curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch[$i], CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch[$i], CURLOPT_POSTFIELDS,$postfield);
        curl_setopt($ch[$i], CURLOPT_HTTPHEADER, array($header_string));
        curl_multi_add_handle($mh, $ch[$i]);
        $j ++;
        if ($j >= min($c, $m)) {
          $responses = array();
          $active = null;
          do {
            $mrc = curl_multi_exec($mh, $active);
          } while ($mrc == CURLM_CALL_MULTI_PERFORM);
          while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) === -1) {
              usleep(100);
            }
            do {
              $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
          }
          foreach($ch as $rm) {
            $responses[]  = json_decode(curl_multi_getcontent($rm),true);
            curl_multi_remove_handle($mh, $rm);
            curl_close($rm);
          }
          curl_multi_close($mh);
          $mh = curl_multi_init();
          foreach ($responses as $response) {
            if (!isset($response) || empty($response) || (isset($response['type']) && $response['type'] == 'error'))
              $error = true;
          }
          $m = $m - $j;
          $j = 0;
          $ch = array();
        }
      }
    }
    if ($error)
      return false;
    return true;
  }
  return false;
}
function cut($str,$len){
  $str = preg_replace('/[\r\n]+/',' ',$str);
  $str = preg_replace('/&quot;/','"',$str);
  $str = preg_replace('/&lt;/','<',$str);
  $str = preg_replace('/&gt;/','>',$str);
  preg_match_all('/(<[^>]+>)/',$str,$match);
  $tagarray=$match[1];
  foreach($tagarray as $tagstr ){
	  $l=mb_strlen($tagstr,'utf-8');
	  $len=$len+$l;
  }
  if ( mb_strlen($str,'utf-8') <= $len || $len < 1 ) {
   return $str ;
  } else {
   $newstr=mb_substr($str,0,$len-3,'utf-8').'...';
   return $newstr;
  }
}
function getcontenturl($folder_id){
  global $use_cdn, $cdn_url, $thumb_cdn, $folder_list, $base_url, $username;
  if ((isset($folder_id) && !is_numeric($folder_id)) || auth($username))
    return $base_url;
  if ($use_cdn == '1' && !empty($cdn_url)) {
    if (!isset($folder_id))
      return $cdn_url;
    elseif (getaccess($folder_id) && (isset($thumb_cdn) && $thumb_cdn == '1'))
      return $cdn_url;
  }
  return $base_url;
}
function getcount($id) {
  global $cache_dir;
  if (!is_numeric($id))
    return false;
  $folder = getfilelist($id);
  if (isset($folder['total_count']))
    return $folder['total_count'];
  return 0;
}
function cleandata($id) {
  global $data_dir;
  if (!isset($id) || !is_numeric($id))
    return false;
  $files = glob($data_dir . "*/" . $id, GLOB_NOSORT);
  foreach ($files as $file) {
    unlink($file);
  }
  return true;
}
/*
function updatecount($ids) {
  global $header_string, $cache_dir, $box_url;
  $mh = curl_multi_init();
  $ch = array();
  $n = count($ids);
  $m = $n;
  $c = 50;
  $j = 0;
  $count = array();
  foreach ($ids as $id => $parent) {
    if (is_numeric($id)) {
      $request_url=$box_url.'/folders/'.$id;
      $request_parameter = array(
        "fields" => "item_collection",
      );
      $url=geturl($request_url,$request_parameter);
      $ch[$j]=curl_init();
      curl_setopt($ch[$j], CURLOPT_URL,$url);
      curl_setopt($ch[$j], CURLOPT_RETURNTRANSFER,true);
      curl_setopt($ch[$j], CURLOPT_HTTPHEADER, array($header_string));
      curl_multi_add_handle($mh, $ch[$j]);
      $j ++;
      if ($j >= min($c, $m)) {
        $responses = array();
        $active = null;
        do {
          $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        while ($active && $mrc == CURLM_OK) {
          if (curl_multi_select($mh) === -1) {
            usleep(100);
          }
          do {
            $mrc = curl_multi_exec($mh, $active);
          } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
        foreach($ch as $rm) {
          $responses[]  = curl_multi_getcontent($rm);
          curl_multi_remove_handle($mh, $rm);
          curl_close($rm);
        }
        curl_multi_close($mh);
        $mh = curl_multi_init();
        foreach ($responses as $response) {
          $response = json_decode($response, true);
          if (isset($response) && !empty($response) && $response['type'] !== 'error') {
            $count[$response['id']] = $response['item_collection']['total_count'];
            $parent = $ids[$response['id']];
            $parent_file = $cache_dir.$parent.'.php';
            if (file_exists($parent_file)) {
              $parent_folder = include($parent_file);
              $parent_folder['item_collection']['id-'.$response['id']]['total_count'] = $response['item_collection']['total_count'];
              file_put_contents($parent_file, "<?php return ".var_export($parent_folder,true). "; ?>", LOCK_EX);
            }
          }
        }
        $m = $m - $j;
        $j = 0;
        $ch = array();
      }
    }
  }
  return $count;
}
 */
?>
