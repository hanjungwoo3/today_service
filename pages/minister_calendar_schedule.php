<?php include_once('../config.php');?>

<?php
$min_sum = '0';
$mr_min = '0';
$hour_sum = '0';
$pub_sum = '0';
$video_sum = '0';
$return_sum = '0';
$study_sum = '0';
$mb_id = mb_id();
$today = date('Y-m-d');
$mb_g_id = get_member_group($mb_id);
$meeting_data = array();
$moveoutDate = get_member_moveout_date($mb_id);

// 전출 전도인은 전출 이후의 기록에 접근할 수 없음
if(is_moveout($mb_id) && $moveoutDate < $s_date){
  echo '<div class="text-center align-middle p-5 text-secondary">해당 날짜의 일정, 모임, 기록을 확인할 수 없습니다</div>';
  return; // 페이지 전체가 아니라 이 파일 내의 코드 실행만 중단
}

if(isset($_GET['toYear']) && isset($_GET['toMonth'])){
  $st_month =  date('Y-m-d', mktime(0, 0, 0, $_GET['toMonth'], 1, $_GET['toYear']));
  $fi_month =  date('Y-m-t', mktime(0, 0, 0, $_GET['toMonth'], 1, $_GET['toYear']));
  $s_date = (date('Y-m') == date('Y-m', mktime(0, 0, 0, $_GET['toMonth'], 1, $_GET['toYear'])))? $today:$st_month;
}else{
  if(isset($_POST['s_date'])){
    $s_date = date('Y-m-d', strtotime($_POST['s_date']));
  }elseif(isset($_GET['s_date'])){
    $s_date = date('Y-m-d', strtotime($_GET['s_date']));
  }else{
    $s_date = $today;
  }
  $st_month = date('Y-m-01', strtotime($s_date));
  $fi_month = date('Y-m-t', strtotime($s_date));
}
$to_change = date('Y-m', strtotime($s_date));
$month = date('n', strtotime($s_date));
$week_val = date('N',strtotime($s_date));

// 지정된 날짜 내 봉사보고
$mr_sql = "SELECT * FROM ".MINISTER_REPORT_TABLE." WHERE mb_id = '{$mb_id}' AND mr_date = '{$s_date}'";
$mr_result = $mysqli->query($mr_sql);
$mr = $mr_result->fetch_assoc();

// 지정된 날짜 내 총 봉사보고
$mrt_sql = "SELECT * FROM ".MINISTER_REPORT_TABLE." WHERE mr_date >= '{$st_month}' AND mr_date <= '{$fi_month}' AND mb_id = '{$mb_id}'";
$mrt_result = $mysqli->query($mrt_sql);
if($mrt_result->num_rows > 0){
  while($mrt = $mrt_result->fetch_assoc()){
    $mr_min += $mrt['mr_min'];
    $hour_sum += $mrt['mr_hour'];
    $pub_sum += $mrt['mr_pub'];
    $video_sum += $mrt['mr_video'];
    $return_sum += $mrt['mr_return'];
    $study_sum += $mrt['mr_study'];
  }
  $min_sum = $mr_min%60;
  $hour_sum += floor($mr_min/60);
}
?>
<!-- 일정 --> 
<div id="minister_event" class="mb-3">
  <?php include_once('minister_calendar_schedule_view.php'); ?>
</div>

<!-- 봉사모임 -->
<?php

// 모임형태 사용 설정 가져오기
$c_meeting_schedule_type_use = unserialize(MEETING_SCHEDULE_TYPE_USE);

// 사용 가능한 모임형태 필터링
$allowed_types = array();
for($i = 1; $i <= 6; $i++) {
  if(!isset($c_meeting_schedule_type_use[$i]) || $c_meeting_schedule_type_use[$i] === 'use') {
    $allowed_types[] = $i;
  }
}
// 현재 날짜 이후용 필터 (MEETING_SCHEDULE_TABLE의 ms_type 사용)
$type_filter = !empty($allowed_types) ? "AND ms.ms_type IN (".implode(',', $allowed_types).")" : "";

if($s_date >= $today){
  $ma_id = get_addschedule_id_sub($s_date);
  $sql = "SELECT 
      ms.ms_id, 
      COALESCE(m.ms_time,ms.ms_time) AS ms_time, 
      COALESCE(m.ms_type,ms.ms_type) AS ms_type, 
      ms.ms_limit, 
      COALESCE(m.mp_name,mp.mp_name) AS mp_name, 
      g.g_name, 
      m.m_id, 
      m.m_cancle, 
      m.m_guide, 
      m.mb_id,
      COALESCE(m.ms_guide,ms.ms_guide) AS ms_guide, 
      COALESCE(m.ms_guide2,ms.ms_guide2) AS ms_guide2,  
      COALESCE(m.ms_week,ms.ms_week) AS ms_week
    FROM 
      ".MEETING_SCHEDULE_TABLE." ms
    LEFT JOIN 
      ".MEETING_PLACE_TABLE." mp ON ms.mp_id = mp.mp_id
    LEFT JOIN 
      ".GROUP_TABLE." g ON ms.g_id = g.g_id
    LEFT JOIN 
      ".MEETING_TABLE." m ON ms.ms_id = m.ms_id
      AND m.m_date = '{$s_date}'
    WHERE 
      (ms.ma_id IN({$ma_id}) OR ms.ma_id = '0') 
      AND ms.ms_week = '{$week_val}' 
      AND (ms.g_id = 0 OR ms.g_id = '{$mb_g_id}') 
      AND (m.m_cancle IS NULL OR m.m_cancle != 2) 
      {$type_filter} 
    ORDER BY 
      ms.ms_time, 
      g.g_name, 
      mp.mp_name,  
      ms.ms_id ASC";
}else{
  $sql = "SELECT
      ms_id, 
      ms_time, 
      ms_type, 
      mp_name, 
      g_name, 
      m_id, 
      m_cancle, 
      m_guide, 
      ms_guide, 
      ms_guide2, 
      ms_week,
      mb_id,
    NULL as 
      ms_limit 
    FROM
      ".MEETING_TABLE." m
    LEFT JOIN
      ".GROUP_TABLE." g ON m.g_id = g.g_id
    WHERE
      m_date = '{$s_date}' 
      AND ms_week = '{$week_val}' 
      AND (m.g_id = 0 OR m.g_id = '{$mb_g_id}') 
      AND m_cancle != '2' 
    ORDER BY 
      ms_time, 
      g_name, 
      mp_name, 
      ms_id ASC";
}
$result = $mysqli->query($sql);
?>
<?php if($result->num_rows > 0): ?>
<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">모임</span><a onclick="open_meeting_view('<?=$s_date?>')" class="btn btn-outline-primary btn-sm float-right"><i class="bi bi-info-circle"></i> 정보</a></h5>
<div class="list-group list-group-flush mt-3 mb-3">
  <?php
  while ($row = $result->fetch_assoc()): 

    // 전시대 참여 '불가능'한 전도인일 경우 전시대 모임 자체를 숨김
    if($row['ms_type'] == 2 && get_member_display($mb_id) == 1) continue;

    $count = 0;

    // 봉사 시간 구하기
    $ms_time_i = date('i', strtotime($row['ms_time']));
    if(isset($attend)){ $ms_time_i = $ms_time_i+$attend; }
    $ms_time = date('H:i', mktime(date('H', strtotime($row['ms_time'])), $ms_time_i, 0, 0, 0, 0));

    // 인도자 정보 구하기
    $str_guide = '';
    if (GUIDE_APPOINT_USE == 'use') {
      $str_guide = !empty($row['m_guide'])?'(' . get_member_name($row['m_guide']) . ')':'';
    }

    $mb_ids = isset($row['mb_id'])?explode(',', $row['mb_id']):array();
    $status = in_array($mb_id,$mb_ids)?'attend':'';

    $attend_limit = get_meeting_schedule_attend_limit($row['ms_id']);

    // 지원되있는 전도인이 더이상 데이터베이스에 남아있지 않을때
    $member_of_meeting = remove_moveout_mb_id(array_unique(array_filter($mb_ids)));
    if($member_of_meeting) $count = count($member_of_meeting);

    // 참석/지원 가능 시간
    $current_time = new DateTime(); // 현재시간
    $meeting_time = new DateTime($s_date.' '.$row['ms_time']); // 모임시간
    $before_time = clone $meeting_time;
    $attend_before = ($row['ms_type'] == 2) ? ATTEND_DISPLAY_BEFORE : ATTEND_BEFORE;
    if ($attend_before < 0) {
      $before_time->modify("+" . abs($attend_before) . " minutes"); // 음수일 경우 양수로 변환 후 더하기
    } else {
      $before_time->modify("-" . $attend_before . " minutes"); // 정상적인 값이면 기존 방식
    }
    $after_time = clone $meeting_time;
    $attend_after = ($row['ms_type'] == 2) ? ATTEND_DISPLAY_AFTER : ATTEND_AFTER;
    if ($attend_after < 0) {
      $after_time->modify("-" . abs($attend_after) . " minutes"); // 음수일 경우 양수로 변환 후 더하기
    } else {
      $after_time->modify("+" . $attend_after . " minutes"); // 정상적인 값이면 기존 방식
    }
    $can_attend = ($current_time >= $before_time && $current_time <= $after_time)?true:false;
  ?>
  <div class="list-group-item list-group-item-action d-flex flex-nowrap justify-content-between px-1 py-2 border-bottom border-light" m_id="<?=$row['m_id']?>">
    <div class="w-100 text-dark" onclick="open_meeting_info('<?=$s_date?>','<?=$row['ms_id']?>','minister')">
      <div>
        <span class="badge badge-pill badge-light align-middle"><?=get_meeting_schedule_type_text($row['ms_type'])?></span>
        <?php //if($row['ma_id'] && $row['ma_id'] != '0') echo '<span class="badge badge-pill badge-light text-info align-middle">추가일정</span>';?>
        <?php if(!empty($row['g_name'])) echo '<span class="badge badge-pill badge-light text-primary align-middle">집단 봉사⋮'.$row['g_name'].'</span>'; ?>
        <?php if($row['m_cancle'] && $row['m_cancle'] != 0) echo '<span class="badge badge-pill badge-light text-danger align-middle">취소됨</span>';?>
        <?php if(in_array($mb_id, $mb_ids)): ?>
          <small class="badge badge-pill badge-light text-success align-middle"><i class="bi bi-person-check-fill"></i> <?=($s_date > $today)?'지원':'참석'?></small>
        <?php else: ?>
          <?php if( !empty($attend_limit) && $count >= $attend_limit): ?>
            <small class="badge badge-pill badge-light text-warning align-middle">참여자 마감</small>
          <?php endif; ?>
        <?php endif; ?>

      </div>
      <div>
        <span class="align-middle"><?=get_datetime_text($row['ms_time'])?> <?=$str_guide?> <i class="bi bi-info-circle text-secondary"></i></span>
      </div>
      <div class="mt-n1">
        <small class="align-middle"><?=$row['mp_name']?></small>
      </div>
      <div class="text-secondary"><small><?=$count;?><?php echo !empty($attend_limit)?'/'.$attend_limit.' ':'';?>명 참여</small></div>
    </div>
    <div class="align-self-center flex-shrink-0">
      <?php
      if((($row['ms_type'] != 2 && MINISTER_ATTEND_USE == 'use') || ($row['ms_type'] == 2 && MINISTER_DISPLAY_ATTEND_USE == 'use' && get_member_display($mb_id) != 1)) && $row['m_cancle'] == 0 && $can_attend ):
        ?>
        <?php if($status == 'attend'): ?>
          <button type="button" class="btn btn-outline-danger" onclick="minister_work('del',<?=$row['ms_id']?> ,'<?=date('Y-m-d', strtotime($s_date))?>', this);">불참</button>
        <?php else: ?>
            <?php if( !empty($attend_limit) && $count >= $attend_limit): ?>
            <button type="button" class="btn btn-outline-primary disabled" disabled>마감</button>
            <?php else: ?>
            <button type="button" class="btn btn-outline-primary" onclick="minister_work('support',<?=$row['ms_id']?>, '<?=date('Y-m-d', strtotime($s_date))?>',this);">지원</button>
            <?php endif;
          endif; ?>
      <?php 
      endif;
      ?>
    </div>
  </div> 
  <?php endwhile; ?>
</div>
<?php endif; ?>

<!-- 봉사기록 -->
<?php if(MINISTER_SCHEDULE_REPORT_USE == 'use'): ?>
<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">기록</span></h5>
<div id="minister_report">
  <?php if($today >= $s_date): ?>
    <form>
      <input type="hidden" name="mb_id" value="<?=$mb_id?>">
      <input type="hidden" name="date" value="<?=$s_date?>">
      <input type="hidden" name="work" value="report">
      <div class="list-group mb-3">
        <div class="list-group-item p-1">
          <div class="d-flex justify-content-center">
            <div class="text-center m-1">
              <div><?php echo ($to_change < '2023-09')?'봉사 기록':'봉사 시간';?></div>
              <div class="d-flex justify-content-center text-muted">
                <div class="m-1"><input class="form-control d-inline p-0" name="hour" type="number" min="0" max="23" value="<?=check_value($mr['mr_hour'])?>" placeholder="0"> 시간</div>
                <div class="m-1"><input class="form-control d-inline p-0" name="min" type="number" min="0" max="59" value="<?=check_value($mr['mr_min'])?>" placeholder="0"> 분</div>
              </div>
            </div>

            <?php if($to_change < '2023-09'): ?>
            <div class="text-center m-1">
              <div>출판물</div>
              <div class="my-1 mx-auto">
                <input class="form-control d-inline p-0" name="pub" type="number" min="0" value="<?=check_value($mr['mr_pub'])?>" placeholder="0">
              </div>
            </div>

            <div class="text-center m-1">
              <div>동영상</div>
              <div class="my-1 mx-auto">
                <input class="form-control d-inline p-0" name="video" type="number" min="0" value="<?=check_value($mr['mr_video'])?>" placeholder="0">
              </div>
            </div>

            <div class="text-center m-1">
              <div>재방문</div>
              <div class="my-1 mx-auto">
                <input class="form-control d-inline p-0" name="return_visit" type="number" min="0" value="<?=check_value($mr['mr_return'])?>" placeholder="0">
              </div>
            </div>
            <?php endif;?>

            <div class="text-center m-1">
              <div>연구</div>
              <div class="my-1 mx-auto">
                <input class="form-control d-inline p-0" name="study" type="number" min="0" value="<?=check_value($mr['mr_study'])?>" placeholder="0">
              </div>
            </div>

          </div>
          <div class="text-center m-2">
            <button type="submit" name="button" class="btn btn-outline-primary"><i class="bi bi-save"></i> 저장</button>
          </div>
        </div>
      </div>
    </form>
  <?php endif;?>

  <div class="list-group">
    <div class="list-group-item d-flex justify-content-center p-1">
      <div class="text-center m-1">
        <div class="mb-1"><?=$month?>월 월간 합계</div>
        <div class="text-muted"><?=$hour_sum?>시간 <?=$min_sum?>분</div>
      </div>

      <?php if($to_change < '2023-09'): ?>
      <div class="text-center m-1">
        <div class="mb-1">출판물</div>
        <div class="text-muted"><?=$pub_sum?>개</div>
      </div>

      <div class="text-center m-1">
        <div class="mb-1">동영상</div>
        <div class="text-muted"><?=$video_sum?>개</div>
      </div>

      <div class="text-center m-1">
        <div class="mb-1">재방문</div>
        <div class="text-muted"><?=$return_sum?>건</div>
      </div>
      <?php endif;?>

      <div class="text-center m-1">
        <div class="mb-1">연구</div>
        <div class="text-muted"><?=$study_sum?>건</div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
