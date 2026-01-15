<?php include_once('../config.php'); ?>

<?php
/**
 * Optimized guide_assign_step_telephone.php
 * Uses batch queries to prevent N+1 performance issues.
 */

$where = '';
$data = array();
$m_data = get_meeting_data($m_id);
$ms_id = $m_data['ms_id'];
$ms_data = get_meeting_schedule_data($ms_id);

// 부재자 옵션
if (empty(ABSENCE_USE))
    $where .= "AND (tp.tp_end_date = '0000-00-00' OR tp.m_id = {$m_id}) AND tp.tp_status <> 'absence'";

//모임스케줄 타입별 전체 필터
switch ($ms_data['ms_type']) {
    case '1':
        $ms_all = 'OR tp.tp_ms_all = 3 OR tp.tp_ms_all = 1';
        break;
    case '2':
        $ms_all = 'OR tp.tp_ms_all = 3 OR tp.tp_ms_all = 2';
        break;
    case '3':
        $ms_all = 'OR tp.tp_ms_all = 3 OR tp.tp_ms_all = 4';
        break;
    case '4':
        $ms_all = 'OR tp.tp_ms_all = 3 OR tp.tp_ms_all = 5';
        break;
    case '5':
        $ms_all = 'OR tp.tp_ms_all = 3 OR tp.tp_ms_all = 6';
        break;
    case '6':
        $ms_all = 'OR tp.tp_ms_all = 3 OR tp.tp_ms_all = 7';
        break;
    default:
        $ms_all = '';
}

// 1. Fetch Telephone Territories
$sql = "SELECT tp.tp_id, tp.tp_assigned, tp.tp_assigned_date, tp.tp_assigned_group, tp.tp_status, tp.tp_num, tp.tp_name, tp.tp_start_date, tp.tp_end_date, tp.m_id
        FROM " . TELEPHONE_TABLE . " AS tp
        WHERE ((tp.ms_id <> 0 AND tp.ms_id = " . $ms_data['ms_id'] . ") OR (tp.ms_id <> 0 AND tp.ms_id = " . $ms_data['copy_ms_id'] . ") {$ms_all}) AND tp.mb_id = 0 {$where}";
$result = $mysqli->query($sql);

$telephones = array();
$tp_ids = array();
$member_ids = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $assign_expiration = MINISTER_TELEPHONE_ASSIGN_EXPIRATION ? MINISTER_TELEPHONE_ASSIGN_EXPIRATION : '7';
        if ($row['tp_assigned_date'] > date("Y-m-d", strtotime("-" . $assign_expiration . " days")) && $row['m_id'] != $m_id) {
            continue;
        }

        $telephones[] = $row;
        $tp_ids[] = $row['tp_id'];

        if ($row['tp_assigned']) {
            $ids = explode(',', $row['tp_assigned']);
            foreach ($ids as $mid) {
                if (is_numeric($mid) && $mid > 0)
                    $member_ids[$mid] = $mid;
            }
        }
    }
}

if (empty($telephones)) {
    echo json_encode(array());
    exit;
}

$tp_ids_str = implode(',', $tp_ids);

// 2. Batch Fetch Progress
$progress_map = array();
$sql = "SELECT tp_id, count(*) as total, 
               SUM(CASE WHEN tph_condition IS NOT NULL AND CAST(tph_condition AS UNSIGNED) > 0 THEN 1 ELSE 0 END) as condition_count,
               SUM(CASE WHEN tph_visit = 'Y' AND (tph_condition IS NULL OR CAST(tph_condition AS UNSIGNED) = 0) THEN 1 ELSE 0 END) as visit,
               SUM(CASE WHEN tph_visit = 'N' AND (tph_condition IS NULL OR CAST(tph_condition AS UNSIGNED) = 0) THEN 1 ELSE 0 END) as absence
        FROM " . TELEPHONE_HOUSE_TABLE . " 
        WHERE tp_id IN ({$tp_ids_str}) 
        GROUP BY tp_id";
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    $progress_map[$row['tp_id']] = $row;
}

// 3. Batch Fetch Past Records
$records_map = array();
$sql = "SELECT tpr_id, tp_id, tpr_start_date, tpr_end_date, tpr_status, tpr_assigned, tpr_assigned_date
        FROM " . TELEPHONE_RECORD_TABLE . " 
        WHERE tp_id IN ({$tp_ids_str}) 
        ORDER BY tp_id, create_datetime ASC";
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    $records_map[$row['tp_id']][] = $row;

    if ($row['tpr_assigned']) {
        $ids = explode(',', $row['tpr_assigned']);
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

// Helper functions (same as in territory file)
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
            $names[] = $id;
        }
    }
    return implode(', ', $names);
}

function get_assigned_group_name_batched($assigned_members, $assigned_group, $member_map)
{
    $assigned_group_arr = array_filter(explode(',', $assigned_group));
    $assigned_members_arr = array_filter(explode(',', $assigned_members));

    if (empty($assigned_group_arr)) {
        return get_names_from_map($assigned_members, $member_map);
    } else {
        $result = array();
        if (count($assigned_group_arr) == 1) {
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
foreach ($telephones as $row) {
    $tp_id = $row['tp_id'];

    // Progress
    $p = isset($progress_map[$tp_id]) ? $progress_map[$tp_id] : array('total' => 0, 'visit' => 0, 'absence' => 0, 'condition_count' => 0);
    $effective_total = $p['total'] - $p['condition_count'];
    $progress_percent = ($effective_total > 0) ? floor((($p['visit'] + $p['absence']) / $effective_total) * 100) : 0;

    // Past Records
    $raw_records = array();
    if (isset($records_map[$tp_id])) {
        foreach ($records_map[$tp_id] as $rec) {
            $raw_records[] = array(
                'id' => $rec['tpr_id'],
                'table' => 'telephone_record',
                'start_date' => $rec['tpr_start_date'],
                'end_date' => $rec['tpr_end_date'],
                'status' => $rec['tpr_status'],
                'assigned' => $rec['tpr_assigned'],
                'assigned_date' => $rec['tpr_assigned_date']
            );
        }
    }

    // Add current record
    $raw_records[] = array(
        'id' => $row['tp_id'],
        'table' => 'telephone',
        'start_date' => $row['tp_start_date'],
        'end_date' => $row['tp_end_date'],
        'status' => $row['tp_status'],
        'assigned' => $row['tp_assigned'],
        'assigned_date' => $row['tp_assigned_date']
    );

    // Process Visits (Same logic)
    $visits = array();
    $current_visit = null;
    $current_records = array();
    $prev_status = null;

    foreach ($raw_records as $rec) {
        $status = $rec['status'];

        if ($current_visit === null) {
            $current_visit = strpos($status, 'absence') !== false ? '부재' : '전체';
            $current_records[] = $rec;
            $prev_status = $status;
            continue;
        }

        if (
            $status === '' || $status === 'absence' ||
            !(($status === '' && $prev_status === 'reassign') ||
                ($prev_status === '' && $status === 'reassign') ||
                ($status === 'absence' && $prev_status === 'absence_reassign') ||
                ($prev_status === 'absence' && $status === 'absence_reassign'))
        ) {

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

            $current_visit = strpos($status, 'absence') !== false ? '부재' : '전체';
            $current_records = array($rec);
        } else {
            $current_records[] = $rec;
        }
        $prev_status = $status;
    }

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

    $latest_past_date = '';
    if (!empty_date($row['tp_start_date'])) {
        $latest_past_date = $row['tp_start_date'];
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

    $progress_status = '';
    if (is_array($all_past_records) && !empty($all_past_records) && isset($all_past_records[0]['progress'])) {
        $progress_status = $all_past_records[0]['progress'];
    }

    $tp_status = (empty($row['tp_status']) && empty_date($row['tp_assigned_date'])) ? 'unassigned' : $row['tp_status'];
    $current_status = (!empty($tp_status) && strpos($tp_status, 'absence') !== false) ? '1' : '0';

    $assigned_group_name = '';
    if ($row['tp_assigned']) {
        $names_arr = get_assigned_group_name_batched($row['tp_assigned'], $row['tp_assigned_group'], $member_names);
        $assigned_group_name = (is_array($names_arr)) ? implode(' | ', $names_arr) : $names_arr;
    }

    $data[] = array(
        'id' => $tp_id,
        'num' => $row['tp_num'],
        'name' => $row['tp_name'],
        'm_id' => $row['m_id'],
        'start_date' => (!empty($row['tp_start_date']) && $row['tp_start_date'] !== '0000-00-00') ? $row['tp_start_date'] : '',
        'end_date' => (!empty($row['tp_end_date']) && $row['tp_end_date'] !== '0000-00-00') ? $row['tp_end_date'] : '',
        'assigned_date' => (!empty($row['tp_assigned_date']) && $row['tp_assigned_date'] !== '0000-00-00') ? $row['tp_assigned_date'] : '',
        'status' => $tp_status,
        'total' => $effective_total,
        'visit' => $p['visit'],
        'absence' => $p['absence'],
        'progress' => $progress_percent,
        'assigned_ids' => $row['tp_assigned'],
        'assigned_group' => $row['tp_assigned_group'],
        'assigned_group_name' => $assigned_group_name,
        'current_status' => $current_status,
        'progress_status' => $progress_status,
        'latest_past_date' => $latest_past_date,
        'all_past_records' => $all_past_records
    );
}

// Sorting (Same as original)
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