<?php
include_once('../data/config.php');
include_once($base_dir.'functions.php');

$auth=auth($username);
session_regenerate_id(true);
if ($auth !== 'pass') {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.urlencode($base_url.'admin/authbox.php');
  $redirect_message = 'Access restricted';
  include($includes_dir.'redirect.php');
  exit(0);
}

if (empty($_GET)) {
  $otp=getkey(30);
  $request_parameter=array(
    'response_type' => 'code',
    'client_id' => $client_id,
    'redirect_uri' => $base_url.'admin/authbox.php',
    'state' => $otp
  );
  $request_url='https://api.box.com/oauth2/authorize';
  $url=geturl($request_url,$request_parameter);
  header("Location: ".$url);
  exit(0);
}
if (!array_key_exists('error',$_GET) && array_key_exists('state',$_GET)) {
  $key=verifykey($_GET['state'],30,null);
  if (!$key)
    header("Location: $base_url");
  $code=$_GET['code'];
  $request_parameter=array(
    'grant_type' => 'authorization_code',
    'code' => $code,
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'redirect_url' => $base_url
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
  if (!array_key_exists('error',$response)) {
    $config=array("access_token" => $response['access_token'],"refresh_token" => $response['refresh_token'],"expires" => time(),"stream_position" => "now");
    $config=var_export($config, true);
    file_put_contents($box_token_file, "<?php return $config ; ?>", LOCK_EX);
    chmod($box_token_file,0600);
    if (!array_key_exists('message',$_SESSION) || empty($_SESSION['message']))
      $_SESSION['message'] = 'Successfully authenticate with Box.com';
    header("Location: ".$base_url.'admin/');
    exit(0);
  }
}
?>
