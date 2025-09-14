<?php include_once("../config.php");?>
<?php check_accessible('admin');?>

<?php
$c_territory_type = unserialize(TERRITORY_TYPE);
$where = array();

if($type == 1||$type == 3){ // 호별/편지 봉사일떄
  $sql = "SELECT *,t.mb_id as t_mb_id, h.mb_id as h_mb_id FROM ".HOUSE_TABLE." h INNER JOIN ".TERRITORY_TABLE." t ON h.tt_id = t.tt_id
          WHERE h.h_id = {$id} ORDER BY h_address1, h_address2*1, h_address3*1 , h_address4*1, h_address5*1 ASC";
}elseif($type == 2){ // 전화봉사일때
  $sql = "SELECT *,tp.mb_id as tp_mb_id, tph.mb_id as tph_mb_id  FROM ".TELEPHONE_HOUSE_TABLE." tph INNER JOIN ".TELEPHONE_TABLE." tp ON tph.tp_id = tp.tp_id
          WHERE tph.tph_id = {$id} ORDER BY tph_name ASC";
}
$result = $mysqli->query($sql);

$data = array();

if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $h_condition = "";
      $cdate = "";
      $mb_name = "";

      if($type == 1||$type == 3){
        $h_condition = $row['h_condition'];
        $h_id = $row['h_id'];

        if($h_condition == '1' || $h_condition == '2'){
          $s_sql = "SELECT create_datetime as cdate FROM ".RETURN_VISIT_TABLE." WHERE h_id = '{$h_id}' ORDER BY create_datetime DESC LIMIT 1";
        }else{
          $s_sql = "SELECT create_datetime as cdate, mb_id FROM ".HOUSE_MEMO_TABLE." WHERE h_id = '{$h_id}' ORDER BY create_datetime DESC LIMIT 1";
        }
        $s_result = $mysqli->query($s_sql);
        $sow = $s_result->fetch_assoc();

        $mb_name = ($h_condition == '1' || $h_condition == '2')?get_member_name($row['h_mb_id']):(isset($sow['mb_id']) ? get_member_name($sow['mb_id']) : '');

      }elseif($type == 2){
        $h_condition = $row['tph_condition'];
        $h_id = $row['tph_id'];

        if($h_condition == '1' || $h_condition == '2'){
          $s_sql = "SELECT create_datetime as cdate FROM ".TELEPHONE_RETURN_VISIT_TABLE." WHERE tph_id = '{$h_id}' ORDER BY create_datetime DESC LIMIT 1";
        }else{
          $s_sql = "SELECT create_datetime as cdate, mb_id FROM ".TELEPHONE_HOUSE_MEMO_TABLE." WHERE tph_id = '{$h_id}' ORDER BY create_datetime DESC LIMIT 1";
        }
        $s_result = $mysqli->query($s_sql);
        $sow = $s_result->fetch_assoc();

        $mb_name = ($h_condition == '1' || $h_condition == '2')?get_member_name($row['tph_mb_id']):(isset($sow['mb_id']) ? get_member_name($sow['mb_id']) : '');

      }

      $cdate = isset($sow['cdate']) && $sow['cdate'] ? date('Y-m-d', strtotime($sow['cdate'])).' '.date('H:i:s', strtotime($sow['cdate'])) : '';

      $address = '';
      if($type == 1||$type == 3) { // 호별/편지 일떄
        if(in_array($row['tt_type'],array('아파트','빌라','추가2'))){
          if($row['tt_type'] == '아파트'){
            $address2_text = $c_territory_type['type_2'][2]?$c_territory_type['type_2'][2]:'동';
            $address3_text = $c_territory_type['type_2'][3]?$c_territory_type['type_2'][3]:'호';
          }elseif($row['tt_type'] == '빌라'){
            $address2_text = $c_territory_type['type_3'][2]?$c_territory_type['type_3'][2]:'동';
            $address3_text = $c_territory_type['type_3'][3]?$c_territory_type['type_3'][3]:'호';
          }else{
            $address2_text = $c_territory_type['type_8'][2]?$c_territory_type['type_8'][2]:'';
            $address3_text = $c_territory_type['type_8'][3]?$c_territory_type['type_8'][3]:'';
          }
          $address .= $row['h_address1'];
          $address .= ' '.$row['h_address2'].$address2_text;
          $address .= ' '.$row['h_address3'].$address3_text;
        }else{
            $address .= $row['h_address1'];
            $address .= ' '.$row['h_address2'];
            $address .= ' '.$row['h_address3'];
            $address .= ' '.$row['h_address4'];
            $address .= ' '.$row['h_address5'];
        }
      }elseif($type == 2){ // 전화봉사일떄
        $d_tph_type = $row['tph_type'];
        $d_tph_name = $row['tph_name'];
        $address .= $row['tph_address'];
      }

      $data = array(
        'id' => ($type == 1||$type == 3)?$row['h_id']:$row['tph_id'],
        'pid' => ($type == 1||$type == 3)?$row['tt_id']:$row['tp_id'],
        'type' => $type,
        'tph_type' => isset($d_tph_type)?$d_tph_type:'',
        'tph_name' => isset($d_tph_name)?$d_tph_name:'',
        'address' => $address,
        'condition' => $h_condition,
        'condition_text' => get_house_condition_text($h_condition),
        'cdate' => $cdate,
        'mb_name' => $mb_name,
        'tt_type' => ($type == 1||$type == 3)?get_type_text($row['tt_type']):''
        );

    }
}

echo json_encode($data);
?>
