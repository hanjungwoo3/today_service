<?php include_once('../config.php');?>

<?php
$m_id = get_meeting_id($s_date, $ms_id);

if($work == 'appoint'){
  $sql = "UPDATE ".MEETING_TABLE." SET m_guide = '{$guide}' WHERE m_id = '{$m_id}'";
}else{
  if($work == '0') $reason = '';
  $sql = "UPDATE ".MEETING_TABLE." SET m_cancle = '{$work}', m_cancle_reason = '{$reason}' WHERE m_id = '{$m_id}'";
}

$mysqli->query($sql);

// 모임 취소 시 참여자에게 Push 알림 발송 (work=1 또는 2: 취소, work=0: 복원은 제외)
if ($work !== 'appoint' && $work != '0') {
  _send_cancle_push($m_id, $s_date, $ms_id, $reason);
}

function _send_cancle_push($m_id, $s_date, $ms_id, $reason) {
  global $mysqli;

  $vapid_public = get_site_option('vapid_public_key');
  $vapid_private = get_site_option('vapid_private_key');
  if (!$vapid_public || !$vapid_private) return;

  $autoload = __DIR__ . '/../vendor/autoload.php';
  if (!file_exists($autoload)) return;
  require_once $autoload;

  // 참여자 조회
  $meeting = get_meeting_data($m_id);
  if (empty($meeting['mb_id'])) return;
  $member_ids = array_filter(array_map('intval', explode(',', $meeting['mb_id'])));
  if (empty($member_ids)) return;

  // 모임 시간/장소
  $ms_time = substr($meeting['ms_time'] ?? '', 0, 5);
  $mp_name = $meeting['mp_name'] ?? '';

  // 날짜 포맷
  $d = new DateTime($s_date);
  $dayLabels = array('일', '월', '화', '수', '목', '금', '토');
  $dateStr = sprintf('%02d월 %02d일(%s)', (int)$d->format('n'), (int)$d->format('j'), $dayLabels[(int)$d->format('w')]);

  $title = '봉사 모임 취소';
  $body = $dateStr . ' ' . $ms_time . ' ' . $mp_name;
  if (!empty(trim($reason))) {
    $body .= "\n사유: " . mb_substr(trim($reason), 0, 100);
  }

  // Push 구독 조회
  $ids_str = implode(',', $member_ids);
  $sql = "SELECT ps_endpoint, ps_auth, ps_p256dh FROM " . PUSH_SUBSCRIPTION_TABLE . "
          WHERE mb_id IN ({$ids_str})";
  $result = $mysqli->query($sql);
  if (!$result || !$result->num_rows) return;

  $auth = [
    'VAPID' => [
      'subject' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
      'publicKey' => $vapid_public,
      'privateKey' => $vapid_private,
    ],
  ];

  $webPush = new \Minishlink\WebPush\WebPush($auth);
  $payload = json_encode(['title' => $title, 'body' => $body, 'url' => '/']);

  while ($sub = $result->fetch_assoc()) {
    $subscription = \Minishlink\WebPush\Subscription::create([
      'endpoint' => $sub['ps_endpoint'],
      'publicKey' => $sub['ps_p256dh'],
      'authToken' => $sub['ps_auth'],
    ]);
    $webPush->queueNotification($subscription, $payload);
  }

  foreach ($webPush->flush() as $report) {
    if ($report->isSubscriptionExpired()) {
      $expired = $mysqli->real_escape_string($report->getEndpoint());
      $mysqli->query("DELETE FROM " . PUSH_SUBSCRIPTION_TABLE . " WHERE ps_endpoint = '{$expired}'");
    }
  }
}
?>
