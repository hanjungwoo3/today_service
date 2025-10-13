<?php include_once('../config.php');?>

<?php
$where = '';
$data = array();
$m_data = get_meeting_data($m_id);
$ms_id= $m_data['ms_id'];
$ms_data = get_meeting_schedule_data($ms_id);

// 부재자 옵션
if(empty(ABSENCE_USE)) $where .= "AND (tp.tp_end_date = '0000-00-00' OR tp.m_id = {$m_id}) AND tp.tp_status <> 'absence'";

//모임스케줄 타입별 전체 필터
switch($ms_data['ms_type']) {
    case '1': $ms_all = 'OR tp.tp_ms_all = 3 OR tp.tp_ms_all = 1'; break;
    case '2': $ms_all = 'OR tp.tp_ms_all = 3 OR tp.tp_ms_all = 2'; break;
    case '3': $ms_all = 'OR tp.tp_ms_all = 3 OR tp.tp_ms_all = 4'; break;
    case '4': $ms_all = 'OR tp.tp_ms_all = 3 OR tp.tp_ms_all = 5'; break;
    case '5': $ms_all = 'OR tp.tp_ms_all = 3 OR tp.tp_ms_all = 6'; break;
    case '6': $ms_all = 'OR tp.tp_ms_all = 3 OR tp.tp_ms_all = 7'; break;
    default: $ms_all = '';
}

// 해당하는 요일의 구역 불러오기 (개인구역 제외)
$sql = "SELECT tp.tp_id, tp.tp_assigned, tp.tp_assigned_date, tp.tp_assigned_group, tp.tp_status, tp.tp_num, tp.tp_name, tp.tp_start_date, tp.tp_end_date, tp.m_id
        FROM ".TELEPHONE_TABLE." AS tp
        WHERE ((tp.ms_id <> 0 AND tp.ms_id = ".$ms_data['ms_id'].") OR (tp.ms_id <> 0 AND tp.ms_id = ".$ms_data['copy_ms_id'].") {$ms_all}) AND tp.mb_id = 0 {$where}";
$result = $mysqli->query($sql);
if($result->num_rows > 0){
    while ($row = $result->fetch_assoc()) {
        $tp_id = $row['tp_id'];

        // 배정된지 일주일이 지난 구역만 보임
        $c_minister_assign_expiration = MINISTER_TELEPHONE_ASSIGN_EXPIRATION?MINISTER_TELEPHONE_ASSIGN_EXPIRATION:'7';
        if($row['tp_assigned_date'] > date("Y-m-d", strtotime("-".$c_minister_assign_expiration." days")) && $row['m_id'] != $m_id){ continue; }

        // 배정상태 구하기
        $tp_status = (empty($row['tp_status']) && empty_date($row['tp_assigned_date']))?'unassigned':$row['tp_status'];
        
        // 진행률 구하기
        $telephone_progress = get_telephone_progress($tp_id);
        $progress_percent =($telephone_progress['total'] > 0)?floor((($telephone_progress['visit']+$telephone_progress['absence'])/$telephone_progress['total'])*100):0;

        // 부재자 방문 설정에 따른 배정 제한
        $all_past_records = get_all_past_records('telephone',$tp_id);
        
        // 진행상태
        $progress_status = '';
        if(is_array($all_past_records) && !empty($all_past_records) && isset($all_past_records[0]['progress'])) {
            $progress_status = $all_past_records[0]['progress'];
        }
        
        // 현재 상태: $tp_status 기준으로 간단하게 계산
        $current_status = (!empty($tp_status) && strpos($tp_status, 'absence') !== false) ? '1' : '0'; // 부재: 1, 전체: 0

        $assigned_group_name = '';
        if($row['tp_assigned']){
          $assigned_group_arr = get_assigned_group_name($row['tp_assigned'],$row['tp_assigned_group']);
          $assigned_group_name = (is_array($assigned_group_arr))?implode(' | ',$assigned_group_arr):$assigned_group_arr;
        }

        $data[] = array(
            'id' => $tp_id,
            'num' => $row['tp_num'],
            'name' => $row['tp_name'],
            'm_id' => $row['m_id'],
            'start_date' => (!empty($row['tp_start_date']) && $row['tp_start_date'] !== '0000-00-00')?$row['tp_start_date']:'',
            'end_date' => (!empty($row['tp_end_date']) && $row['tp_end_date'] !== '0000-00-00')?$row['tp_end_date']:'',
            'assigned_date' => (!empty($row['tp_assigned_date']) && $row['tp_assigned_date'] !== '0000-00-00')?$row['tp_assigned_date']:'',
            'status' => $tp_status,
            'total' => $telephone_progress['total'],
            'visit' => $telephone_progress['visit'],
            'absence' => $telephone_progress['absence'],
            'progress' => $progress_percent,
            'assigned_ids' => $row['tp_assigned'],
            'assigned_group' => $row['tp_assigned_group'],
            'assigned_group_name' => $assigned_group_name,
            'current_status' => $current_status,
            'progress_status' => $progress_status,
            'all_past_records' => $all_past_records
        );

    }
}

$num = array();
$name = array();
$num_prefix = array();
$num_numeric = array();
$progress_status = array();

foreach ($data as $key => $row) {
    $num[$key] = (string)$row['num'];
    $name[$key] = (string)$row['name'];
    // 접두문자(숫자 제거)와 숫자부분 분리
    $num_prefix[$key] = trim(preg_replace('/[0-9]/','', $row['num']));
    $digits = preg_replace('/[^0-9]/','', $row['num']);
    $num_numeric[$key] = $digits === '' ? 0 : (int)$digits;

    // all_past_records의 progress 값을 숫자로 변환 (정렬용)
    $progress_status_num = 0; // 기본값: incomplete
    if(isset($row['progress_status'])) {
        if($row['progress_status'] == 'in_progress') {
            $progress_status_num = 1;
        } elseif($row['progress_status'] == 'completed') {
            $progress_status_num = 2;
        }
    }

	$progress_status[$key] = $progress_status_num;
}

if(GUIDE_CARD_ORDER == '1'){ // 구역번호순 (접두문자→숫자→이름)
  array_multisort($num_prefix, SORT_ASC, $num_numeric, SORT_ASC, $name, SORT_ASC, $data);
}else{ // 추천 순 : 복잡한 정렬 로직
	// 우선순위
	// 1) progress_status (진행중 1 > 완료 2 = 진행전 0) 
	// 2) latest_past_date 유무 (없음 0 > 있음 1)
	// 3) current_status (전체 0 > 부재 1)
	// 4) progress_status (진행전 0 > 완료 2) 
	// 5) latest_past_date 오름차순
	// 6) 구역번호(접두/숫자)/이름 오름차순
	usort($data, function($a, $b) use ($progress_status, $data){
		// 1) progress_status (진행중 1 > 완료 2 = 진행전 0) - 계산된 숫자 값 사용
		$keyA = array_search($a, $data, true);
		$keyB = array_search($b, $data, true);
		$progA = isset($progress_status[$keyA]) ? $progress_status[$keyA] : 0;
		$progB = isset($progress_status[$keyB]) ? $progress_status[$keyB] : 0;
		
		// 진행중(1)만 최우선, 나머지는 순서 무관
		if($progA == 1 && $progB != 1) return -1; // A가 진행중이면 A 우선
		if($progA != 1 && $progB == 1) return 1;  // B가 진행중이면 B 우선
		// 둘 다 진행중이거나 둘 다 진행중이 아니면 다음 조건으로

		// 2) latest_past_date 유무 (없음 0 > 있음 1)
		$lpA = isset($a['latest_past_date']) && $a['latest_past_date'] ? 1 : 0;
		$lpB = isset($b['latest_past_date']) && $b['latest_past_date'] ? 1 : 0;
		if($lpA !== $lpB) return ($lpA < $lpB) ? -1 : (($lpA > $lpB) ? 1 : 0); // asc (없음 우선)

		// 3) current_status (전체 0 > 부재 1)
		$currA = isset($a['current_status']) ? (int)$a['current_status'] : 0;
		$currB = isset($b['current_status']) ? (int)$b['current_status'] : 0;
		if($currA !== $currB) return ($currA < $currB) ? -1 : (($currA > $currB) ? 1 : 0); // asc (전체 우선)

		// 4) progress_status (진행전 0 > 완료 2)
		if($progA !== $progB) return ($progA < $progB) ? -1 : (($progA > $progB) ? 1 : 0); // asc (진행전 우선)

		// 5) latest_past_date 오름차순
		$ldA = isset($a['latest_past_date']) && $a['latest_past_date'] ? $a['latest_past_date'] : '9999-12-31';
		$ldB = isset($b['latest_past_date']) && $b['latest_past_date'] ? $b['latest_past_date'] : '9999-12-31';
		
		// 날짜 형식 변환 (2024.1.23 -> 2024-01-23)
		$ldA = str_replace('.', '-', $ldA);
		$ldB = str_replace('.', '-', $ldB);
		
		if($ldA !== $ldB) return strcmp($ldA, $ldB); // asc

		// 6) 구역번호(접두/숫자)/이름 오름차순
		$preA = trim(preg_replace('/[0-9]/','', isset($a['num'])?$a['num']:''));
		$preB = trim(preg_replace('/[0-9]/','', isset($b['num'])?$b['num']:''));
		if($preA !== $preB) return strcmp($preA, $preB);
		$digA = preg_replace('/[^0-9]/','', isset($a['num'])?$a['num']:'');
		$digB = preg_replace('/[^0-9]/','', isset($b['num'])?$b['num']:'');
		$intA = $digA === '' ? 0 : (int)$digA;
		$intB = $digB === '' ? 0 : (int)$digB;
		if($intA !== $intB) return ($intA < $intB) ? -1 : (($intA > $intB) ? 1 : 0);

		$nameA = isset($a['name'])?$a['name']:'';
		$nameB = isset($b['name'])?$b['name']:'';
		return strcmp($nameA, $nameB);
	});
}

echo json_encode($data);
?>
