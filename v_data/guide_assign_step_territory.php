<?php include_once('../config.php');?>

<?php
$where = '';
$data = array();
$m_data = get_meeting_data($m_id);
$ms_id= $m_data['ms_id'];
$ms_data = get_meeting_schedule_data($ms_id);
$c_territory_type_use = unserialize(TERRITORY_TYPE_USE);

// 부재자 옵션
if(empty(ABSENCE_USE)) $where .= "AND (t.tt_end_date = '0000-00-00' OR t.m_id = {$m_id}) AND t.tt_status <> 'absence'";

//모임스케줄 타입별 전체 필터
switch ($ms_data['ms_type']) {
    case '1': $ms_all = 'OR t.tt_ms_all = 3 OR t.tt_ms_all = 1'; break;
    case '2': $ms_all = 'OR t.tt_ms_all = 3 OR t.tt_ms_all = 2'; break;
    case '3': $ms_all = 'OR t.tt_ms_all = 3 OR t.tt_ms_all = 4'; break;
    case '4': $ms_all = 'OR t.tt_ms_all = 3 OR t.tt_ms_all = 5'; break;
    case '5': $ms_all = 'OR t.tt_ms_all = 3 OR t.tt_ms_all = 6'; break;
    case '6': $ms_all = 'OR t.tt_ms_all = 3 OR t.tt_ms_all = 7'; break;
    default: $ms_all = '';
}

// 구역타입 사용여부에 따라...
$in_tt_type = array();
$in_tt_type[] = '\'편지\'';
if(!isset($c_territory_type_use['type_1']) ||  $c_territory_type_use['type_1'] === 'use') $in_tt_type[] = '\'일반\'';
if(!isset($c_territory_type_use['type_2']) ||  $c_territory_type_use['type_2'] === 'use') $in_tt_type[] = '\'아파트\'';
if(!isset($c_territory_type_use['type_3']) ||  $c_territory_type_use['type_3'] === 'use') $in_tt_type[] = '\'빌라\'';
if(!isset($c_territory_type_use['type_4']) ||  $c_territory_type_use['type_4'] === 'use') $in_tt_type[] = '\'격지\'';
if(!isset($c_territory_type_use['type_7']) ||  $c_territory_type_use['type_7'] === 'use') $in_tt_type[] = '\'추가1\'';
if(!isset($c_territory_type_use['type_8']) ||  $c_territory_type_use['type_8'] === 'use') $in_tt_type[] = '\'추가2\'';

$where .= " AND t.tt_type IN (".implode(',',$in_tt_type).")";

// 해당하는 요일의 구역 불러오기 (개인구역 제외)
$sql = "SELECT t.tt_id, t.tt_assigned, t.tt_assigned_date, t.tt_assigned_group, t.tt_type, t.tt_status, t.tt_num, t.tt_name, t.tt_start_date, t.tt_end_date, t.m_id, t.tt_address
        FROM ".TERRITORY_TABLE." AS t
        WHERE ((t.ms_id <> 0 AND t.ms_id = ".$ms_data['ms_id'].") OR (t.ms_id <> 0 AND t.ms_id = ".$ms_data['copy_ms_id'].") {$ms_all}) AND t.mb_id = 0 {$where}";
$result = $mysqli->query($sql);
if($result->num_rows > 0){
	while ($row = $result->fetch_assoc()) {
		$tt_id = $row['tt_id'];
 
		// 배정된지 일주일이 지난 구역만 보임
		$assign_expiration = ($row['tt_type'] == '편지')?MINISTER_LETTER_ASSIGN_EXPIRATION:MINISTER_ASSIGN_EXPIRATION;
		$c_minister_assign_expiration = $assign_expiration?$assign_expiration:'7';
		if($row['tt_assigned_date'] > date("Y-m-d", strtotime("-".$c_minister_assign_expiration." days")) && $row['m_id'] != $m_id) continue;

		// 배정상태 구하기
		$tt_status = (empty($row['tt_status']) && empty_date($row['tt_assigned_date']))?'unassigned':$row['tt_status'];

		// 진행률 구하기
		$territory_progress = get_territory_progress($tt_id);
		$progress_percent = ($territory_progress['total'] > 0)?floor((($territory_progress['visit']+$territory_progress['absence'])/$territory_progress['total'])*100):0;

		// 부재자 방문 설정에 따른 배정 제한
		$all_past_records = get_all_past_records('territory',$tt_id);

		// 진행상태
		$progress_status = '';
		if(is_array($all_past_records) && !empty($all_past_records) && isset($all_past_records[0]['progress'])) {
			$progress_status = $all_past_records[0]['progress'];
		}
		
		// 현재 상태: $tt_status 기준으로 간단하게 계산
		$current_status = (!empty($tt_status) && strpos($tt_status, 'absence') !== false) ? '1' : '0'; // 부재: 1, 전체: 0

		$assigned_group_name = '';
		if($row['tt_assigned']){
			$assigned_group_arr = get_assigned_group_name($row['tt_assigned'],$row['tt_assigned_group']);
			$assigned_group_name = (is_array($assigned_group_arr))?implode(' | ',$assigned_group_arr):$assigned_group_arr;
		}

		$data[] = array(
			'id' => $tt_id,
			'num' => $row['tt_num'],
			'name' => $row['tt_name'],
			'type' => get_type_text($row['tt_type']),
			'm_id' => $row['m_id'],
			'start_date' => (!empty($row['tt_start_date']) && $row['tt_start_date'] !== '0000-00-00')?$row['tt_start_date']:'',
			'end_date' => (!empty($row['tt_end_date']) && $row['tt_end_date'] !== '0000-00-00')?$row['tt_end_date']:'',
			'assigned_date' => (!empty($row['tt_assigned_date']) && $row['tt_assigned_date'] !== '0000-00-00')?$row['tt_assigned_date']:'',
			'status' => $tt_status,
			'total' => $territory_progress['total'],
			'visit' => $territory_progress['visit'],
			'absence' => $territory_progress['absence'],
			'progress' => $progress_percent,
			'assigned_ids' => $row['tt_assigned'],
			'assigned_group' => $row['tt_assigned_group'],
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
	$lp_start_date[$key] = isset($row['latest_past_date']) ? $row['latest_past_date'] : '';
	
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
