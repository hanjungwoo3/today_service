<?php include_once('../config.php');?>

<?php
$data = array();
$mb_id = mb_id();
$mb_g_id = get_member_group($mb_id);
$today = date('Y-m-d');
if(empty($s_date)) $s_date = $today;
$week_val = date('N',strtotime($s_date));
$color = array('text-dark', 'text-primary', 'text-info', 'text-success', 'text-warning', 'text-danger', 'text-secondary');

//지정된 날짜 내 회중일정
if(!is_moveout($mb_id)){ // 전출전도인이 아닐때만 회중일정 볼 수 있게
  $ma_id = get_addschedule_id_sub($s_date);
  $ma_sql = "SELECT * FROM ".MEETING_ADD_TABLE." WHERE ma_id IN({$ma_id}) ORDER BY ma_auto DESC, ma_date DESC, ma_date2, ma_title";
  $ma_result = $mysqli->query($ma_sql);
  if($ma_result->num_rows > 0){
    while($mar = $ma_result->fetch_assoc()){
      $time1 = '';
      $time2 = '';
      $str_date = '';

      if($mar['ma_auto'] == 1){
        $str_date = '하루 종일';
        $start = $s_date.' 00:00';
        $finish = $s_date.' 23:59';
      }else{
        if($mar['ma_switch'] == 0){
         $time1 =  date('H:i', strtotime($mar['ma_date']));
         $time2 =  date('H:i', strtotime($mar['ma_date2']));
        }
        $month1 = $mar['ma_date']?date('n월 j일', strtotime($mar['ma_date'])):'';
        $month2 = $mar['ma_date2']?date('n월 j일', strtotime($mar['ma_date2'])):'';
        if($month1 == $month2){
          if($time1 && $time2){
            $str_date = ($time1 == $time2)?$time1:$time1.' ~ '.$time2;
          }elseif(empty($time1 && $time2)){
            $str_date = '하루 종일';
          }
        }else{
          $str_date = ($time1 || $time2)?$month1.' ('.$time1.') ~ '.$month2.' ('.$time2.')':$month1.' ~ '.$month2;
        }
        $str_time1 = $time1?$time1:'00:00';
        $str_time2 = $time2?$time2:'23:59';
        $str_ma_date = $mar['ma_date']?date('Y-m-d', strtotime($mar['ma_date'])):'';
        $str_ma_date2 = $mar['ma_date2']?date('Y-m-d', strtotime($mar['ma_date2'])):'';

        $start = $str_ma_date.' '.$str_time1;
        $finish = $str_ma_date2.' '.$str_time2;
      }

      $data[] = array(
        'start' => $start,
        'finish' => $finish,
        'title' => $mar['ma_title'],
        'color' => '<i class="bi bi-record-circle-fill" style="color:'.$mar['ma_color'].'"></i>',
        'date' => $str_date,
        'content' => $mar['ma_content'],
        'me_id' => ''
      );
    }
  }
}

// 지정된 날짜 내 개인일정
if(MINISTER_SCHEDULE_EVENT_USE == 'use'){
  $me_sql = "SELECT * FROM ".MINISTER_EVENT_TABLE." WHERE mb_id = '{$mb_id}' AND DATE(me_date) <= '{$s_date}' AND DATE(me_date2) >= '{$s_date}' ORDER BY me_date, me_date2";
  $me_result = $mysqli->query($me_sql);
  if($me_result->num_rows > 0){
    while($me = $me_result->fetch_assoc()){
      $time1 = '';
      $time2 = '';
      $ampm1 = '';
      $ampm2 = '';
      $str_date = '';

      if($me['me_switch'] == 0){
       $time1 =  date('h:i', strtotime($me['me_date']));
       $ampm1 = date('A', strtotime($me['me_date']));
       $time2 =  date('H:i', strtotime($me['me_date2']));
       $ampm2 = date('A', strtotime($me['me_date2']));
       if ($ampm1 == 'AM') { $ampm1 = '오전';} else {$ampm1 = '오후';}
       if ($ampm2 == 'AM') { $ampm2 = '오전';} else {$ampm2 = '오후';}
      }

      $month1 = $me['me_date']?date('n월 j일', strtotime($me['me_date'])):'';
      $month2 = $me['me_date2']?date('n월 j일', strtotime($me['me_date2'])):'';
      if($month1 == $month2){
        if($time1 && $time2){
          $str_date = ($me['me_date'] == $me['me_date2'])?$ampm1.' '.$time1:$ampm1.' '.$time1.' ~ '.$ampm2.' '.$time2;
        }elseif(empty($time1 && $time2)) $str_date = '하루 종일';
      }else{
        if($me['me_switch'] == 0){
          $str_date = ($me['me_date'] || $me['me_date2'])?$month1.' ('.$ampm1.' '.$time1.') ~ '.$month2.' ('.$ampm2.' '.$time2.')':$month1.' ~ '.$month2;
        }else{
          $str_date = $month1.' ~ '.$month2;
        }
      }
      $str_time1 = $time1?date('H:i', strtotime($me['me_date'])):'00:00';
      $str_time2 = $time2?date('H:i', strtotime($me['me_date2'])):'23:59';
      $str_me_date = $me['me_date']?date('Y-m-d', strtotime($me['me_date'])):'';
      $str_me_date2 = $me['me_date2']?date('Y-m-d', strtotime($me['me_date2'])):'';

      $start = $str_me_date.' '.$str_time1;
      $finish = $str_me_date2.' '.$str_time2;

      $data[] = array(
        'start' => $start,
        'finish' => $finish,
        'title' => $me['me_title'],
        'color' => '<i class="bi bi-record-circle '.$color[$me['me_color']].'"></i>',
        'date' => $str_date,
        'content' => $me['me_content'],
        'me_id' => $me['me_id']
      );
    }
  }
}

//정렬
foreach ($data as $key => $row) {
    $start_arr[$key] = $row['start'];
    $finish_arr[$key] = $row['finish'];
}
if(isset($start_arr) && isset($finish_arr)){
  if (is_array($start_arr) && is_array($finish_arr)) {
    array_multisort($start_arr, SORT_ASC, $finish_arr, SORT_DESC, $data);
  }
}
?>


<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix">
  <span class="align-middle mt-2 d-inline-block">일정</span>
  <?php if(MINISTER_SCHEDULE_EVENT_USE == 'use'):?>
      <button type="button" class="btn btn-sm btn-outline-primary align-middle float-right" onclick="minister_schedule_work('add','','<?=$s_date?>');">
        <i class="bi bi-plus-circle-dotted"></i> 추가
      </button>
  <?php endif;?>
</h5>
<?php if($data):?>
<div class="list-group list-group-flush mt-2">
<?php foreach ($data as $value) :?>
  <div class="list-group-item d-flex flex-nowrap justify-content-between px-1 py-2 border-bottom border-light">
    <span class="align-self-center">
      <?=$value['color'];?>
    </span>
    <div class="flex-grow-1 px-2">
      <div><?=$value['title']?></div>
      <div class="mt-n1"><small><?=$value['content']?></small></div>
      <div class="text-secondary mt-n1">
        <small class="align-middle"><?=$value['date'];?></small>
      </div>
    </div>
    <?php if($value['me_id']):?>
    <div class="align-self-center flex-shrink-0">
      <div class="dropdown">
        <button class="btn btn-outline-secondary" type="button" id="ex<?=$value['me_id']?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i class="bi bi-three-dots-vertical "></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="ex<?=$value['me_id']?>" >
          <button class="dropdown-item" type="button" onclick="minister_schedule_work('edit','<?=$value['me_id']?>','<?=$s_date?>');">수정</button>
          <button class="dropdown-item" type="button" onclick="minister_schedule_work('del','<?=$value['me_id']?>','<?=$s_date?>');">삭제</button>
        </div>
      </div>
    </div>
    <?php endif;?>
  </div>
  <?php endforeach;?>
</div>
<?php endif;?>



