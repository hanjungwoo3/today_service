<?php
/**
 * 24.12.29 with ChatGPT
 */
include_once('../config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Content-Type 확인
  $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

  if (strpos($contentType, 'application/json') !== false) {

    // JSON 데이터 수신
    if (function_exists('file_get_contents')) {
      $input = file_get_contents("php://input");
    } else {
      $input = '';
      $fh = fopen('php://input', 'r');
      while ($chunk = fread($fh, 1024)) {
          $input .= $chunk;
      }
      fclose($fh);
    }
    $request = json_decode($input, true);

    // JSON 디코딩 오류 체크
    if (json_last_error() !== JSON_ERROR_NONE) {
      $errorMessages = array(
          JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
          JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
          JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
          JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
          JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
      );
      switch (json_last_error()) {
        case JSON_ERROR_DEPTH:
            $errorMessage = 'Maximum stack depth exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $errorMessage = 'Underflow or the modes mismatch';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $errorMessage = 'Unexpected control character found';
            break;
        case JSON_ERROR_SYNTAX:
            $errorMessage = 'Syntax error, malformed JSON';
            break;
        case JSON_ERROR_UTF8:
            $errorMessage = 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
        default:
            $errorMessage = 'Unknown error';
      }
      header('HTTP/1.1 400 Bad Request');
      die(json_encode(array("status" => "error", "message" => $errorMessage)));
    }

    $work = isset($request['work']) ? $request['work'] : null;
    $data = isset($request['data']) ? $request['data'] : array();

    if ($work === 'territory_house') {

      $insertData = array();
      $deleteData = array();
      $updateData = array();
      
      $i = 1;
      // tt_id -> tt_type 캐시로 중복 조회 최소화
      $ttTypeCache = array();
      foreach ($data as $key => $value) {
          $tt_id = isset($value['tt_id']) ? $value['tt_id'] : '';
          if (!empty($tt_id)) {
              $h_id = isset($value['h_id']) ? $value['h_id'] : '';
              $h_address1 = isset($value['h_address1']) ? upload_filter($value['h_address1']) : '';
              $h_address2 = isset($value['h_address2']) ? upload_filter($value['h_address2']) : '';
              $h_address3 = isset($value['h_address3']) ? upload_filter($value['h_address3']) : '';
              $h_address4 = isset($value['h_address4']) ? upload_filter($value['h_address4']) : '';
              $h_address5 = isset($value['h_address5']) ? upload_filter($value['h_address5']) : '';

              // 구역 유형 조회 (캐시 사용)
              $tt_type = '';
              if(isset($ttTypeCache[$tt_id])){
                  $tt_type = $ttTypeCache[$tt_id];
              }else{
                  $tt_row = $mysqli->query("SELECT tt_type FROM ".TERRITORY_TABLE." WHERE tt_id = {$tt_id}")->fetch_assoc();
                  $tt_type = $tt_row ? $tt_row['tt_type'] : '';
                  $ttTypeCache[$tt_id] = $tt_type;
              }

              // 특정 유형(type_2: 아파트, type_3: 빌라, type_8: 추가2)은 h_address4/5 미사용 -> 강제 비움
              if(in_array($tt_type, array('아파트','빌라','추가2'))){
                  $h_address4 = '';
                  $h_address5 = '';
              }

              if (isset($value['add']) && $value['add'] == 'add') { 
                  // 추가 작업 배열에 저장
                  if (!(isset($value['delete']) && $value['delete'] == 'delete')) {
                      $insertData[] = [
                          'tt_id' => $tt_id,
                          'h_address1' => $h_address1,
                          'h_address2' => $h_address2,
                          'h_address3' => $h_address3,
                          'h_address4' => $h_address4,
                          'h_address5' => $h_address5,
                          'h_order' => $i
                      ];
                  }
              } elseif (isset($value['delete']) && $value['delete'] == 'delete') { 
                  // 삭제 작업 배열에 저장
                  $deleteData[] = $h_id;
              } else { 
                  // 수정 작업 배열에 저장
                  $updateData[] = [
                      'h_id' => $h_id,
                      'h_address1' => $h_address1,
                      'h_address2' => $h_address2,
                      'h_address3' => $h_address3,
                      'h_address4' => $h_address4,
                      'h_address5' => $h_address5,
                      'h_order' => $i
                  ];
              }
          } 
        $i++;
      }

      // 1. Insert 데이터 처리
      if (!empty($insertData)) {
          $insertValues = [];
          foreach ($insertData as $row) {
              $insertValues[] = "('{$row['tt_id']}', '{$row['h_address1']}', '{$row['h_address2']}', '{$row['h_address3']}', '{$row['h_address4']}', '{$row['h_address5']}', {$row['h_order']}, '', '', '', 0)";
              $i++;
          }
          $sql = "INSERT INTO " . HOUSE_TABLE . " (tt_id, h_address1, h_address2, h_address3, h_address4, h_address5, h_order, h_condition, h_visit, h_visit_old, mb_id) VALUES " . implode(", ", $insertValues);
          $mysqli->query($sql);
      }

      // 2. Delete 데이터 처리
      if (!empty($deleteData)) {
          $deleteIds = implode(",", $deleteData);
          $sql = "DELETE FROM " . HOUSE_TABLE . " WHERE h_id IN ({$deleteIds})";
          $mysqli->query($sql);

          $sql = "DELETE FROM " . HOUSE_MEMO_TABLE . " WHERE h_id IN ({$deleteIds})";
          $mysqli->query($sql);

          $sql = "DELETE FROM " . RETURN_VISIT_TABLE . " WHERE h_id IN ({$deleteIds})";
          $mysqli->query($sql);
      }

      // 3. Update 데이터 처리
      if (!empty($updateData)) {
          foreach ($updateData as $row) {
              $sql = "UPDATE " . HOUSE_TABLE . " 
                      SET h_address1 = '{$row['h_address1']}', 
                          h_address2 = '{$row['h_address2']}', 
                          h_address3 = '{$row['h_address3']}', 
                          h_address4 = '{$row['h_address4']}', 
                          h_address5 = '{$row['h_address5']}', 
                          h_order = {$row['h_order']} 
                      WHERE h_id = {$row['h_id']}";
              $mysqli->query($sql);
              $i++;
          }
      }
      
    }

  } else {

    $postData = $_POST; // 입력 데이터 원본 가져오기
  
    if (!$postData['work']) {
      http_response_code(400);
      die(json_encode(['status' => 'error', 'message' => 'Invalid work parameter']));
    }
  
    // Territory 객체 생성
    $territory = new Territory($mysqli);
  
    switch ($postData['work']) {
  
      case 'add': // 구역 추가
        $postData['tt_polygon'] = stripslashes($postData['tt_polygon']);
        $insertId = $territory->insert($postData);
        echo $insertId;
        break;
      case 'edit': // 구역 수정
        $tt_id = $postData['tt_id'];
        $postData['tt_polygon'] = stripslashes($postData['tt_polygon']);
        $updateId = $territory->update($tt_id,$postData);
        echo $updateId;
        break;
      case 'del': // 구역 삭제
        $tt_id = $postData['tt_id'];
        $isDeleted = $territory->delete($tt_id);
        if($isDeleted){
          $sql = "DELETE hm FROM ".HOUSE_MEMO_TABLE." as hm INNER JOIN ".HOUSE_TABLE." as h ON h.h_id = hm.h_id WHERE h.tt_id = {$tt_id}";
          $mysqli->query($sql);
          $sql = "DELETE rv FROM ".RETURN_VISIT_TABLE." as rv INNER JOIN ".HOUSE_TABLE." as h ON h.h_id = rv.h_id WHERE h.tt_id = {$tt_id}";
          $mysqli->query($sql);
          $sql = "DELETE FROM ".HOUSE_TABLE." WHERE tt_id = {$tt_id}";
          $mysqli->query($sql);
        }
        break;
      case 'assign': // 선택한 구역들을 선택한 모임으로 분배
        $tt_id = $postData['tt_id'];
        $ms_id = $postData['ms_id'];
        if(in_array($ms_id, array('all_3','all_1','all_2','all_4','all_5','all_6','all_7'))){ // 전체/봉사형태 모임계획 분배
          $ms_id = str_replace('all_','',$ms_id);
          foreach ($tt_id as $key => $value) {
            $data = [
              'ms_id' => 0,
              'tt_ms_all' => $ms_id,
            ];
            $updateId = $territory->update($value,$data);
          }
        }else{
          foreach ($tt_id as $key => $value) {
            $data = [
              'ms_id' => $ms_id,
              'tt_ms_all' => 0,
            ];
            $updateId = $territory->update($value,$data);
          }
        }
        echo $updateId;
        break;
      case 'check_delete': // 선택한 구역들을 삭제
        $tt_id = $postData['tt_id'];
        foreach ($tt_id as $key => $value) {
          $isDeleted = $territory->delete($value);
          if($isDeleted){
            $sql = "DELETE hm FROM ".HOUSE_MEMO_TABLE." as hm INNER JOIN ".HOUSE_TABLE." as h ON h.h_id = hm.h_id WHERE h.tt_id = {$value}";
            $mysqli->query($sql);
      
            $sql = "DELETE rv FROM ".RETURN_VISIT_TABLE." as rv INNER JOIN ".HOUSE_TABLE." as h ON h.h_id = rv.h_id WHERE h.tt_id = {$value}";
            $mysqli->query($sql);
      
            $sql = "DELETE FROM ".HOUSE_TABLE." WHERE tt_id = {$value}";
            $mysqli->query($sql);
          }
    
        }
        break;
      case 'check_reset': // 선택한 구역들을 리셋
        // 중요: territory_reset() 함수는 봉사기록(TERRITORY_RECORD_TABLE)을 삭제하지 않으며,
        // 리셋 전의 봉사 정보를 봉사기록 테이블에 저장합니다.
        $tt_id = $postData['tt_id'];
        foreach ($tt_id as $key => $value) {
          territory_reset($value);
          territory_house_reset($value);
          // 봉사기록은 territory_reset() 함수 내에서 자동으로 저장되며 삭제되지 않습니다.
        }
        insert_work_log('territory_reset_check');
        break;
      case 'territory_record': // 봉사기록 편집 (삭제만 가능)
        foreach ($postData['territory_record'] as $key => $value) {
          if(isset($value['delete']) && $value['delete'] == 'delete'){
            $sql = "DELETE FROM ".TERRITORY_RECORD_TABLE." WHERE ttr_id = {$key}";
            $mysqli->query($sql);
          }
        }
        break;
      case 'copy': // 구역 복제
        // 간결한 방식: 정수화 후 단순 조회 (PHP 5.5~8.3 호환)
        $pid = isset($postData['pid']) ? (int)$postData['pid'] : 0;
        $row = $mysqli->query("SELECT tt_num, tt_name, tt_type, tt_polygon, tt_address, tt_address2 FROM ".TERRITORY_TABLE." WHERE tt_id = {$pid}")->fetch_assoc();
        if(!$row){ echo '0'; break; }
  
        // 현재 날짜 가져오기
        $today = date('Y-m-d');

        $insertData = array(
          'tt_num' => $row['tt_num'].'-복제',
          'tt_name' => $row['tt_name'],
          'tt_type' => $row['tt_type'],
          'tt_polygon' => $row['tt_polygon'],
          'tt_address' => $row['tt_address'],
          'tt_address2' => $row['tt_address2']
        );
  
        // 반복문 실행
        $return = array();
        $copyCount = isset($postData['count']) ? (int)$postData['count'] : 1;
        if($copyCount < 1){ $copyCount = 1; }
        for ($i = 0; $i < $copyCount; $i++) {
          $return[] = $territory->insert($insertData);
        }
  
        // 결과 출력
        echo implode(',', $return);
        break;
    }

  }

}