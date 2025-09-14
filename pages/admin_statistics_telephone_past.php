<?php include_once('../config.php');?>

<?php
if(empty($date) || empty($date2)) exit;

// 변수 초기화
$tp = array();

// 지난 봉사 구역카드 전체 개수
$sql="SELECT count(tp_id) as count FROM ".TELEPHONE_TABLE;
$result = $mysqli->query($sql);
if($result->num_rows > 0){
  $telephone = $result->fetch_assoc();
  $tp[0] = $telephone['count'];
}

// 지난 봉사 구역카드 진행률
$sql="SELECT * FROM (
      (SELECT tpr.tp_id as tp_id, tpr.tpr_assigned_date as tpr_assigned_date, tpr.tpr_end_date as tpr_end_date, tpr_mb_name as mb FROM ".TELEPHONE_TABLE." tp INNER JOIN ".TELEPHONE_RECORD_TABLE." tpr ON tp.tp_id = tpr.tp_id
      WHERE ((tpr_assigned_date != '0000-00-00' AND tpr_assigned_date >= '{$date}' AND tpr_assigned_date <= '{$date2}') OR (tpr_mb_name != '' AND tpr_start_date >= '{$date}' AND tpr_start_date <= '{$date2}')))
      UNION
      (SELECT tp_id, tp_assigned_date as tpr_assigned_date, tp_end_date as tpr_end_date, mb_id as mb FROM ".TELEPHONE_TABLE."
      WHERE ((tp_assigned_date != '0000-00-00' AND tp_assigned_date >= '{$date}' AND tp_assigned_date <= '{$date2}') OR (tp_mb_date != '0000-00-00' AND tp_mb_date >= '{$date}' AND tp_mb_date <= '{$date2}')))
    ) T GROUP BY T.tp_id;";
$result = $mysqli->query($sql);
if($result->num_rows > 0){
  while ($tpr = $result->fetch_assoc()){
    if(!empty_date($tpr['tpr_end_date'])){
      if(isset($tp[3])){
        $tp[3]++;
      }else{
        $tp[3] = 1;
      }
    }else{
      if(isset($tp[2])){
        $tp[2]++;
      }else{
        $tp[2] = 1;
      }
    }
  }
}

if(empty($tp[3])) $tp[3] = 0;
if(empty($tp[2])) $tp[2] = 0;
if(empty($tp[0])) $tp[0] = 0;
$tp[1] = $tp[0] - ($tp[2] + $tp[3]);
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
        <div>전화</div>
        <small class="text-muted">(<?=$tp[0];?>개)</small>
      </th>
      <td class="align-middle">
        <div><?=$tp[3];?>개</div>
        <small class="text-muted">(<?=($tp[0] > 0 ? get_percent($tp[3], $tp[0]) : 0).'%';?>)</small>
      </td>
      <td class="align-middle">
        <div><?=$tp[2];?>개</div>
        <small class="text-muted">(<?=($tp[0] > 0 ? get_percent($tp[2], $tp[0]) : 0).'%';?>)</small>
      </td>
      <td class="align-middle">
        <div><?=$tp[1];?>개</div>
        <small class="text-muted">(<?=($tp[0] > 0 ? get_percent($tp[1], $tp[0]) : 0).'%';?>)</small>
      </td>
    </tr>
  </tbody>
</table>

<div class="text-center">
  <button type="button" class="btn btn-outline-info" onclick="location.href='<?=BASE_PATH?>/include/telephone_record_excel_download.php?tp_sdate=<?=$date?>&tp_fdate=<?=$date2?>'">구역임명기록(<?=$date?>_<?=$date2?>).xlsx</button>
</div>
