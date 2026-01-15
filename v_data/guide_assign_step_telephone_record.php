<?php include_once('../config.php'); ?>

<?php
$data = array();

$sql = "SELECT tpr_id, tp_id,  tpr_assigned, tpr_assigned_date, tpr_start_date, tpr_end_date, tpr_status, tpr_assigned_group
        FROM " . TELEPHONE_RECORD_TABLE . " WHERE m_id = " . $m_id . " AND tpr_mb_name = '' ORDER BY tpr_id ASC";
$result = $mysqli->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        $t_sql = "SELECT tp_num, tp_name FROM " . TELEPHONE_TABLE . " WHERE tp_id = " . $row['tp_id'];
        $t_result = $mysqli->query($t_sql);
        if ($t_result->num_rows > 0) {
            $t_row = $t_result->fetch_assoc();

            // 배정상태 구하기
            $tpr_status = (empty($row['tpr_status']) && empty_date($row['tpr_assigned_date'])) ? 'unassigned' : $row['tpr_status'];
            $status = get_status_text($tpr_status);

            $assigned_group_arr = get_assigned_group($row['tpr_assigned'], $row['tpr_assigned_group']);

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
            $telephone_progress = get_telephone_progress($row['tp_id']);
            $effective_total = $telephone_progress['total'] - $telephone_progress['condition'];
            $progress_percent = ($effective_total > 0) ? floor((($telephone_progress['visit'] + $telephone_progress['absence']) / $effective_total) * 100) : 0;

            // 방문 기록 가져오기 (과거 기록에서는 봉사 기록 기반으로 방문 정보 구성)
            $all_past_records = array();
            if (!empty_date($row['tpr_start_date']) || !empty_date($row['tpr_end_date'])) {
                $visit_records = array();
                if (!empty_date($row['tpr_start_date'])) {
                    $visit_records[] = array(
                        'start_date' => $row['tpr_start_date'],
                        'end_date' => $row['tpr_end_date'],
                        'table' => 'telephone'
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
                'id' => $row['tpr_id'],
                'num' => $t_row['tp_num'],
                'name' => $t_row['tp_name'],
                'start_date' => !empty_date($row['tpr_start_date']) ? $row['tpr_start_date'] : '',
                'end_date' => !empty_date($row['tpr_end_date']) ? $row['tpr_end_date'] : '',
                'assigned_date' => !empty_date($row['tpr_assigned_date']) ? $row['tpr_assigned_date'] : '',
                'assigned_names' => $row['tpr_assigned'],
                'status' => $status,
                'assigned_group_name' => $assigned_group_name,
                'all_past_records' => $all_past_records,
                'total' => $effective_total,
                'visit' => $telephone_progress['visit'],
                'absence' => $telephone_progress['absence'],
                'progress' => $progress_percent
            );
        }
    }
}

echo json_encode($data);
?>