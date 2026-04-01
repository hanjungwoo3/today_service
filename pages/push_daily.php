<?php
/**
 * 매일 아침 배정 알림 Push 발송
 * 외부 cron 서비스(cron-job.org 등)에서 호출
 *
 * ■ 매일: 봉사인도 + 오늘 봉사 배정
 * ■ 월요일만: 이번 주 집회 배정 (공개강연/파수대, 평일집회, 마이크/안내인)
 *
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
$isMonday = ((int)date('w') === 1);

// 중복 발송 방지: 오늘 이미 발송했으면 스킵
$lockFile = __DIR__ . '/../c/storage/push_daily.lock';
if (file_exists($lockFile) && trim(file_get_contents($lockFile, false, null, 0, 10)) === $today) {
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

// ══════════════════════════════════════
// 매일: 오늘 봉사 배정 + 봉사인도
// ══════════════════════════════════════

// ── 1. 봉사인도 캘린더 (매일) ──
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

// ══════════════════════════════════════
// 월요일만: 이번 주 집회 배정
// ══════════════════════════════════════

if ($isMonday) {
    // 이번 주 날짜 범위 (월~일)
    $weekStart = date('Y-m-d'); // 오늘 월요일
    $weekEnd = date('Y-m-d', strtotime('+6 days'));

    // ── 3. 공개강연/파수대 (이번 주) ──
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
                $datePrefix = (int)$td->format('n') . '/' . (int)$td->format('j') . '(' . $dayLabels[(int)$td->format('w')] . ') ';
                foreach ($roleLabels as $key => $label) {
                    $name = trim($talk[$key] ?? '');
                    if ($name !== '' && isset($memberMap[$name])) {
                        addNotification($memberMap[$name], $datePrefix . $label);
                    }
                }
            }
        }
    }

    // ── 4. 평일집회 프로그램 (이번 주) ──
    $meetingApiPath = __DIR__ . '/../s/api.php';
    if (file_exists($meetingApiPath)) {
        require_once $meetingApiPath;
        $meetingMgr = new MeetingDataManager();
        $curYear = (int)date('o');
        $curWeek = (int)date('W');
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
            $meetingDateStr = (int)$ws->format('n') . '/' . (int)$ws->format('j') . '(' . $dayLabels[(int)$ws->format('w')] . ') ';

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

    // ── 5. 청소/마이크/안내인 (이번 주 해당 반기) ──
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
    $sql = "SELECT ps_endpoint, ps_auth, ps_p256dh FROM " . PUSH_SUBSCRIPTION_TABLE . "
            WHERE mb_id = " . intval($mb_id);
    $result = $mysqli->query($sql);
    if (!$result || !$result->num_rows) continue;

    if ($isMonday && count($messages) > 1) {
        // 월요일: 여러 배정을 줄바꿈으로 묶어서 발송
        $title = '이번 주 배정 알림';
        $body = implode("\n", $messages);
    } else {
        $title = '오늘의 배정 알림';
        $body = $todayDisplay . '(' . $todayDay . ') ' . implode(', ', $messages);
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
foreach ($webPush->flush() as $report) {
    if ($report->isSubscriptionExpired()) {
        $expired_endpoint = $mysqli->real_escape_string($report->getEndpoint());
        $mysqli->query("DELETE FROM " . PUSH_SUBSCRIPTION_TABLE . " WHERE ps_endpoint = '{$expired_endpoint}'");
    }
}

// 발송 완료 기록
$log = date('Y-m-d H:i:s') . ($isMonday ? ' [월요일 주간알림]' : ' [일일알림]');
$log .= " - sent {$sentCount} push(es) to " . count($notifications) . " member(s)\n";
foreach ($notifications as $mb_id => $messages) {
    $log .= "  " . ($memberNames[$mb_id] ?? $mb_id) . ": " . implode(', ', $messages) . "\n";
}
file_put_contents($lockFile, $log);
echo $log;
