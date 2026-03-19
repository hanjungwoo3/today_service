<?php include_once('../config.php'); ?>

<?php
$month = date("n");
$first_d = mktime(0, 0, 0, date("m"), 1, date("Y"));

if ($month >= 9) {
  $year = date("Y", strtotime("+1 year", $first_d));
  $yfday = date("Y-09-01");
} elseif ($month < 9) {
  $year = date("Y");
  $yfday = date("Y-09-01", strtotime("-1 year", $first_d));
}
$ylday = date("Y-m-d");

if (empty($st_year))
  $st_year = $year;
if ($st_year != $year) {
  $last_d = mktime(0, 0, 0, 1, 1, $st_year);
  $yfday = date("Y-09-01", strtotime("-1 year", $last_d)); // 시작 날
  $ylday = date("Y-08-31", $last_d); // 마지막 날
}

if (empty($mb_id)) {
  echo '<div><small class="text-secondary text-center">전도인을 선택해 주세요</small></div>';
  exit;
}

$where = $mb_id ? "WHERE mb_id = " . $mb_id : "";
$mb_sql = "SELECT mb_id, mb_name, mb_movein_date, mb_moveout_date FROM " . MEMBER_TABLE . " " . $where . " ORDER BY mb_name";
$mb_result = $mysqli->query($mb_sql);
$mb = $mb_result->fetch_assoc();
$movein_date = $mb['mb_movein_date'];
$moveout_date = $mb['mb_moveout_date'];

if ($yfday < $movein_date)
  $yfday = $movein_date;
if (!empty_date($moveout_date) && $ylday > $moveout_date)
  $ylday = $moveout_date;

$c_meeting_schedule_type_use = unserialize(MEETING_SCHEDULE_TYPE_USE);
$total_counts = array();
$member_counts = array();
$while_check = '';

$tm_sql = "SELECT * FROM " . MEETING_TABLE . " WHERE m_date >= '{$yfday}' AND m_date <= '{$ylday}' AND m_cancle = '0' AND mb_id != '' ORDER BY m_date";
$tm_result = $mysqli->query($tm_sql);
if ($tm_result->num_rows > 0) {
  $while_check = 'check';
  while ($total = $tm_result->fetch_assoc()) {
    $t = $total['ms_type'];
    if (!isset($total_counts[$t]))
      $total_counts[$t] = array();
    if (isset($total_counts[$t][$total['m_date']])) {
      $total_counts[$t][$total['m_date']]++;
    } else {
      $total_counts[$t][$total['m_date']] = 1;
    }
  }
}

$m_sql = "SELECT * FROM " . MEETING_TABLE . " WHERE FIND_IN_SET(" . $mb['mb_id'] . ",mb_id) AND m_date >= '{$yfday}' AND m_date <= '{$ylday}' AND m_cancle = '0'";
$m_result = $mysqli->query($m_sql);
if ($m_result->num_rows > 0) {
  while ($minister = $m_result->fetch_assoc()) {
    $t = $minister['ms_type'];
    if (!isset($member_counts[$t]))
      $member_counts[$t] = array();
    if (isset($member_counts[$t][$minister['m_date']])) {
      $member_counts[$t][$minister['m_date']]++;
    } else {
      $member_counts[$t][$minister['m_date']] = 1;
    }
  }
}

$chart_data = array();
foreach ($total_counts as $t => $dates) {
  if (isset($c_meeting_schedule_type_use[$t]) && $c_meeting_schedule_type_use[$t] === 'unused')
    continue;

  $month_array = array();
  $t_count_monthly = array();
  $m_count_monthly = array();

  foreach ($dates as $date => $val) {
    $month = date('y.m', strtotime($date));
    if (!in_array($month, $month_array))
      $month_array[] = $month;
    if (isset($t_count_monthly[$month]))
      $t_count_monthly[$month]++;
    else
      $t_count_monthly[$month] = 1;
  }

  if (isset($member_counts[$t])) {
    foreach ($member_counts[$t] as $date => $val) {
      $month = date('y.m', strtotime($date));
      if (isset($m_count_monthly[$month]))
        $m_count_monthly[$month]++;
      else
        $m_count_monthly[$month] = 1;
    }
  }

  $rows = array();
  foreach ($month_array as $date) {
    $tv = isset($t_count_monthly[$date]) ? $t_count_monthly[$date] : 0;
    $mv = isset($m_count_monthly[$date]) ? $m_count_monthly[$date] : 0;
    $mn = $tv - $mv;
    $rows[] = "['" . $date . "', " . $mv . ", " . $mv . ", " . $mn . "]";
  }
  if (empty($rows))
    $rows[] = "['" . $ylday . "', 0, 0, 0]";

  $count = count($rows);
  $height = ($count < 4) ? 120 : (($count < 7) ? 240 : (($count < 10) ? 360 : 480));

  $chart_data[$t] = array(
    'title' => get_meeting_schedule_type_text($t),
    'rows' => implode(",", $rows),
    'height' => $height
  );
}
?>

<script type="text/javascript">
  google.charts.load('current', { packages: ['corechart', 'bar'] });
  <?php foreach ($chart_data as $t => $data): ?>
    google.charts.setOnLoadCallback(chart_<?= $t ?>);
    function chart_<?= $t ?>() {
      var data = google.visualization.arrayToDataTable([
        ['date', '참여', { role: 'annotation' }, '불참'],
        <?= $data['rows'] ?>
      ]);
      var options = {
        title: '<?= $data['title'] ?>',
        titleTextStyle: { color: '#2e2e33', fontSize: 16 },
        colors: ["#6390d8", "#e9ecef"],
        chartArea: { width: '80%' },
        height: <?= $data['height'] ?>,
        legend: { position: 'bottom', textStyle: { fontSize: 13 } },
        isStacked: 'percent',
        bar: { groupWidth: "70%" },
        animation: { duration: 1200, easing: 'out', startup: true }
      };
      var chart = new google.visualization.BarChart(document.getElementById('chart_div_<?= $t ?>'));
      chart.draw(data, options);
    }
  <?php endforeach; ?>
</script>

<?php if ($while_check == 'check' && !empty($chart_data)): ?>
  <?php foreach ($chart_data as $t => $data): ?>
    <div id="chart_div_<?= $t ?>" class="mb-4"></div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="text-center font-weight-bold pt-4">데이터가 없습니다.</div>
<?php endif; ?>