<?php include_once('../config.php');?>

<?php
$mb_id = mb_id();
$rv_datetime = isset($datetime)?date("Y-m-d H:i:s", strtotime($datetime)):'';

if($work == 'stop'){ // 재방문 중단

  if($table == 'territory'){
    // 재방문중인 전도인 ID 제거
    $sql = "UPDATE ".HOUSE_TABLE." SET mb_id = 0, h_condition = '' WHERE h_id = {$pid}";
    $mysqli->query($sql);

    // 이 집의 모든 재방문 기록 삭제
    $sql = "DELETE FROM ".RETURN_VISIT_TABLE." WHERE h_id = {$pid}";
    $mysqli->query($sql);
  }elseif($table == 'telephone'){
    // 재방문중인 전도인 ID 제거
    $sql = "UPDATE ".TELEPHONE_HOUSE_TABLE." SET mb_id = 0, tph_condition = '' WHERE tph_id = {$pid}";
    $mysqli->query($sql);

    // 이 집의 모든 재방문 기록 삭제
    $sql = "DELETE FROM ".TELEPHONE_RETURN_VISIT_TABLE." WHERE tph_id = {$pid}";
    $mysqli->query($sql);
  }

}elseif($work == 'transfer'){ // 재방문 양도

  if($mb){
    if($table == 'territory'){

      $sql = "UPDATE ".HOUSE_TABLE." SET mb_id = {$mb} WHERE h_id = {$pid}";
      $mysqli->query($sql);
      $sql = "INSERT INTO ".RETURN_VISIT_TABLE."(h_id, mb_id, rv_content, rv_datetime, create_datetime, update_datetime, rv_transfer)
              VALUES({$pid},'{$mb_id}','양도',NOW(), NOW(), NOW(), 1)";
      $mysqli->query($sql);

    }elseif($table == 'telephone'){

      $sql = "UPDATE ".TELEPHONE_HOUSE_TABLE." SET mb_id = {$mb} WHERE tph_id = {$pid}";
      $mysqli->query($sql);
      $sql = "INSERT INTO ".TELEPHONE_RETURN_VISIT_TABLE."(tph_id, mb_id, tprv_content, tprv_datetime, create_datetime, update_datetime, tprv_transfer)
              VALUES({$pid},'{$mb_id}','양도',NOW(), NOW(), NOW(), 1)";
      $mysqli->query($sql);

    }
  }

}elseif($work == 'add_return_visit'){ // 재방문 기록 추가

  if($table == 'territory'){

    $sql = "INSERT INTO ".RETURN_VISIT_TABLE."(h_id, mb_id, rv_content, rv_datetime, create_datetime, update_datetime, rv_transfer)
            VALUES({$pid},'{$mb_id}','{$content}','{$rv_datetime}', NOW(), NOW(), 0)";
    $mysqli->query($sql);

  }elseif($table == 'telephone'){

    $sql = "INSERT INTO ".TELEPHONE_RETURN_VISIT_TABLE."(tph_id, mb_id, tprv_content, tprv_datetime, create_datetime, update_datetime, tprv_transfer)
            VALUES({$pid},'{$mb_id}','{$content}','{$rv_datetime}', NOW(), NOW(), 0)";
    $mysqli->query($sql);
  }

}elseif($work == 'update_return_visit'){ // 재방문 기록 업데이트

  if($table == 'territory'){

    $sql = "UPDATE ".RETURN_VISIT_TABLE." SET rv_content = '{$rv_content}', rv_datetime = '{$rv_datetime}', update_datetime = NOW() WHERE rv_id = {$rv_id}";
    $mysqli->query($sql);

  }elseif($table == 'telephone'){

    $sql = "UPDATE ".TELEPHONE_RETURN_VISIT_TABLE." SET tprv_content = '{$rv_content}', tprv_datetime = '{$rv_datetime}', update_datetime = NOW() WHERE tprv_id = {$rv_id}";
    $mysqli->query($sql);
  }

}elseif($work == 'delete_return_visit'){ // 재방문 기록 삭제

  if($table == 'territory'){

    $sql = "DELETE FROM ".RETURN_VISIT_TABLE." WHERE rv_id = {$rv_id}";
    $mysqli->query($sql);

  }elseif($table == 'telephone'){

    $sql = "DELETE FROM ".TELEPHONE_RETURN_VISIT_TABLE." WHERE tprv_id = {$rv_id}";
    $mysqli->query($sql);

  }

}elseif($work == 'returnvisit_change_study'){ // 재방문, 연구 상태변경

  if($table == 'territory'){

    $sql = "UPDATE ".HOUSE_TABLE." SET h_condition = {$condition} WHERE h_id = {$pid}";
    $mysqli->query($sql);

  }elseif($table == 'telephone'){

    $sql = "UPDATE ".TELEPHONE_HOUSE_TABLE." SET tph_condition = '{$condition}' WHERE tph_id = {$pid}";
    $mysqli->query($sql);

  }
}elseif($work == 'info'){ // 전도인 개인정보 변경

  if($mb_hp) $mb_hp = encrypt($mb_hp);
  if($mb_address) $mb_address = encrypt($mb_address);
  if($mb_password){
    $hash = password_hash($mb_password, PASSWORD_BCRYPT);
    $sql = "UPDATE ".MEMBER_TABLE." SET mb_hash = '{$hash}', mb_hp = '{$mb_hp}', mb_address = '{$mb_address}', font_size = '{$font_size}' WHERE mb_id = {$mb_id}";
    $mysqli->query($sql);
  }else{
    $sql = "UPDATE ".MEMBER_TABLE." SET mb_hp = '{$mb_hp}', mb_address = '{$mb_address}', font_size = '{$font_size}' WHERE mb_id = {$mb_id}";
    $mysqli->query($sql);
  }
}
?>
