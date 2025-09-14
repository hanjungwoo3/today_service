<?php
include_once('../config.php');

if($work == 'del'){ // 회중일정 삭제

  $sql = "DELETE FROM ".MEETING_ADD_TABLE." WHERE ma_id = {$del_id}";
  $mysqli->query($sql);
  $mas_sql = "DELETE FROM ".MEETING_SCHEDULE_TABLE." WHERE ma_id = {$del_id}";
  $mysqli->query($mas_sql);

}else{ // 일정 추가/수정

  if(!isset($autoswitch)) $autoswitch = '0';
  if(!isset($timeswitch)) $timeswitch = '0';

  if($autoswitch == '1'){
    if($ma_id){
      $sql = "UPDATE ".MEETING_ADD_TABLE." SET ma_title = '{$title}', ma_content = '{$content}', ma_week = '{$week}', ma_weekday = '{$weekday}', ma_auto = '{$autoswitch}', ma_color = '{$color}' WHERE ma_id = {$ma_id}";
    }else{
      $sql = "INSERT INTO ".MEETING_ADD_TABLE."(ma_title, ma_date, ma_date2, ma_switch, ma_content, ma_week, ma_weekday, ma_auto, ma_color) VALUES('{$title}','0000-00-00 00:00:00','0000-00-00 00:00:00',0,'{$content}','{$week}','{$weekday}','{$autoswitch}','{$color}')";
    }
    $mysqli->query($sql);
  }else{
    if($timeswitch == '1'){
      $ma_date = $date;
      $ma_date2 = $date2;
    }else{
      $ma_date = $datetime;
      $ma_date2 = $datetime2;
    }
    $month1 =  date('Y-m-d', strtotime($ma_date));
    $month2 =  date('Y-m-d', strtotime($ma_date2));
    if($ma_id){
      $sql = "UPDATE ".MEETING_ADD_TABLE." SET ma_title = '{$title}', ma_content = '{$content}', ma_date = '{$ma_date}', ma_date2 = '{$ma_date2}', ma_switch = '{$timeswitch}', ma_auto = '{$autoswitch}', ma_color = '{$color}' WHERE ma_id = {$ma_id}";
    }else{
      $sql = "INSERT INTO ".MEETING_ADD_TABLE."(ma_title,  ma_content, ma_date, ma_date2, ma_switch, ma_auto, ma_color, ma_week, ma_weekday) VALUES('{$title}','{$content}','{$ma_date}','{$ma_date2}','{$timeswitch}','{$autoswitch}','{$color}', 0, 0)";
    }
    $mysqli->query($sql);
  }

}
?>
