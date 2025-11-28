<?php
// 로컬 개발 모드 체크
$localConfigFile = dirname(__FILE__) . '/../c/config.php';
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}

// 로그인한 사용자 정보 가져오기
$loggedInUserName = '';
$is_admin = false;

// 로컬 모드가 아닐 때만 관리자 권한 체크
if (!defined('LOCAL_MODE') || LOCAL_MODE !== true) {
    if (file_exists(dirname(__FILE__) . '/../config.php')) {
        require_once dirname(__FILE__) . '/../config.php';
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
} else {
    // 로컬 개발 환경에서는 테스트용 사용자 설정
    if (defined('USER')) {
        $userName = constant('USER');
        if (!empty($userName)) {
            $loggedInUserName = $userName;
        }
    }
    $is_admin = true;
}

require_once 'api.php';

$manager = new MeetingDataManager();
$currentYear = (int)date('Y');
$currentMonth = (int)date('n');

// URL 파라미터로 연도/월 받기
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;

// 월 이동 계산
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// 해당 월에 포함된 주차 계산
// 1. 해당 월의 1일이 속한 주차부터 시작
// 2. 해당 월의 마지막 날이 속한 주차까지 포함

// ISO-8601 주차 계산 로직 사용
function getWeeksInMonth($year, $month)
{
    $weeks = array();

    // 해당 월의 1일
    $firstDay = new DateTime("$year-$month-01");
    // 해당 월의 마지막 날
    $lastDay = new DateTime("$year-$month-" . $firstDay->format('t'));

    // 시작 주차
    $startWeek = (int)$firstDay->format('W');
    // 1월인데 주차가 52나 53이면 전년도 마지막 주차임. 하지만 여기서는 단순화를 위해 처리 필요.
    // PHP date('W')는 월요일 시작 기준 ISO-8601 주차 번호 반환.

    // 간단하게 해당 월의 모든 날짜를 순회하며 주차 번호를 수집 (중복 제거)
    $current = clone $firstDay;
    while ($current <= $lastDay) {
        $w = (int)$current->format('W');
        $y = (int)$current->format('o'); // ISO 주차 연도

        $key = $y . '-' . $w;
        if (!isset($weeks[$key])) {
            $weeks[$key] = array('year' => $y, 'week' => $w);
        }

        $current->modify('+1 day');
    }

    return array_values($weeks);
}

$targetWeeks = getWeeksInMonth($year, $month);
$weeksData = array();

foreach ($targetWeeks as $weekInfo) {
    $wData = $manager->load($weekInfo['year'], $weekInfo['week']);

    // 데이터가 없으면 건너뛰기
    if ($wData === null) {
        continue;
    }

    // 프로그램 분류
    $categorized = array('treasures' => array(), 'ministry' => array(), 'living' => array());
    if (!empty($wData['program'])) {
        foreach ($wData['program'] as $item) {
            if (isset($item['section'])) {
                $section = $item['section'];
                if ($section === 'treasures') $categorized['treasures'][] = $item;
                elseif ($section === 'ministry') $categorized['ministry'][] = $item;
                else $categorized['living'][] = $item;
            } else {
                // 구버전 데이터 호환
                $title = $item['title'];
                $num = 0;
                if (preg_match('/^(\d+)\./', $title, $matches)) {
                    $num = (int)$matches[1];
                }
                if ($num >= 1 && $num <= 3) $categorized['treasures'][] = $item;
                elseif ($num >= 4 && $num <= 6) $categorized['ministry'][] = $item;
                else $categorized['living'][] = $item;
            }
        }
    }
    $wData['categorized'] = $categorized;
    $weeksData[] = $wData;
}

?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $year; ?>년 <?php echo $month; ?>월 평일집회 계획표</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* 기본 스타일 (view.php와 동일하게 유지하되 인쇄용 조정) */
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            /* 크롬, 사파리 등에서 배경색 인쇄 강제 */
            print-color-adjust: exact;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .page-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .controls {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .month-nav {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 18px;
            font-weight: bold;
        }

        .nav-btn {
            text-decoration: none;
            color: #667eea;
            padding: 5px 10px;
            border-radius: 4px;
            background: #f0f2f5;
        }

        .nav-btn:hover {
            background: #e0e2e5;
        }

        .print-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .print-btn:hover {
            background: #45a049;
        }

        /* 주차별 카드 스타일 */
        .week-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 20px;
            page-break-inside: avoid;
            /* 인쇄 시 중간에 잘리지 않도록 */
            border: 1px solid #e0e0e0;
        }

        .week-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .week-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .week-bible {
            font-size: 14px;
            color: #666;
            background: #f5f5f5;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .assignments-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 15px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
        }

        .assignment-box {
            text-align: center;
        }

        .assignment-label {
            font-size: 11px;
            color: #666;
            margin-bottom: 2px;
            font-weight: 600;
        }

        .assignment-value {
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }

        .section {
            margin-bottom: 15px;
        }

        /* WOL 스타일 적용 */
        .section-header {
            background: white;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px solid #eee;
        }

        .section-header.treasures {
            color: #00796B;
        }

        .section-header.ministry {
            color: #A86500;
        }

        .section-header.living {
            color: #8E201D;
        }

        .section-icon {
            font-size: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }

        .dc-icon--gem {
            background-image: url('../icons/icon-gem.png');
        }

        .dc-icon--wheat {
            background-image: url('../icons/icon-wheat.png');
        }

        .dc-icon--sheep {
            background-image: url('../icons/icon-sheep.png');
        }

        .program-item {
            padding: 4px 0;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .program-item:last-child {
            border-bottom: none;
        }

        .program-title {
            flex: 1;
            font-weight: 600;
            font-size: 13px;
            color: #333;
        }

        /* 섹션별 프로그램 제목 색상 */
        .section-treasures .program-title {
            color: #00796B;
        }

        .section-ministry .program-title {
            color: #A86500;
        }

        .section-living .program-title {
            color: #8E201D;
        }

        .program-duration {
            color: #888;
            font-size: 12px;
            width: 50px;
            text-align: right;
        }

        .program-assigned {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-left: 15px;
            text-align: left;
            min-width: 100px;
            white-space: nowrap;
        }

        .no-meeting-msg {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 16px;
            background: #fff3e0;
            border-radius: 8px;
        }

        /* 인쇄 전용 스타일 */
        @media print {
            @page {
                size: landscape;
                /* 가로 모드 설정 */
            }

            body {
                background: white;
                padding: 8mm;
                margin: 0;
                font-size: 12px;
                -webkit-print-color-adjust: exact;
            }

            .controls {
                display: none;
            }

            .page-container {
                max-width: 100%;
                width: 100%;
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .week-card {
                box-shadow: none;
                border: 1px solid #ccc;
                padding: 12px;
                margin-bottom: 0;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .week-header {
                margin-bottom: 12px;
                padding-bottom: 8px;
                border-bottom: 2px solid #eee;
            }

            .week-title {
                font-size: 16px;
            }

            .week-bible {
                font-size: 12px;
            }

            .assignments-grid {
                gap: 8px;
                padding: 8px;
                margin-bottom: 12px;
                background: #f8f9fa;
            }

            .assignment-label {
                font-size: 11px;
            }

            .assignment-value {
                font-size: 12px;
            }

            .section-header {
                padding: 5px 8px;
                margin-bottom: 5px;
                font-size: 13px;
            }

            .section-icon {
                width: 18px;
                height: 18px;
                font-size: 16px;
            }

            .program-item {
                padding: 4px 0;
                margin-bottom: 4px;
                border-bottom: 1px solid #f0f0f0;
            }

            /* 링크 URL 출력 방지 */
            a[href]:after {
                content: none !important;
            }

            .program-title {
                font-size: 12px;
            }

            .program-duration {
                font-size: 11px;
                width: 45px;
                text-align: right;
            }

            .program-assigned {
                font-size: 12px;
                margin-left: 12px;
                text-align: left;
                min-width: 90px;
                white-space: nowrap;
            }
        }
    </style>
</head>

<body>
    <div class="page-container">
        <!-- 상단 컨트롤 (인쇄 시 숨김) -->
        <div class="controls">
            <div class="month-nav">
                <a href="?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>" class="nav-btn"><i class="bi bi-chevron-left"></i></a>
                <span><?php echo $year; ?>년 <?php echo $month; ?>월</span>
                <a href="?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>" class="nav-btn"><i class="bi bi-chevron-right"></i></a>
            </div>
            <button onclick="window.print()" class="print-btn">
                <i class="bi bi-printer"></i> 인쇄하기
            </button>
        </div>

        <!-- 주차별 데이터 출력 -->
        <?php foreach ($weeksData as $data): ?>
            <div class="week-card">
                <div class="week-header">
                    <div class="week-title"><?php echo $data['date']; ?></div>
                    <?php if (!empty($data['bible_reading'])): ?>
                        <div class="week-bible">성경 읽기: <?php echo htmlspecialchars($data['bible_reading']); ?></div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($data['no_meeting'])): ?>
                    <div class="no-meeting-msg">
                        <strong><?php echo htmlspecialchars($data['no_meeting_title']); ?></strong>
                        <?php if (!empty($data['no_meeting_reason'])): ?>
                            <br><?php echo nl2br(htmlspecialchars($data['no_meeting_reason'])); ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- 주요 배정 (사회자, 기도 등) -->
                    <div class="assignments-grid">
                        <div class="assignment-box">
                            <div class="assignment-label">사회자</div>
                            <div class="assignment-value"><?php echo htmlspecialchars(isset($data['assignments']['opening_remarks']) ? $data['assignments']['opening_remarks'] : '-'); ?></div>
                        </div>
                        <div class="assignment-box">
                            <div class="assignment-label">시작 기도</div>
                            <div class="assignment-value"><?php echo htmlspecialchars(isset($data['assignments']['opening_prayer']) ? $data['assignments']['opening_prayer'] : '-'); ?></div>
                        </div>
                        <div class="assignment-box">
                            <div class="assignment-label">맺음말</div>
                            <div class="assignment-value"><?php echo htmlspecialchars(isset($data['assignments']['closing_remarks']) ? $data['assignments']['closing_remarks'] : '-'); ?></div>
                        </div>
                        <div class="assignment-box">
                            <div class="assignment-label">마치는 기도</div>
                            <div class="assignment-value"><?php echo htmlspecialchars(isset($data['assignments']['closing_prayer']) ? $data['assignments']['closing_prayer'] : '-'); ?></div>
                        </div>
                    </div>

                    <!-- 1. 성경에 담긴 보물 -->
                    <?php if (!empty($data['categorized']['treasures'])): ?>
                        <div class="section section-treasures">
                            <div class="section-header treasures">
                                <span class="section-icon dc-icon--gem"></span>
                                <span><?php echo htmlspecialchars($data['sections']['treasures']); ?></span>
                            </div>
                            <?php foreach ($data['categorized']['treasures'] as $item): ?>
                                <div class="program-item">
                                    <span class="program-title"><?php echo htmlspecialchars($item['title']); ?></span>
                                    <span class="program-duration">(<?php echo htmlspecialchars($item['duration']); ?>)</span>
                                    <span class="program-assigned"><?php
                                        if (is_array($item['assigned'])) {
                                            echo htmlspecialchars(implode(', ', array_filter($item['assigned'])));
                                        } else {
                                            echo htmlspecialchars($item['assigned']);
                                        }
                                    ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- 2. 야외 봉사에 힘쓰십시오 -->
                    <?php if (!empty($data['categorized']['ministry'])): ?>
                        <div class="section section-ministry">
                            <div class="section-header ministry">
                                <span class="section-icon dc-icon--wheat"></span>
                                <span><?php echo htmlspecialchars($data['sections']['ministry']); ?></span>
                            </div>
                            <?php foreach ($data['categorized']['ministry'] as $item): ?>
                                <div class="program-item">
                                    <span class="program-title"><?php echo htmlspecialchars($item['title']); ?></span>
                                    <span class="program-duration">(<?php echo htmlspecialchars($item['duration']); ?>)</span>
                                    <span class="program-assigned"><?php
                                        if (is_array($item['assigned'])) {
                                            echo htmlspecialchars(implode(', ', array_filter($item['assigned'])));
                                        } else {
                                            echo htmlspecialchars($item['assigned']);
                                        }
                                    ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- 3. 그리스도인 생활 -->
                    <?php if (!empty($data['categorized']['living'])): ?>
                        <div class="section section-living">
                            <div class="section-header living">
                                <span class="section-icon dc-icon--sheep"></span>
                                <span><?php echo htmlspecialchars($data['sections']['living']); ?></span>
                            </div>
                            <?php foreach ($data['categorized']['living'] as $item): ?>
                                <div class="program-item">
                                    <span class="program-title"><?php echo htmlspecialchars($item['title']); ?></span>
                                    <span class="program-duration">(<?php echo htmlspecialchars($item['duration']); ?>)</span>
                                    <span class="program-assigned"><?php
                                        if (is_array($item['assigned'])) {
                                            echo htmlspecialchars(implode(', ', array_filter($item['assigned'])));
                                        } else {
                                            echo htmlspecialchars($item['assigned']);
                                        }
                                    ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>