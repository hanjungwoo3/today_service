<?php
// 로컬 개발 모드 체크
$localConfigFile = dirname(__FILE__) . '/../c/config.php';
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}

// 로그인한 사용자 정보 가져오기 (선택적)
// 로컬 모드가 아닐 때만 상위 디렉토리 config.php 로드
$loggedInUserName = '';
$is_admin = false;
if (!defined('LOCAL_MODE') || LOCAL_MODE !== true) {
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
} else {
    // 로컬 개발 환경에서는 테스트용 사용자 설정
    if (defined('USER')) {
        $userName = constant('USER');
        if (!empty($userName)) {
            $loggedInUserName = $userName;
        }
    }
    // 로컬 모드일 때는 관리자로 설정
    $is_admin = true;
}

require_once 'api.php';

$manager = new MeetingDataManager();
$currentYear = $manager->getCurrentYear();
$currentWeek = $manager->getCurrentWeek();

// URL 파라미터로 연도/주차 받기
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$week = isset($_GET['week']) ? (int)$_GET['week'] : $currentWeek;

// 주차 범위 체크
if ($week < 1) {
    $week = 52;
    $year--;
} elseif ($week > 52) {
    $week = 1;
    $year++;
}

// 데이터 로드
$data = $manager->load($year, $week);
$showNoDataAlert = false;

// 데이터가 없으면 현재 주차로 리다이렉트
if ($data === null && ($year !== $currentYear || $week !== $currentWeek)) {
    header("Location: view.php?year={$currentYear}&week={$currentWeek}&nodata=1");
    exit;
}

// 현재 주차도 데이터가 없으면 빈 데이터로 표시
if ($data === null) {
    $data = $manager->createEmpty($year, $week);
}

// nodata 파라미터 확인
if (isset($_GET['nodata']) && $_GET['nodata'] == '1') {
    $showNoDataAlert = true;
}

// 저장된 주차 목록 가져오기
$availableWeeks = $manager->getAvailableWeeks();

// 현재 주차의 인덱스 찾기
$currentIndex = -1;
$currentWeekKey = $year . str_pad($week, 2, '0', STR_PAD_LEFT);
foreach ($availableWeeks as $index => $weekData) {
    $weekKey = $weekData['year'] . str_pad($weekData['week'], 2, '0', STR_PAD_LEFT);
    if ($weekKey === $currentWeekKey) {
        $currentIndex = $index;
        break;
    }
}

// 이전/다음 주차 정보
// availableWeeks는 내림차순 정렬 (최신 -> 과거)
$prevWeekData = null;
$nextWeekData = null;

// 인덱스 + 1 = 과거 주차 (이전)
if ($currentIndex >= 0 && $currentIndex < count($availableWeeks) - 1) {
    $prevWeekData = $availableWeeks[$currentIndex + 1];
}

// 인덱스 - 1 = 최신 주차 (다음)
if ($currentIndex > 0) {
    $nextWeekData = $availableWeeks[$currentIndex - 1];
}

// 프로그램을 섹션별로 분류
function categorizePrograms($programs) {
    $treasures = array();
    $ministry = array();
    $living = array();

    foreach ($programs as $item) {
        // section 정보가 있으면 그것을 사용
        if (isset($item['section'])) {
            $section = $item['section'];
            if ($section === 'treasures') {
                $treasures[] = $item;
            } elseif ($section === 'ministry') {
                $ministry[] = $item;
            } else {
                $living[] = $item;
            }
        } else {
            // section 정보가 없으면 번호로 분류 (하위 호환성)
            $title = $item['title'];
            $num = '';

            // 번호 추출
            if (preg_match('/^(\d+)\./', $title, $matches)) {
                $num = (int)$matches[1];
            }

            if ($num >= 1 && $num <= 3) {
                $treasures[] = $item;
            } elseif ($num >= 4 && $num <= 6) {
                $ministry[] = $item;
            } else {
                $living[] = $item;
            }
        }
    }

    return array(
        'treasures' => $treasures,
        'ministry' => $ministry,
        'living' => $living
    );
}

$categorized = categorizePrograms($data['program']);

// 로그인한 사용자의 향후 배정 특권 수집
$myUpcomingAssignments = array();
if (!empty($loggedInUserName)) {
    // 실제 현재 날짜 기준 주차 계산
    $currentYear = (int)date('Y');
    $currentWeek = (int)date('W');

    $allWeeks = $manager->getAvailableWeeks();

    foreach ($allWeeks as $weekInfo) {
        // 실제 이번 주 포함 미래인 경우 확인 (availableWeeks는 내림차순)
        if ($weekInfo['year'] > $currentYear || ($weekInfo['year'] == $currentYear && $weekInfo['week'] >= $currentWeek)) {
            $weekData = $manager->load($weekInfo['year'], $weekInfo['week']);

            if (!$weekData || !empty($weekData['no_meeting'])) {
                continue;
            }

            // 날짜 범위 계산 (ISO 8601)
            $jan4 = new DateTime($weekInfo['year'] . '-01-04');
            $jan4Day = $jan4->format('N');
            $weekStart = clone $jan4;
            $weekStart->modify('-' . ($jan4Day - 1) . ' days');
            $weekStart->modify('+' . (($weekInfo['week'] - 1) * 7) . ' days');
            $weekEnd = clone $weekStart;
            $weekEnd->modify('+6 days');
            $dateRange = $weekStart->format('n월j일') . '-' . $weekEnd->format('j일');

            // 해당 주차의 배정 임시 저장
            $weekAssignments = array();

            // 기본 배정 확인 (소개말, 시작기도)
            if (!empty($weekData['assignments'])) {
                $openingAssignments = array(
                    'opening_remarks' => array('label' => '소개말', 'order' => 0),
                    'opening_prayer' => array('label' => '시작 기도', 'order' => 1)
                );

                foreach ($openingAssignments as $key => $info) {
                    if (!empty($weekData['assignments'][$key]) && trim($weekData['assignments'][$key]) === $loggedInUserName) {
                        $weekAssignments[] = array(
                            'year' => $weekInfo['year'],
                            'week' => $weekInfo['week'],
                            'dateRange' => $dateRange,
                            'section' => '',
                            'title' => $info['label'],
                            'order' => $info['order']
                        );
                    }
                }
            }

            // 프로그램 항목 확인
            if (!empty($weekData['program'])) {
                $programIndex = 0;
                foreach ($weekData['program'] as $item) {
                    $isAssigned = false;

                    if (is_array($item['assigned'])) {
                        foreach ($item['assigned'] as $assignedName) {
                            $trimmedAssignedName = trim($assignedName);
                            if (!empty($trimmedAssignedName) && $trimmedAssignedName === $loggedInUserName) {
                                $isAssigned = true;
                                break;
                            }
                        }
                    } elseif (!empty($item['assigned'])) {
                        $trimmedAssigned = trim($item['assigned']);
                        if ($trimmedAssigned === $loggedInUserName) {
                            $isAssigned = true;
                        }
                    }

                    if ($isAssigned) {
                        $sectionName = '';
                        if (isset($item['section'])) {
                            if ($item['section'] === 'treasures') {
                                $sectionName = isset($weekData['sections']['treasures']) ? $weekData['sections']['treasures'] : '성경에 담긴 보물';
                            } elseif ($item['section'] === 'ministry') {
                                $sectionName = isset($weekData['sections']['ministry']) ? $weekData['sections']['ministry'] : '야외 봉사에 힘쓰십시오';
                            } else {
                                $sectionName = isset($weekData['sections']['living']) ? $weekData['sections']['living'] : '그리스도인 생활';
                            }
                        }

                        $weekAssignments[] = array(
                            'year' => $weekInfo['year'],
                            'week' => $weekInfo['week'],
                            'dateRange' => $dateRange,
                            'section' => $sectionName,
                            'title' => $item['title'],
                            'order' => 2 + $programIndex
                        );
                    }

                    $programIndex++;
                }
            }

            // 기본 배정 확인 (맺음말, 마치는기도)
            if (!empty($weekData['assignments'])) {
                $closingAssignments = array(
                    'closing_remarks' => array('label' => '맺음말', 'order' => 1000),
                    'closing_prayer' => array('label' => '마치는 기도', 'order' => 1001)
                );

                foreach ($closingAssignments as $key => $info) {
                    if (!empty($weekData['assignments'][$key]) && trim($weekData['assignments'][$key]) === $loggedInUserName) {
                        $weekAssignments[] = array(
                            'year' => $weekInfo['year'],
                            'week' => $weekInfo['week'],
                            'dateRange' => $dateRange,
                            'section' => '',
                            'title' => $info['label'],
                            'order' => $info['order']
                        );
                    }
                }
            }

            // 주차 내에서 순서대로 정렬 후 전체 배열에 추가
            usort($weekAssignments, 'compareAssignmentOrder');

            foreach ($weekAssignments as $assignment) {
                unset($assignment['order']); // order 필드 제거
                $myUpcomingAssignments[] = $assignment;
            }
        }
    }

    // 가까운 미래부터 표시하기 위해 주차별로 그룹화 후 역순으로 재배치
    // 주차별로 그룹화
    $groupedByWeek = array();
    foreach ($myUpcomingAssignments as $assignment) {
        $key = $assignment['year'] . '_' . $assignment['week'];
        if (!isset($groupedByWeek[$key])) {
            $groupedByWeek[$key] = array();
        }
        $groupedByWeek[$key][] = $assignment;
    }

    // 그룹 순서를 역순으로 하여 다시 평면화
    $myUpcomingAssignments = array();
    foreach (array_reverse($groupedByWeek) as $weekGroup) {
        foreach ($weekGroup as $assignment) {
            $myUpcomingAssignments[] = $assignment;
        }
    }
}

// 배정 순서 정렬을 위한 비교 함수
function compareAssignmentOrder($a, $b) {
    return $a['order'] - $b['order'];
}

// 배정명 필터링을 위한 함수
function filterAssignedNames($v) {
    $trimmed = trim($v);
    return !empty($trimmed);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>생활과 봉사 집회 - <?php echo $data['date']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: white;
            padding: 2px;
            min-height: 100vh;
            font-size: 12px;
        }

        .container {
            max-width: 380px;
            margin: 0 auto;
            background: white;
            border-radius: 6px;
            padding: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .header {
            text-align: center;
            margin-bottom: 6px;
            padding-bottom: 6px;
            border-bottom: 2px solid #667eea;
        }

        .header h1 {
            color: #333;
            font-size: 15px;
            margin-bottom: 3px;
        }

        .header .date {
            color: #667eea;
            font-size: 13px;
            font-weight: 600;
        }

        .navigation {
            margin-bottom: 6px;
        }

        .nav-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            margin-bottom: 4px;
        }

        .nav-button {
            padding: 5px 6px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-weight: 600;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
            font-size: 11px;
        }

        .nav-button:hover {
            background: #5568d3;
        }

        /* 주차 선택 모달 */
        .week-selector-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .week-selector-modal.hidden {
            display: none;
        }

        .week-selector-content {
            background: white;
            border-radius: 8px;
            width: 100%;
            max-width: 360px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .week-selector-header {
            position: sticky;
            top: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 12px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .week-selector-title {
            font-weight: 700;
            font-size: 13px;
        }

        .week-selector-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 18px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            padding: 0;
        }

        .week-selector-close:hover {
            background: rgba(255,255,255,0.3);
        }

        .week-selector-year {
            background: #f5f5f5;
            padding: 8px 10px;
            font-weight: 700;
            color: #333;
            border-top: 1px solid #ddd;
            font-size: 11px;
        }

        .week-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            padding: 10px;
        }

        .week-item {
            padding: 8px 4px;
            background: #f5f5f5;
            border: 2px solid transparent;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 10px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .week-item:hover {
            background: #e3f2fd;
            border-color: #667eea;
        }

        .week-item.has-data {
            background: #e8f5e9;
        }

        .week-item.today {
            background: white;
        }

        .week-item.today.has-data {
            background: white;
        }

        .week-item.current {
            border-color: #f44336 !important;
            border-width: 3px;
            box-shadow: 0 0 8px rgba(244, 67, 54, 0.3);
        }

        .week-number {
            font-weight: 400;
            display: block;
            font-size: 9px;
            color: #999;
        }

        .week-date {
            font-size: 10px;
            font-weight: 700;
            color: #333;
            line-height: 1.2;
        }

        .week-item.has-data .week-date {
            color: #2e7d32;
        }

        .week-item.today .week-date {
            color: #1565c0;
        }

        .week-item .week-number {
            color: #999;
        }

        .url-link {
            text-align: center;
            margin-bottom: 6px;
        }

        .url-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 11px;
            word-break: break-all;
        }

        .url-link a:hover {
            text-decoration: underline;
        }

        .my-assignments-section {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }

        .my-assignments-title {
            font-size: 14px;
            font-weight: 700;
            color: #666;
            margin-bottom: 12px;
        }

        .my-assignment-item {
            display: block;
            padding: 8px 10px;
            margin-bottom: 6px;
            background: #f9f9f9;
            border-radius: 6px;
            font-size: 12px;
            line-height: 1.5;
            text-decoration: none;
            color: inherit;
            transition: background 0.2s;
        }

        .my-assignment-item:hover {
            background: #efefef;
        }

        .my-assignment-date {
            font-weight: 600;
            color: #666;
            margin-right: 8px;
        }

        .my-assignment-section {
            color: #999;
            margin-right: 4px;
        }

        .my-assignment-title {
            color: #333;
            font-weight: 600;
        }

        .bible-reading {
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
            padding: 6px;
            background: #f8f9ff;
            border-radius: 4px;
        }

        .section {
            margin-bottom: 10px;
        }

        .section-header {
            color: white;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .section-header.treasures {
            background: #4A919E;
        }

        .section-header.ministry {
            background: #E87722;
        }

        .section-header.living {
            background: #942926;
        }

        .section-icon {
            font-size: 14px;
        }

        .program-item {
            padding: 5px 6px;
            margin-bottom: 4px;
            background: #f9f9f9;
            border-radius: 4px;
            border-left: 3px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-treasures .program-item {
            border-left-color: #8DB9C4;
        }

        .section-ministry .program-item {
            border-left-color: #F0A366;
        }

        .section-living .program-item {
            border-left-color: #C16B6D;
        }

        .program-info {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .program-title {
            font-weight: 600;
            font-size: 11px;
            color: #333;
        }

        .program-duration {
            color: #888;
            font-size: 10px;
        }

        .program-assigned {
            background: white;
            color: #333;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            min-width: 60px;
            text-align: center;
        }

        .program-assigned.empty {
            background: white;
            color: #999;
        }

        .program-assigned.my-name {
            background: linear-gradient(135deg, #ef4444, #f97316) !important;
            color: #fff !important;
            font-weight: 700;
        }

        .assignment-row {
            display: flex;
            gap: 4px;
            margin-bottom: 4px;
        }

        .assignment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 6px;
            background: #fff;
            border-radius: 3px;
            border: 2px solid #e0e0e0;
            flex: 1;
        }

        .assignment-label {
            font-weight: 600;
            font-size: 10px;
            color: #555;
        }

        .assignment-name {
            background: white;
            color: #333;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 12px;
            min-width: 50px;
            text-align: center;
        }

        .assignment-name.empty {
            background: white;
            color: #999;
        }

        .assignment-name.my-name {
            background: linear-gradient(135deg, #ef4444, #f97316) !important;
            color: #fff !important;
            font-weight: 700;
        }

        .assignments-section {
            background: #f8f9ff;
            padding: 6px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .no-data {
            text-align: center;
            color: #999;
            font-style: italic;
            font-size: 11px;
            padding: 12px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                box-shadow: none;
                padding: 20px;
            }

            .navigation {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- 주차 선택 모달 -->
    <div id="weekSelectorModal" class="week-selector-modal hidden">
        <div class="week-selector-content">
            <div class="week-selector-header">
                <div class="week-selector-title">📅 주차 선택</div>
                <button class="week-selector-close" onclick="hideWeekSelector()">×</button>
            </div>
            <div id="weekSelectorBody"></div>
        </div>
    </div>

    <div class="container">
        <div class="navigation">
            <?php
            // 버튼 개수에 따라 그리드 컬럼 수 동적 조정
            $buttonCount = 2; // 기본: 이번주, 주차선택
            if ($prevWeekData !== null) $buttonCount++;
            if ($nextWeekData !== null) $buttonCount++;
            ?>
            <div class="nav-row" style="display: grid; grid-template-columns: repeat(<?php echo $buttonCount; ?>, 1fr); gap: 6px;">
                <?php if ($prevWeekData !== null): ?>
                    <a href="?year=<?php echo $prevWeekData['year']; ?>&week=<?php echo $prevWeekData['week']; ?>" class="nav-button" style="background: #667eea;">◀ 이전</a>
                <?php endif; ?>
                <a href="?year=<?php echo $currentYear; ?>&week=<?php echo $currentWeek; ?>" class="nav-button" style="background: #4CAF50;">📅 이번주</a>
                <button onclick="showWeekSelector()" class="nav-button" style="background: #FF9800;">📆 주차선택</button>
                <?php if ($nextWeekData !== null): ?>
                    <a href="?year=<?php echo $nextWeekData['year']; ?>&week=<?php echo $nextWeekData['week']; ?>" class="nav-button" style="background: #667eea;">다음 ▶</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="header">
            <div class="date"><?php echo htmlspecialchars($data['date']); ?></div>
        </div>

        <?php if (!empty($data['no_meeting']) && $data['no_meeting']): ?>
            <!-- 배정없음 표시 -->
            <div style="text-align: center; padding: 60px 20px; background: #fff3cd; border: 3px solid #ffc107; border-radius: 12px; margin: 40px 0;">
                <div style="font-size: 48px; margin-bottom: 20px;">📅</div>
                <div style="font-size: 24px; font-weight: 700; color: #856404; margin-bottom: 15px;">
                    <?php echo !empty($data['no_meeting_title']) ? htmlspecialchars($data['no_meeting_title']) : '배정없음'; ?>
                </div>
                <?php if (!empty($data['no_meeting_reason'])): ?>
                <div style="background: white; padding: 20px; border-radius: 8px; width: 100%; margin: 0 auto;">
                    <div style="font-size: 16px; color: #333; font-weight: 600; white-space: pre-line; text-align: left;"><?php echo htmlspecialchars($data['no_meeting_reason']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- 일반 프로그램 표시 -->
            <?php if (!empty($data['bible_reading'])): ?>
            <div class="bible-reading">
                <?php echo htmlspecialchars($data['bible_reading']); ?>
            </div>
            <?php endif; ?>


        <div class="assignments-section">
            <div class="assignment-row">
                <div class="assignment-item">
                    <span class="assignment-label">소개말</span>
                    <?php
                        $openingRemarksName = isset($data['assignments']['opening_remarks']) ? trim($data['assignments']['opening_remarks']) : '';
                        $isMyOpeningRemarks = !empty($loggedInUserName) && !empty($openingRemarksName) && $loggedInUserName === $openingRemarksName;
                        $openingRemarksClass = 'assignment-name';
                        if (empty($openingRemarksName)) {
                            $openingRemarksClass .= ' empty';
                        } elseif ($isMyOpeningRemarks) {
                            $openingRemarksClass .= ' my-name';
                        }
                    ?>
                    <span class="<?php echo $openingRemarksClass; ?>">
                        <?php echo !empty($openingRemarksName) ? htmlspecialchars($openingRemarksName) : '미배정'; ?>
                    </span>
                </div>
                <div class="assignment-item">
                    <span class="assignment-label">시작 기도</span>
                    <?php
                        $openingPrayerName = isset($data['assignments']['opening_prayer']) ? trim($data['assignments']['opening_prayer']) : '';
                        $isMyOpeningPrayer = !empty($loggedInUserName) && !empty($openingPrayerName) && $loggedInUserName === $openingPrayerName;
                        $openingPrayerClass = 'assignment-name';
                        if (empty($openingPrayerName)) {
                            $openingPrayerClass .= ' empty';
                        } elseif ($isMyOpeningPrayer) {
                            $openingPrayerClass .= ' my-name';
                        }
                    ?>
                    <span class="<?php echo $openingPrayerClass; ?>">
                        <?php echo !empty($openingPrayerName) ? htmlspecialchars($openingPrayerName) : '미배정'; ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if (!empty($categorized['treasures'])): ?>
        <div class="section section-treasures">
            <div class="section-header treasures">
                <span class="section-icon">💎</span>
                <span><?php echo htmlspecialchars($data['sections']['treasures']); ?></span>
            </div>
            <?php foreach ($categorized['treasures'] as $item): ?>
            <div class="program-item">
                <div class="program-info">
                    <span class="program-title"><?php echo htmlspecialchars($item['title']); ?></span>
                    <span class="program-duration"><?php echo htmlspecialchars($item['duration']); ?></span>
                </div>
                <?php
                    // assigned가 배열인 경우 빈 값 제외
                    $assignedNames = array();
                    if (is_array($item['assigned'])) {
                        $assignedNames = array_filter($item['assigned'], 'filterAssignedNames');
                    } elseif (!empty($item['assigned'])) {
                        $assignedNames = array($item['assigned']);
                    }
                ?>
                <?php if (empty($assignedNames)): ?>
                    <div class="program-assigned empty">미배정</div>
                <?php else: ?>
                    <?php foreach ($assignedNames as $name): ?>
                        <?php
                            $trimmedName = trim($name);
                            $isMyName = !empty($loggedInUserName) && !empty($trimmedName) && $loggedInUserName === $trimmedName;
                            $assignedClass = 'program-assigned';
                            if ($isMyName) {
                                $assignedClass .= ' my-name';
                            }
                        ?>
                        <div class="<?php echo $assignedClass; ?>">
                            <?php echo htmlspecialchars($trimmedName); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($categorized['ministry'])): ?>
        <div class="section section-ministry">
            <div class="section-header ministry">
                <span class="section-icon">🌾</span>
                <span><?php echo htmlspecialchars($data['sections']['ministry']); ?></span>
            </div>
            <?php foreach ($categorized['ministry'] as $item): ?>
            <div class="program-item">
                <div class="program-info">
                    <span class="program-title"><?php echo htmlspecialchars($item['title']); ?></span>
                    <span class="program-duration"><?php echo htmlspecialchars($item['duration']); ?></span>
                </div>
                <?php
                    // assigned가 배열인 경우 빈 값 제외
                    $assignedNames = array();
                    if (is_array($item['assigned'])) {
                        $assignedNames = array_filter($item['assigned'], 'filterAssignedNames');
                    } elseif (!empty($item['assigned'])) {
                        $assignedNames = array($item['assigned']);
                    }
                ?>
                <?php if (empty($assignedNames)): ?>
                    <div class="program-assigned empty">미배정</div>
                <?php else: ?>
                    <?php foreach ($assignedNames as $name): ?>
                        <?php
                            $trimmedName = trim($name);
                            $isMyName = !empty($loggedInUserName) && !empty($trimmedName) && $loggedInUserName === $trimmedName;
                            $assignedClass = 'program-assigned';
                            if ($isMyName) {
                                $assignedClass .= ' my-name';
                            }
                        ?>
                        <div class="<?php echo $assignedClass; ?>">
                            <?php echo htmlspecialchars($trimmedName); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($categorized['living'])): ?>
        <div class="section section-living">
            <div class="section-header living">
                <span class="section-icon">🐑</span>
                <span><?php echo htmlspecialchars($data['sections']['living']); ?></span>
            </div>
            <?php foreach ($categorized['living'] as $item): ?>
            <div class="program-item">
                <div class="program-info">
                    <span class="program-title"><?php echo htmlspecialchars($item['title']); ?></span>
                    <span class="program-duration"><?php echo htmlspecialchars($item['duration']); ?></span>
                </div>
                <?php
                    // assigned가 배열인 경우 빈 값 제외
                    $assignedNames = array();
                    if (is_array($item['assigned'])) {
                        $assignedNames = array_filter($item['assigned'], 'filterAssignedNames');
                    } elseif (!empty($item['assigned'])) {
                        $assignedNames = array($item['assigned']);
                    }
                ?>
                <?php if (empty($assignedNames)): ?>
                    <div class="program-assigned empty">미배정</div>
                <?php else: ?>
                    <?php foreach ($assignedNames as $name): ?>
                        <?php
                            $trimmedName = trim($name);
                            $isMyName = !empty($loggedInUserName) && !empty($trimmedName) && $loggedInUserName === $trimmedName;
                            $assignedClass = 'program-assigned';
                            if ($isMyName) {
                                $assignedClass .= ' my-name';
                            }
                        ?>
                        <div class="<?php echo $assignedClass; ?>">
                            <?php echo htmlspecialchars($trimmedName); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($data['program'])): ?>
        <div class="no-data">배정 정보가 없습니다.</div>
        <?php endif; ?>

            <div class="assignments-section">
                <div class="assignment-row">
                    <div class="assignment-item">
                        <span class="assignment-label">맺음말</span>
                        <?php
                            $closingRemarksName = isset($data['assignments']['closing_remarks']) ? trim($data['assignments']['closing_remarks']) : '';
                            $isMyClosingRemarks = !empty($loggedInUserName) && !empty($closingRemarksName) && $loggedInUserName === $closingRemarksName;
                            $closingRemarksClass = 'assignment-name';
                            if (empty($closingRemarksName)) {
                                $closingRemarksClass .= ' empty';
                            } elseif ($isMyClosingRemarks) {
                                $closingRemarksClass .= ' my-name';
                            }
                        ?>
                        <span class="<?php echo $closingRemarksClass; ?>">
                            <?php echo !empty($closingRemarksName) ? htmlspecialchars($closingRemarksName) : '미배정'; ?>
                        </span>
                    </div>
                    <div class="assignment-item">
                        <span class="assignment-label">마치는 기도</span>
                        <?php
                            $closingPrayerName = isset($data['assignments']['closing_prayer']) ? trim($data['assignments']['closing_prayer']) : '';
                            $isMyClosingPrayer = !empty($loggedInUserName) && !empty($closingPrayerName) && $loggedInUserName === $closingPrayerName;
                            $closingPrayerClass = 'assignment-name';
                            if (empty($closingPrayerName)) {
                                $closingPrayerClass .= ' empty';
                            } elseif ($isMyClosingPrayer) {
                                $closingPrayerClass .= ' my-name';
                            }
                        ?>
                        <span class="<?php echo $closingPrayerClass; ?>">
                            <?php echo !empty($closingPrayerName) ? htmlspecialchars($closingPrayerName) : '미배정'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (!empty($data['url'])): ?>
            <div class="url-link">
                <a href="<?php echo htmlspecialchars($data['url']); ?>" target="_blank"><?php echo htmlspecialchars($data['url']); ?></a>
            </div>
            <?php endif; ?>

            <?php if (!empty($myUpcomingAssignments)): ?>
            <div class="my-assignments-section">
                <div class="my-assignments-title">📋 이번 주 이후 나에게 배정된 특권</div>
                <?php foreach ($myUpcomingAssignments as $assignment): ?>
                <a href="view.php?year=<?php echo $assignment['year']; ?>&week=<?php echo $assignment['week']; ?>" class="my-assignment-item">
                    <span class="my-assignment-date"><?php echo htmlspecialchars($assignment['dateRange']); ?></span>
                    <?php if (!empty($assignment['section'])): ?>
                    <span class="my-assignment-section"><?php echo htmlspecialchars($assignment['section']); ?></span>
                    <?php endif; ?>
                    <span class="my-assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($is_admin): ?>
        <div style="text-align: center; margin-top: 10px; padding: 10px 20px;">
          <a href="index.php?year=<?php echo $year; ?>&week=<?php echo $week; ?>"
             id="adminBtn"
             class="admin-btn"
             style="display: inline-block;
                    padding: 8px 16px;
                    background: #f1f5f9;
                    color: #94a3b8;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 400;
                    font-size: 13px;
                    border: 1px solid #e2e8f0;
                    box-shadow: none;
                    transition: all 0.2s ease;">
            <span id="adminBtnText">관리자모드로 보기</span>
          </a>
        </div>
        <script>
          // iframe 안에서만 새창으로 열기
          (function() {
            const isInIframe = window.self !== window.top;
            const adminBtn = document.getElementById('adminBtn');
            const adminBtnText = document.getElementById('adminBtnText');

            if (isInIframe) {
              adminBtnText.textContent = '관리자모드로 보기 ↗';
              adminBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.open(this.href, '_blank', 'noopener,noreferrer');
              });
            }
          })();
        </script>
        <?php endif; ?>
    </div>

    <script>
        // 데이터 없음 경고 표시
        <?php if ($showNoDataAlert): ?>
        window.onload = function() {
            alert('해당 주차의 배정 정보가 없습니다. 이번 주차로 이동합니다.');
            // URL에서 nodata 파라미터 제거
            var url = new URL(window.location.href);
            url.searchParams.delete('nodata');
            window.history.replaceState({}, document.title, url.toString());
        };
        <?php endif; ?>

        // 주차 선택 모달 표시
        function showWeekSelector() {
            var formData = new FormData();
            formData.append('action', 'list_weeks');

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(result) {
                if (result.success) {
                    renderWeekSelector(result.weeks);
                    document.getElementById('weekSelectorModal').classList.remove('hidden');
                } else {
                    alert('주차 목록을 불러올 수 없습니다.');
                }
            })
            .catch(function(error) {
                alert('오류가 발생했습니다: ' + error.message);
            });
        }

        function hideWeekSelector() {
            document.getElementById('weekSelectorModal').classList.add('hidden');
        }

        function renderWeekSelector(availableWeeks) {
            var currentYear = <?php echo $currentYear; ?>;
            var currentWeek = <?php echo $currentWeek; ?>;
            var selectedYear = <?php echo $year; ?>;
            var selectedWeek = <?php echo $week; ?>;

            // 저장된 주차를 맵으로 변환
            var weekMap = {};
            var maxYear = currentYear;
            var maxWeek = currentWeek;

            for (var i = 0; i < availableWeeks.length; i++) {
                var w = availableWeeks[i];
                var key = w.year + '_' + w.week;
                weekMap[key] = true;

                // 가장 마지막 주차 찾기
                if (w.year > maxYear || (w.year === maxYear && w.week > maxWeek)) {
                    maxYear = w.year;
                    maxWeek = w.week;
                }
            }

            // 마지막 주차 + 1
            var endYear = maxYear;
            var endWeek = maxWeek + 1;
            if (endWeek > 52) {
                endWeek = 1;
                endYear++;
            }

            // 연도별로 그룹화 (JSON 파일이 있는 주차만)
            var yearGroups = {};
            var years = [];

            for (var i = 0; i < availableWeeks.length; i++) {
                var w = availableWeeks[i];
                var year = w.year;
                var week = w.week;

                if (!yearGroups[year]) {
                    years.push(year);
                    yearGroups[year] = [];
                }

                var isCurrent = (year === selectedYear && week === selectedWeek);
                var isToday = (year === currentYear && week === currentWeek);

                var noMeeting = w.no_meeting || false;

                yearGroups[year].push({
                    year: year,
                    week: week,
                    hasData: !noMeeting,  // 배정없음이면 hasData는 false
                    isCurrent: isCurrent,
                    isToday: isToday,
                    noMeeting: noMeeting,
                    noMeetingTitle: w.no_meeting_title || '',
                    noMeetingReason: w.no_meeting_reason || ''
                });
            }

            // 연도를 오름차순 정렬
            years.sort(function(a, b) {
                return a - b;
            });

            // 각 연도의 주차를 오름차순 정렬
            for (var y = 0; y < years.length; y++) {
                var year = years[y];
                yearGroups[year].sort(function(a, b) {
                    return a.week - b.week;
                });
            }

            // HTML 생성
            var html = '';
            for (var y = 0; y < years.length; y++) {
                var year = years[y];
                html += '<div class="week-selector-year">' + year + '년</div>';
                html += '<div class="week-grid">';

                var weeks = yearGroups[year];
                for (var w = 0; w < weeks.length; w++) {
                    var weekData = weeks[w];
                    var classes = ['week-item'];
                    if (weekData.hasData) classes.push('has-data');
                    if (weekData.isCurrent) classes.push('current');
                    if (weekData.isToday) classes.push('today');

                    var dateRange = getWeekDateRange(weekData.year, weekData.week);

                    html += '<div class="' + classes.join(' ') + '" onclick="selectWeek(' + weekData.year + ', ' + weekData.week + ')">';
                    if (weekData.noMeeting) {
                        html += '<span class="week-date" style="color: #ff9800; font-weight: bold; font-size: 10px; display: block;">배정없음</span>';
                        if (weekData.noMeetingTitle) {
                            // 제목만 표시
                            html += '<span class="week-date" style="color: #666; font-size: 9px; display: block; margin-top: 2px;">' + weekData.noMeetingTitle + '</span>';
                        } else if (weekData.noMeetingReason) {
                            // 제목이 없으면 상세 사유의 처음 2줄만 표시
                            var lines = weekData.noMeetingReason.split('\n');
                            var displayText = lines.slice(0, 2).join(' ');
                            if (lines.length > 2) displayText += '...';
                            html += '<span class="week-date" style="color: #666; font-size: 9px; display: block; margin-top: 2px;">' + displayText + '</span>';
                        }
                    } else {
                        html += '<span class="week-date">' + dateRange + '</span>';
                        if (weekData.hasData) {
                            html += '<span class="week-date" style="color: #4CAF50; font-weight: normal; font-size: 11px; display: block;">✓</span>';
                        }
                    }
                    html += '<span class="week-number">' + weekData.week + '주</span>';
                    html += '</div>';
                }

                html += '</div>';
            }

            document.getElementById('weekSelectorBody').innerHTML = html;
        }

        function selectWeek(year, week) {
            window.location.href = '?year=' + year + '&week=' + week;
        }

        // 주차 번호를 날짜 범위로 변환
        function getWeekDateRange(year, week) {
            // ISO 8601 주차 계산
            var jan4 = new Date(year, 0, 4);
            var jan4Day = jan4.getDay() || 7;
            var weekStart = new Date(jan4);
            weekStart.setDate(jan4.getDate() - jan4Day + 1 + (week - 1) * 7);

            var weekEnd = new Date(weekStart);
            weekEnd.setDate(weekStart.getDate() + 6);

            var startMonth = weekStart.getMonth() + 1;
            var startDate = weekStart.getDate();
            var endMonth = weekEnd.getMonth() + 1;
            var endDate = weekEnd.getDate();

            return startMonth + '/' + startDate + '~' + endMonth + '/' + endDate;
        }
    </script>
</body>
</html>
