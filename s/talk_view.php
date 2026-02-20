<?php
// 서비스 워커 캐시 방지
header('Cache-Control: no-cache, no-store, must-revalidate');

date_default_timezone_set('Asia/Seoul');

// 로그인한 사용자 정보
$loggedInUserName = '';
$is_admin = false;
if (file_exists(dirname(__FILE__) . '/../config.php')) {
    @require_once dirname(__FILE__) . '/../config.php';
    if (function_exists('mb_id') && function_exists('get_member_name')) {
        $mbId = mb_id();
        if (!empty($mbId)) {
            $loggedInUserName = get_member_name($mbId);
        }
    }
    if (function_exists('mb_id') && function_exists('is_admin')) {
        $is_admin = is_admin(mb_id());
    }
}

require_once dirname(__FILE__) . '/talk_api.php';

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
            font-size: 14px;
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
            font-size: 18px;
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
            font-size: 12px;
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
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
        }
        .talk-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #e8e8e8;
            font-size: 13px;
            vertical-align: top;
        }
        .talk-table tr:last-child td { border-bottom: none; }
        .talk-table tr:hover { background: #f9f9f9; }

        .col-date { width: 80px; text-align: center; white-space: nowrap; }
        .col-speaker { width: 70px; text-align: center; }
        .col-congregation { width: 90px; text-align: center; }
        .col-topic { min-width: 150px; width: 25%; }
        .col-chairman { width: 60px; text-align: center; }
        .col-reader { width: 60px; text-align: center; }
        .col-prayer { width: 60px; text-align: center; }
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
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .topic-label.circuit { background: #43a047; color: white; }
        .topic-label.special { background: #ef6c00; color: white; }
        .topic-text {
            display: block;
            line-height: 1.4;
        }
        .past-row { opacity: 0.5; }
        .next-row td { border-top: 2px solid #ef4444; border-bottom: 2px solid #ef4444; }
        .next-row td:first-child { border-left: 2px solid #ef4444; }
        .next-row td:last-child { border-right: 2px solid #ef4444; }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 15px;
        }
        .utility-section {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .utility-btn {
            padding: 8px 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            color: #555;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
        }
        .utility-btn:hover { background: #f5f5f5; }
        #newWindowGroup { display: none; margin-top: 8px; }

        @media (max-width: 768px) {
            body { overflow-x: auto; }
            .container { padding: 6px; min-width: 540px; }
            .talk-table { font-size: 11px; min-width: 520px; }
            .talk-table th { padding: 6px 3px; font-size: 11px; }
            .talk-table td { padding: 4px 3px; font-size: 11px; }
            .col-date { width: 55px; }
            .col-speaker, .col-chairman, .col-reader, .col-prayer { width: 45px; }
            .col-congregation { width: 65px; }
            .page-title { font-size: 15px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1 class="page-title">공개 강연 계획표</h1>
    </div>

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
                    $dateDisplay = $d->format('y/m/d');
                    $rowClass = '';
                    if ($talk['topic_type'] === 'circuit_visit') $rowClass = 'row-circuit';
                    elseif ($talk['topic_type'] === 'special_talk') $rowClass = 'row-special';
                ?>
                <tr class="<?php echo $isPast ? 'past-row' : ''; ?> <?php echo $isNext ? 'next-row' : ''; ?> <?php echo $rowClass; ?>">
                    <td class="col-date"><span class="date-text"><?php echo $dateDisplay; ?></span></td>
                    <td class="col-speaker">
                        <?php if (!empty(trim($talk['speaker'])) && trim($talk['speaker']) === $loggedInUserName): ?>
                            <span class="my-name"><?php echo htmlspecialchars($talk['speaker']); ?></span>
                        <?php else: ?>
                            <?php echo htmlspecialchars($talk['speaker']); ?>
                        <?php endif; ?>
                    </td>
                    <td class="col-congregation"><?php echo htmlspecialchars($talk['congregation']); ?></td>
                    <td class="col-topic">
                        <?php if ($talk['topic_type'] === 'circuit_visit'): ?>
                            <span class="topic-label circuit">순회 방문</span>
                        <?php elseif ($talk['topic_type'] === 'special_talk'): ?>
                            <span class="topic-label special">특별 강연</span>
                        <?php endif; ?>
                        <span class="topic-text"><?php echo htmlspecialchars($talk['topic']); ?></span>
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
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="utility-section">
        <?php if ($is_admin): ?>
            <a href="talk_admin.php" class="utility-btn">관리자모드로 보기</a>
        <?php endif; ?>
    </div>

    <div id="newWindowGroup">
        <a href="#" id="newWindowBtn" class="utility-btn">새창으로 보기 ↗</a>
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
