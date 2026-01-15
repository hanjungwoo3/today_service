<?php include_once('../config.php'); ?>

<?php
/**
 * Optimized guide_assign_step_territory.php
 * Uses batch queries to prevent N+1 performance issues.
 */

$where = '';
$data = array();
$m_data = get_meeting_data($m_id);
$ms_id = $m_data['ms_id'];
$ms_data = get_meeting_schedule_data($ms_id);
$c_territory_type_use = unserialize(TERRITORY_TYPE_USE);

// 부재자 옵션
if (empty(ABSENCE_USE))
	$where .= "AND (t.tt_end_date = '0000-00-00' OR t.m_id = {$m_id}) AND t.tt_status <> 'absence'";

//모임스케줄 타입별 전체 필터
switch ($ms_data['ms_type']) {
	case '1':
		$ms_all = 'OR t.tt_ms_all = 3 OR t.tt_ms_all = 1';
		break;
	case '2':
		$ms_all = 'OR t.tt_ms_all = 3 OR t.tt_ms_all = 2';
		break;
	case '3':
		$ms_all = 'OR t.tt_ms_all = 3 OR t.tt_ms_all = 4';
		break;
	case '4':
		$ms_all = 'OR t.tt_ms_all = 3 OR t.tt_ms_all = 5';
		break;
	case '5':
		$ms_all = 'OR t.tt_ms_all = 3 OR t.tt_ms_all = 6';
		break;
	case '6':
		$ms_all = 'OR t.tt_ms_all = 3 OR t.tt_ms_all = 7';
		break;
	default:
		$ms_all = '';
}

// 구역타입 사용여부에 따라...
$in_tt_type = array();
$in_tt_type[] = '\'편지\'';
if (!isset($c_territory_type_use['type_1']) || $c_territory_type_use['type_1'] === 'use')
	$in_tt_type[] = '\'일반\'';
if (!isset($c_territory_type_use['type_2']) || $c_territory_type_use['type_2'] === 'use')
	$in_tt_type[] = '\'아파트\'';
if (!isset($c_territory_type_use['type_3']) || $c_territory_type_use['type_3'] === 'use')
	$in_tt_type[] = '\'빌라\'';
if (!isset($c_territory_type_use['type_4']) || $c_territory_type_use['type_4'] === 'use')
	$in_tt_type[] = '\'격지\'';
if (!isset($c_territory_type_use['type_7']) || $c_territory_type_use['type_7'] === 'use')
	$in_tt_type[] = '\'추가1\'';
if (!isset($c_territory_type_use['type_8']) || $c_territory_type_use['type_8'] === 'use')
	$in_tt_type[] = '\'추가2\'';

$where .= " AND t.tt_type IN (" . implode(',', $in_tt_type) . ")";

// 1. Fetch Territories
$sql = "SELECT t.tt_id, t.tt_assigned, t.tt_assigned_date, t.tt_assigned_group, t.tt_type, t.tt_status, t.tt_num, t.tt_name, t.tt_start_date, t.tt_end_date, t.m_id
        FROM " . TERRITORY_TABLE . " AS t
        WHERE ((t.ms_id <> 0 AND t.ms_id = " . $ms_data['ms_id'] . ") OR (t.ms_id <> 0 AND t.ms_id = " . $ms_data['copy_ms_id'] . ") {$ms_all}) AND t.mb_id = 0 {$where}";
$result = $mysqli->query($sql);

$territories = array();
$tt_ids = array();
$member_ids = array(); // To batch fetch member names

if ($result->num_rows > 0) {
	while ($row = $result->fetch_assoc()) {
		// 배정된지 일주일이 지난 구역만 보임 (Filter in PHP to avoid complex WHERE)
		$assign_expiration = ($row['tt_type'] == '편지') ? MINISTER_LETTER_ASSIGN_EXPIRATION : MINISTER_ASSIGN_EXPIRATION;
		$c_minister_assign_expiration = $assign_expiration ? $assign_expiration : '7';
		if ($row['tt_assigned_date'] > date("Y-m-d", strtotime("-" . $c_minister_assign_expiration . " days")) && $row['m_id'] != $m_id)
			continue;

		$territories[] = $row;
		$tt_ids[] = $row['tt_id'];

		if ($row['tt_assigned']) {
			// Split comma separated IDs
			$ids = explode(',', $row['tt_assigned']);
			foreach ($ids as $mid) {
				if (is_numeric($mid) && $mid > 0)
					$member_ids[$mid] = $mid;
			}
		}
	}
}

if (empty($territories)) {
	echo json_encode(array());
	exit;
}

$tt_ids_str = implode(',', $tt_ids);

// 2. Batch Fetch Progress
$progress_map = array();
$sql = "SELECT tt_id, count(*) as total, 
               SUM(CASE WHEN h_condition IS NOT NULL AND CAST(h_condition AS UNSIGNED) > 0 THEN 1 ELSE 0 END) as condition_count,
               SUM(CASE WHEN h_visit = 'Y' AND (h_condition IS NULL OR CAST(h_condition AS UNSIGNED) = 0) THEN 1 ELSE 0 END) as visit,
               SUM(CASE WHEN h_visit = 'N' AND (h_condition IS NULL OR CAST(h_condition AS UNSIGNED) = 0) THEN 1 ELSE 0 END) as absence
        FROM " . HOUSE_TABLE . " 
        WHERE tt_id IN ({$tt_ids_str}) 
        GROUP BY tt_id";
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
	$progress_map[$row['tt_id']] = $row;
}


// 3. Batch Fetch Past Records
// ... (Lines 116-132 skipped in replacement context, assuming correct replacement) since I am replacing chunk.
// ACTUALLY, I am targeting the block from line 102 to ... wait.
// I can just replace the query block and the loop where calculation happens.
// I will split this into two chunks.

// Chunk 1: The Query
// chunk 2: The Loop calculation (Lines 201-202)
// chunk 3: The Data assignment (Lines 375-378)



// 3. Batch Fetch Past Records
$records_map = array();
$sql = "SELECT ttr_id, tt_id, ttr_start_date, ttr_end_date, ttr_status, ttr_assigned, ttr_assigned_date
        FROM " . TERRITORY_RECORD_TABLE . " 
        WHERE tt_id IN ({$tt_ids_str}) 
        ORDER BY tt_id, create_datetime ASC";
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
	$records_map[$row['tt_id']][] = $row;

	if ($row['ttr_assigned']) {
		$ids = explode(',', $row['ttr_assigned']);
		foreach ($ids as $mid) {
			if (is_numeric($mid) && $mid > 0)
				$member_ids[$mid] = $mid;
		}
	}
}

// 4. Batch Fetch Member Names
$member_names = array();
if (!empty($member_ids)) {
	$member_ids_str = implode(',', $member_ids);
	$sql = "SELECT mb_id, mb_name FROM " . MEMBER_TABLE . " WHERE mb_id IN ({$member_ids_str})";
	$result = $mysqli->query($sql);
	while ($row = $result->fetch_assoc()) {
		$member_names[$row['mb_id']] = $row['mb_name'];
	}
}

// Helper to get member names from direct mapping or fallback
function get_names_from_map($ids_str, $map)
{
	if (empty($ids_str))
		return '';
	$ids = explode(',', $ids_str);
	$names = array();
	foreach ($ids as $id) {
		if (is_numeric($id) && isset($map[$id])) {
			$names[] = $map[$id];
		} else {
			$names[] = $id; // Fallback or if it's already a name (legacy data)
		}
	}
	return implode(', ', $names);
}

// Helper for assigned group name
function get_assigned_group_name_batched($assigned_members, $assigned_group, $member_map)
{
	$assigned_group_arr = array_filter(explode(',', $assigned_group));
	$assigned_members_arr = array_filter(explode(',', $assigned_members));

	if (empty($assigned_group_arr)) {
		return get_names_from_map($assigned_members, $member_map);
	} else {
		$result = array();

		if (count($assigned_group_arr) == 1) {
			// Reusable array splice logic on copy
			$temp_members = $assigned_members_arr;
			while ($temp_members) {
				$slice = array_splice($temp_members, 0, $assigned_group_arr[0]);
				$result[] = get_names_from_map(implode(',', $slice), $member_map);
			}
		} else {
			$temp_members = $assigned_members_arr;
			foreach ($assigned_group_arr as $group) {
				$slice = array_splice($temp_members, 0, $group);
				$result[] = get_names_from_map(implode(',', $slice), $member_map);
			}
			if ($temp_members) {
				foreach ($temp_members as $member)
					$result[] = isset($member_map[$member]) ? $member_map[$member] : $member;
			}
		}
		return $result;
	}
}


// 5. Process and Assemble Data
foreach ($territories as $row) {
	$tt_id = $row['tt_id'];

	// Progress
	$p = isset($progress_map[$tt_id]) ? $progress_map[$tt_id] : array('total' => 0, 'visit' => 0, 'absence' => 0, 'condition_count' => 0);
	$effective_total = $p['total'] - $p['condition_count'];
	$progress_percent = ($effective_total > 0) ? floor((($p['visit'] + $p['absence']) / $effective_total) * 100) : 0;

	// Past Records Logic (Refactored from get_all_past_records)
	$raw_records = array();

	// Add historic records
	if (isset($records_map[$tt_id])) {
		foreach ($records_map[$tt_id] as $rec) {
			$raw_records[] = array(
				'id' => $rec['ttr_id'],
				'table' => 'territory_record',
				'start_date' => $rec['ttr_start_date'],
				'end_date' => $rec['ttr_end_date'],
				'status' => $rec['ttr_status'],
				'assigned' => $rec['ttr_assigned'],
				'assigned_date' => $rec['ttr_assigned_date']
			);
		}
	}

	// Add current record (from territory table)
	$raw_records[] = array(
		'id' => $row['tt_id'],
		'table' => 'territory',
		'start_date' => $row['tt_start_date'],
		'end_date' => $row['tt_end_date'],
		'status' => $row['tt_status'],
		'assigned' => $row['tt_assigned'],
		'assigned_date' => $row['tt_assigned_date']
	);

	// Process Visits
	$visits = array();
	$current_visit = null;
	$current_records = array();
	$prev_status = null;
	$latest_past_date = '';
	$assigned_names_last = '';

	foreach ($raw_records as $rec) {
		$status = $rec['status'];

		// Logic to track latest start date
		if (!empty_date($rec['start_date'])) {
			// For the territory row (last one), we check specifically if it matches what original code did
			// Original: if(!empty_date($row['tt_start_date'])) $latest_past_date = $row['tt_start_date'];
			// else check all_past_records...
			// Here we are iterating all. The logic in original code was:
			// 1. If current tt_start_date exists, use it.
			// 2. Else loop past records and find 'latest' start date.
			// We can simulate this by finding the last valid start date in the sequence, 
			// BUT essentially we just need to know if the current active assignment has a start date.
		}

		// Grouping logic
		if ($current_visit === null) {
			$current_visit = strpos($status, 'absence') !== false ? '부재' : '전체';
			$current_records[] = $rec;
			$prev_status = $status;
			continue;
		}

		if (
			$status === '' || $status === 'absence' ||
			!(
				($status === '' && $prev_status === 'reassign') ||
				($prev_status === '' && $status === 'reassign') ||
				($status === 'absence' && $prev_status === 'absence_reassign') ||
				($prev_status === 'absence' && $status === 'absence_reassign')
			)
		) {
			// Finish previous visit
			$has_any_start = false;
			foreach ($current_records as $r)
				if (!empty_date($r['start_date']))
					$has_any_start = true;
			$prog_status = $has_any_start ? 'in_progress' : 'incomplete';

			// Check if completed
			$has_start_end = false;
			// Simplified check: if any record has start AND any record has end? 
			// Original code: if($has_any_start && $has_any_end) -> completed.
			$has_st = 0;
			$has_ed = 0;
			foreach ($current_records as $r) {
				if (!empty_date($r['start_date']))
					$has_st = 1;
				if (!empty_date($r['end_date']))
					$has_ed = 1;
			}
			if ($has_st && $has_ed)
				$prog_status = 'completed';


			$visits[] = array(
				'visit' => $current_visit,
				'progress' => $prog_status,
				'records' => array_reverse($current_records) // Reverse records inside visit
			);

			// Start new visit
			$current_visit = strpos($status, 'absence') !== false ? '부재' : '전체';
			$current_records = array($rec);
		} else {
			$current_records[] = $rec;
		}
		$prev_status = $status;
	}

	// Push last visit
	if ($current_visit !== null) {
		$has_st = 0;
		$has_ed = 0;
		foreach ($current_records as $r) {
			if (!empty_date($r['start_date']))
				$has_st = 1;
			if (!empty_date($r['end_date']))
				$has_ed = 1;
		}
		$prog_status = ($has_st && $has_ed) ? 'completed' : ($has_st ? 'in_progress' : 'incomplete');

		$visits[] = array(
			'visit' => $current_visit,
			'progress' => $prog_status,
			'records' => array_reverse($current_records)
		);
	}

	$all_past_records = array_reverse($visits);

	// Find latest_past_date logic from original code
	// 1. Check current tt_start_date
	$latest_past_date = '';
	if (!empty_date($row['tt_start_date'])) {
		$latest_past_date = $row['tt_start_date'];
	} elseif (!empty($all_past_records)) {
		foreach ($all_past_records as $visit_data) {
			if (isset($visit_data['records']) && is_array($visit_data['records'])) {
				foreach ($visit_data['records'] as $record) {
					if (!empty_date($record['start_date'])) {
						$latest_past_date = $record['start_date'];
						break 2;
					}
				}
			}
		}
	}

	// Progress Status from latest visit
	$progress_status = '';
	if (is_array($all_past_records) && !empty($all_past_records) && isset($all_past_records[0]['progress'])) {
		$progress_status = $all_past_records[0]['progress'];
	}

	$tt_status = (empty($row['tt_status']) && empty_date($row['tt_assigned_date'])) ? 'unassigned' : $row['tt_status'];
	$current_status = (!empty($tt_status) && strpos($tt_status, 'absence') !== false) ? '1' : '0';

	$assigned_group_name = '';
	if ($row['tt_assigned']) {
		$names_arr = get_assigned_group_name_batched($row['tt_assigned'], $row['tt_assigned_group'], $member_names);
		$assigned_group_name = (is_array($names_arr)) ? implode(' | ', $names_arr) : $names_arr;
	}

	$data[] = array(
		'id' => $tt_id,
		'num' => $row['tt_num'],
		'name' => $row['tt_name'],
		'type' => get_type_text($row['tt_type']),
		'm_id' => $row['m_id'],
		'start_date' => (!empty($row['tt_start_date']) && $row['tt_start_date'] !== '0000-00-00') ? $row['tt_start_date'] : '',
		'end_date' => (!empty($row['tt_end_date']) && $row['tt_end_date'] !== '0000-00-00') ? $row['tt_end_date'] : '',
		'assigned_date' => (!empty($row['tt_assigned_date']) && $row['tt_assigned_date'] !== '0000-00-00') ? $row['tt_assigned_date'] : '',
		'status' => $tt_status,
		'total' => $effective_total,
		'visit' => $p['visit'],
		'absence' => $p['absence'],
		'progress' => $progress_percent,
		'assigned_ids' => $row['tt_assigned'],
		'assigned_group' => $row['tt_assigned_group'],
		'assigned_group_name' => $assigned_group_name,
		'current_status' => $current_status,
		'progress_status' => $progress_status,
		'latest_past_date' => $latest_past_date,
		'all_past_records' => $all_past_records
	);
}

// Sorting Logic (Same as original)
$num = array();
$name = array();
$num_prefix = array();
$num_numeric = array();
$progress_status_arr = array();

foreach ($data as $key => $row) {
	$num[$key] = (string) $row['num'];
	$name[$key] = (string) $row['name'];
	$num_prefix[$key] = trim(preg_replace('/[0-9]/', '', $row['num']));
	$digits = preg_replace('/[^0-9]/', '', $row['num']);
	$num_numeric[$key] = $digits === '' ? 0 : (int) $digits;

	$progress_status_num = 0;
	if (isset($row['progress_status'])) {
		if ($row['progress_status'] == 'in_progress') {
			$progress_status_num = 1;
		} elseif ($row['progress_status'] == 'completed') {
			$progress_status_num = 2;
		}
	}
	$progress_status_arr[$key] = $progress_status_num;
}

if (GUIDE_CARD_ORDER == '1') {
	array_multisort($num_prefix, SORT_ASC, $num_numeric, SORT_ASC, $name, SORT_ASC, $data);
} else {
	usort($data, function ($a, $b) use ($progress_status_arr, $data) {
		$keyA = array_search($a, $data, true);
		$keyB = array_search($b, $data, true);
		$progA = isset($progress_status_arr[$keyA]) ? $progress_status_arr[$keyA] : 0;
		$progB = isset($progress_status_arr[$keyB]) ? $progress_status_arr[$keyB] : 0;

		if ($progA == 1 && $progB != 1)
			return -1;
		if ($progA != 1 && $progB == 1)
			return 1;

		$lpA = isset($a['latest_past_date']) && $a['latest_past_date'] ? 1 : 0;
		$lpB = isset($b['latest_past_date']) && $b['latest_past_date'] ? 1 : 0;
		if ($lpA !== $lpB)
			return ($lpA < $lpB) ? -1 : (($lpA > $lpB) ? 1 : 0);

		$currA = isset($a['current_status']) ? (int) $a['current_status'] : 0;
		$currB = isset($b['current_status']) ? (int) $b['current_status'] : 0;
		if ($currA !== $currB)
			return ($currA < $currB) ? -1 : (($currA > $currB) ? 1 : 0);

		if ($progA !== $progB)
			return ($progA < $progB) ? -1 : (($progA > $progB) ? 1 : 0);

		$ldA = isset($a['latest_past_date']) && $a['latest_past_date'] ? $a['latest_past_date'] : '9999-12-31';
		$ldB = isset($b['latest_past_date']) && $b['latest_past_date'] ? $b['latest_past_date'] : '9999-12-31';
		$ldA = str_replace('.', '-', $ldA);
		$ldB = str_replace('.', '-', $ldB);
		if ($ldA !== $ldB)
			return strcmp($ldA, $ldB);

		$preA = trim(preg_replace('/[0-9]/', '', isset($a['num']) ? $a['num'] : ''));
		$preB = trim(preg_replace('/[0-9]/', '', isset($b['num']) ? $b['num'] : ''));
		if ($preA !== $preB)
			return strcmp($preA, $preB);
		$digA = preg_replace('/[^0-9]/', '', isset($a['num']) ? $a['num'] : '');
		$digB = preg_replace('/[^0-9]/', '', isset($b['num']) ? $b['num'] : '');
		$intA = $digA === '' ? 0 : (int) $digA;
		$intB = $digB === '' ? 0 : (int) $digB;
		if ($intA !== $intB)
			return ($intA < $intB) ? -1 : (($intA > $intB) ? 1 : 0);

		$nameA = isset($a['name']) ? $a['name'] : '';
		$nameB = isset($b['name']) ? $b['name'] : '';
		return strcmp($nameA, $nameB);
	});
}

echo json_encode($data);
?>