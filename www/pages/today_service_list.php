<?php
include_once(__DIR__.'/../config.php');

$mb_id = mb_id();
$mb_g_id = get_member_group($mb_id);
$today = date("Y-m-d");
$week = date('N');

// 배정된 호별 구역 (오늘 배정받은 것만)
$tt_sql = "SELECT t.*, m.m_date, ms.ms_time, mp.mp_name 
           FROM ".TERRITORY_TABLE." t 
           LEFT JOIN ".MEETING_TABLE." m ON t.m_id = m.m_id 
           LEFT JOIN ".MEETING_SCHEDULE_TABLE." ms ON m.ms_id = ms.ms_id 
           LEFT JOIN ".MEETING_PLACE_TABLE." mp ON ms.mp_id = mp.mp_id 
           WHERE FIND_IN_SET({$mb_id},t.tt_assigned) AND t.mb_id = 0 AND t.tt_assigned_date = '{$today}' 
           ORDER BY t.tt_num+0 ASC, t.tt_num ASC";
$tt_result = $mysqli->query($tt_sql);

// 배정된 전시대
$d_sql = "SELECT d.*, m.m_date, ms.ms_time, mp.mp_name 
          FROM ".DISPLAY_TABLE." d 
          INNER JOIN ".MEETING_TABLE." m ON d.m_id = m.m_id 
          LEFT JOIN ".MEETING_SCHEDULE_TABLE." ms ON m.ms_id = ms.ms_id 
          LEFT JOIN ".MEETING_PLACE_TABLE." mp ON ms.mp_id = mp.mp_id 
          WHERE FIND_IN_SET({$mb_id},d.d_assigned) AND d.d_assigned_date = '{$today}' 
          ORDER BY m.ms_time";
$d_result = $mysqli->query($d_sql);

// 배정된 전화구역
$tp_sql = "SELECT t.*, m.m_date, ms.ms_time, mp.mp_name 
           FROM ".TELEPHONE_TABLE." t 
           LEFT JOIN ".MEETING_TABLE." m ON t.m_id = m.m_id 
           LEFT JOIN ".MEETING_SCHEDULE_TABLE." ms ON m.ms_id = ms.ms_id 
           LEFT JOIN ".MEETING_PLACE_TABLE." mp ON ms.mp_id = mp.mp_id 
           WHERE FIND_IN_SET({$mb_id},t.tp_assigned) AND t.mb_id = 0 AND t.tp_assigned_date = '{$today}' 
           ORDER BY t.tp_num+0 ASC, t.tp_num ASC";
$tp_result = $mysqli->query($tp_sql);

// 모임형태 사용 설정 가져오기
$c_meeting_schedule_type_use = unserialize(MEETING_SCHEDULE_TYPE_USE);

// 사용 가능한 모임형태 필터링
$allowed_types = array();
for($i = 1; $i <= 6; $i++) {
  if(!isset($c_meeting_schedule_type_use[$i]) || $c_meeting_schedule_type_use[$i] === 'use') {
    $allowed_types[] = $i;
  }
}
$type_filter = !empty($allowed_types) ? "AND ms.ms_type IN (".implode(',', $allowed_types).")" : "";

// 당일 모임장소 출력
$ma_id = get_addschedule_id($today);
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
        AND m.m_date = '{$today}'
      WHERE 
        (ms.ma_id IN({$ma_id}) OR ms.ma_id = '0') 
        AND ms.ms_week = '{$week}' 
        AND (ms.g_id = 0 OR ms.g_id = '{$mb_g_id}') 
        AND (m.m_cancle IS NULL OR m.m_cancle != 2) 
        {$type_filter}
      ORDER BY 
        ms.ms_time, 
        g.g_name, 
        mp.mp_name,  
        ms.ms_id ASC";
$result = $mysqli->query($sql);
if($result->num_rows > 0){
  while($row = $result->fetch_assoc()){

    // 전시대참여 '불가능'한 전도인일 경우 참석버튼 안나오게
    if($row['ms_type'] == 2 && get_member_display($mb_id) == 1) continue;

    $m = get_meeting_data(get_meeting_id($today, $row['ms_id']));

    $attend_limit = get_meeting_schedule_attend_limit($row['ms_id']);

    $g_name = $row['g_name']?'['.$row['g_name'].'집단]':'';
    $count = 0;
    $attend = false;

    // 지원되있는 전도인이 더이상 데이터베이스에 남아있지 않을때
    $member_of_meeting = remove_moveout_mb_id(array_unique(array_filter(explode(',',$m['mb_id']))));
    if($member_of_meeting) $count = count($member_of_meeting);

    // 인도자 정보 구하기
    $str_guide = '';
    if (GUIDE_APPOINT_USE == 'use') {
      $str_guide = !empty($m['m_guide'])?'(' . get_member_name($m['m_guide']) . ')':'';
    }

    if($m['m_cancle'] == 0){ // 모임시간이 +-분인 모임은 참석 버튼 표시

      // 참석/지원 가능 시간
      $current_time = new DateTime(); // 현재시간
      $meeting_time = new DateTime($today.' '.$row['ms_time']); // 모임시간
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

      if(ATTEND_USE == 'use' && $row['ms_type'] != 2 && $can_attend){
        $attend = true;
      }elseif(ATTEND_DISPLAY_USE == 'use' && $row['ms_type'] == 2 && get_member_display($mb_id) != 1 && $can_attend){
        $attend = true;
      }

      if($attend){
        $status = ( $m['mb_id'] && in_array($mb_id,explode(',',$m['mb_id'])) )?'attend':'';
        ?>
        <div class="list-group list-group-flush border-bottom border-light">
          <div class="list-group-item list-group-item-action d-flex flex-nowrap justify-content-between px-3 py-2" >
            <div class="w-100 text-dark" onclick="open_meeting_info('<?=$today?>','<?=$row['ms_id']?>','home')">
              <div>
                <span class="badge badge-pill badge-light align-middle"><?=get_meeting_schedule_type_text($row['ms_type'])?></span>
                <?php if(!empty($row['g_name'])) echo '<span class="badge badge-pill badge-light text-primary align-middle">집단 봉사⋮'.$row['g_name'].'</span>'; ?>
                <?php if($status == 'attend'): ?>
                  <small class="badge badge-pill badge-light text-success align-middle"><i class="bi bi-person-check-fill"></i> 참석</small>
                <?php else: ?>
                  <?php if( !empty($attend_limit) && $count >= $attend_limit): ?>
                    <small class="badge badge-pill badge-light text-warning align-middle">참여자 마감</small>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
              <div>
                <span class="align-middle"> <?=get_datetime_text($row['ms_time'])?> <?=$str_guide?> <i class="bi bi-info-circle text-secondary"></i></span>
              </div>
              <div class="mt-n1"><small class="align-middle"><?php echo $row['mp_name'];?></small></div>
              <div class="text-secondary"><small><?=$count;?><?php echo !empty($attend_limit)?'/'.$attend_limit.' ':'';?>명 참여</small></div>
            </div>
            <div class="align-self-center flex-shrink-0">
              <?php if($status == 'attend'): ?>
                <button type="button" class="btn btn-outline-danger" onclick="attend_ministry('<?=date('Y-m-d')?>',<?=$row['ms_id']?>,this);">불참</button>
              <?php else: ?>
                 <?php if( !empty($attend_limit) && $count >= $attend_limit): ?>
                 <button type="button" class="btn btn-outline-primary disabled" disabled>마감</button>
                 <?php else: ?>
                 <button type="button" class="btn btn-outline-primary" onclick="attend_ministry('<?=date('Y-m-d')?>',<?=$row['ms_id']?>,this);">참석</button>
                 <?php endif;
               endif; ?>
            </div>
          </div>
        </div>
        <?php
      }
    }
  }
}
?>

<!-- 당일 배정받은 구역카드,전시대 출력 -->
<div id="container" class="container-fluid mt-3">
<?php
if($tt_result->num_rows || $d_result->num_rows || $tp_result->num_rows):
  if($tt_result->num_rows > 0):
    while($row = $tt_result->fetch_assoc()):
      $tt_id = $row['tt_id'];
      $color_type = $row['tt_type']=='편지'?'badge-info':'badge-success';
      $territory_progress = get_territory_progress($tt_id);
      if ($territory_progress['total'] != 0) {
          $progress = floor((($territory_progress['visit']+$territory_progress['absence']) / $territory_progress['total']) * 100);
      } else {
          $progress = 0; // 혹은 원하는 기본값으로 설정
      }
      $assigned_group_arr = get_assigned_group_name($row['tt_assigned'],$row['tt_assigned_group']);
      $assigned_group = (is_array($assigned_group_arr) == 1)?implode(' <span class="mx-1">|</span> ', $assigned_group_arr):$assigned_group_arr;
      
      $all_past_records = get_all_past_records('territory',$tt_id);
      ?>
      <div class="list-group mb-2">
        <div class="list-group-item d-flex flex-nowrap justify-content-between p-2 border-light">
          <div class="flex-grow-1 pr-2">
            <div class="mb-1">
              <span class="badge badge-pill <?=$color_type?> badge-outline px-1 align-middle"><?=$row['tt_num']?> · <?=get_type_text($row['tt_type'])?></span>
              <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle">
                <?=$row['tt_status'] == 'absence' || $row['tt_status'] == 'absence_reassign'?'<i class="bi bi-person-fill-slash"></i> 부재':'<i class="bi bi-people-fill"></i> 전체'?>
            
                <?php 
                // 방문 기록이 있는지 확인
                if(!empty($all_past_records)): ?>
                  <?php 
                  // 새로운 progress 키 사용
                  if($all_past_records[0]['progress'] == 'completed'): ?>
                    <span class="text-success">완료</span>
                  <?php 
                  // 진행 중
                  elseif($all_past_records[0]['progress'] == 'in_progress'): ?>
                    <span class="text-warning">진행 중</span>
                  <?php endif; ?>
                <?php endif; ?>
              </span>
            </div>
            <div>
              <span class=" align-middle"><?=$row['tt_name']?></span>
            </div>

            <div class="progress d-inline-flex align-middle w-100 mt-n1" style="height: 5px;">
              <div class="progress-bar <?= $progress == 100 ? 'bg-success' : 'bg-warning'?>" role="progressbar" style="width:<?=$progress.'%';?>" aria-valuenow="<?=$progress;?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <div class="mt-n2">
              <small class="text-secondary d-inline-block">
                <?php if($row['tt_type'] == '편지'): ?>
                  전체 <?=$territory_progress['total']?> · 발송 <?=$territory_progress['visit']?> · 남은 집 <?=$territory_progress['total'] - $territory_progress['visit']?>
                <?php else: ?>
                  전체 <?=$territory_progress['total']?> · 만남 <?=$territory_progress['visit']?> · 부재 <?=$territory_progress['absence']?> · 남은 집 <?=$territory_progress['total'] - $territory_progress['visit'] - $territory_progress['absence']?>
                <?php endif; ?>
              </small>
            </div>

            <?php if($assigned_group) echo '<div class="assigned_group_name mt-1">'.$assigned_group.'</div>'; ?>

          </div>
          <div class="align-self-center flex-shrink-0">
            <button type="button" class="btn btn-outline-secondary" onclick="open_territory_view(<?=$tt_id?>,'start')">시작</button>
          </div>
        </div>
      </div>
  <?php endwhile;
  endif;
  if($d_result->num_rows > 0):
    while ($row = $d_result->fetch_assoc()):
      $dp_name = $row['dp_name'].' ';
      $dp_name .= ($row['dp_num'])?$row['dp_num'].'팀':'1팀';

      $d_assigned_group_arr = get_assigned_group_name($row['d_assigned'],$row['d_assigned_group']);
      $d_assigned_group = (is_array($d_assigned_group_arr) == 1)?implode(' <span class="mx-1">|</span> ', $d_assigned_group_arr):$d_assigned_group_arr;
      ?>
      <div class="list-group mb-2">
        <div class="list-group-item p-2 border-light">
          <div class="d-flex flex-nowrap justify-content-between">
            <div>
              <div>
                <span class="badge badge-pill badge-primary badge-outline px-1 align-middle">전시대</span>
              </div>
              <div>
                <span class="align-middle"><?=$dp_name?></span>
                <?php if($row['dp_address']) echo kakao_menu($row['dp_address']);?>
              </div>
              <?php if($d_assigned_group) echo '<div class="assigned_group_name mt-1">'.$d_assigned_group.'</div>'; ?>
            </div>
          </div>
          <div class="bg-light p-2">
            <?php
            $dp_name2 = '';
            $sql = "SELECT * FROM ".DISPLAY_TABLE." WHERE m_id = {$row['m_id']} order by dp_name ASC, dp_num";
            $result = $mysqli->query($sql);
              while ($row2 = $result->fetch_assoc()) :
                if($dp_name2 != $row2['dp_name']){
                  $dp_name2 = $row2['dp_name'];
                  echo '<small>'.$dp_name2.'</small>';
                }
                $num2 = $row2['dp_num']?$row2['dp_num'].'팀':'1팀';
                $d_assigned_group2 = '';
                $d_assigned_group_arr2 = get_assigned_group_name($row2['d_assigned'],$row2['d_assigned_group']);
                $d_assigned_group2 = (is_array($d_assigned_group_arr2) == 1)?implode(' <span class="mx-1">|</span> ', $d_assigned_group_arr2):$d_assigned_group_arr2;
              ?>
              <div class="d-flex flex-row bd-highlight text-secondary mt-n1">
                <span class="flex-shrink-0">
                  <small class="text-secondary pr-1"><?=$num2?>:</small>
                </span>
                <span class="flex-grow-1">
                  <small class="text-secondary"><?=$d_assigned_group2?></small>
                </span>
              </div>
            <?php endwhile;?>
          </div>
        </div>
      </div>
  <?php endwhile;
  endif;
  if($tp_result->num_rows > 0):
    while ($row = $tp_result->fetch_assoc()):
      $tp_id = $row['tp_id'];
      $telephone_progress = get_telephone_progress($tp_id);
      if ($telephone_progress['total'] != 0) {
          $progress = floor((($telephone_progress['visit']+$telephone_progress['absence'])/$telephone_progress['total'])*100);
      } else {
          $progress = 0; // 또는 0 대신 다른 기본값을 설정할 수 있습니다.
      }
      $tp_assigned_group_arr = get_assigned_group_name($row['tp_assigned'],$row['tp_assigned_group']);
      $tp_assigned_group = (is_array($tp_assigned_group_arr) == 1)?implode(' <span class="mx-1">|</span> ', $tp_assigned_group_arr):$tp_assigned_group_arr;
      
      $tp_all_past_records = get_all_past_records('telephone',$tp_id);
      ?>
      <div class="list-group mb-2">
        <div class="list-group-item d-flex flex-nowrap justify-content-between p-2 border-light">
          <div class="flex-grow-1 pr-2">
            <div class="mb-1">
              <span class="badge badge-pill badge-warning badge-outline px-1 align-middle"><?=$row['tp_num']?> · 전화</span>
              <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle">
                <?=$row['tp_status'] == 'absence' || $row['tp_status'] == 'absence_reassign'?'<i class="bi bi-person-fill-slash"></i> 부재':'<i class="bi bi-people-fill"></i> 전체'?>
            
                <?php 
                // 방문 기록이 있는지 확인
                if(!empty($tp_all_past_records)): ?>
                  <?php 
                  // 새로운 progress 키 사용
                  if($tp_all_past_records[0]['progress'] == 'completed'): ?>
                    <span class="text-success">완료</span>
                  <?php 
                  // 진행 중
                  elseif($tp_all_past_records[0]['progress'] == 'in_progress'): ?>
                    <span class="text-warning">진행 중</span>
                  <?php endif; ?>
                <?php endif; ?>
              </span>
            </div>
            <div>
              <span class=" align-middle"><?=$row['tp_name']?></span>
            </div>

            <div class="progress d-inline-flex align-middle w-100 mt-n1" style="height: 5px;">
              <div class="progress-bar <?= $progress == 100 ? 'bg-success' : 'bg-warning'?>" role="progressbar" style="width:<?=$progress.'%';?>" aria-valuenow="<?=$progress;?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <div class="mt-n2">
              <small class="text-secondary d-inline-block">
                전체 <?=$telephone_progress['total']?> · 만남 <?=$telephone_progress['visit']?> · 부재 <?=$telephone_progress['absence']?> · 남은 집 <?=$telephone_progress['total'] - $telephone_progress['visit'] - $telephone_progress['absence']?>
              </small>
            </div>

            <?php if($assigned_group) echo '<div class="assigned_group_name mt-1">'.$assigned_group.'</div>'; ?>
          </div>
          <div class="align-self-center flex-shrink-0">
            <button type="button" class="btn btn-outline-secondary" onclick="open_telephone_view(<?=$tp_id?>,'start')">시작</button>
          </div>
        </div>
      </div>
    <?php endwhile;
    endif;
  else:
    echo '<div class="text-center align-middle p-5 text-secondary" >배정받은 구역이 없습니다</div>';
  endif;?>
</div>