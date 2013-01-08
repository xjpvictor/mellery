<?php
include_once("../config.php");
if (!defined('includeauth')) {
  header('Location: '.$base_url);
  exit(0);
}
$url=getpageurl();
?>
</div>
</div>
</div>
<div id="footer">
<div id="footer-content">
<div>
<?php include($base_dir.'my_foot.php'); ?>
<p>&copy; <?php echo date("Y"); ?> <a href="<?php echo $base_url; ?>"><?php echo $site_name; ?></a>. <a rel="license" target="_blank" href="http://creativecommons.org/licenses/by-nc-sa/3.0/">CC BY-NC-SA 3.0</a></p>
<p>Powered by Mellery and <a href="https://www.box.com" target="_blank">box</a>.</p>
<p>Hotkeys: j/k - Scroll down/up; J/K - Scroll to bottom/top; h/l - Page down/up; H/L - Scroll left/right; Left/Right - Go to Previous/Next page; U - Back to parent folder</p>
</div>
</div>
</div>
<script type="text/javascript" src="<?php echo $base_url; ?>library/jquery.js"></script>
<?php
if (!empty($_SESSION) && array_key_exists('message',$_SESSION) && !empty($_SESSION['message'])) {
  echo '<div id="delaymessage">';
  echo $_SESSION['message'];
  echo '</div>';
  echo '<script type="text/javascript">'."\n";
  echo '  $(document).ready( function(){'."\n";
  echo '    $("#delaymessage").show("fast");'."\n";
  echo '    var to=setTimeout("hideDiv()",5000);'."\n";
  echo '  });'."\n";
  echo '  function hideDiv()'."\n";
  echo '  {'."\n";
  echo '    $("#delaymessage").hide("fast");'."\n";
  echo '  }'."\n";
  echo '</script>';
  $_SESSION['message'] = '';
}
?>
</body>
</html>
