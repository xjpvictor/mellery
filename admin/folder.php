<?php
define('includeauth',true);
include('../init.php');

header('X-Robots-Tag: noindex,nofollow,noarchive');

$auth=auth($username);
$url = getpageurl();
if ($auth !== 'pass') {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.$url;
  $redirect_message = 'Login required';
  include($includes_dir.'redirect.php');
  exit(0);
}

$header_string=boxauth();
$box_cache=boxcache();

if (empty($_GET) || !isset($_GET['fid']) || empty($_GET['fid']))
  $folder_id = $box_root_folder_id;
else
  $folder_id = $_GET['fid'];

if (isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p'] > 0)
  $p = $_GET['p'];
else
  $p = '1';

$folder = getfilelist($folder_id, $admin_folder_limit, $p - 1, $order);
if ($folder == 'error' || ($p !== '1' && empty($folder))) {
  header("Status: 404 Not Found");
  $redirect_url = $base_url.'admin/folder.php';
  include($includes_dir.'404.php');
  exit(0);
}

if (isset($_GET['code'])) {
  if (!file_exists($access_file)) {
    header("Status: 404 Not Found");
    $redirect_url = $base_url.'admin/folder.php';
    include($includes_dir.'404.php');
    exit(0);
  }

  if ($_GET['code'] == '4') {
    echo $general_access_code;
    exit(0);
  } elseif ($_GET['code'] == '2') {
    $code = include($access_file);
    $code = $code['id-'.$folder_id]['access'][2];
    if (!empty($code)) {
      echo $code;
      exit(0);
    }
  }
  echo 'No access code specified';
  exit(0);
}

if ($_POST) {
  if (isset($_POST['admin_folder_limit'])) {
    if (!empty($_POST['admin_folder_limit'])) {
      changeconf(array('admin_folder_limit' => $_POST['admin_folder_limit']));
    }
  } elseif (isset($_POST['new'])) {
    if ($_POST['new'] == '1' && isset($_POST['name']) && !empty($_POST['name']) && isset($_POST['dest'])) {
      if (($new_folder = newfolder($_POST['name'], $_POST['dest']))) {
        $_SESSION['message'] = 'Album created';
        $redirect_url = $base_url.'admin/folder.php?fid='.$new_folder['id'];
        getaccess($new_folder['id'], null, null, 'new', 0);
      } else
        $_SESSION['message'] = 'Error occurs';
    }
  } elseif (isset($_POST['multiple']['submit'])) {
    if ($_POST['multiple']['submit'] == 'Grant') {
      if (isset($_POST['multiple']['access'])) {
        $update_access = false;
        if (file_exists($access_file)) {
          $access = include($access_file);
          foreach ($_POST['multiple']['item'] as $id => $type) {
            if (!isset($access['id-'.$id]['access']['0'])) {
              $update_access = true;
              $access['id-'.$id] = array('new' => '0', 'access' => array($_POST['multiple']['access'], '', '', 'time' => ''));
            } elseif ($access['id-'.$id]['access']['0'] !== $_POST['multiple']['access']) {
              $update_access = true;
              $access['id-'.$id]['access']['0'] = $_POST['multiple']['access'];
            }
            if ($access['id-'.$id]['new']) {
              $update_access = true;
              $access['id-'.$id]['new'] = '0';
            }
          }
        } else {
          foreach ($_POST['multiple']['item'] as $id => $type)
            $access['id-'.$id] = array('new' => '0', 'access' => array($_POST['multiple']['access'], '', '', 'time' => ''));
          $update_access = true;
        }
        if ($update_access) {
          $_SESSION['message'] = 'Updated';
          file_put_contents($access_file, "<?php return ".var_export($access,true). "; ?>", LOCK_EX);
          chmod($access_file, 0600);
        }
      }
    } elseif ($_POST['multiple']['submit'] == 'Move') {
      if (isset($_POST['multiple']['move']) && preg_match('/^\d+$/', $_POST['multiple']['move']) && isset($_POST['parent']) && preg_match('/^\d+$/', $_POST['parent']) && $_POST['multiple']['move'] !== $_POST['parent']) {
        if (movefile(array_keys($_POST['multiple']['item']), $_POST['multiple']['move'], array_values($_POST['multiple']['item'])))
          $_SESSION['message'] = 'Moved';
        else
          $_SESSION['message'] = 'Error occurs';
      }
    } elseif ($_POST['multiple']['submit'] == 'Delete') {
      if (deletefile(array_keys($_POST['multiple']['item']), array_values($_POST['multiple']['item'])))
        $_SESSION['message'] = 'Deleted';
      else
        $_SESSION['message'] = 'Error occurs';
    }
  } elseif (isset($_POST['single']['update'])) {
    $id = $_POST['single']['update'];
    $item = $_POST['single'][$id];
    $type = $_POST['single'][$id]['type'];
    $list = getfilelist($_POST['parent']);
    $list_items = $list['item_collection'];

    if (isset($_POST['cover-id']) && $_POST['cover-id'])
      changeconf(array('cover_id' => $_POST['cover-id']));
    elseif (isset($_POST['cover-id']) && isset($cover_id) && $cover_id == $id)
      changeconf(array('cover_id' => ''));

    $update_detail = false;
    $error = false;
    if (isset($item['name']) && $item['name'] !== $list_items['id-'.$id]['name'] && ($item['name'] == '0' || !empty($item['name']))) {
      if ($type == 'file')
        $list_items['id-'.$id]['name'] = $item['name'] . substr($list_items['id-'.$id]['name'], strrpos($list_items['id-'.$id]['name'], '.', -1));
      else
        $list_items['id-'.$id]['name'] = $item['name'];
      $update_detail = true;
    }
    if (isset($item['description']) && $item['description'] !== $list_items['id-'.$id]['description']) {
      $list_items['id-'.$id]['description'] = $item['description'];
      $update_detail = true;
    }
    if ($update_detail) {
      if (!updatedetail($id, array('name' => $list_items['id-'.$id]['name'], 'description' => $list_items['id-'.$id]['description']), $type))
        $error = true;
    }

    if (isset($item['move']) && $item['move'] !== $_POST['parent']) {
      if (!movefile($id, $item['move'], $type))
        $error = true;
      else
        $redirect_url = $base_url.'admin/folder.php?fid='.$item['move'];
    }

    if ($type == 'folder') {
      $m = 0;
      foreach ($item['access'] as $key => $n)
        $m = $m + $n;
      if (file_exists($access_file))
        $access = include($access_file);
      else {
        $access = array();
        $access['id-'.$id] = array('new' => '0', 'access' => array('', '', '', 'time' => ''));
      }
      $update_access = false;
      if ($access['id-'.$id]['access']['0'] !== $m) {
        $update_access = true;
        $access['id-'.$id]['access']['0'] = $m;
      }
      if (isset($item['specific-code']) && $item['specific-code'] && $item['specific-code'] !== 'Leave blank if not changed') {
        $update_access = true;
        $access['id-'.$id]['access']['2'] = $item['specific-code'];
      }
      if (isset($item['temporary-code']) && $item['temporary-code'] && $item['temporary-code'] !== 'Specify an access code') {
        $update_access = true;
        $access['id-'.$id]['access']['1'] = $item['temporary-code'];
        if (isset($item['temporary-time']) && is_numeric($item['temporary-time']) && $item['temporary-time'] >= 0)
          $access['id-'.$id]['access']['time'] = time() + 3600 * $item['temporary-time'];
        else
          $access['id-'.$id]['access']['time'] = time() + 3600 * $expire_access_code;
      }
      if ($access['id-'.$id]['new']) {
        $update_access = true;
        $access['id-'.$id]['new'] = '0';
      }
      if (!isset($access['id-'.$id]['cl']) || $access['id-'.$id]['cl'] !== $item['cl']) {
        $update_access = true;
        $access['id-'.$id]['cl'] = $item['cl'];
      }
      if (isset($access['id-'.$id]['cl']) && $access['id-'.$id]['cl'] && ((!isset($access['id-'.$id]['cl_url']) && $item['cl_url']) || (isset($access['id-'.$id]['cl_url']) && $access['id-'.$id]['cl_url'] !== $item['cl_url']))) {
        $update_access = true;
        $access['id-'.$id]['cl_url'] = $item['cl_url'];
      }
      if ($update_access) {
        file_put_contents($access_file, "<?php return ".var_export($access,true). "; ?>", LOCK_EX);
        chmod($access_file, 0600);
      }
    }
    if ($error)
      $_SESSION['message'] = 'Error occurs';
    elseif ($update_detail || $update_access)
      $_SESSION['message'] = 'Updated';
  } elseif (isset($_POST['single']['delete'])) {
    $id = $_POST['single']['delete'];
    if (deletefile($id, $_POST['single'][$id]['type'])) {
      $_SESSION['message'] = 'Deleted';
    } else
      $_SESSION['message'] = 'Error occurs';
  }

  if (!isset($redirect_url) && isset($_GET['ref']) && !empty($_GET['ref']))
    $redirect_url = urldecode($_GET['ref']).(isset($_POST['single']['update']) ? '#'.$_POST['single']['update'] : '');
  elseif (!isset($redirect_url))
    $redirect_url = $base_url.'admin/folder.php';
  session_regenerate_id(true);
  sleep(2);
  header("Location: $redirect_url");
  exit(0);
}
?>

<?php
if (getaccess($folder_id, null, null, 'new')) {
  getaccess($folder_id, null, null, 'new', '0');
}
?>

<?php
if (file_exists($data_dir.'my_page.php'))
  $my_page = include($data_dir.'my_page.php');
include('head.php');

$otp=getotp($expire_image);

$move_list = '';
$move_list .= '<option value="'.$box_root_folder_id.'">My Albums</option>'."\n";
$all_album_list = '<ul>';
$all_album_list .= '<li><span><a href="'.$base_url.'admin/folder.php">My Albums</a></span></li>'."\n";
$folder_l1 = getfilelist($box_root_folder_id);
foreach ($folder_l1['item_collection'] as $id => $l1_folder) {
  if ($l1_folder['type'] == 'folder') {
    $move_list .= '<option value="'.$l1_folder['id'].'">'.$l1_folder['name'].'</option>'."\n";
    $all_album_list .= '<li><span><a href="'.$base_url.'admin/folder.php?fid='.$l1_folder['id'].'">'.$l1_folder['name'].'</a></span></li>'."\n";
  }
}
$all_album_list .= '</ul>';

$list = $folder['item_collection'];
$n = $folder['total_count'];
?>

<div class="admin-folder-nav clearfix">
<form class="right" method="post" action="folder.php?ref=<?php echo urlencode($base_url.'admin/folder.php?fid='.$folder_id); ?>">
<input name="name">
<input type="hidden" name="dest" value="<?php echo $folder_id; ?>">
<button class="button" type="submit" name="new" value="1">New <?php echo ($folder_id == $box_root_folder_id ? 'Album' : 'Subfolder'); ?></button>
</form>
</div>

<div class="admin-folder-nav clearfix" id="<?php echo $folder['id']; ?>">
<a href="<?php echo $base_url; ?>?fid=<?php echo $folder['id']; ?>"><?php echo $folder['name']; ?></a>
(<?php echo $folder['total_count']; ?> items, <?php if (file_exists($stat_dir.$folder['id'])) echo file_get_contents($stat_dir.$folder['id'], true); else echo '0'; ?> views)
<span class="edit-admin"><a href="javascript:;" onclick="toggleShow('admin-folder-<?php echo $folder['id']; ?>');toggleShow('admin-folder-content');">Edit <?php echo ($folder['parent']['id'] == $box_root_folder_id ? 'Album' : 'Folder'); ?></a></span>
<p class="meta-admin">Created: <?php echo date('d. F Y', strtotime($folder['created_at'])); ?><br/>Modified: <?php echo date('d. F Y', strtotime($folder['modified_at'])); ?></p>
</div>

<div class="admin-folder-nav clearfix">
<div class="admin-folder clearfix" id="admin-folder-<?php echo $folder['id']; ?>" style="display:none;">

<img class="admin-album" src="<?php echo $base_url; ?>cover.php?fid=<?php echo $folder['id']; ?>&amp;otp=<?php echo $otp; ?>" alt="<?php echo $folder['name']; ?>" title="<?php echo $folder['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" />

<div class="admin-access">
<form style="padding:10px;line-height:25px;vertical-align:middle;" method="POST" action="folder.php?ref=<?php echo $url; ?>">
<input type="hidden" name="parent" value="<?php echo $folder['parent']['id']; ?>">

<p>Name:</p><input class="name-conf" name="single[<?php echo $folder['id']; ?>][name]" value="<?php echo $folder['name']; ?>"><br/>
<?php if ($folder_id !== $box_root_folder_id) { ?>
<p>Description:</p><textarea rows="3" class="description-conf" name="single[<?php echo $folder['id']; ?>][description]"><?php echo $folder['description']; ?></textarea><br/>
<?php } ?>

<p>Access:</p>
<label><input class="radio" type="radio" name="single[<?php echo $folder['id']; ?>][access][0]" value="8"<?php if (getaccess($folder['id'], null, '8')) echo ' checked'; ?> onclick="hide('extra-<?php echo $folder['id']; ?>')">Public access</label><br/>
<label><input class="radio" type="radio" name="single[<?php echo $folder['id']; ?>][access][0]" value="0"<?php if (!getaccess($folder['id'], null, '8') && !getaccess($folder['id'], null, '0')) echo ' checked'; ?> onclick="toggleShow('extra-<?php echo $folder['id']; ?>')">Restrict access</label><br/>

<div class="extra" id="extra-<?php echo $folder['id']; ?>">
<input type="hidden" name="single[<?php echo $folder['id']; ?>][access][4]" value="0"><label><input class="checkbox" type="checkbox" name="single[<?php echo $folder['id']; ?>][access][4]" value="4" <?php if (getaccess($folder['id'], null, '4')) echo ' checked'; ?>>Access with general access code</label><br/><br/>
<input type="hidden" name="single[<?php echo $folder['id']; ?>][access][2]" value="0"><label><input class="checkbox" type="checkbox" name="single[<?php echo $folder['id']; ?>][access][2]" value="2"<?php if (getaccess($folder['id'], null, '2')) echo ' checked'; ?>>Specify an access code: </label><input id="specific-code-<?php echo $folder['id']; ?>" type="text" style="width:180px;" name="single[<?php echo $folder['id']; ?>][specific-code]" value="Leave blank if not changed" onfocus="if(this.value==this.defaultValue)this.value='';" onblur="if(this.value=='')this.value=this.defaultValue;"> or
<input class="button" type="button" name="single[<?php echo $folder['id']; ?>][specific-code-generate]" value="Generate randomly" onclick="document.getElementById ('specific-code-<?php echo $folder['id']; ?>').value=(getRandomString(8))"><br/>
<p class="button" style="float:left;margin:3px 25px;"><a href="<?php echo $base_url; ?>admin/folder.php?code=2&amp;fid=<?php echo $folder['id']; ?>" target="_blank">Show current access code</a></p><br/><br/>
<input type="hidden" name="single[<?php echo $folder['id']; ?>][access][1]" value="0"><label><input class="checkbox" type="checkbox" name="single[<?php echo $folder['id']; ?>][access][1]" value="1"<?php if (getaccess($folder['id'], null, '1')) echo ' checked'; ?>>Grant temporary access for </label><input type="text" style="width:30px;" name="single[<?php echo $folder['id']; ?>][temporary-time]" value="<?php echo $expire_access_code; ?>"> hours<br/><input id="temporary-code-<?php echo $folder['id']; ?>" type="text" style="margin-left:25px;width:180px;" name="single[<?php echo $folder['id']; ?>][temporary-code]" value="Specify an access code" onfocus="if(this.value==this.defaultValue)this.value='';" onblur="if(this.value=='')this.value=this.defaultValue;"> or <input class="button" type="button" name="single[<?php echo $folder['id']; ?>][temporary-code-generate]" value="Generate temporary access code" onclick="document.getElementById ('temporary-code-<?php echo $folder['id']; ?>').value=(getRandomString(8))"><br/>
<p style="padding-left:25px;" class="small">* Specifying new access code will revoke the original one</p>
</div>

<label><input class="radio" type="radio" name="single[<?php echo $folder['id']; ?>][access][0]" value="-8"<?php if (getaccess($folder['id'], null, '0')) echo ' checked'; ?> onclick="hide('extra-<?php echo $folder['id']; ?>')">Private</label><br/>

<?php if ($folder_id !== $box_root_folder_id) { ?>
<p>Location:</p>
<div class="dropdown drop-move">
<select name="single[<?php echo $folder['id']; ?>][move]">
<?php echo str_replace($folder['parent']['id'].'"', $folder['parent']['id'].'" selected', $move_list); ?>
</select>
</div>

<br/>
<input type="hidden" name="single[<?php echo $folder['id']; ?>][cl]" value="0">
<label><input type="checkbox" class="checkbox" name="single[<?php echo $folder['id']; ?>][cl]" value="1" <?php echo (($cl = getaccess($folder['id'], null, null, 'cl')) ? 'checked' : ''); ?> onclick="if(this.checked){document.getElementById('cl-<?php echo $folder['id']; ?>').disabled=false;document.getElementById('cl-url-<?php echo $folder['id']; ?>').disabled=false;show('cl-input-<?php echo $folder['id']; ?>');}else{document.getElementById('cl-<?php echo $folder['id']; ?>').disabled=true;document.getElementById('cl-url-<?php echo $folder['id']; ?>').disabled=true;hide('cl-input-<?php echo $folder['id']; ?>');}">Custom license</label>
<br/>
<div class="cl" id="cl-input-<?php echo $folder['id']; ?>" style="display:<?php echo ($cl ? 'block' : 'none'); ?>;">
<p>Name:<br/><input id="cl-<?php echo $folder['id']; ?>" name="single[<?php echo $folder['id']; ?>][cl]" <?php echo (!$cl ? 'disabled' : 'value="'.htmlentities($cl).'"'); ?>></p>
<p>Link:<br/><input id="cl-url-<?php echo $folder['id']; ?>" name="single[<?php echo $folder['id']; ?>][cl_url]"<?php echo (!$cl ? ' disabled' : (!($cl_url = getaccess($folder['id'], null, null, 'cl_url')) ? '' : ' value="'.htmlentities($cl_url).'"')); ?>></p>
</div>

<?php if (getaccess($folder['id'], null, '8')) { ?>
<br/><p>Embed:</p>
<p>Folder preview iframe</p>
<input class="name-conf" value="<?php echo htmlentities('<iframe src="'.$base_url.'frame.php?fid='.$folder['id'].'&l=6&a='.getotp(null,$folder['id'].'-id').'" width="550" height="480" allowtransparency="true" seamless scrolling="auto" frameborder="0">'.$folder['name'].'</iframe>'); ?>" onclick="this.select()"><br/>
<?php } ?>
<?php } ?>

<br/>
<button class="button" type="submit" name="single[update]" value="<?php echo $folder['id']; ?>">Update</button>
<button class="button right delete" type="submit" name="single[delete]" value="<?php echo $folder['id']; ?>" onclick="return confirmAct();">Delete</button>

<input type="hidden" name="single[<?php echo $folder['id']; ?>][type]" value="folder">
</form>

</div></div>
</div>

<div id="admin-folder-content" style="display:block;">
<div class="admin-folder-nav clearfix">

<div class="admin-folder-parent">
<?php if ($folder_id !== $box_root_folder_id) { ?>
<a href="<?php echo $base_url; ?>admin/folder.php<?php if ($folder['parent']['id'] !== $box_root_folder_id) echo '?fid=',$folder['parent']['id']; ?>">&lt;&lt;&nbsp;<?php if ($folder['parent']['id'] !== $box_root_folder_id) echo $folder['parent']['name']; else echo 'My Albums'; ?></a>
<div class="edit-admin" id="jumpto">
<a href="javascript:;" onclick="toggleShow('all-album-list')">Jump to..</a>
<div id="all-album-list">
<a class="close" href="javascript:;" onclick="toggleShow('all-album-list')">[Close]</a><br/><?php echo $all_album_list; ?>
</div>
</div>
<?php } else { ?>
My Albums (<?php echo $n; ?> Albums)
<?php } ?>
</div>

<form method="post" class="admin-folder-form right" action="folder.php?ref=<?php echo urlencode($base_url.'admin/folder.php?fid='.$folder_id); ?>">
Items per page:<input class="admin-folder-limit" name="admin_folder_limit" value="<?php echo $admin_folder_limit; ?>">
</form>

<div id="pager-top">
<?php
if ($p > 1)
  echo '<div class="prev"><a href="folder.php?fid='.$folder_id.($p == 2 ? '' : '&amp;p='.($p - 1)).'" title="Previous">← Previous</a></div>';
if ($p * $admin_folder_limit < $n)
  echo '<div class="next"><a href="folder.php?fid='.$folder_id.'&amp;p='.($p + 1).'" title="Next">Next →</a></div>';
?>
<div class="pager">Page <form method="GET" action="folder.php"><input type="hidden" name="fid" value="<?php echo $folder_id; ?>"><input name="p" value="<?php echo $p; ?>"></form> of <?php echo max(1, ceil($n / $admin_folder_limit)); ?></div>
</div>

</div>

<form method="POST" action="folder.php?ref=<?php echo $url; ?>" name="myForm">
<input type="hidden" name="parent" value="<?php echo $folder_id; ?>">

<input style="display:none;" type="submit" name="none" value="None" onclick="return false;">
<div class="admin-folder-nav clearfix">

<label class="left"><input class="checkbox" type="checkbox" name="select-all" onclick="if(this.checked==true) {selectAll(1); } else {selectAll(0); }"><span class="multiform">All/None</span></label>

<?php if ($folder_id == $box_root_folder_id): ?>
<div class="dropdown left multiform drop-access">
<select id="multi-access-1" name="multiple[access]" onchange="javascript:{document.getElementById('multi-access-2').value=this.value;}">
<option>Album access</option>
<option value="8">Public</option>
<option value="4">Restricted</option>
<option value="0">Private</option>
</select>
</div>
<input class="button left multiform" type="submit" name="multiple[submit]" value="Grant">
<?php endif; ?>

<div class="dropdown drop-move multiform left">
<select id="multi-move-1" name="multiple[move]" onchange="javascript:{document.getElementById('multi-move-2').value=this.value;}">
<option selected>Move to Location</option>
<?php echo $move_list; ?>
</select>
</div>
<input class="button left multiform" type="submit" name="multiple[submit]" value="Move">

<input class="button right delete" type="submit" name="multiple[submit]" value="Delete" onclick="return confirmAct();">

</div>

<?php foreach ($list as $id => $item) { ?>
<div class="site-config clearfix" id="<?php echo $item['id']; ?>">
<input class="checkbox" type="checkbox" name="multiple[item][<?php echo $item['id']; ?>]" value="<?php echo $item['type']; ?>">

<?php
  if ($item !== 'error' && $item['type'] == 'folder') {
?>

  <a href="<?php echo $base_url; ?>?fid=<?php echo $item['id']; ?>"><?php echo $item['name']; ?></a> (<?php echo getcount($item['id']); ?> items, <?php if (file_exists($stat_dir.$item['id'])) echo file_get_contents($stat_dir.$item['id'], true); else echo '0'; ?> views)

<?php if (getaccess($item['id'], null, null, 'new')) { ?>
   <span class="new">NEW</span>
<?php } ?>

   <span class="edit-admin"><a href="folder.php?fid=<?php echo $item['id']; ?>">Manage <?php echo ($folder_id == $box_root_folder_id ? 'Album' : 'Folder'); ?></a></span>
   <br/><img class="admin-album" src="<?php echo $base_url; ?>cover.php?fid=<?php echo $item['id']; ?>&amp;otp=<?php echo $otp; ?>" alt="<?php echo $item['name']; ?>" title="<?php echo $item['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" />

<?php
  } elseif ($item !== 'error' && $item['type'] == 'file') {
?>

  <a href="<?php echo $base_url; ?>image.php?id=<?php echo $item['id']; ?>"><?php echo $item['name']; ?></a> (<?php if (file_exists($stat_dir.$item['id'])) echo file_get_contents($stat_dir.$item['id'], true); else echo '0'; ?> views)

<?php
    $item['name'] = substr($item['name'], 0, strrpos($item['name'], '.', -1));
?>
  <br/><img class="admin-img" src="<?php echo $base_url; ?>thumbnail.php?id=<?php echo $item['id']; ?>&amp;otp=<?php echo $otp; ?>" alt="<?php echo $item['name']; ?>" title="<?php echo $item['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" />

<?php
  }
?>

  <div class="admin-access"><div class="admin-form">
  <p>Name:</p><input class="name-conf" name="single[<?php echo $item['id']; ?>][name]" value="<?php echo $item['name']; ?>"><br/>
  <p>Description:</p><textarea rows="3" class="description-conf" name="single[<?php echo $item['id']; ?>][description]"><?php echo $item['description']; ?></textarea><br/>

<?php if ($item !== 'error' && $item['type'] == 'folder') { ?>
    <p>Access:</p>
    <label><input class="radio" type="radio" name="single[<?php echo $item['id']; ?>][access][0]" value="8"<?php if (getaccess($item['id'], null, '8')) echo ' checked'; ?> onclick="hide('extra-<?php echo $id; ?>')">Public access</label><br/>
    <label><input class="radio" type="radio" name="single[<?php echo $item['id']; ?>][access][0]" value="0"<?php if (!getaccess($item['id'], null, '8') && !getaccess($item['id'], null, '0')) echo ' checked'; ?> onclick="toggleShow('extra-<?php echo $id; ?>')">Restrict access</label><br/>

    <div class="extra" id="extra-<?php echo $id; ?>">
    <input type="hidden" name="single[<?php echo $item['id']; ?>][access][4]" value="0"><label><input class="checkbox" type="checkbox" name="single[<?php echo $item['id']; ?>][access][4]" value="4" <?php if (getaccess($item['id'], null, '4')) echo ' checked'; ?>>Access with general access code</label><br/><br/>
    <input type="hidden" name="single[<?php echo $item['id']; ?>][access][2]" value="0"><label><input class="checkbox" type="checkbox" name="single[<?php echo $item['id']; ?>][access][2]" value="2"<?php if (getaccess($item['id'], null, '2')) echo ' checked'; ?>>Specify an access code: </label><input id="specific-code-<?php echo $id; ?>" type="text" style="width:180px;" name="single[<?php echo $item['id']; ?>][specific-code]" value="Leave blank if not changed" onfocus="if(this.value==this.defaultValue)this.value='';" onblur="if(this.value=='')this.value=this.defaultValue;"> or
   <input class="button" type="button" name="single[<?php echo $item['id']; ?>][specific-code-generate]" value="Generate randomly" onclick="document.getElementById ('specific-code-<?php echo $id; ?>').value=(getRandomString(8))"><br/>
    <p class="button" style="float:left;margin:3px 25px;"><a href="<?php echo $base_url; ?>admin/folder.php?code=2&amp;fid=<?php echo $item['id']; ?>" target="_blank">Show current access code</a></p><br/><br/>
    <input type="hidden" name="single[<?php echo $item['id']; ?>][access][1]" value="0"><label><input class="checkbox" type="checkbox" name="single[<?php echo $item['id']; ?>][access][1]" value="1"<?php if (getaccess($item['id'], null, '1')) echo ' checked'; ?>>Grant temporary access for </label><input type="text" style="width:30px;" name="single[<?php echo $item['id']; ?>][temporary-time]" value="<?php echo $expire_access_code; ?>"> hours<br/><input id="temporary-code-<?php echo $id; ?>" type="text" style="margin-left:25px;width:180px;" name="single[<?php echo $item['id']; ?>][temporary-code]" value="Specify an access code" onfocus="if(this.value==this.defaultValue)this.value='';" onblur="if(this.value=='')this.value=this.defaultValue;"> or <input class="button" type="button" name="single[<?php echo $item['id']; ?>][temporary-code-generate]" value="Generate temporary access code" onclick="document.getElementById ('temporary-code-<?php echo $id; ?>').value=(getRandomString(8))"><br/>
    <p style="padding-left:25px;" class="small">* Specifying new access code will revoke the original one</p>
    </div>
    <label><input class="radio" type="radio" name="single[<?php echo $item['id']; ?>][access][0]" value="-8"<?php if (getaccess($item['id'], null, '0')) echo ' checked'; ?> onclick="hide('extra-<?php echo $id; ?>')">Private</label><br/>
    <input type="hidden" name="single[<?php echo $item['id']; ?>][type]" value="folder">

<?php } elseif ($item !== 'error' && $item['type'] == 'file') { ?>
    <input type="hidden" name="single[<?php echo $item['id']; ?>][type]" value="file">

<?php } ?>

  <p>Location:</p>
  <div class="dropdown drop-move">
  <select name="single[<?php echo $item['id']; ?>][move]">
<?php echo str_replace($item['parent']['id'].'"', $item['parent']['id'].'" selected', $move_list); ?>
  </select>
  </div>

<?php if ($item !== 'error' && $item['type'] == 'folder') { ?>
  <br/>
  <input type="hidden" name="single[<?php echo $item['id']; ?>][cl]" value="0">
  <label><input type="checkbox" class="checkbox" name="single[<?php echo $item['id']; ?>][cl]" value="1" <?php echo (($cl = getaccess($item['id'], null, null, 'cl')) ? 'checked' : ''); ?> onclick="if(this.checked){document.getElementById('cl-<?php echo $item['id']; ?>').disabled=false;document.getElementById('cl-url-<?php echo $item['id']; ?>').disabled=false;show('cl-input-<?php echo $item['id']; ?>');}else{document.getElementById('cl-<?php echo $item['id']; ?>').disabled=true;document.getElementById('cl-url-<?php echo $item['id']; ?>').disabled=true;hide('cl-input-<?php echo $item['id']; ?>');}">Custom license</label>
  <br/>
  <div class="cl" id="cl-input-<?php echo $item['id']; ?>" style="display:<?php echo ($cl ? 'block' : 'none'); ?>;">
  <p>Name:<br/><input id="cl-<?php echo $item['id']; ?>" name="single[<?php echo $item['id']; ?>][cl]" <?php echo (!$cl ? 'disabled' : 'value="'.htmlentities($cl).'"'); ?>></p>
  <p>Link:<br/><input id="cl-url-<?php echo $item['id']; ?>" name="single[<?php echo $item['id']; ?>][cl_url]"<?php echo (!$cl ? ' disabled' : (!($cl_url = getaccess($item['id'], null, null, 'cl_url')) ? '' : ' value="'.htmlentities($cl_url).'"')); ?>></p>
  </div>
<?php } ?>

<?php if ($item !== 'error' && $item['type'] == 'folder' && getaccess($item['id'], null, '8')) { ?>
  <br/><p>Embed:</p>
  <p>Folder preview iframe</p>
  <input class="name-conf" value="<?php echo htmlentities('<iframe src="'.$base_url.'frame.php?fid='.$item['id'].'&l=6&a='.getotp(null,$item['id'].'-id').'" width="550" height="480" allowtransparency="true" scrolling="auto" seamless frameborder="0">'.$item['name'].'</iframe>'); ?>" onclick="this.select()"><br/>
<?php } elseif ($item !== 'error' && $item['type'] == 'file') { ?>
  <br/><p><label><input class="radio" type="radio" name="cover-id" value="<?php echo $item['id']; ?>" <?php echo ($item['id'] == $cover_id ? 'checked ' : ''); ?>/>Set as Cover Image</label><?php echo ($item['id'] == $cover_id ? '<br/><label><input type="radio" class="radio" name="cover-id" value="0">Clear Cover Image</label>' : ''); ?></p>
<?php } ?>

  <br/><button class="button" type="submit" name="single[update]" value="<?php echo $item['id']; ?>">Update</button>
  <button class="button right delete" type="submit" name="single[delete]" value="<?php echo $item['id']; ?>" onclick="return confirmAct();">Delete</button>
  <div class="clear"></div>
  </div></div>
  </div>

<?php } ?>

<div class="admin-folder-nav clearfix">
<label class="left"><input class="checkbox" type="checkbox" name="select-all" onclick="if(this.checked==true) {selectAll(1); } else {selectAll(0); }"><span class="multiform">All/None</span></label>
<?php if ($folder_id == $box_root_folder_id): ?>
<div class="dropdown left multiform drop-access">
<select id="multi-access-2" name="multiple[access]" onchange="javascript:{document.getElementById('multi-access-1').value=this.value;}">
<option>Album access</option>
<option value="8">Public</option>
<option value="4">Restricted</option>
<option value="0">Private</option>
</select>
</div>
<input class="button left multiform" type="submit" name="multi-submit" value="Grant">
<?php endif; ?>
<div class="dropdown drop-move multiform left">
<select id="multi-move-2" name="multiple[move]" onchange="javascript:{document.getElementById('multi-move-1').value=this.value;}">
<option selected>Move to Location</option>
<?php echo $move_list; ?>
</select>
</div>
<input class="button left multiform" type="submit" name="multiple[submit]" value="Move">
<input class="button right delete" type="submit" name="multiple[submit]" value="Delete" onclick="return confirmAct();">
</div>

</form>

<div class="admin-folder-nav clearfix">

<?php
if ($p > 1)
  echo '<div id="prev"><a href="folder.php?fid=',$folder_id,($p == 2 ? '' : '&amp;p='.($p - 1)),'" title="Previous">← Previous</a></div>';
if ($p * $admin_folder_limit < $n)
  echo '<div id="next"><a href="folder.php?fid=',$folder_id,'&amp;p='.($p + 1),'" title="Next">Next →</a></div>';
?>

<div class="pager">Page <form method="GET" action="folder.php"><input type="hidden" name="fid" value="<?php echo $folder_id; ?>"><input name="p" value="<?php echo $p; ?>"></form> of <?php echo max(1, ceil($n / $admin_folder_limit)); ?></div>

</div>

<div class="admin-folder-nav clearfix">

<?php if ($folder_id !== $box_root_folder_id) { ?>
<div class="admin-folder-parent" id="parent"><a href="<?php echo $base_url; ?>admin/folder.php<?php if ($folder['parent']['id'] !== $box_root_folder_id) echo '?fid=',$folder['parent']['id']; ?>">&lt;&lt;&nbsp;<?php if ($folder['parent']['id'] !== $box_root_folder_id) echo $folder['parent']['name']; else echo 'My Albums'; ?></a>
<span class="edit-admin"><a href="#jumpto" onclick="toggleShow('all-album-list')">Jump to..</a></span></div>
<?php } ?>

<form method="post" class="admin-folder-form right" action="folder.php?ref=<?php echo urlencode($base_url.'admin/folder.php?fid='.$folder_id); ?>">
Items per page:<input class="admin-folder-limit" name="admin_folder_limit" value="<?php echo $admin_folder_limit; ?>">
</form>
</div>

</div>
</div>
<script type="text/javascript">
function hide(id) {
 document.getElementById(id).style.display = "none";
};
function show(id) {
 document.getElementById(id).style.display = "block";
};
function toggleShow(id) {
  if ((document.getElementById(id).style.display) == "block") {
    document.getElementById(id).style.display = "none";
  } else {
    document.getElementById(id).style.display = "block";
  }
};
function getRandomString(stringLength)
{
    var ret = "";
    for (var i=0; i<stringLength; i++){
        var randNumb = 58; 
        while (randNumb> 57 && randNumb < 65) { 
            randNumb = Math.floor(Math.random() * (90 - 48) + 48);
        }
        ret += String.fromCharCode(randNumb);
    }
    return ret;
}
function confirmAct()
{
    if(confirm('Are you sure to delete the file? It is impossible to undo this.'))
    {
        return true;
    }
    return false;
}
function selectAll(a) {
    var theForm = document.myForm;
    for (i=0; i<theForm.elements.length; i++) {
        if (theForm.elements[i].name.indexOf('multiple[') == 0)
            theForm.elements[i].checked = a;
        if (theForm.elements[i].name=='select-all')
            theForm.elements[i].checked = a;
    }
}
</script>

<?php
include('foot.php');
?>
