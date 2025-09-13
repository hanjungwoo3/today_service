<?php include_once("../config.php");?>
<?php check_accessible('admin');?>

<?php
$c_territory_type = unserialize(TERRITORY_TYPE);
$where = array();
$data = array();

if($type == 1 || $type == 3){ // 호별봉사일떄

  if($type == 1){ // 구역형태검색
    $where[] ="t.tt_type <> '편지'";
    if($s_type && $s_type != '전체') $where[] = "t.tt_type = '{$s_type}'";
  }elseif($type == 3){
    $where[] ="t.tt_type = '편지'";
  }

  if(!empty($h_assign) && $h_assign != '전체') $where[] = "h.h_condition = '{$h_assign}'";   //특이사항검색
  if(!empty($h_address1)) $where[] = "h.h_address1 LIKE '%".$h_address1."%'";   //주소검색
  if(!empty($h_address2)) $where[] = "h.h_address2 LIKE '%".$h_address2."%'";   //주소검색
  if(!empty($h_address3)) $where[] = "h.h_address3 LIKE '%".$h_address3."%'";  //주소검색 
  if(!empty($h_address4)) $where[] = "h.h_address4 LIKE '%".$h_address4."%'";  //주소검색
  if(!empty($h_address5)) $where[] = "h.h_address5 LIKE '%".$h_address5."%'";  //주소검색
  if(!empty($p_id)) $where[] = "t.tt_id = ".$p_id;  // 구역ID검색
  if(!empty($h_id)) $where[] = "h.h_id = ".$h_id;  // 세대ID검색

  $page = $page?$page:1;
  $where = $where?'WHERE '.implode(' AND ',$where):'';
  $total = $mysqli->query("SELECT count(*) FROM ".HOUSE_TABLE." h INNER JOIN ".TERRITORY_TABLE." t ON h.tt_id = t.tt_id {$where}")->fetch_row()[0];
  $limit = defined('TERRITORY_ITEM_PER_PAGE') && TERRITORY_ITEM_PER_PAGE ? TERRITORY_ITEM_PER_PAGE : 50;
  $pagelength = ceil($total / $limit);
  $offset = ($page - 1) * $limit;

  $sql = "SELECT *,t.mb_id as t_mb_id, h.mb_id as h_mb_id FROM ".HOUSE_TABLE." h INNER JOIN ".TERRITORY_TABLE." t ON h.tt_id = t.tt_id ".$where."
          ORDER BY h_address1, h_address2*1, h_address3*1 , h_address4*1, h_address5*1 ASC LIMIT {$offset}, {$limit}";

}elseif($type == 2){ // 전화봉사일때

  if(!empty($h_assign) && $h_assign != '전체') $where[] = "tph.tph_condition = '{$h_assign}'";  //특이사항검색
  if(!empty($tph_number)) $where[] = "tph.tph_number LIKE '%".$tph_number."%'";  //번호검색
  if(!empty($tph_type)) $where[] = "tph.tph_type LIKE '%".$tph_type."%'";  //업종검색
  if(!empty($tph_name)) $where[] = "tph.tph_name LIKE '%".$tph_name."%'";  //상호검색
  if(!empty($tph_address)) $where[] = "tph.tph_address LIKE '%".$tph_address."%'";  //주소검색
  if(!empty($p_id)) $where[] = "tp.tp_id = ".$p_id;    // 구역ID검색
  if(!empty($h_id)) $where[] = "tph.tph_id = ".$h_id;  // 세대ID검색

  $page = $page?$page:1;
  $where = $where?'WHERE '.implode(' AND ',$where):'';
  $total = $mysqli->query("SELECT count(*) FROM ".TELEPHONE_HOUSE_TABLE." tph INNER JOIN ".TELEPHONE_TABLE." tp ON tph.tp_id = tp.tp_id {$where}")->fetch_row()[0];
  $limit = defined('TERRITORY_ITEM_PER_PAGE') && TERRITORY_ITEM_PER_PAGE ? TERRITORY_ITEM_PER_PAGE : 50;
  $pagelength = ceil($total / $limit);
  $offset = ($page - 1) * $limit;

  $sql = "SELECT *,tp.mb_id as tp_mb_id, tph.mb_id as tph_mb_id  FROM ".TELEPHONE_HOUSE_TABLE." tph INNER JOIN ".TELEPHONE_TABLE." tp ON tph.tp_id = tp.tp_id ".$where."
          ORDER BY tph_name ASC LIMIT {$offset}, {$limit}";

}
$result = $mysqli->query($sql);

if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $h_condition = '';
      $cdate = '';
      $mb_name = '';

      if($type == 1||$type == 3){
        if(!empty($row['h_condition'])){
          $h_condition = $row['h_condition'];
          $h_id = $row['h_id'];

          if($h_condition == '1' || $h_condition == '2'){
            $s_sql = "SELECT create_datetime as cdate FROM ".RETURN_VISIT_TABLE." WHERE h_id = '{$h_id }' ORDER BY create_datetime DESC LIMIT 1";
          }else{
            $s_sql = "SELECT create_datetime as cdate, mb_id FROM ".HOUSE_MEMO_TABLE." WHERE h_id = '{$h_id }' ORDER BY create_datetime DESC LIMIT 1";
          }
          $s_result = $mysqli->query($s_sql);
          $sow = $s_result->fetch_assoc();

          if($h_condition == '1' || $h_condition == '2'){
            if(!empty($row['h_mb_id'])){
              $mb_name = get_member_name($row['h_mb_id']);
            }
          }else{
            if(!empty($sow['mb_id'])){
              $mb_name = get_member_name($sow['mb_id']);
            }
          }

        }
      }elseif($type == 2){
        if(!empty($row['tph_condition'])){
          $h_condition = $row['tph_condition'];
          $h_id = $row['tph_id'];

          if($h_condition == '1' || $h_condition == '2'){
            $s_sql = "SELECT create_datetime as cdate FROM ".TELEPHONE_RETURN_VISIT_TABLE." WHERE tph_id = '{$h_id}' ORDER BY create_datetime DESC LIMIT 1";
          }else{
            $s_sql = "SELECT create_datetime as cdate, mb_id FROM ".TELEPHONE_HOUSE_MEMO_TABLE." WHERE tph_id = '{$h_id}' ORDER BY create_datetime DESC LIMIT 1";
          }

          $s_result = $mysqli->query($s_sql);
          $sow = $s_result->fetch_assoc();

          if($h_condition == '1' || $h_condition == '2'){
            if(!empty($row['tph_mb_id'])){
              $mb_name = get_member_name($row['tph_mb_id']);
            }
          }else{
            if(!empty($sow['mb_id'])){
              $mb_name = get_member_name($sow['mb_id']);
            }
          }

        }
      }

      $cdate = isset($sow['cdate']) && $sow['cdate'] ? date('Y-m-d', strtotime($sow['cdate'])).' '.date('H:i:s', strtotime($sow['cdate'])) : '';

      $address = '';
      if($type == 1||$type == 3) { // 호별일떄
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
        $address .= $row['tph_address']?$row['tph_address']:'';
      }

      $data[] = array(
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
        'tt_type' => ($type == 1||$type == 3)?get_type_text($row['tt_type']):'',
        'total' => $total,
        'page' => $page,
        'pagelength' => $pagelength
        );

    }
}

echo json_encode($data);
?>
