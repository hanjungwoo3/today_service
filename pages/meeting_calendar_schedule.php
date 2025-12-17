<?php include_once('../config.php');?>

<?php if(!empty($_GET['toYear']) && !empty($_GET['toMonth']) && empty($_GET['s_date'])) exit;?>

<?php
$i = 0;
$mb_id = mb_id();
$mb_g_id = get_member_group($mb_id);
$member_of_meeting = array();

// 로컬 날짜 전달값 우선 사용해 서버 타임존 차이 방지
// 클라이언트에서 넘어온 날짜를 우선 사용해 서버 타임존으로 하루 밀리는 문제 방지
if(!empty($_POST['s_date'])){
  $s_date = $_POST['s_date'];
}elseif(!empty($_GET['s_date'])){
  $s_date = $_GET['s_date'];
}else{
  $s_date = date('Y-m-d');
}
$week_val = date('N', strtotime($s_date));

//지정된 날짜 내 회중일정 sql
$ma_id = get_addschedule_id($s_date);
$sql = "SELECT * FROM ".MEETING_ADD_TABLE." WHERE ma_id IN({$ma_id}) ORDER BY ma_auto DESC, ma_date DESC, ma_date2, ma_title";
$ma_result = $mysqli->query($sql);

//전시대 일정 리스트 sql
$sql = "SELECT ms_id, ms_time, mp_name, g_name, ms_limit, ms_type
        FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".MEETING_PLACE_TABLE." mp ON ms.mp_id = mp.mp_id LEFT JOIN ".GROUP_TABLE." g ON ms.g_id = g.g_id
        WHERE (ma_id IN({$ma_id}) OR ma_id = '0') AND ms_week = '{$week_val}' AND ms_type = 2 AND (ms.g_id = 0 OR ms.g_id = '{$mb_g_id}')
        ORDER BY ms_time, g_name, mp_name, ms_id ASC";
$result = $mysqli->query($sql);
?>

<?php if($ma_result->num_rows > 0):?>
  <div class="list-group mb-3">
  <?php while($mar = $ma_result->fetch_assoc()):?>
    <div class="list-group-item d-flex justify-content-between align-items-center p-2">
      <span>
        <span class="badge badge-light text-info align-middle">일정</span>
        <span class="align-middle"><?=$mar['ma_title']?></span>
      </span>
      <a href="<?=BASE_PATH?>/pages/minister_schedule.php?s_date=<?=$s_date?>#minister_event" class="btn btn-outline-secondary btn-sm badge"><small>상세보기</small></a>
    </div>
  <?php endwhile;?>
  </div>
<?php endif;?>
 
<div class="list-group list-group-flush">
<?php if($result->num_rows > 0):
  while ($row = $result->fetch_assoc()) {
    $ms_id = $row['ms_id'];
    $attend_limit = get_meeting_schedule_attend_limit($row['ms_id']);
    $mp_name = $row['g_name']?'['.$row['g_name'].'집단] ':'';
    $mp_name .= $row['mp_name'];

    $ds_sql = "SELECT mb_id, m_cancle, m_guide, ms_guide FROM ".MEETING_TABLE." WHERE m_date = '{$s_date}' AND ms_id = '{$ms_id}'";
    $ds_result = $mysqli->query($ds_sql);
    $dsw = $ds_result->fetch_assoc();
    // 모임 생성 전이거나, 취소되지 않은 모임 정보 가져오기
    if((!empty($dsw) && $dsw['m_cancle'] == 0) || empty($dsw)){

      // 지원자 수 구하기 (지원되있는 전도인이 더이상 데이터베이스에 남아있지 않을때를 걸러냄)
      $member_of_meeting = array();
      if(!empty($dsw)){
        $member_of_meeting = remove_moveout_mb_id(array_unique(array_filter(explode(',',$dsw['mb_id']))));
      }
      $count = count($member_of_meeting);

      // 인도자 정보 구하기
      $str_guide = '';
      if (GUIDE_APPOINT_USE == 'use') {
          $guide = isset($dsw['m_guide']) ? $dsw['m_guide'] : (isset($dsw['ms_guide']) ? $dsw['ms_guide'] : '');
          if ($guide) $str_guide = '(' . get_member_name($guide) . ')';
      }

      // 참석/지원 가능 시간
      $current_time = new DateTime(); // 현재시간
      $meeting_time = new DateTime($s_date.' '.$row['ms_time']); // 모임시간
      $before_time = clone $meeting_time;
      $attend_before = ($row['ms_type'] == 2) ? ATTEND_DISPLAY_BEFORE : ATTEND_BEFORE;
      if ($attend_before < 0) {
        $before_time->modify("+" . abs($attend_before) . " minutes");
      } else {
        $before_time->modify("-" . $attend_before . " minutes");
      }
      $after_time = clone $meeting_time;
      $attend_after = ($row['ms_type'] == 2) ? ATTEND_DISPLAY_AFTER : ATTEND_AFTER;
      if ($attend_after < 0) {
        $after_time->modify("-" . abs($attend_after) . " minutes");
      } else {
        $after_time->modify("+" . $attend_after . " minutes");
      }
      $can_attend = ($current_time >= $before_time && $current_time <= $after_time)?true:false;
      ?>

      <div class="list-group-item list-group-item-action d-flex flex-nowrap justify-content-between px-1 py-2">
        <div class="w-100 text-dark" onclick="open_meeting_info('<?=$s_date?>','<?=$ms_id?>','display')">
          <div>
            <span class="badge badge-pill badge-light align-middle"><?=get_meeting_schedule_type_text($row['ms_type'])?></span>
            <?php if(in_array($mb_id,$member_of_meeting)): ?>
              <small class="badge badge-pill badge-light text-success align-middle"><i class="bi bi-person-check-fill"></i> 지원</small>
            <?php else: ?>
              <?php if( !empty($attend_limit) && $count >= $attend_limit): ?>
                <small class="badge badge-pill badge-light text-warning align-middle">참여자 마감</small>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <div>
            <span class="align-middle"><?=get_datetime_text($row['ms_time'])?> <?=$str_guide?> <i class="bi bi-info-circle text-secondary"></i></span>
          </div>
          <div class="mt-n1"><small class="align-middle"><?=$mp_name?></small></div>
          <div class="text-secondary"><small><?=$count;?><?php echo !empty($attend_limit)?'/'.$attend_limit.' ':'';?>명 참여</small></div>
        </div>
        <div class="align-self-center flex-shrink-0 pl-2">
          <?php if( $can_attend ): // 참석 가능한지
            $button = '<button type="button" class="btn btn-outline-';
            if(in_array($mb_id,$member_of_meeting)){
              $button .= 'danger" onclick="display_work(\'cancle\', \''.$ms_id.'\', \''.$s_date.'\')">불참';
            }else{ // 지원 인원수 제한
              $button .= ( !empty($attend_limit) && $count >= $attend_limit )?'primary disabled" disabled>마감':'primary" onclick="display_work(\'support\', \''.$ms_id.'\', \''.$s_date.'\')">지원';
            }
            $button .= '</button>';
            echo $button;
          endif;?>
        </div>
      </div>
    <?php
    $i++;
    }
  }
  if($i == 0) echo '<div class="text-center align-middle p-5">마련된 공개 모임이 없습니다.</div>';?>
<?php else:?>
  <div class="text-center align-middle p-5">마련된 전시대 모임이 없습니다.</div>
<?php endif;?>
</div>
