<?php include_once('../config.php');?>
<?php check_accessible('admin');?>

<?php
$sql = "SELECT * FROM ".TELEPHONE_TABLE." tp LEFT JOIN ".MEETING_SCHEDULE_TABLE." ms ON tp.ms_id = ms.ms_id WHERE tp.tp_id = {$id} ORDER BY tp_num+0 ASC, tp_num ASC";
$result = $mysqli->query($sql);

$data = array();

if($result->num_rows > 0){
    while ($row = $result->fetch_assoc()) {
      $tp_id = $row['tp_id'];
      $record_count = 0; // 봉사기록 카운트

      $sql2 = "SELECT count(*) FROM ".TELEPHONE_HOUSE_TABLE." WHERE tp_id = {$tp_id}";
      $result2 = $mysqli->query($sql2);
      $row2 = $result2->fetch_row();

      $telephone_progress = get_telephone_progress($tp_id);
      $progress_percent = $telephone_progress['total']?floor((($telephone_progress['visit']+$telephone_progress['absence'])/$telephone_progress['total'])*100):0;

      if($row['tp_assigned'] || !empty_date($row['tp_start_date'])){
        $complete = !empty_date($row['tp_end_date'])?'complete':'incomplete';
        $record_count++;
      }else{
        $complete = 'unused';
      }

      $tp_status = (empty($row['tp_status']) && empty_date($row['tp_assigned_date']))?'unassigned':$row['tp_status'];
      
      // 상세한 배정 상태 생성
      $status_text = '';
      $status_detail = '';
      $progress_date = '';
      
      // 방문 기록 가져오기
      $all_past_records = get_all_past_records('telephone', $tp_id);
      
      if($row['tp_assigned'] || !empty_date($row['tp_start_date'])) {
          // 배정된 구역
          if(strpos($row['tp_status'], 'absence') !== false) {
              // 부재자 구역
              $status_text = '부재';
              // 실제 봉사 시작일이 있는 경우에만 진행중/완료 판단
              if(!empty_date($row['tp_start_date']) && !empty($all_past_records)) {
                  // 새로운 progress 키 사용
                  if($all_past_records[0]['progress'] == 'completed') {
                      $status_detail .= '완료';
                      // 완료된 경우 시작일과 종료일 표시
                      if(!empty($all_past_records[0]['records'])) {
                          $first_record = $all_past_records[0]['records'][0];
                          $last_record = end($all_past_records[0]['records']);
                          if(!empty_date($last_record['start_date']) && !empty_date($first_record['end_date'])) {
                              $progress_date = date('y.m.d', strtotime($last_record['start_date'])).'~'.date('y.m.d', strtotime($first_record['end_date']));
                          }
                      }
                  } 
                  // 진행 중
                  elseif($all_past_records[0]['progress'] == 'in_progress') {
                      $status_detail .= '진행중';
                      // 진행 중인 경우 시작일만 표시
                      if(!empty($all_past_records[0]['records'])) {
                          $last_record = end($all_past_records[0]['records']);
                          if(!empty_date($last_record['start_date'])) {
                              $progress_date = date('y.m.d', strtotime($last_record['start_date'])).'~';
                          }
                      }
                  }
              }
          } else {
              // 전체 구역
              $status_text = '전체';
              // 실제 봉사 시작일이 있는 경우에만 진행중/완료 판단
              if(!empty_date($row['tp_start_date']) && !empty($all_past_records)) {
                  // 새로운 progress 키 사용
                  if($all_past_records[0]['progress'] == 'completed') {
                      $status_detail .= '완료';
                      // 완료된 경우 시작일과 종료일 표시
                      if(!empty($all_past_records[0]['records'])) {
                          $first_record = $all_past_records[0]['records'][0];
                          $last_record = end($all_past_records[0]['records']);
                          if(!empty_date($last_record['start_date']) && !empty_date($first_record['end_date'])) {
                              $progress_date = date('y.m.d', strtotime($last_record['start_date'])).'~'.date('y.m.d', strtotime($first_record['end_date']));
                          }
                      }
                  } 
                  // 진행 중
                  elseif($all_past_records[0]['progress'] == 'in_progress') {
                      $status_detail .= '진행중';
                      // 진행 중인 경우 시작일만 표시
                      if(!empty($all_past_records[0]['records'])) {
                          $last_record = end($all_past_records[0]['records']);
                          if(!empty_date($last_record['start_date'])) {
                              $progress_date = date('y.m.d', strtotime($last_record['start_date'])).'~';
                          }
                      }
                  }
              }
          }
      } else {
          // 미배정 구역
          if(strpos($row['tp_status'], 'absence') !== false) {
              $status_text = '부재';
          } else {
              $status_text = '전체';
          }
      }
      
      $status = get_status_text($tp_status);

      // 단일 상세 조회에서도 전체 기록 수 합산
      $sql3 = "SELECT count(*) FROM ".TELEPHONE_RECORD_TABLE." WHERE tp_id = {$tp_id}";
      $result3 = $mysqli->query($sql3);
      $row3 = $result3->fetch_row();
      $record_count = $record_count + (int)$row3[0];

      if($row['tp_assigned'] && !empty_date($row['tp_start_date'])){
        $start_date = $row['tp_start_date'];
      }else{
        $latest_record = get_latest_record('telephone', $tp_id);
        $start_date = isset($latest_record['tpr_start_date'])?$latest_record['tpr_start_date']:'';
      }
      $recent_start_date = !empty_date($start_date)?date('y.m.d', strtotime($start_date)):'';

      switch ($row['tp_ms_all']) {
        case '1': $ms_id_text = '호별'; break;
        case '2': $ms_id_text = '전시대'; break;
        case '3': $ms_id_text = '전체'; break;
        case '4': $ms_id_text = get_meeting_schedule_type_text(3); break;
        case '5': $ms_id_text = get_meeting_schedule_type_text(4); break;
        case '6': $ms_id_text = get_meeting_schedule_type_text(5); break;
        case '7': $ms_id_text = get_meeting_schedule_type_text(6); break;
        default : $ms_id_text = $row['ms_id']?get_week_text($row['ms_week']).' '.'('.$row['ms_id'].')':'';
    }

      $data = array(
        'id' => $tp_id,
        'num' => $row['tp_num'],
        'name' => $row['tp_name'],
        'type' => '전화',
        'house_count' => $row2[0],
        'status' => $status,
        'status_text' => $status_text,
        'status_detail' => $status_detail,
        'start_date' => $recent_start_date,
        'progress' => $progress_percent,
        'progress_date' => $progress_date,
        'absence' => $telephone_progress['absence'],
        'record_count' => $record_count,
        'memo' => $row['tp_memo'],
        'ms_id_text' => $ms_id_text,
        'return_visit_member' => $row['mb_id']?get_member_name($row['mb_id']):'',
        'return_visit_date' => !empty_date($row['tp_mb_date'])?date('y.m.d', strtotime($row['tp_mb_date'])):'-'
        );
    }
}

echo json_encode($data);
?>
