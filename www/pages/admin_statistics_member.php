<?php include_once('../config.php');?>

<?php
$where = "mb_moveout_date='0000-00-00'";
$com_where = "mb_moveout_date='0000-00-00' AND mb_position != '3'";

$sql = "SELECT count(mb_id) as sum FROM ".MEMBER_TABLE." WHERE ".$com_where;
$result = $mysqli->query($sql);
$member = $result->fetch_assoc();

$s_sql="SELECT * FROM (SELECT
        (SELECT count(mb_id) FROM ".MEMBER_TABLE." WHERE ".$com_where." AND mb_sex='M') a,
        (SELECT count(mb_id) FROM ".MEMBER_TABLE." WHERE ".$com_where." AND mb_sex='W') b) T;";
$s_result = $mysqli->query($s_sql);
$sex = $s_result->fetch_assoc();

$pi_sql="SELECT * FROM (SELECT
        (SELECT count(mb_id) FROM ".MEMBER_TABLE." WHERE ".$com_where." AND mb_pioneer='1') a,
        (SELECT count(mb_id) FROM ".MEMBER_TABLE." WHERE ".$com_where." AND mb_pioneer='2') b,
        (SELECT count(mb_id) FROM ".MEMBER_TABLE." WHERE ".$com_where." AND mb_pioneer='3') c,
        (SELECT count(mb_id) FROM ".MEMBER_TABLE." WHERE ".$com_where." AND mb_pioneer='4') d) T;";
$pi_result = $mysqli->query($pi_sql);
$pioneer = $pi_result->fetch_assoc();

$po_sql="SELECT * FROM (SELECT
        (SELECT count(mb_id) FROM ".MEMBER_TABLE." WHERE ".$where." AND mb_position='') a,
        (SELECT count(mb_id) FROM ".MEMBER_TABLE." WHERE ".$where." AND mb_position='1') b,
        (SELECT count(mb_id) FROM ".MEMBER_TABLE." WHERE ".$where." AND mb_position='2') c) T;";
$po_result = $mysqli->query($po_sql);
$position = $po_result->fetch_assoc();

$group_arr[] = "['group', '집단']";
$g_sql = "SELECT g_name, COUNT(mb_id) AS cnt
          FROM ".MEMBER_TABLE." AS mb INNER JOIN ".GROUP_TABLE." AS g ON mb.g_id = g.g_id
          WHERE ".$com_where." GROUP BY mb.g_id
          UNION
          SELECT '미배정' as g_name, COUNT(mb_id) AS cnt
          FROM ".MEMBER_TABLE."
          WHERE ".$com_where." AND g_id = ''";
$g_result = $mysqli->query($g_sql);
while ($group = $g_result->fetch_assoc()) $group_arr[] = "['".$group['g_name']."(".$group['cnt']."명)', ".$group['cnt']."]";
?>

<script type="text/javascript">
  google.charts.load('current', {'packages':['corechart']});
  google.charts.setOnLoadCallback(drawChart);
  google.charts.setOnLoadCallback(drawChart1);
  google.charts.setOnLoadCallback(drawChart2);
  google.charts.setOnLoadCallback(drawChart3);

  function drawChart() {
    var data = google.visualization.arrayToDataTable([
      ['sex', '형제 / 자매'],
      ['형제(<?=$sex['a']?>명)',     <?=$sex['a']?>],
      ['자매(<?=$sex['b']?>명)',    <?=$sex['b']?>]
    ]);
    var options = {
      colors: [ "#6390d8", "#d86363"],
      width:540,
      height:300,
      legend: {
        position: 'right',
        textStyle: {
          fontSize: 13
        }
      },
      pieSliceTextStyle:{
        fontSize:14
      },
      tooltip: {
        textStyle:{
          fontSize:14
        }
      },
      pieStartAngle: 180,
      title:'형제 / 자매',
      titleTextStyle: {
        fontSize: 15
      }
    };
    var chart = new google.visualization.PieChart(document.getElementById('piechart'));
    chart.draw(data, options);
  }

  function drawChart1() {
    var data = google.visualization.arrayToDataTable([
      <?php echo implode(",",$group_arr);?>
    ]);
    var options = {
      colors: ["#6390d8", "#d8a463", "#63d87c", "#b663d8", "#d86363", "#63d3d8", "#d86394", "#cbd863", "#6a63d8", "#9bd863", "#d87e63", "#a5a5a5"],
      width:540,
      height:300,
      legend: {
        position: 'right',
        textStyle: {
          fontSize: 13
        }
      },
      pieSliceTextStyle:{
        fontSize:14
      },
      tooltip: {
        textStyle:{
          fontSize:14
        }
      },
      title:'집단',
      titleTextStyle: {
        fontSize: 15
      }
    };
    var chart = new google.visualization.PieChart(document.getElementById('piechart1'));
    chart.draw(data, options);
  }

  function drawChart2() {
    var data = google.visualization.arrayToDataTable([
      ['pioneer', '파이오니아'],
      ['전도인(<?=$pioneer['a']?>명)',     <?=$pioneer['a']?>],
      ['정규(<?=$pioneer['b']?>명)',     <?=$pioneer['b']?>],
      ['특별(<?=$pioneer['c']?>명)',     <?=$pioneer['c']?>],
      ['선교인(<?=$pioneer['d']?>명)',    <?=$pioneer['d']?>]
    ]);
    var options = {
      colors: [ "#6390d8", "#abd863", "#d89963", "#d86363"],
      width:540,
      height:300,
      legend: {
        position: 'right',
        textStyle: {
          fontSize: 13
        }
      },
      pieSliceTextStyle:{
        fontSize:14
      },
      tooltip: {
        textStyle:{
          fontSize:14
        }
      },
      slices: {
        0: {offset: 0.1},
        1: {offset: 0.1},
        2: {offset: 0.1},
        3: {offset: 0.1}
      },
      title:'파이오니아',
      titleTextStyle: {
        fontSize: 15
      }
    };
    var chart = new google.visualization.PieChart(document.getElementById('piechart2'));
    chart.draw(data, options);
  }

  function drawChart3() {
    var data = google.visualization.arrayToDataTable([
      ['position', '직책'],
      ['전도인(<?=$position['a']?>명)',     <?=$position['a']?>],
      ['봉사의 종(<?=$position['b']?>명)',   <?=$position['b']?>],
      ['장로(<?=$position['c']?>명)',    <?=$position['c']?>]
    ]);
    var options = {
      colors: [ "#6390d8", "#abd863", "#d86363"],
      width:540,
      height:300,
      legend: {
        position: 'right',
        textStyle: {
          fontSize: 13
        }
      },
      pieSliceTextStyle:{
        fontSize:14
      },
      tooltip: {
        textStyle:{
          fontSize:14
        }
      },
      slices: {
        0: {offset: 0.1},
        1: {offset: 0.1},
        2: {offset: 0.1},
        3: {offset: 0.1}
      },
      title:'직책',
      titleTextStyle: {
        fontSize: 15
      }
    };
    var chart = new google.visualization.PieChart(document.getElementById('piechart3'));
    chart.draw(data, options);
  }
</script>

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">전도인 현황</span><small class="mt-2 float-right">전체 전도인 : <?=$member['sum']?>명</small></h5>

<div class="table-responsive">
  <table class="table table-bordered columns" style="min-width: 450px;">
    <tr>
      <td class="p-0"><div id="piechart"></div></td>
      <td class="p-0"><div id="piechart2"></div></td>
    </tr>
  </table>
</div>
<div class="table-responsive mb-3">
  <table class="table table-bordered columns" style="min-width: 450px;">
    <tr>
      <td class="p-0"><div id="piechart1"></div></td>
      <td class="p-0"><div id="piechart3"></div></td>
    </tr>
  </table>
</div>

<?php if(get_member_auth(mb_id()) == 1):?>
<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">전도인별 모임 참여 수</span></h5>
<div>
  <form class="form-group" method="post" url="statistics_minister">
    <div class="row m-3">
      <label for="mb_id" class="col-4 col-md-2 col-form-label">전도인</label>
      <select id="mb_id" class="col-8 col-md-10 form-control" name="mb_id">
        <option value="">선택 안 함</option>
        <?php echo get_member_option('');?>
      </select>
    </div>
    <div class="row m-3">
      <label for="st_year" class="col-4 col-md-2 col-form-label">봉사 연도</label>
      <select id="st_year" class="col-8 col-md-10 form-control" name="st_year">
        <?php $year = (date("n") >= 9)?date("Y", strtotime("+1 year", mktime(0,0,0, date("m"), 1, date("Y")))):date("Y");?>
        <?php for($i=$year; $i >= 2018; $i--) echo '<option value="'.$i.'">'.$i.'</option>';?>
      </select>
    </div>
    <div class="text-right mx-3">
      <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> 검색</button>
    </div>
  </form>
</div>
<div id="statistics_minister"></div>

<hr class="my-5" />

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">모임 형태별 전도인 참여 현황</span></h5>
<div>
  <form class="form-group" method="post" url="statistics_minister_all">
    <div class="m-3 text-right">
      <div class="btn-group btn-group-toggle" data-toggle="buttons">
        <label class="btn btn-outline-secondary active">
          <input type="radio" name="mb_sex" value="" checked> 전체
        </label>
        <label class="btn btn-outline-secondary">
          <input type="radio" name="mb_sex" value="M"> 형제
        </label>
        <label class="btn btn-outline-secondary">
          <input type="radio" name="mb_sex" value="W"> 자매
        </label>
      </div>
    </div>
    <div class="row m-3">
      <label for="mb_month" class="col-4 col-md-2 col-form-label">봉사 연도</label>
      <input id="mb_month" class="col-8 col-md-10 form-control" type="month" name="mb_month" value="<?=date("Y-m")?>" min="2018-09" max="<?=date("Y-m")?>"/>
    </div>
    <div class="text-right mx-3">
      <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> 검색</button>
    </div>
  </form>
</div>

<div id="statistics_minister_all" class="mt-3">
  <?php include_once('admin_statistics_minister_all.php'); ?>
</div>
<?php endif; ?>