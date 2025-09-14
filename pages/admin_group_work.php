<?php include_once('../config.php');?>

<?php
if(isset($work) && $work == 'del'){ // 집단삭제

  $sql = "DELETE FROM ".GROUP_TABLE." WHERE g_id = {$del_id}";
  $mysqli->query($sql);

}else{ // 집단 추가/수정

  if(!empty($group['u'])){
    foreach ($group['u'] as $id => $value) {
      $sql = "UPDATE ".GROUP_TABLE." SET g_name = '".$value['name']."' WHERE g_id = {$id}";
      $mysqli->query($sql);
    }
  }
  if(!empty($group['n'])){
    foreach ($group['n'] as $id => $value) {
      $sql = "INSERT INTO ".GROUP_TABLE." (g_name) VALUES('".$value['name']."')";
      $mysqli->query($sql);
    }
  }

}
?>
