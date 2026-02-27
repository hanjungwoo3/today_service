<?php
// 서비스 워커 캐시 방지
header('Cache-Control: no-cache, no-store, must-revalidate');

date_default_timezone_set('Asia/Seoul');

// 로그인한 사용자 정보
$loggedInUserName = '';
$is_elder = false;
if (file_exists(dirname(__FILE__) . '/../config.php')) {
    @require_once dirname(__FILE__) . '/../config.php';
    if (function_exists('mb_id') && function_exists('get_member_name')) {
        $mbId = mb_id();
        if (!empty($mbId)) {
            $loggedInUserName = get_member_name($mbId);
        }
    }
    if (function_exists('mb_id') && function_exists('get_member_position')) {
        $is_elder = (get_member_position(mb_id()) >= '2');
    }
}

require_once dirname(__FILE__) . '/talk_api.php';
require_once dirname(__FILE__) . '/duty_api.php';

$manager = new TalkDataManager();
$data = $manager->load();
$talks = $data['talks'];

$embed = isset($_GET['embed']) ? (int)$_GET['embed'] : 0;

// 표시 시작점: 관리자 설정값 또는 지난주 일요일
$displayStartDate = isset($data['display_start_date']) ? $data['display_start_date'] : '';
if (!empty($displayStartDate)) {
    $startDate = $displayStartDate;
} else {
    $now = new DateTime();
    $dayOfWeek = (int)$now->format('w');
    $lastSunday = clone $now;
    if ($dayOfWeek === 0) {
        $lastSunday->modify('-7 days');
    } else {
        $lastSunday->modify('-' . $dayOfWeek . ' days');
        $lastSunday->modify('-7 days');
    }
    $startDate = $lastSunday->format('Y-m-d');
}

// 표시할 강연 필터
$visibleTalks = array();
foreach ($talks as $talk) {
    if ($talk['date'] >= $startDate) {
        $visibleTalks[] = $talk;
    }
}

$today = (new DateTime())->format('Y-m-d');

// duty 데이터 로드 (연도별 캐시)
$dutyManager = new DutyDataManager();
$dutyByYear = array();
foreach ($visibleTalks as $talk) {
    $year = (int)substr($talk['date'], 0, 4);
    if (!isset($dutyByYear[$year])) {
        $dutyByYear[$year] = $dutyManager->load($year);
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>공개 강연 계획표</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Malgun Gothic', sans-serif;
            background: #f5f5f5;
            color: #333;
            font-size: 16px;
        }
        .container {
            max-width: 1024px;
            margin: 0 auto;
            padding: 10px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            margin-bottom: 8px;
        }
        .page-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }
        .header-actions {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        .header-btn {
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: white;
            color: #555;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
        }
        .header-btn:hover { background: #f0f0f0; }

        /* 테이블 */
        .talk-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .talk-table th {
            background: #4CAF50;
            color: white;
            padding: 10px 6px;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
        }
        .talk-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #e8e8e8;
            font-size: 16px;
            vertical-align: top;
        }
        .talk-table tr:last-child td { border-bottom: none; }
        .talk-table tr:hover { background: #f9f9f9; }

        .col-date { width: 1%; text-align: center; white-space: nowrap; }
        .date-short { display: none; }
        .col-speaker { width: 1%; text-align: center; white-space: nowrap; }
        .col-congregation { width: 1%; text-align: center; white-space: nowrap; }
        .col-topic { }
        .col-chairman { width: 1%; text-align: center; white-space: nowrap; }
        .col-reader { width: 1%; text-align: center; white-space: nowrap; }
        .col-prayer { width: 1%; text-align: center; white-space: nowrap; }
        .date-text { font-weight: 600; }
        .my-name {
            background: linear-gradient(135deg, #ef4444, #f97316);
            color: white;
            padding: 1px 6px;
            border-radius: 3px;
            font-weight: 700;
        }
        tr.row-circuit { background: #e8f5e9; }
        tr.row-special { background: #fff3e0; }
        .topic-label {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .topic-label.circuit { background: #43a047; color: white; }
        .topic-label.special { background: #ef6c00; color: white; }
        .topic-text {
            display: block;
            line-height: 1.4;
        }
        .mobile-speaker { display: none; }
        .desktop-only { }
        .mobile-only-label { display: none; }
        .past-row { opacity: 0.5; }
        .next-row td { border-top: 2px solid #ef4444; }
        .next-row td:first-child { border-left: 2px solid #ef4444; }
        .next-row td:last-child { border-right: 2px solid #ef4444; }
        .next-row:not(:has(+ .duty-sub)) td { border-bottom: 2px solid #ef4444; }
        /* duty 서브행 */
        .duty-sub td {
            padding: 2px 6px 6px;
            font-size: 15px;
            color: #555;
            background: #f8f8f8;
            border-bottom: 2px solid #ef4444;
            border-left: 2px solid #ef4444;
            border-right: 2px solid #ef4444;
        }
        .duty-sub:hover td { background: #f0f0f0; }
        .duty-label { color: #888; margin-right: 2px; }
        .duty-label::after { content: ':'; }
        .duty-assist { color: #999; font-size: 14px; }
        .duty-sep { color: #ccc; margin: 0 6px; }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 17px;
        }
        .bottom-actions {
            margin-top: 16px;
            border-top: 1px solid #e0e0e0;
            padding-top: 12px;
        }
        .action-card {
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .action-card.normal { background: #f8f9ff; border: 1px solid #e0e0e0; }
        .action-card-title {
            font-weight: 600;
            font-size: 16px;
            color: #333;
            margin-bottom: 6px;
        }
        .action-card-desc {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .action-card-btn {
            width: 100%;
            display: block;
            text-align: center;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .action-card-btn.admin { background: #e0e0e0; color: #333; }
        .action-card-btn.admin:hover { background: #d5d5d5; }
        #newWindowGroup { display: none; }

        .table-scroll-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 12px;
        }
        @media (max-width: 768px) {
            .container { padding: 6px; }
            .talk-table { font-size: 14px; min-width: 0; width: 100%; }
            .talk-table th { padding: 6px 3px; font-size: 13px; }
            .talk-table td { padding: 5px 3px; font-size: 15px; }
            .col-date { width: 1%; white-space: nowrap; }
            .date-full { display: none; }
            .date-short { display: inline; }
            .date-text { font-weight: normal; }
            .col-speaker, .col-congregation { display: none; }
            .col-chairman, .col-reader, .col-prayer { width: 1%; white-space: nowrap; }
            .mobile-speaker { display: block; font-weight: normal; margin-bottom: 2px; color: #555; }
            .topic-text { font-weight: 700; }
            .desktop-only { display: none !important; }
            .mobile-only-label { display: inline-block !important; }
            .duty-sub td { font-size: 14px; padding: 2px 3px 5px; white-space: normal; }
            .duty-sep { margin: 0 3px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="table-scroll-wrap">
    <table class="talk-table">
        <thead>
            <tr>
                <th class="col-date">일자</th>
                <th class="col-speaker">연사</th>
                <th class="col-congregation">회중</th>
                <th class="col-topic">연제</th>
                <th class="col-chairman">사회</th>
                <th class="col-reader">낭독</th>
                <th class="col-prayer">기도</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($visibleTalks)): ?>
                <tr><td colspan="7" class="empty-state">등록된 강연 일정이 없습니다.</td></tr>
            <?php else: ?>
                <?php $nextFound = false; ?>
                <?php foreach ($visibleTalks as $talk):
                    $isPast = $talk['date'] < $today;
                    $isNext = false;
                    if (!$isPast && !$nextFound) {
                        $isNext = true;
                        $nextFound = true;
                    }
                    $d = new DateTime($talk['date']);
                    $dateDisplayFull = $d->format('y/m/d');
                    $dateDisplayShort = $d->format('n/j');
                    $rowClass = '';
                    if ($talk['topic_type'] === 'circuit_visit') $rowClass = 'row-circuit';
                    elseif ($talk['topic_type'] === 'special_talk') $rowClass = 'row-special';
                ?>
                <tr class="<?php echo $isPast ? 'past-row' : ''; ?> <?php echo $isNext ? 'next-row' : ''; ?> <?php echo $rowClass; ?>">
                    <td class="col-date"><span class="date-text"><span class="date-full"><?php echo $dateDisplayFull; ?></span><span class="date-short"><?php echo $dateDisplayShort; ?></span></span></td>
                    <td class="col-speaker">
                        <?php if (!empty(trim($talk['speaker'])) && trim($talk['speaker']) === $loggedInUserName): ?>
                            <span class="my-name"><?php echo htmlspecialchars($talk['speaker']); ?></span>
                        <?php else: ?>
                            <?php echo htmlspecialchars($talk['speaker']); ?>
                        <?php endif; ?>
                    </td>
                    <td class="col-congregation"><?php echo htmlspecialchars($talk['congregation']); ?></td>
                    <td class="col-topic">
                        <span class="desktop-only"><?php
                            if ($talk['topic_type'] === 'circuit_visit') echo '<span class="topic-label circuit">순회 방문</span>';
                            elseif ($talk['topic_type'] === 'special_talk') echo '<span class="topic-label special">특별 강연</span>';
                        ?></span>
                        <span class="topic-text"><?php
                            if ($talk['topic_type'] === 'circuit_visit') echo '<span class="topic-label circuit mobile-only-label">순회 방문</span> ';
                            elseif ($talk['topic_type'] === 'special_talk') echo '<span class="topic-label special mobile-only-label">특별 강연</span> ';
                            echo htmlspecialchars($talk['topic']);
                        ?></span>
                        <span class="mobile-speaker"><?php
                            $sp = trim($talk['speaker']); $cg = trim($talk['congregation']);
                            if (!empty($sp)) {
                                if ($sp === $loggedInUserName) echo '<span class="my-name">' . htmlspecialchars($sp) . '</span>';
                                else echo htmlspecialchars($sp);
                                if (!empty($cg)) echo '(' . htmlspecialchars($cg) . ')';
                            }
                        ?></span>
                    </td>
                    <td class="col-chairman">
                        <?php if (!empty(trim($talk['chairman'])) && trim($talk['chairman']) === $loggedInUserName): ?>
                            <span class="my-name"><?php echo htmlspecialchars($talk['chairman']); ?></span>
                        <?php else: ?>
                            <?php echo htmlspecialchars($talk['chairman']); ?>
                        <?php endif; ?>
                    </td>
                    <td class="col-reader">
                        <?php if (!empty(trim($talk['reader'])) && trim($talk['reader']) === $loggedInUserName): ?>
                            <span class="my-name"><?php echo htmlspecialchars($talk['reader']); ?></span>
                        <?php else: ?>
                            <?php echo htmlspecialchars($talk['reader']); ?>
                        <?php endif; ?>
                    </td>
                    <td class="col-prayer">
                        <?php if (!empty(trim($talk['prayer'])) && trim($talk['prayer']) === $loggedInUserName): ?>
                            <span class="my-name"><?php echo htmlspecialchars($talk['prayer']); ?></span>
                        <?php else: ?>
                            <?php echo htmlspecialchars($talk['prayer']); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php
                    // duty 서브행 — 다가오는 주(next-row)만 표시
                    if ($isNext):
                        $tYear = (int)$d->format('Y');
                        $tMonth = (string)(int)$d->format('m');
                        $tDay = (int)$d->format('d');
                        $dutyMonth = isset($dutyByYear[$tYear]['months'][$tMonth]) ? $dutyByYear[$tYear]['months'][$tMonth] : null;
                        if ($dutyMonth):
                            $half = ($tDay <= 15) ? 'first_half' : 'second_half';
                            $h = isset($dutyMonth[$half]) ? $dutyMonth[$half] : array();

                            // 이름 하이라이트 헬퍼
                            $hl = function($name) use ($loggedInUserName) {
                                $name = trim($name);
                                if ($name === '') return '';
                                if ($name === $loggedInUserName) return '<span class="my-name">' . htmlspecialchars($name) . '</span>';
                                return htmlspecialchars($name);
                            };

                            $parts = array();

                            // 청소
                            $cg = trim($dutyMonth['cleaning_group'] ?? '');
                            if ($cg !== '') $parts[] = '<span class="duty-label">청소</span>' . $hl($cg);

                            // 음료: 주(보조)
                            $dm = trim($dutyMonth['drink_main'] ?? '');
                            $da = trim($dutyMonth['drink_assist'] ?? '');
                            if ($dm !== '') {
                                $drinkText = '<span class="duty-label">음료</span>' . $hl($dm);
                                if ($da !== '') $drinkText .= '<span class="duty-assist">(' . $hl($da) . ')</span>';
                                $parts[] = $drinkText;
                            }

                            // 마이크: 주1,주2(보조)
                            $m1 = trim($h['mic1'] ?? '');
                            $m2 = trim($h['mic2'] ?? '');
                            $ma = trim($h['mic_assist'] ?? '');
                            if ($m1 !== '' || $m2 !== '') {
                                $micText = '<span class="duty-label">마이크</span>';
                                $micMain = array();
                                if ($m1 !== '') $micMain[] = $hl($m1);
                                if ($m2 !== '') $micMain[] = $hl($m2);
                                $micText .= implode(',', $micMain);
                                if ($ma !== '') $micText .= '<span class="duty-assist">(' . $hl($ma) . ')</span>';
                                $parts[] = $micText;
                            }

                            // 청중석
                            $ah1 = trim($h['att_hall1'] ?? '');
                            $ah2 = trim($h['att_hall2'] ?? '');
                            if ($ah1 !== '' || $ah2 !== '') {
                                $hallMain = array();
                                if ($ah1 !== '') $hallMain[] = $hl($ah1);
                                if ($ah2 !== '') $hallMain[] = $hl($ah2);
                                $parts[] = '<span class="duty-label">청중석</span>' . implode(',', $hallMain);
                            }

                            // 출입구
                            $ae = trim($h['att_entrance'] ?? '');
                            if ($ae !== '') $parts[] = '<span class="duty-label">출입구</span>' . $hl($ae);

                            if ($parts):
                ?>
                <tr class="duty-sub">
                    <td colspan="7"><?php echo implode('<span class="duty-sep">|</span>', $parts); ?></td>
                </tr>
                <?php
                            endif;
                        endif;
                    endif;
                ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div><!-- /.table-scroll-wrap -->

    <?php if ($is_elder): ?>
    <div style="margin-top: 16px; border-top: 1px solid #e0e0e0; padding-top: 12px;">
        <div style="background: #f8f9ff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px;">
            <div style="font-weight: 600; font-size: 14px; color: #333; margin-bottom: 6px;">관리자모드</div>
            <p style="font-size: 12px; color: #666; margin-bottom: 8px; line-height: 1.4;">강연 일정을 추가, 수정, 삭제할 수 있습니다. 변경 사항은 자동 저장됩니다.</p>
            <a href="talk_admin.php" style="width: 100%; display: block; text-align: center; text-decoration: none; padding: 8px 16px; border-radius: 4px; font-size: 14px; font-weight: 600; background: #e0e0e0; color: #333; border: none; cursor: pointer; box-sizing: border-box;">관리자모드로 보기</a>
        </div>
    </div>
    <?php endif; ?>
    <div id="newWindowGroup" style="display: none; margin-top: 10px;">
        <a href="#" id="newWindowBtn" style="width: 100%; display: block; text-align: center; text-decoration: none; padding: 8px 16px; border-radius: 4px; font-size: 14px; font-weight: 600; background: #e0e0e0; color: #333; border: none; cursor: pointer; box-sizing: border-box;">↗ 새창으로 보기</a>
    </div>
</div>

<script>
(function() {
    if (window.self !== window.top) {
        var group = document.getElementById('newWindowGroup');
        var btn = document.getElementById('newWindowBtn');
        if (group) group.style.display = '';
        if (btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                window.open(window.location.href, '_blank', 'noopener,noreferrer');
            });
        }
    }
})();
</script>
</body>
</html>
