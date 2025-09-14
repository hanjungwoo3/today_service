<?php include_once('../config.php');?>

<?php
$mb_id = mb_id();
$today = date('Y-m-d');
$tdatetime = date('Y-m-d H:i');
// 빈 문자열 안전 처리 (PHP 5.5~8.3)
$ms_id_string = get_ms_id_by_guide($mb_id);
$ms_ids = !empty($ms_id_string) ? explode(',', $ms_id_string) : array();

if(!isset($s_date)){
  if(isset($_GET['toYear']) && isset($_GET['toMonth'])){
    $s_date = isset($_GET['s_date'])?$_GET['s_date']:((date('Y-m') == date('Y-m', mktime(0, 0, 0, $_GET['toMonth'], 1, $_GET['toYear'])))?$today:date('Y-m-d', mktime(0, 0, 0, $_GET['toMonth'], 1, $_GET['toYear'])));
  }else{
    $s_date = $today;
  }
}

$week_val = date('N',strtotime($s_date));

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
  // 회중일정 ID 조건 안전 생성 (빈 IN() 방지)
  $ma_condition = (!empty($ma_id)) ? "(ms.ma_id IN({$ma_id}) OR ms.ma_id = '0')" : "ms.ma_id = '0'";
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
      {$ma_condition} 
      AND ms.ms_week = '{$week_val}' 
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
    ORDER BY 
      ms_time, 
      g_name, 
      mp_name, 
      ms_id ASC";
}
$result = $mysqli->query($sql);
?>

<div class="list-group list-group-flush mt-2">
<?php
if($result->num_rows > 0):
  while ($row=$result->fetch_assoc()):
    $button = array();
    // $attend = ($row['ms_type'] == 1)?ATTEND_AFTER:ATTEND_DISPLAY_AFTER;
    // $ms_time = date('H:i', mktime(date('H', strtotime($row['ms_time'])), date('i', strtotime($row['ms_time']))+$attend, 0, 0, 0, 0));
    // $datetime = $s_date.' '.$ms_time;
    $auth = (is_admin($mb_id) || check_include_guide($mb_id, $row['ms_guide']) || check_include_guide($mb_id, $row['ms_guide2']))?true:false;
    $m = get_meeting_data(get_meeting_id($s_date, $row['ms_id']));
    $m_cancle = $m['m_cancle'];

    // 전시대 지원자 수 제한 구하기
    $attend_limit = get_meeting_schedule_attend_limit($row['ms_id']);

    // 모임 지원자 수 구하기 (모임이 생성되있는지, 생성안되있는지 구분)
    $sql = "SELECT mb_id FROM ".MEETING_TABLE." WHERE m_date = '{$s_date}' AND ms_id = {$row['ms_id']}";
    $result2 = $mysqli->query($sql);
    if($result2){
      $firstRow = $result2->fetch_assoc();
      $member_of_meeting = remove_moveout_mb_id(array_unique(array_filter(explode(',',$firstRow['mb_id']))));
      $count = $member_of_meeting?count($member_of_meeting):0;
    }else{
      $count = 0;
    }

    // 인도자 정보 구하기
    $str_guide = '';
    if (GUIDE_APPOINT_USE == 'use') {
      $str_guide = !empty($m['m_guide'])?'(' . get_member_name($m['m_guide']) . ')':'';
    }
    ?>

    <div class="list-group-item list-group-item-action d-flex flex-nowrap justify-content-between px-1 py-2 border-bottom border-light">
      <div class="w-100 text-dark" onclick="open_meeting_info('<?=$s_date?>','<?=$row['ms_id']?>','guide')">
        <div>
          <span class="badge badge-pill badge-light align-middle"><?=get_meeting_schedule_type_text($row['ms_type'])?></span>
          <?php if(!empty($row['ma_id']) && $row['ma_id'] != '0') echo '<span class="badge badge-pill badge-light text-info align-middle align-middle">회중일정</span>';?>
          <?php if(!empty($row['g_name'])) echo '<span class="badge badge-pill badge-light text-primary align-middle">집단 봉사⋮'.$row['g_name'].'</span>'; ?>
          <?php
          // 취소 뱃지
          if($m_cancle != 0){
            echo '<span class="badge badge-pill badge-light text-danger align-middle">취소됨';
            if($m_cancle == 2){
              echo '⋮알림비노출';
            }else{
              echo '⋮알림노출';
            }
            echo '</span>';
          }
          ?>
          <?php if(!empty($attend_limit) && $count >= $attend_limit): ?>
            <small class="badge badge-pill badge-light text-warning align-middle">참여자 마감</small>
          <?php endif; ?>
        </div>
        <div>
          <span class="align-middle"><?=get_datetime_text($row['ms_time'])?> <?=$str_guide?> <i class="bi bi-info-circle text-secondary"></i></span>
        </div>
        <div class="mt-n1">
          <small class="align-middle"><?php echo $row['mp_name'];?></small>
        </div>
        <div class="text-secondary"><small class="align-middle"><?=$count?><?=(!empty($attend_limit))?'/'.$attend_limit:''?>명 참여</small></div>
      </div>

      <?php if(is_admin($mb_id) || is_guide($mb_id)):?>
        <?php
        if($s_date >= $today && (in_array($row['ms_id'], $ms_ids) || is_admin($mb_id))){
          $btn_color = 'primary';
          $btn_text = '인도시작';
        }else{
          $btn_color = 'secondary';
          $btn_text = '모임정보';
        }
        ?>
      <div class="align-self-center flex-shrink-0">
        <button type="button" class="btn btn-outline-<?=$btn_color?>" onclick="pageGoPost({url:BASE_PATH+'/pages/guide_assign_step.php',vals:[['s_date','<?=$s_date?>'],['ms_id','<?=$row['ms_id']?>']]});"><?=$btn_text?></button>        
      </div>
      <?php endif; ?>
    </div>
  <?php endwhile;
  else:?>
  <div class="text-center align-middle p-5 text-secondary">이 날짜에는 모임이 없습니다</div>
<?php endif;?>
</div>
