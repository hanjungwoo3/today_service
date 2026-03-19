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
$types = array('일반', '아파트', '빌라', '격지', '추가1', '추가2');
$subqueries = array();
$idx = 1;
foreach ($types as $tt_type) {
  // 1-7. 요일별 분배 (특정 모임계획 ms_id != 0 AND ma_id == 0)
  for ($w = 1; $w <= 7; $w++) {
    $subqueries[] = "(SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND tt_type = '{$tt_type}' AND ms_week = '{$w}' AND mb_id = '0') s{$idx}";
    $idx++;
  }
  // 8. 기타 (ma_id != 0)
  $subqueries[] = "(SELECT count(DISTINCT tt_id) FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id != '0' AND tt_type = '{$tt_type}' AND mb_id = '0') s{$idx}";
  $idx++;
  // 9. 개인 (mb_id != 0)
  $subqueries[] = "(SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE tt_type = '{$tt_type}' AND mb_id != '0') s{$idx}";
  $idx++;
  // 10. 전체 분배 (tt_ms_all != 0)
  $subqueries[] = "(SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE tt_ms_all != '0' AND tt_type = '{$tt_type}' AND mb_id = '0') s{$idx}";
  $idx++;
  // 11. 미분배 (ms_id == 0 AND tt_ms_all == 0 AND mb_id == 0)
  $subqueries[] = "(SELECT count(DISTINCT tt_id) FROM " . TERRITORY_TABLE . " WHERE ms_id = '0' AND tt_ms_all = '0' AND tt_type = '{$tt_type}' AND mb_id = '0') s{$idx}";
  $idx++;
}
$sql = "SELECT * FROM (SELECT " . implode(", ", $subqueries) . ") T;";
$result = $mysqli->query($sql);
$week_territory = $result->fetch_assoc();

//구역타입별 진행률 (1.진행전, 2.진행중, 3.완료)
$type = array();
$type_sum = array();
$sql = "SELECT tt_id, tt_type FROM " . TERRITORY_TABLE . " WHERE mb_id = '0' AND tt_type != '편지' ORDER BY FIELD(tt_type, '일반', '아파트', '빌라', '격지', '추가1', '추가2')";
$result = $mysqli->query($sql);
while ($ty = $result->fetch_assoc()) {
  $records = get_all_past_records('territory', $ty['tt_id']);
  $recent = !empty($records) ? $records[0] : array('visit' => '전체', 'progress' => 'incomplete');

  $visit_mode = $recent['visit']; // "전체" or "부재"
  $prog_status = $recent['progress']; // "completed", "in_progress", "incomplete"

  // 상태 키 생성 (진행전:1, 진행중:2, 완료:3)
  $status_key = 1;
  if ($prog_status == 'completed')
    $status_key = 3;
  else if ($prog_status == 'in_progress')
    $status_key = 2;

  $group_key = $ty['tt_type'] . '|' . $visit_mode;

  if (isset($type[$group_key][0])) {
    $type[$group_key][0]++;
  } else {
    $type[$group_key][0] = 1;
  }
  if (isset($type_sum[0])) {
    $type_sum[0]++;
  } else {
    $type_sum[0] = 1;
  }

  if (isset($type[$group_key][$status_key])) {
    $type[$group_key][$status_key]++;
  } else {
    $type[$group_key][$status_key] = 1;
  }
  if (isset($type_sum[$status_key])) {
    $type_sum[$status_key]++;
  } else {
    $type_sum[$status_key] = 1;
  }
}

//구역카드 요일별 진행률 (1.진행전, 2.진행중, 3.완료)
$week_data = array();
$sql = "SELECT tt_id, ms_week FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND mb_id = '0' AND tt_type != '편지'";
$result = $mysqli->query($sql);
while ($we = $result->fetch_assoc()) {
  $records = get_all_past_records('territory', $we['tt_id']);
  $recent = !empty($records) ? $records[0] : array('visit' => '전체', 'progress' => 'incomplete');
  $visit_mode = $recent['visit'];
  $week_key = $we['ms_week'] . '|' . $visit_mode;

  if (!isset($week_data[$week_key][0]))
    $week_data[$week_key][0] = 0;
  $week_data[$week_key][0]++;

  $status_key = 1;
  if ($recent['progress'] == 'completed')
    $status_key = 3;
  else if ($recent['progress'] == 'in_progress')
    $status_key = 2;

  if (!isset($week_data[$week_key][$status_key]))
    $week_data[$week_key][$status_key] = 0;
  $week_data[$week_key][$status_key]++;
}

//구역카드 모임별 진행률
$meeting_data_agg = array();
$m_sql = "SELECT tt_id, ms.ms_id as ms_id FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . TERRITORY_TABLE . " tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id != '0' AND mb_id = '0' AND tt_type != '편지'";
$m_result = $mysqli->query($m_sql);
while ($mow = $m_result->fetch_assoc()) {
  $records = get_all_past_records('territory', $mow['tt_id']);
  $recent = !empty($records) ? $records[0] : array('visit' => '전체', 'progress' => 'incomplete');
  $visit_mode = $recent['visit'];
  $meeting_key = $mow['ms_id'] . '|' . $visit_mode;

  if (!isset($meeting_data_agg[$meeting_key][0]))
    $meeting_data_agg[$meeting_key][0] = 0;
  $meeting_data_agg[$meeting_key][0]++;

  $status_key = 1;
  if ($recent['progress'] == 'completed')
    $status_key = 3;
  else if ($recent['progress'] == 'in_progress')
    $status_key = 2;

  if (!isset($meeting_data_agg[$meeting_key][$status_key]))
    $meeting_data_agg[$meeting_key][$status_key] = 0;
  $meeting_data_agg[$meeting_key][$status_key]++;
}

foreach ($meeting_data_agg as $key => $territory_con) {
  $key_arr = explode('|', $key);
  $ms_id = $key_arr[0];
  $visit_mode = $key_arr[1];

  $ms_sql = "SELECT * FROM " . MEETING_SCHEDULE_TABLE . " ms INNER JOIN " . MEETING_PLACE_TABLE . " mp ON ms.mp_id = mp.mp_id LEFT JOIN " . GROUP_TABLE . " g ON ms.g_id = g.g_id
            WHERE ms_id = '{$ms_id}' ORDER BY ms_week, ms_time, g_name, mp_name ASC";
  $ms_result = $mysqli->query($ms_sql);
  $msw = $ms_result->fetch_assoc();

  $meeting[$msw['ms_week']][] = array(
    'name' => '(' . get_week_text($msw['ms_week']) . ') ' . get_meeting_data_text($msw['ms_time'], $msw['g_name'], $msw['mp_name']) . ' [' . $visit_mode . ']',
    's3' => !empty($territory_con[3]) ? $territory_con[3] : 0,
    's2' => !empty($territory_con[2]) ? $territory_con[2] : 0,
    's1' => !empty($territory_con[1]) ? $territory_con[1] : 0,
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

if (empty($meeting_data_agg))
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
      $sum_arr = array();
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
      ['월', <?= $week_territory['s1'] ?>, <?= $week_territory['s12'] ?>, <?= $week_territory['s23'] ?>, <?= $week_territory['s34'] ?>, <?= $week_territory['s45'] ?>, <?= $week_territory['s56'] ?>],
      ['화', <?= $week_territory['s2'] ?>, <?= $week_territory['s13'] ?>, <?= $week_territory['s24'] ?>, <?= $week_territory['s35'] ?>, <?= $week_territory['s46'] ?>, <?= $week_territory['s57'] ?>],
      ['수', <?= $week_territory['s3'] ?>, <?= $week_territory['s14'] ?>, <?= $week_territory['s25'] ?>, <?= $week_territory['s36'] ?>, <?= $week_territory['s47'] ?>, <?= $week_territory['s58'] ?>],
      ['목', <?= $week_territory['s4'] ?>, <?= $week_territory['s15'] ?>, <?= $week_territory['s26'] ?>, <?= $week_territory['s37'] ?>, <?= $week_territory['s48'] ?>, <?= $week_territory['s59'] ?>],
      ['금', <?= $week_territory['s5'] ?>, <?= $week_territory['s16'] ?>, <?= $week_territory['s27'] ?>, <?= $week_territory['s38'] ?>, <?= $week_territory['s49'] ?>, <?= $week_territory['s60'] ?>],
      ['토', <?= $week_territory['s6'] ?>, <?= $week_territory['s17'] ?>, <?= $week_territory['s28'] ?>, <?= $week_territory['s39'] ?>, <?= $week_territory['s50'] ?>, <?= $week_territory['s61'] ?>],
      ['일', <?= $week_territory['s7'] ?>, <?= $week_territory['s18'] ?>, <?= $week_territory['s29'] ?>, <?= $week_territory['s40'] ?>, <?= $week_territory['s51'] ?>, <?= $week_territory['s62'] ?>],
      ['기타', <?= $week_territory['s8'] ?>, <?= $week_territory['s19'] ?>, <?= $week_territory['s30'] ?>, <?= $week_territory['s41'] ?>, <?= $week_territory['s52'] ?>, <?= $week_territory['s63'] ?>],
      ['개인구역', <?= $week_territory['s9'] ?>, <?= $week_territory['s20'] ?>, <?= $week_territory['s31'] ?>, <?= $week_territory['s42'] ?>, <?= $week_territory['s53'] ?>, <?= $week_territory['s64'] ?>],
      ['전체', <?= $week_territory['s10'] ?>, <?= $week_territory['s21'] ?>, <?= $week_territory['s32'] ?>, <?= $week_territory['s43'] ?>, <?= $week_territory['s54'] ?>, <?= $week_territory['s65'] ?>],
      ['미분배', <?= $week_territory['s11'] ?>, <?= $week_territory['s22'] ?>, <?= $week_territory['s33'] ?>, <?= $week_territory['s44'] ?>, <?= $week_territory['s55'] ?>, <?= $week_territory['s66'] ?>]
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

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">요일별 구역 현황 (분배현황)</span>
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
          <th scope="col">진행중</th>
          <th scope="col">진행전</th>
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
          $type_total_count = ($value[0]) ? $value[0] : 0;
          $key_arr = explode('|', $key);
          $name = get_type_text($key_arr[0]) . ' [' . $key_arr[1] . ']';
          ?>
          <tr>
            <th scope="row" class="bg-light align-middle">
              <div style="font-size: 0.9rem;"><?= $name ?></div>
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
          <th scope="col">진행중</th>
          <th scope="col">진행전</th>
        </tr>
      </thead>
      <tbody class="text-center">
        <?php
        $week_keys = array_keys($week_data);
        sort($week_keys);
        foreach ($week_keys as $wk):
          $wk_arr = explode('|', $wk);
          $wk_idx = $wk_arr[0];
          $wk_mode = $wk_arr[1];
          $wk_val = $week_data[$wk];
          $week_total_count = !empty($wk_val[0]) ? $wk_val[0] : 0;
          ?>
          <tr>
            <th scope="row" class="bg-light align-middle">
              <div style="font-size: 0.9rem;"><?= get_week_text($wk_idx); ?> [<?= $wk_mode ?>]</div>
              <small class="text-muted">(<?= $week_total_count; ?>개)</small>
            </th>
            <?php for ($i = 3; $i > 0; $i--):
              $week_count = !empty($wk_val[$i]) ? $wk_val[$i] : 0;
              ?>
              <td>
                <div><?= $week_count ?>개</div>
                <small class="text-muted">(<?= get_percent($week_count, $week_total_count) . '%'; ?>)</small>
              </td>
            <?php endfor; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">기타 구역 진행률</span></h5>

<table class="table table-bordered mb-5">
  <thead class="thead-light text-center">
    <tr>
      <th scope="col">이름</th>
      <th scope="col">완료</th>
      <th scope="col">진행중</th>
      <th scope="col">진행전</th>
    </tr>
  </thead>
  <tbody class="text-center">
    <?php foreach ($ms_meeting as $key => $value): ?>
      <tr>
        <th class="bg-light text-left p-1">
          <div><?= $value['name']; ?><small class="text-muted ml-2">(<?= $value['s0']; ?>개)</small></div>
        </th>
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
  <div class="row p-3 justify-content-md-end">
    <div class="row col col-lg-3 col-sm-6 col-12 mb-2">
      <div class="col-8 p-0"><input class="form-control w-100" type="date" name="date" value="<?= date("Y-m-d") ?>"
          min="2018-08-22" max="<?= date("Y-m-d") ?>" /></div>
      <div class="col-4">부터</div>
    </div>
    <div class="row col col-lg-3 col-sm-6 col-12 mb-2">
      <div class="col-8 p-0"><input class="form-control w-100" type="date" name="date2" value="<?= date("Y-m-d") ?>"
          min="2018-08-22" max="<?= date("Y-m-d") ?>" /></div>
      <div class="col-4">까지</div>
    </div>
    <div class="col-md-auto mb-2">
      <button type="submit" class="btn btn-outline-secondary float-right"><i class="bi bi-search"></i> 검색</button>
    </div>
  </div>
</form>
<div id="statistics_territory_past"></div>