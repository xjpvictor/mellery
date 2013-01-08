<?php
include_once("config.php");
if (!defined('includeauth')) {
  header('Location: '.$base_url);
  exit(0);
}
include($base_dir.'my_foot.php');
$url=getpageurl();
?>
<p>&copy; <?php echo date("Y"); ?> <a href="<?php echo $base_url; ?>"><?php echo $site_name; ?></a>. <a rel="license" target="_blank" href="http://creativecommons.org/licenses/by-nc-sa/3.0/">CC BY-NC-SA 3.0</a></p>
<p>Powered by Mellery and <a href="https://www.box.com" target="_blank">box</a>.</p>
<p>Hotkeys: j/k - Scroll down/up; J/K - Scroll to bottom/top; h/l - Page down/up; H/L - Scroll left/right; Left/Right - Go to Previous/Next image; U - Back to parent folder</p>
<p><a href="<?php echo $base_url; ?>stat.php?dnt=1&amp;ref=<?php echo $url; ?>" title="Do Not Track">Do Not Track</a></p>
<img src="<?php echo $base_url; ?>admin/cache.php" width="1" height="1" alt="" />
</div>
</div>
</div>
<script type="text/javascript" src="<?php echo $base_url; ?>library/jquery.js"></script>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=206236866085078";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
