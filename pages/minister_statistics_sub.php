<?php include_once('../config.php');?>

<?php
$mb_id = mb_id();
$month = date("n");
$first_d = mktime(0,0,0, date("m"), 1, date("Y"));
$hm = array();
$hm_count = array();
$time_count = array();

if($month >= 9){
  $year = date("Y", strtotime("+1 year", $first_d));
  $yfday = date("Y-09-01");
}elseif($month < 9){
  $year = date("Y");
  $yfday = date("Y-09-01", strtotime("-1 year", $first_d));
}

$ylday = date("Y-m-d");

if(!empty($st_year)){
  $year_month_check = 'old'; //지난 봉사 연도 출력인지 여부
}else{
  $st_year = $year;
}
if($st_year != $year){
  $last_d = mktime(0,0,0, 1, 1, $st_year);
  $yfday = date("Y-09-01", strtotime("-1 year", $last_d)); // 시작 날
  $ylday = date("Y-08-31", $last_d); // 마지막 날
}


//봉사시간
$t_sql = "SELECT * FROM ".MINISTER_REPORT_TABLE." WHERE mr_date >= '{$yfday}' AND mr_date <= '{$ylday}' AND mb_id = '{$mb_id}' ORDER BY mr_date";
$t_result = $mysqli->query($t_sql);
if ($t_result && $t_result->num_rows > 0) {
    while ($time = $t_result->fetch_assoc()) {
        $time_month = date('n', strtotime($time['mr_date']));

        // $time_count 배열에 $time_month 키가 없으면 초기화
        if (!isset($time_count[$time_month])) {
            $time_count[$time_month] = ['min' => 0, 'hour' => 0];
        }

        $time_count[$time_month]['min'] += (int)$time['mr_min'];
        $time_count[$time_month]['hour'] += (int)$time['mr_hour'];
    }
} else {
    $time_count[$month] = ['min' => 0, 'hour' => 0];
}

$time_array = array();
$min_sum = '0';
$sum_hour = '0';
$total_min = '0';
$total_hour = '0';
$length = count($time_count);
foreach ($time_count as $time_key => $time_value) {
  if(!isset($time_value['min'])) $time_value['min'] = '0';
  if(!isset($time_value['hour'])) $time_value['hour'] = '0';
  $min_sum = $time_value['min']%60;
  $time_div = floor($time_value['min']/60);
  $time_value['hour'] += $time_div;
  if(isset($year_month_check) && $year_month_check == 'old'){ //지난 봉사 연도 출력인지 여부
    $time_array[] = '[\''.$time_key.'월\', '.$time_value['hour'].', \'#6390d8\', \''.$time_value['hour'].'시간\']';
    $sum_hour += $time_value['hour'];
  }else{
    if($time_key == $month){
      $this_month = '[\''.$time_key.'월\', '.$time_value['hour'].', \'#6390d8\', \''.$time_value['hour'].'시간\']';
      $length--;
    }else{
      $time_array[] = '[\''.$time_key.'월\', '.$time_value['hour'].', \'#6390d8\', \''.$time_value['hour'].'시간\']';
      $sum_hour += $time_value['hour'];
    }
  }
  $total_min += $min_sum;
  $total_hour += $time_value['hour'];
}

if($length != 0){ $avg_hour = round($sum_hour/$length); }else{ $avg_hour = 0; }
$time_array[] = '[\'평균\', '.$avg_hour.', \'#6a63d8\', \''.$avg_hour.'시간\']';
if(isset($this_month)) $time_array[] = $this_month;
$time_array_count = count($time_array);
if($time_array_count < 4){
  $time_height = 150;
}elseif($time_array_count < 7){
  $time_height = 300;
}elseif($time_array_count < 10){
  $time_height = 450;
}else{
  $time_height = 600;
}

//총 봉사시간
$total_min_floor = floor($total_min/60);
$total_hour += $total_min_floor;


//모임 참여 일수
$mb_sql = "SELECT mb_name, mb_movein_date, mb_moveout_date FROM ".MEMBER_TABLE." WHERE mb_id = '{$mb_id}'";
$mb_result = $mysqli->query($mb_sql);
$mow = $mb_result->fetch_assoc();
if(isset($mow['mb_movein_date'])){ $movein_date = $mow['mb_movein_date']; }else{ $movein_date = ''; }
if(isset($mow['mb_moveout_date'])){ $moveout_date = $mow['mb_moveout_date']; }else{ $moveout_date = ''; }

if($yfday < $movein_date) $yfday = $movein_date;
if(!empty_date($moveout_date) && $ylday > $moveout_date) $ylday = $moveout_date;


/* 모임 참여 수 */

// 달별로 모임의 총 개수를 구함
$meetings_count = [];
for ($i = 1; $i <= 6; $i++) {
    $sql = "SELECT MONTH(m_date) AS month, COUNT(*) AS count 
            FROM " . MEETING_TABLE . " 
            WHERE m_date >= '{$yfday}' 
              AND m_date <= '{$ylday}' 
              AND m_cancle = '0' 
              AND ms_type = " . $i . " 
            GROUP BY MONTH(m_date) 
            ORDER BY MONTH(m_date)";

    $result = $mysqli->query($sql);
    $meetings_count[$i] = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (isset($row['month']) && isset($row['count'])) {
                $meetings_count[$i][$row['month']] = $row['count'];
            }
        }
    }
}

// 달별로 참석한 모임의 총 개수를 구함
$attend_count = [];
for ($i = 1; $i <= 6; $i++) {
    $sql = "SELECT MONTH(m_date) AS month, COUNT(*) AS count 
            FROM " . MEETING_TABLE . " 
            WHERE FIND_IN_SET('{$mb_id}', mb_id) 
              AND m_date >= '{$yfday}' 
              AND m_date <= '{$ylday}' 
              AND m_cancle = '0' 
              AND ms_type = " . $i . " 
            GROUP BY MONTH(m_date) 
            ORDER BY MONTH(m_date)";

    $result = $mysqli->query($sql);
    $attend_count[$i] = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (isset($row['month']) && isset($row['count'])) {
                $attend_count[$i][$row['month']] = $row['count'];
            }
        }
    }
}

// 봉사 모임 참석 수 차트의 데이터를 만듬
$attend_chart_data = array();
for ($i = 1; $i <= 6; $i++) {
    // $meetings_count와 $attend_count의 결과가 존재하는지 확인
    if (!empty($meetings_count[$i]) && is_array($meetings_count[$i])) {
        $m_count_array = $meetings_count[$i];
        $a_count_array = !empty($attend_count[$i]) && is_array($attend_count[$i]) ? $attend_count[$i] : [];

        foreach ($m_count_array as $month => $count) {
            $a_count = isset($a_count_array[$month]) ? $a_count_array[$month] : 0;
            $attend_chart_data[$i][] = '[\'' . $month . '월\',' . $a_count . ',' . $a_count . ',' . $count . ']';
        }
    }
}

// 차트 높이 설정
$meeting_chart_height = array();
for ($i = 1; $i <= 6; $i++) {
    if (!empty($meetings_count[$i]) && is_array($meetings_count[$i])) {
        $count = count($meetings_count[$i]);
        if ($count < 4) {
            $meeting_chart_height[$i] = 150;
        } elseif ($count < 7) {
            $meeting_chart_height[$i] = 300;
        } elseif ($count < 10) {
            $meeting_chart_height[$i] = 450;
        } else {
            $meeting_chart_height[$i] = 600;
        }
    } else {
        $meeting_chart_height[$i] = 150;
    }
}

?>

<script type="text/javascript">
  google.charts.load('current', {packages: ['corechart', 'bar']});
  google.charts.setOnLoadCallback(minister_time_Charts);

  function minister_time_Charts() {
    var data = google.visualization.arrayToDataTable([
      ['월', '봉사 시간', { role: 'style' }, {role: 'annotation'}],
      <?php echo implode(",",$time_array);?>
    ]);

    var options = {
      title: '총 <?=$total_hour?>시간',
      titleTextStyle: {
        color : '#2e2e33',
        fontSize: 16
      },
      colors: ['#6390d8'],
      chartArea: {
        top: 20,
        right: 20,
        width: '85%'
      },
      tooltip: {
        textStyle:{
          fontSize:14
        }
      },
      hAxis: {
        minValue: 0,
        maxValue: 50,
        textStyle: {
          fontSize: 12
        }
      },
      vAxis: {
        baselineColor: '#DDD',
        textStyle: {
          fontSize: 12
        }
      },
      legend: {
        position: 'none'
      },
      isStacked: 'true',
      height: <?=$time_height?>,
      bar: {groupWidth: "60%"},
      animation: {
        duration: 1200,
        easing: 'out',
        startup: true
      }
    };
    var chart = new google.visualization.BarChart(document.getElementById('minister_time_Charts'));
    chart.draw(data, options);
  }

  <?php
  $c_meeting_schedule_type_use = unserialize(MEETING_SCHEDULE_TYPE_USE);
  for ($i = 1; $i <= 6; $i++) {
    if(!isset($c_meeting_schedule_type_use[$i]) || $c_meeting_schedule_type_use[$i] === 'use'){
      ?>
      google.charts.setOnLoadCallback(meeting_Charts_type<?=$i?>);
      function meeting_Charts_type<?=$i?>() {
        var data = google.visualization.arrayToDataTable([
          ['date', '참여', {role: 'annotation'}, '미참여'],
          <?php if(isset($attend_chart_data[$i])){ echo implode(",",$attend_chart_data[$i]); }else{ echo '[]';} ?>
        ]);

        var options = {
          title: '<?=get_meeting_schedule_type_text($i)?>',
          titleTextStyle: {
            color : '#2e2e33',
            fontSize: 15
          },
          colors: [ "#6390d8", "#e9ecef"],
          chartArea: {
            right: 20,
            width: '85%'
          },
          height: <?=$meeting_chart_height[$i]?>,
          legend: {
            position: 'none'
          },
          isStacked: 'percent',
          bar: {groupWidth: "70%"},
          animation: {
            duration: 1200,
            easing: 'out',
            startup: true
          }
        };
        var chart = new google.visualization.BarChart(document.getElementById('minister_day_chart<?=$i?>'));
        chart.draw(data, options);
      }
      <?php
    }
  }
  ?>

</script>

<?php if(MINISTER_SCHEDULE_REPORT_USE == 'use'): ?>
<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">봉사 시간</span></h5>
<div id="minister_time_Charts"></div>
<?php endif; ?>

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">모임 참여 수</span></h5>
<div id="minister_day_chart1"></div>
<div id="minister_day_chart2"></div>
<div id="minister_day_chart3"></div>
<div id="minister_day_chart4"></div>
<div id="minister_day_chart5"></div>
<div id="minister_day_chart6"></div>
