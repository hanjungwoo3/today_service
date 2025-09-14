<?php include_once('../config.php');?>

<?php
$m_id = get_meeting_id($s_date, $ms_id);

if($work == 'appoint'){
  $sql = "UPDATE ".MEETING_TABLE." SET m_guide = '{$guide}' WHERE m_id = '{$m_id}'";
}else{
  if($work == '0') $reason = '';
  $sql = "UPDATE ".MEETING_TABLE." SET m_cancle = '{$work}', m_cancle_reason = '{$reason}' WHERE m_id = '{$m_id}'";
}

$mysqli->query($sql);
?>
