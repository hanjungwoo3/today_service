<?php include_once('../config.php'); ?>

<?php
if (empty($date) || empty($date2))
  exit;

// 변수 초기화
$tt = array();

// 지난 봉사 구역카드 진행률 집계 (엑셀과 동일한 데이터 범위 - 해당 기간 활동 구역 중심)
$sql = "
    SELECT T.tt_id, MAX(T.is_completed) as is_completed, MAX(T.is_in_progress) as is_in_progress
    FROM (
        SELECT 
            ttr.tt_id, 
            -- 엑셀 로직에 맞춰 완료 날짜가 있기만 하면 완료로 간주
            IF(ttr.ttr_end_date != '0000-00-00', 1, 0) as is_completed,
            IF((ttr.ttr_assigned_date != '0000-00-00' AND ttr.ttr_assigned_date >= '{$date}' AND ttr.ttr_assigned_date <= '{$date2}') OR (ttr.ttr_mb_name != '' AND ttr.ttr_start_date >= '{$date}' AND ttr.ttr_start_date <= '{$date2}'), 1, 0) as is_in_progress
        FROM " . TERRITORY_TABLE . " AS tt 
        INNER JOIN " . TERRITORY_RECORD_TABLE . " AS ttr ON tt.tt_id = ttr.tt_id
        WHERE tt.tt_type = '편지' AND tt.create_datetime <= '{$date2}'
          AND ttr.ttr_assigned_date >= '{$date}' AND ttr.ttr_assigned_date <= '{$date2}'
        
        UNION ALL
        
        SELECT 
            tt.tt_id, 
            IF(tt.tt_end_date != '0000-00-00', 1, 0) as is_completed,
            IF((tt.tt_assigned_date != '0000-00-00' AND tt.tt_assigned_date >= '{$date}' AND tt.tt_assigned_date <= '{$date2}') OR (tt.tt_mb_date != '0000-00-00' AND tt.tt_mb_date >= '{$date}' AND tt.tt_mb_date <= '{$date2}'), 1, 0) as is_in_progress
        FROM " . TERRITORY_TABLE . " AS tt
        WHERE tt.tt_type = '편지' AND tt.create_datetime <= '{$date2}'
          AND tt.tt_assigned_date >= '{$date}' AND tt.tt_assigned_date <= '{$date2}'
    ) AS T 
    GROUP BY T.tt_id;
";
$result = $mysqli->query($sql);
$tt[0] = 0; // 초기화
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $tt[0]++; // 해당 기간 활동 구역 합계

    if ($row['is_completed'] == 1) {
      if (isset($tt[3]))
        $tt[3]++;
      else
        $tt[3] = 1;
    } else if ($row['is_in_progress'] == 1) {
      if (isset($tt[2]))
        $tt[2]++;
      else
        $tt[2] = 1;
    }
  }
}


if (empty($tt[3]))
  $tt[3] = 0;
if (empty($tt[2]))
  $tt[2] = 0;
if (empty($tt[0]))
  $tt[0] = 0;
$tt[1] = $tt[0] - ($tt[2] + $tt[3]);
if ($tt[1] < 0)
  $tt[1] = 0;
?>

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
        <div>편지</div>
        <small class="text-muted">(<?= $tt[0]; ?>개)</small>
      </th>
      <td class="align-middle">
        <div><?= $tt[3]; ?>개</div>
        <small class="text-muted">(<?= ($tt[0] > 0 ? get_percent($tt[3], $tt[0]) : 0) . '%'; ?>)</small>
      </td>
      <td class="align-middle">
        <div><?= $tt[2]; ?>개</div>
        <small class="text-muted">(<?= ($tt[0] > 0 ? get_percent($tt[2], $tt[0]) : 0) . '%'; ?>)</small>
      </td>
      <td class="align-middle">
        <div><?= $tt[1]; ?>개</div>
        <small class="text-muted">(<?= ($tt[0] > 0 ? get_percent($tt[1], $tt[0]) : 0) . '%'; ?>)</small>
      </td>
    </tr>
  </tbody>
</table>

<div class="text-center">
  <button type="button" class="btn btn-outline-info"
    onclick="location.href='<?= BASE_PATH ?>/include/territory_record_excel_download.php?tt_sdate=<?= $date ?>&tt_fdate=<?= $date2 ?>&tt_type=편지'">구역임명기록(<?= $date ?>_<?= $date2 ?>).xlsx</button>
</div>