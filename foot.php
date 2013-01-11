<?php
include_once('./data/config.php');
if (!defined('includeauth')) {
  header('Location: '.$base_url);
  exit(0);
}
?>
<p>&copy; <?php echo date("Y"); ?> <a href="<?php echo $base_url; ?>"><?php echo $site_name; ?></a>. <a rel="license" target="_blank" href="http://creativecommons.org/licenses/by-nc-sa/3.0/">CC BY-NC-SA 3.0</a></p>
<p>Powered by <a href="https://github.com/xjpvictor/mellery" target="_blank">mellery</a> and <a href="https://www.box.com" target="_blank">box</a>.</p>
<p>Validated with <a href="http://validator.w3.org/check?uri=<?php echo $url; ?>" target="_blank">HTML</a> and <a href="http://jigsaw.w3.org/css-validator/validator?uri=<?php echo $url; ?>" target="_blank">CSS</a>. Optimized for <a href="http://www.opera.com/" target="_blank">Opera</a></p>
<p>Hotkeys: j/k - Scroll down/up; J/K - Scroll to bottom/top; h/l - Page down/up; H/L - Scroll left/right; Left/Right - Go to Previous/Next image; U - Back to parent folder</p>
<p><a href="<?php echo $base_url; ?>stat.php?dnt=1&amp;ref=<?php echo $url; ?>" title="Do Not Track">Do Not Track</a></p>
<img id="cache-img" src="<?php echo $base_url; ?>admin/cache.php" width="1" height="1" alt="" />
<?php if (!isset($my_page)) $my_page = include($data_dir.'my_page.php'); echo $my_page['foot']; ?>

<div id="fb-root"></div>
<script type="text/javascript" src="<?php echo $base_url; ?>library/jquery.js"></script>
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
