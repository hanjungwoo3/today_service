<?php include_once('../config.php');?>

<?php
$mb_id = mb_id();

if($work){
  if($work == 'add'){

    if($table == 'territory'){

      if($mb_id && $pid && $condition){
        $sql = "UPDATE ".HOUSE_TABLE." SET h_condition = {$condition} WHERE h_id = {$pid}";
        $mysqli->query($sql);

        // 특이사항 추가 시 만남 자동 체크
        $sql = "UPDATE ".HOUSE_TABLE." SET h_visit = 'Y' WHERE h_id = {$pid}";
        $mysqli->query($sql);

        if(in_array($condition,array(1,2))){

          if($datetime){ // 재방 또는 연구일떄
            $rv_datetime = date("Y-m-d H:i:s", strtotime($datetime));

            $sql = "UPDATE ".HOUSE_TABLE." SET mb_id = {$mb_id} WHERE h_id = {$pid}";
            $mysqli->query($sql);

            $sql = "INSERT INTO ".RETURN_VISIT_TABLE."(h_id, mb_id, rv_content, rv_datetime, create_datetime, update_datetime, rv_transfer) VALUES({$pid},{$mb_id},'{$content}','{$rv_datetime}', NOW(), NOW(), 0)";
            $mysqli->query($sql);
          }

        }else{

          $sql = "INSERT INTO ".HOUSE_MEMO_TABLE."(h_id, mb_id, hm_content, hm_condition, create_datetime, update_datetime) VALUES({$pid},{$mb_id},'{$content}',{$condition},NOW(),NOW())";
          $mysqli->query($sql);

        }
      }

    }elseif($table == 'telephone'){

      if($mb_id && $pid && $condition){
        $sql = "UPDATE ".TELEPHONE_HOUSE_TABLE." SET tph_condition = {$condition} WHERE tph_id = {$pid}";
        $mysqli->query($sql);

        // 특이사항 추가 시 만남 자동 체크
        $sql = "UPDATE ".TELEPHONE_HOUSE_TABLE." SET tph_visit = 'Y' WHERE tph_id = {$pid}";
        $mysqli->query($sql);

        if(in_array($condition,array(1,2))){ // 재방 또는 연구일떄

          if($datetime){
            $tprv_datetime = date("Y-m-d H:i:s", strtotime($datetime));

            $sql = "UPDATE ".TELEPHONE_HOUSE_TABLE." SET mb_id = {$mb_id} WHERE tph_id = {$pid}";
            $mysqli->query($sql);

            $sql = "INSERT INTO ".TELEPHONE_RETURN_VISIT_TABLE."(tph_id, mb_id, tprv_content, tprv_datetime, create_datetime, update_datetime, tprv_transfer) VALUES({$pid},{$mb_id},'{$content}','{$tprv_datetime}', NOW(), NOW(), 0)";
            $mysqli->query($sql);
          }

        }else{

          $sql = "INSERT INTO ".TELEPHONE_HOUSE_MEMO_TABLE."(tph_id, mb_id, tphm_content, tphm_condition, create_datetime, update_datetime) VALUES({$pid},{$mb_id},'{$content}',{$condition},NOW(),NOW())";
          $mysqli->query($sql);

        }

      }

    }

  }elseif($work == 'delete'){

    if($table == 'territory'){

      $sql = "UPDATE ".HOUSE_TABLE." SET h_condition = '' , mb_id = 0 WHERE h_id = {$pid}";
      $mysqli->query($sql);

    }elseif($table == 'telephone'){

      $sql = "UPDATE ".TELEPHONE_HOUSE_TABLE." SET tph_condition = '' , mb_id = 0 WHERE tph_id = {$pid}";
      $mysqli->query($sql);

    }

  }elseif($work == 'edit'){

    if($pid && $condition){

      if($table == 'territory'){

        $sql = "UPDATE ".HOUSE_TABLE." SET h_condition = {$condition} WHERE h_id = {$pid}";
        $mysqli->query($sql);

        $sql = "UPDATE ".HOUSE_MEMO_TABLE." SET hm_content = '{$content}' , hm_condition = {$condition}, update_datetime = NOW() WHERE hm_id = {$hm_id}";
        $mysqli->query($sql);

      }elseif($table == 'telephone'){

        $sql = "UPDATE ".TELEPHONE_HOUSE_TABLE." SET tph_condition = {$condition} WHERE tph_id = {$pid}";
        $mysqli->query($sql);

        $sql = "UPDATE ".TELEPHONE_HOUSE_MEMO_TABLE." SET tphm_content = '{$content}' , tphm_condition = {$condition}, update_datetime = NOW() WHERE tphm_id = {$hm_id}";
        $mysqli->query($sql);

      }

    }

  }
}
?>
