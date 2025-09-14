<?php include_once('../config.php');?>
<?php check_accessible('admin');?>

<?php
$where = array();

if($s_num) $where[] = "tp.tp_num LIKE '%{$s_num}%'"; // 구역번호검색
if($s_name) $where[] = "tp.tp_name LIKE '%{$s_name}%'"; // 구역명검색

// 배정여부검색
if($s_assign && $s_assign != '선택안함'){
  switch ($s_assign) {
      case '개인구역': $where[] = "tp.mb_id <> ''"; break;
      case '분배되지않음': $where[] = "tp.ms_id = '' AND tp.tp_ms_all = ''"; break;
      case '전체': $where[] = "tp.tp_ms_all = 3"; break;
      case '호별': $where[] = "tp.tp_ms_all = 1"; break;
      case '전시대': $where[] = "tp.tp_ms_all = 2"; break;
      case '추가1': $where[] = "tp.tp_ms_all = 4"; break;
      case '추가2': $where[] = "tp.tp_ms_all = 5"; break;
      case '추가3': $where[] = "tp.tp_ms_all = 6"; break;
      case '추가4': $where[] = "tp.tp_ms_all = 7"; break;
      default: $where[] = "tp.ms_id = {$s_assign}";
  }
}

// 배정상태검색
if($s_status && $s_status != '선택안함'){
switch ($s_status) {
    case '미배정': $where[] = "tp.tp_status = '' AND tp.tp_assigned_date = '0000-00-00'"; break;
    case '첫배정': $where[] = "tp.tp_status = '' AND tp.tp_assigned_date <> '0000-00-00'"; break;
    case '재배정': $where[] = "tp.tp_status = 'reassign'"; break;
    case '부재자': $where[] = "tp.tp_status = 'absence'"; break;
    case '부재자재배정': $where[] = "tp.tp_status = 'absence_reassign'"; break;
}
}

// 세대추가요청검색
if($s_memo && $s_memo != '선택안함'){
  switch ($s_memo) {
      case '미포함': $where[] = "tp.tp_memo = ''"; break;
      case '포함': $where[] = "tp.tp_memo <> ''"; break;
  }
}

$page = $page?$page:1;
$where = $where?'WHERE '.implode(' AND ',$where):'';
$total = $mysqli->query("SELECT count(*) FROM ".TELEPHONE_TABLE." tp LEFT JOIN ".MEETING_SCHEDULE_TABLE." ms ON tp.ms_id = ms.ms_id {$where}")->fetch_row()[0];
$limit = TERRITORY_ITEM_PER_PAGE?TERRITORY_ITEM_PER_PAGE:50;
$pagelength = ceil($total / $limit);
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM ".TELEPHONE_TABLE." tp LEFT JOIN ".MEETING_SCHEDULE_TABLE." ms ON tp.ms_id = ms.ms_id {$where} ORDER BY tp_num+0 ASC, tp_num ASC LIMIT {$offset}, {$limit}";
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
    $progress_percent = ($telephone_progress['total'])?floor((($telephone_progress['visit']+$telephone_progress['absence'])/$telephone_progress['total'])*100):0;

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

    $sql3 = "SELECT count(*) FROM ".TELEPHONE_RECORD_TABLE." WHERE tp_id = {$tp_id}";
    $result3 = $mysqli->query($sql3);
    $row3 = $result3->fetch_row();
    $record_count = $record_count + $row3[0];

    $latest_assigned_date = '';
    if($row['tp_assigned'] && !empty_date($row['tp_assigned_date'])){
      $latest_assigned_date = !empty_date($row['tp_assigned_date'])?date('y.m.d', strtotime($row['tp_assigned_date'])):'';
    }else{
      $latest_record = get_latest_record('telephone', $tp_id);
      if(!empty($latest_record)){
        $latest_assigned_date = date('y.m.d', strtotime($latest_record['tpr_assigned_date']));
      }
    }

    switch ($row['tp_ms_all']) {
        case '1': $ms_id_text = get_meeting_schedule_type_text(1); break;
        case '2': $ms_id_text = get_meeting_schedule_type_text(2); break;
        case '3': $ms_id_text = '전체'; break;
        case '4': $ms_id_text = get_meeting_schedule_type_text(3); break;
        case '5': $ms_id_text = get_meeting_schedule_type_text(4); break;
        case '6': $ms_id_text = get_meeting_schedule_type_text(5); break;
        case '7': $ms_id_text = get_meeting_schedule_type_text(6); break;
        default : $ms_id_text = $row['ms_id']?get_week_text($row['ms_week']).' '.'('.$row['ms_id'].')':'';
    }

    $data[] = array(
      'id' => $tp_id,
      'num' => $row['tp_num'],
      'name' => $row['tp_name'],
      'house_count' => $row2[0],
      'status' => $status,
      'status_text' => $status_text,
      'status_detail' => $status_detail,
      'latest_assigned_date' => $latest_assigned_date,
      'progress' => $progress_percent,
      'progress_date' => $progress_date,
      'absence' => $telephone_progress['absence'],
      'record_count' => $record_count,
      'memo' => $row['tp_memo'],
      'ms_id_text' => $ms_id_text,
      'return_visit_member' => $row['mb_id']?get_member_name($row['mb_id']):'',
      'return_visit_date' => !empty_date($row['tp_mb_date'])?date('y.m.d', strtotime($row['tp_mb_date'])):'-',
      'total' => $total,
      'page' => $page,
      'pagelength' => $pagelength
    );
  }
}

echo json_encode($data);
?>
