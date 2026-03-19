<?php include_once('../config.php'); ?>

<?php
if (empty($date) || empty($date2))
  exit;

// 변수 초기화
$tt = array();
$data = array();
$territory = array();

// 지난 봉사 구역카드 진행률 집계 (엑셀과 동일한 데이터 범위 - 해당 기간 활동 구역 중심)
$order = array('일반' => 1, '아파트' => 2, '빌라' => 3, '격지' => 4, '추가1' => 5, '추가2' => 6);
$sql = "
    SELECT T.tt_id, T.tt_type, MAX(T.is_completed) as is_completed, MAX(T.is_active) as is_active
    FROM (
        SELECT 
            ttr.tt_id, 
            tt.tt_type,
            -- 엑셀 로직에 맞춰 완료 날짜가 있기만 하면 완료로 간주 (기간 종료 체크 제거)
            IF(ttr.ttr_end_date != '0000-00-00', 1, 0) as is_completed,
            1 as is_active
        FROM " . TERRITORY_TABLE . " tt 
        INNER JOIN " . TERRITORY_RECORD_TABLE . " ttr ON tt.tt_id = ttr.tt_id
        WHERE tt.tt_type != '편지' AND tt.create_datetime <= '{$date2}'
          AND ttr.ttr_assigned_date >= '{$date}' AND ttr.ttr_assigned_date <= '{$date2}'
        
        UNION ALL
        
        SELECT 
            tt.tt_id, 
            tt.tt_type,
            -- 엑셀 로직에 맞춰 완료 날짜가 있기만 하면 완료로 간주
            IF(tt.tt_end_date != '0000-00-00', 1, 0) as is_completed,
            1 as is_active
        FROM " . TERRITORY_TABLE . " tt
        WHERE tt.tt_type != '편지' AND tt.create_datetime <= '{$date2}'
          AND tt.tt_assigned_date >= '{$date}' AND tt.tt_assigned_date <= '{$date2}'
    ) T 
    GROUP BY T.tt_id;
";
$result = $mysqli->query($sql);
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $type_key = $row['tt_type'];

    // 이 구역은 해당 기간에 '활동'이 있는 구역이므로 [0](전체) 카운트에 포함하여 엑셀과 일치시킴
    if (isset($tt[$type_key][0]))
      $tt[$type_key][0]++;
    else
      $tt[$type_key][0] = 1;

    if ($row['is_completed'] == 1) {
      if (isset($tt[$type_key][3]))
        $tt[$type_key][3]++;
      else
        $tt[$type_key][3] = 1;
    } else if ($row['is_active'] == 1) {
      if (isset($tt[$type_key][2]))
        $tt[$type_key][2]++;
      else
        $tt[$type_key][2] = 1;
    }
  }

  $sum0 = 0;
  $sum1 = 0;
  $sum2 = 0;
  $sum3 = 0;

  foreach ($tt as $tt_type => $con) {
    if (empty($con[3]))
      $con[3] = 0;
    if (empty($con[2]))
      $con[2] = 0;
    if (empty($con[0]))
      $con[0] = 0;
    $con[1] = $con[0] - ($con[2] + $con[3]);
    if ($con[1] < 0)
      $con[1] = 0;

    $sum0 += $con[0];
    $sum1 += $con[1];
    $sum2 += $con[2];
    $sum3 += $con[3];
    if (!empty($order[$tt_type])) {
      $data[$order[$tt_type]] = array('name' => $tt_type, 's3' => $con[3], 's2' => $con[2], 's1' => $con[1], 's0' => $con[0]);
    }
  }

  ksort($data);
  $territory[] = array('name' => '전체', 's3' => $sum3, 's2' => $sum2, 's1' => $sum1, 's0' => $sum0);
  $territory = array_merge($territory, array_values($data));
} else {
  $territory[] = array('name' => '전체', 's3' => 0, 's2' => 0, 's1' => 0, 's0' => 0);
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
      <th scope="col">진행중</th>
      <th scope="col">진행전</th>
    </tr>
  </thead>
  <tbody class="text-center">
    <?php foreach ($territory as $key => $value): ?>
      <tr>
        <th scope="row" class="bg-light align-middle">
          <div><?= $value['name']; ?></div>
          <small class="text-muted">(<?= $value['s0']; ?>개)</small>
        </th>
        <td class="align-middle">
          <div><?= $value['s3']; ?>개</div>
          <small
            class="text-muted">(<?= ($value['s0'] > 0 ? get_percent($value['s3'], $value['s0']) : 0) . '%'; ?>)</small>
        </td>
        <td class="align-middle">
          <div><?= $value['s2']; ?>개</div>
          <small
            class="text-muted">(<?= ($value['s0'] > 0 ? get_percent($value['s2'], $value['s0']) : 0) . '%'; ?>)</small>
        </td>
        <td class="align-middle">
          <div><?= $value['s1']; ?>개</div>
          <small
            class="text-muted">(<?= ($value['s0'] > 0 ? get_percent($value['s1'], $value['s0']) : 0) . '%'; ?>)</small>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="text-center">
  <button type="button" class="btn btn-outline-info"
    onclick="location.href='<?= BASE_PATH ?>/include/territory_record_excel_download.php?tt_sdate=<?= $date ?>&tt_fdate=<?= $date2 ?>'">구역배정기록(<?= $date ?>_<?= $date2 ?>).xlsx</button>
</div>