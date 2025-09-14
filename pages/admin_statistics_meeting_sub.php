<?php include_once('../config.php');?>

<?php
$hmonth_key_count = 0;

$h0_array = array('\'월별\'');
$h1_array = array('\'월\'');
$h2_array = array('\'화\'');
$h3_array = array('\'수\'');
$h4_array = array('\'목\'');
$h5_array = array('\'금\'');
$h6_array = array('\'토\'');
$h7_array = array('\'일\'');
$jmonth_key_count = 0;
$j0_array = array('\'월별\'');
$j1_array = array('\'월\'');
$j2_array = array('\'화\'');
$j3_array = array('\'수\'');
$j4_array = array('\'목\'');
$j5_array = array('\'금\'');
$j6_array = array('\'토\'');
$j7_array = array('\'일\'');

$h0_string = '';
$h1_string = '';
$h2_string = '';
$h3_string = '';
$h4_string = '';
$h5_string = '';
$h6_string = '';
$h7_string = '';

$j0_string = '';
$j1_string = '';
$j2_string = '';
$j3_string = '';
$j4_string = '';
$j5_string = '';
$j6_string = '';
$j7_string = '';

$mh = array();
$mj = array();
$mhl = array();

$volunteered = array(); //모임 참석자 id 배열
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

//봉사모임 참석자
$mt_sql = "SELECT * FROM ".MEETING_TABLE." WHERE m_date >= '{$yfday}' AND m_date <= '{$ylday}' AND m_cancle = '0' AND mb_id != '' ORDER BY m_date, ms_week ASC";
$mt_result = $mysqli->query($mt_sql);
if($mt_result->num_rows > 0){
  while($mt = $mt_result->fetch_assoc()){
    $m_count = 0;
    $time_month = date('n', strtotime($mt['m_date']));
    $volunteered = explode(',',$mt['mb_id']);
    $m_count += count($volunteered);

    if($mt['ms_type'] == '1'){
      if(!isset($mh[$time_month][$mt['ms_week']])){
        $mh[$time_month][$mt['ms_week']] = 0;
      }
      if(!isset($mh_length[$mt['m_date']][$mt['ms_week']])){
        $mh_length[$mt['m_date']][$mt['ms_week']] = 0;
      }
      $mh[$time_month][$mt['ms_week']] += $m_count;
      $mh_length[$mt['m_date']][$mt['ms_week']]++;
    }elseif($mt['ms_type'] == '2'){
      if(!isset($mj[$time_month][$mt['ms_week']])){
        $mj[$time_month][$mt['ms_week']] = 0;
      }
      if(!isset($mj_length[$mt['m_date']][$mt['ms_week']])){
        $mj_length[$mt['m_date']][$mt['ms_week']] = 0;
      }
      $mj[$time_month][$mt['ms_week']] += $m_count;
      $mj_length[$mt['m_date']][$mt['ms_week']]++;
    }
  }
}

if(!empty($mh_length)){
  foreach ($mh_length as $date => $week) {
    $mh_month = date('n', strtotime($date));
    if(!isset($mhl[$mh_month][key($week)])){
      $mhl[$mh_month][key($week)] = 0;
    }
    $mhl[$mh_month][key($week)]++;
  }
}
if(!empty($mj_length)){
  foreach ($mj_length as $date => $week) {
    $mj_month = date('n', strtotime($date));
    if(!isset($mjl[$mj_month][key($week)])){
      $mjl[$mj_month][key($week)] = 0;
    }
    $mjl[$mj_month][key($week)]++;
  }
}

if(!empty($mh)){
  foreach ($mh as $month_key => $week) {
    $hmonth_key_count++;
    $h0_array[] = '\''.$month_key.'월\'';
    for ($s=1; $s < 8; $s++) {
      if(isset($mhl[$month_key][$s])){
        $h_sub = $week[$s]/$mhl[$month_key][$s];
        $h_count = (is_nan(round($h_sub)) || is_infinite(round($h_sub)))?0:round($h_sub);
      }else{
        $h_count = 0;
      }

      if (!isset(${"h".$s."_count"})) {
        ${"h".$s."_count"} = 0; // 변수를 0으로 초기화
      }

      ${"h".$s."_array"}[] = $h_count;
      ${"h".$s."_count"} += $h_count;
    }
  }
}

$h0_array[] = '\'평균\'';
$h0_array[] = $h0_string;
for ($s=1; $s < 8; $s++) {
  if ($hmonth_key_count > 0 && is_numeric(${"h".$s."_count"}/$hmonth_key_count)) {
    $h_count = (is_nan(round(${"h".$s."_count"}/$hmonth_key_count)) || is_infinite(round(${"h".$s."_count"}/$hmonth_key_count)))?0:round(${"h".$s."_count"}/$hmonth_key_count);
  }else{
    $h_count = 0;
  }
  ${"h".$s."_array"}[] = $h_count;
  ${"h".$s."_array"}[] = ${"h".$s."_string"};
}
for ($i=0; $i < 8; $i++) {
  ${"string_mh".$i} = '['.implode(",",${"h".$i."_array"}).']';
  $mh_array[] = ${"string_mh".$i};
}

if($hmonth_key_count < 4){
  $hheight = 300;
}elseif($hmonth_key_count < 7){
  $hheight = 600;
}elseif($hmonth_key_count < 10){
  $hheight = 900;
}else{
  $hheight = 1200;
}

if(!empty($mj)){
  foreach ($mj as $month_key => $week) {
    $jmonth_key_count++;
    $j0_array[] = '\''.$month_key.'월\'';
    for ($s=1; $s < 8; $s++) {
      if(isset($mjl[$month_key][$s])){
        $j_sub = $week[$s]/$mjl[$month_key][$s];
        $j_count = (is_nan(round($j_sub)) || is_infinite(round($j_sub)))?0:round($j_sub);
      }else{
        $j_count = 0;
      }

      if (!isset(${"j".$s."_count"})) {
        ${"j".$s."_count"} = 0; // 변수를 0으로 초기화
      }

      ${"j".$s."_array"}[] = $j_count;
      ${"j".$s."_count"} += $j_count;
    }
  }
}

$j0_array[] = '\'평균\'';
$j0_array[] = $j0_string;
for ($s=1; $s < 8; $s++) {
  if ($jmonth_key_count > 0 && is_numeric(${"j".$s."_count"}/$jmonth_key_count)) {
    $j_count = (is_nan(round(${"j".$s."_count"}/$jmonth_key_count)) || is_infinite(round(${"j".$s."_count"}/$jmonth_key_count)))?0:round(${"j".$s."_count"}/$jmonth_key_count);
  }else{
    $j_count = 0;
  }
  ${"j".$s."_array"}[] = $j_count;
  ${"j".$s."_array"}[] = ${"j".$s."_string"};
}
for ($i=0; $i < 8; $i++) {
  ${"string_mj".$i} = '['.implode(",",${"j".$i."_array"}).']';
  $mj_array[] = ${"string_mj".$i};
}

if($jmonth_key_count < 4){
  $jheight = 300;
}elseif($jmonth_key_count < 7){
  $jheight = 600;
}elseif($jmonth_key_count < 10){
  $jheight = 900;
}else{
  $jheight = 1200;
}

?>
<script type="text/javascript">
  google.charts.load('current', {'packages':['corechart', 'bar']});
  google.charts.setOnLoadCallback(admin_hmeeting_all);
  google.charts.setOnLoadCallback(admin_jmeeting_all);
  google.charts.setOnLoadCallback(admin_hmeeting);
  google.charts.setOnLoadCallback(admin_jmeeting);


  function admin_hmeeting_all() {
    var data = google.visualization.arrayToDataTable([
      <?php echo implode(",",$mh_array);?>
    ]);

    var options = {
      chartArea: {
        left: 35,
        top: 10,
        width: '90%',
        height: '85%'
      },
      title: '호별 평균 참여자',
      curveType: 'function',
      hAxis: {
        textStyle: {
          fontSize: 13
        }
      },
      vAxis: {
        minValue: 0,
        maxValue: 30,
        baselineColor: '#DDD',
        textStyle: {
          fontSize: 11
        },
        format: '#명'
      },
      legend: {
        position: 'none',
        textStyle: {
          fontSize: 11
        }
      },
      animation: {
        duration: 1200,
        easing: 'out',
        startup: true
      }
    };

    var chart = new google.visualization.LineChart(document.getElementById('admin_hmeeting_all'));

    chart.draw(data, options);
  }

  function admin_jmeeting_all() {
    var data = google.visualization.arrayToDataTable([
      <?php echo implode(",",$mj_array);?>
    ]);

    var options = {
      chartArea: {
        left: 35,
        top: 10,
        width: '90%',
        height: '85%'
      },
      title: '전시대 평균 참여자',
      curveType: 'function',
      hAxis: {
        textStyle: {
          fontSize: 13
        }
      },
      vAxis: {
        minValue: 0,
        maxValue: 30,
        baselineColor: '#DDD',
        textStyle: {
          fontSize: 11
        },
        format: '#명'
      },
      legend: {
        position: 'none',
        textStyle: {
          fontSize: 11
        }
      },
      animation: {
        duration: 1200,
        easing: 'out',
        startup: true
      }
    };

    var chart = new google.visualization.LineChart(document.getElementById('admin_jmeeting_all'));

    chart.draw(data, options);
  }

  function admin_hmeeting() {
    var data = google.visualization.arrayToDataTable([
      <?php echo implode(",",$mh_array);?>
    ]);

    var options = {
      chartArea: {
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
          fontSize: 11
        }
      },
      vAxis: {
        textStyle: {
          fontSize: 12
        }
      },
      legend: {
        textStyle: {
          fontSize: 13
        }
      },
      height: <?=$hheight?>,
      bars: 'horizontal',
      bar: {groupWidth: "75%"},
      animation: {
        duration: 1200,
        easing: 'out',
        startup: true
      }
    };

    var chart = new google.charts.Bar(document.getElementById('admin_hmeeting'));

    chart.draw(data, google.charts.Bar.convertOptions(options));
  }

  function admin_jmeeting() {
    var data = google.visualization.arrayToDataTable([
      <?php echo implode(",",$mj_array);?>
    ]);

    var options = {
      chartArea: {
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
          fontSize: 11
        }
      },
      vAxis: {
        textStyle: {
          fontSize: 12
        }
      },
      legend: {
        textStyle: {
          fontSize: 13
        }
      },
      height: <?=$jheight?>,
      bars: 'horizontal',
      bar: {groupWidth: "75%"},
      animation: {
        duration: 1200,
        easing: 'out',
        startup: true
      }
    };

    var chart = new google.charts.Bar(document.getElementById('admin_jmeeting'));

    chart.draw(data, google.charts.Bar.convertOptions(options));
  }
</script>

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">일반 모임 평균 참여자</span></h5>

<div id="admin_hmeeting"></div>
<div class="my-3" id="admin_hmeeting_all"></div>

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">전시대 모임 평균 참여자</span></h5>

<div id="admin_jmeeting"></div>
<div class="my-3" id="admin_jmeeting_all"></div>
