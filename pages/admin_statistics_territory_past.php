<?php include_once('../config.php');?>

<?php
if(empty($date) || empty($date2)) exit;

// 변수 초기화
$tt = array();
$data = array();
$territory = array();

// 지난 봉사 구역카드 전체 개수
$sql="SELECT tt_type FROM ".TERRITORY_TABLE." WHERE create_datetime <= '{$date2}' AND tt_type != '편지'";
$result = $mysqli->query($sql);
if($result->num_rows > 0){
  while ($ttp = $result->fetch_assoc()){
    if(isset($tt[$ttp['tt_type']][0])){
      $tt[$ttp['tt_type']][0]++;
    }else{
      $tt[$ttp['tt_type']][0] = 1;
    }
  }
}

// 지난 봉사 구역카드 진행률
$order = array('일반' => 1, '아파트' => 2, '빌라' => 3, '격지' => 4, '추가1' => 5, '추가2' => 6);
$sql = "
    SELECT * FROM (
        -- 첫 번째 SELECT
        (SELECT 
            ttr.tt_id AS tt_id, 
            ttr.ttr_assigned_date AS ttr_assigned_date, 
            ttr.ttr_end_date AS ttr_end_date, 
            tt.tt_type AS tt_type, 
            ttr.ttr_mb_name AS mb 
        FROM ".TERRITORY_TABLE." tt 
        INNER JOIN ".TERRITORY_RECORD_TABLE." ttr 
            ON tt.tt_id = ttr.tt_id
        WHERE  
            ((ttr.ttr_assigned_date != '0000-00-00' 
                AND ttr.ttr_assigned_date >= '{$date}' 
                AND ttr.ttr_assigned_date <= '{$date2}')
             OR 
             (ttr.ttr_mb_name != '' 
                AND ttr.ttr_start_date >= '{$date}' 
                AND ttr.ttr_start_date <= '{$date2}'))
            AND ttr.create_datetime <= '{$date2}' -- 테이블 명시
            AND tt.tt_type != '편지')
        
        UNION
        
        -- 두 번째 SELECT
        (SELECT 
            tt.tt_id AS tt_id, 
            tt.tt_assigned_date AS ttr_assigned_date, 
            tt.tt_end_date AS ttr_end_date, 
            tt.tt_type AS tt_type, 
            tt.mb_id AS mb 
        FROM ".TERRITORY_TABLE." tt
        WHERE 
            ((tt.tt_assigned_date != '0000-00-00' 
                AND tt.tt_assigned_date >= '{$date}' 
                AND tt.tt_assigned_date <= '{$date2}')
             OR 
             (tt.tt_mb_date != '0000-00-00' 
                AND tt.tt_mb_date >= '{$date}' 
                AND tt.tt_mb_date <= '{$date2}'))
            AND tt.create_datetime <= '{$date2}' -- 테이블 명시
            AND tt.tt_type != '편지')
    ) T 
    GROUP BY T.tt_id;
";
$result = $mysqli->query($sql);
if($result->num_rows > 0){
  while ($ttr = $result->fetch_assoc()){
    if(!empty_date($ttr['ttr_end_date'])){
      if(isset($tt[$ttr['tt_type']][3])){
        $tt[$ttr['tt_type']][3]++;
      }else{
        $tt[$ttr['tt_type']][3] = 1;
      }
    }else{
      if(isset($tt[$ttr['tt_type']][2])){
        $tt[$ttr['tt_type']][2]++;
      }else{
        $tt[$ttr['tt_type']][2] = 1;
      }
    }
  }

  $sum0 = 0;
  $sum1 = 0;
  $sum2 = 0;
  $sum3 = 0;

  foreach ($tt as $tt_type => $con) {
    if(empty($con[3])) $con[3] = 0;
    if(empty($con[2])) $con[2] = 0;
    if(empty($con[0])) $con[0] = 0;
    $con[1] = $con[0] - ($con[2] + $con[3]);
    $sum0 += $con[0];
    $sum1 += $con[1];
    $sum2 += $con[2];
    $sum3 += $con[3];
    if(!empty($order[$tt_type])){
      $data[$order[$tt_type]] = array('name' => get_type_text($tt_type), 's3' => $con[3], 's2' => $con[2], 's1' => $con[1], 's0' => $con[0] );
    }
  }

  ksort($data);
  $territory[] = array('name' => '전체', 's3' => $sum3, 's2' => $sum2, 's1' => $sum1, 's0' => $sum0 );
  $territory = array_merge($territory, $data);
}else{
  $territory[] = array('name' => '전체', 's3' => 0, 's2' => 0, 's1' => 0, 's0' => 0 );
}
?>

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
    <?php foreach ($territory as $key => $value):?>
      <tr>
        <th scope="row" class="bg-light align-middle">
          <div><?=$value['name'];?></div>
          <small class="text-muted">(<?=$value['s0'];?>개)</small>
        </th>
        <td class="align-middle">
          <div><?=$value['s3'];?>개</div>
          <small class="text-muted">(<?=($value['s0'] > 0 ? get_percent($value['s3'], $value['s0']) : 0).'%';?>)</small>
        </td>
        <td class="align-middle">
          <div><?=$value['s2'];?>개</div>
          <small class="text-muted">(<?=($value['s0'] > 0 ? get_percent($value['s2'], $value['s0']) : 0).'%';?>)</small>
        </td>
        <td class="align-middle">
          <div><?=$value['s1'];?>개</div>
          <small class="text-muted">(<?=($value['s0'] > 0 ? get_percent($value['s1'], $value['s0']) : 0).'%';?>)</small>
        </td>
      </tr>
    <?php endforeach;?>
  </tbody>
</table>

<div class="text-center">
  <button type="button" class="btn btn-outline-info" onclick="location.href='<?=BASE_PATH?>/include/territory_record_excel_download.php?tt_sdate=<?=$date?>&tt_fdate=<?=$date2?>'">구역임명기록(<?=$date?>_<?=$date2?>).xlsx</button>
</div>
