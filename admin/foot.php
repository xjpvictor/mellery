<?php
include_once('../data/config.php');
if (!defined('includeauth')) {
  header('Location: '.$base_url);
  exit(0);
}
?>

</div>

<div id="footer">
<div id="footer-content">
<?php if (!isset($my_page) && file_exists($data_dir.'my_page.php')) $my_page = include($data_dir.'my_page.php'); echo $my_page['foot']; ?>
<p>&copy; <?php echo date("Y"); ?> <a href="<?php echo $base_url; ?>"><?php echo $site_name; ?></a></p>
<p>Powered by <a href="https://github.com/xjpvictor/mellery" target="_blank">mellery</a> and <a href="https://www.box.com" target="_blank">box</a></p>
<p><a class="tipTip" title="<span id='hotkeytip'>Available keyboard shortcuts<br/><br/>? - Display this help<br/>j/k - Scroll down/up<br/>J/K - Scroll to bottom/top<br/>h/l - Page down/up<br/>H/L - Scroll left/right<br/>Left/Right - Go to Previous/Next page<br/>U - Back to parent folder</span>" id="shortcut">Keyboard shortcuts</a></p>
<img id="cache-img" src="<?php echo $base_url; ?>utils/cache.php" width="1" height="1" alt="" />
</div>
</div>

<script type="text/javascript" src="<?php echo $base_url; ?>content/jquery.js"></script>
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
