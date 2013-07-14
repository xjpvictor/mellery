<?php
define('includeauth',true);
include_once('../data/config.php');
include_once($base_dir.'functions.php');

$auth=auth($username);
$url = getpageurl();
if ($auth !== 'pass') {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.$url;
  $redirect_message = 'Access restricted';
  include($includes_dir.'redirect.php');
  exit(0);
}
  
$header_string=boxauth();
$box_cache=boxcache();
$folder_list = getfolderlist();
$data_file = $data_dir.'folder.php';

if (!empty($_GET) && array_key_exists('code',$_GET)) {
  if ($_GET['code'] == '1' && array_key_exists('fid',$_GET) && !empty($_GET['fid']) && array_key_exists('otp',$_GET)) {
    $otp_session=$_GET['otp'];
    if (!verifykey($otp_session,$expire_session,null)) {
      header("HTTP/1.1 403 Forbidden");
      include($includes_dir.'403.php');
      exit(0);
    }

    if ($_GET['fid'] == 'general') {
      echo $general_access_code;
      exit(0);
    }

    $id='id-'.$_GET['fid'];
    if (!array_key_exists($id,$folder_list)) {
      header("Status: 404 Not Found");
      include($includes_dir.'404.php');
      exit(0);
    }

    $code = $folder_list[$id]['access']['specific']['code'];
    if (!empty($code))
      echo $code;
    else
      echo 'No access code specified';
    exit(0);
  } else {
    header("HTTP/1.1 403 Forbidden");
    include($includes_dir.'403.php');
    exit(0);
  }
}

if (!empty($_POST) && array_key_exists('admin_folder_limit', $_POST)) {
  if (!empty($_POST['admin_folder_limit'])) {
    changeconf(array('admin_folder_limit' => $_POST['admin_folder_limit']));
  }
  if (array_key_exists('ref', $_GET) && !empty($_GET['ref']))
    $redirect_url = urldecode($_GET['ref']);
  else
    $redirect_url = $basedir.'admin/folder.php';
  header("Location: $redirect_url");
  exit(0);
}

if (!empty($_POST) && array_key_exists('otp',$_POST)) {
  $otp_session=$_POST['otp'];
  if (!verifykey($otp_session,$expire_session,null)) {
    header("HTTP/1.1 403 Forbidden");
    include($includes_dir.'403.php');
    exit(0);
  }

  if (array_key_exists('multi-submit', $_POST)) {
    $update = false;
    if ($_POST['multi-submit'] == 'Grant') {
      if (array_key_exists('multi-access', $_POST) && ($_POST['multi-access'] == 'public' || $_POST['multi-access'] == 'general' || $_POST['multi-access'] == 'private')) {
        foreach ($_POST['multiple'] as $id) {
          if (array_key_exists('id-'.$id, $folder_list)) {
            if ($_POST['multi-access'] == 'public') {
              $folder_list['id-'.$id]['access']['public'][0] = '1';
              $update = true;
            } elseif ($_POST['multi-access'] == 'general') {
              $folder_list['id-'.$id]['access']['public'][0] = '0';
              $folder_list['id-'.$id]['access']['general'][0] = '1';
              $update = true;
            } elseif ($_POST['multi-access'] == 'private') {
              $folder_list['id-'.$id]['access']['public'][0] = '0';
              $folder_list['id-'.$id]['access']['general'][0] = '0';
              $folder_list['id-'.$id]['access']['specific'][0] = '0';
              $folder_list['id-'.$id]['access']['temporary'][0] = '0';
              $update = true;
            }
            if ($update)
              $folder_list['id-'.$id]['new'] = '0';
          }
        }
      }
      if ($update) {
        $_SESSION['message'] = 'Updated';
        file_put_contents($data_file, "<?php return ".var_export($folder_list,true). "; ?>", LOCK_EX);
      }
    } elseif ($_POST['multi-submit'] == 'Move') {
      if (array_key_exists('multi-move', $_POST) && preg_match('/^\d+$/', $_POST['multi-move']) && array_key_exists('multi-move-ori', $_POST) && preg_match('/^\d+$/', $_POST['multi-move-ori']) && $_POST['multi-move'] !== $_POST['multi-move-ori']) {
        foreach ($_POST['multiple'] as $id) {
          if ($id) {
            if (array_key_exists('id-'.$id, $folder_list))
              $type = 'folder';
            else
              $type = 'file';
            $move_files = movefile($id, $_POST['multi-move'], $type);
            if ($move_files)
              $_SESSION['message'] = 'Moved';
          }
        }
      }
    } elseif ($_POST['multi-submit'] == 'Delete') {
      foreach ($_POST['multiple'] as $id) {
        if ($id) {
          if (array_key_exists('id-'.$id, $folder_list))
            $type = 'folder';
          else
            $type = 'file';
          $delete_files = deletefile($id,$type);
          if (empty($delete_files))
            $_SESSION['message'] = 'Deleted';
        }
      }
    }
  } elseif (array_key_exists('new', $_POST) && $_POST['new'] == 'New Album') {
    if (array_key_exists('name', $_POST) && !empty($_POST['name']) && array_key_exists('dest', $_POST) && preg_match('/^\d+$/', $_POST['dest'])) {
      $response = newfolder($_POST['name'], $_POST['dest']);
      if (isset($response) && !empty($response) && $response['type'] !== 'error')
        $_SESSION['message'] = 'Album created';
      else
        $_SESSION['message'] = 'Error occurs';
    }
  } else {
    $update_folder_list = false;
    foreach ($_POST['single'] as $id => $item) {
      if (array_key_exists('submit', $item) && $item['submit'] == 'Delete') {
        $delete = false;
        if (array_key_exists('type', $item) && $item['type'] == 'folder' && array_key_exists('id-'.$id,$folder_list)) {
          $type = 'folder';
          $delete = true;
        } elseif (array_key_exists('type', $item) && $item['type'] == 'file' && array_key_exists('fid', $item)&& array_key_exists('id-'.$item['fid'],$folder_list)) {
          $type = 'file';
          $delete = true;
        }
        if ($delete) {
          $delete_files = deletefile($id,$type);
          if (empty($delete_files))
            $_SESSION['message'] = 'Deleted';
        }
      } elseif (array_key_exists('submit', $item) && $item['submit'] == 'Update') {
        if (array_key_exists('type', $item) && $item['type'] == 'folder' && array_key_exists('id-'.$id,$folder_list)) {
          $list = $folder_list;
          $update_folder = true;
          $update_folder_list = true;
        } elseif (array_key_exists('type', $item) && $item['type'] == 'file' && array_key_exists('fid', $item)&& array_key_exists('id-'.$item['fid'],$folder_list)) {
          $list = getfilelist($item['fid'],null,null);
          $update_folder = false;
        } else {
          header("Status: 404 Not Found");
          include($includes_dir.'404.php');
          exit(0);
        }

        $update_detail = false;
        $id = 'id-'.$id;
        if (array_key_exists('name',$item) && $item['name'] !== $list[$id]['name'] && ($item['name'] == '0' || !empty($item['name']))) {
          if (!$update_folder)
            $list[$id]['name'] = $item['name'] . substr($list[$id]['name'], strrpos($list[$id]['name'], '.', -1));
          else
            $list[$id]['name'] = $item['name'];
          $update_detail = true;
        }
        if (array_key_exists('description',$item) && $item['description'] !== $list[$id]['description']) {
          $list[$id]['description'] = $item['description'];
          $update_detail = true;
        }
        if ($update_detail)
          updatedetail($list[$id]['id'], array('name' => $list[$id]['name'], 'description' => $list[$id]['description']), $item['type']);

        if (array_key_exists('move',$item) && $item['move'] !== $list[$id]['parent']['id']) {
          movefile($list[$id]['id'], $item['move'], $list[$id]['type']);
        }

        if ($update_folder) {
          $access = $list[$id]['access'];
          if ($item['public'] == 'private') {
            foreach ($access as $key => $value) {
              if (array_key_exists($key,$item)) {
                $list[$id]['access'][$key][0] = '0';
              }
            }
          } elseif ($item['public'] == '1') {
            $list[$id]['access']['public'][0] = '1';
          } else {
            foreach ($access as $key => $value) {
              if (array_key_exists($key,$item)) {
                $list[$id]['access'][$key][0] = $item[$key];
              }
            }
            if (array_key_exists('specific-code',$item) && $item['specific-code'] !== 'Leave blank if not changed') {
              $list[$id]['access']['specific']['code'] = $item['specific-code'];
            }
            if (array_key_exists('temporary-code',$item) && $item['temporary-code'] !== 'Specify an access code') {
              $list[$id]['access']['temporary']['code'] = $item['temporary-code'];
            }
            if (array_key_exists('temporary-time',$item) && is_numeric($item['temporary-time']) && $item['temporary-time'] >= 0) {
              $list[$id]['access']['temporary']['time'] = $item['temporary-time'] * 3600 + time();
            }
          }
          $list[$id]['new'] = '0';
        }

        $_SESSION['message'] = 'Updated';
      }
    }
    if ($update_folder_list)
      file_put_contents($data_file, "<?php return ".var_export($list,true). "; ?>", LOCK_EX);
  }

  if (array_key_exists('ref', $_GET) && !empty($_GET['ref']))
    $redirect_url = urldecode($_GET['ref']);
  else
    $redirect_url = $basedir.'admin/folder.php';
  session_regenerate_id(true);
  header("Location: $redirect_url");
  exit(0);
}
?>

<?php
if (file_exists($data_dir.'my_page.php'))
  $my_page = include($data_dir.'my_page.php');
include('head.php');

$otp=getkey($expire_image);
$otp_session=getkey($expire_session);

if (empty($_GET) || !array_key_exists('p', $_GET) || empty($_GET['p']))
  $p = '0';
else
  $p = $_GET['p'];
if (empty($_GET) || !array_key_exists('fid', $_GET) || empty($_GET['fid']) || !array_key_exists('id-'.$_GET['fid'], $folder_list))
  $_GET['fid'] = $box_root_folder_id;

$move_list = '';
$all_album_list = '<ul>';
foreach ($folder_list as $id => $folder) {
  if ($folder['id'] == $box_root_folder_id) {
    $move_list .= '<option value="'.$folder['id'].'">Box.com</option>'."\n";
    $all_album_list .= '<li><span><a href="'.$base_url.'admin/folder.php?fid='.$folder['id'].'">Albums list</a></span></li>'."\n";
  } else {
    $move_list .= '<option value="'.$folder['id'].'">'.$folder['name'].'</option>'."\n";
    $all_album_list .= '<li><span><a href="'.$base_url.'admin/folder.php?fid='.$folder['id'].'">'.$folder['name'].'</a></span></li>'."\n";
  }
}
$all_album_list .= '</ul>';

if ($_GET['fid'] !== $box_root_folder_id) {
  $folder = $folder_list['id-'.$_GET['fid']];
  $list = getfilelist($_GET['fid'], null, null);
  $n = count($list);
  $list = array_slice($list, $p * $admin_folder_limit, $admin_folder_limit);
  $single = true;
} else {
  if (array_key_exists('id-'.$box_root_folder_id, $folder_list))
    unset($folder_list['id-'.$box_root_folder_id]);
  $list = getfilelist($_GET['fid'], null, null);
  $n1 = count($folder_list);
  $n2 = count($list) - $n1;
  $folder_list = array_merge($list, $folder_list);
  $n = count($folder_list);
  $new = array();
  foreach ($folder_list as $id => $folder) {
    if (isset($folder['new']) && $folder['new'] == 1)
      $new = array_merge($new, array($id => $folder));
  }
  $list = array_merge($new, $list);
  $list = array_slice($list, $p * $admin_folder_limit, $admin_folder_limit);
  $single = false;
}

if ($single) {
  $access=$folder['access'];
?>
<div class="admin-folder-nav clearfix" id="<?php echo $folder['id']; ?>">

<a href="<?php echo $base_url; ?>?id=<?php echo $folder['id']; ?>"><?php echo $folder['name']; ?></a>
(<?php echo $folder['total_count']; ?> items, <?php if (file_exists($stat_dir.$folder['id'])) echo file_get_contents($stat_dir.$folder['id'], true); else echo '0'; ?> views)
<?php if ($folder['new'] == 1) { ?>
<span class="new">NEW</span>
<?php } ?>
<span class="edit-admin"><a href="javascript:;" onclick="show('admin-folder-<?php echo $folder['id']; ?>')">Edit Album</a></span>
<p class="meta-admin">Created: <?php echo date('d. F Y', strtotime($folder['created_at'])); ?><br/>Modified: <?php echo date('d. F Y', strtotime($folder['modified_at'])); ?></p>

<form class="right" method="post" action="folder.php?ref=<?php echo urlencode($base_url.'admin/folder.php?fid='.$_GET['fid']); ?>">
<input name="name">
<input type="hidden" name="dest" value="<?php echo $_GET['fid']; ?>">
<input type="hidden" name="otp" value="<?php echo $otp_session; ?>">
<input class="button" type="submit" name="new" value="New Album">
</form><br/>

<div class="admin-folder clearfix" id="admin-folder-<?php echo $folder['id']; ?>" style="display:none;">

<img class="admin-album" src="<?php echo $base_url; ?>cover.php?fid=<?php echo $folder['id']; ?>&amp;w=<?php echo $w; ?>&amp;h=<?php echo $h; ?>&amp;otp=<?php echo $otp; ?>" alt="<?php echo $folder['name']; ?>" title="<?php echo $folder['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" />

<div class="admin-access">
<form style="padding:10px;line-height:25px;vertical-align:middle;" method="POST" action="folder.php?ref=<?php echo $url; ?>">

<p>Name:</p><input class="name-conf" name="single[<?php echo $folder['id']; ?>][name]" value="<?php echo $folder['name']; ?>"><br/>
<p>Description:</p><textarea rows="3" class="description-conf" name="single[<?php echo $folder['id']; ?>][description]"><?php echo $folder['description']; ?></textarea><br/>

<p>Access:</p>
<label><input class="radio" type="radio" name="single[<?php echo $folder['id']; ?>][public]" value="1"<?php if ($access['public'][0] == 1) echo ' checked'; ?> onclick="hide('extra-<?php echo $folder['id']; ?>')">Public access</label><br/>
<label><input class="radio" type="radio" name="single[<?php echo $folder['id']; ?>][public]" value="0"<?php if ($access['public'][0] == 0 && ($access['general'][0] == 1 || $access['specific'][0] == 1 || $access['temporary'][0] == 1)) echo ' checked'; ?> onclick="show('extra-<?php echo $folder['id']; ?>')">Restrict access</label><br/>

<div class="extra" id="extra-<?php echo $folder['id']; ?>">
<input type="hidden" name="single[<?php echo $folder['id']; ?>][general]" value="0"><label><input class="checkbox" type="checkbox" name="single[<?php echo $folder['id']; ?>][general]" value="1" <?php if ($access['general'][0] == 1) echo ' checked'; ?>>Access with general access code</label><br/>
<input type="hidden" name="single[<?php echo $folder['id']; ?>][specific]" value="0"><label><input class="checkbox" type="checkbox" name="single[<?php echo $folder['id']; ?>][specific]" value="1"<?php if ($access['specific'][0] == 1) echo ' checked'; ?>>Specify an access code: </label><input id="specific-code-<?php echo $folder['id']; ?>" type="text" style="width:180px;font-size:12px;" name="single[<?php echo $folder['id']; ?>][specific-code]" value="Leave blank if not changed" onfocus="if(this.value==this.defaultValue)this.value='';" onblur="if(this.value=='')this.value=this.defaultValue;"> or
<input class="button" type="button" name="single[<?php echo $folder['id']; ?>][specific-code-generate]" value="Generate randomly" onclick="document.getElementById ('specific-code-<?php echo $folder['id']; ?>').value=(getRandomString(8))"><br/>
<p class="button" style="margin-left:25px;width:180px;"><a href="<?php echo $base_url; ?>admin/folder.php?code=1&amp;fid=<?php echo $folder['id']; ?>&amp;otp=<?php echo $otp_session; ?>" target="_blank">Show current access code</a></p>
<input type="hidden" name="single[<?php echo $folder['id']; ?>][temporary]" value="0"><label><input class="checkbox" type="checkbox" name="single[<?php echo $folder['id']; ?>][temporary]" value="1"<?php if ($access['temporary'][0] == 1) echo ' checked'; ?>>Grant temporary access for </label><input type="text" style="width:30px;" name="single[<?php echo $folder['id']; ?>][temporary-time]" value="24"> hours<br/><input id="temporary-code-<?php echo $folder['id']; ?>" type="text" style="margin-left:25px;width:180px;font-size:12px;" name="single[<?php echo $folder['id']; ?>][temporary-code]" value="Specify an access code" onfocus="if(this.value==this.defaultValue)this.value='';" onblur="if(this.value=='')this.value=this.defaultValue;"> or <input class="button" type="button" name="single[<?php echo $folder['id']; ?>][temporary-code-generate]" value="Generate temporary access code" onclick="document.getElementById ('temporary-code-<?php echo $folder['id']; ?>').value=(getRandomString(8))"><br/>
<p style="padding-left:25px;" class="small">* Specifying new access code will revoke the original one</p>
</div>

<label><input class="radio" type="radio" name="single[<?php echo $folder['id']; ?>][public]" value="private"<?php if ($access['public']['0'] == 0 && $access['general'][0] == 0 && $access['specific'][0] == 0 && $access['temporary'][0] == 0) echo ' checked'; ?> onclick="hide('extra-<?php echo $folder['id']; ?>')">Private</label><br/>

<p>Location:</p>
<div class="dropdown drop-move">
<select name="single[<?php echo $folder['id']; ?>][move]">
<?php echo str_replace($folder['parent']['id'].'"', $folder['parent']['id'].'" selected', $move_list); ?>
</select>
</div>

<br/><p>Embed:</p>
<p>Folder cover</p>
<input class="name-conf" value="<?php echo htmlentities('<a href="'.$base_url.'?id='.$folder['id'].'" target="_blank"><img src="'.$base_url.'cover.php?fid='.$folder['id'].'&w='.$w.'&h='.$h.'&otp='.substr(hash('sha256', $secret_key.$folder['id']), 13, 15).'" alt="'.$folder['name'].'" title="'.$folder['name'].'" width="'.$w.'" height="'.$h.'" /></a>'); ?>" onclick="this.select()"><br/>
<p>Folder preview</p>
<input class="name-conf" value="<?php echo htmlentities('<iframe src="'.$base_url.'frame.php?fid='.$folder['id'].'&limit=6" width="540" height="480" allowtransparency="true" seamless scrolling="auto" frameborder="0">'.$folder['name'].'</iframe>'); ?>" onclick="this.select()"><br/>

<br/><input class="button" type="submit" name="single[<?php echo $folder['id']; ?>][submit]" value="Update">
<input class="button right delete" type="submit" name="single[<?php echo $folder['id']; ?>][submit]" value="Delete" onclick="return confirmAct();">

<input type="hidden" name="single[<?php echo $folder['id']; ?>][type]" value="folder">
<input type="hidden" name="otp" value="<?php echo $otp_session; ?>">
</form>

</div></div></div>

<div class="admin-folder-nav clearfix">
<div class="admin-folder-parent">
<a href="<?php echo $base_url; ?>admin/folder.php<?php if ($folder['parent']['id'] !== $box_root_folder_id) echo '?fid=',$folder['parent']['id']; ?>">&lt;&lt;&nbsp;<?php if ($folder['parent']['id'] !== $box_root_folder_id) echo $folder['parent']['name']; else echo 'Albums list'; ?></a>
<div class="edit-admin" id="jumpto">
<a href="javascript:;" onclick="show('all-album-list')">Jump to..</a>
<div id="all-album-list">
<a class="close" href="javascript:;" onclick="show('all-album-list')">[Close]</a><br/><?php echo $all_album_list; ?>
</div>
</div>
</div>

<?php
} else {
?>
<div class="admin-folder-nav clearfix">
Albums list (<?php echo $n1; ?> albums<?php if ($n2 > 0) echo ', ',$n2,' images'; ?>)
<form class="right" method="post" action="folder.php?ref=<?php echo urlencode($base_url.'admin/folder.php?fid='.$_GET['fid']); ?>">
<input name="name">
<input class="button" type="submit" name="new" value="New Album">
<input type="hidden" name="dest" value="<?php echo $_GET['fid']; ?>">
<input type="hidden" name="otp" value="<?php echo $otp_session; ?>">
</form>
</div>

<div class="admin-folder-nav clearfix">

<?php
}
?>

<form method="post" class="admin-folder-form right" action="folder.php?ref=<?php echo urlencode($base_url.'admin/folder.php?fid='.$_GET['fid']); ?>">
Items per page:<input class="admin-folder-limit" name="admin_folder_limit" value="<?php echo $admin_folder_limit; ?>">
</form>

<div id="pager-top">
<?php
if ($p > 0)
  echo '<div class="prev"><a href="folder.php?fid='.$_GET['fid'].'&amp;p='.($p - 1).'" title="Previous">← Previous</a></div>';
if (($p + 1) * $admin_folder_limit < $n)
  echo '<div class="next"><a href="folder.php?fid='.$_GET['fid'].'&amp;p='.($p + 1).'" title="Next">Next →</a></div>';
?>
<div class="pager">Page <?php echo ($p + 1); ?> of <?php echo max(1, ceil($n / $admin_folder_limit)); ?></div>
</div>

</div>

<form name="myForm" method="POST" action="folder.php?ref=<?php echo $url; ?>">

<input style="display:none;" type="submit" name="none" value="None" onclick="return false;">
<div class="admin-folder-nav clearfix">

<label class="left"><input class="checkbox" type="checkbox" name="select-all" onclick="if(this.checked==true) {selectAll(1); } else {selectAll(0); }"><span class="multiform">All/None</span></label>

<div class="dropdown left multiform drop-access">
<select id="multi-access-1" name="multi-access" onchange="javascript:{document.getElementById('multi-access-2').value=this.value;}">
<option>Album access</option>
<option value="public">Public</option>
<option value="general">Restricted</option>
<option value="private">Private</option>
</select>
</div>

<input class="button left multiform" type="submit" name="multi-submit" value="Grant">
<div class="dropdown drop-move multiform left">
<select id="multi-move-1" name="multi-move" onchange="javascript:{document.getElementById('multi-move-2').value=this.value;}">
<option selected>Move to Location</option>
<?php echo $move_list; ?>
</select>
</div>

<input type="hidden" name="multi-move-ori" value="<?php echo $_GET['fid']; ?>">
<input class="button left multiform" type="submit" name="multi-submit" value="Move">
<input class="button right delete" type="submit" name="multi-submit" value="Delete" onclick="return confirmAct();">

</div>

<?php foreach ($list as $id => $item) { ?>
<div class="site-config clearfix" id="<?php echo $item['id']; ?>">
<input class="checkbox" type="checkbox" name="multiple[]" value="<?php echo $item['id']; ?>">

<?php
  if ($item !== 'error' && $item['type'] == 'folder') {
    $access=$folder_list[$id]['access'];
?>

  <a href="<?php echo $base_url; ?>?id=<?php echo $item['id']; ?>"><?php echo $item['name']; ?></a> (<?php echo $folder_list[$id]['total_count']; ?> items, <?php if (file_exists($stat_dir.$item['id'])) echo file_get_contents($stat_dir.$item['id'], true); else echo '0'; ?> views)

<?php if ($folder_list[$id]['new'] == 1) { ?>
   <span class="new">NEW</span>
<?php } ?>

   <span class="edit-admin"><a href="folder.php?fid=<?php echo $item['id']; ?>">Manage Album</a></span>
   <br/><img class="admin-album" src="<?php echo $base_url; ?>cover.php?fid=<?php echo $item['id']; ?>&amp;w=<?php echo $w; ?>&amp;h=<?php echo $h; ?>&amp;otp=<?php echo $otp; ?>" alt="<?php echo $item['name']; ?>" title="<?php echo $item['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" />

<?php
  } elseif ($item !== 'error' && $item['type'] == 'file') {
?>

  <a href="<?php echo $base_url; ?>image.php?id=<?php echo $item['id']; ?>"><?php echo $item['name']; ?></a> ( <?php if (file_exists($stat_dir.$item['id'])) echo file_get_contents($stat_dir.$item['id'], true); else echo '0'; ?> views)

<?php
    $item['name'] = substr($item['name'], 0, strrpos($item['name'], '.', -1));
?>
  <br/><img class="admin-img" src="<?php echo $base_url; ?>thumbnail.php?id=<?php echo $item['id']; ?>&amp;w=150&amp;h=150&amp;otp=<?php echo $otp; ?>" alt="<?php echo $item['name']; ?>" title="<?php echo $item['name']; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" />

<?php
  }
?>

  <div class="admin-access"><div class="admin-form">
  <p>Name:</p><input class="name-conf" name="single[<?php echo $item['id']; ?>][name]" value="<?php echo $item['name']; ?>"><br/>
  <p>Description:</p><textarea rows="3" class="description-conf" name="single[<?php echo $item['id']; ?>][description]"><?php echo $item['description']; ?></textarea><br/>

<?php if ($item !== 'error' && $item['type'] == 'folder') { ?>
    <p>Access:</p>
    <label><input class="radio" type="radio" name="single[<?php echo $item['id']; ?>][public]" value="1"<?php if ($access['public'][0] == 1) echo ' checked'; ?> onclick="hide('extra-<?php echo $id; ?>')">Public access</label><br/>
    <label><input class="radio" type="radio" name="single[<?php echo $item['id']; ?>][public]" value="0"<?php if ($access['public'][0] == 0 && ($access['general'][0] == 1 || $access['specific'][0] == 1 || $access['temporary'][0] == 1)) echo ' checked'; ?> onclick="show('extra-<?php echo $id; ?>')">Restrict access</label><br/>

    <div class="extra" id="extra-<?php echo $id; ?>">
    <input type="hidden" name="single[<?php echo $item['id']; ?>][general]" value="0"><label><input class="checkbox" type="checkbox" name="single[<?php echo $item['id']; ?>][general]" value="1" <?php if ($access['general'][0] == 1) echo ' checked'; ?>>Access with general access code</label><br/>
    <input type="hidden" name="single[<?php echo $item['id']; ?>][specific]" value="0"><label><input class="checkbox" type="checkbox" name="single[<?php echo $item['id']; ?>][specific]" value="1"<?php if ($access['specific'][0] == 1) echo ' checked'; ?>>Specify an access code: </label><input id="specific-code-<?php echo $id; ?>" type="text" style="width:180px;font-size:12px;" name="single[<?php echo $item['id']; ?>][specific-code]" value="Leave blank if not changed" onfocus="if(this.value==this.defaultValue)this.value='';" onblur="if(this.value=='')this.value=this.defaultValue;"> or
   <input class="button" type="button" name="single[<?php echo $item['id']; ?>][specific-code-generate]" value="Generate randomly" onclick="document.getElementById ('specific-code-<?php echo $id; ?>').value=(getRandomString(8))"><br/>
    <p class="button" style="margin-left:25px;width:180px;"><a href="<?php echo $base_url; ?>admin/folder.php?code=1&amp;fid=<?php echo $item['id']; ?>&amp;otp=<?php echo $otp_session; ?>" target="_blank">Show current access code</a></p>
    <input type="hidden" name="single[<?php echo $item['id']; ?>][temporary]" value="0"><label><input class="checkbox" type="checkbox" name="single[<?php echo $item['id']; ?>][temporary]" value="1"<?php if ($access['temporary'][0] == 1) echo ' checked'; ?>>Grant temporary access for </label><input type="text" style="width:30px;" name="single[<?php echo $item['id']; ?>][temporary-time]" value="24"> hours<br/><input id="temporary-code-<?php echo $id; ?>" type="text" style="margin-left:25px;width:180px;font-size:12px;" name="single[<?php echo $item['id']; ?>][temporary-code]" value="Specify an access code" onfocus="if(this.value==this.defaultValue)this.value='';" onblur="if(this.value=='')this.value=this.defaultValue;"> or <input class="button" type="button" name="single[<?php echo $item['id']; ?>][temporary-code-generate]" value="Generate temporary access code" onclick="document.getElementById ('temporary-code-<?php echo $id; ?>').value=(getRandomString(8))"><br/>
    <p style="padding-left:25px;" class="small">* Specifying new access code will revoke the original one</p>
    </div>
    <label><input class="radio" type="radio" name="single[<?php echo $item['id']; ?>][public]" value="private"<?php if ($access['public']['0'] == 0 && $access['general'][0] == 0 && $access['specific'][0] == 0 && $access['temporary'][0] == 0) echo ' checked'; ?> onclick="hide('extra-<?php echo $id; ?>')">Private</label><br/>
    <input type="hidden" name="single[<?php echo $item['id']; ?>][type]" value="folder">

<?php } elseif ($item !== 'error' && $item['type'] == 'file') { ?>
    <input type="hidden" name="single[<?php echo $item['id']; ?>][fid]" value="<?php echo $item['parent']['id']; ?>">
    <input type="hidden" name="single[<?php echo $item['id']; ?>][type]" value="file">

<?php } ?>

  <p>Location:</p>
  <div class="dropdown drop-move">
  <select name="single[<?php echo $item['id']; ?>][move]">
<?php echo str_replace($item['parent']['id'].'"', $item['parent']['id'].'" selected', $move_list); ?>
  </select>
  </div>

<?php if ($item !== 'error' && $item['type'] == 'folder') { ?>
  <br/><p>Embed:</p>
  <p>Folder cover</p>
  <input class="name-conf" value="<?php echo htmlentities('<a href="'.$base_url.'?id='.$item['id'].'" target="_blank"><img src="'.$base_url.'cover.php?fid='.$item['id'].'&w='.$w.'&h='.$h.'&otp='.substr(hash('sha256', $secret_key.$item['id']), 13, 15).'" alt="'.$item['name'].'" title="'.$item['name'].'" width="'.$w.'" height="'.$h.'" /></a>'); ?>" onclick="this.select()"><br/>
  <p>Folder preview</p>
  <input class="name-conf" value="<?php echo htmlentities('<iframe src="'.$base_url.'frame.php?fid='.$item['id'].'&limit=6" width="540" height="480" allowtransparency="true" scrolling="auto" seamless frameborder="0">'.$item['name'].'</iframe>'); ?>" onclick="this.select()"><br/>
<?php } elseif ($item !== 'error' && $item['type'] == 'file') { ?>
  <br/><p>Embed:</p><p>Image Thumbnail</p><input class="name-conf" value="<?php echo htmlentities('<a href="'.$base_url.'image.php?id='.$item['id'].'" target="_blank"><img src="'.$base_url.'thumbnail.php?id='.$item['id'].'&w='.$w.'&h='.$h.'&otp='.substr(hash('sha256', $secret_key.$item['id']), 13, 15).'" alt="'.$item['name'].'" title="'.$item['name'].'" width="'.$w.'" height="'.$h.'" /></a>'); ?>" onclick="this.select()"><br/>
<?php } ?>

  <br/><input class="button" type="submit" name="single[<?php echo $item['id']; ?>][submit]" value="Update">
  <input class="button right delete" type="submit" name="single[<?php echo $item['id']; ?>][submit]" value="Delete" onclick="return confirmAct();">
  <div class="clear"></div>
  </div></div>
  </div>

<?php } ?>

<input type="hidden" name="otp" value="<?php echo $otp_session; ?>">

<div class="admin-folder-nav clearfix">
<label class="left"><input class="checkbox" type="checkbox" name="select-all" onclick="if(this.checked==true) {selectAll(1); } else {selectAll(0); }"><span class="multiform">All/None</span></label>
<div class="dropdown left multiform drop-access">
<select id="multi-access-2" name="multi-access" onchange="javascript:{document.getElementById('multi-access-1').value=this.value;}">
<option>Album access</option>
<option value="public">Public</option>
<option value="general">Restricted</option>
<option value="private">Private</option>
</select>
</div>
<input class="button left multiform" type="submit" name="multi-submit" value="Grant">
<div class="dropdown drop-move multiform left">
<select id="multi-move-2" name="multi-move" onchange="javascript:{document.getElementById('multi-move-1').value=this.value;}">
<option selected>Move to Location</option>
<?php echo $move_list; ?>
</select>
</div>
<input type="hidden" name="multi-move-ori" value="<?php echo $_GET['fid']; ?>">
<input class="button left multiform" type="submit" name="multi-submit" value="Move">
<input class="button right delete" type="submit" name="multi-submit" value="Delete" onclick="return confirmAct();">
</div>

</form>

<div class="admin-folder-nav clearfix">

<?php
if ($p > 0)
  echo '<div id="prev"><a href="folder.php?fid=',$_GET['fid'],'&amp;p='.($p - 1),'" title="Previous">← Previous</a></div>';
if (($p + 1) * $admin_folder_limit < $n)
  echo '<div id="next"><a href="folder.php?fid=',$_GET['fid'],'&amp;p='.($p + 1),'" title="Next">Next →</a></div>';
?>

<div class="pager">Page <?php echo ++$p; ?> of <?php echo max(1, ceil($n / $admin_folder_limit)); ?></div>

</div>

<div class="admin-folder-nav clearfix">

<?php if($single) { ?>
<div class="admin-folder-parent" id="parent"><a href="<?php echo $base_url; ?>admin/folder.php<?php if ($folder['parent']['id'] !== $box_root_folder_id) echo '?fid=',$folder['parent']['id']; ?>">&lt;&lt;&nbsp;<?php if ($folder['parent']['id'] !== $box_root_folder_id) echo $folder['parent']['name']; else echo 'Albums list'; ?></a>
<span class="edit-admin"><a href="#jumpto" onclick="show('all-album-list')">Jump to..</a></span></div>
<?php } ?>

<form method="post" class="admin-folder-form right" action="folder.php?ref=<?php echo urlencode($base_url.'admin/folder.php?fid='.$_GET['fid']); ?>">
Items per page:<input class="admin-folder-limit" name="admin_folder_limit" value="<?php echo $admin_folder_limit; ?>">
</form>
</div>

</div>
<script type="text/javascript">
function hide(id) {
 document.getElementById(id).style.display = "none";
};
function show(id) {
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
        if (theForm.elements[i].name=='multiple[]')
            theForm.elements[i].checked = a;
        if (theForm.elements[i].name=='select-all')
            theForm.elements[i].checked = a;
    }
}
</script>

<?php
include('foot.php');
?>
