<?php include_once('../config.php');?>

<?php
if(empty($ms_id)) exit;

$today = date("Y-m-d");
$month = date("n");
$first_d = mktime(0,0,0, date("m"), 1, date("Y"));
$mb_id = mb_id();
$type = array();
$type_sum = array();
$meeting_avg_date = array();
$meeting_avg = 0;
$meeting_array = array();

if($month >= 9){
  $year = date("Y", strtotime("+1 year", $first_d));
  $yfday = date("Y-09-01");
}elseif($month < 9){
  $year = date("Y");
  $yfday = date("Y-09-01", strtotime("-1 year", $first_d));
}

$ms_row = get_meeting_schedule_data($ms_id);
if($ms_row['g_id']){
  $total_member = '<span class="align-middle mt-2 d-inline-block">월별 참여자 수</span><small class="mt-2 float-right">집단 전도인 : '.count(get_group_member_data($ms_row['g_id'])).'명</small>';
  $group_name = get_group_name($ms_row['g_id']).'집단';
}else{
  $sql = "SELECT count(mb_id) as sum FROM ".MEMBER_TABLE." WHERE mb_moveout_date='0000-00-00' AND mb_position != '3'";
  $result = $mysqli->query($sql);
  $member = $result->fetch_assoc();
  $total_member = '<span class="align-middle mt-2 d-inline-block">월별 참여자 수</span><small class="mt-2 float-right">전체 전도인 : '.$member['sum'].'명</small>';
}

//참석자 통계
$m_sql = "SELECT mb_id, m_date FROM ".MEETING_TABLE."
          WHERE ms_id = '{$ms_id}' AND m_date >= '{$yfday}' AND m_date <= '{$today}' AND m_cancle = '0' AND mb_id != '' ORDER BY m_date";
$m_result = $mysqli->query($m_sql);
while ($mow = $m_result->fetch_assoc()) {
  $count = '0';
  $volunteered = array();
  $volunteered = explode(',',$mow['mb_id']);
  $count = count($volunteered);
  if(isset($meeting_array[$mow['m_date']])){
    $meeting_array[$mow['m_date']] += $count;
  }else{
    $meeting_array[$mow['m_date']] = $count;
  }
  $meeting_avg += $count;
  if(isset($meeting_avg_date[$mow['m_date']])){
    $meeting_avg_date[$mow['m_date']] ++;
  }else{
    $meeting_avg_date[$mow['m_date']] = 1;
  }
}

$length = count($meeting_avg_date);
if($length > 0){
  $avg = round($meeting_avg/$length);
}else{
  $avg = 0;
}
if(is_nan($avg) || is_infinite($avg)) $avg = '0';


// 호별구역카드 (1.미사용, 2.미완료 3.완료)
$sql = "SELECT tt_type, tt_assigned_date, tt_end_date FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id
        WHERE ms.ma_id = '0' AND mb_id = '0' AND ms.ms_id = '{$ms_id}' AND tt_type != '편지' ORDER BY FIELD(tt_type, '일반', '아파트', '빌라', '격지')";
$result = $mysqli->query($sql);
while ($ty = $result->fetch_assoc()){
  if(isset($type[$ty['tt_type']][0])){
    $type[$ty['tt_type']][0]++;
  }else{
    $type[$ty['tt_type']][0] = 1;
  }
  if(isset($type_sum[0])){
    $type_sum[0]++;
  }else{
    $type_sum[0] = 1;
  }
  if(empty_date($ty['tt_assigned_date'])){
    if(isset($type[$ty['tt_type']][1])){
      $type[$ty['tt_type']][1]++;
    }else{
      $type[$ty['tt_type']][1] = 1;
    }
    if(isset($type_sum[1])){
      $type_sum[1]++;
    }else{
      $type_sum[1] = 1;
    }
  }elseif(!empty_date($ty['tt_end_date'])){
    if(isset($type[$ty['tt_type']][3])){
      $type[$ty['tt_type']][3]++;
    }else{
      $type[$ty['tt_type']][3] = 1;
    }
    if(isset($type_sum[3])){
      $type_sum[3]++;
    }else{
      $type_sum[3] = 1;
    }
  }else{
    if(isset($type[$ty['tt_type']][2])){
      $type[$ty['tt_type']][2]++;
    }else{
      $type[$ty['tt_type']][2] = 1;
    }
    if(isset($type_sum[2])){
      $type_sum[2]++;
    }else{
      $type_sum[2] = 1;
    }
  }
}

//전화구역카드
$tp_sql="SELECT * FROM (SELECT
        (SELECT count(DISTINCT tp_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TELEPHONE_TABLE." tp ON ms.ms_id = tp.ms_id WHERE ms.ma_id = '0' AND mb_id = '0' AND ms.ms_id = '{$ms_id}') sum,
        (SELECT count(DISTINCT tp_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TELEPHONE_TABLE." tp ON ms.ms_id = tp.ms_id WHERE ms.ma_id = '0' AND mb_id = '0' AND ms.ms_id = '{$ms_id}' AND tp_assigned_date = '0000-00-00') s1,
        (SELECT count(DISTINCT tp_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TELEPHONE_TABLE." tp ON ms.ms_id = tp.ms_id WHERE ms.ma_id = '0' AND mb_id = '0' AND ms.ms_id = '{$ms_id}' AND tp_assigned_date != '0000-00-00' AND tp_end_date = '0000-00-00') s2,
        (SELECT count(DISTINCT tp_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TELEPHONE_TABLE." tp ON ms.ms_id = tp.ms_id WHERE ms.ma_id = '0' AND mb_id = '0' AND ms.ms_id = '{$ms_id}' AND tp_assigned_date != '0000-00-00' AND tp_end_date != '0000-00-00') s3) T;";
$tp_result = $mysqli->query($tp_sql);
$telephone = $tp_result->fetch_assoc();

//편지구역카드
$tl_sql="SELECT * FROM (SELECT
        (SELECT count(DISTINCT tt_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND mb_id = '0' AND ms.ms_id = '{$ms_id}' AND tt_type = '편지') sum,
        (SELECT count(DISTINCT tt_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND mb_id = '0' AND ms.ms_id = '{$ms_id}' AND tt_type = '편지' AND tt_assigned_date = '0000-00-00') s1,
        (SELECT count(DISTINCT tt_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND mb_id = '0' AND ms.ms_id = '{$ms_id}' AND tt_type = '편지' AND tt_assigned_date != '0000-00-00' AND tt_end_date = '0000-00-00') s2,
        (SELECT count(DISTINCT tt_id) FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".TERRITORY_TABLE." tt ON ms.ms_id = tt.ms_id WHERE ms.ma_id = '0' AND mb_id = '0' AND ms.ms_id = '{$ms_id}' AND tt_type = '편지' AND tt_assigned_date != '0000-00-00' AND tt_end_date != '0000-00-00') s3) T;";
$tl_result = $mysqli->query($tl_sql);
$letter = $tl_result->fetch_assoc();
?>

<script type="text/javascript">
  google.charts.load('current', {packages: ['corechart', 'bar', 'controls']});
  google.charts.setOnLoadCallback(drawDashboard);

    function drawDashboard() {

      var chartData = '';

      //날짜형식 변경하고 싶으시면 이 부분 수정하세요.
      var chartDateformat     = 'yy년MM월dd일';
      //라인차트의 라인 수
      var chartLineCount    = 10;
      //컨트롤러 바 차트의 라인 수
      var controlLineCount    = 10;

      var data = new google.visualization.DataTable();
      //그래프에 표시할 컬럼 추가
      data.addColumn('date' , '모임 날짜');
      data.addColumn('number'   , '참여자');

      //그래프에 표시할 데이터
      var dataRow = [];

      <?php
        foreach ($meeting_array as $date => $value) {
          if(!isset($value)) $value = '0';
          $m_year = date("Y", strtotime($date));
          $m_month = date("n", strtotime($date))-1;
          $m_day = date("j", strtotime($date));
          ?>
          dataRow = <?='['?>new Date('<?=$m_year?>', '<?=$m_month?>', '<?=$m_day?>' , '10')<?=', '.$value.']'?>;
          data.addRow(dataRow);
          <?php
        }
       ?>

       var chart = new google.visualization.ChartWrapper({
          chartType   : 'LineChart',
          containerId : 'lineChartArea', //라인 차트 생성할 영역
          options     : {
                          isStacked   : 'percent',
                          focusTarget : 'category',
                          height          : 350,
                          width              : '100%',
                          chartArea: {
                            width: '85%'
                          },
                          legend          : { position: "bottom", textStyle: {fontSize: 13}},
                          pointSize        : 5,
                          tooltip          : {textStyle : {fontSize:12}, showColorCode : true,trigger: 'both'},
                          hAxis              : {format: chartDateformat, gridlines:{count:chartLineCount,units: {
                                                              years : {format: ['yyyy년']},
                                                              months: {format: ['MM월']},
                                                              days  : {format: ['dd일']},
                                                              hours : {format: ['HH시']}}
                                                            },textStyle: {fontSize:12}},
            vAxis              : {minValue: 25,viewWindow:{min:0},gridlines:{count:-1},textStyle:{fontSize:12},format: '#명'},
            animation        : {startup: true,duration: 1000,easing: 'in' },
            annotations    : {pattern: chartDateformat,
                            textStyle: {
                            fontSize: 15,
                            bold: true,
                            italic: true,
                            color: '#871b47',
                            auraColor: '#d799ae',
                            opacity: 0.8,
                            pattern: chartDateformat
                          }
                        }
          }
        });

        var control = new google.visualization.ControlWrapper({
          controlType: 'ChartRangeFilter',
          containerId: 'controlsArea',  //control bar를 생성할 영역
          options: {
              ui:{
                    chartType: 'LineChart',
                    chartOptions: {
                    chartArea: {'width': '65%','height' : 70},
                      hAxis: {'baselineColor': 'none', format: chartDateformat, textStyle: {fontSize:12},
                        gridlines:{count:controlLineCount,units: {
                              years : {format: ['yyyy년']},
                              months: {format: ['MM월']},
                              days  : {format: ['dd일']},
                              hours : {format: ['HH시']}}
                        }}
                    }
              },
                filterColumnIndex: 0
            }
            // 'state': {'range': {'start': new Date(2018, 10, 1), 'end': new Date(2018, 11, 1)}}
        });

        var date_formatter = new google.visualization.DateFormat({ pattern: chartDateformat});
        date_formatter.format(data, 0);

        var dashboard = new google.visualization.Dashboard(document.getElementById('guide_meeting_Controls'));
        window.addEventListener('resize', function() { dashboard.draw(data); }, false); //화면 크기에 따라 그래프 크기 변경
        dashboard.bind([control], [chart]);
        dashboard.draw(data);
    }
</script>

<h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><?=$total_member?></h5>
<div id="guide_meeting_Controls"></div>
<div id="lineChartArea"></div>
<div id="controlsArea"></div>

<div class="row">
  <div class="col-lg-6">
    <?php $type_sum_total_count = isset($type_sum[0])?$type_sum[0]:0;?>
    <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">일반 구역 현황</span></h5>
    <table class="table table-bordered mb-3">
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
            <small class="text-muted">(<?=$type_sum_total_count;?>개)</small>
          </th>
          <?php for ($i=3; $i > 0; $i--):
            $type_sum_count = isset($type_sum[$i])?$type_sum[$i]:0;
            ?>
          <td>
            <div><?=$type_sum_count;?>개</div>
            <small class="text-muted">(<?=get_percent($type_sum_count, $type_sum_total_count).'%';?>)</small>
          </td>
          <?php endfor;?>
        </tr>
        <?php foreach ($type as $key => $value) :
          $type_total_count = ($value[0])?$value[0]:0;?>
        <tr>
          <th scope="row" class="bg-light align-middle">
            <div><?=get_type_text($key)?></div>
            <small class="text-muted">(<?=$type_total_count;?>개)</small>
          </th>
          <?php for ($i=3; $i > 0; $i--):
            $type_count = isset($value[$i])?$value[$i]:0;?>
            <td>
              <div><?=$type_count;?>개</div>
              <small class="text-muted">(<?=get_percent( $type_count, $type_total_count).'%';?>)</small>
            </td>
          <?php endfor;?>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <div class="col-lg-6">
    <div class="row">
      <div class="col-lg-12">
        <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">전화 구역 현황</span></h5>
        <table class="table table-bordered mb-3">
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
            <tr>
              <th scope="row" class="bg-light align-middle">
                <div>전체</div>
                <small class="text-muted">(<?=$telephone['sum'];?>개)</small>
              </th>
              <?php for ($i=3; $i > 0; $i--):?>
                <?php $order = 's'.$i; ?>
                <td>
                  <div><?=$telephone[$order];?>개</div>
                  <small class="text-muted">(<?=get_percent($telephone[$order], $telephone['sum']).'%';?>)</small>
                </td>
              <?php endfor;?>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="col-lg-12">
        <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">편지 구역 현황</span></h5>
        <table class="table table-bordered mb-3">
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
            <tr>
              <th scope="row" class="bg-light align-middle">
                <div>전체</div>
                <small class="text-muted">(<?=$letter['sum'];?>개)</small>
              </th>
              <?php for ($i=3; $i > 0; $i--):?>
                <?php $order = 's'.$i; ?>
                <td>
                  <div><?=$letter[$order];?>개</div>
                  <small class="text-muted">(<?=get_percent($letter[$order], $letter['sum']).'%';?>)</small>
                </td>
              <?php endfor;?>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
