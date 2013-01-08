<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head><title>Redirecting..</title>
<link rel="shortcut icon" href="/favicon.ico" />
<script type="text/javascript">
var stoptime=2;
var My_Url="<?php echo $redirect_url; ?>"
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
<center><h1><?php echo $redirect_message; ?></h1></center>
<center><img src="<?php echo $base_url; ?>library/redirect.png" height="250px"/></center>
<center><p>Will be automatically <a href="<?php echo $redirect_url; ?>">redirected</a> in <span id="count-down"></span> s.</p></center>
</body>
</html>
