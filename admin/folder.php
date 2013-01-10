<?php
define('includeauth',true);
include_once('../functions.php');

$auth=auth($username);
$url = getpageurl();
if ($auth !== 'pass') {
  header("HTTP/1.1 401 Unauthorized");
  $redirect_url = $base_url.'admin/login.php?ref='.$url;
  $redirect_message = 'Access restricted';
  include($base_dir."library/redirect.php");
  exit(0);
}
  
$header_string=boxauth();
$box_cache=boxcache();
$folder_list = getfolderlist();
$data_file = $data_dir.'folder.php';

if (!empty($_GET) && array_key_exists('code',$_GET)) {
  if ($_GET['code'] == '1' && array_key_exists('id',$_GET) && !empty($_GET['id']) && array_key_exists('otp',$_GET)) {
    $otp_session=$_GET['otp'];
    if (!verifykey($otp_session,$expire_session,null)) {
      header("HTTP/1.1 403 Forbidden");
      include($base_dir."library/403.php");
      exit(0);
    }

    if ($_GET['id'] == 'general') {
      echo $general_access_code;
      exit(0);
    }

    $id='id-'.$_GET['id'];
    if (!array_key_exists($id,$folder_list)) {
      header("Status: 404 Not Found");
      include($base_dir."library/404.php");
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
    include($base_dir."library/403.php");
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
    include($base_dir."library/403.php");
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
          include($base_dir."library/404.php");
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

$my_page = include($data_dir.'my_page.php');
include('head.php');
?>
<?php
$otp=getkey($expire_image);
$otp_session=getkey($expire_session);

if (empty($_GET) || !array_key_exists('p', $_GET) || empty($_GET['p']))
  $p = '0';
else
  $p = $_GET['p'];
if (empty($_GET) || !array_key_exists('id', $_GET) || empty($_GET['id']) || !array_key_exists('id-'.$_GET['id'], $folder_list))
  $_GET['id'] = $box_root_folder_id;

$move_list = '';
$all_album_list = '<ul>';
foreach ($folder_list as $id => $folder) {
  if ($folder['id'] == $box_root_folder_id) {
    $move_list .= '<option value="'.$folder['id'].'">Box.com</option>'."\n";
    $all_album_list .= '<li><a href="'.$base_url.'admin/folder.php?id='.$folder['id'].'">Albums list</a></li>'."\n";
  } else {
    $move_list .= '<option value="'.$folder['id'].'">'.$folder['name'].'</option>'."\n";
    $all_album_list .= '<li><a href="'.$base_url.'admin/folder.php?id='.$folder['id'].'">'.$folder['name'].'</a></li>'."\n";
  }
}
$all_album_list .= '</ul>';

if ($_GET['id'] !== $box_root_folder_id) {
  $folder = $folder_list['id-'.$_GET['id']];
  $list = getfilelist($_GET['id'], null, null);
  $n = count($list);
  $list = array_slice($list, $p * $admin_folder_limit, $admin_folder_limit);
  $single = true;
} else {
  if (array_key_exists('id-'.$box_root_folder_id, $folder_list))
    unset($folder_list['id-'.$box_root_folder_id]);
  $new = array();
  foreach ($folder_list as $id => $folder) {
    if ($folder['new'] == 1)
      $new = array_merge($new, array($id => $folder));
  }
  $folder_list = array_merge($new, $folder_list);
  $n = count($folder_list);
  $list = array_slice($folder_list, $p * $admin_folder_limit, $admin_folder_limit);
  $single = false;
}

if ($single) {
  $access=$folder['access'];
  echo '<div class="admin-folder-nav clearfix" id="'.$folder['id'].'"><a href="'.$base_url.'?id='.$folder['id'].'">'.$folder['name'].'</a> ('.$folder['total_count'].' items, ';
  if (file_exists($data_dir.$folder['id']))
    echo file_get_contents($data_dir.$folder['id'], true);
  else
    echo '0';
  echo ' views)';
  if ($folder['new'] == 1)
    echo '<span class="new">NEW</span>';
  echo '<span class="edit-admin"><a href="javascript:;" onclick="show(\'admin-folder-'.$folder['id'].'\')">Manage Album</a></span>';
  echo '<form class="right" method="post" action="folder.php?ref='.urlencode($base_url.'admin/folder.php?id='.$_GET['id']).'">'."\n";
  echo '<input name="name">'."\n";
  echo '<input type="hidden" name="dest" value="'.$_GET['id'].'">'."\n";
  echo '<input type="hidden" name="otp" value="'.$otp_session.'">'."\n";
  echo '<input class="button" type="submit" name="new" value="New Album">'."\n";
  echo '</form>'."\n";
  echo '<br/><div class="admin-folder clearfix" id="admin-folder-'.$folder['id'].'" style="display:none;"><img class="admin-album" src="'.$base_url.'cover.php?id='.$folder['id'].'-'.$folder['sequence_id'].'&amp;w='.$w.'&amp;h='.$h.'&amp;otp='.$otp.'" alt="'.$folder['name'].'" title="'.$folder['name'].'" width="'.$w.'" height="'.$h.'" /><div class="admin-access">';
  if ($access['public'][0] == 1) {
    $public = ' checked';
    $restrict = '';
    $private = '';
  } elseif ($access['general']['0'] == 1 || $access['specific']['0'] == 1 || $access['temporary']['0'] == 1) {
    $public = '';
    $restrict = ' checked';
    $private = '';
  } else {
    $public = '';
    $restrict = '';
    $private = ' checked';
  }
  echo '<form style="padding:10px;line-height:25px;vertical-align:middle;" method="POST" action="folder.php?ref='.$url.'">'."\n";
  echo '<p>Name:</p><input class="name-conf" name="single['.$folder['id'].'][name]" value="'.$folder['name'].'"><br/>'."\n";
  echo '<p>Description:</p><textarea rows="3" class="description-conf" name="single['.$folder['id'].'][description]">'.$folder['description'].'</textarea><br/>'."\n";
  echo '<p>Access:</p>'."\n";
  echo '<label><input class="checkme radio" type="radio" name="single['.$folder['id'].'][public]" value="1"'.$public.' onclick="hide(\'extra-'.$folder['id'].'\')">Public access</label><br/>'."\n";
  echo '<label><input class="checkme radio" type="radio" name="single['.$folder['id'].'][public]" value="0"'.$restrict.' onclick="show(\'extra-'.$folder['id'].'\')">Restrict access</label><br/>'."\n";
  echo '<div class="extra" id="extra-'.$folder['id'].'">'."\n";
  if ($access['general']['0'] == 1)
    $checked = ' checked';
  else
    $checked ='';
  echo '<input type="hidden" name="single['.$folder['id'].'][general]" value="0"><label><input class="checkbox" type="checkbox" name="single['.$folder['id'].'][general]" value="1" '.$checked.'>Access with general access code</label><br/>'."\n";
  if ($access['specific']['0'] == 1)
    $checked = ' checked';
  else
    $checked ='';
  echo '<input type="hidden" name="single['.$folder['id'].'][specific]" value="0"><label><input class="checkbox" type="checkbox" name="single['.$folder['id'].'][specific]" value="1"'.$checked.'>Specify an access code: </label><input id="specific-code-'.$folder['id'].'" type="text" style="width:180px;font-size:12px;" name="single['.$folder['id'].'][specific-code]" value="Leave blank if not changed" onfocus="if(this.value==this.defaultValue)this.value=\'\';" onblur="if(this.value==\'\')this.value=this.defaultValue;"> or'."\n";
 echo '<input class="button" type="button" name="single['.$folder['id'].'][specific-code-generate]" value="Generate randomly" onclick="document.getElementById (\'specific-code-'.$folder['id'].'\').value=(getRandomString(8))"><br/>'."\n";
  echo '<p class="button" style="margin-left:25px;width:180px;"><a href="'.$base_url.'admin/folder.php?code=1&amp;id='.$folder['id'].'&amp;otp='.$otp_session.'" target="_blank">Show current access code</a></p>'."\n";
  if ($access['temporary']['0'] == 1)
    $checked = ' checked';
  else
    $checked ='';
  echo '<input type="hidden" name="single['.$folder['id'].'][temporary]" value="0"><label><input class="checkbox" type="checkbox" name="single['.$folder['id'].'][temporary]" value="1"'.$checked.'>Grant temporary access for </label><input type="text" style="width:30px;" name="single['.$folder['id'].'][temporary-time]" value="24"> hours<br/><input id="temporary-code-'.$folder['id'].'" type="text" style="margin-left:25px;width:180px;font-size:12px;" name="single['.$folder['id'].'][temporary-code]" value="Specify an access code" onfocus="if(this.value==this.defaultValue)this.value=\'\';" onblur="if(this.value==\'\')this.value=this.defaultValue;"> or <input class="button" type="button" name="single['.$folder['id'].'][temporary-code-generate]" value="Generate temporary access code" onclick="document.getElementById (\'temporary-code-'.$folder['id'].'\').value=(getRandomString(8))"><br/>'."\n";
  echo '<p style="font-size:13px;line-height:25px;padding-left:25px;">* Specifying new access code will revoke the original one</p>'."\n";
  echo '</div>'."\n";
  echo '<label><input class="checkme radio" type="radio" name="single['.$folder['id'].'][public]" value="private"'.$private.' onclick="hide(\'extra-'.$folder['id'].'\')">Private</label><br/>'."\n";
  echo '<p>Location:</p>'."\n";
  echo '<div class="dropdown drop-move">'."\n";
  echo '<select name="single['.$folder['id'].'][move]">'."\n";
  echo preg_replace('/'.$folder['parent']['id'].'"/', $folder['parent']['id'].'" selected', $move_list);
  echo '</select>'."\n";
  echo '</div>'."\n";
  echo '<input type="hidden" name="single['.$folder['id'].'][type]" value="folder">'."\n";
  echo '<br/><input class="button" type="submit" name="single['.$folder['id'].'][submit]" value="Update">'."\n";
  echo '<input class="button right delete" type="submit" name="single['.$folder['id'].'][submit]" value="Delete" onclick="return confirmAct();">'."\n";
  echo '<input type="hidden" name="otp" value="'.$otp_session.'">'."\n";
  echo '</form>'."\n";
  echo '</div></div></div>'."\n";
} else {
  echo '<div class="admin-folder-nav clearfix">'."\n";
  echo 'Albums list ('.$n.' albums)'."\n";
  echo '<form class="right" method="post" action="folder.php?ref='.urlencode($base_url.'admin/folder.php?id='.$_GET['id']).'">'."\n";
  echo '<input name="name">'."\n";
  echo '<input type="hidden" name="dest" value="'.$_GET['id'].'">'."\n";
  echo '<input type="hidden" name="otp" value="'.$otp_session.'">'."\n";
  echo '<input class="button" type="submit" name="new" value="New Album">'."\n";
  echo '</form>'."\n";
  echo '</div>';
}

echo '<div class="admin-folder-nav clearfix">'."\n";
if ($single && $folder['parent']['id'] !== $box_root_folder_id)
  echo '<div class="admin-folder-parent" id="jumpto"><a href="'.$base_url.'admin/folder.php?id='.$folder['parent']['id'].'">&lt;&lt;&nbsp;'.$folder['parent']['name'].'</a>';
elseif ($single)
  echo '<div class="admin-folder-parent" id="jumpto"><a href="'.$base_url.'admin/folder.php">&lt;&lt;&nbsp;Albums list</a>';
if ($single)
  echo '<div class="edit-admin"><a href="javascript:;" onclick="show(\'all-album-list\')">Jump to..</a><div id="all-album-list"><a class="close" href="javascript:;" onclick="show(\'all-album-list\')">[Close]</a><br/>'.$all_album_list.'</div></div></div>'."\n";
echo '<form method="post" class="admin-folder-form right" action="folder.php?ref='.urlencode($base_url.'admin/folder.php?id='.$_GET['id']).'">'."\n";
echo 'Items per page:<input class="admin-folder-limit" name="admin_folder_limit" value="'.$admin_folder_limit.'">'."\n";
echo '</form>'."\n";
echo '<div id="pager-top">'."\n";
if ($p > 0)
  echo '<div class="prev"><a href="folder.php?id='.$_GET['id'].'&amp;p='.($p - 1).'" title="Previous">← Previous</a></div>';
if (($p + 1) * $admin_folder_limit < $n)
  echo '<div class="next"><a href="folder.php?id='.$_GET['id'].'&amp;p='.($p + 1).'" title="Next">Next →</a></div>';
echo '<div class="pager">Page '.($p + 1).' of '.max(1, ceil($n / $admin_folder_limit)).'</div>'."\n";
echo '</div>'."\n";
echo '</div>'."\n";

echo '<form name="myForm" method="POST" action="folder.php?ref='.$url.'">'."\n";
echo '<input style="display:none;" type="submit" name="none" value="None" onclick="return false;">'."\n";
echo '<div class="admin-folder-nav clearfix">'."\n";
echo '<label class="left"><input class="checkbox" type="checkbox" name="select-all" onclick="if(this.checked==true) {selectAll(1); } else {selectAll(0); }"><span class="multiform">All/None</span></label>'."\n";
echo '<div class="dropdown left multiform drop-access">'."\n";
echo '<select id="multi-access-1" name="multi-access" onchange="javascript:{document.getElementById(\'multi-access-2\').value=this.value;}">'."\n";
echo '<option>Album access</option>'."\n";
echo '<option value="public">Public</option>'."\n";
echo '<option value="general">Restricted</option>'."\n";
echo '<option value="private">Private</option>'."\n";
echo '</select>'."\n";
echo '</div>'."\n";
echo '<input class="button left multiform" type="submit" name="multi-submit" value="Grant">'."\n";
echo '<div class="dropdown drop-move multiform left">'."\n";
echo '<select id="multi-move-1" name="multi-move" onchange="javascript:{document.getElementById(\'multi-move-2\').value=this.value;}">'."\n";
echo '<option selected>Move to Location</option>'."\n";
echo $move_list;
echo '</select>'."\n";
echo '</div>'."\n";
echo '<input type="hidden" name="multi-move-ori" value="'.$_GET['id'].'">'."\n";
echo '<input class="button left multiform" type="submit" name="multi-submit" value="Move">'."\n";
echo '<input class="button right delete" type="submit" name="multi-submit" value="Delete" onclick="return confirmAct();">'."\n";
echo '</div>'."\n";
foreach ($list as $id => $item) {
  echo '<div class="site-config clearfix" id="'.$item['id'].'">';
  echo '<input class="checkbox" type="checkbox" name="multiple[]" value="'.$item['id'].'">';
  if ($item !== 'error' && $item['type'] == 'folder') {
    $access=$folder_list[$id]['access'];
    echo '<a href="'.$base_url.'?id='.$item['id'].'">'.$item['name'].'</a> ('.$folder_list[$id]['total_count'].' items, ';
    if (file_exists($data_dir.$item['id']))
      echo file_get_contents($data_dir.$item['id'], true);
    else
      echo '0';
    echo ' views)';
    if ($folder_list[$id]['new'] == 1)
      echo '<span class="new">NEW</span>';
    echo '<span class="edit-admin"><a href="folder.php?id='.$item['id'].'">Manage Album</a></span>';
    echo '<br/><img class="admin-album" src="'.$base_url.'cover.php?id='.$item['id'].'-'.$item['sequence_id'].'&amp;w='.$w.'&amp;h='.$h.'&amp;otp='.$otp.'" alt="'.$item['name'].'" title="'.$item['name'].'" width="'.$w.'" height="'.$h.'" />';
  } elseif ($item !== 'error' && $item['type'] == 'file') {
    echo '<a href="'.$base_url.'image.php?id='.$item['id'].'&amp;fid='.$_GET['id'].'">'.$item['name'].'</a> (';
    if (file_exists($data_dir.$item['id']))
      echo file_get_contents($data_dir.$item['id'], true);
    else
      echo '0';
    echo ' views)';
    $item['name'] = substr($item['name'], 0, strrpos($item['name'], '.', -1));
    echo '<br/><img class="admin-img" src="'.$base_url.'thumbnail.php?id='.$item['id'].'-'.$item['sequence_id'].'&amp;fid='.$_GET['id'].'&amp;w=150&amp;h=150&amp;otp='.$otp.'" alt="'.$item['name'].'" title="'.$item['name'].'" width="'.$w.'" height="'.$h.'" />';
  }
  echo '<div class="admin-access"><div class="admin-form">'."\n";
  echo '<p>Name:</p><input class="name-conf" name="single['.$item['id'].'][name]" value="'.$item['name'].'"><br/>'."\n";
  echo '<p>Description:</p><textarea rows="3" class="description-conf" name="single['.$item['id'].'][description]">'.$item['description'].'</textarea><br/>'."\n";
  if ($item !== 'error' && $item['type'] == 'folder') {
    if ($access['public'][0] == 1) {
      $public = ' checked';
      $restrict = '';
      $private = '';
    } elseif ($access['general']['0'] == 1 || $access['specific']['0'] == 1 || $access['temporary']['0'] == 1) {
      $public = '';
      $restrict = ' checked';
      $private = '';
    } else {
      $public = '';
      $restrict = '';
      $private = ' checked';
    }
    echo '<p>Access:</p>'."\n";
    echo '<label><input class="checkme radio" type="radio" name="single['.$item['id'].'][public]" value="1"'.$public.' onclick="hide(\'extra-'.$id.'\')">Public access</label><br/>'."\n";
    echo '<label><input class="checkme radio" type="radio" name="single['.$item['id'].'][public]" value="0"'.$restrict.' onclick="show(\'extra-'.$id.'\')">Restrict access</label><br/>'."\n";
    echo '<div class="extra" id="extra-'.$id.'">'."\n";
    if ($access['general']['0'] == 1)
      $checked = ' checked';
    else
      $checked ='';
    echo '<input type="hidden" name="single['.$item['id'].'][general]" value="0"><label><input class="checkbox" type="checkbox" name="single['.$item['id'].'][general]" value="1" '.$checked.'>Access with general access code</label><br/>'."\n";
    if ($access['specific']['0'] == 1)
      $checked = ' checked';
    else
      $checked ='';
    echo '<input type="hidden" name="single['.$item['id'].'][specific]" value="0"><label><input class="checkbox" type="checkbox" name="single['.$item['id'].'][specific]" value="1"'.$checked.'>Specify an access code: </label><input id="specific-code-'.$id.'" type="text" style="width:180px;font-size:12px;" name="single['.$item['id'].'][specific-code]" value="Leave blank if not changed" onfocus="if(this.value==this.defaultValue)this.value=\'\';" onblur="if(this.value==\'\')this.value=this.defaultValue;"> or'."\n";
   echo '<input class="button" type="button" name="single['.$item['id'].'][specific-code-generate]" value="Generate randomly" onclick="document.getElementById (\'specific-code-'.$id.'\').value=(getRandomString(8))"><br/>'."\n";
    echo '<p class="button" style="margin-left:25px;width:180px;"><a href="'.$base_url.'admin/folder.php?code=1&amp;id='.$item['id'].'&amp;otp='.$otp_session.'" target="_blank">Show current access code</a></p>'."\n";
    if ($access['temporary']['0'] == 1)
      $checked = ' checked';
    else
      $checked ='';
    echo '<input type="hidden" name="single['.$item['id'].'][temporary]" value="0"><label><input class="checkbox" type="checkbox" name="single['.$item['id'].'][temporary]" value="1"'.$checked.'>Grant temporary access for </label><input type="text" style="width:30px;" name="single['.$item['id'].'][temporary-time]" value="24"> hours<br/><input id="temporary-code-'.$id.'" type="text" style="margin-left:25px;width:180px;font-size:12px;" name="single['.$item['id'].'][temporary-code]" value="Specify an access code" onfocus="if(this.value==this.defaultValue)this.value=\'\';" onblur="if(this.value==\'\')this.value=this.defaultValue;"> or <input class="button" type="button" name="single['.$item['id'].'][temporary-code-generate]" value="Generate temporary access code" onclick="document.getElementById (\'temporary-code-'.$id.'\').value=(getRandomString(8))"><br/>'."\n";
    echo '<p style="font-size:13px;line-height:25px;padding-left:25px;">* Specifying new access code will revoke the original one</p>'."\n";
    echo '</div>'."\n";
    echo '<label><input class="checkme radio" type="radio" name="single['.$item['id'].'][public]" value="private"'.$private.' onclick="hide(\'extra-'.$id.'\')">Private</label><br/>'."\n";
    echo '<input type="hidden" name="single['.$item['id'].'][type]" value="folder">'."\n";
  } elseif ($item !== 'error' && $item['type'] == 'file') {
    echo '<input type="hidden" name="single['.$item['id'].'][fid]" value="'.$item['parent']['id'].'">'."\n";
    echo '<input type="hidden" name="single['.$item['id'].'][type]" value="file">'."\n";
  }
  echo '<p>Location:</p>'."\n";
  echo '<div class="dropdown drop-move">'."\n";
  echo '<select name="single['.$item['id'].'][move]">'."\n";
  echo preg_replace('/'.$item['parent']['id'].'"/', $item['parent']['id'].'" selected', $move_list);
  echo '</select>'."\n";
  echo '</div>'."\n";
  echo '<br/><input class="button" type="submit" name="single['.$item['id'].'][submit]" value="Update">'."\n";
  echo '<input class="button right delete" type="submit" name="single['.$item['id'].'][submit]" value="Delete" onclick="return confirmAct();">'."\n";
  echo '<div class="clear"></div>'."\n";
  echo '</div></div>'."\n";
  echo '</div>'."\n";
}
echo '<input type="hidden" name="otp" value="'.$otp_session.'">'."\n";

echo '<div class="admin-folder-nav clearfix">'."\n";
echo '<label class="left"><input class="checkbox" type="checkbox" name="select-all" onclick="if(this.checked==true) {selectAll(1); } else {selectAll(0); }"><span class="multiform">All/None</span></label>'."\n";
echo '<div class="dropdown left multiform drop-access">'."\n";
echo '<select id="multi-access-2" name="multi-access" onchange="javascript:{document.getElementById(\'multi-access-1\').value=this.value;}">'."\n";
echo '<option>Album access</option>'."\n";
echo '<option value="public">Public</option>'."\n";
echo '<option value="general">Restricted</option>'."\n";
echo '<option value="private">Private</option>'."\n";
echo '</select>'."\n";
echo '</div>'."\n";
echo '<input class="button left multiform" type="submit" name="multi-submit" value="Grant">'."\n";
echo '<div class="dropdown drop-move multiform left">'."\n";
echo '<select id="multi-move-2" name="multi-move" onchange="javascript:{document.getElementById(\'multi-move-1\').value=this.value;}">'."\n";
echo '<option selected>Move to Location</option>'."\n";
echo $move_list;
echo '</select>'."\n";
echo '</div>'."\n";
echo '<input type="hidden" name="multi-move-ori" value="'.$_GET['id'].'">'."\n";
echo '<input class="button left multiform" type="submit" name="multi-submit" value="Move">'."\n";
echo '<input class="button right delete" type="submit" name="multi-submit" value="Delete" onclick="return confirmAct();">'."\n";
echo '</div>'."\n";

echo '</form>';

echo '<div class="admin-folder-nav clearfix">'."\n";
if ($p > 0)
  echo '<div id="prev"><a href="folder.php?id='.$_GET['id'].'&amp;p='.($p - 1).'" title="Previous">← Previous</a></div>';
if (($p + 1) * $admin_folder_limit < $n)
  echo '<div id="next"><a href="folder.php?id='.$_GET['id'].'&amp;p='.($p + 1).'" title="Next">Next →</a></div>';
echo '<div class="pager">Page '.++$p.' of '.max(1, ceil($n / $admin_folder_limit)).'</div>'."\n";
echo '</div>'."\n";

echo '<div class="admin-folder-nav clearfix">'."\n";
if ($single && $folder['parent']['id'] !== $box_root_folder_id)
  echo '<div class="admin-folder-parent" id="parent"><a href="'.$base_url.'admin/folder.php?id='.$folder['parent']['id'].'">&lt;&lt;&nbsp;'.$folder['parent']['name'].'</a>';
elseif ($single)
  echo '<div class="admin-folder-parent" id="parent"><a href="'.$base_url.'admin/folder.php">&lt;&lt;&nbsp;Albums list</a>';
if ($single)
  echo '<span class="edit-admin"><a href="#jumpto" onclick="show(\'all-album-list\')">Jump to..</a></span></div>'."\n";
echo '<form method="post" class="admin-folder-form right" action="folder.php?ref='.urlencode($base_url.'admin/folder.php?id='.$_GET['id']).'">'."\n";
echo 'Items per page:<input class="admin-folder-limit" name="admin_folder_limit" value="'.$admin_folder_limit.'">'."\n";
echo '</form>'."\n";
echo '</div>'."\n";
?>
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
