<?php include_once('../config.php');?>

<?php
if($work == 'report'){

  $mr_sql = "SELECT * FROM ".MINISTER_REPORT_TABLE." WHERE mb_id = '{$mb_id}' AND mr_date = '{$date}'";
  $mr_result = $mysqli->query($mr_sql);
  $mr = $mr_result->fetch_assoc();

  if(!isset($pub)) $pub = 0;
  if(!isset($video)) $video = 0;
  if(!isset($return_visit)) $return_visit = 0;

  if($hour == '') $hour = 0;
  if($min == '') $min = 0;
  if($pub == '') $pub = 0;
  if($video == '') $video = 0;
  if($return_visit == '') $return_visit = 0;
  if($study == '') $study = 0;

  if($mr){ // 보고 수정
    $sql = "UPDATE ".MINISTER_REPORT_TABLE." SET mr_hour = '{$hour}', mr_min = '{$min}', mr_pub = '{$pub}', mr_video = '{$video}', mr_return = '{$return_visit}', mr_study = '{$study}' WHERE mb_id = '{$mb_id}' AND mr_date = '{$date}'";
  }else{// 보고 생성
    $sql = "INSERT INTO ".MINISTER_REPORT_TABLE."(mr_hour, mr_min, mr_pub, mr_video, mr_return, mr_study, mb_id, mr_date) VALUES('{$hour}','{$min}', '{$pub}', '{$video}', '{$return_visit}', '{$study}', '{$mb_id}', '{$date}')";
  }
  $mysqli->query($sql);

}elseif($work == 'event'){

  // mb_id 검증: 0이거나 없으면 현재 로그인한 사용자 ID 사용
  if(empty($mb_id) || $mb_id == 0){
    $mb_id = mb_id();
  }
  
  // mb_id가 여전히 0이거나 없으면 오류 처리
  if(empty($mb_id) || $mb_id == 0){
    die('오류: 사용자 정보를 확인할 수 없습니다.');
  }

  if(!isset($timeswitch)) $timeswitch = 0;

  if($timeswitch == '1'){
    $me_date = $date;
    $me_date2 = $date2;
  }else{
    $me_date = $datetime;
    $me_date2 = $datetime2;
  }

  if($me_id){ // 이벤트 수정
    $sql = "UPDATE ".MINISTER_EVENT_TABLE." SET me_title = '{$title}', me_date = '{$me_date}', me_date2 = '{$me_date2}', me_switch = '{$timeswitch}', me_content = '{$content}', me_color = '{$color}' WHERE me_id = '{$me_id}'";
  }else{// 이벤트 생성
    $sql = "INSERT INTO ".MINISTER_EVENT_TABLE."(me_title, me_date, me_date2, me_switch, me_content, mb_id, me_color) VALUES('{$title}','{$me_date}','{$me_date2}','{$timeswitch}','{$content}','{$mb_id}','{$color}')";
  }
  $mysqli->query($sql);

}elseif($work == 'del'){ // 이벤트 삭제

  $sql = "DELETE FROM ".MINISTER_EVENT_TABLE." WHERE me_id = {$me_id}";
  $mysqli->query($sql);

}
?>
