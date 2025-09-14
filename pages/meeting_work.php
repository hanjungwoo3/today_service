<?php
include_once('../config.php');
$m_id = get_meeting_id($s_date, $ms_id);
$meeting_data = get_meeting_data($m_id);

if($work == 'del'){ // 전시대봉사 불참
  $volunteered = array_unique(array_filter(explode(",", $meeting_data['mb_id'])));
  $key = array_search(mb_id(), $volunteered, true);
  if($key !== false){
    array_splice($volunteered, $key, 1);
    $mb_id = implode(",", remove_moveout_mb_id($volunteered));

    $sql = "UPDATE ".MEETING_TABLE." SET mb_id = '{$mb_id}' WHERE m_date = '{$s_date}' AND ms_id = '{$ms_id}'";
    $mysqli->query($sql);
  } 
}elseif($work == 'support' || $work == 'today_support'){ // 전시대봉사 지원
  $ms = get_meeting_schedule_data($ms_id);
  $attend_limit = get_meeting_schedule_attend_limit($ms_id);

  // 같은 시간의 봉사모임에 중복 지원을 막음
  if(DUPLICATE_ATTEND_LIMIT == 'use'){
    $sql = "SELECT * FROM ".MEETING_TABLE." WHERE m_date = '".$s_date."' AND ms_time = '".$meeting_data['ms_time']."' AND FIND_IN_SET( ".mb_id()." , mb_id)";
    $result=$mysqli->query($sql);
    if ($result->num_rows > 0) {
      echo 'duplicated';
      exit;
    }
  }

  if(!empty($meeting_data['mb_id'])){
    // 지원되있는 전도인이 더이상 데이터베이스에 남아있지 않을때
    $volunteered = remove_moveout_mb_id(array_unique(array_filter(explode(",", $meeting_data['mb_id']))));
    
    // 지원 인원수 제한
    if(!empty($attend_limit) && count($volunteered) >= $attend_limit){
      echo 'disabled';
      exit;
    }else{
      array_push($volunteered, mb_id());
      $mb_id = implode(",",$volunteered);
    }
  }else{
    $mb_id = mb_id();
  }

  $sql = "UPDATE ".MEETING_TABLE." SET mb_id = '{$mb_id}' WHERE m_id = {$m_id}";
  $mysqli->query($sql);
}
?>
