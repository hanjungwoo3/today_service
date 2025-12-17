<?php include_once('../config.php');?>

<?php
$mb_id = mb_id();
$mb_g_id = get_member_group($mb_id);

// 로컬 날짜(s_date)가 넘어오면 그것을 오늘로 사용해 서버 타임존 차이로 인한 하루 차이 방지
$s_date_param = '';
if (!empty($_POST['s_date'])) {
  $s_date_param = $_POST['s_date'];
} elseif (!empty($_GET['s_date'])) {
  $s_date_param = $_GET['s_date'];
}
$today_dt = DateTime::createFromFormat('Y-m-d', $s_date_param);
$today = $today_dt ? $today_dt->format('Y-m-d') : date('Y-m-d');
//주에 일정이 몇 개 있는지
$ms_week = array();
$m_sql = "SELECT ms_week FROM ".MEETING_SCHEDULE_TABLE." WHERE ms_type = 2 AND ma_id = 0 AND (g_id = 0 OR g_id = '{$mb_g_id}')";
$m_result = $mysqli->query($m_sql);
while($mow = $m_result->fetch_assoc()){
  if(empty($ms_week[$mow['ms_week']])) $ms_week[$mow['ms_week']] = 0;
  $ms_week[$mow['ms_week']]++;
}

//취소된 일정 개수
$m_cancle = array();
$ms_sql = "SELECT m.m_date
           FROM ".MEETING_TABLE." m INNER JOIN ".MEETING_SCHEDULE_TABLE." ms ON m.ms_id = ms.ms_id
           WHERE (m.ms_type = 2 OR m.ms_type = 0) AND m.m_cancle != 0  AND (ms.g_id = 0 OR ms.g_id = '{$mb_g_id}')";
$ms_result = $mysqli->query($ms_sql);
while($msw = $ms_result->fetch_assoc()){
  if(empty($m_cancle[$msw['m_date']])) $m_cancle[$msw['m_date']] = 0;
  $m_cancle[$msw['m_date']]++;
} 

$volunteered = array();
$sql = "SELECT m_date FROM ".MEETING_TABLE." WHERE ms_type = 2 AND FIND_IN_SET({$mb_id},mb_id) AND m_cancle = '0'";
$result = $mysqli->query($sql);
while($row = $result->fetch_assoc()) $volunteered[] = $row['m_date'];

/********** 사용자 설정값 + 입력값 **********/
if(isset($_GET['toYear']) && isset($_GET['toMonth'])){
  $year = (int)$_GET['toYear'];
  $month = (int)$_GET['toMonth'];

  if(!empty($_POST['s_date'])){
    $s_date = $_POST['s_date'];
  }elseif(!empty($_GET['s_date'])){
    $s_date = $_GET['s_date'];
  }else{
    // 오늘 달이면 오늘, 아니면 그 달의 1일
    $s_date = (date('Y-m') == sprintf('%04d-%02d', $year, $month))
      ? $today
      : sprintf('%04d-%02d-01', $year, $month);
  }
}else{
  if(!empty($_POST['s_date'])){
    $s_date = $_POST['s_date'];
  }elseif(!empty($_GET['s_date'])){
    $s_date = $_GET['s_date'];
  }else{
    $s_date = $today;
  }
  $year = (int)date( "Y", strtotime($s_date) );
  $month = (int)date( "n", strtotime($s_date) );
}

$hide = (!empty($_GET['toYear']) && !empty($_GET['toMonth']) && empty($_GET['s_date']))?'':'show';

/********** 계산값 **********/
$mktime = mktime(0, 0, 0, $month, 1, $year);      // 입력된 값으로 년-월-01을 만든다
$days = date("t", $mktime);                        // 현재의 year와 month로 현재 달의 일수 구해오기
$startDay = date("w", $mktime);                        // 시작요일 알아내기

// 지난달 일수 구하기
$prevDayCount  = date("t", mktime( 0, 0, 0, $month, 0, $year)) - $startDay + 1;

$nowDayCount = 1;             // 이번달 일자 카운팅
$nextDayCount = 1;            // 다음달 일자 카운팅

// 이전, 다음 만들기
$prevYear = ( $month == 1 )? ( $year - 1 ) : $year;
$prevMonth = ( $month == 1 )? 12 : ( $month - 1 );
$nextYear = ( $month == 12 )? ( $year + 1 ) : $year;
$nextMonth = ( $month == 12 )? 1 : ( $month + 1 );

// 출력행 계산
$setRows = ceil( ( $startDay + $days ) / 7 );

//달력 출력
$weeks = array();
$week = '';
for($rows = 0; $rows < $setRows; $rows++){
  $week .=  '<tr class="text-center">';
  for($cols = 0; $cols < 7; $cols++) {// 셀 인덱스
    $cellIndex = (7 * $rows) + $cols;
    // 이번달이라면
    if($startDay <= $cellIndex && $nowDayCount <= $days){
      $cal_day = date('Y-m-d', mktime(0, 0, 0, $month, $nowDayCount, $year));
      $week_val = date('N', strtotime($cal_day));

      $class = 'rounded-circle mx-auto';

      $ma_week = 0;
      $ma_id = get_addschedule_id($cal_day);
      $ma_sql = "SELECT ms_id
                 FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".MEETING_ADD_TABLE." ma ON ms.ma_id = ma.ma_id
                 WHERE ms.ma_id IN({$ma_id}) AND ms_type = 2 AND ms_week = '{$week_val}' AND (g_id = 0 OR g_id = '{$mb_g_id}')";
      $ma_result = $mysqli->query($ma_sql);
      while($maw = $ma_result->fetch_assoc()) $ma_week++;

      if($today > $cal_day){
        $week .= '<td class="p-1 text-black-50 disabled"><div class="mx-auto" style="width:27px;height:27px;line-height:27px;">'.$nowDayCount++.'</div><p class="p-1"></p></td>';
      }else{
        
        $ms_week_count = isset($ms_week[$week_val])?$ms_week[$week_val]:0;
        $m_cancle_count = isset($m_cancle[$cal_day])?$m_cancle[$cal_day]:0;

        if(($ms_week_count - $m_cancle_count + $ma_week) > 0){
          if($today == $cal_day) $class .= ' badge-primary';
          if($s_date == $cal_day && $hide) $class .= ' badge-danger';

          $icon = '';

          // 회중일정 있으면 표시
          // if($ma_id != 'NULL'){
          //   $icon = '<small class="align-middle text-info"><i class="bi bi-record2-fill"></i></small>';
          // }

          //지원했는지 확인 
          if(in_array($cal_day, $volunteered)){
            $icon .= '<small class="align-middle text-success"><i class="bi bi-person-check-fill"></i></small>';
          }

          $week .= '<td class="py-1 px-0" onclick="schedule_reload(\''.$cal_day.'\', \'meeting_calendar_schedule\');"><div class="'.$class.'" style="width:27px;height:27px;line-height:27px;"><strong class="align-middle">'.$nowDayCount++.'</strong></div><div class="p-0" style="line-height:12px;min-height:12px;">'.$icon.'</div></td>';
        }else{
          if($today == $cal_day){
            $week .= '<td class="py-1 px-0 disabled"><div class="'.$class.' badge-primary" style="width:27px;height:27px;line-height:27px;"><strong class="align-middle">'.$nowDayCount++.'</strong></div><p class="p-1"></p></td>';
          }else{
            $week .= '<td class="py-1 px-0 text-black-50 disabled"><div class="mx-auto" style="width:27px;height:27px;line-height:27px;">'.$nowDayCount++.'</div><p class="p-1"></p></td>';
          }
        }
      }
    // 이전달이라면
    } elseif ($cellIndex < $startDay){
      // $week .= '<td class="p-1 text-black-50 disabled"><div>'.$prevDayCount++.'</div></td>';
      $week .= '<td class="p-1 disabled"><div></div></td>';
    // 다음달 이라면
    } elseif($cellIndex >= $days){
      // $week .= '<td class="p-1 text-black-50 disabled"><div>'.$nextDayCount++.'</div></td>';
      $week .= '<td class="p-1 disabled"><div></div></td>';
    }
  }
  $week .=  '</tr>';
}
$weeks[] = $week;
$week = '';
?>

<div class="calendar" calendar="meeting_calendar" list="meeting_calendar_schedule">
  <div class="calendar_haeder text-right h5">
    <a href="#" class="calendar_month d-inline" toYear=<?=$prevYear?> toMonth=<?=$prevMonth?>>
      <i class="bi bi-caret-left h4 align-middle"></i>
    </a>
    <div class="d-inline"><?=$year?>년 <?=$month?>월</div>
    <a href="#" class="calendar_month d-inline" toYear=<?=$nextYear?> toMonth=<?=$nextMonth?>>
      <i class="bi bi-caret-right h4 align-middle"></i>
    </a>
  </div>
  <table class="table m-0">
    <thead class="text-center">
      <tr>
          <th style="color: #e91e63;">일</th>
          <th>월</th>
          <th>화</th>
          <th>수</th>
          <th>목</th>
          <th>금</th>
          <th style="color: #53b3ca">토</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($weeks as $week) echo $week; ?>
    </tbody>
  </table>
</div>
