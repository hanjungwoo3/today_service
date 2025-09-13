<?php include_once('../config.php');?>

<?php
if(isset($work) && $work == 'del'){ // 장소 삭제

  $sql = "DELETE FROM ".MEETING_PLACE_TABLE." WHERE mp_id = ?";
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param('i', $del_id);
  $stmt->execute();

}else{ // 장소 추가수정

  if(!empty($meeting_place['u'])){
    foreach ($meeting_place['u'] as $id => $value) {
      $sql = "UPDATE ".MEETING_PLACE_TABLE." SET mp_name = ?, mp_address = ? WHERE mp_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param('ssi', $value['name'], $value['address'], $id);
      $stmt->execute();
    }
  }
  if(!empty($meeting_place['n'])){
    foreach ($meeting_place['n'] as $id => $value) {
      $sql = "INSERT INTO ".MEETING_PLACE_TABLE." (mp_name, mp_address) VALUES (?, ?)";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param('ss', $value['name'], $value['address']);
      $stmt->execute();
    }
  }

}
?>
