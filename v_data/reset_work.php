<?php include_once('../config.php');?>

<?php
if($type){

    if($type == '전화'){

        // 개인구역을 제외한 구역 추출
        $tp_sql = "SELECT tp_id FROM ".TELEPHONE_TABLE." WHERE mb_id = '0'";
        $tp_result = $mysqli->query($tp_sql);
        if($tp_result->num_rows > 0){
          while($tp_row = $tp_result->fetch_assoc()){
            $tp_id = $tp_row['tp_id'];
            telephone_reset($tp_id);
            telephone_house_reset($tp_id);
          }
        }
        insert_work_log('telephone_reset_all');

    }else{

      	// 개인구역을 제외한 구역 추출
        $tt_sql = "SELECT tt_id FROM ".TERRITORY_TABLE." WHERE mb_id = '0' AND tt_type = '{$type}'";
        $tt_result = $mysqli->query($tt_sql);
        if($tt_result->num_rows > 0){
          while($tt_row = $tt_result->fetch_assoc()){
            $tt_id = $tt_row['tt_id'];
            territory_reset($tt_id);
            territory_house_reset($tt_id);
          }
        }
        insert_work_log('territory_reset_all');

    }

}
