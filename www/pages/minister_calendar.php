<?php include_once('../config.php');?>

<?php
$mb_id = mb_id();
$today = date('Y-m-d');
$today_time = date('H:i:s');
$bgcolor = array('text-dark', 'text-primary', 'text-info', 'text-success', 'text-warning', 'text-danger', 'text-secondary');
$events = array();
$vol = array();
?>
 
<?php
// 모든 회중일정 출력 sql - $year와 $month가 정의된 후에 실행되도록 이동

//봉사자 개인 계획
if(MINISTER_SCHEDULE_EVENT_USE == 'use'){
  $me_sql = "SELECT me_date, me_date2, me_color FROM ".MINISTER_EVENT_TABLE." WHERE mb_id = '{$mb_id}'";
  $me_result = $mysqli->query($me_sql);
  if($me_result->num_rows > 0){
    while($me = $me_result->fetch_assoc()){
      $me_date1 =  date('Y-m-d', strtotime($me['me_date']));
      $me_date2 =  date('Y-m-d', strtotime($me['me_date2']));
      $events[] = array(
        'type' => 'me',
        'date1' => $me_date1,
        'date2' => $me_date2,
        'color' => $bgcolor[$me['me_color']]
      );
    }
  }
}

/********** 입력값 **********/
if(isset($_GET['toYear']) && isset($_GET['toMonth'])){
  if(isset($_GET['s_date'])){
    $s_date = date('Y-m-d', strtotime($_GET['s_date']));
    $year = date( "Y", strtotime($_GET['s_date']));
    $month =  date( "n", strtotime($_GET['s_date']));
  }else{
    $year = $_GET['toYear'];
    $month = $_GET['toMonth'];
    $s_date = (date('Y-m') == date('Y-m', mktime(0, 0, 0, $month, 1, $year)))?$today:date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
  }
}elseif(isset($_GET['s_date'])){
  $s_date = date('Y-m-d', strtotime($_GET['s_date']));
  $year = date( "Y", strtotime($_GET['s_date']));
  $month =  date( "n", strtotime($_GET['s_date']));
}else{
  $s_date = $today;
  $year = date( "Y" );
  $month =  date( "n" );
}


/********** 계산값 **********/
$mktime = mktime(0, 0, 0, $month, 1, $year);      // 입력된 값으로 년-월-01을 만든다
$days = date("t", $mktime);                        // 현재의 year와 month로 현재 달의 일수 구해오기
$startDay = date("w", $mktime);                        // 시작요일 알아내기

// 모든 회중일정 출력 sql
if(!is_moveout($mb_id)){ // 전출전도인이 아닐때만 회중일정 볼 수 있게
  $mac_sql = "SELECT * FROM ".MEETING_ADD_TABLE." ORDER BY DATE(ma_date)";
  $mac_result = $mysqli->query($mac_sql);
  if($mac_result->num_rows > 0){
    while($mac = $mac_result->fetch_assoc()) {
      $date = '';
      $auto_year = $year;  // 달력에서 보고 있는 연도 사용
      $auto_month = $month; // 달력에서 보고 있는 월 사용

      if($mac['ma_auto'] == 1){
        $week = ($mac['ma_week'] == 6)?5:$mac['ma_week'];
        $weekday = ($mac['ma_weekday'] == 7)?0:$mac['ma_weekday'];

        $day = getNthWeekday($auto_year, $auto_month, $week, $weekday);
        if($day == false && $mac['ma_week'] == 6){
          $week = 4;
          $day = getNthWeekday($auto_year, $auto_month, $week, $weekday);
        }
        if($day !== false){
          $date = date('Y-m-d', mktime(0, 0, 0, $auto_month, $day, $auto_year));
          $date1 = $date;
          $date2 = $date;
        }else{
          continue;
        }
      }else{
        $date1 =  date('Y-m-d', strtotime($mac['ma_date']));
        $date2 =  date('Y-m-d', strtotime($mac['ma_date2']));
      }
      $events[] = array(
        'type' => 'ma',
        'date1' => $date1,
        'date2' => $date2,
        'color' => $mac['ma_color']
      );
    }
  }
}

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

//봉사모임 참여 여부
$volunteered = array();
$sql = "SELECT m_date FROM ".MEETING_TABLE." WHERE FIND_IN_SET({$mb_id},mb_id) AND m_cancle = '0' AND WEEKDAY(m_date) + 1 = ms_week AND YEAR(m_date) = ".$year." AND MONTH(m_date) = ".$month;
$result = $mysqli->query($sql);
if($result->num_rows > 0){
  while($row = $result->fetch_assoc()) $volunteered[] = $row['m_date'];
}

// 집단봉사 스케쥴 모두 구하기
$g_ms = array();
$sql = "SELECT ms_id, ms_week FROM ".MEETING_SCHEDULE_TABLE." WHERE g_id <> 0";
$result = $mysqli->query($sql);
if($result->num_rows > 0){
  while($row = $result->fetch_assoc()) $g_ms[$row['ms_week']][] = $row['ms_id'];
}

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
      $week .= '';
      if($today == $cal_day) $class .= ' badge-primary';
      if($s_date == $cal_day) $class .= ' badge-danger';
      if(isset($vol[$cal_day])) $class .= ' border border-warning';

      // 집단봉사가 있다면
      // if(array_key_exists($cols,$g_ms) && $today != $cal_day){
      //     $class .= ' text-primary';
      // } 

      // 전출 전도인은 전출 날짜 이후의 봉사모임 정보는 볼 수 없게
      $moveoutDate = get_member_moveout_date($mb_id);
      $formattedDate = sprintf("%04d-%02d-%02d", $year, $month, $nowDayCount);
      if(is_moveout($mb_id) && $moveoutDate < $formattedDate){
        $week .= '<td class="py-1 px-0 disabled"><div class="'.$class.'" style="width:27px;height:27px;line-height:27px;">'.$nowDayCount++.'</div>';
      }else{
        $week .= '<td class="py-1 px-0" onclick="schedule_reload(\''.$cal_day.'\', \'minister_calendar_schedule\');"><div class="'.$class.'" style="width:27px;height:27px;line-height:27px;"><strong class="align-middle">'.$nowDayCount++.'</strong></div>';
      }
      
      // 일정이 있다면
      $week .= '<div class="p-0" style="line-height:12px;min-height:12px;">';
      if(!empty($events)){
        $i = 0;
        foreach($events as $event){
          if($event['date1'] <= $cal_day && $event['date2'] >= $cal_day && $i == 4){
            $week .= '<i class="bi bi-caret-right-fill" style="font-size:10px;margin: -1px;"></i>';
            break;
          }elseif($i < 3){
            if($event['date1'] <= $cal_day && $event['date2'] >= $cal_day){
              if($i < 3){
                if($event['type'] == 'me'){
                  $week .= '<i class="bi bi-record2-fill '.$event['color'].'" style="font-size:10px;margin: -1px;"></i>';
                }else{
                  $week .= '<i class="bi bi-record2-fill" style="font-size:10px;margin: -1px;color:'.$event['color'].'"></i>';
                }
                $i++;
              }
              if($i == 3) $i++; 
            }
          }
        }
      }
      $week .= '</div>';

      $week .= '<div class="p-0" style="line-height:12px;min-height:12px;">';

      // 참석했다면
      if(in_array($cal_day, $volunteered)){
        $week .= '<small class="align-middle text-success"><i class="bi bi-person-check-fill"></i></small>';
      }
      
      $week .= '</div>';


      $week .= '</td>';
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
  $week .= '</tr>';
}
$weeks[] = $week;
$week = '';
?>

<div class="calendar" calendar="minister_calendar" list="minister_calendar_schedule">
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
