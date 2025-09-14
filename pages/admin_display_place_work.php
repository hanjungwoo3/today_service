<?php include_once('../config.php');?>

<?php
if(isset($work) && $work == 'del'){ // 장소 삭제

  $sql = "DELETE FROM ".DISPLAY_PLACE_TABLE." WHERE dp_id = {$del_id}";
  $mysqli->query($sql);
 
}else{ // 장소 추가/수정
  
  if(!empty($display_place['u'])){
    foreach ($display_place['u'] as $id => $value) {
      $sql = "UPDATE ".DISPLAY_PLACE_TABLE." SET dp_name = '".$value['name']."', dp_address = '".$value['address']."', dp_count = '".$value['count']."', ms_id = '".(isset($value['ms_id']) ? $value['ms_id'] : 0)."' WHERE dp_id = {$id}";
      $mysqli->query($sql);
    }
  }
  if(!empty($display_place['n'])){
    foreach ($display_place['n'] as $id => $value) {
      $sql = "INSERT INTO ".DISPLAY_PLACE_TABLE."(dp_name, dp_address, dp_count, ms_id) VALUES('".$value['name']."','".$value['address']."','".$value['count']."','".(isset($value['ms_id']) ? $value['ms_id'] : 0)."')";
      $mysqli->query($sql);
    }
  }

}
?>
