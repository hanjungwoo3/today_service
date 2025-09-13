<?php include_once('../config.php');?>

<?php
//구역카드 요일별 구역타입 비율
$sql="SELECT * FROM (SELECT
      (SELECT count(DISTINCT tt_id) FROM ".TERRITORY_TABLE." WHERE tt_type = '편지') sum,
      (SELECT count(DISTINCT tt_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND ms_week = '1' AND mb_id = '0' AND tt_type = '편지') s1,
      (SELECT count(DISTINCT tt_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND ms_week = '2' AND mb_id = '0' AND tt_type = '편지') s2,
      (SELECT count(DISTINCT tt_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND ms_week = '3' AND mb_id = '0' AND tt_type = '편지') s3,
      (SELECT count(DISTINCT tt_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND ms_week = '4' AND mb_id = '0' AND tt_type = '편지') s4,
      (SELECT count(DISTINCT tt_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND ms_week = '5' AND mb_id = '0' AND tt_type = '편지') s5,
      (SELECT count(DISTINCT tt_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND ms_week = '6' AND mb_id = '0' AND tt_type = '편지') s6,
      (SELECT count(DISTINCT tt_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND ms_week = '7' AND mb_id = '0' AND tt_type = '편지') s7,
      (SELECT count(DISTINCT tt_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id != '0' AND tt_type = '편지') s8,
      (SELECT count(DISTINCT tt_id) FROM ".TERRITORY_TABLE." WHERE ms_id = '0' AND mb_id = '0' AND tt_type = '편지') s9,
      (SELECT count(DISTINCT tt_id) FROM ".TERRITORY_TABLE." WHERE mb_id != '0' AND tt_type = '편지') s10) T;";
$result = $mysqli->query($sql);
$week_letter = $result->fetch_assoc();

//구역카드 요일별 진행률 (1.미사용, 2.미완료, 3.완료)
$week = array();
$sql = "SELECT ms_week, tt_assigned_date, tt_end_date FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND mb_id = '0' AND tt_type = '편지'";
$result = $mysqli->query($sql);
while ($we = $result->fetch_assoc()){
  if(isset($week[$we['ms_week']][0])){
    $week[$we['ms_week']][0]++;
  }else{
    $week[$we['ms_week']][0] = 1;
  }
  if(empty_date($we['tt_assigned_date'])){
    if(isset($week[$we['ms_week']][1])){
      $week[$we['ms_week']][1]++;
    }else{
      $week[$we['ms_week']][1] = 1;
    }
  }elseif(!empty_date($we['tt_end_date'])){
    if(isset($week[$we['ms_week']][3])){
      $week[$we['ms_week']][3]++;
    }else{
      $week[$we['ms_week']][3] = 1;
    }
  }else{
    if(isset($week[$we['ms_week']][2])){
      $week[$we['ms_week']][2]++;
    }else{
      $week[$we['ms_week']][2] = 1;
    }
  }
}

//구역카드 모임별 진행률
$meeting_array = array();
$m_sql="SELECT ms.ms_id as ms_id, tt_assigned_date, tt_end_date FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id != '0' AND mb_id = '0' AND tt_type = '편지'";
$m_result = $mysqli->query($m_sql);
while ($mow = $m_result->fetch_assoc()){
  if(isset($meeting_array[$mow['ms_id']][0])){
    $meeting_array[$mow['ms_id']][0]++;
  }else{
    $meeting_array[$mow['ms_id']][0] = 1;
  }
  if(empty_date($mow['tt_assigned_date'])){
    if(isset($meeting_array[$mow['ms_id']][1])){
      $meeting_array[$mow['ms_id']][1]++;
    }else{
      $meeting_array[$mow['ms_id']][1] = 1;
    }
  }elseif(!empty_date($mow['tt_assigned_date']) && empty_date($mow['tt_end_date'])){
    if(isset($meeting_array[$mow['ms_id']][2])){
      $meeting_array[$mow['ms_id']][2]++;
    }else{
      $meeting_array[$mow['ms_id']][2] = 1;
    }
  }elseif(!empty_date($mow['tt_end_date'])){
    if(isset($meeting_array[$mow['ms_id']][3])){
      $meeting_array[$mow['ms_id']][3]++;
    }else{
      $meeting_array[$mow['ms_id']][3] = 1;
    }
  }
}

foreach ($meeting_array as $ms_id => $letter_con) {
  $ms_sql = "SELECT * FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".MEETING_PLACE_TABLE." mp ON ms.mp_id = mp.mp_id LEFT JOIN ".GROUP_TABLE." g ON ms.g_id = g.g_id
             WHERE ms_id = '{$ms_id}' ORDER BY ms_week, ms_time, mp_name, g_name, ms_id ASC";
  $ms_result = $mysqli->query($ms_sql);
  $ms = $ms_result->fetch_assoc();

  if(empty($letter_con[3])) $letter_con[3] = 0;
  if(empty($letter_con[2])) $letter_con[2] = 0;
  if(empty($letter_con[1])) $letter_con[1] = 0;
  if(empty($letter_con[0])) $letter_con[0] = 0;

  $tt_meeting[$ms['ms_week']][] = array(
    'name' => '('.get_week_text($ms['ms_week']).') '.get_meeting_data_text($ms['ms_time'], $ms['g_name'], $ms['mp_name']),
    's3' => $letter_con[3],
    's2' => $letter_con[2],
    's1' => $letter_con[1],
    's0' => $letter_con[0]
  );
}

if(!empty($tt_meeting)){
  ksort($tt_meeting);
  $ms_meeting = array();
  foreach ($tt_meeting as $ms_week => $string) {
    $count_length = count($string);
    for ($i=0; $i < $count_length; $i++) $ms_meeting[] = $string[$i];
  }
}

if(empty($meeting_array)) $ms_meeting[] = array('name' => '전체', 's3' => 0, 's2' => 0, 's1' => 0, 's0' => 0 );
?>

<script type="text/javascript">
google.charts.load('current', {packages: ['corechart', 'bar']});
google.charts.setOnLoadCallback(week_telephone_chart);

function week_telephone_chart() {
  var data = new google.visualization.DataTable();
  data.addColumn('string', 'Topping');
  data.addColumn('number', 'Slices');
  data.addRows([
    ['월', <?=$week_letter['s1']?>],
    ['화', <?=$week_letter['s2']?>],
    ['수', <?=$week_letter['s3']?>],
    ['목', <?=$week_letter['s4']?>],
    ['금', <?=$week_letter['s5']?>],
    ['토', <?=$week_letter['s6']?>],
    ['일', <?=$week_letter['s7']?>],
    ['기타', <?=$week_letter['s8']?>],
    ['개인', <?=$week_letter['s10']?>],
    ['미배정', <?=$week_letter['s9']?>]
  ]);

  var options = {
    colors: [ "#8044d4", "#4465d4", "#4cbce4", "#42af47", "#FF9900", "#DC3912", "#8B0707", "#717171", "#000"],
    height:300,
    legend: {
      textStyle: {
        fontSize: 12
      }
    },
    pieSliceTextStyle:{
      fontSize:14
    },
    pieStartAngle: 100
  };

  var chart = new google.visualization.PieChart(document.getElementById('week_telephone_chart'));
  chart.draw(data, options);
}
</script>

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">편지 구역 현황</span><small class="mt-2 float-right">전체 구역 : <?=$week_letter['sum']?>개</small></h5>

<div id="week_telephone_chart"></div>

<div class="row">
  <div class="col-lg-6">
    <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">요일별 구역 진행률</span></h5>

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
        <?php for($k=1; $k < 8; $k++):?>
          <?php $week_total_count = !empty($week[$k][0])?$week[$k][0]:0;?>
          <tr>
            <th scope="row" class="bg-light align-middle">
              <div><?=get_week_text($k);?></div>
              <small class="text-muted">(<?=$week_total_count;?>개)</small>
            </th>
            <?php for($i=3; $i > 0; $i--):?>
              <?php $week_count = !empty($week[$k][$i])?$week[$k][$i]:0;?>
              <td>
                <div><?=$week_count?>개</div>
                <small class="text-muted">(<?=get_percent($week_count, $week_total_count).'%';?>)</small>
              </td>
            <?php endfor;?>
          </tr>
        <?php endfor;?>
      </tbody>
    </table>
  </div>
  <div class="col-lg-6">
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
        <?php foreach ($ms_meeting as $key => $value):?>
          <tr>
            <th class="bg-light text-left p-1" colspan="4">
              <div><?=$value['name'];?><small class="text-muted ml-2">(<?=$value['s0'];?>개)</small></div>
            </th>
          </tr>
          <tr>
          </tr>
            <td class="align-middle">
              <div><?=$value['s3'];?>개</div>
              <small class="text-muted">(<?=get_percent($value['s3'], $value['s0']).'%';?>)</small>
            </td>
            <td class="align-middle">
              <div><?=$value['s2'];?>개</div>
              <small class="text-muted">(<?=get_percent($value['s2'], $value['s0']).'%';?>)</small>
            </td>
            <td class="align-middle">
              <div><?=$value['s1'];?>개</div>
              <small class="text-muted">(<?=get_percent($value['s1'], $value['s0']).'%';?>)</small>
            </td>
          </tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>
</div>

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">기간별 구역 진행률</span></h5>

<form method="post" url="statistics_letter_past">
  <div class="row p-3 justify-content-md-end">
    <div class="row col col-lg-3 col-sm-6 col-12 mb-2">
      <div class="col-8 p-0"><input class="form-control w-100" type="date" name="date" value="<?=date("Y-m-d")?>" min="2018-08-22" max="<?=date("Y-m-d")?>" onchange="datemin();"/></div>
      <div class="col-4">부터</div>
    </div>
    <div class="row col col-lg-3 col-sm-6 col-12 mb-2">
      <div class="col-8 p-0"><input class="form-control w-100" type="date" name="date2" value="<?=date("Y-m-d")?>" min="2018-08-22" max="<?=date("Y-m-d")?>" onchange="datemax();"/></div>
      <div class="col-4">까지</div>
    </div>
    <div class="col-md-auto mb-2">
      <button type="submit" class="btn btn-outline-secondary float-right"><i class="bi bi-search"></i> 검색</button>
    </div>
  </div>
</form>
<div id="statistics_letter_past"></div>
