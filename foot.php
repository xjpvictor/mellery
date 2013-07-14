<?php
include_once('./data/config.php');
if (!defined('includeauth')) {
  header('Location: '.$base_url);
  exit(0);
}
?>
<p>&copy; <?php echo date("Y"); ?> <a href="<?php echo $base_url; ?>"><?php echo $site_name; ?></a></p>
<p>Powered by <a href="https://github.com/xjpvictor/mellery" target="_blank">mellery</a> and <a href="https://www.box.com" target="_blank">box</a></p>
<p><span id="ss-sk"><a class="tipTip" title="<span id='hotkeytip'>Available keyboard shortcuts<br/><br/>? - Display this help<br/>j/k - Scroll down/up<br/>J/K - Scroll to bottom/top<br/>h/l - Page down/up<br/>H/L - Scroll left/right<br/>Left/Right - Go to Previous/Next image<br/>U - Back to parent folder<br/>F - Toggle fullscreen</span>" id="shortcut">Keyboard shortcuts</a> | </span><a href="<?php echo $base_url; ?>utils/view.php?dnt=1&amp;ref=<?php echo $url; ?>" title="Do Not Track">Do Not Track</a></p>
<img id="cache-img" src="<?php echo $base_url; ?>utils/cache.php" width="1" height="1" alt="" />
<?php if (!isset($my_page) && file_exists($data_dir.'my_page.php')) $my_page = include($data_dir.'my_page.php'); echo $my_page['foot']; ?>

<div id="fb-root"></div>
<script type="text/javascript" src="<?php echo $cu; ?>content/jquery.js<?php if ($cu !== $base_url) echo '?ver=',filemtime($content_dir.'jquery.js'); ?>"></script>
<script type="text/javascript">
  !function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");
  (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));
  (function() {
    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
    po.src = 'https://apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
  })();
</script>
