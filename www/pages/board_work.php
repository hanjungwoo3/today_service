<?php include_once('../config.php');?>

<?php
$mb_id = mb_id();

if($work){

  if($work == 'add'){ // 공지 생성
    parse_str($data,$data);
    $b_guide = implode(' ', $data['b_guide']);

    $sql = "INSERT INTO ".BOARD_TABLE."(b_title, b_content, create_datetime, update_datetime, b_guide, mb_id, read_mb, b_notice)
            VALUES('{$data['title']}','{$data['content']}', NOW(), NOW(), '{$b_guide}', '{$mb_id}', '', '{$data['notice']}')";
    $mysqli->query($sql);

  }elseif($work == 'edit'){ // 공지 수정
    parse_str($data,$data);
    $b_guide = implode(' ', $data['b_guide']);

    $sql = "UPDATE ".BOARD_TABLE." SET b_title = '{$data['title']}', b_content = '{$data['content']}', update_datetime = NOW(), b_guide = '{$b_guide}', b_notice = '{$data['notice']}' WHERE b_id = {$data['b_id']}";
    $mysqli->query($sql);

  }elseif($work == 'del'){ // 공지 삭제

    $sql = "DELETE FROM ".BOARD_TABLE." WHERE b_id = {$b_id}";
    $mysqli->query($sql);

  }elseif($work == 'view'){ // 공지 읽음 처리

    $sql = "SELECT read_mb FROM ".BOARD_TABLE." WHERE b_id = {$b_id}";
  	$result = $mysqli->query($sql);
  	$row = $result->fetch_assoc();

    $read = $row['read_mb']?explode(" ", $row['read_mb']):array();
    array_push($read, $mb_id);
    $read_unique = array_unique($read);
    $mb = implode(" ",$read_unique);

    $sql = "UPDATE ".BOARD_TABLE." SET read_mb = '{$mb}' WHERE b_id = {$b_id}";
    $mysqli->query($sql);

  }elseif($work == 'all_view'){ // 모든 공지 읽음 처리

    $sql = "SELECT b_id, read_mb FROM ".BOARD_TABLE." WHERE b_guide = {$auth}";
  	$result = $mysqli->query($sql);
    while($row = $result->fetch_assoc()){
      $read = $row['read_mb']?explode(" ", $row['read_mb']):array();
      array_push($read, $mb_id);
      $read_unique = array_unique($read);
      $mb = implode(" ",$read_unique);

      $sql = "UPDATE ".BOARD_TABLE." SET read_mb = '{$mb}' WHERE b_id = {$row['b_id']}";
      $mysqli->query($sql);
    }

  }
}
?>
