<?php include_once("../config.php");?>
<?php check_accessible('admin');?>

<?php

$where = array();
if(!empty($name)) $where[] = "mb_name LIKE '%".$name."%'";
if(!isset($moveout)) $where[] = "mb_moveout_date = '0000-00-00'";
$where = $where?'where '.implode(' AND ',$where):'';

$sql = "SELECT * FROM ".MEMBER_TABLE." ".$where." ORDER BY mb_name ASC";
$result = $mysqli->query($sql);

$data = array();
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){ 

      $data[] = array(
        'id' => $row['mb_id'],
        'mb_name' => $row['mb_name'],
        'mb_sex' => $row['mb_sex']=='M'?'형제':'자매',
        'mb_hp' => get_hp_text(decrypt($row['mb_hp'])),
        'pioneer' => $row['mb_pioneer'],
        'mb_pioneer' => get_member_pioneer_text($row['mb_pioneer']),
        'g_name' => get_group_name($row['g_id']),
        'position' => $row['mb_position'],
        'mb_position' => get_member_position_text($row['mb_position']),
        'auth' => $row['mb_auth'], 
        'mb_auth' => get_member_auth_text($row['mb_auth']),
        'mb_display' => $row['mb_display']==1?'':'선정',
        'mb_address' => decrypt($row['mb_address']),
        'mb_movein_date' => !empty_date($row['mb_movein_date'])?date('y.m.d', strtotime($row['mb_movein_date'])):'-',
        'mb_moveout_date' => !empty_date($row['mb_moveout_date'])?date('y.m.d', strtotime($row['mb_moveout_date'])):'-'
      );

    }
}

echo json_encode($data);
?>
