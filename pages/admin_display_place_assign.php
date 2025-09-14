<?php include_once('../config.php');?>
<?php check_accessible('admin');?>

<?php
// JSON 응답 헤더 설정
header('Content-Type: application/json; charset=utf-8');

$work = isset($_POST['work']) ? $_POST['work'] : '';

if($work == 'assign'){
  // dp_id 배열이 없거나 비어있는 경우 처리
  if(!isset($_POST['dp_id']) || empty($_POST['dp_id']) || !is_array($_POST['dp_id'])){
    echo json_encode(array('success' => false, 'message' => '분배할 전시대 장소를 선택해주세요.'));
    exit;
  }
  
  $ms_id = isset($_POST['ms_id']) ? $_POST['ms_id'] : '0';
  $dp_ids = $_POST['dp_id'];
  
  // SQL 인젝션 방지를 위한 안전한 처리
  $dp_ids = array_map('intval', $dp_ids);
  
  if(in_array($ms_id, array('all_3','all_1','all_2','all_4','all_5','all_6','all_7'))){ 
    // 전체/모임 형태 분배
    $ms_id = str_replace('all_','',$ms_id);
    
    // d_ms_all 컬럼에 모임 타입 저장, ms_id는 0으로 설정
    $sql = "UPDATE ".DISPLAY_PLACE_TABLE." SET ms_id = 0, d_ms_all = {$ms_id} WHERE dp_id IN (".implode(',', $dp_ids).")";
    $result = $mysqli->query($sql);
    if($result) {
      echo json_encode(array('success' => true, 'message' => '모임 형태별 분배가 완료되었습니다.'));
    } else {
      $error = $mysqli->error;
      echo json_encode(array('success' => false, 'message' => '데이터베이스 오류가 발생했습니다: ' . $error));
    }
  }else{
    // 개별 모임 계획으로 분배
    $ms_id = intval($ms_id);
    $sql = "UPDATE ".DISPLAY_PLACE_TABLE." SET ms_id = {$ms_id}, d_ms_all = 0 WHERE dp_id IN (".implode(',', $dp_ids).")";
    $result = $mysqli->query($sql);
    if($result) {
      echo json_encode(array('success' => true, 'message' => '분배가 완료되었습니다.'));
    } else {
      $error = $mysqli->error;
      echo json_encode(array('success' => false, 'message' => '데이터베이스 오류가 발생했습니다: ' . $error));
    }
  }
}
?> 