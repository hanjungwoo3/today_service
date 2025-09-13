<?php include_once('../config.php');?>

<?php
$month = date("n");
$first_d = mktime(0,0,0, date("m"), 1, date("Y"));

if($month >= 9){
  $year = date("Y", strtotime("+1 year", $first_d));
  $yfday = date("Y-09-01");
}elseif($month < 9){
  $year = date("Y");
  $yfday = date("Y-09-01", strtotime("-1 year", $first_d));
}
$ylday = date("Y-m-d");

if(empty($st_year)) $st_year = $year;
if($st_year != $year){
  $last_d = mktime(0,0,0, 1, 1, $st_year);
  $yfday = date("Y-09-01", strtotime("-1 year", $last_d)); // 시작 날
  $ylday = date("Y-08-31", $last_d); // 마지막 날
}

if(empty($mb_id)){
  echo '<div><small class="text-secondary text-center">전도인을 선택해 주세요</small></div>';
  exit;
}

$where = $mb_id?"WHERE mb_id = ".$mb_id:"";
$mb_sql = "SELECT mb_id, mb_name, mb_movein_date, mb_moveout_date FROM ".MEMBER_TABLE." ".$where." ORDER BY mb_name";
$mb_result = $mysqli->query($mb_sql);
$mb = $mb_result->fetch_assoc();
$movein_date = $mb['mb_movein_date'];
$moveout_date = $mb['mb_moveout_date'];

if($yfday < $movein_date) $yfday = $movein_date;
if(!empty_date($moveout_date) && $ylday > $moveout_date) $ylday = $moveout_date;

$thm = array();
$tjm = array();
$while_check = '';

$tm_sql="SELECT * FROM ".MEETING_TABLE." WHERE m_date >= '{$yfday}' AND m_date <= '{$ylday}' AND m_cancle = '0' AND mb_id != '' ORDER BY m_date";
$tm_result = $mysqli->query($tm_sql);
if($tm_result->num_rows > 0){
  $while_check = 'check';
  while($total = $tm_result->fetch_assoc()){
    if($total['ms_type'] == '1'){
      if(isset($thm[$total['m_date']])){
        $thm[$total['m_date']]++;
      }else{
        $thm[$total['m_date']] = 1;
      }
    }elseif($total['ms_type'] == '2'){
      if(isset($tjm[$total['m_date']])){
        $tjm[$total['m_date']]++;
      }else{
        $tjm[$total['m_date']] = 1;
      }
    }
  }
}

$hmonth_array = array();
$jmonth_array = array();
$thm_count = array();
$tjm_count = array();

foreach ($thm as $date => $value) {
  $month = date('y.m', strtotime($date));
  $hmonth_array[] = $month;
  if(isset($thm_count[$month])){
    $thm_count[$month]++;
  }else{
    $thm_count[$month] = 1;
  }
}
$hmonth = array_unique($hmonth_array);
foreach ($tjm as $date => $value) {
  $month = date('y.m', strtotime($date));
  $jmonth_array[] = $month;
  if(isset($tjm_count[$month])){
    $tjm_count[$month]++;
  }else{
    $tjm_count[$month] = 1;
  }
}
$jmonth = array_unique($jmonth_array);

$hm = array();
$jm = array();

$m_sql="SELECT * FROM ".MEETING_TABLE." WHERE FIND_IN_SET(".$mb['mb_id'].",mb_id) AND m_date >= '{$yfday}' AND m_date <= '{$ylday}' AND m_cancle = '0'";
$m_result = $mysqli->query($m_sql);
if($m_result->num_rows > 0){
  while($minister = $m_result->fetch_assoc()){
    if($minister['ms_type'] == '1'){
      if(isset($hm[$minister['m_date']])){
        $hm[$minister['m_date']]++;
      }else{
        $hm[$minister['m_date']] = 1;
      }
    }elseif($minister['ms_type'] == '2'){
      if(isset($jm[$minister['m_date']])){
        $jm[$minister['m_date']]++;
      }else{
        $jm[$minister['m_date']] = 1;
      }
    }
  }
}

$hm_count = array();
$jm_count = array();

foreach ($hm as $date => $value) {
  $month = date('y.m', strtotime($date));
  if(isset($hm_count[$month])){
    $hm_count[$month]++;
  }else{
    $hm_count[$month] = 1;
  }
}
foreach ($jm as $date => $value) {
  $month = date('y.m', strtotime($date));
  if(isset($jm_count[$month])){
    $jm_count[$month]++;
  }else{
    $jm_count[$month] = 1;
  }
}

foreach ($hmonth as $key => $date) {
  if(!isset($thm_count[$date])) $thm_count[$date] = '0';
  if(!isset($hm_count[$date])) $hm_count[$date] = '0';
  $hv = $hm_count[$date];
  $hn = $thm_count[$date] - $hm_count[$date];
  $hm_array[] ='[\''.$date.'\', '.$hv.', '.$hv.', '.$hn.']';
}
if(!isset($hm_array)) $hm_array[] ='[\''.$ylday.'\', 0, 0, 0]';
$hm_array_count = count($hm_array);
if($hm_array_count < 4){
  $hm_height = 120;
}elseif($hm_array_count < 7){
  $hm_height = 240;
}elseif($hm_array_count < 10){
  $hm_height = 360;
}else{
  $hm_height = 480;
}

foreach ($jmonth as $key => $date) {
  if(!isset($tjm_count[$date])) $tjm_count[$date] = '0';
  if(!isset($jm_count[$date])) $jm_count[$date] = '0';
  $jv = $jm_count[$date];
  $jn = $tjm_count[$date] - $jm_count[$date];
  $jm_array[] ='[\''.$date.'\', '.$jv.', '.$jv.', '.$jn.']';
}
if(!isset($jm_array)) $jm_array[] ='[\''.$ylday.'\', 0, 0, 0]';
$jm_array_count = count($jm_array);
if($jm_array_count < 4){
  $jm_height = 120;
}elseif($jm_array_count < 7){
  $jm_height = 240;
}elseif($jm_array_count < 10){
  $jm_height = 360;
}else{
  $jm_height = 480;
}
?>

<script type="text/javascript">
  google.charts.load('current', {packages: ['corechart', 'bar']});
  google.charts.setOnLoadCallback(hmeeting_minister_chart);
  google.charts.setOnLoadCallback(jmeeting_minister_chart);

  function hmeeting_minister_chart() {
    var data = google.visualization.arrayToDataTable([
      ['date', '참여', {role: 'annotation'}, '불참'],
      <?php echo implode(",",$hm_array);?>
    ]);

      var options = {
        title: '호별',
        titleTextStyle: {
          color : '#2e2e33',
          fontSize: 16
        },
        colors: [ "#6390d8", "#e9ecef"],
        chartArea: {
          width: '80%'
        },
        height: <?=$hm_height?>,
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
      var chart = new google.visualization.BarChart(document.getElementById('hmeeting_minister_chart'));
      chart.draw(data, options);
    }


    function jmeeting_minister_chart() {
      var data = google.visualization.arrayToDataTable([
        ['date', '참여', {role: 'annotation'}, '불참'],
        <?php echo implode(",",$jm_array);?>
      ]);

        var options = {
          title: '전시대',
          titleTextStyle: {
            color : '#2e2e33',
            fontSize: 16
          },
          colors: [ "#6390d8", "#e9ecef"],
          chartArea: {
            width: '80%'
          },
          height: <?=$jm_height?>,
          legend: {
            position: 'bottom',
            textStyle: {
              fontSize: 13
            }
          },
          isStacked: 'percent',
          bar: {groupWidth: "70%"},
          animation: {
            duration: 1200,
            easing: 'out',
            startup: true
          }
        };
        var chart = new google.visualization.BarChart(document.getElementById('jmeeting_minister_chart'));
        chart.draw(data, options);
      }
</script>

<?php if($while_check == 'check'): ?>
  <div id="hmeeting_minister_chart"></div>
  <div id="jmeeting_minister_chart"></div>
<?php else:?>
  <div class="text-center font-weight-bold pt-4">데이터가 없습니다.</div>
<?php endif; ?>
