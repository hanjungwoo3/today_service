<?php include_once('../config.php');?>

<?php
if(empty($date) || empty($date2)) exit;

// 변수 초기화
$tt = array();

// 지난 봉사 구역카드 전체 개수
$sql="SELECT tt_type FROM ".TERRITORY_TABLE." WHERE create_datetime <= '{$date2}' AND tt_type = '편지'";
$result = $mysqli->query($sql);
if($result->num_rows > 0){
  while ($ttp = $result->fetch_assoc()){
    if(isset($tt[0])){
      $tt[0]++;
    }else{
      $tt[0] = 1;
    }
  }
}

// 지난 봉사 구역카드 진행률
$sql = "
    SELECT * FROM (
        -- 첫 번째 SELECT
        (SELECT 
            ttr.tt_id AS tt_id, 
            ttr.ttr_assigned_date AS ttr_assigned_date, 
            ttr.ttr_end_date AS ttr_end_date, 
            ttr.ttr_mb_name AS mb 
        FROM ".TERRITORY_TABLE." AS tt 
        INNER JOIN ".TERRITORY_RECORD_TABLE." AS ttr 
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
            AND tt.tt_type = '편지')
        
        UNION
        
        -- 두 번째 SELECT
        (SELECT 
            tt.tt_id AS tt_id, 
            tt.tt_assigned_date AS ttr_assigned_date, 
            tt.tt_end_date AS ttr_end_date, 
            tt.mb_id AS mb 
        FROM ".TERRITORY_TABLE." AS tt
        WHERE 
            ((tt.tt_assigned_date != '0000-00-00' 
                AND tt.tt_assigned_date >= '{$date}' 
                AND tt.tt_assigned_date <= '{$date2}')
             OR 
             (tt.tt_mb_date != '0000-00-00' 
                AND tt.tt_mb_date >= '{$date}' 
                AND tt.tt_mb_date <= '{$date2}'))
            AND tt.create_datetime <= '{$date2}' -- 테이블 명시
            AND tt.tt_type = '편지')
    ) AS T 
    GROUP BY T.tt_id;
";
$result = $mysqli->query($sql);
if($result->num_rows > 0){
  while ($ttr = $result->fetch_assoc()){
    if(!empty_date($ttr['ttr_end_date'])){
      if(isset($tt[3])){
        $tt[3]++;
      }else{
        $tt[3] = 1;
      }
    }else{
      if(isset($tt[2])){
        $tt[2]++;
      }else{
        $tt[2] = 1;
      }
    }
  }
}

if(empty($tt[3])) $tt[3] = 0;
if(empty($tt[2])) $tt[2] = 0;
if(empty($tt[0])) $tt[0] = 0;
$tt[1] = $tt[0] - ($tt[2] + $tt[3]);
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
    <tr>
      <th scope="row" class="bg-light align-middle">
        <div>편지</div>
        <small class="text-muted">(<?=$tt[0];?>개)</small>
      </th>
      <td class="align-middle">
        <div><?=$tt[3];?>개</div>
        <small class="text-muted">(<?=($tt[0] > 0 ? get_percent($tt[3], $tt[0]) : 0).'%';?>)</small>
      </td>
      <td class="align-middle">
        <div><?=$tt[2];?>개</div>
        <small class="text-muted">(<?=($tt[0] > 0 ? get_percent($tt[2], $tt[0]) : 0).'%';?>)</small>
      </td>
      <td class="align-middle">
        <div><?=$tt[1];?>개</div>
        <small class="text-muted">(<?=($tt[0] > 0 ? get_percent($tt[1], $tt[0]) : 0).'%';?>)</small>
      </td>
    </tr>
  </tbody>
</table>

<div class="text-center">
  <button type="button" class="btn btn-outline-info" onclick="location.href='<?=BASE_PATH?>/include/territory_record_excel_download.php?tt_sdate=<?=$date?>&tt_fdate=<?=$date2?>&tt_type=편지'">구역임명기록(<?=$date?>_<?=$date2?>).xlsx</button>
</div>
