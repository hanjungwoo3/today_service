<?php include_once('../config.php'); ?>

<?php
//구역카드 total
$total = 0;
$sum = array();
$sub_sum = array();
$type = array();
$type_sum = array();
$week = array();
$meeting_array = array();
$ms_meeting = array();

$sql = "SELECT tt_type, mb_id FROM " . TERRITORY_TABLE . " WHERE tt_type != '편지' ORDER BY FIELD(tt_type, '일반', '아파트', '빌라', '격지', '추가1', '추가2')";
$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
  $total++;
  if (isset($sum[$row['tt_type']])) {
    $sum[$row['tt_type']]++;
  } else {
    $sum[$row['tt_type']] = 1;
  }
  if ($row['mb_id'] == '0' && isset($sub_sum[$row['tt_type']]))
    $sub_sum[$row['tt_type']]++;
}

//요일별 구역타입 비율 (1.요일, 2.기타, 3.개인, 4.미배정)
$sql = "SELECT * FROM (SELECT
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '일반' AND ms_week = '1' AND mb_id = '0') s1,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '일반' AND ms_week = '2' AND mb_id = '0') s2,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '일반' AND ms_week = '3' AND mb_id = '0') s3,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '일반' AND ms_week = '4' AND mb_id = '0') s4,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '일반' AND ms_week = '5' AND mb_id = '0') s5,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '일반' AND ms_week = '6' AND mb_id = '0') s6,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '일반' AND ms_week = '7' AND mb_id = '0') s7,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id != '0' AND tt_type = '일반' AND mb_id = '0') s8,
      (SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE tt_type = '일반' AND mb_id != '0') s9,
      (SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE ms_id = '0' AND tt_type = '일반' AND mb_id = '0') s10,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '아파트' AND ms_week = '1' AND mb_id = '0') s11,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '아파트' AND ms_week = '2' AND mb_id = '0') s12,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '아파트' AND ms_week = '3' AND mb_id = '0') s13,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '아파트' AND ms_week = '4' AND mb_id = '0') s14,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '아파트' AND ms_week = '5' AND mb_id = '0') s15,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '아파트' AND ms_week = '6' AND mb_id = '0') s16,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '아파트' AND ms_week = '7' AND mb_id = '0') s17,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id != '0' AND tt_type = '아파트' AND mb_id = '0') s18,
      (SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE tt_type = '아파트' AND mb_id != '0') s19,
      (SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE ms_id = '0' AND tt_type = '아파트' AND mb_id = '0') s20,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '빌라' AND ms_week = '1' AND mb_id = '0') s21,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '빌라' AND ms_week = '2' AND mb_id = '0') s22,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '빌라' AND ms_week = '3' AND mb_id = '0') s23,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '빌라' AND ms_week = '4' AND mb_id = '0') s24,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '빌라' AND ms_week = '5' AND mb_id = '0') s25,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '빌라' AND ms_week = '6' AND mb_id = '0') s26,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '빌라' AND ms_week = '7' AND mb_id = '0') s27,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id != '0' AND tt_type = '빌라' AND mb_id = '0') s28,
      (SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE tt_type = '빌라' AND mb_id != '0') s29,
      (SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE ms_id = '0' AND tt_type = '빌라' AND mb_id = '0') s30,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '격지' AND ms_week = '1' AND mb_id = '0') s31,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '격지' AND ms_week = '2' AND mb_id = '0') s32,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '격지' AND ms_week = '3' AND mb_id = '0') s33,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '격지' AND ms_week = '4' AND mb_id = '0') s34,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '격지' AND ms_week = '5' AND mb_id = '0') s35,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '격지' AND ms_week = '6' AND mb_id = '0') s36,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '격지' AND ms_week = '7' AND mb_id = '0') s37,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id != '0' AND tt_type = '격지' AND mb_id = '0') s38,
      (SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE tt_type = '격지' AND mb_id != '0') s39,
      (SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE ms_id = '0' AND tt_type = '격지' AND mb_id = '0') s40,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가1' AND ms_week = '1' AND mb_id = '0') s41,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가1' AND ms_week = '2' AND mb_id = '0') s42,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가1' AND ms_week = '3' AND mb_id = '0') s43,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가1' AND ms_week = '4' AND mb_id = '0') s44,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가1' AND ms_week = '5' AND mb_id = '0') s45,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가1' AND ms_week = '6' AND mb_id = '0') s46,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가1' AND ms_week = '7' AND mb_id = '0') s47,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id != '0' AND tt_type = '추가1' AND mb_id = '0') s48,
      (SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE tt_type = '추가1' AND mb_id != '0') s49,
      (SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE ms_id = '0' AND tt_type = '추가1' AND mb_id = '0') s50,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가2' AND ms_week = '1' AND mb_id = '0') s51,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가2' AND ms_week = '2' AND mb_id = '0') s52,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가2' AND ms_week = '3' AND mb_id = '0') s53,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가2' AND ms_week = '4' AND mb_id = '0') s54,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가2' AND ms_week = '5' AND mb_id = '0') s55,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가2' AND ms_week = '6' AND mb_id = '0') s56,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '추가2' AND ms_week = '7' AND mb_id = '0') s57,
      (SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id != '0' AND tt_type = '추가2' AND mb_id = '0') s58,
      (SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE tt_type = '추가2' AND mb_id != '0') s59,
      (SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE ms_id = '0' AND tt_type = '추가2' AND mb_id = '0') s60) T;";
$result = $mysqli->query($sql);
$week_territory = $result->fetch_assoc();

//구역타입별 진행률 (1.미사용, 2.미완료, 3.완료)
$sql = "SELECT tt_type, tt_assigned, tt_start_date, tt_end_date FROM " . TERRITORY_TABLE . " WHERE mb_id = '0' AND tt_type != '편지' ORDER BY FIELD(tt_type, '일반', '아파트', '빌라', '격지', '추가1', '추가2')";
$result = $mysqli->query($sql);
while ($ty = $result->fetch_assoc()) {
  if (isset($type[$ty['tt_type']][0])) {
    $type[$ty['tt_type']][0]++;
  } else {
    $type[$ty['tt_type']][0] = 1;
  }
  if (isset($type_sum[0])) {
    $type_sum[0]++;
  } else {
    $type_sum[0] = 1;
  }

  if ($ty['tt_assigned'] || !empty_date($ty['tt_start_date'])) {
    // 배정됨 (Active)
    if (!empty_date($ty['tt_end_date'])) {
      // 완료 (3)
      $status_key = 3;
    } else {
      // 미완료 (2)
      $status_key = 2;
    }
  } else {
    // 미사용 (1)
    $status_key = 1;
  }

  if (isset($type[$ty['tt_type']][$status_key])) {
    $type[$ty['tt_type']][$status_key]++;
  } else {
    $type[$ty['tt_type']][$status_key] = 1;
  }
  if (isset($type_sum[$status_key])) {
    $type_sum[$status_key]++;
  } else {
    $type_sum[$status_key] = 1;
  }
}

//구역카드 요일별 진행률 (1.미사용, 2.미완료, 3.완료)
$sql = "SELECT ms_week, tt_assigned, tt_start_date, tt_end_date FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND mb_id = '0' AND tt_type != '편지'";
$result = $mysqli->query($sql);
while ($we = $result->fetch_assoc()) {
  if (isset($week[$we['ms_week']][0])) {
    $week[$we['ms_week']][0]++;
  } else {
    $week[$we['ms_week']][0] = 1;
  }

  if ($we['tt_assigned'] || !empty_date($we['tt_start_date'])) {
    // 배정됨 (Active)
    if (!empty_date($we['tt_end_date'])) {
      // 완료 (3)
      $status_key = 3;
    } else {
      // 미완료 (2)
      $status_key = 2;
    }
  } else {
    // 미사용 (1)
    $status_key = 1;
  }

  if (isset($week[$we['ms_week']][$status_key])) {
    $week[$we['ms_week']][$status_key]++;
  } else {
    $week[$we['ms_week']][$status_key] = 1;
  }
}

//구역카드 모임별 진행률
$m_sql = "SELECT ms.ms_id as ms_id, tt_assigned, tt_start_date, tt_end_date FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id != '0' AND mb_id = '0' AND tt_type != '편지'";
$m_result = $mysqli->query($m_sql);
while ($mow = $m_result->fetch_assoc()) {
  if (isset($meeting_array[$mow['ms_id']][0])) {
    $meeting_array[$mow['ms_id']][0]++;
  } else {
    $meeting_array[$mow['ms_id']][0] = 1;
  }

  if ($mow['tt_assigned'] || !empty_date($mow['tt_start_date'])) {
    // 배정됨 (Active)
    if (!empty_date($mow['tt_end_date'])) {
      // 완료 (3)
      $status_key = 3;
    } else {
      // 미완료 (2)
      $status_key = 2;
    }
  } else {
    // 미사용 (1)
    $status_key = 1;
  }

  if (isset($meeting_array[$mow['ms_id']][$status_key])) {
    $meeting_array[$mow['ms_id']][$status_key]++;
  } else {
    $meeting_array[$mow['ms_id']][$status_key] = 1;
  }
}

foreach ($meeting_array as $ms_id => $territory_con) {
  $ms_sql = "SELECT * FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . MEETING_PLACE_TABLE . " mp ON ms.mp_id = mp.mp_id LEFT JOIN " . GROUP_TABLE . " g ON ms.g_id = g.g_id
            WHERE ms_id = '{$ms_id}' ORDER BY ms_week, ms_time, g_name, mp_name ASC";
  $ms_result = $mysqli->query($ms_sql);
  $msw = $ms_result->fetch_assoc();

  if (empty($territory_con[3]))
    $territory_con[3] = 0;
  if (empty($territory_con[2]))
    $territory_con[2] = 0;
  if (empty($territory_con[1]))
    $territory_con[1] = 0;
  if (empty($territory_con[0]))
    $territory_con[0] = 0;

  $meeting[$msw['ms_week']][] = array(
    'name' => '(' . get_week_text($msw['ms_week']) . ') ' . get_meeting_data_text($msw['ms_time'], $msw['g_name'], $msw['mp_name']),
    's3' => $territory_con[3],
    's2' => $territory_con[2],
    's1' => $territory_con[1],
    's0' => $territory_con[0]
  );
}
if (!empty($meeting)) {
  ksort($meeting);
  foreach ($meeting as $ms_week => $string) {
    $count_length = count($string);
    for ($i = 0; $i < $count_length; $i++)
      $ms_meeting[] = $string[$i];
  }
}

if (empty($meeting_array))
  $ms_meeting[] = array('name' => '전체', 's3' => 0, 's2' => 0, 's1' => 0, 's0' => 0);
?>
<script type="text/javascript">
  google.charts.load('current', { packages: ['corechart', 'bar'] });
  google.charts.setOnLoadCallback(territory_type_chart);
  google.charts.setOnLoadCallback(week_territory_type_chart);

  function territory_type_chart() {

    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Topping');
    data.addColumn('number', 'Slices');
    data.addRows([
      <?php
      foreach ($sum as $key => $value)
        $sum_arr[] = '[\'' . get_type_text($key) . '\', ' . $value . ']';
      echo implode(",", $sum_arr);
      ?>
    ]);

    var options = {
      colors: ["#5bb4de", "#e4927a", "#d378e0", "#e1e65f", "#63de5b", "#6f5bde"],
      title: '구역 종류',
      titleTextStyle: {
        fontSize: 15
      },
      tooltip: {
        textStyle: {
          fontSize: 14
        }
      },
      height: 300,
      legend: {
        textStyle: {
          fontSize: 12
        }
      },
      pieStartAngle: 100,
      sliceVisibilityThreshold: 0
    };

    var chart = new google.visualization.PieChart(document.getElementById('territory_type_chart'));
    chart.draw(data, options);
  }

  function week_territory_type_chart() {
    var data = google.visualization.arrayToDataTable([
      ['type', '<?= get_type_text('일반') ?>', '<?= get_type_text('아파트') ?>', '<?= get_type_text('빌라') ?>', '<?= get_type_text('격지') ?>', '<?= get_type_text('추가1') ?>', '<?= get_type_text('추가2') ?>'],
      ['월', <?= $week_territory['s1'] ?>, <?= $week_territory['s11'] ?>, <?= $week_territory['s21'] ?>, <?= $week_territory['s31'] ?>, <?= $week_territory['s41'] ?>, <?= $week_territory['s51'] ?>],
      ['화', <?= $week_territory['s2'] ?>, <?= $week_territory['s12'] ?>, <?= $week_territory['s22'] ?>, <?= $week_territory['s32'] ?>, <?= $week_territory['s42'] ?>, <?= $week_territory['s52'] ?>],
      ['수', <?= $week_territory['s3'] ?>, <?= $week_territory['s13'] ?>, <?= $week_territory['s23'] ?>, <?= $week_territory['s33'] ?>, <?= $week_territory['s43'] ?>, <?= $week_territory['s53'] ?>],
      ['목', <?= $week_territory['s4'] ?>, <?= $week_territory['s14'] ?>, <?= $week_territory['s24'] ?>, <?= $week_territory['s34'] ?>, <?= $week_territory['s44'] ?>, <?= $week_territory['s54'] ?>],
      ['금', <?= $week_territory['s5'] ?>, <?= $week_territory['s15'] ?>, <?= $week_territory['s25'] ?>, <?= $week_territory['s35'] ?>, <?= $week_territory['s45'] ?>, <?= $week_territory['s55'] ?>],
      ['토', <?= $week_territory['s6'] ?>, <?= $week_territory['s16'] ?>, <?= $week_territory['s26'] ?>, <?= $week_territory['s36'] ?>, <?= $week_territory['s46'] ?>, <?= $week_territory['s56'] ?>],
      ['일', <?= $week_territory['s7'] ?>, <?= $week_territory['s17'] ?>, <?= $week_territory['s27'] ?>, <?= $week_territory['s37'] ?>, <?= $week_territory['s47'] ?>, <?= $week_territory['s57'] ?>],
      ['기타', <?= $week_territory['s8'] ?>, <?= $week_territory['s18'] ?>, <?= $week_territory['s28'] ?>, <?= $week_territory['s38'] ?>, <?= $week_territory['s48'] ?>, <?= $week_territory['s58'] ?>],
      ['개인', <?= $week_territory['s9'] ?>, <?= $week_territory['s19'] ?>, <?= $week_territory['s29'] ?>, <?= $week_territory['s39'] ?>, <?= $week_territory['s49'] ?>, <?= $week_territory['s59'] ?>],
      ['미배정', <?= $week_territory['s10'] ?>, <?= $week_territory['s20'] ?>, <?= $week_territory['s30'] ?>, <?= $week_territory['s40'] ?>, <?= $week_territory['s50'] ?>, <?= $week_territory['s60'] ?>]
    ]);

    var options = {
      colors: ["#5bb4de", "#e4927a", "#d378e0", "#e1e65f", "#63de5b", "#6f5bde"],
      chartArea: {
        top: 20,
        right: 20,
        width: '85%'
      },
      legend: {
        position: 'bottom',
        textStyle: {
          fontSize: 12
        }
      },
      isStacked: true,
      height: 500,
      bar: { groupWidth: "65%" },
      animation: {
        duration: 1200,
        easing: 'out',
        startup: true
      }
    };
    var chart = new google.visualization.BarChart(document.getElementById('week_territory_type_chart'));
    chart.draw(data, options);
  }
</script>

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">일반 구역 현황</span><small
    class="mt-2 float-right">전체 구역 : <?= $total ?>개</small></h5>
<div id="territory_type_chart"></div>

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">요일별 구역 형태 비율</span>
</h5>

<div id="week_territory_type_chart"></div>

<div class="row">
  <div class="col-lg-6">
    <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">구역 형태별 진행률</span>
    </h5>

    <table class="table table-bordered mb-5">
      <colgroup>
        <col style="width:100px;">
      </colgroup>
      <thead class="thead-light text-center">
        <tr>
          <th scope="col">구분</th>
          <th scope="col">완료</th>
          <th scope="col">미완료</th>
          <th scope="col">미사용</th>
        </tr>
      </thead>
      <tbody class="text-center">
        <tr>
          <th scope="row" class="bg-light align-middle">
            <div>전체</div>
            <?php $type_sum_total_count = !empty($type_sum[0]) ? $type_sum[0] : 0; ?>
            <small class="text-muted">(<?= $type_sum_total_count; ?>개)</small>
          </th>
          <?php for ($i = 3; $i > 0; $i--):
            $type_sum_count = !empty($type_sum[$i]) ? $type_sum[$i] : 0;
            ?>
            <td>
              <div><?= $type_sum_count; ?>개</div>
              <small class="text-muted">(<?= get_percent($type_sum_count, $type_sum_total_count) . '%'; ?>)</small>
            </td>
          <?php endfor; ?>
        </tr>
        <?php foreach ($type as $key => $value):
          $type_total_count = ($value[0]) ? $value[0] : 0; ?>
          <tr>
            <th scope="row" class="bg-light align-middle">
              <div><?= get_type_text($key) ?></div>
              <small class="text-muted">(<?= $type_total_count; ?>개)</small>
            </th>
            <?php for ($i = 3; $i > 0; $i--):
              $type_count = !empty($value[$i]) ? $value[$i] : 0; ?>
              <td>
                <div><?= $type_count; ?>개</div>
                <small class="text-muted">(<?= get_percent($type_count, $type_total_count) . '%'; ?>)</small>
              </td>
            <?php endfor; ?>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <div class="col-lg-6">
    <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">요일별 구역 진행률</span>
    </h5>

    <table class="table table-bordered mb-5">
      <colgroup>
        <col style="width:70px;">
      </colgroup>
      <thead class="thead-light text-center">
        <tr>
          <th scope="col">구분</th>
          <th scope="col">완료</th>
          <th scope="col">미완료</th>
          <th scope="col">미사용</th>
        </tr>
      </thead>
      <tbody class="text-center">
        <?php for ($k = 1; $k < 8; $k++):
          $week_total_count = !empty($week[$k][0]) ? $week[$k][0] : 0;
          ?>
          <tr>
            <th scope="row" class="bg-light align-middle">
              <div><?= get_week_text($k); ?></div>
              <small class="text-muted">(<?= $week_total_count; ?>개)</small>
            </th>
            <?php for ($i = 3; $i > 0; $i--):
              $week_count = !empty($week[$k][$i]) ? $week[$k][$i] : 0;
              ?>
              <td>
                <div><?= $week_count ?>개</div>
                <small class="text-muted">(<?= get_percent($week_count, $week_total_count) . '%'; ?>)</small>
              </td>
            <?php endfor; ?>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>
</div>

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">기타 구역 진행률</span></h5>

<table class="table table-bordered mb-5">
  <colgroup>
    <col style="width:33.33%" span="3">
  </colgroup>
  <thead class="thead-light text-center">
    <tr>
      <th scope="col">완료</th>
      <th scope="col">미완료</th>
      <th scope="col">미사용</th>
    </tr>
  </thead>
  <tbody class="text-center">
    <?php foreach ($ms_meeting as $key => $value): ?>
      <tr>
        <th class="bg-light text-left p-1" colspan="4">
          <div><?= $value['name']; ?><small class="text-muted ml-2">(<?= $value['s0']; ?>개)</small></div>
        </th>
      </tr>
      <tr>
        <td class="align-middle">
          <div><?= $value['s3']; ?>개</div>
          <small class="text-muted">(<?= get_percent($value['s3'], $value['s0']) . '%'; ?>)</small>
        </td>
        <td class="align-middle">
          <div><?= $value['s2']; ?>개</div>
          <small class="text-muted">(<?= get_percent($value['s2'], $value['s0']) . '%'; ?>)</small>
        </td>
        <td class="align-middle">
          <div><?= $value['s1']; ?>개</div>
          <small class="text-muted">(<?= get_percent($value['s1'], $value['s0']) . '%'; ?>)</small>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">기간별 구역 진행률</span></h5>

<form method="post" url="statistics_territory_past">
  <div class="form-group row m-0 mb-1 align-items-center">
    <label class="col-4 p-0 m-0">기간</label>
    <div class="col-8 p-0"><input class="form-control w-100" type="date" name="date" value="<?= date("Y-m-d") ?>"
        min="2018-08-22" max="<?= date("Y-m-d") ?>" /></div>
  </div>
  <div class="form-group row m-0 mb-3 align-items-center">
    <label class="col-4 p-0 m-0 text-center">~</label>
    <div class="col-8 p-0"><input class="form-control w-100" type="date" name="date2" value="<?= date("Y-m-d") ?>"
        min="2018-08-22" max="<?= date("Y-m-d") ?>" /></div>
  </div>
  <div class="col-md-auto mb-2">
    <button type="submit" class="btn btn-outline-secondary float-right"><i class="bi bi-search"></i> 검색</button>
  </div>
</form>
<div id="statistics_territory_past"></div>