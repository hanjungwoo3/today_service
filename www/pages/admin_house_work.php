<?php include_once('../config.php');?>

<?php
if($work){
  if($work == 'assign'){ // 선택한 세대들을 선택한 구역으로 이동

    if($type && $h_id && $id){
      if($type == 2){ // 전화
        $sql = "SELECT * FROM ".TELEPHONE_TABLE." WHERE tp_id = {$id}";
        $result = $mysqli->query($sql);
        if($result->num_rows > 0){
          foreach ($h_id as $key => $value) {
            $sql = "UPDATE ".TELEPHONE_HOUSE_TABLE." SET tp_id = {$id} WHERE tph_id = {$value}";
            $mysqli->query($sql);
          }
        }else{
          echo 'invalid_id';
        }
      }else{ // 호별 편지
        $sql = "SELECT * FROM ".TERRITORY_TABLE." WHERE tt_id = {$id}";
        $result = $mysqli->query($sql);
        if($result->num_rows > 0){
          foreach ($h_id as $key => $value) {
            $sql = "UPDATE ".HOUSE_TABLE." SET tt_id = {$id} WHERE h_id = {$value}";
            $mysqli->query($sql);
          }
        }else{
          echo 'invalid_id';
        }
      }
    }

  }elseif($work == 'check_delete'){ // 선택한 구역들을 삭제

    if($type && $h_id){
      if($type == 2){ //전화
        foreach ($h_id as $key => $value) {
          $sql = "DELETE FROM ".TELEPHONE_HOUSE_TABLE." WHERE tph_id = {$value}";
          $mysqli->query($sql);
          $sql = "DELETE FROM ".TELEPHONE_HOUSE_MEMO_TABLE." WHERE tph_id = {$value}";
          $mysqli->query($sql);
          $sql = "DELETE FROM ".TELEPHONE_RETURN_VISIT_TABLE." WHERE tph_id = {$value}";
          $mysqli->query($sql);
        }
      }else{ // 호별 편지
        foreach ($h_id as $key => $value) {
          $sql = "DELETE FROM ".HOUSE_TABLE." WHERE h_id = {$value}";
          $mysqli->query($sql);
          $sql = "DELETE FROM ".HOUSE_MEMO_TABLE." WHERE h_id = {$value}";
          $mysqli->query($sql);
          $sql = "DELETE FROM ".RETURN_VISIT_TABLE." WHERE h_id = {$value}";
          $mysqli->query($sql);
        }
      }
    }

  }
}
?>
