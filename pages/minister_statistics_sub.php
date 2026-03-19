<?php include_once('../config.php'); ?>

<?php
$mb_id = mb_id();
$month = date("n");
$first_d = mktime(0, 0, 0, date("m"), 1, date("Y"));
$hm = array();
$hm_count = array();
$time_count = array();

if ($month >= 9) {
  $year = date("Y", strtotime("+1 year", $first_d));
  $yfday = date("Y-09-01");
} elseif ($month < 9) {
  $year = date("Y");
  $yfday = date("Y-09-01", strtotime("-1 year", $first_d));
}

$ylday = date("Y-m-d");

if (!empty($st_year)) {
  $year_month_check = 'old'; //지난 봉사 연도 출력인지 여부
} else {
  $st_year = $year;
}
if ($st_year != $year) {
  $last_d = mktime(0, 0, 0, 1, 1, $st_year);
  $yfday = date("Y-09-01", strtotime("-1 year", $last_d)); // 시작 날
  $ylday = date("Y-08-31", $last_d); // 마지막 날
}


//봉사시간
$t_sql = "SELECT * FROM " . MINISTER_REPORT_TABLE . " WHERE mr_date >= '{$yfday}' AND mr_date <= '{$ylday}' AND mb_id = '{$mb_id}' ORDER BY mr_date";
$t_result = $mysqli->query($t_sql);
if ($t_result && $t_result->num_rows > 0) {
  while ($time = $t_result->fetch_assoc()) {
    $time_month = date('n', strtotime($time['mr_date']));

    // $time_count 배열에 $time_month 키가 없으면 초기화
    if (!isset($time_count[$time_month])) {
      $time_count[$time_month] = ['min' => 0, 'hour' => 0];
    }

    $time_count[$time_month]['min'] += (int) $time['mr_min'];
    $time_count[$time_month]['hour'] += (int) $time['mr_hour'];
  }
} else {
  $time_count[$month] = ['min' => 0, 'hour' => 0];
}

// 봉사 연도 월 순서 (9월 ~ 다음해 8월)
$month_order = [9, 10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8];

// 봉사시간 집계 (정렬 순서 보장)
$time_array = array();
$total_min = 0;
$total_hour = 0;
$sum_hour_for_avg = 0;
$actual_month_count = 0;
$this_month_data = null;

foreach ($month_order as $m) {
  // 검색 기간(yfday ~ ylday)에 속하는 달인지 확인
  $m_yfday = (int) date('n', strtotime($yfday));
  $m_ylday = (int) date('n', strtotime($ylday));

  // 현재 루프의 달($m)이 통계 범위 내에 있는지 체크 (간단히 mr_date 결과가 있는 달만 해도 되지만, 0이라도 표시하기 위해)
  $h = isset($time_count[$m]['hour']) ? $time_count[$m]['hour'] : 0;
  $mi = isset($time_count[$m]['min']) ? $time_count[$m]['min'] : 0;

  // 시간 계산
  $total_hour_from_min = floor($mi / 60);
  $final_hour = $h + $total_hour_from_min;
  $final_min = $mi % 60;

  $total_min += $final_min;
  $total_hour += $final_hour;

  // 차트 데이터 (해당 연도/월에 데이터가 있거나 현재 연도 통계인 경우)
  if (isset($time_count[$m]) || (!isset($year_month_check) || $year_month_check != 'old')) {
    $data_str = "['{$m}월', {$final_hour}, '#6390d8', '{$final_hour}시간']";

    if (!isset($year_month_check) || $year_month_check != 'old') {
      if ($m == $month) {
        $this_month_data = $data_str;
      } else {
        $time_array[] = $data_str;
        $sum_hour_for_avg += $final_hour;
        $actual_month_count++;
      }
    } else {
      $time_array[] = $data_str;
      $sum_hour_for_avg += $final_hour;
      $actual_month_count++;
    }
  }
}

$total_hour += floor($total_min / 60);
$avg_hour = ($actual_month_count > 0) ? round($sum_hour_for_avg / $actual_month_count) : 0;
$time_array[] = "['평균', {$avg_hour}, '#6a63d8', '{$avg_hour}시간']";
if ($this_month_data)
  $time_array[] = $this_month_data;

$time_height = (count($time_array) < 4) ? 150 : ((count($time_array) < 7) ? 300 : ((count($time_array) < 10) ? 450 : 600));


//모임 참여 일수
$mb_sql = "SELECT mb_name, mb_movein_date, mb_moveout_date FROM " . MEMBER_TABLE . " WHERE mb_id = '{$mb_id}'";
$mb_result = $mysqli->query($mb_sql);
$mow = $mb_result->fetch_assoc();
if (isset($mow['mb_movein_date'])) {
  $movein_date = $mow['mb_movein_date'];
} else {
  $movein_date = '';
}
if (isset($mow['mb_moveout_date'])) {
  $moveout_date = $mow['mb_moveout_date'];
} else {
  $moveout_date = '';
}

if ($yfday < $movein_date)
  $yfday = $movein_date;
if (!empty_date($moveout_date) && $ylday > $moveout_date)
  $ylday = $moveout_date;


/* 모임 참여 수 */

// 달별로 모임의 총 개수 및 참석 개수를 구함
$meetings_count = [];
$attend_count = [];
for ($i = 1; $i <= 6; $i++) {
  $meetings_count[$i] = [];
  $attend_count[$i] = [];

  // 전체 모임
  $sql = "SELECT MONTH(m_date) AS month, COUNT(*) AS count FROM " . MEETING_TABLE . " WHERE m_date >= '{$yfday}' AND m_date <= '{$ylday}' AND m_cancle = '0' AND ms_type = " . $i . " GROUP BY MONTH(m_date)";
  $result = $mysqli->query($sql);
  while ($row = $result->fetch_assoc())
    $meetings_count[$i][$row['month']] = $row['count'];

  // 참석한 모임
  $sql = "SELECT MONTH(m_date) AS month, COUNT(*) AS count FROM " . MEETING_TABLE . " WHERE FIND_IN_SET('{$mb_id}', mb_id) AND m_date >= '{$yfday}' AND m_date <= '{$ylday}' AND m_cancle = '0' AND ms_type = " . $i . " GROUP BY MONTH(m_date)";
  $result = $mysqli->query($sql);
  while ($row = $result->fetch_assoc())
    $attend_count[$i][$row['month']] = $row['count'];
}

// 봉사 모임 참석 수 차트의 데이터를 만듬 (9월부터 정렬)
$attend_chart_data = array();
$meeting_chart_height = array();

for ($i = 1; $i <= 6; $i++) {
  $attend_chart_data[$i] = [];
  $has_data = false;

  foreach ($month_order as $m) {
    if (isset($meetings_count[$i][$m])) {
      $count = $meetings_count[$i][$m];
      $a_count = isset($attend_count[$i][$m]) ? $attend_count[$i][$m] : 0;
      $not_attend = $count - $a_count;
      // Google Charts isStacked: 'percent' 모드에서는 [참석, 참여_라벨, 미참석] 순으로 전달해야 함
      $attend_chart_data[$i][] = "['{$m}월', {$a_count}, '{$a_count}', {$not_attend}]";
      $has_data = true;
    }
  }

  if ($has_data) {
    $count = count($attend_chart_data[$i]);
    $meeting_chart_height[$i] = ($count < 4) ? 150 : (($count < 7) ? 300 : (($count < 10) ? 450 : 600));
  } else {
    $meeting_chart_height[$i] = 150;
  }
}

?>

<script type="text/javascript">
  google.charts.load('current', { packages: ['corechart', 'bar'] });
  google.charts.setOnLoadCallback(minister_time_Charts);

  function minister_time_Charts() {
    var data = google.visualization.arrayToDataTable([
      ['월', '봉사 시간', { role: 'style' }, { role: 'annotation' }],
      <?php echo implode(",", $time_array); ?>
    ]);

    var options = {
      title: '총 <?= $total_hour ?>시간',
      titleTextStyle: {
        color: '#2e2e33',
        fontSize: 16
      },
      colors: ['#6390d8'],
      chartArea: {
        top: 20,
        right: 20,
        width: '85%'
      },
      tooltip: {
        textStyle: {
          fontSize: 14
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
      height: <?= $time_height ?>,
      bar: { groupWidth: "60%" },
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
    // 데이터가 있고 사용 설정된 유형만 차트 생성
    if (!empty($attend_chart_data[$i]) && (!isset($c_meeting_schedule_type_use[$i]) || $c_meeting_schedule_type_use[$i] === 'use')) {
      ?>
      google.charts.setOnLoadCallback(meeting_Charts_type<?= $i ?>);
      function meeting_Charts_type<?= $i ?>() {
        var data = google.visualization.arrayToDataTable([
          ['date', '참여', { role: 'annotation' }, '미참여'],
          <?php echo implode(",", $attend_chart_data[$i]); ?>
        ]);

        var options = {
          title: '<?= get_meeting_schedule_type_text($i) ?>',
          titleTextStyle: {
            color: '#2e2e33',
            fontSize: 15
          },
          colors: ["#6390d8", "#e9ecef"],
          chartArea: {
            right: 20,
            width: '85%'
          },
          height: <?= (int) $meeting_chart_height[$i] ?>,
          legend: {
            position: 'none'
          },
          isStacked: 'true',
          bar: { groupWidth: "70%" },
          animation: {
            duration: 1200,
            easing: 'out',
            startup: true
          }
        };
        var chartIdx = 'minister_day_chart<?= $i ?>';
        var chartDiv = document.getElementById(chartIdx);
        if (chartDiv) {
          var chart = new google.visualization.BarChart(chartDiv);
          chart.draw(data, options);
        }
      }
      <?php
    }
  }
  ?>

</script>

<?php if (MINISTER_SCHEDULE_REPORT_USE == 'use'): ?>
  <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">봉사 시간</span></h5>
  <div id="minister_time_Charts"></div>
<?php endif; ?>

<?php
// 데이터가 있는 모임 차트 영역만 출력
$has_any_meeting_data = false;
for ($i = 1; $i <= 6; $i++) {
  if (!empty($attend_chart_data[$i])) {
    if (!$has_any_meeting_data) {
      echo '<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">모임 참여 수</span></h5>';
      $has_any_meeting_data = true;
    }
    echo '<div id="minister_day_chart' . $i . '" class="mb-3"></div>';
  }
}
?>