<?php
include_once('../data/config.php');
if (!defined('includeauth')) {
  header('Location: '.$base_url);
  exit(0);
}
?>

<div id="footer">
<div id="footer-content">
<?php if (!isset($my_page)) $my_page = include($data_dir.'my_page.php'); echo $my_page['foot']; ?>
<p>&copy; <?php echo date("Y"); ?> <a href="<?php echo $base_url; ?>"><?php echo $site_name; ?></a>. <a rel="license" target="_blank" href="http://creativecommons.org/licenses/by-nc-sa/3.0/">CC BY-NC-SA 3.0</a></p>
<p>Powered by <a href="https://github.com/xjpvictor/mellery" target="_blank">mellery</a> and <a href="https://www.box.com" target="_blank">box</a>.</p>
<p>Hotkeys: j/k - Scroll down/up; J/K - Scroll to bottom/top; h/l - Page down/up; H/L - Scroll left/right; Left/Right - Go to Previous/Next page; U - Back to parent folder</p>
<img id="cache-img" src="<?php echo $base_url; ?>admin/cache.php" width="1" height="1" alt="" />
</div>
</div>

<script type="text/javascript" src="<?php echo $base_url; ?>library/jquery.js"></script>
<?php if (!empty($_SESSION) && array_key_exists('message',$_SESSION) && !empty($_SESSION['message'])) { ?>
<div id="delaymessage">
<?php echo $_SESSION['message']; $_SESSION['message'] = ''; ?>
</div>
<script type="text/javascript">
  $(document).ready(function(){
    $("#delaymessage").show("fast");
    var to=setTimeout("hideDiv()",5000);
  });
  function hideDiv()
  {
    $("#delaymessage").hide("fast");
  }
</script>
<?php } ?>

</body>
</html>
