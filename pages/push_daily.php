<?php
/**
 * 매일 아침 배정 알림 Push 발송
 * 외부 cron 서비스(cron-job.org 등)에서 호출
 * URL: /pages/push_daily.php?key=비밀키
 */

// 비밀키 검증
$DAILY_PUSH_KEY = 'today_service_daily_2026';
if (($_GET['key'] ?? '') !== $DAILY_PUSH_KEY && php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

include_once(__DIR__ . '/../config.php');

header('Content-Type: text/plain; charset=utf-8');

$today = date('Y-m-d');
$todayDisplay = (int)date('n') . '월 ' . (int)date('j') . '일';
$dayLabels = array('일', '월', '화', '수', '목', '금', '토');
$todayDay = $dayLabels[(int)date('w')];
$timeLabels = array('새벽', '오전', '오후', '저녁');

// 중복 발송 방지: 오늘 이미 발송했으면 스킵
$lockFile = __DIR__ . '/../c/storage/push_daily_' . $today . '.lock';
if (file_exists($lockFile)) {
    echo "Already sent today.\n";
    exit;
}

// VAPID 키 확인
$vapid_public = get_site_option('vapid_public_key');
$vapid_private = get_site_option('vapid_private_key');
if (!$vapid_public || !$vapid_private) {
    echo "VAPID keys not configured.\n";
    exit;
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    echo "vendor/autoload.php not found.\n";
    exit;
}
require_once $autoload;

// ── 전체 회원 목록 (이름 → mb_id 매핑) ──
$memberMap = array(); // name => mb_id
$memberNames = array(); // mb_id => name
$sql = "SELECT mb_id, mb_name FROM " . MEMBER_TABLE . " WHERE mb_level >= 1";
$result = $mysqli->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $memberMap[trim($row['mb_name'])] = intval($row['mb_id']);
        $memberNames[intval($row['mb_id'])] = trim($row['mb_name']);
    }
}

// 회원별 알림 메시지 수집
$notifications = array(); // mb_id => array of messages

function addNotification($mb_id, $message) {
    global $notifications;
    if (!$mb_id) return;
    if (!isset($notifications[$mb_id])) $notifications[$mb_id] = array();
    $notifications[$mb_id][] = $message;
}

// ── 1. 오늘 봉사 배정 (t_meeting) ──
$sql = "SELECT m.ms_id, m.mb_id, m.m_guide, ms.ms_time, ms.ms_type
        FROM " . MEETING_TABLE . " m
        JOIN " . MEETING_SCHEDULE_TABLE . " ms ON m.ms_id = ms.ms_id
        WHERE m.m_date = '{$today}' AND m.m_cancle = '0' AND m.mb_id != '' AND m.mb_id != '0'";
$result = $mysqli->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $typeLabels = array(1 => '호별봉사', 2 => '전시대', 3 => '공개증거', 4 => '편지봉사');
        $typeLabel = $typeLabels[intval($row['ms_type'])] ?? '봉사';
        $time = substr($row['ms_time'], 0, 5);
        $ids = array_filter(array_map('intval', explode(',', $row['mb_id'])));
        foreach ($ids as $id) {
            addNotification($id, $typeLabel . ' ' . $time);
        }
    }
}

// ── 2. 봉사인도 캘린더 ──
$helpersPath = __DIR__ . '/../c/lib/helpers.php';
if (file_exists($helpersPath)) {
    require_once $helpersPath;
    $calData = loadCalendarData((int)date('Y'), (int)date('n'));
    if (!empty($calData['dates'][$today]['names'])) {
        foreach ($calData['dates'][$today]['names'] as $idx => $name) {
            $name = trim($name);
            if ($name !== '' && isset($memberMap[$name])) {
                addNotification($memberMap[$name], '봉사인도(' . $timeLabels[$idx] . ')');
            }
        }
    }
}

// ── 3. 공개강연/파수대 (talks.json) ──
$talkApiPath = __DIR__ . '/../s/talk_api.php';
if (file_exists($talkApiPath)) {
    require_once $talkApiPath;
    $talkMgr = new TalkDataManager();
    $talkData = $talkMgr->load();
    $roleLabels = array('speaker' => '공개 강연(연사)', 'chairman' => '파수대(사회)', 'reader' => '파수대(낭독)', 'prayer' => '주말집회(기도)');
    if (!empty($talkData['talks'])) {
        foreach ($talkData['talks'] as $talk) {
            if (($talk['date'] ?? '') !== $today) continue;
            foreach ($roleLabels as $key => $label) {
                $name = trim($talk[$key] ?? '');
                if ($name !== '' && isset($memberMap[$name])) {
                    addNotification($memberMap[$name], $label);
                }
            }
        }
    }
}

// ── 4. 청소/마이크/안내인 (duty) ──
$dutyApiPath = __DIR__ . '/../s/duty_api.php';
if (file_exists($dutyApiPath)) {
    require_once $dutyApiPath;
    $dutyMgr = new DutyDataManager();
    $dutyData = $dutyMgr->load((int)date('Y'));
    $month = (string)(int)date('n');
    $day = (int)date('j');
    $monthData = $dutyData['months'][$month] ?? null;
    if ($monthData) {
        $half = ($day <= 15) ? 'first_half' : 'second_half';
        $h = $monthData[$half] ?? array();

        // 상반기/하반기 시작일에만 알림 (1일 또는 16일)
        if ($day === 1 || $day === 16) {
            $dutyRoles = array(
                'mic1' => '마이크', 'mic2' => '마이크', 'mic_assist' => '마이크 보조',
                'att_hall1' => '청중석 안내', 'att_hall2' => '청중석 안내', 'att_entrance' => '출입구 안내'
            );
            foreach ($dutyRoles as $dk => $label) {
                $name = trim($h[$dk] ?? '');
                if ($name !== '' && isset($memberMap[$name])) {
                    addNotification($memberMap[$name], $label . ' (' . $month . '월 ' . ($day <= 15 ? '상' : '하') . '반기)');
                }
            }

            // 음료 (월 단위)
            if ($day === 1) {
                foreach (array('drink_main' => '연사음료', 'drink_assist' => '연사음료 보조') as $dk => $label) {
                    $name = trim($monthData[$dk] ?? '');
                    if ($name !== '' && isset($memberMap[$name])) {
                        addNotification($memberMap[$name], $label . ' (' . $month . '월)');
                    }
                }
                // 청소집단
                $cg = trim($monthData['cleaning_group'] ?? '');
                if ($cg !== '') {
                    // 청소집단 이름이 멤버 이름이 아닌 경우가 있으므로 스킵
                }
            }
        }
    }
}

// ── Push 발송 ──
if (empty($notifications)) {
    echo "No notifications to send today.\n";
    file_put_contents($lockFile, date('Y-m-d H:i:s') . " - no notifications\n");
    exit;
}

$auth = [
    'VAPID' => [
        'subject' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'ys1914.mycafe24.com'),
        'publicKey' => $vapid_public,
        'privateKey' => $vapid_private,
    ],
];

$webPush = new \Minishlink\WebPush\WebPush($auth);

$sentCount = 0;
foreach ($notifications as $mb_id => $messages) {
    // 해당 회원의 push 구독 조회
    $sql = "SELECT ps_endpoint, ps_auth, ps_p256dh FROM " . PUSH_SUBSCRIPTION_TABLE . "
            WHERE mb_id = " . intval($mb_id);
    $result = $mysqli->query($sql);
    if (!$result || !$result->num_rows) continue;

    $body = $todayDisplay . '(' . $todayDay . ') 배정: ' . implode(', ', $messages);
    $payload = json_encode([
        'title' => '오늘의 배정 알림',
        'body' => $body,
        'url' => '/'
    ]);

    while ($sub = $result->fetch_assoc()) {
        $subscription = \Minishlink\WebPush\Subscription::create([
            'endpoint' => $sub['ps_endpoint'],
            'publicKey' => $sub['ps_p256dh'],
            'authToken' => $sub['ps_auth'],
        ]);
        $webPush->queueNotification($subscription, $payload);
        $sentCount++;
    }
}

// 일괄 발송
foreach ($webPush->flush() as $report) {
    if ($report->isSubscriptionExpired()) {
        $expired_endpoint = $mysqli->real_escape_string($report->getEndpoint());
        $mysqli->query("DELETE FROM " . PUSH_SUBSCRIPTION_TABLE . " WHERE ps_endpoint = '{$expired_endpoint}'");
    }
}

// 발송 완료 기록
$log = date('Y-m-d H:i:s') . " - sent {$sentCount} push(es) to " . count($notifications) . " member(s)\n";
foreach ($notifications as $mb_id => $messages) {
    $log .= "  " . ($memberNames[$mb_id] ?? $mb_id) . ": " . implode(', ', $messages) . "\n";
}
file_put_contents($lockFile, $log);
echo $log;
