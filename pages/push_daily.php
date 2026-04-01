<?php
/**
 * 배정 알림 Push 발송 (매일 13시 실행)
 * 외부 cron 서비스(cron-job.org 등)에서 호출
 *
 * ■ 매일: 내일 봉사인도 배정 알림
 * ■ 일요일: 내일(월)~다음주 일요일까지 집회 배정 알림
 *   (공개강연/파수대, 평일집회, 마이크/안내인)
 *
 * URL: /pages/push_daily.php?key=비밀키
 * 테스트: ?key=비밀키&test=mb_id
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
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$tomorrowDt = new DateTime($tomorrow);
$tomorrowDisplay = sprintf('%02d월 %02d일', (int)$tomorrowDt->format('n'), (int)$tomorrowDt->format('j'));
$dayLabels = array('일', '월', '화', '수', '목', '금', '토');
$tomorrowDay = $dayLabels[(int)$tomorrowDt->format('w')];
$timeLabels = array('새벽', '오전', '오후', '저녁');
$isSunday = ((int)date('w') === 0);
$testMbId = isset($_GET['test']) ? intval($_GET['test']) : 0;

// 테스트 모드: lock 무시, 주간 알림도 포함
if (!$testMbId) {
    // 중복 발송 방지: 오늘 이미 발송했으면 스킵
    $lockFile = __DIR__ . '/../c/storage/push_daily.lock';
    if (file_exists($lockFile) && trim(file_get_contents($lockFile, false, null, 0, 10)) === $today) {
        echo "Already sent today.\n";
        exit;
    }
} else {
    $lockFile = null;
    $isSunday = true; // 테스트 시 주간 알림도 포함
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
$sql = "SELECT mb_id, mb_name FROM " . MEMBER_TABLE;
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

// ══════════════════════════════════════
// 매일: 내일 봉사인도 배정
// ══════════════════════════════════════

$helpersPath = __DIR__ . '/../c/lib/helpers.php';
if (file_exists($helpersPath)) {
    require_once $helpersPath;
    $calData = loadCalendarData((int)$tomorrowDt->format('Y'), (int)$tomorrowDt->format('n'));
    if (!empty($calData['dates'][$tomorrow]['names'])) {
        foreach ($calData['dates'][$tomorrow]['names'] as $idx => $name) {
            $name = trim($name);
            if ($name !== '' && isset($memberMap[$name])) {
                addNotification($memberMap[$name], '봉사인도(' . $timeLabels[$idx] . ')');
            }
        }
    }
}

// ══════════════════════════════════════
// 일요일: 내일(월)~다음주 일요일 집회 배정
// ══════════════════════════════════════

if ($isSunday) {
    // 내일(월) ~ 다음주 일요일 (7일간)
    $weekStart = $tomorrow;
    $weekEnd = date('Y-m-d', strtotime('+7 days'));

    // ── 공개강연/파수대 ──
    $talkApiPath = __DIR__ . '/../s/talk_api.php';
    if (file_exists($talkApiPath)) {
        require_once $talkApiPath;
        $talkMgr = new TalkDataManager();
        $talkData = $talkMgr->load();
        $roleLabels = array('speaker' => '공개 강연(연사)', 'chairman' => '파수대(사회)', 'reader' => '파수대(낭독)', 'prayer' => '주말집회(기도)');
        if (!empty($talkData['talks'])) {
            foreach ($talkData['talks'] as $talk) {
                $talkDate = $talk['date'] ?? '';
                if ($talkDate < $weekStart || $talkDate > $weekEnd) continue;
                $td = new DateTime($talkDate);
                $datePrefix = sprintf('%02d월 %02d일(%s) ', (int)$td->format('n'), (int)$td->format('j'), $dayLabels[(int)$td->format('w')]);
                foreach ($roleLabels as $key => $label) {
                    $name = trim($talk[$key] ?? '');
                    if ($name !== '' && isset($memberMap[$name])) {
                        addNotification($memberMap[$name], $datePrefix . $label);
                    }
                }
            }
        }
    }

    // ── 평일집회 프로그램 ──
    $meetingApiPath = __DIR__ . '/../s/api.php';
    if (file_exists($meetingApiPath)) {
        require_once $meetingApiPath;
        $meetingMgr = new MeetingDataManager();
        // 내일(월)이 속한 ISO 주차
        $nextMon = new DateTime($tomorrow);
        $curYear = (int)$nextMon->format('o');
        $curWeek = (int)$nextMon->format('W');
        $wd = $meetingMgr->load($curYear, $curWeek);
        if ($wd && empty($wd['no_meeting'])) {
            $meetingDay = $meetingMgr->getMeetingWeekday();
            // 집회 날짜 계산
            $jan4 = new DateTime($curYear . '-01-04');
            $jan4Day = $jan4->format('N');
            $ws = clone $jan4;
            $ws->modify('-' . ($jan4Day - 1) . ' days');
            $ws->modify('+' . (($curWeek - 1) * 7) . ' days');
            $ws->modify('+' . ($meetingDay - 1) . ' days');
            $meetingDateStr = sprintf('%02d월 %02d일(%s) ', (int)$ws->format('n'), (int)$ws->format('j'), $dayLabels[(int)$ws->format('w')]);

            // 소개말, 시작기도
            $openings = array('opening_remarks' => '소개말', 'opening_prayer' => '시작 기도');
            foreach ($openings as $k => $label) {
                $name = trim($wd['assignments'][$k] ?? '');
                if ($name !== '' && isset($memberMap[$name])) {
                    addNotification($memberMap[$name], $meetingDateStr . '평일집회 ' . $label);
                }
            }

            // 프로그램 항목
            if (!empty($wd['program'])) {
                foreach ($wd['program'] as $item) {
                    $assignedNames = array();
                    if (is_array($item['assigned'])) {
                        $assignedNames = $item['assigned'];
                    } elseif (!empty($item['assigned'])) {
                        $assignedNames = array($item['assigned']);
                    }
                    foreach ($assignedNames as $an) {
                        $an = trim($an);
                        if ($an !== '' && isset($memberMap[$an])) {
                            $title = mb_substr($item['title'] ?? '', 0, 20);
                            addNotification($memberMap[$an], $meetingDateStr . $title);
                        }
                    }
                }
            }

            // 맺음말, 마치는기도
            $closings = array('closing_remarks' => '맺음말', 'closing_prayer' => '마치는 기도');
            foreach ($closings as $k => $label) {
                $name = trim($wd['assignments'][$k] ?? '');
                if ($name !== '' && isset($memberMap[$name])) {
                    addNotification($memberMap[$name], $meetingDateStr . '평일집회 ' . $label);
                }
            }
        }
    }

    // ── 청소/마이크/안내인 ──
    $dutyApiPath = __DIR__ . '/../s/duty_api.php';
    if (file_exists($dutyApiPath)) {
        require_once $dutyApiPath;
        $dutyMgr = new DutyDataManager();
        // 내일(월) 기준 반기
        $dutyDt = new DateTime($tomorrow);
        $dutyData = $dutyMgr->load((int)$dutyDt->format('Y'));
        $month = (string)(int)$dutyDt->format('n');
        $day = (int)$dutyDt->format('j');
        $monthData = $dutyData['months'][$month] ?? null;
        if ($monthData) {
            $half = ($day <= 15) ? 'first_half' : 'second_half';
            $halfLabel = $month . '월 ' . ($day <= 15 ? '상' : '하') . '반기';
            $h = $monthData[$half] ?? array();

            $dutyRoles = array(
                'mic1' => '마이크', 'mic2' => '마이크', 'mic_assist' => '마이크 보조',
                'att_hall1' => '청중석 안내', 'att_hall2' => '청중석 안내', 'att_entrance' => '출입구 안내'
            );
            foreach ($dutyRoles as $dk => $label) {
                $name = trim($h[$dk] ?? '');
                if ($name !== '' && isset($memberMap[$name])) {
                    addNotification($memberMap[$name], $label . '(' . $halfLabel . ')');
                }
            }

            // 음료
            foreach (array('drink_main' => '연사음료', 'drink_assist' => '연사음료 보조') as $dk => $label) {
                $name = trim($monthData[$dk] ?? '');
                if ($name !== '' && isset($memberMap[$name])) {
                    addNotification($memberMap[$name], $label . '(' . $month . '월)');
                }
            }
        }
    }
}

// ══════════════════════════════════════
// Push 발송
// ══════════════════════════════════════

if (empty($notifications) || ($testMbId && !isset($notifications[$testMbId]))) {
    echo ($testMbId ? "[TEST] " : "") . "No notifications to send today.\n";
    if ($lockFile) file_put_contents($lockFile, date('Y-m-d H:i:s') . " - no notifications\n");
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
// 테스트 모드: 해당 회원만 발송
if ($testMbId) {
    $notifications = array_intersect_key($notifications, [$testMbId => true]);
}
foreach ($notifications as $mb_id => $messages) {
    $sql = "SELECT ps_endpoint, ps_auth, ps_p256dh FROM " . PUSH_SUBSCRIPTION_TABLE . "
            WHERE mb_id = " . intval($mb_id);
    $result = $mysqli->query($sql);
    if (!$result || !$result->num_rows) continue;

    // 봉사인도(내일)와 주간 알림 분리
    $dailyMsgs = array_filter($messages, function($m) { return strpos($m, '봉사인도') === 0; });
    $weeklyMsgs = array_filter($messages, function($m) { return strpos($m, '봉사인도') !== 0; });

    if (!empty($dailyMsgs) && !empty($weeklyMsgs)) {
        $title = '배정 알림';
        $body = $tomorrowDisplay . '(' . $tomorrowDay . ') ' . implode(', ', $dailyMsgs) . "\n" . implode("\n", $weeklyMsgs);
    } elseif (!empty($weeklyMsgs)) {
        $title = '이번 주 배정 알림';
        $body = implode("\n", $weeklyMsgs);
    } else {
        $title = '내일 배정 알림';
        $body = $tomorrowDisplay . '(' . $tomorrowDay . ') ' . implode(', ', $dailyMsgs);
    }

    $payload = json_encode([
        'title' => $title,
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
$successCount = 0;
$failCount = 0;
foreach ($webPush->flush() as $report) {
    if ($report->isSuccess()) {
        $successCount++;
    } else {
        $failCount++;
        if ($report->isSubscriptionExpired()) {
            $expired_endpoint = $mysqli->real_escape_string($report->getEndpoint());
            $mysqli->query("DELETE FROM " . PUSH_SUBSCRIPTION_TABLE . " WHERE ps_endpoint = '{$expired_endpoint}'");
        }
    }
}

// 발송 완료 기록
$log = date('Y-m-d H:i:s') . ($isSunday ? ' [일요일 주간알림]' : ' [일일알림]');
$log .= " - queued:{$sentCount} success:{$successCount} fail:{$failCount} members:" . count($notifications) . "\n";
foreach ($notifications as $mb_id => $messages) {
    $log .= "  " . ($memberNames[$mb_id] ?? $mb_id) . ": " . implode(', ', $messages) . "\n";
}
if ($lockFile) file_put_contents($lockFile, $log);
if ($testMbId) $log = "[TEST mb_id={$testMbId}] " . $log;
echo $log;
