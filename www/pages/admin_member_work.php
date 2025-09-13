<?php
include_once('../config.php');

if(isset($work) && $work == 'search'){

  if($mb_id) $where = ' AND mb_id != '.$mb_id;
  $sql = "SELECT * FROM ".MEMBER_TABLE." WHERE mb_name = '{$mb_name}'".$where;
  $result = $mysqli->query($sql);
  echo ($result->num_rows > 0)?1:0;

}elseif(isset($work) && $work == 'del'){

  $sql = "DELETE FROM ".MEMBER_TABLE." WHERE mb_id = {$mb_id}";
  $mysqli->query($sql);

}else{
  // 방식 변경 필요
  parse_str($data,$data);
  $mb_id = $data['mb_id'];
  $mb_name = $data['mb_name'];
  $mb_password = $data['mb_password'];
  $mb_hp = $data['mb_hp'];
  $mb_sex = $data['mb_sex'];
  $mb_position = $data['mb_position'];
  $mb_pioneer = $data['mb_pioneer'];
  $mb_auth = $data['mb_auth'];
  $mb_display = $data['mb_display'];
  $mb_address = $data['mb_address'];
  $g_id = !empty($data['g_id'])?$data['g_id']:0;
  $mb_movein_date = !empty($data['mb_movein_date'])?$data['mb_movein_date']:'0000-00-00';
  $mb_moveout_date = !empty($data['mb_moveout_date'])?$data['mb_moveout_date']:'0000-00-00';
  // 방식 변경 필요

  if($mb_hp) $mb_hp = encrypt($mb_hp);
  if($mb_address) $mb_address = encrypt($mb_address);

  if($mb_id){
    if(empty($mb_pioneer)) $mb_pioneer = '1';
    if($mb_password){
      $hash = password_hash($mb_password, PASSWORD_BCRYPT); // 단방향 암호화
      $sql = "UPDATE ".MEMBER_TABLE." SET mb_name = '{$mb_name}', mb_hash = '{$hash}', mb_hp = '{$mb_hp}', mb_sex = '{$mb_sex}', mb_position = '{$mb_position}', mb_pioneer = '{$mb_pioneer}', mb_auth = '{$mb_auth}', mb_display = '{$mb_display}', mb_address = '{$mb_address}', g_id = '{$g_id}', mb_movein_date = '{$mb_movein_date}', mb_moveout_date = '{$mb_moveout_date}', font_size = '' WHERE mb_id = {$mb_id}";
    }else{
      $sql = "UPDATE ".MEMBER_TABLE." SET mb_name = '{$mb_name}', mb_hp = '{$mb_hp}', mb_sex = '{$mb_sex}', mb_position = '{$mb_position}', mb_pioneer = '{$mb_pioneer}', mb_auth = '{$mb_auth}', mb_display = '{$mb_display}', mb_address = '{$mb_address}', g_id = '{$g_id}', mb_movein_date = '{$mb_movein_date}', mb_moveout_date = '{$mb_moveout_date}', font_size = '' WHERE mb_id = {$mb_id}";
    }
    $mysqli->query($sql);
  }else{
    $hash = password_hash($mb_password, PASSWORD_BCRYPT); // 단방향 암호화

    $sql = "INSERT INTO ".MEMBER_TABLE."(mb_name, mb_hash, mb_hp, mb_sex, mb_position, mb_pioneer, mb_auth, mb_display, mb_address, g_id, mb_movein_date, mb_moveout_date, font_size)
    VALUES('{$mb_name}','{$hash}','{$mb_hp}','{$mb_sex}', '{$mb_position}', '{$mb_pioneer}', '{$mb_auth}', {$mb_display}, '{$mb_address}', {$g_id}, '{$mb_movein_date}', '{$mb_moveout_date}', '')";
    $mysqli->query($sql);
  }

}
?>
