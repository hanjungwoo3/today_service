<?php
include_once('../config.php');

$territory = new Territory($mysqli);
$telephone = new Telephone($mysqli);

if($work){

  if($work == 'assign'){ // 구역 배정

    $assigned_date = get_meeting_date($m_id);
    $assigned_member = implode(',',$member);

    if(!empty($territories)){
      foreach($territories as $id){

        $sql = "SELECT tt_status, m_id, tt_assigned_date, tt_end_date FROM ".TERRITORY_TABLE." WHERE tt_id = {$id} LIMIT 1";
        $result = $mysqli->query($sql);
        if($result->num_rows > 0){
          while ($row = $result->fetch_assoc()) {

            if(!empty($row['m_id']) && $row['m_id'] == $m_id){ // 구역이 배정되어있고, 배정된 모임 아이디가 현재 모임 아이디와 같을 때 (즉 당일의 구역을 재배정 할때)

              $updateData = array(
                'tt_assigned' => $assigned_member,
                'tt_assigned_group' => $assigned_group,
              );
              $updateId = $territory->update($id,$updateData);

            }else{ // 오늘이 아닌 이전에 배정되었던 구역, 또는 배정이 된 적 없는 구역

              if($row['tt_assigned_date'] == '0000-00-00' && empty($row['tt_status'])){ // 미배정

                $updateData = array(
                  'tt_assigned' => $assigned_member,
                  'tt_assigned_date' => $assigned_date,
                  'tt_start_date' => '0000-00-00',
                  'tt_end_date' => '0000-00-00',
                  'tt_status' => '',
                  'm_id' => $m_id,
                  'tt_assigned_group' => $assigned_group,
                );
                $updateId = $territory->update($id,$updateData);

              }else{ // 1차배정, 재배정, 부재자

                // 이전 상태 저장 (territory_reset 호출 전에)
                $old_status = $row['tt_status'];
                // 완료 여부 저장 (territory_reset 호출 전에)
                $is_completed = !empty($row['tt_end_date']) && $row['tt_end_date'] != '0000-00-00';

                if($is_completed){ // 구역 완료
                  $new_status = (ABSENCE_USE == 'use')?'absence':'reassign';
                }else{ // 구역 미완료
                  if(ABSENCE_USE == 'use' && $row['tt_status'] == 'absence'){ // 부재자 였다면, 미완료 라도 부재자 그대로 봉사할 수 있도록...
                    $new_status = 'absence_reassign';
                  }else{
                    $new_status = 'reassign';
                  }
                }

                territory_reset($id,$new_status,$m_id); 

                $updateData = array(
                  'tt_assigned' => $assigned_member,
                  'tt_assigned_date' => $assigned_date,
                  'm_id' => $m_id,
                  'tt_assigned_group' => $assigned_group,
                );
                $updateId = $territory->update($id,$updateData);

              }

              // 이전 상태와 완료 여부를 전달하여 체크박스 비우기 여부 결정
              if(isset($old_status)){
                territory_house_update($id,'',$new_status,$old_status,isset($is_completed)?$is_completed:false);
              }else{
                territory_house_update($id,'',$new_status);
              }

            }

          }
        }
      }
    }

    if(!empty($telephones)){
      foreach($telephones as $id){

        $sql = "SELECT tp_status, m_id, tp_assigned_date, tp_end_date FROM ".TELEPHONE_TABLE." WHERE tp_id = {$id} LIMIT 1";
        $result = $mysqli->query($sql);
        if($result->num_rows > 0){
          while ($row = $result->fetch_assoc()) {

            if($row['m_id'] && $row['m_id'] == $m_id){

              $updateData = array(
                'tp_assigned' => $assigned_member,
                'tp_assigned_group' => $assigned_group,
              );
              $updateId = $telephone->update($id,$updateData);

            }else{

              if($row['tp_assigned_date'] == '0000-00-00' && empty($row['tp_status'])){ // 미배정

                $updateData = array(
                  'tp_assigned' => $assigned_member,
                  'tp_assigned_date' => $assigned_date,
                  'tp_start_date' => '0000-00-00',
                  'tp_end_date' => '0000-00-00',
                  'tp_status' => '',
                  'm_id' => $m_id,
                  'tp_assigned_group' => $assigned_group,
                );
                $updateId = $telephone->update($id,$updateData);

              }else{ // 1차배정, 재배정, 부재자

                // 이전 상태 저장 (telephone_reset 호출 전에)
                $old_status = $row['tp_status'];
                // 완료 여부 저장 (telephone_reset 호출 전에)
                $is_completed = !empty($row['tp_end_date']) && $row['tp_end_date'] != '0000-00-00';

                if($is_completed){ // 구역 완료
                  $new_status = (ABSENCE_USE == 'use')?'absence':'reassign';
                }else{ // 구역 미완료
                  if(ABSENCE_USE == 'use' && $row['tp_status'] == 'absence'){ // 부재자 였다면, 미완료 라도 부재자 그대로 봉사할 수 있도록...
                    $new_status = 'absence_reassign';
                  }else{
                    $new_status = 'reassign';
                  }
                }

                telephone_reset($id,$new_status,$m_id); 

                $updateData = array(
                  'tp_assigned' => $assigned_member,
                  'tp_assigned_date' => $assigned_date,
                  'm_id' => $m_id,
                  'tp_assigned_group' => $assigned_group,
                );
                $updateId = $telephone->update($id,$updateData);

              }

              // 이전 상태와 완료 여부를 전달하여 체크박스 비우기 여부 결정
              if(isset($old_status)){
                telephone_house_update($id,'',$new_status,$old_status,isset($is_completed)?$is_completed:false);
              }else{
                telephone_house_update($id,'',$new_status);
              }

            }

          }
        }
      }
    }

    if(!empty($displays)){
      foreach($displays as $id){

        $explode_id = explode('_',$id);

        $sql = "SELECT dp_name, dp_address FROM ".DISPLAY_PLACE_TABLE." WHERE dp_id = {$explode_id[0]} LIMIT 1";
        $result = $mysqli->query($sql);
        if($result->num_rows > 0){
          while ($row = $result->fetch_assoc()) {

            $d_sql = "SELECT d_id FROM ".DISPLAY_TABLE." WHERE dp_id = {$explode_id[0]} AND dp_num = {$explode_id[1]} AND m_id = {$m_id} LIMIT 1";
            $d_result = $mysqli->query($d_sql);

            if($d_result->num_rows > 0){
              while ($d_row = $d_result->fetch_assoc()) {
                $sql = "UPDATE ".DISPLAY_TABLE." SET d_assigned = '{$assigned_member}', d_assigned_group = '{$assigned_group}' WHERE d_id = {$d_row['d_id']}";
                $mysqli->query($sql);
              }
            }else{
              $sql = "INSERT INTO ".DISPLAY_TABLE." (d_assigned, d_assigned_date, dp_id, m_id, dp_address, dp_name, d_assigned_group, dp_num) VALUES ('{$assigned_member}', '{$assigned_date}', '{$explode_id[0]}', '{$m_id}', '{$row['dp_address']}', '{$row['dp_name']}', '{$assigned_group}', {$explode_id[1]})";
              $mysqli->query($sql);
            }

          }
        }
      }
    }

  }elseif($work == 'assign_cancel'){ // 구역 배정 취소
    if($table == 'territory'){
      // 배정취소시 구역기록도 복구되도록
      $sql = "SELECT ttr_id, ttr_assigned_num, ttr_assigned_date, ttr_assigned_group, ttr_start_date, ttr_end_date, m_id, ttr_status FROM ".TERRITORY_RECORD_TABLE." WHERE tt_id = {$pid} AND record_m_id = {$m_id} ORDER BY ttr_id DESC LIMIT 1";
      $result = $mysqli->query($sql);
      if($result->num_rows > 0){ // 오늘 배정한 모임중 지난 배정기록이 있으면
        $row = $result->fetch_assoc();

        $updateData = array(
          'tt_assigned' => $row['ttr_assigned_num'],
          'tt_assigned_date' => $row['ttr_assigned_date'],
          'tt_assigned_group' => $row['ttr_assigned_group'],
          'tt_start_date' => $row['ttr_start_date'],
          'tt_end_date' => $row['ttr_end_date'],
          'm_id' => $row['m_id'],
          'tt_status' => $row['ttr_status'],
        );
        $updateId = $territory->update($pid,$updateData);

        $sql = "DELETE FROM ".TERRITORY_RECORD_TABLE." WHERE ttr_id = {$row['ttr_id']}";
        $mysqli->query($sql);
      }else{

        $updateData = array(
          'tt_assigned' => '',
          'tt_assigned_date' => '0000-00-00',
          'tt_assigned_group' => '',
          'tt_start_date' => '0000-00-00',
          'tt_end_date' => '0000-00-00',
          'm_id' => 0,
        );
        $updateId = $territory->update($pid,$updateData);

      }
      territory_house_update($pid,'restore');
    }elseif($table == 'display') {
      $explode_id = explode('_',$pid);
      $sql = "DELETE FROM ".DISPLAY_TABLE." WHERE dp_id = {$explode_id[0]} AND dp_num = {$explode_id[1]} AND m_id = {$m_id}";
      $mysqli->query($sql);
    }elseif($table == 'telephone'){
      // 배정취소시 구역기록도 복구되도록
      $sql = "SELECT tpr_id, tpr_assigned_num, tpr_assigned_date, tpr_assigned_group, tpr_start_date, tpr_end_date, m_id, tpr_status FROM ".TELEPHONE_RECORD_TABLE." WHERE tp_id = {$pid} AND record_m_id = {$m_id} ORDER BY tpr_id DESC LIMIT 1";
      $result = $mysqli->query($sql);
      if($result->num_rows > 0){ // 오늘 배정한 모임중 지난 배정기록이 있으면
        $row = $result->fetch_assoc();

        $updateData = array(
          'tp_assigned' => $row['tpr_assigned_num'],
          'tp_assigned_date' => $row['tpr_assigned_date'],
          'tp_assigned_group' => $row['tpr_assigned_group'],
          'tp_start_date' => $row['tpr_start_date'],
          'tp_end_date' => $row['tpr_end_date'],
          'm_id' => $row['m_id'],
          'tp_status' => $row['tpr_status'],
        );
        $updateId = $telephone->update($pid,$updateData);

        $sql = "DELETE FROM ".TELEPHONE_RECORD_TABLE." WHERE tpr_id = {$row['tpr_id']}";
        $mysqli->query($sql);
      }else{

        $updateData = array(
          'tp_assigned' => '',
          'tp_assigned_date' => '0000-00-00',
          'tp_assigned_group' => '',
          'tp_start_date' => '0000-00-00',
          'tp_end_date' => '0000-00-00',
          'm_id' => 0,
        );
        $updateId = $telephone->update($pid,$updateData);

      }
      telephone_house_update($pid,'restore');
    }
  }elseif($work == 'select_minister'){ // 참석자 선택
    $sql = "SELECT mb_id FROM ".MEETING_TABLE." WHERE m_id = {$m_id}";
    $result = $mysqli->query($sql);
    if($result->num_rows > 0){
      $row = $result->fetch_assoc();
      $mb_id_array = !empty($row['mb_id'])?remove_moveout_mb_id(explode(',',$row['mb_id'])):array();

      if($action == 'add'){
        if(!in_array($current_mb_id, $mb_id_array)){
          $mb_id_array[] = $current_mb_id;
          $mb_id = implode(',',$mb_id_array);
          $sql = "UPDATE ".MEETING_TABLE." SET mb_id = '{$mb_id}' WHERE m_id = {$m_id}";
          if($mysqli->query($sql)) echo json_encode(array('attend' => '1'));
        }
      }elseif($action == 'delete'){
        if(in_array($current_mb_id, $mb_id_array)){
          $key = array_search($current_mb_id, $mb_id_array, true);
          unset($mb_id_array[$key]);
          $mb_id = implode(',',$mb_id_array);
          $sql = "UPDATE ".MEETING_TABLE." SET mb_id = '{$mb_id}' WHERE m_id = {$m_id}";
          if($mysqli->query($sql)) echo json_encode(array('attend' => '0'));
        }
      }

    }
  }elseif($work == 'update_meeting_contents'){ // 모임내용 기록
    $sql = "UPDATE ".MEETING_TABLE." SET m_contents = '{$m_contents}' WHERE m_id = {$m_id}";
    $mysqli->query($sql);
  }
}

?>
