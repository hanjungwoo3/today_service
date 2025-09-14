<?php include_once('../config.php');?>

<?php
$c_territory_type_use = unserialize(TERRITORY_TYPE_USE);
$mb_id = mb_id();
$where = array();
$data = array();

if($search_type == 'telephone'){
  $where[] = "tp.mb_id = 0";
  if($s_num) $where[] = "tp.tp_num LIKE '%{$s_num}%'"; // 구역번호검색
  if($s_name) $where[] = "tp.tp_name LIKE '%{$s_name}%'"; // 구역명검색

  // 분배상태 검색
  if($s_assign && $s_assign != '선택안함'){
    switch ($s_assign) {
        case '전체': $where[] = "tp.tp_ms_all = 3"; break;
        case '호별': $where[] = "tp.tp_ms_all = 1"; break;
        case '전시대': $where[] = "tp.tp_ms_all = 2"; break;
        case '추가1': $where[] = "tp.tp_ms_all = 4"; break;
        case '추가2': $where[] = "tp.tp_ms_all = 5"; break;
        case '추가3': $where[] = "tp.tp_ms_all = 6"; break;
        case '추가4': $where[] = "tp.tp_ms_all = 7"; break;
        default:
        $copy_ms_id = get_copy_ms_id($s_assign);
        $where[] = $copy_ms_id?"( tp.ms_id = {$s_assign} || FIND_IN_SET(tp.ms_id,'{$copy_ms_id}') )":"tp.ms_id = {$s_assign}";
    }
  }else{
    $ms_ids = get_ms_id_by_guide($mb_id);
    $copy_ms_id = get_copy_ms_id($ms_ids);
    $str_where = "( tp.tp_ms_all <> 0 ";
    if($ms_ids) $str_where .= "|| FIND_IN_SET(tp.ms_id,'{$ms_ids}') ";
    if($copy_ms_id) $str_where .= "|| FIND_IN_SET(tp.ms_id,'{$copy_ms_id}') ";
    $str_where .= ")";
    $where[] = $str_where;
  }

  // 배정상태검색
  if($s_status && $s_status != '선택안함'){
    switch ($s_status) {
        case '미배정': $where[] = "tp.tp_status = '' AND tp.tp_assigned_date = '0000-00-00'"; break;
        case '첫배정': $where[] = "tp.tp_status = '' AND tp.tp_assigned_date <> '0000-00-00'"; break;
        case '재배정': $where[] = "tp.tp_status = 'reassign'"; break;
        case '부재자': $where[] = "tp.tp_status = 'absence'"; break;
        case '부재자재배정': $where[] = "tp.tp_status = 'absence_reassign'"; break;
    }
  }

  $page = $page?$page:1;
  $where = $where?'WHERE '.implode(' AND ',$where):'';
  $total = $mysqli->query("SELECT count(*) FROM ".TELEPHONE_TABLE." tp LEFT JOIN ".MEETING_SCHEDULE_TABLE." ms ON tp.ms_id = ms.ms_id {$where}")->fetch_row()[0];
  $limit = TERRITORY_ITEM_PER_PAGE?TERRITORY_ITEM_PER_PAGE:50;
  $pagelength = ceil($total / $limit);
  $offset = ($page - 1) * $limit;

  $sql = "SELECT * FROM ".TELEPHONE_TABLE." tp LEFT JOIN ".MEETING_SCHEDULE_TABLE." ms ON tp.ms_id = ms.ms_id {$where} ORDER BY tp.tp_num+0 ASC, tp.tp_num ASC LIMIT {$offset}, {$limit}";
  $result = $mysqli->query($sql);
  if($result->num_rows > 0){
    while ($row = $result->fetch_assoc()) {
      $tp_id = $row['tp_id'];
      $record_count = 0; // 봉사기록 카운트

      $sql2 = "SELECT count(*) FROM ".TELEPHONE_HOUSE_TABLE." WHERE tp_id = {$tp_id}";
      $result2 = $mysqli->query($sql2);
      $row2 = $result2->fetch_row();

      $telephone_progress = get_telephone_progress($tp_id);
      $percent = $telephone_progress['total']?floor((($telephone_progress['visit']+$telephone_progress['absence'])/$telephone_progress['total'])*100):0;

      if($row['tp_assigned'] || !empty_date($row['tp_start_date'])){
        $complete = !empty_date($row['tp_end_date'])?'complete':'incomplete';
        $record_count++;
      }else{
        $complete = 'unused';
      }

      $tp_status = (empty($row['tp_status']) && empty_date($row['tp_assigned_date']))?'unassigned':$row['tp_status'];
      $status = get_status_text($tp_status);

      $sql3 = "SELECT count(*) FROM ".TELEPHONE_RECORD_TABLE." WHERE tp_id = {$tp_id}";
      $result3 = $mysqli->query($sql3);
      $row3 = $result3->fetch_row();

      $record_count = $record_count + $row3[0];

      $data[] = array(
        'id' => $tp_id,
        'num' => $row['tp_num'],
        'name' => $row['tp_name'],
        'status' => $status,
        'progress' => $percent,
        'record_count' => $record_count,
        'total' => $total,
        'page' => $page,
        'pagelength' => $pagelength
      );
    }
  }
}else{
  $where[] = "t.mb_id = 0";
  if($s_num) $where[] = "t.tt_num LIKE '%{$s_num}%'"; // 구역번호검색
  if($s_name) $where[] = "t.tt_name LIKE '%{$s_name}%'"; // 구역명검색
  if($s_type && $s_type != '전체') $where[] = "t.tt_type = '{$s_type}'"; // 구역형태검색
  if($s_type != '편지') $where[] ="t.tt_type <> '편지'";

  //분배상태 검색
  if($s_assign && $s_assign != '선택안함'){
    switch ($s_assign) {
        case '전체': $where[] = "t.tt_ms_all = 3"; break;
        case '호별': $where[] = "t.tt_ms_all = 1"; break;
        case '전시대': $where[] = "t.tt_ms_all = 2"; break;
        case '추가1': $where[] = "t.tt_ms_all = 4"; break;
        case '추가2': $where[] = "t.tt_ms_all = 5"; break;
        case '추가3': $where[] = "t.tt_ms_all = 6"; break;
        case '추가4': $where[] = "t.tt_ms_all = 7"; break;
        default:
        $copy_ms_id = get_copy_ms_id($s_assign);
        $where[] = $copy_ms_id?"( t.ms_id = {$s_assign} || FIND_IN_SET(t.ms_id,'{$copy_ms_id}') )":"t.ms_id = {$s_assign}";
    }
  }else{
    $ms_ids = get_ms_id_by_guide($mb_id);
    $copy_ms_id = get_copy_ms_id($ms_ids);
    $str_where = "( t.tt_ms_all <> 0 ";
    if($ms_ids) $str_where .= "|| FIND_IN_SET(t.ms_id,'{$ms_ids}') ";
    if($copy_ms_id) $str_where .= "|| FIND_IN_SET(t.ms_id,'{$copy_ms_id}') ";
    $str_where .= ")";
    $where[] = $str_where;
  }

  // 배정상태검색
  if($s_status && $s_status != '선택안함'){
    switch ($s_status) {
        case '미배정': $where[] = "t.tt_status = '' AND t.tt_assigned_date = '0000-00-00'"; break;
        case '첫배정': $where[] = "t.tt_status = '' AND t.tt_assigned_date <> '0000-00-00'"; break;
        case '재배정': $where[] = "t.tt_status = 'reassign'"; break;
        case '부재자': $where[] = "t.tt_status = 'absence'"; break;
        case '부재자재배정': $where[] = "t.tt_status = 'absence_reassign'"; break;
    }
  }

  // 구역타입 사용여부에 따라...
  $in_tt_type = array();
  if(!isset($c_territory_type_use['type_1']) || $c_territory_type_use['type_1'] === 'use') $in_tt_type[] = '\'일반\'';
  if(!isset($c_territory_type_use['type_2']) || $c_territory_type_use['type_2'] === 'use') $in_tt_type[] = '\'아파트\'';
  if(!isset($c_territory_type_use['type_3']) || $c_territory_type_use['type_3'] === 'use') $in_tt_type[] = '\'빌라\'';
  if(!isset($c_territory_type_use['type_4']) || $c_territory_type_use['type_4'] === 'use') $in_tt_type[] = '\'격지\'';
  $in_tt_type[] = '\'편지\'';
  if(!isset($c_territory_type_use['type_7']) || $c_territory_type_use['type_7'] === 'use') $in_tt_type[] = '\'추가1\'';
  if(!isset($c_territory_type_use['type_8']) || $c_territory_type_use['type_8'] === 'use') $in_tt_type[] = '\'추가2\'';
  $where[] = "t.tt_type IN (".implode(',',$in_tt_type).")";

  $page = $page?$page:1;
  $where = $where?'WHERE '.implode(' AND ',$where):'';
  $total = $mysqli->query("SELECT count(*) FROM ".TERRITORY_TABLE." t LEFT JOIN ".MEETING_SCHEDULE_TABLE." ms ON t.ms_id = ms.ms_id {$where}")->fetch_row()[0];
  $limit = TERRITORY_ITEM_PER_PAGE?TERRITORY_ITEM_PER_PAGE:50;
  $pagelength = ceil($total / $limit);
  $offset = ($page - 1) * $limit;

  $sql = "SELECT * FROM ".TERRITORY_TABLE." t LEFT JOIN ".MEETING_SCHEDULE_TABLE." ms ON t.ms_id = ms.ms_id {$where} ORDER BY t.tt_num+0 ASC, t.tt_num ASC LIMIT {$offset}, {$limit}";
  $result = $mysqli->query($sql);
  if($result->num_rows > 0){
    while ($row = $result->fetch_assoc()) {
      $tt_id = $row['tt_id'];
      $record_count = 0; // 봉사기록 카운트

      $sql2 = "SELECT count(*) FROM ".HOUSE_TABLE." WHERE tt_id = {$tt_id}";
      $result2 = $mysqli->query($sql2);
      $row2 = $result2->fetch_row();

      $territory_progress = get_territory_progress($tt_id);
      $percent = $territory_progress['total']?floor((($territory_progress['visit']+$territory_progress['absence'])/$territory_progress['total'])*100):0;

      if($row['tt_assigned'] || !empty_date($row['tt_start_date'])){
        $complete = !empty_date($row['tt_end_date'])?'complete':'incomplete';
        $record_count++;
      }else{
        $complete = 'unused';
      }

      $tt_status = (empty($row['tt_status']) && empty_date($row['tt_assigned_date']))?'unassigned':$row['tt_status'];
      $status = get_status_text($tt_status);

      $sql3 = "SELECT count(*) FROM ".TERRITORY_RECORD_TABLE." WHERE tt_id = {$tt_id}";
      $result3 = $mysqli->query($sql3);
      $row3 = $result3->fetch_row();

      $record_count = $record_count + $row3[0];

      $data[] = array(
        'id' => $tt_id,
        'num' => $row['tt_num'],
        'name' => $row['tt_name'],
        'type' => get_type_text($row['tt_type']),
        'status' => $status,
        'progress' => $percent,
        'record_count' => $record_count,
        'total' => $total,
        'page' => $page,
        'pagelength' => $pagelength
      );
    }
  }
}

echo json_encode($data);
?>
