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
function getWeeksInMonth($year, $month)
{
    $weeks = array();
    $firstDay = new DateTime("$year-$month-01");
    $lastDay = new DateTime("$year-$month-" . $firstDay->format('t'));

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
        /* 기본 스타일 */
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.6;
            /* 줄간격 확대 */
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
            white-space: nowrap;
            /* 날짜 줄바꿈 방지 */
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
            white-space: nowrap;
            /* 버튼 텍스트 줄바꿈 방지 */
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
            border: 1px solid #e0e0e0;
        }

        .week-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
            /* 간격 확대 */
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

        /* 주요 배정 정보 한 줄로 압축 및 중앙 정렬 (웹/인쇄 공통) */
        .assignments-grid {
            display: flex;
            justify-content: center;
            /* 전체 박스들을 중앙으로 정렬 */
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
            /* 간격 확대 */
            background: transparent;
            padding: 0;
        }

        .assignment-box {
            display: flex;
            align-items: center;
            justify-content: center;
            /* 박스 내부 텍스트 중앙 정렬 */
            border: 1px solid #eee;
            padding: 4px 10px;
            border-radius: 4px;
            background: #f9f9f9;
            width: auto;
            /* 내용물 크기에 맞춤 */
        }

        /* WOL 스타일 적용 */
        .section-header {
            background: white;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 14px;
            /* 폰트 확대 */
            font-weight: 700;
            margin-bottom: 8px;
            /* 간격 확대 */
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
            padding: 8px 0;
            /* 항목 간격 확대 (4px -> 8px) */
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
            font-size: 14px;
            /* 폰트 확대 */
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
            font-size: 13px;
            /* 폰트 확대 */
            width: 55px;
            text-align: right;
        }

        .program-assigned {
            font-size: 14px;
            /* 폰트 확대 */
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
        }

        /* 주요 배정 정보 (사회자 등) 폰트 확대 */
        .assignment-label {
            font-size: 14px;
            /* 폰트 확대 */
            margin-bottom: 0;
            margin-right: 5px;
            white-space: nowrap;
            color: #555;
            font-weight: normal;
        }

        .assignment-value {
            font-size: 14px;
            /* 폰트 확대 */
            white-space: nowrap;
            font-weight: bold;
            color: #333;
        }

        /* 제외된 주차 스타일 */
        .week-card.excluded {
            opacity: 0.4;
            filter: grayscale(100%);
            background: #e0e0e0;
        }

        /* 주차 선택 체크박스 영역 */
        .week-selectors {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
            margin: 0 20px;
        }

        .week-checkbox-label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            font-size: 12px;
            /* 폰트 크기 축소 */
            font-weight: 500;
            user-select: none;
            color: #666;
            /* 회색으로 변경 */
        }

        .week-checkbox-label input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        /* 인쇄 전용 스타일 */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
                font-size: 14px;
                /* 인쇄 시 폰트도 14px로 확대 */
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* 인쇄 시 컨트롤 박스 숨김 */
            .controls {
                display: none !important;
            }

            /* 제외된 주차는 인쇄하지 않음 */
            .week-card.excluded {
                display: none !important;
            }

            .page-container {
                max-width: 100%;
                width: 100%;
                display: block;
            }

            .week-card {
                box-shadow: none;
                border: 1px solid #ccc;
                padding: 8px;
                /* 인쇄 시 패딩 약간 축소 (공간 확보) */
                margin-bottom: 5px;
                /* 인쇄 시 마진 축소 */
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .week-header {
                margin-bottom: 5px;
                /* 헤더 마진 축소 */
                padding-bottom: 5px;
                border-bottom: 1px solid #eee;
            }

            .week-title {
                font-size: 15px;
            }

            .week-bible {
                font-size: 11px;
                padding: 2px 6px;
            }

            /* 주요 배정 정보 (공통 스타일 상속) */

            .section-header {
                padding: 4px 6px;
                /* 여백 약간 확대 */
                margin-bottom: 4px;
                font-size: 14px;
                /* 폰트 확대 (12px -> 14px) */
            }

            .section-icon {
                width: 16px;
                height: 16px;
                font-size: 14px;
            }

            .program-item {
                padding: 4px 0;
                /* 간격 약간 확대 (3px -> 4px) */
                margin-bottom: 2px;
                border-bottom: 1px solid #f5f5f5;
            }

            /* 링크 URL 출력 방지 */
            a[href]:after {
                content: none !important;
            }

            .program-title {
                font-size: 14px;
            }

            .program-duration {
                font-size: 13px;
                width: 45px;
                text-align: right;
            }

            .program-assigned {
                font-size: 14px;
                margin-left: 10px;
                text-align: left;
                min-width: 90px;
                white-space: nowrap;
            }

            /* 인쇄 시에도 주요 배정 정보 폰트 유지 */
            .assignment-label {
                font-size: 14px;
            }

            .assignment-value {
                font-size: 14px;
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

            <!-- 주차 선택 체크박스 -->
            <div class="week-selectors">
                <?php foreach ($weeksData as $index => $data): ?>
                    <label class="week-checkbox-label">
                        <input type="checkbox" checked onchange="toggleWeek(<?php echo $index; ?>)">
                        <?php echo $data['date']; ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <button onclick="window.print()" class="print-btn">
                <i class="bi bi-printer"></i> 인쇄하기
            </button>
        </div>

        <!-- 주차별 데이터 출력 -->
        <?php foreach ($weeksData as $index => $data): ?>
            <div class="week-card" id="week-card-<?php echo $index; ?>">
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

    <script>
        function toggleWeek(index) {
            const card = document.getElementById('week-card-' + index);
            if (card) {
                card.classList.toggle('excluded');
            }
        }
    </script>
</body>

</html>