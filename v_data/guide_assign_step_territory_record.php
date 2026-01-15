<?php include_once('../config.php'); ?>

<?php
$data = array();

$sql = "SELECT ttr_id, tt_id, ttr_assigned, ttr_assigned_date, ttr_start_date, ttr_end_date, ttr_status, ttr_assigned_group
        FROM " . TERRITORY_RECORD_TABLE . " WHERE m_id = " . $m_id . " AND ttr_mb_name = '' ORDER BY ttr_id ASC";
$result = $mysqli->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        $t_sql = "SELECT tt_num, tt_name, tt_type FROM " . TERRITORY_TABLE . " WHERE tt_id = " . $row['tt_id'];
        $t_result = $mysqli->query($t_sql);
        if ($t_result->num_rows > 0) {
            $t_row = $t_result->fetch_assoc();

            // 배정상태 구하기
            $ttr_status = (empty($row['ttr_status']) && empty_date($row['ttr_assigned_date'])) ? 'unassigned' : $row['ttr_status'];
            $status = get_status_text($ttr_status);

            $assigned_group_arr = get_assigned_group($row['ttr_assigned'], $row['ttr_assigned_group']);

            // 다차원 배열이 반환될 가능성 대비
            if (is_array($assigned_group_arr)) {
                $flattened = [];
                foreach ($assigned_group_arr as $item) {
                    if (is_array($item)) {
                        $flattened[] = implode(',', $item);
                    } else {
                        $flattened[] = $item;
                    }
                }
                $assigned_group_name = implode(' | ', $flattened);
            } else {
                $assigned_group_name = $assigned_group_arr;
            }

            // 진행률 구하기
            $territory_progress = get_territory_progress($row['tt_id']);
            $effective_total = $territory_progress['total'] - $territory_progress['condition'];
            $progress_percent = ($effective_total > 0) ? floor((($territory_progress['visit'] + $territory_progress['absence']) / $effective_total) * 100) : 0;

            // 방문 기록 가져오기 (과거 기록에서는 봉사 기록 기반으로 방문 정보 구성)
            $all_past_records = array();
            if (!empty_date($row['ttr_start_date']) || !empty_date($row['ttr_end_date'])) {
                $visit_records = array();
                if (!empty_date($row['ttr_start_date'])) {
                    $visit_records[] = array(
                        'start_date' => $row['ttr_start_date'],
                        'end_date' => $row['ttr_end_date'],
                        'table' => 'territory'
                    );
                }
                if (!empty($visit_records)) {
                    $all_past_records[] = array(
                        'visit' => '전체',
                        'records' => $visit_records
                    );
                }
            }

            $data[] = array(
                'id' => $row['ttr_id'],
                'num' => $t_row['tt_num'],
                'name' => $t_row['tt_name'],
                'og_type' => $t_row['tt_type'],
                'type' => get_type_text($t_row['tt_type']),
                'start_date' => !empty_date($row['ttr_start_date']) ? $row['ttr_start_date'] : '',
                'end_date' => !empty_date($row['ttr_end_date']) ? $row['ttr_end_date'] : '',
                'assigned_date' => !empty_date($row['ttr_assigned_date']) ? $row['ttr_assigned_date'] : '',
                'assigned_names' => $row['ttr_assigned'],
                'status' => $status,
                'assigned_group_name' => $assigned_group_name,
                'all_past_records' => $all_past_records,
                'total' => $effective_total,
                'visit' => $territory_progress['visit'],
                'absence' => $territory_progress['absence'],
                'progress' => $progress_percent
            );
        }
    }
}

echo json_encode($data);
?>