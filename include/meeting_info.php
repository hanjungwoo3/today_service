<?php include_once('../config.php'); ?>
<?php
$count = 0;
$volunteered = '';
$mb_id = mb_id();
$today = date('Y-m-d');

$sql = "SELECT 
ms.ms_id, 
COALESCE(m.ms_time,ms.ms_time) AS ms_time,  
COALESCE(m.mp_name,mp.mp_name) AS mp_name, 
mp.mp_address, 
COALESCE(m.ms_type,ms.ms_type) AS ms_type, 
COALESCE(m.m_start_time,ms.ms_start_time) AS ms_start_time,  
COALESCE(m.m_finish_time,ms.ms_finish_time) AS ms_finish_time, 
ms.g_id, 
ms.ms_limit 
FROM 
" . MEETING_SCHEDULE_TABLE . " ms
LEFT JOIN 
" . MEETING_PLACE_TABLE . " mp ON ms.mp_id = mp.mp_id
LEFT JOIN 
" . GROUP_TABLE . " g ON ms.g_id = g.g_id
LEFT JOIN 
" . MEETING_TABLE . " m ON ms.ms_id = m.ms_id 
AND m.m_date = '{$s_date}'
WHERE ms.ms_id = '{$ms_id}'";

$result = $mysqli->query($sql);
$row = $result->fetch_assoc();

$mp_name = $row['g_id'] ? '[' . get_group_name($row['g_id']) . '집단] ' : '';
$mp_name .= $row['mp_name'];
$msw = get_meeting_data(get_meeting_id($s_date, $ms_id));
$mb_ids = isset($msw['mb_id']) ? explode(',', $msw['mb_id']) : array();
$g_name = !empty($msw['g_id']) ? get_group_name($msw['g_id']) : '';

$attend_limit = get_meeting_limit($s_date, $ms_id);

// 지원되있는 전도인이 더이상 데이터베이스에 남아있지 않을때는 보여주지 않음
$member_of_meeting = remove_moveout_mb_id(array_unique(array_filter(explode(',', $msw['mb_id']))));
if ($member_of_meeting)
  $count = count($member_of_meeting);

$member_string = implode(",", $member_of_meeting);
if ($member_string) {
  $mb_sql = "SELECT mb_id, mb_name FROM " . MEMBER_TABLE . " WHERE mb_id IN ({$member_string}) ORDER BY mb_name";
  $mb_result = $mysqli->query($mb_sql);
  $a = 0;
  while ($mb_row = $mb_result->fetch_assoc())
    $volunteered .= '<span class="badge badge' . (($mb_row['mb_id'] == mb_id()) ? '-success' : '-light') . ' p-2 m-1 align-middle">' . $mb_row['mb_name'] . '</span>';
}
?>
<div class="mb-3">
  <small class="badge badge-pill badge-light align-middle"><?= get_meeting_schedule_type_text($row['ms_type']) ?></small>
  <?php if (!empty($g_name))
    echo '<span class="badge badge-pill badge-light text-primary align-middle">집단 봉사⋮' . $g_name . '</span>'; ?>
  <?php if ($msw['m_cancle'] == 1): ?>
    <span class="badge badge-pill badge-light text-danger align-middle">취소됨</span>
  <?php endif; ?>
  <?php if (in_array($mb_id, $mb_ids)): ?>
    <small class="badge badge-pill badge-light text-success align-middle"><i class="bi bi-person-check-fill"></i>
      <?= ($s_date > $today) ? '지원' : '참석' ?></small>
  <?php else: ?>
    <?php if (!empty($attend_limit) && $count >= $attend_limit): ?>
      <small class="badge badge-pill badge-light text-warning align-middle">참여자 마감</small>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php if ($msw['m_cancle'] == 1 && !empty($msw['m_cancle_reason'])): ?>
  <div class="alert alert-danger" role="alert">
    <?= $msw['m_cancle_reason'] ?>
  </div>
<?php endif; ?>

<div class="mb-3">
  <h6 class="text-secondary">일시</h6>
  <div class="">
    <?= get_datetime_text($s_date . ' ' . $row['ms_time']); ?>
    <br>
    <small><?= '(' . get_datetime_text($row['ms_start_time']) . ' ~ ' . get_datetime_text($row['ms_finish_time']) . ')' ?></small>
  </div>
</div>
<div class="mb-3">
  <h6 class=" text-secondary ">장소</h6>
  <div class="">
    <div class="mb-1"><?= $mp_name ?>
      </br>
      <small><?= $row['mp_address'] ?></small>
    </div>
    <button class="btn btn-sm btn-outline-secondary"
      onclick="kakao_navi('<?= DEFAULT_ADDRESS . ' ' . $row['mp_address'] ?>','<?= $row['mp_name'] ?>');">
      <i class="bi bi-cursor"></i> 길찾기
    </button>
  </div>
</div>
<div class="mb-3">
  <h6 class="text-secondary">인도자</h6>
  <?php
  $btn_sm = '';
  if (!empty($msw['m_guide'])) {
    $m_guide = get_guide_data($msw['m_guide']);
    echo '<div><a class="btn btn-sm btn-outline-primary m-1" href="tel:' . $m_guide[0]['hp'] . '"><i class="bi bi-telephone"></i> ' . $m_guide[0]['name'] . '</a></div>';
  } else {
    if ($msw['ms_guide'])
      foreach (get_guide_data($msw['ms_guide']) as $value)
        echo '<a class="btn btn-sm btn-outline-primary m-1" href="tel:' . $value['hp'] . '"><i class="bi bi-telephone"></i> ' . $value['name'] . '</a>';
    if ($msw['ms_guide2'])
      foreach (get_guide_data($msw['ms_guide2']) as $value)
        echo '<a class="btn btn-sm btn-outline-secondary m-1" href="tel:' . $value['hp'] . '"><i class="bi bi-telephone"></i> ' . $value['name'] . '</a>';
  }
  ?>
  <?php if (GUIDE_APPOINT_USE == 'use' && (is_admin($mb_id) || check_include_guide($mb_id, $msw['ms_guide']))): ?>
    <div class="row mt-1">
      <div class="col-7">
        <select class="form-control mr-2" name="guide">
          <option value="">당일 인도자 없음</option><?php echo get_guide_option($msw['m_guide']); ?>
        </select>
      </div>
      <div class="col-5">
        <button class="btn btn-outline-primary" type="button"
          onclick="guide_meeting_work('appoint', '<?= $ms_id ?>', '<?= $s_date ?>', '<?= $page ?>')">모임 임명</button>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php if (SHOW_ATTEND_USE == 'use' || SHOW_ATTEND_USE == ''): ?>
  <div class="mb-3">
    <h6 class="text-secondary">참여자</h6>
    <?= $volunteered ?>
  </div>
<?php endif; ?>
<?php if (is_admin($mb_id) || check_include_guide($mb_id, $msw['ms_guide'])): ?>
  <?php if (empty($msw['m_cancle'])): ?>
    <div class="row mb-3">
      <div class="col-7">
        <select class="form-control mb-1" name="cancle_type">
          <option value="1">취소 알림 노출</option>
          <option value="2">취소 알림 비노출</option>
        </select>
        <input type="text" name="cancle_reason" class="form-control" placeholder="취소사유">
      </div>
      <div class="col-5">
        <button class="btn btn-outline-danger" type="button"
          onclick="guide_meeting_work('','<?= $ms_id ?>','<?= $s_date ?>','<?= $page ?>')">모임 취소</button>
      </div>
    </div>
  <?php else: ?>
    <button class="btn btn-outline-success" type="button"
      onclick="guide_meeting_work('0','<?= $ms_id ?>','<?= $s_date ?>','<?= $page ?>')">모임 복원</button>
  <?php endif; ?>
<?php endif; ?>