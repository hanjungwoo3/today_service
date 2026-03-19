<?php include_once('../config.php'); ?>

<?php
if (empty($date) || empty($date2))
  exit;

// 변수 초기화
$tp = array();

// 지난 봉사 구역카드 진행률 집계 (엑셀과 동일한 데이터 범위 - 해당 기간 활동 구역 중심)
$sql = "
    SELECT T.tp_id, MAX(T.is_completed) as is_completed, MAX(T.is_active) as is_active
    FROM (
        SELECT 
            tpr.tp_id, 
            -- 엑셀 로직에 맞춰 완료 날짜가 있기만 하면 완료로 간주 (기간 종료 체크 제거)
            IF(tpr.tpr_end_date != '0000-00-00', 1, 0) as is_completed,
            1 as is_active
        FROM " . TELEPHONE_TABLE . " tp 
        INNER JOIN " . TELEPHONE_RECORD_TABLE . " tpr ON tp.tp_id = tpr.tp_id
        WHERE tp.create_datetime <= '{$date2}'
          AND tpr.tpr_assigned_date >= '{$date}' AND tpr.tpr_assigned_date <= '{$date2}'
        
        UNION ALL
        
        SELECT 
            tp.tp_id, 
            -- 엑셀 로직에 맞춰 완료 날짜가 있기만 하면 완료로 간주
            IF(tp.tp_end_date != '0000-00-00', 1, 0) as is_completed,
            1 as is_active
        FROM " . TELEPHONE_TABLE . " tp
        WHERE tp.create_datetime <= '{$date2}'
          AND tp.tp_assigned_date >= '{$date}' AND tp.tp_assigned_date <= '{$date2}'
    ) T 
    GROUP BY T.tp_id;
";
$result = $mysqli->query($sql);
$tp[0] = 0; // 초기화
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $tp[0]++; // 해당 기간에 '활동'이 있는 구역이므로 [0](전체) 카운트에 포함

    if ($row['is_completed'] == 1) {
      if (isset($tp[3]))
        $tp[3]++;
      else
        $tp[3] = 1;
    } else if ($row['is_active'] == 1) {
      if (isset($tp[2]))
        $tp[2]++;
      else
        $tp[2] = 1;
    }
  }
}


if (empty($tp[3]))
  $tp[3] = 0;
if (empty($tp[2]))
  $tp[2] = 0;
if (empty($tp[0]))
  $tp[0] = 0;
$tp[1] = $tp[0] - ($tp[2] + $tp[3]);
if ($tp[1] < 0)
  $tp[1] = 0;
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
        <div>전화</div>
        <small class="text-muted">(<?= $tp[0]; ?>개)</small>
      </th>
      <td class="align-middle">
        <div><?= $tp[3]; ?>개</div>
        <small class="text-muted">(<?= ($tp[0] > 0 ? get_percent($tp[3], $tp[0]) : 0) . '%'; ?>)</small>
      </td>
      <td class="align-middle">
        <div><?= $tp[2]; ?>개</div>
        <small class="text-muted">(<?= ($tp[0] > 0 ? get_percent($tp[2], $tp[0]) : 0) . '%'; ?>)</small>
      </td>
      <td class="align-middle">
        <div><?= $tp[1]; ?>개</div>
        <small class="text-muted">(<?= ($tp[0] > 0 ? get_percent($tp[1], $tp[0]) : 0) . '%'; ?>)</small>
      </td>
    </tr>
  </tbody>
</table>

<div class="text-center">
  <button type="button" class="btn btn-outline-info"
    onclick="location.href='<?= BASE_PATH ?>/include/telephone_record_excel_download.php?tp_sdate=<?= $date ?>&tp_fdate=<?= $date2 ?>'">구역임명기록(<?= $date ?>_<?= $date2 ?>).xlsx</button>
</div>