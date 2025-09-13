<?php include_once('../config.php');?>

<?php
/********** 사용자 설정값 **********/
$firstday = '2018-08-22';
$today = date('Y-m-d');

/********** 입력값 **********/
if(isset($_POST['s_date'])){
  $s_date = $_POST['s_date'];
  $year = date( "Y", strtotime($s_date));
  $month = date( "n", strtotime($s_date));
}elseif(isset($_GET['toYear']) && isset($_GET['toMonth'])){
  $year = $_GET['toYear'];
  $month = $_GET['toMonth'];
  if(isset($_GET['s_date'])){
    $s_date = $_GET['s_date'];
  }else{
    $s_date = (date('Y-m') == date('Y-m', mktime(0, 0, 0, $month, 1, $year)))?$today:date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
  }
}else{
  $s_date = $today;
  $year = date( "Y" );
  $month = date( "n" );
}

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

      if($firstday < $cal_day){
        $week .= '<td onclick="schedule_reload(\''.$cal_day.'\', \'guide_history_list\');">';
        if($today == $cal_day) $class .= ' badge-primary';
        if($s_date == $cal_day) $class .= ' badge-danger';
        $week .= '<div class="'.$class.'" style="width:25px;">'.$nowDayCount++.'</div></td>';
      }else{
        $week .= '<td class="text-black-50 disabled"><div>'.$nowDayCount++.'</div></td>';
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

<div class="calendar" calendar="guide_history_calendar" list="guide_history_list">
  <div class="calendar_haeder text-right h5">
    <a href="#" class="calendar_month d-inline" toYear=<?=$prevYear?> toMonth=<?=$prevMonth?>>
      <i class="bi bi-caret-left-fill"></i>
    </a>
    <div class="d-inline"><?=$year?>년 <?=$month?>월</div>
    <a href="#" class="calendar_month d-inline" toYear=<?=$nextYear?> toMonth=<?=$nextMonth?>>
      <i class="bi bi-caret-right-fill"></i>
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
