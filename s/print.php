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
    <title>평일 집회 계획표</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* 기본 스타일 */
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: 'Malgun Gothic', 'Dotum', sans-serif;
            /* 문서 느낌 폰트 */
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: #000;
            font-size: 14px;
            /* 기본 폰트 확대 (13px -> 15px) */
        }

        /* 컨트롤 박스 스타일 */
        .controls {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            width: 210mm;
            /* 너비 고정 */
            min-width: 210mm;
            /* 최소 너비 고정 */
            margin-left: auto;
            margin-right: auto;
        }

        .controls-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 20px;
        }

        .right-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .month-nav {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 18px;
            font-weight: bold;
            white-space: nowrap;
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
            justify-content: center;
            white-space: nowrap;
        }

        .print-btn:hover {
            background: #45a049;
        }

        /* 멀티 셀렉트 드롭다운 */
        .multi-select-container {
            position: relative;
            width: 200px;
        }

        .select-box {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            color: #555;
            height: 100%;
        }

        .select-box:hover {
            background: #e9ecef;
        }

        .select-box::after {
            content: '';
            border: 5px solid transparent;
            border-top-color: #666;
            margin-left: 10px;
            margin-top: 4px;
        }

        .checkboxes {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
            max-height: 200px;
            overflow-y: auto;
            margin-top: 4px;
        }

        .checkboxes.show {
            display: block;
        }

        .checkboxes label {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }

        .checkboxes label:last-child {
            border-bottom: none;
        }

        .checkboxes label:hover {
            background: #f5f5f5;
        }

        .checkboxes input {
            margin-right: 10px;
        }

        /* =========================================
           문서 양식 스타일 (인쇄 및 웹 미리보기 공통)
           ========================================= */
        .page-container {
            width: 210mm;
            /* 고정 너비 설정 (약 794px) */
            min-width: 210mm;
            /* 최소 너비 고정 */
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            /* 내부 컨텐츠 넘침 방지 */
        }

        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 3px solid #333;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }

        .congregation-name {
            font-size: 16px;
            font-weight: bold;
            white-space: nowrap;
            /* 줄바꿈 방지 */
        }

        .doc-title {
            font-size: 24px;
            font-weight: bold;
            white-space: nowrap;
            /* 줄바꿈 방지 */
        }

        /* 주차별 블록 */
        .week-block {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .week-block.excluded {
            opacity: 0.3;
            filter: grayscale(100%);
        }

        /* 주차 헤더 (날짜 | 성경읽기 vs 사회자/기도) */
        .week-info-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .week-left {
            font-size: 16px;
            /* 15px -> 17px */
            font-weight: bold;
            white-space: nowrap;
            /* 줄바꿈 방지 */
        }

        .week-right {
            text-align: right;
            font-size: 13px;
            /* 12px -> 14px */
            line-height: 1.4;
            white-space: nowrap;
            /* 줄바꿈 방지 */
        }

        .role-row {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .role-label {
            font-weight: bold;
            color: #555;
            min-width: 80px;
            text-align: right;
        }

        .role-name {
            min-width: 60px;
            text-align: left;
            font-weight: bold;
        }

        /* 프로그램 테이블 */
        .program-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            /* 12px -> 14px */
        }

        .program-table td {
            padding: 5px 5px;
            /* 패딩 약간 확대 */
            vertical-align: top;
            white-space: nowrap;
            /* 테이블 셀 내용 줄바꿈 방지 */
        }

        /* 열 너비 조정 */
        .col-time {
            width: 45px;
            color: #555;
            font-weight: bold;
        }

        .col-content {
            white-space: normal !important;
        }

        /* 내용 부분은 필요시 줄바꿈 허용할 수도 있으나 요청에 따라 일단 둠, 하지만 너무 길어질 수 있으므로 확인 필요. 요청은 '줄바꿈 안되도록'이므로 nowrap 유지하되, 너무 긴 경우만 normal로? 사용자는 '줄바꿈 안되도록'을 원함. */

        .col-label {
            width: 90px;
            text-align: right;
            color: #555;
            font-size: 12px;
        }

        .col-assignee {
            width: 130px;
            font-weight: bold;
        }

        .col-aux {
            width: 80px;
            text-align: right;
            color: #777;
            font-size: 12px;
        }

        /* 보조교실 */

        /* 섹션 헤더 띠지 */
        .section-row td {
            padding: 6px 10px;
            font-weight: bold;
            color: white;
            font-size: 14px;
            /* 13px -> 15px */
        }

        .bg-treasures {
            background-color: #546E7A;
        }

        /* 성경에 담긴 보물 (청회색) */
        .bg-ministry {
            background-color: #C18C00;
        }

        /* 야외 봉사에 힘쓰십시오 (황토색) */
        .bg-living {
            background-color: #8E201D;
        }

        /* 그리스도인 생활 (자주색) */

        .program-title {
            font-weight: normal;
        }

        .bullet {
            margin-right: 5px;
            color: #333;
        }

        /* 모바일 대응 */
        @media (max-width: 600px) {

            /* 모바일에서도 page-container 및 controls 너비 유지 (가로 스크롤 발생) */
            .page-container,
            .controls {
                padding: 20px;
                /* 패딩 유지 */
            }

            .doc-title {
                font-size: 24px;
            }

            /* 폰트 크기 유지 */

            /* 모바일에서 가로 스크롤을 위한 body 설정 */
            body {
                overflow-x: auto;
            }
        }

        /* 인쇄 전용 스타일 */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
                font-size: 14px;
                /* 인쇄 시에도 15px 유지 */
            }

            .controls {
                display: none !important;
            }

            .week-block.excluded {
                display: none !important;
            }

            .page-container {
                box-shadow: none;
                padding: 0;
                margin: 0;
                width: 100%;
                max-width: 100%;
            }

            .week-block {
                margin-bottom: 20px;
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <!-- 상단 컨트롤 -->
    <div class="controls">
        <div class="controls-row">
            <!-- 년월 선택 -->
            <div class="month-nav">
                <a href="?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>" class="nav-btn"><i class="bi bi-chevron-left"></i></a>
                <span><?php echo $year; ?>. <?php echo str_pad($month, 2, '0', STR_PAD_LEFT); ?></span>
                <a href="?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>" class="nav-btn"><i class="bi bi-chevron-right"></i></a>
            </div>

            <!-- 주차 선택 및 인쇄 버튼 그룹 -->
            <div class="right-controls">
                <!-- 멀티 셀렉트 -->
                <div class="multi-select-container">
                    <div class="select-box" onclick="toggleCheckboxes()">
                        <span id="select-text">주차 선택</span>
                    </div>
                    <div class="checkboxes" id="checkboxes">
                        <?php foreach ($weeksData as $index => $data): ?>
                            <label>
                                <input type="checkbox" checked onchange="toggleWeek(<?php echo $index; ?>)" />
                                <?php echo $data['date']; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 인쇄 버튼 -->
                <button onclick="window.print()" class="print-btn">
                    <i class="bi bi-printer"></i> 인쇄하기
                </button>
            </div>
        </div>
    </div>

    <div class="page-container">
        <!-- 인쇄 시 매 페이지마다 반복될 헤더를 위해 테이블 구조 사용 -->
        <table style="width: 100%; border-collapse: collapse; border: none;">
            <thead>
                <tr>
                    <td style="border: none; padding: 0;">
                        <div class="doc-header">
                            <div class="congregation-name">시흥장현회중</div>
                            <div class="doc-title">평일 집회 계획표</div>
                        </div>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: none; padding: 0;">
                        <?php foreach ($weeksData as $index => $data): ?>
                            <?php
                            // 노래 찾기
                            $songs = array();
                            if (!empty($data['program'])) {
                                foreach ($data['program'] as $item) {
                                    if (strpos($item['title'], '노래') !== false) {
                                        $songs[] = $item['title'];
                                    }
                                }
                            }
                            $openingSong = isset($songs[0]) ? $songs[0] : '노래';
                            $middleSong = isset($songs[1]) ? $songs[1] : '노래';
                            $closingSong = isset($songs[2]) ? $songs[2] : '노래';
                            ?>
                            <div class="week-block" id="week-block-<?php echo $index; ?>">
                                <!-- 주차 헤더 정보 -->
                                <div class="week-info-header">
                                    <div class="week-left">
                                        <?php echo $data['date']; ?> | 주간 성경 읽기: <?php echo htmlspecialchars($data['bible_reading']); ?>
                                    </div>
                                    <div class="week-right">
                                        <div class="role-row">
                                            <span class="role-label">사회자:</span>
                                            <span class="role-name"><?php echo htmlspecialchars(isset($data['assignments']['opening_remarks']) ? $data['assignments']['opening_remarks'] : ''); ?></span>
                                        </div>

                                        <div class="role-row">
                                            <span class="role-label">기도:</span>
                                            <span class="role-name"><?php echo htmlspecialchars(isset($data['assignments']['opening_prayer']) ? $data['assignments']['opening_prayer'] : ''); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($data['no_meeting'])): ?>
                                    <div style="padding: 20px; text-align: center; background: #eee; border-radius: 4px;">
                                        <strong><?php echo htmlspecialchars($data['no_meeting_title']); ?></strong>
                                        <br><?php echo nl2br(htmlspecialchars($data['no_meeting_reason'])); ?>
                                    </div>
                                <?php else: ?>
                                    <table class="program-table">
                                        <!-- 노래 및 소개말 -->
                                        <tr>
                                            <td class="col-time">0:00</td>
                                            <td class="col-content"><span class="bullet">●</span><?php echo htmlspecialchars($openingSong); ?></td>
                                            <td class="col-label"></td>
                                            <td class="col-assignee"></td>
                                        </tr>
                                        <tr>
                                            <td class="col-time">0:05</td>
                                            <td class="col-content"><span class="bullet">●</span>소개말 (1분)</td>
                                            <td class="col-label"></td>
                                            <td class="col-assignee"></td>
                                        </tr>

                                        <!-- 1. 성경에 담긴 보물 -->
                                        <tr class="section-row">
                                            <td colspan="5" class="bg-treasures">성경에 담긴 보물</td>
                                        </tr>
                                        <?php if (!empty($data['categorized']['treasures'])): ?>
                                            <?php foreach ($data['categorized']['treasures'] as $item): ?>
                                                <tr>
                                                    <td class="col-time"></td>
                                                    <td class="col-content">
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                        (<?php echo htmlspecialchars($item['duration']); ?>)
                                                    </td>
                                                    <td class="col-label">
                                                        <?php if (strpos($item['title'], '성경 낭독') !== false) echo '학생:'; ?>
                                                    </td>
                                                    <td class="col-assignee">
                                                        <?php
                                                        if (is_array($item['assigned'])) echo htmlspecialchars(implode(', ', array_filter($item['assigned'])));
                                                        else echo htmlspecialchars($item['assigned']);
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                        <!-- 2. 야외 봉사에 힘쓰십시오 -->
                                        <tr class="section-row">
                                            <td colspan="5" class="bg-ministry">야외 봉사에 힘쓰십시오</td>
                                        </tr>
                                        <?php if (!empty($data['categorized']['ministry'])): ?>
                                            <?php foreach ($data['categorized']['ministry'] as $item): ?>
                                                <tr>
                                                    <td class="col-time"></td>
                                                    <td class="col-content">
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                        (<?php echo htmlspecialchars($item['duration']); ?>)
                                                    </td>
                                                    <td class="col-label">학생/보조자:</td>
                                                    <td class="col-assignee">
                                                        <?php
                                                        if (is_array($item['assigned'])) echo htmlspecialchars(implode(', ', array_filter($item['assigned'])));
                                                        else echo htmlspecialchars($item['assigned']);
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                        <!-- 3. 그리스도인 생활 -->
                                        <tr class="section-row">
                                            <td colspan="5" class="bg-living">그리스도인 생활</td>
                                        </tr>
                                        <tr>
                                            <td class="col-time"></td>
                                            <td class="col-content"><span class="bullet">●</span><?php echo htmlspecialchars($middleSong); ?></td>
                                            <td class="col-label"></td>
                                            <td class="col-assignee"></td>
                                        </tr>
                                        <?php if (!empty($data['categorized']['living'])): ?>
                                            <?php foreach ($data['categorized']['living'] as $item): ?>
                                                <tr>
                                                    <td class="col-time"></td>
                                                    <td class="col-content">
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                        (<?php echo htmlspecialchars($item['duration']); ?>)
                                                    </td>
                                                    <td class="col-label">
                                                        <?php if (strpos($item['title'], '회중 성서 연구') !== false) echo '사회자/낭독자:'; ?>
                                                    </td>
                                                    <td class="col-assignee">
                                                        <?php
                                                        if (is_array($item['assigned'])) echo htmlspecialchars(implode(', ', array_filter($item['assigned'])));
                                                        else echo htmlspecialchars($item['assigned']);
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <tr>
                                            <td class="col-time"></td>
                                            <td class="col-content"><span class="bullet">●</span>맺음말 (3분)</td>
                                            <td class="col-label"></td>
                                            <td class="col-assignee"><?php echo htmlspecialchars(isset($data['assignments']['closing_remarks']) ? $data['assignments']['closing_remarks'] : ''); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="col-time"></td>
                                            <td class="col-content"><span class="bullet">●</span><?php echo htmlspecialchars($closingSong); ?></td>
                                            <td class="col-label">기도:</td>
                                            <td class="col-assignee"><?php echo htmlspecialchars(isset($data['assignments']['closing_prayer']) ? $data['assignments']['closing_prayer'] : ''); ?></td>
                                        </tr>
                                    </table>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        let expanded = false;

        function toggleCheckboxes() {
            const checkboxes = document.getElementById("checkboxes");
            if (!expanded) {
                checkboxes.classList.add('show');
                expanded = true;
            } else {
                checkboxes.classList.remove('show');
                expanded = false;
            }
        }

        document.addEventListener('click', function(e) {
            const container = document.querySelector('.multi-select-container');
            if (expanded && !container.contains(e.target)) {
                document.getElementById("checkboxes").classList.remove('show');
                expanded = false;
            }
        });

        function toggleWeek(index) {
            const block = document.getElementById('week-block-' + index);
            if (block) {
                block.classList.toggle('excluded');
            }
            updateSelectText();
        }

        function updateSelectText() {
            const total = <?php echo count($weeksData); ?>;
            const excluded = document.querySelectorAll('.week-block.excluded').length;
            const selected = total - excluded;
            const textSpan = document.getElementById('select-text');

            if (selected === total) {
                textSpan.textContent = "모든 주차 선택됨";
            } else if (selected === 0) {
                textSpan.textContent = "선택된 주차 없음";
            } else {
                textSpan.textContent = selected + "개 주차 선택됨";
            }
        }
        document.addEventListener('DOMContentLoaded', updateSelectText);
    </script>
</body>

</html>