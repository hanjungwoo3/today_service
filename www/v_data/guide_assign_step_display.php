<?php include_once('../config.php');?>

<?php
$data = array();
$m_data = get_meeting_data($m_id);
$ms_id = $m_data['ms_id'];
$ms_data = get_meeting_schedule_data($ms_id);

//모임스케줄 타입별 전체 필터
switch ($ms_data['ms_type']) {
    case '1': $ms_all = 'OR dp.d_ms_all = 3 OR dp.d_ms_all = 1'; break;
    case '2': $ms_all = 'OR dp.d_ms_all = 3 OR dp.d_ms_all = 2'; break;
    case '3': $ms_all = 'OR dp.d_ms_all = 3 OR dp.d_ms_all = 4'; break;
    case '4': $ms_all = 'OR dp.d_ms_all = 3 OR dp.d_ms_all = 5'; break;
    case '5': $ms_all = 'OR dp.d_ms_all = 3 OR dp.d_ms_all = 6'; break;
    case '6': $ms_all = 'OR dp.d_ms_all = 3 OR dp.d_ms_all = 7'; break;
    default: $ms_all = '';
}

$sql = "SELECT * FROM ".DISPLAY_PLACE_TABLE." dp WHERE ((dp.ms_id <> 0 AND dp.ms_id = ".$ms_data['ms_id'].") OR (dp.ms_id <> 0 AND dp.ms_id = ".$ms_data['copy_ms_id'].") {$ms_all}) order by dp_name ASC";
$result = $mysqli->query($sql);
if($result->num_rows > 0){
  while ($row = $result->fetch_assoc()) {

    for($i=1;$i <= $row['dp_count']; $i++){

        $d_id = '';
        $d_m_id = '';
        $d_assigned = '';
        $d_assigned_group = '';
        $d_assigned_date = '';
        $assigned_group_name = '';

        $d_sql = "SELECT * FROM ".DISPLAY_TABLE." WHERE dp_id = '".$row['dp_id']."' AND dp_num = {$i} AND m_id = {$m_id}";
        $d_result = $mysqli->query($d_sql);

        if($d_result->num_rows > 0){
          $d_row = $d_result->fetch_assoc();
          if($d_row['d_assigned']){
            $assigned_group_arr = get_assigned_group_name($d_row['d_assigned'],$d_row['d_assigned_group']);
            $assigned_group_name = (is_array($assigned_group_arr))?implode(' | ',$assigned_group_arr):$assigned_group_arr;

            $d_id = $d_row['d_id'];
            $d_m_id = $d_row['m_id'];
            $d_assigned = $d_row['d_assigned'];
            $d_assigned_group = $d_row['d_assigned_group'];
            $d_assigned_date = $d_row['d_assigned_date'];
          }
        }

        $data[] = array(
            'id' => $row['dp_id'].'_'.$i,
            'dp_id' => $row['dp_id'],
            'd_id' => $d_id,
            'm_id' => $d_m_id,
            'name' => $row['dp_name'],
            'assigned_date' => $d_assigned_date,
            'assigned_group_name' => $assigned_group_name,
            'assigned_ids' => $d_assigned,
            'assigned_group' => $d_assigned_group,
            'address' => $row['dp_address'],
            'num' => $i
        );

    }

  }
}

echo json_encode($data);
?>
