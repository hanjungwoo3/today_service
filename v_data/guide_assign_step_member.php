<?php include_once('../config.php');?>

<?php
$data = array();
$member_of_meeting = explode(',', get_member_of_meeting($m_id));

$sql = "SELECT mb_id, mb_name, mb_sex, mb_display FROM ".MEMBER_TABLE." WHERE mb_moveout_date = '0000-00-00' ORDER BY mb_sex DESC , mb_name ASC";
$result = $mysqli->query($sql);
if($result->num_rows > 0){
  while ($row = $result->fetch_assoc()) {
    $mb = $row;
    $mb['attend'] = (in_array($mb['mb_id'],$member_of_meeting))?'1':'0';
    $data[] = $mb;
  }
}

echo json_encode($data);
?> 
