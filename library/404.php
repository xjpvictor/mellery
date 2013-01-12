<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head><title>404 Not Found</title>
<link rel="shortcut icon" href="/favicon.ico" />
<script language="javascript">
var stoptime=5;
var My_Url="<?php echo $base_url; ?>";
godomain();
function godomain()
{setTimeout("gourl()",stoptime*1000);}
function gourl()
{window.location=My_Url;}
function Load(){ 
for(var i=stoptime;i>=0;i--) 
{ 
  window.setTimeout('doUpdate(' + i + ')', (stoptime-i) * 1000); 
}
} 
function doUpdate(num) 
{
  document.getElementById('count-down').innerHTML = num ;
}
Load();
</script> 
</head>
<body bgcolor="white">
<center><h1>404 Not Found</h1></center>
<center><img src="<?php echo $base_url; ?>library/404.jpg" height="300px"/></center>
<center><p>Will be automatically redirected to <a href="<?php echo $base_url; ?>"><?php echo $site_name; ?></a> in <span id="count-down"></span> s.</p></center>
</body>
</html>
