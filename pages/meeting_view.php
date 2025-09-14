<?php include_once('../header.php');?>
<?php
if(empty($m_id)){ // m_id 가 넘어오지 않을때
  echo '<script> location.href="'.BASE_PATH.'/"; </script>';
  exit;
}
?>

<?php
$count = 0;
$volunteered = '';

$sql = "SELECT ms_id, ms_time, mp_name, mp_address, ms_type, ms_start_time, ms_finish_time, g_id, ms_limit
        FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".MEETING_PLACE_TABLE." mp ON ms.mp_id = mp.mp_id
        WHERE ms_id = '{$ms_id}'";
$result = $mysqli->query($sql);
$row = $result->fetch_assoc();

$attend_limit = get_meeting_schedule_attend_limit($row['ms_id']);

$datetime = $row['ms_time']? date('Y-m-d H:i', mktime(date('H', strtotime($row['ms_time'])), date('i', strtotime($row['ms_time']))+ATTEND_DISPLAY_AFTER, 0, date('n', strtotime($s_date)), date('j', strtotime($s_date)), date('Y', strtotime($s_date)))):'';
$mp_name = $row['g_id']?'['.get_group_name($row['g_id']).'집단] ':'';
$mp_name .= $row['mp_name'];

$msw = get_meeting_data(get_meeting_id($s_date, $ms_id));

// 지원되있는 전도인이 더이상 데이터베이스에 남아있지 않을때
$member_of_meeting = remove_moveout_mb_id(array_unique(array_filter(explode(',',$msw['mb_id']))));
if($member_of_meeting) $count = count($member_of_meeting);

$member_string = implode(",",$member_of_meeting);
if($member_string){
  $mb_sql = "SELECT mb_id, mb_name FROM ".MEMBER_TABLE." WHERE mb_id IN ({$member_string}) ORDER BY mb_name";
  $mb_result = $mysqli->query($mb_sql);
  while ($mb_row = $mb_result->fetch_assoc()) $volunteered .= '<span class="badge badge'.(($mb_row['mb_id'] == mb_id())?'-success':'-light').' p-2 m-1 align-middle"><span class="h6">'.$mb_row['mb_name'].'</span></span>';
}
?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">전시대</h1>
</header>

<?php echo footer_menu('전시대'); ?>

<div id="container" class="container-fluid">
  <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">모임 장소</span></h5>
  <div class="mb-2">
    <div class="p-2"><?=$mp_name?></div>
    <div class="d-flex justify-content-between p-2">
      <div><?=$row['mp_address']?></div>
      <div class="flex-shrink-0">
        <button class="btn btn-sm btn-outline-secondary" onclick="kakao_navi('<?=DEFAULT_ADDRESS.' '.$row['mp_address']?>','<?=$row['mp_name']?>');">
          <i class="bi bi-cursor"></i> 길찾기
        </button>
      </div>
    </div>
  </div>

  <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">모임 일정</span></h5>
  <div class="mb-3">
    <div class="d-flex justify-content-between p-2">
      <div class="flex-shrink-0">날짜</div>
      <div><?=$s_date?></div>
    </div>
    <div class="d-flex justify-content-between p-2">
      <div class="flex-shrink-0">모임 시간</div>
      <div><?php echo get_datetime_text($row['ms_time']);?></div>
    </div>
    <?php if(!(($row['ms_start_time'] == $row['ms_finish_time']) && ($row['ms_start_time'] == '00:00:00'))): ?>
    <div class="d-flex justify-content-between p-2">
      <div class="flex-shrink-0">봉사 시간</div>
      <div><?=date('H:i', strtotime($row['ms_start_time'])).' ~ '.date('H:i', strtotime($row['ms_finish_time']))?></div>
    </div>
    <?php endif; ?>
  </div>

  <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">인도자/보조자</span></h5>
  <div class="mb-3 p-2">
    <?php
    if(!empty($msw['m_guide'])){
      foreach (get_guide_data($msw['m_guide']) as $value) echo '<a class="btn btn-outline-primary m-1" href="tel:'.$value['hp'].'"><i class="bi bi-telephone"></i> '.$value['name'].'</a>';
    }else{
      if($msw['ms_guide']) foreach (get_guide_data($msw['ms_guide']) as $value) echo '<a class="btn btn-outline-primary m-1" href="tel:'.$value['hp'].'"><i class="bi bi-telephone"></i> '.$value['name'].'</a>';
      if($msw['ms_guide2']) foreach (get_guide_data($msw['ms_guide2']) as $value) echo '<a class="btn btn-outline-secondary m-1" href="tel:'.$value['hp'].'"><i class="bi bi-telephone"></i> '.$value['name'].'</a>';
    }
    ?>
  </div>

  <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">봉사자</span></h5>
  <div id="volunteer_view" class="mb-3 p-2"><?=$volunteered?></div>
  <div class="text-right">
    <a class="btn btn-outline-secondary m-1" href="<?=BASE_PATH.'/pages/meeting.php?s_date='.$s_date.'&toYear='.date("Y", strtotime($s_date)).'&toMonth='.date("n", strtotime($s_date))?>">목록</a>
    <?php if( $datetime >= date('Y-m-d H:i') ):
       $button = '<button type="button" class="m-1 btn btn-outline-';
       if(in_array(mb_id(),$member_of_meeting)){
         $button .= 'danger" onclick="display_work(\'cancle\', \''.$row['ms_id'].'\', \''.$s_date.'\', this)">불참';
       }else{ // 지원 인원수 제한
         $button .= ( !empty($attend_limit) && $count >= $attend_limit )?'primary disabled" disabled>마감':'primary" onclick="display_work(\'support\', \''.$row['ms_id'].'\', \''.$s_date.'\', true)">지원';
       }
       $button .= '</button>';
       echo $button;
     endif;?>
  </div>
</div>

<?php include_once('../footer.php'); ?>
