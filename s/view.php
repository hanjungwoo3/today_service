<?php
// 서비스 워커 캐시 방지
header('Cache-Control: no-cache, no-store, must-revalidate');

// 로그인한 사용자 정보 가져오기
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
function categorizePrograms($programs)
{
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

// (배정 특권 표시는 홈 화면으로 이동됨)

// 배정명 필터링을 위한 함수
function filterAssignedNames($v)
{
    $trimmed = trim($v);
    return !empty($trimmed);
}

$embed = isset($_GET['embed']) && $_GET['embed'] == '1';
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>생활과 봉사 집회 - <?php echo $data['date']; ?></title>
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
            overflow-x: auto;
        }

        .container {
            min-width: 340px;
            max-width: 1024px;
            margin: 0 auto;
            background: white;
            border-radius: 6px;
            padding: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
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

        .header .date {
            color: #667eea;
            font-size: 15px;
            font-weight: 600;
        }

        .navigation {
            margin-bottom: 6px;
            position: relative;
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
            font-size: 13px;
        }

        .nav-button:hover {
            background: #5568d3;
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

        /* 주차 선택 모달 */
        .week-selector-modal {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 8px;
            z-index: 1000;
            width: 100%;
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
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .week-selector-header {
            position: sticky;
            top: 0;
            background: white;
            padding: 8px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            z-index: 10;
        }

        .week-selector-title {
            font-weight: 700;
            font-size: 15px;
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

        .url-link {
            text-align: center;
            margin-bottom: 6px;
        }

        .url-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            word-break: break-all;
        }

        .url-link a:hover {
            text-decoration: underline;
        }

        .bible-reading {
            text-align: center;
            font-size: 17px;
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
            background: white;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
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

        /* WOL-style icons */
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
            padding: 5px 6px;
            margin-bottom: 4px;
            background: #f9f9f9;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }



        .program-info {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .program-title {
            font-weight: 600;
            font-size: 15px;
            color: #333;
            flex: 1;
            word-break: break-word;
            overflow-wrap: break-word;
        }

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
            font-size: 14px;
            flex-shrink: 0;
            white-space: nowrap;
            margin-right: 8px;
        }

        .program-assigned {
            background: white;
            color: #333;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 16px;
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
            font-size: 14px;
            color: #555;
        }

        .assignment-name {
            background: white;
            color: #333;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
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
            font-size: 13px;
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
        <?php if ($embed): ?>
        body { padding-top: 0; }
        <?php endif; ?>
    </style>
</head>

<body>
    <div class="container">
        <div class="navigation">
            <div class="nav-row" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;">
                <?php $eq = $embed ? '&embed=1' : ''; ?>
                <?php if ($prevWeekData !== null): ?>
                    <a href="?year=<?php echo $prevWeekData['year']; ?>&week=<?php echo $prevWeekData['week'] . $eq; ?>" class="nav-button" style="background: #667eea;">◀ 이전</a>
                <?php else: ?>
                    <span class="nav-button" style="background: #ccc; color: #888; cursor: not-allowed; pointer-events: none;">◀ 이전</span>
                <?php endif; ?>
                <a href="?year=<?php echo $currentYear; ?>&week=<?php echo $currentWeek . $eq; ?>" class="nav-button" style="background: #4CAF50;">📅 이번주</a>
                <button onclick="showWeekSelector()" class="nav-button" style="background: #FF9800;">📆 선택</button>
                <?php if ($nextWeekData !== null): ?>
                    <a href="?year=<?php echo $nextWeekData['year']; ?>&week=<?php echo $nextWeekData['week'] . $eq; ?>" class="nav-button" style="background: #667eea;">다음 ▶</a>
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
            <div class="date"><?php echo htmlspecialchars($data['date']); ?></div>
        </div>

        <?php if (!empty($data['no_meeting']) && $data['no_meeting']): ?>
            <!-- 배정없음 표시 -->
            <div style="text-align: center; padding: 15px 5px; background: #f5f5f5; border-radius: 12px; margin: 20px 0;">
                <div style="font-size: 22px; font-weight: 700; color: #666; margin-bottom: 8px;">
                    <?php echo !empty($data['no_meeting_title']) ? htmlspecialchars($data['no_meeting_title']) : '배정없음'; ?>
                </div>
                <?php if (!empty($data['no_meeting_reason'])): ?>
                    <div style="background: white; padding: 12px; border-radius: 8px; width: calc(100% - 10px); margin: 0 auto;">
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
                        <span class="section-icon dc-icon--gem"></span>
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

                            // 노래 항목인지 확인
                            $isSong = strpos($item['title'], '노래') !== false;
                            ?>
                            <?php if (!$isSong): ?>
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
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($categorized['ministry'])): ?>
                <div class="section section-ministry">
                    <div class="section-header ministry">
                        <span class="section-icon dc-icon--wheat"></span>
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

                            // 노래 항목인지 확인
                            $isSong = strpos($item['title'], '노래') !== false;
                            ?>
                            <?php if (!$isSong): ?>
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
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($categorized['living'])): ?>
                <div class="section section-living">
                    <div class="section-header living">
                        <span class="section-icon dc-icon--sheep"></span>
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

                            // 노래 항목인지 확인
                            $isSong = strpos($item['title'], '노래') !== false;
                            ?>
                            <?php if (!$isSong): ?>
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
        <?php endif; ?>

        <?php if ($is_elder): ?>
        <div style="margin-top: 16px; border-top: 1px solid #e0e0e0; padding-top: 12px;">
            <div style="background: #f8f9ff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px;">
                <div style="font-weight: 600; font-size: 14px; color: #333; margin-bottom: 6px;">관리자모드</div>
                <p style="font-size: 12px; color: #666; margin-bottom: 8px; line-height: 1.4;">프로그램 배정을 수정하고 웹에서 데이터를 가져올 수 있습니다. 변경 후 저장 버튼을 눌러야 적용됩니다.</p>
                <a href="index.php?year=<?php echo $year; ?>&week=<?php echo $week . $eq; ?>"
                   style="width: 100%; display: block; text-align: center; text-decoration: none; padding: 8px 16px; border-radius: 4px; font-size: 14px; font-weight: 600; background: #e0e0e0; color: #333; border: none; cursor: pointer; box-sizing: border-box;">관리자모드로 보기</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // 평일집회 요일 (1=월요일 ~ 7=일요일)
        var meetingWeekday = <?php echo $manager->getMeetingWeekday(); ?>;


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
                        document.getElementById('weekSelectorOverlay').classList.add('active');
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
            document.getElementById('weekSelectorOverlay').classList.remove('active');
        }

        function renderWeekSelector(availableWeeks) {
            var currentYear = <?php echo $currentYear; ?>;
            var currentWeek = <?php echo $currentWeek; ?>;
            var selectedYear = <?php echo $year; ?>;
            var selectedWeek = <?php echo $week; ?>;

            // JSON 파일이 있는 주차만 필터링 (현재 주차 이후)
            var yearGroups = {};
            var years = [];

            for (var i = 0; i < availableWeeks.length; i++) {
                var w = availableWeeks[i];
                var year = w.year;
                var week = w.week;

                // 현재 주차 이후만 표시
                if (year < currentYear || (year === currentYear && week < currentWeek)) {
                    continue;
                }

                if (!yearGroups[year]) {
                    years.push(year);
                    yearGroups[year] = [];
                }

                var isCurrent = (year === selectedYear && week === selectedWeek);
                var isToday = (year === currentYear && week === currentWeek);
                var noMeeting = w.no_meeting || false;
                var date = w.date || '';

                yearGroups[year].push({
                    year: year,
                    week: week,
                    date: date,
                    hasData: !noMeeting,
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

                    // 입력한 날짜가 있으면 사용, 없으면 주간 날짜 계산
                    var displayDate = weekData.date ? weekData.date : getWeekDateRange(weekData.year, weekData.week);

                    html += '<div class="' + classes.join(' ') + '" onclick="selectWeek(' + weekData.year + ', ' + weekData.week + ')">';
                    if (weekData.noMeeting) {
                        // 배정없음일 경우 제목 표시 (제목이 없으면 날짜)
                        if (weekData.noMeetingTitle) {
                            html += '<span class="week-date" style="font-size: 12px; color: #ff9800;">' + weekData.noMeetingTitle + '</span>';
                        } else {
                            html += '<span class="week-date" style="color: #ff9800;">' + displayDate + '</span>';
                        }
                    } else {
                        html += '<span class="week-date">' + displayDate + '</span>';
                    }
                    html += '<span class="week-number">' + weekData.week + '주</span>';
                    html += '</div>';
                }

                html += '</div>';
            }

            document.getElementById('weekSelectorBody').innerHTML = html;
        }

        function selectWeek(year, week) {
            var embedParam = <?php echo $embed ? "'&embed=1'" : "''"; ?>;
            window.location.href = '?year=' + year + '&week=' + week + embedParam;
        }

        // 주차 번호를 날짜로 변환 (평일집회 요일 날짜)
        function getWeekDateRange(year, week) {
            // ISO 8601 주차 계산
            var jan4 = new Date(year, 0, 4);
            var jan4Day = jan4.getDay() || 7;
            var weekStart = new Date(jan4);
            weekStart.setDate(jan4.getDate() - jan4Day + 1 + (week - 1) * 7);

            // 집회 요일로 이동 (월요일=1 기준)
            var currentDay = weekStart.getDay() || 7;
            var daysToAdd = meetingWeekday - currentDay;
            if (daysToAdd < 0) {
                daysToAdd += 7;
            }
            var meetingDate = new Date(weekStart);
            meetingDate.setDate(weekStart.getDate() + daysToAdd);

            var meetingMonth = meetingDate.getMonth() + 1;
            var meetingDay = meetingDate.getDate();

            return meetingMonth + '월 ' + meetingDay + '일';
        }
    </script>
</body>

</html>