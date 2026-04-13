<?php
/**
 * Push 발송/수신 로그 조회
 * URL: /pages/push_log.php?key=비밀키
 */

$DAILY_PUSH_KEY = 'today_service_daily_2026';
if (($_GET['key'] ?? '') !== $DAILY_PUSH_KEY) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

$deliveryLog = __DIR__ . '/../c/storage/push_delivery.log';
$lockFile = __DIR__ . '/../c/storage/push_daily.lock';

echo "======== 최근 cron 실행 결과 ========\n";
if (file_exists($lockFile)) {
    echo file_get_contents($lockFile);
} else {
    echo "(기록 없음)\n";
}

echo "\n\n======== 발송/수신 로그 ========\n";
if (!file_exists($deliveryLog)) {
    echo "(기록 없음)\n";
    exit;
}

$lines = file($deliveryLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// 일수 필터 (?days=N, 기본 7일)
$days = isset($_GET['days']) ? max(1, min(30, intval($_GET['days']))) : 7;
$cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

$sends = array(); // delivery_id => [ts, mb_id, name]
$acks = array();  // delivery_id => ack_ts

foreach ($lines as $l) {
    $parts = explode("\t", $l);
    if (count($parts) < 3) continue;
    if ($parts[0] < $cutoff) continue;
    if ($parts[1] === 'SEND') {
        $sends[$parts[2]] = array($parts[0], $parts[3] ?? '', $parts[4] ?? '');
    } elseif ($parts[1] === 'ACK') {
        $acks[$parts[2]] = $parts[0];
    }
}

// 날짜별 그룹화
$byDate = array();
foreach ($sends as $did => $info) {
    $date = substr($info[0], 0, 10);
    if (!isset($byDate[$date])) $byDate[$date] = array();
    $byDate[$date][] = array(
        'send_ts' => $info[0],
        'mb_id' => $info[1],
        'name' => $info[2],
        'ack_ts' => $acks[$did] ?? null,
        'delivery_id' => $did,
    );
}

krsort($byDate);

echo "최근 {$days}일 요약\n\n";
foreach ($byDate as $date => $items) {
    $sendCount = count($items);
    $ackCount = 0;
    $notAcked = array();
    foreach ($items as $it) {
        if ($it['ack_ts']) {
            $ackCount++;
        } else {
            $notAcked[] = $it['name'] ?: $it['mb_id'];
        }
    }
    echo "[{$date}] 발송 {$sendCount}건 / 실수신 {$ackCount}건";
    if (!empty($notAcked)) {
        echo " / 미수신: " . implode(', ', $notAcked);
    }
    echo "\n";
    foreach ($items as $it) {
        $mark = $it['ack_ts'] ? 'O' : 'X';
        $ackTs = $it['ack_ts'] ? ' → ' . substr($it['ack_ts'], 11, 8) : '';
        echo "  [{$mark}] " . substr($it['send_ts'], 11, 8) . ' ' . ($it['name'] ?: $it['mb_id']) . $ackTs . "\n";
    }
    echo "\n";
}
