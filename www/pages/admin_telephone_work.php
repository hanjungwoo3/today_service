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

    if ($work === 'telephone_house') {

      $insertData = array();
      $deleteData = array();
      $updateData = array();
      
      $i = 1;
      foreach ($data as $key => $value) {
        $tp_id = isset($value['tp_id']) ? $value['tp_id'] : '';
        if (!empty($tp_id)) {
            $tph_id = isset($value['tph_id']) ? $value['tph_id'] : '';
            $tph_number = isset($value['tph_number']) ? upload_filter($value['tph_number']) : '';
            $tph_type = isset($value['tph_type']) ? upload_filter($value['tph_type']) : '';
            $tph_name = isset($value['tph_name']) ? upload_filter($value['tph_name']) : '';
            $tph_address = isset($value['tph_address']) ? upload_filter($value['tph_address']) : '';

            if (isset($value['add']) && $value['add'] == 'add') { 
                // 추가 작업 배열에 저장
                if (!(isset($value['delete']) && $value['delete'] == 'delete')) {
                    $insertData[] = [
                        'tp_id' => $tp_id,
                        'tph_number' => $tph_number,
                        'tph_type' => $tph_type,
                        'tph_name' => $tph_name,
                        'tph_address' => $tph_address,
                        'tph_order' => $i
                    ];
                }
            } elseif (isset($value['delete']) && $value['delete'] == 'delete') { 
                // 삭제 작업 배열에 저장
                $deleteData[] = $tph_id;
            } else { 
                // 수정 작업 배열에 저장
                $updateData[] = [
                    'tph_id' => $tph_id,
                    'tph_number' => $tph_number,
                    'tph_type' => $tph_type,
                    'tph_name' => $tph_name,
                    'tph_address' => $tph_address,
                    'tph_order' => $i
                ];
            }
        } 
        $i++;
      }

      // 1. Insert 데이터 처리
      if (!empty($insertData)) {
          $insertValues = [];
          foreach ($insertData as $row) {
              $insertValues[] = "('{$row['tp_id']}', '{$row['tph_number']}', '{$row['tph_type']}', '{$row['tph_name']}', '{$row['tph_address']}', {$row['tph_order']}, '', '', '', 0)";
              $i++;
          }
          $sql = "INSERT INTO " . TELEPHONE_HOUSE_TABLE . " (tp_id, tph_number, tph_type, tph_name, tph_address, tph_order, tph_condition, tph_visit, tph_visit_old, mb_id) VALUES " . implode(", ", $insertValues);
          $mysqli->query($sql);
      }

      // 2. Delete 데이터 처리
      if (!empty($deleteData)) {
          $deleteIds = implode(",", $deleteData);
          $sql = "DELETE FROM " . TELEPHONE_HOUSE_TABLE . " WHERE tph_id IN ({$deleteIds})";
          $mysqli->query($sql);

          $sql = "DELETE FROM " . TELEPHONE_HOUSE_MEMO_TABLE . " WHERE tph_id IN ({$deleteIds})";
          $mysqli->query($sql);

          $sql = "DELETE FROM " . TELEPHONE_RETURN_VISIT_TABLE . " WHERE tph_id IN ({$deleteIds})";
          $mysqli->query($sql);
      }

      // 3. Update 데이터 처리
      if (!empty($updateData)) {
          foreach ($updateData as $row) {
              $sql = "UPDATE " . TELEPHONE_HOUSE_TABLE . " 
                      SET tph_number = '{$row['tph_number']}', 
                          tph_type = '{$row['tph_type']}', 
                          tph_name = '{$row['tph_name']}', 
                          tph_address = '{$row['tph_address']}', 
                          tph_order = {$row['tph_order']} 
                      WHERE tph_id = {$row['tph_id']}";
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

    // Telephone 객체 생성
    $telephone = new Telephone($mysqli);

    switch ($postData['work']) {
  
      case 'add': // 구역 추가
        $insertId = $telephone->insert($postData);
        echo $insertId;
        break;
      case 'edit': // 구역 수정
        $tp_id = $postData['tp_id'];
        $updateId = $telephone->update($tp_id,$postData);
        echo $updateId;
        break;
      case 'del': // 구역 삭제
        $tp_id = $postData['tp_id'];
        $isDeleted = $telephone->delete($tp_id);
        if($isDeleted){
          $sql = "DELETE tphm FROM ".TELEPHONE_HOUSE_MEMO_TABLE." as tphm INNER JOIN ".TELEPHONE_HOUSE_TABLE." as tph ON tph.tph_id = tphm.tph_id WHERE tph.tp_id = {$tp_id}";
          $mysqli->query($sql);
          $sql = "DELETE tprv FROM ".TELEPHONE_RETURN_VISIT_TABLE." as tprv INNER JOIN ".TELEPHONE_HOUSE_TABLE." as tph ON tph.tph_id = tprv.tph_id WHERE tph.tp_id = {$tp_id}";
          $mysqli->query($sql);
          $sql = "DELETE FROM ".TELEPHONE_HOUSE_TABLE." WHERE tp_id = {$tp_id}";
          $mysqli->query($sql);
        }
        break;
      case 'assign': // 선택한 구역들을 선택한 모임으로 분배
        $tp_id = $postData['tp_id'];
        $ms_id = $postData['ms_id'];
        if(in_array($ms_id, array('all_3','all_1','all_2','all_4','all_5','all_6','all_7'))){ // 전체/봉사형태 모임계획 분배
          $ms_id = str_replace('all_','',$ms_id);
          foreach ($tp_id as $key => $value) {
            $data = [
              'ms_id' => 0,
              'tp_ms_all' => $ms_id,
            ];
            $updateId = $telephone->update($value,$data);
          }
        }else{
          foreach ($tp_id as $key => $value) {
            $data = [
              'ms_id' => $ms_id,
              'tp_ms_all' => 0,
            ];
            $updateId = $telephone->update($value,$data);
          }
        }
        echo $updateId;
        break;
      case 'check_delete': // 선택한 구역들을 삭제
        $tp_id = $postData['tp_id'];
        foreach ($tp_id as $key => $value) {
          $isDeleted = $telephone->delete($value);
          if($isDeleted){
            $sql = "DELETE tphm FROM ".TELEPHONE_HOUSE_MEMO_TABLE." as tphm INNER JOIN ".TELEPHONE_HOUSE_TABLE." as tph ON tph.tph_id = tphm.tph_id WHERE tph.tp_id = {$value}";
            $mysqli->query($sql);
      
            $sql = "DELETE tprv FROM ".TELEPHONE_RETURN_VISIT_TABLE." as tprv INNER JOIN ".TELEPHONE_HOUSE_TABLE." as tph ON tph.tph_id = tprv.tph_id WHERE tph.tp_id = {$value}";
            $mysqli->query($sql);
      
            $sql = "DELETE FROM ".TELEPHONE_HOUSE_TABLE." WHERE tp_id = {$value}";
            $mysqli->query($sql);
          }
    
        }
        break;
      case 'check_reset': // 선택한 구역들을 리셋
        $tp_id = $postData['tp_id'];
        foreach ($tp_id as $key => $value) {
          telephone_reset($value);
          telephone_house_reset($value);
        }
        insert_work_log('telephone_reset_check');
        break;
      case 'telephone_record': // 봉사기록 편집 (삭제만 가능)
        foreach ($postData['telephone_record'] as $key => $value) {
          if(isset($value['delete']) && $value['delete'] == 'delete'){
            $sql = "DELETE FROM ".TELEPHONE_RECORD_TABLE." WHERE tpr_id = {$key}";
            $mysqli->query($sql);
          }
        }
        break;
      case 'copy': // 구역 복제
        // SELECT 쿼리로 기존 데이터 가져오기
        $sql = "SELECT * FROM ".TELEPHONE_TABLE." WHERE tp_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $postData['pid']); // $pid는 tp_id로 정수형 변수
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
  
        // 현재 날짜 가져오기
        $today = date('Y-m-d');

        $insertData = array(
          'tp_num' => $row['tp_num'].'-복제',
          'tp_name' => $row['tp_name']
        );
  
        // 반복문 실행
        $return = array();
        for ($i = 0; $i < $postData['count']; $i++) {
          $return[] = $telephone->insert($insertData);
        }
  
        // 결과 출력
        echo implode(',', $return);
        break;
    }

  }

}