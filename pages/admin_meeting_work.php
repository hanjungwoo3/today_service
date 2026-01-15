<?php include_once('../config.php'); ?>

<?php
$today = date("Y-m-d");
$current_time = date("H:i:s");

if (isset($work) && $work == 'del') { // 모임 계획 삭제

  $sql = "DELETE FROM " . MEETING_SCHEDULE_TABLE . " WHERE ms_id = {$del_id}";
  $mysqli->query($sql);

  $m_sql = "DELETE FROM " . MEETING_TABLE . " WHERE ms_id = {$del_id} AND ( (m_date > '{$today}') OR ( m_date = '{$today}' AND ms_time > '{$current_time}') )";
  $mysqli->query($m_sql);

} else {

  $guide_str1 = !empty($guide1) ? implode(',', $guide1) : '';
  $guide_str2 = !empty($guide2) ? implode(',', $guide2) : '';

  if (empty($ms_id)) { // 모임 계획 추가

    $sql = "INSERT INTO " . MEETING_SCHEDULE_TABLE . "
            (ms_week, ms_time, ms_guide, ms_guide2, mp_id, ms_type, copy_ms_id, ma_id, ms_start_time, ms_finish_time, g_id, ms_limit)
            VALUES('{$week}','{$time}','{$guide_str1}','{$guide_str2}','{$place}','{$type}','{$copy_ms_id}','{$ma_id}','{$st_time}','{$fi_time}','{$group}','{$ms_limit}')";
    $mysqli->query($sql);

  } else { // 모임 계획 수정

    $ms = get_meeting_schedule_data($ms_id);
    $ms_week = $ms['ms_week'];

    $sql = "UPDATE " . MEETING_SCHEDULE_TABLE . "
            SET ms_week = '{$week}', ms_time = '{$time}', ms_guide = '{$guide_str1}', ms_guide2 = '{$guide_str2}', mp_id = '{$place}', ms_type = '{$type}', copy_ms_id = '{$copy_ms_id}', ms_start_time = '{$st_time}', ms_finish_time = '{$fi_time}', g_id = '{$group}', ms_limit = '{$ms_limit}'
            WHERE ms_id = {$ms_id}";
    $mysqli->query($sql);

    $mp = get_meeting_place_data($place);
    // st_time, fi_time 변수가 정의되지 않았을 수 있으므로 안전하게 처리
    $st_time = isset($st_time) ? $st_time : '';
    $fi_time = isset($fi_time) ? $fi_time : '';

    $m_sql = "UPDATE " . MEETING_TABLE . "
              SET ms_time = '{$time}', ms_guide = '{$guide_str1}', ms_guide2 = '{$guide_str2}', mp_id = '{$place}', ms_type = '{$type}', mp_name = '" . $mp['mp_name'] . "', mp_address = '" . $mp['mp_address'] . "', g_id = '{$group}', m_start_time = '{$st_time}', m_finish_time = '{$fi_time}', ms_limit = '{$ms_limit}'
              WHERE ms_id = {$ms_id} AND ( (m_date > '{$today}') OR ( m_date = '{$today}' AND ms_time > '{$current_time}') )";
    $mysqli->query($m_sql);

  }
}
?>