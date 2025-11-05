<?php
// 로컬 개발 모드 체크
$localConfigFile = __DIR__ . '/../c/config.php';
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

    // 관리자가 아니면 view.php로 리다이렉트
    if (!$is_admin) {
        header('Location: view.php' . (isset($_GET['year']) && isset($_GET['week']) ? '?year='.$_GET['year'].'&week='.$_GET['week'] : ''));
        exit;
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

// 사용 가능한 주차 목록 가져오기
$availableWeeks = $manager->getAvailableWeeks();

// 현재 주차의 인덱스 찾기
$currentIndex = -1;
for ($i = 0; $i < count($availableWeeks); $i++) {
    if ($availableWeeks[$i]['year'] == $year && $availableWeeks[$i]['week'] == $week) {
        $currentIndex = $i;
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

// 기본값 설정 (데이터가 없을 경우를 위한 계산)
$prevWeek = $week - 1;
$prevYear = $year;
if ($prevWeek < 1) {
    $prevWeek = 52;
    $prevYear--;
}

$nextWeek = $week + 1;
$nextYear = $year;
if ($nextWeek > 52) {
    $nextWeek = 1;
    $nextYear++;
}

// 주차의 날짜 범위 계산 (ISO 8601)
$jan4 = new DateTime($year . '-01-04');
$jan4Day = $jan4->format('N'); // 1(월요일) ~ 7(일요일)
$weekStart = clone $jan4;
$weekStart->modify('-' . ($jan4Day - 1) . ' days');
$weekStart->modify('+' . (($week - 1) * 7) . ' days');
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');

$dateRange = $weekStart->format('n/j') . '~' . $weekEnd->format('n/j');

// 데이터 로드 (웹에서 자동으로 가져오지 않음)
$data = $manager->load($year, $week);
if ($data === null) {
    $data = $manager->createEmpty($year, $week);
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

// 로그인한 사용자의 배정된 주차 수집
$myAssignedWeeks = array();
if (!empty($loggedInUserName)) {
    // 실제 현재 날짜 기준 주차 계산
    $currentYearNow = (int)date('Y');
    $currentWeekNow = (int)date('W');

    $allWeeks = $manager->getAvailableWeeks();

    foreach ($allWeeks as $weekInfo) {
        // 실제 이번 주 포함 미래인 경우 확인
        if ($weekInfo['year'] > $currentYearNow || ($weekInfo['year'] == $currentYearNow && $weekInfo['week'] >= $currentWeekNow)) {
            $weekData = $manager->load($weekInfo['year'], $weekInfo['week']);

            if (!$weekData || !empty($weekData['no_meeting'])) {
                continue;
            }

            $isAssigned = false;

            // 기본 배정 확인 (소개말, 시작기도, 맺음말, 마치는기도)
            if (!empty($weekData['assignments'])) {
                $basicAssignments = array('opening_remarks', 'opening_prayer', 'closing_remarks', 'closing_prayer');
                foreach ($basicAssignments as $key) {
                    if (!empty($weekData['assignments'][$key]) && trim($weekData['assignments'][$key]) === $loggedInUserName) {
                        $isAssigned = true;
                        break;
                    }
                }
            }

            // 프로그램 항목 확인
            if (!$isAssigned && !empty($weekData['program'])) {
                foreach ($weekData['program'] as $item) {
                    if (is_array($item['assigned'])) {
                        foreach ($item['assigned'] as $assignedName) {
                            $trimmedAssignedName = trim($assignedName);
                            if (!empty($trimmedAssignedName) && $trimmedAssignedName === $loggedInUserName) {
                                $isAssigned = true;
                                break 2;
                            }
                        }
                    } elseif (!empty($item['assigned'])) {
                        $trimmedAssigned = trim($item['assigned']);
                        if ($trimmedAssigned === $loggedInUserName) {
                            $isAssigned = true;
                            break;
                        }
                    }
                }
            }

            if ($isAssigned) {
                $myAssignedWeeks[] = $weekInfo['year'] . '_' . $weekInfo['week'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>집회 프로그램 관리자 - <?php echo $data['date']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
            font-size: 14px;
        }

        .container {
            max-width: 600px;
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
            font-size: 17px;
            margin-bottom: 3px;
        }

        .header .subtitle {
            color: #666;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .date-edit {
            width: 100%;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 10px;
            transition: border-color 0.3s;
        }

        .date-edit:focus {
            outline: none;
            border-color: #667eea;
        }

        .navigation {
            display: flex;
            flex-direction: column;
            margin-bottom: 6px;
            gap: 4px;
            position: relative;
        }

        .nav-row {
            display: flex;
            justify-content: space-between;
            gap: 4px;
        }

        .nav-button, .action-button {
            padding: 5px 6px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 3px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 13px;
            white-space: nowrap;
            flex: 1;
        }

        .nav-button:hover, .action-button:hover {
            background: #5568d3;
        }

        .action-button.refresh {
            background: #e0e0e0;
            color: #666;
        }

        .action-button.refresh:hover {
            background: #d0d0d0;
        }

        .action-button.save {
            background: #4CAF50;
        }

        .action-button.save:hover {
            background: #45a049;
        }

        .action-button.preview {
            background: #e0e0e0;
            color: #666;
        }

        .action-button.preview:hover {
            background: #d0d0d0;
        }

        .url-edit, .bible-edit {
            width: 100%;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 10px;
            transition: border-color 0.3s;
        }

        .url-edit:focus, .bible-edit:focus {
            outline: none;
            border-color: #667eea;
        }

        .no-meeting-section {
            margin-bottom: 12px;
            padding: 10px;
            background: #f5f5f5;
            border: none;
            border-radius: 6px;
        }

        .no-meeting-label {
            display: flex;
            align-items: center;
            font-weight: 600;
            font-size: 13px;
            color: #666;
            cursor: pointer;
            margin-bottom: 8px;
        }

        .no-meeting-label input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            cursor: pointer;
        }

        .no-meeting-reason {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            background: white;
            resize: vertical;
            min-height: 60px;
        }

        .no-meeting-reason:focus {
            outline: none;
            border-color: #999;
        }

        .no-meeting-title {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            background: white;
            margin-bottom: 8px;
        }

        .no-meeting-title:focus {
            outline: none;
            border-color: #999;
        }

        .bible-reading {
            text-align: center;
            margin-bottom: 6px;
        }

        .assignments-section {
            background: #f8f9ff;
            padding: 6px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .assignment-row {
            display: flex;
            gap: 4px;
            margin-bottom: 4px;
        }

        .assignment-item {
            display: flex;
            align-items: center;
            gap: 3px;
            padding: 4px 6px;
            background: #fff;
            border-radius: 3px;
            border: 2px solid #e0e0e0;
            flex: 1;
        }

        .assignment-label {
            font-weight: 600;
            font-size: 12px;
            color: #555;
            white-space: nowrap;
        }

        .assignment-input {
            width: 45px;
            padding: 4px 5px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 12px;
            transition: border-color 0.3s;
        }

        .assignment-input:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .assignment-input::placeholder {
            color: #d1d5db;
            opacity: 0.6;
        }

        .section {
            margin-bottom: 10px;
        }

        .section-header {
            color: white;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 14px;
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
            font-size: 16px;
        }

        .section-title-edit {
            background: transparent;
            border: none;
            color: white;
            font-size: 14px;
            font-weight: 700;
            flex: 1;
            padding: 0;
        }

        .section-title-edit:focus {
            outline: none;
            background: rgba(255,255,255,0.1);
            padding: 3px 6px;
            border-radius: 3px;
        }

        .program-item {
            padding: 5px 6px;
            margin-bottom: 4px;
            background: #f9f9f9;
            border-radius: 4px;
            border-left: 3px solid #ddd;
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

        .program-header {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }

        .program-title-container {
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
            min-width: 150px;
        }

        .program-title-edit {
            flex: 1;
            padding: 4px 6px;
            border: 2px solid #e0e0e0;
            border-radius: 3px;
            font-size: 13px;
            font-weight: 600;
        }

        .program-duration-edit {
            width: 40px;
            padding: 4px;
            border: 2px solid #e0e0e0;
            border-radius: 3px;
            font-size: 12px;
            color: #888;
        }

        .program-title-edit:focus, .program-duration-edit:focus {
            outline: none;
            border-color: #667eea;
        }

        .program-title-edit::placeholder,
        .program-duration-edit::placeholder,
        .program-assigned-edit::placeholder {
            color: #d1d5db;
            opacity: 0.6;
        }

        .program-assigned-container {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .program-assigned-label {
            font-weight: 600;
            color: #555;
            font-size: 11px;
            white-space: nowrap;
        }

        .program-assigned-edit {
            padding: 4px 5px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 12px;
            width: 45px;
            transition: border-color 0.3s;
        }

        .program-assigned-edit:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-remove {
            background: #f44336;
            color: white;
            border: none;
            padding: 3px 6px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            line-height: 1;
            min-width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-remove:hover {
            background: #d32f2f;
        }

        .btn-add {
            width: 100%;
            padding: 8px;
            background: #ddd;
            color: #666;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            margin-top: 6px;
        }

        .btn-add:hover {
            background: #ccc;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
        }

        .hidden {
            display: none;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none; /* 기본적으로 숨김 */
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-overlay:not(.hidden) {
            display: flex; /* hidden 클래스가 없을 때만 표시 */
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 6px solid #f3f3f3;
            border-top: 6px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: white;
            font-size: 22px;
            font-weight: 600;
            text-align: center;
            white-space: pre-line;
            line-height: 1.6;
        }

        /* 주차 선택 오버레이 */
        .week-selector-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .week-selector-overlay.active {
            display: block;
        }

        .week-selector-modal {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 8px;
            z-index: 1000;
            width: 100%;
            max-width: 380px;
            display: block;
        }

        .week-selector-modal.hidden {
            display: none !important;
        }

        .week-selector-content {
            background: white;
            border-radius: 8px;
            width: 100%;
            max-height: 600px;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .week-selector-header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 8px;
        }

        .week-selector-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }

        .week-selector-close {
            background: #f0f0f0;
            border: none;
            color: #666;
            font-size: 20px;
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
            background: #e0e0e0;
        }

        .week-selector-year {
            background: #f5f5f5;
            padding: 8px 10px;
            font-weight: 700;
            color: #333;
            border-top: 1px solid #ddd;
            font-size: 13px;
        }

        .week-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            padding: 10px;
        }

        .week-item {
            padding: 8px 4px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            gap: 3px;
            position: relative;
        }

        .week-item:hover {
            background: #e3f2fd;
            border-color: #667eea;
        }

        .week-item.has-data {
            background: white;
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
            font-size: 11px;
            color: #999;
        }

        .week-date {
            font-size: 12px;
            font-weight: 700;
            color: #333;
            line-height: 1.2;
        }

        .week-item.has-data .week-date {
            color: #333;
        }

        .week-item.today .week-date {
            color: #f44336;
        }

        .week-item .week-number {
            color: #999;
        }

        @media (min-width: 768px) {
            .week-grid {
                grid-template-columns: repeat(3, 1fr);
            }
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

            .navigation, .actions, .btn-remove {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- 로딩 오버레이 -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="loading-spinner"></div>
        <div class="loading-text" id="loadingText">처리 중입니다...</div>
    </div>

    <div class="container">
        <div class="navigation">
            <div class="nav-row" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;">
                <?php if ($prevWeekData !== null): ?>
                    <a href="?year=<?php echo $prevWeekData['year']; ?>&week=<?php echo $prevWeekData['week']; ?>" class="nav-button" style="background: #667eea;">◀ 이전</a>
                <?php else: ?>
                    <span class="nav-button" style="background: #ccc; color: #888; cursor: not-allowed; pointer-events: none;">◀ 이전</span>
                <?php endif; ?>
                <a href="?year=<?php echo $currentYear; ?>&week=<?php echo $currentWeek; ?>" class="nav-button" style="background: #4CAF50;">📅 이번주</a>
                <button onclick="showWeekSelector()" class="action-button" style="background: #FF9800;">📆 선택</button>
                <?php if ($nextWeekData !== null): ?>
                    <a href="?year=<?php echo $nextWeekData['year']; ?>&week=<?php echo $nextWeekData['week']; ?>" class="nav-button" style="background: #667eea;">다음 ▶</a>
                <?php else: ?>
                    <span class="nav-button" style="background: #ccc; color: #888; cursor: not-allowed; pointer-events: none;">다음 ▶</span>
                <?php endif; ?>
            </div>

            <!-- 주차 선택 오버레이 -->
            <div id="weekSelectorOverlay" class="week-selector-overlay" onclick="hideWeekSelector()"></div>

            <!-- 주차 선택 모달 -->
            <div id="weekSelectorModal" class="week-selector-modal hidden">
                <div class="week-selector-content">
                    <div class="week-selector-header">
                        <button class="week-selector-close" onclick="hideWeekSelector()">×</button>
                    </div>
                    <div id="weekSelectorBody"></div>
                </div>
            </div>
        </div>

        <div class="header">
            <div class="subtitle">관리자 모드 - <?php echo $year; ?>년 <?php echo $week; ?>주차 (<?php echo $dateRange; ?>)</div>
        </div>

        <input type="hidden" id="year" value="<?php echo $year; ?>">
        <input type="hidden" id="week" value="<?php echo $week; ?>">

        <!-- 프로그램 입력 영역 -->
        <div id="program-content" style="<?php echo (!empty($data['no_meeting']) && $data['no_meeting']) ? 'display:none;' : ''; ?>">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
            <span style="font-weight: 600; font-size: 13px; color: #555; white-space: nowrap;">날짜</span>
            <input type="text" class="date-edit" id="date" value="<?php echo htmlspecialchars($data['date']); ?>" placeholder="날짜 입력 (예: 11월 3-9일)" style="flex: 1;">
        </div>
        <div class="bible-reading" style="display: flex; align-items: center; gap: 8px;">
            <span style="font-weight: 600; font-size: 13px; color: #555; white-space: nowrap;">성구</span>
            <input type="text" class="bible-edit" id="bible_reading" value="<?php echo htmlspecialchars($data['bible_reading']); ?>" placeholder="성경 읽기 범위 입력 (예: 솔로몬의 노래 1-2장)" style="flex: 1;">
        </div>

        <div class="assignments-section">
            <div class="assignment-row">
                <div class="assignment-item">
                    <span class="assignment-label">소개말</span>
                    <input type="text" class="assignment-input" id="opening_remarks" value="<?php echo htmlspecialchars($data['assignments']['opening_remarks']); ?>" placeholder="이름">
                </div>
                <div class="assignment-item">
                    <span class="assignment-label">시작 기도</span>
                    <input type="text" class="assignment-input" id="opening_prayer" value="<?php echo htmlspecialchars($data['assignments']['opening_prayer']); ?>" placeholder="이름">
                </div>
            </div>
        </div>

        <!-- 성경에 담긴 보물 -->
        <div class="section section-treasures">
            <div class="section-header treasures">
                <span class="section-icon">💎</span>
                <input type="text" class="section-title-edit" id="section_treasures" value="<?php echo htmlspecialchars($data['sections']['treasures']); ?>">
            </div>
            <div id="treasuresContainer">
                <?php foreach ($categorized['treasures'] as $index => $item): ?>
                <div class="program-item" data-section="treasures" data-index="<?php echo $index; ?>">
                    <div class="program-header">
                        <div class="program-title-container">
                            <input type="text" class="program-title-edit" value="<?php echo htmlspecialchars($item['title']); ?>" placeholder="제목">
                            <input type="text" class="program-duration-edit" value="<?php echo htmlspecialchars($item['duration']); ?>" placeholder="시간">
                        </div>
                        <div class="program-assigned-container">
                            <input type="text" class="program-assigned-edit" value="<?php echo htmlspecialchars(is_array($item['assigned']) ? $item['assigned'][0] : $item['assigned']); ?>" placeholder="이름">
                            <input type="text" class="program-assigned-edit" value="<?php echo htmlspecialchars(is_array($item['assigned']) && isset($item['assigned'][1]) ? $item['assigned'][1] : ''); ?>" placeholder="이름">
                        </div>
                        <button type="button" class="btn-remove" onclick="removeProgram(this)">×</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn-add" onclick="addProgram('treasures')">+ 항목 추가</button>
        </div>

        <!-- 야외 봉사에 힘쓰십시오 -->
        <div class="section section-ministry">
            <div class="section-header ministry">
                <span class="section-icon">🌾</span>
                <input type="text" class="section-title-edit" id="section_ministry" value="<?php echo htmlspecialchars($data['sections']['ministry']); ?>">
            </div>
            <div id="ministryContainer">
                <?php foreach ($categorized['ministry'] as $index => $item): ?>
                <div class="program-item" data-section="ministry" data-index="<?php echo $index; ?>">
                    <div class="program-header">
                        <div class="program-title-container">
                            <input type="text" class="program-title-edit" value="<?php echo htmlspecialchars($item['title']); ?>" placeholder="제목">
                            <input type="text" class="program-duration-edit" value="<?php echo htmlspecialchars($item['duration']); ?>" placeholder="시간">
                        </div>
                        <div class="program-assigned-container">
                            <input type="text" class="program-assigned-edit" value="<?php echo htmlspecialchars(is_array($item['assigned']) ? $item['assigned'][0] : $item['assigned']); ?>" placeholder="이름">
                            <input type="text" class="program-assigned-edit" value="<?php echo htmlspecialchars(is_array($item['assigned']) && isset($item['assigned'][1]) ? $item['assigned'][1] : ''); ?>" placeholder="이름">
                        </div>
                        <button type="button" class="btn-remove" onclick="removeProgram(this)">×</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn-add" onclick="addProgram('ministry')">+ 항목 추가</button>
        </div>

        <!-- 그리스도인 생활 -->
        <div class="section section-living">
            <div class="section-header living">
                <span class="section-icon">🐑</span>
                <input type="text" class="section-title-edit" id="section_living" value="<?php echo htmlspecialchars($data['sections']['living']); ?>">
            </div>
            <div id="livingContainer">
                <?php foreach ($categorized['living'] as $index => $item): ?>
                <div class="program-item" data-section="living" data-index="<?php echo $index; ?>">
                    <div class="program-header">
                        <div class="program-title-container">
                            <input type="text" class="program-title-edit" value="<?php echo htmlspecialchars($item['title']); ?>" placeholder="제목">
                            <input type="text" class="program-duration-edit" value="<?php echo htmlspecialchars($item['duration']); ?>" placeholder="시간">
                        </div>
                        <div class="program-assigned-container">
                            <input type="text" class="program-assigned-edit" value="<?php echo htmlspecialchars(is_array($item['assigned']) ? $item['assigned'][0] : $item['assigned']); ?>" placeholder="이름">
                            <input type="text" class="program-assigned-edit" value="<?php echo htmlspecialchars(is_array($item['assigned']) && isset($item['assigned'][1]) ? $item['assigned'][1] : ''); ?>" placeholder="이름">
                        </div>
                        <button type="button" class="btn-remove" onclick="removeProgram(this)">×</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn-add" onclick="addProgram('living')">+ 항목 추가</button>
        </div>

        <div class="assignments-section">
            <div class="assignment-row">
                <div class="assignment-item">
                    <span class="assignment-label">맺음말</span>
                    <input type="text" class="assignment-input" id="closing_remarks" value="<?php echo htmlspecialchars($data['assignments']['closing_remarks']); ?>" placeholder="이름">
                </div>
                <div class="assignment-item">
                    <span class="assignment-label">마치는 기도</span>
                    <input type="text" class="assignment-input" id="closing_prayer" value="<?php echo htmlspecialchars($data['assignments']['closing_prayer']); ?>" placeholder="이름">
                </div>
            </div>
        </div>

        <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <span style="font-weight: 600; font-size: 13px; color: #555; white-space: nowrap;">WOL</span>
            <input type="text" class="url-edit" id="url" value="<?php echo htmlspecialchars($data['url']); ?>" placeholder="URL 입력 (예: https://wol.jw.org/...)" style="flex: 1;">
        </div>
        </div><!-- 프로그램 입력 영역 끝 -->

        <!-- 배정없음 섹션 -->
        <div class="no-meeting-section" style="margin-top: 15px;">
            <label class="no-meeting-label">
                <input type="checkbox" id="no_meeting" <?php echo (!empty($data['no_meeting']) && $data['no_meeting']) ? 'checked' : ''; ?>>
                <span>배정없음</span>
            </label>
            <p style="font-size: 12px; color: #666; margin: 0 0 8px 0; line-height: 1.4;">
                대회, 순회 방문, 기념식 주간 등 정규 집회가 없는 경우에 사용하세요.
            </p>
            <input type="text" class="no-meeting-title" id="no_meeting_title" placeholder="제목 입력 (예: 대회)" value="<?php echo htmlspecialchars(isset($data['no_meeting_title']) ? $data['no_meeting_title'] : ''); ?>" style="<?php echo (empty($data['no_meeting']) || !$data['no_meeting']) ? 'display:none;' : ''; ?>">
            <textarea class="no-meeting-reason" id="no_meeting_reason" placeholder="상세 사유 입력 (예: 지역대회 주간)" rows="10" style="<?php echo (empty($data['no_meeting']) || !$data['no_meeting']) ? 'display:none;' : ''; ?>"><?php echo htmlspecialchars(isset($data['no_meeting_reason']) ? $data['no_meeting_reason'] : ''); ?></textarea>
        </div>

        <div class="actions">
            <button onclick="saveData()" class="action-button save">💾 저장하기</button>
        </div>

        <div style="margin-top: 20px; border-top: 1px solid #e0e0e0; padding-top: 15px;">
            <div style="background: #f8f9ff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px; margin-bottom: 10px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                    <span style="font-weight: 600; font-size: 14px; color: #333;">사용자모드로 보기</span>
                </div>
                <p style="font-size: 12px; color: #666; margin-bottom: 8px; line-height: 1.4;">
                    현재 입력한 내용을 사용자 화면에서 확인할 수 있습니다. 저장되지 않은 내용은 반영되지 않으니, 저장 후 확인하세요.
                </p>
                <a href="view.php?year=<?php echo $year; ?>&week=<?php echo $week; ?>" class="action-button preview" style="width: 100%; margin: 0; display: block; text-align: center; text-decoration: none;">👁️ 사용자모드로 보기</a>
            </div>

            <div id="web-fetch-section" style="<?php echo (!empty($data['no_meeting']) && $data['no_meeting']) ? 'display:none;' : ''; ?>">
                <div style="background: #f8f9ff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                        <span style="font-weight: 600; font-size: 14px; color: #333;">웹에서 가져오기</span>
                    </div>
                    <p style="font-size: 12px; color: #666; margin-bottom: 8px; line-height: 1.4;">
                        공식 웹사이트에서 이번 주차의 프로그램 데이터를 가져옵니다. 기존 배정 정보는 유지되며, 가져온 후 "저장하기" 버튼을 눌러야 적용됩니다.
                    </p>
                    <button onclick="fetchFromWeb()" class="action-button refresh" style="width: 100%; margin: 0;">🌐 웹에서 가져오기</button>
                </div>
            </div>

            <div style="background: #fff5f5; border: 1px solid #ffcccc; border-radius: 6px; padding: 10px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                    <span style="font-weight: 600; font-size: 14px; color: #d32f2f;">⚠️ 위험: 데이터 삭제</span>
                </div>
                <p style="font-size: 12px; color: #666; margin-bottom: 8px; line-height: 1.4;">
                    현재 주차의 데이터를 영구 삭제합니다. 삭제된 데이터는 백업 폴더에 보관되지만, 복구를 위해서는 관리자에게 문의해야 합니다. 신중히 사용하세요.
                </p>
                <button onclick="deleteData()" style="padding: 4px 8px; font-size: 12px; background: #d32f2f; color: white; border: none; border-radius: 4px; cursor: pointer; display: inline-block;">🗑️ 삭제</button>
            </div>
        </div>
    </div>

    <script>
        var programIndex = <?php echo count($data['program']); ?>;

        // 로그인한 사용자의 배정이 있는 주차 목록
        var myAssignedWeeks = <?php echo json_encode($myAssignedWeeks); ?>;

        // 로딩 오버레이 제어
        function showLoading(text) {
            text = text || '처리 중입니다...';
            document.getElementById('loadingText').textContent = text;
            document.getElementById('loadingOverlay').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
        }

        // 즉시 로딩 오버레이 숨기기
        hideLoading();

        // 배정없음 체크박스 이벤트
        document.getElementById('no_meeting').addEventListener('change', function() {
            var titleInput = document.getElementById('no_meeting_title');
            var reasonInput = document.getElementById('no_meeting_reason');
            var programContent = document.getElementById('program-content');
            var webFetchSection = document.getElementById('web-fetch-section');
            if (this.checked) {
                titleInput.style.display = 'block';
                reasonInput.style.display = 'block';
                programContent.style.display = 'none';
                webFetchSection.style.display = 'none';
            } else {
                titleInput.style.display = 'none';
                reasonInput.style.display = 'none';
                programContent.style.display = 'block';
                webFetchSection.style.display = 'block';
            }
        });

        function addProgram(section) {
            var container = document.getElementById(section + 'Container');
            var index = programIndex++;

            var div = document.createElement('div');
            div.className = 'program-item';
            div.setAttribute('data-section', section);
            div.setAttribute('data-index', index);
            div.innerHTML = '<div class="program-header">' +
                '<div class="program-title-container">' +
                '<input type="text" class="program-title-edit" value="" placeholder="제목">' +
                '<input type="text" class="program-duration-edit" value="" placeholder="시간">' +
                '</div>' +
                '<div class="program-assigned-container">' +
                '<input type="text" class="program-assigned-edit" value="" placeholder="이름">' +
                '<input type="text" class="program-assigned-edit" value="" placeholder="이름">' +
                '</div>' +
                '<button type="button" class="btn-remove" onclick="removeProgram(this)">×</button>' +
                '</div>';

            container.appendChild(div);
        }

        function removeProgram(button) {
            if (confirm('이 항목을 삭제하시겠습니까?')) {
                button.closest('.program-item').remove();
            }
        }

        function collectData() {
            var program = [];

            // 모든 섹션의 프로그램 수집 (섹션 정보 포함)
            var sections = ['treasures', 'ministry', 'living'];
            for (var i = 0; i < sections.length; i++) {
                var section = sections[i];
                var container = document.getElementById(section + 'Container');
                var items = container.querySelectorAll('.program-item');

                for (var j = 0; j < items.length; j++) {
                    var item = items[j];
                    var title = item.querySelector('.program-title-edit').value.trim();
                    var duration = item.querySelector('.program-duration-edit').value.trim();
                    var assignedInputs = item.querySelectorAll('.program-assigned-edit');
                    var assigned = [
                        assignedInputs[0] ? assignedInputs[0].value.trim() : '',
                        assignedInputs[1] ? assignedInputs[1].value.trim() : ''
                    ];

                    if (title) {
                        program.push({
                            title: title,
                            duration: duration,
                            assigned: assigned,
                            section: section
                        });
                    }
                }
            }

            return {
                year: parseInt(document.getElementById('year').value),
                week: parseInt(document.getElementById('week').value),
                url: document.getElementById('url').value.trim(),
                date: document.getElementById('date').value.trim(),
                bible_reading: document.getElementById('bible_reading').value.trim(),
                no_meeting: document.getElementById('no_meeting').checked,
                no_meeting_title: document.getElementById('no_meeting_title').value.trim(),
                no_meeting_reason: document.getElementById('no_meeting_reason').value.trim(),
                sections: {
                    treasures: document.getElementById('section_treasures').value.trim(),
                    ministry: document.getElementById('section_ministry').value.trim(),
                    living: document.getElementById('section_living').value.trim()
                },
                program: program,
                assignments: {
                    opening_remarks: document.getElementById('opening_remarks').value.trim(),
                    closing_remarks: document.getElementById('closing_remarks').value.trim(),
                    opening_prayer: document.getElementById('opening_prayer').value.trim(),
                    closing_prayer: document.getElementById('closing_prayer').value.trim()
                }
            };
        }

        function saveData() {
            var data = collectData();
            console.log('Saving data:', data);

            var formData = new FormData();
            formData.append('action', 'save');
            formData.append('year', data.year);
            formData.append('week', data.week);
            formData.append('data', JSON.stringify(data));

            // 로딩 오버레이 표시
            showLoading('저장 중입니다...');

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(function(result) {
                console.log('Result:', result);
                if (result.success) {
                    // 성공 메시지 표시
                    showLoading('✓ 저장되었습니다!');
                    // 1.5초 후 오버레이 숨김
                    setTimeout(function() {
                        hideLoading();
                    }, 1500);
                } else {
                    hideLoading();
                    alert('저장에 실패했습니다: ' + (result.error || '알 수 없는 오류'));
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                hideLoading();
                alert('저장 중 오류가 발생했습니다: ' + error.message);
            });
        }

        function deleteData() {
            if (!confirm('현재 주차의 데이터를 삭제하시겠습니까?\n삭제된 데이터는 백업 폴더에 보관됩니다.')) {
                return;
            }

            var year = document.getElementById('year').value;
            var week = document.getElementById('week').value;

            var formData = new FormData();
            formData.append('action', 'delete');
            formData.append('year', year);
            formData.append('week', week);

            // 로딩 오버레이 표시
            showLoading('삭제 중입니다...');

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(result) {
                hideLoading();
                if (result.success) {
                    alert('삭제되었습니다.');
                    // 주차 선택 모달 표시
                    showWeekSelector();
                } else {
                    alert('삭제에 실패했습니다: ' + (result.error || '알 수 없는 오류'));
                }
            })
            .catch(function(error) {
                hideLoading();
                alert('삭제 중 오류가 발생했습니다: ' + error.message);
            });
        }

        function fetchFromWeb() {
            if (!confirm('웹에서 데이터를 가져오시겠습니까?\n현재 입력한 내용은 사라집니다.')) {
                return;
            }

            // 로딩 오버레이 표시
            showLoading('웹에서 데이터를 가져오는 중입니다...');

            var year = document.getElementById('year').value;
            var week = document.getElementById('week').value;

            var formData = new FormData();
            formData.append('action', 'fetch');
            formData.append('year', year);
            formData.append('week', week);

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                console.log('Fetch response status:', response.status);
                return response.json();
            })
            .then(function(result) {
                console.log('Fetch result:', result);
                if (result.success) {
                    // 성공 메시지로 업데이트
                    showLoading('데이터를 가져왔습니다!\n페이지를 새로고침합니다...');
                    // 페이지 새로고침 (임시 파일이 로드됨)
                    setTimeout(function() {
                        window.location.href = window.location.href;
                    }, 800);
                } else {
                    hideLoading();
                    alert('웹에서 데이터를 가져올 수 없습니다: ' + (result.error || '알 수 없는 오류'));
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                hideLoading();
                alert('웹에서 데이터를 가져오는 중 오류가 발생했습니다: ' + error.message);
            });
        }

        // 자동 저장 (선택사항)
        var saveTimeout;
        document.addEventListener('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                // 자동 저장을 원하면 주석 해제
                // saveData();
            }, 2000);
        });

        // 주차 선택 모달
        function showWeekSelector() {
            showLoading('주차 목록을 불러오는 중...');

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
                hideLoading();
                if (result.success) {
                    renderWeekSelector(result.weeks);
                    document.getElementById('weekSelectorModal').classList.remove('hidden');
                    document.getElementById('weekSelectorOverlay').classList.add('active');
                } else {
                    alert('주차 목록을 불러올 수 없습니다.');
                }
            })
            .catch(function(error) {
                hideLoading();
                alert('오류가 발생했습니다: ' + error.message);
            });
        }

        function hideWeekSelector() {
            document.getElementById('weekSelectorModal').classList.add('hidden');
            document.getElementById('weekSelectorOverlay').classList.remove('active');
        }

        function renderWeekSelector(availableWeeks) {
            var currentYear = <?php echo $currentYear; ?>;
            var currentWeek = <?php echo $currentWeek; ?>;
            var selectedYear = <?php echo $year; ?>;
            var selectedWeek = <?php echo $week; ?>;

            // 저장된 주차를 맵으로 변환
            var weekMap = {};
            var weekInfoMap = {};
            var maxYear = currentYear;
            var maxWeek = currentWeek;

            for (var i = 0; i < availableWeeks.length; i++) {
                var w = availableWeeks[i];
                var key = w.year + '_' + w.week;
                weekMap[key] = true;
                weekInfoMap[key] = {
                    noMeeting: w.no_meeting || false,
                    noMeetingTitle: w.no_meeting_title || '',
                    noMeetingReason: w.no_meeting_reason || ''
                };

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

            // 연도별로 그룹화 (현재 주차부터 마지막 데이터 + 1주까지)
            var yearGroups = {};
            var years = [];

            for (var year = currentYear; year <= endYear; year++) {
                years.push(year);
                yearGroups[year] = [];
            }

            for (var y = 0; y < years.length; y++) {
                var year = years[y];
                var startWeek = (year === currentYear) ? currentWeek : 1;
                var lastWeek = (year === endYear) ? endWeek : 52;

                for (var week = startWeek; week <= lastWeek; week++) {
                    var key = year + '_' + week;
                    var hasData = weekMap[key] || false;
                    var isCurrent = (year === selectedYear && week === selectedWeek);
                    var isToday = (year === currentYear && week === currentWeek);
                    var weekInfo = weekInfoMap[key] || {noMeeting: false, noMeetingTitle: '', noMeetingReason: ''};

                    // 배정없음이면 hasData를 false로
                    if (weekInfo.noMeeting) {
                        hasData = false;
                    }

                    yearGroups[year].push({
                        year: year,
                        week: week,
                        hasData: hasData,
                        isCurrent: isCurrent,
                        isToday: isToday,
                        noMeeting: weekInfo.noMeeting,
                        noMeetingTitle: weekInfo.noMeetingTitle,
                        noMeetingReason: weekInfo.noMeetingReason
                    });
                }
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

                    // 사용자 배정 여부 체크
                    var weekKey = weekData.year + '_' + weekData.week;
                    var isMyAssignment = myAssignedWeeks.indexOf(weekKey) !== -1;

                    html += '<div class="' + classes.join(' ') + '" onclick="selectWeek(' + weekData.year + ', ' + weekData.week + ')">';
                    if (weekData.noMeeting) {
                        // 배정없음일 경우 제목 표시 (제목이 없으면 날짜)
                        if (weekData.noMeetingTitle) {
                            html += '<span class="week-date" style="font-size: 12px; color: #ff9800;">' + weekData.noMeetingTitle + '</span>';
                        } else {
                            html += '<span class="week-date" style="color: #ff9800;">' + dateRange + '</span>';
                        }
                    } else {
                        html += '<span class="week-date">' + dateRange + '</span>';
                        // 사용자 배정이 있는 주차에 아이콘 표시 (절대 위치)
                        if (isMyAssignment) {
                            html += '<i class="bi bi-person-check-fill" style="position: absolute; bottom: 5px; right: 5px; font-size: 16px; color: #4CAF50; line-height: 1;"></i>';
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
