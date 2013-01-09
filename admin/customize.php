<?php
define('includeauth',true);
include_once('../functions.php');

$auth=auth($username);
session_regenerate_id(true);
$url=getpageurl();
if (!$auth || $auth == 'fail') {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.$url;
  $redirect_message = 'Access restricted';
  include($base_dir."library/redirect.php");
  exit(0);
}

$my_page_file = $data_dir.'my_page.php';
$my_page = include($my_page_file);
$my_style_file = $data_dir.'my_style.css';
if (file_exists($my_style_file))
  $my_style = file_get_contents($my_style_file, true);

if (!empty($_POST)) {
  if (array_key_exists('header',$_POST)) {
    if ($_POST['header'] !== $my_page['header']) {
      $my_page['header'] = $_POST['header'];
      $update = true;
    }
  }
  if (array_key_exists('foot',$_POST)) {
    if ($_POST['foot'] !== $my_page['foot']) {
      $my_page['foot'] = $_POST['foot'];
      $update = true;
    }
  }
  if (array_key_exists('widget',$_POST)) {
    foreach ($_POST['widget'] as $n => $value) {
      if (empty($value['content']) && array_key_exists($n, $my_page['widget'])) {
        unset($my_page['widget'][$n]);
        $my_page['widget'] = array_values(array_filter($my_page['widget']));
        $update = true;
      } elseif (!empty($value['content']) && !array_key_exists($n, $my_page['widget'])) {
        $my_page['widget'][$n] = $value;
        $update = true;
      } elseif (!empty($value['content']) && array_key_exists($n, $my_page['widget']) && ($value['title'] !== $my_page[$n]['title'] || $value['content'] !== $my_page[$n]['content'])) {
        $my_page['widget'][$n] = $value;
        $update = true;
      }
    }
  }
  if (isset($update) && $update) {
    file_put_contents($my_page_file, '<?php return '.var_export($my_page,true). '; ?>', LOCK_EX);
  }
  if (array_key_exists('my_style',$_POST) && $_POST['my_style'] !== $my_style) {
    file_put_contents($my_style_file, $_POST['my_style'], LOCK_EX);
  }
}

$my_page = include($data_dir.'my_page.php');
include('head.php');
?>

<div class="site-config clearfix">
<p>Custom header</p>
<form method="post" action="customize.php">
<textarea rows="10" class="custom" name="header">
<?php echo htmlentities($my_page['header']); ?>
</textarea><br/><br/>
<input class="button right" type="submit" value="Update">
</form> 
</div>

<div class="site-config clearfix">
<p>Custom footer</p>
<form method="post" action="customize.php">
<textarea rows="10" class="custom" name="foot">
<?php echo htmlentities($my_page['foot']); ?>
</textarea><br/><br/>
<input class="button right" type="submit" value="Update">
</form> 
</div>

<?php
$i = 0;
if (array_key_exists('widget', $my_page)) {
  foreach ($my_page['widget'] as $key => $widget) {
    echo '<div class="site-config clearfix">'."\n";
    echo '<p>Widget '.($i + 1).'</p>'."\n";
    echo '<form method="post" action="customize.php">'."\n";
    echo '<p>Title (Optional):</p>'."\n";
    echo '<textarea rows="1" class="custom" name="widget['.$key.'][title]">'."\n";
    echo htmlentities($widget['title']);
    echo '</textarea><br/><br/>'."\n";
    echo '<textarea rows="10" class="custom" name="widget['.$key.'][content]">'."\n";
    echo htmlentities($widget['content']);
    echo '</textarea><br/><br/>'."\n";
    echo '<input class="button right" type="submit" value="Update">'."\n";
    echo '</form>'."\n";
    echo '</div>'."\n";
    $i ++;
  }
}
?>

<div class="site-config clearfix">
<p>New sidebar widget</p>
<form method="post" action="customize.php">
<p>Title (Optional):</p>
<textarea rows="1" class="custom" name="widget[<?php echo $i; ?>][title]">
</textarea><br/><br/>
<textarea rows="10" class="custom" name="widget[<?php echo $i; ?>][content]">
</textarea><br/><br/>
<input class="button right" type="submit" value="New">
</form>
</div>

<div class="site-config clearfix">
<p>Custom CSS stylesheet</p>
<form method="post" action="customize.php">
<textarea rows="10" class="custom" name="my_style">
<?php if (isset($my_style)) echo htmlentities($my_style); ?>
</textarea><br/><br/>
<input class="button right" type="submit" value="Update">
</form> 
</div>

</div>

<?php
include('foot.php');
?>
