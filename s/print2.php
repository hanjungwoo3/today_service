<?php
// 로컬 개발 모드 체크
$localConfigFile = dirname(__FILE__) . '/../c/config.php';
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}

require_once 'api.php';

$manager = new MeetingDataManager();

// URL 파라미터로 연도/주차 받기
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('o');
$week = isset($_GET['week']) ? (int)$_GET['week'] : (int)date('W');

// 이전/다음 주차 계산
$prevWeek = $week - 1;
$prevYear = $year;
if ($prevWeek < 1) {
    $prevYear--;
    $prevWeek = (int)date('W', strtotime($prevYear . '-12-28'));
}

$nextWeek = $week + 1;
$nextYear = $year;
$maxWeek = (int)date('W', strtotime($year . '-12-28'));
if ($nextWeek > $maxWeek) {
    $nextYear++;
    $nextWeek = 1;
}

// 데이터 로드
$data = $manager->load($year, $week);

// 과제 항목 추출 (성경 낭독 + 봉사 섹션)
$assignments = array();

if ($data && !empty($data['program'])) {
    foreach ($data['program'] as $item) {
        // 노래 제외
        if (strpos($item['title'], '노래') !== false) {
            continue;
        }

        // 성경 낭독 (treasures 섹션)
        if (strpos($item['title'], '성경 낭독') !== false) {
            $assignments[] = $item;
        }
        // 봉사 섹션 항목
        elseif (isset($item['section']) && $item['section'] === 'ministry') {
            $assignments[] = $item;
        }
    }
}

// 날짜 계산
$meetingDate = isset($data['date']) ? $data['date'] : '';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>과제 용지 - <?php echo $year; ?>년 <?php echo $week; ?>주차</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: 'Malgun Gothic', '맑은 고딕', sans-serif;
            font-size: 14px;
            line-height: 1.4;
            background: #f5f5f5;
        }

        .card {
            border: none;
            padding: 8mm;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 6mm;
            line-height: 1.3;
        }

        .card-title span {
            display: block;
        }

        .field {
            margin-bottom: 4mm;
            display: flex;
            align-items: baseline;
        }

        .field-label {
            font-weight: bold;
            min-width: 55px;
        }

        .field-value {
            flex: 1;
            border-bottom: 1px dotted #000;
            min-height: 18px;
            padding-left: 18px;
        }

        .location-section {
            margin-top: 4mm;
            margin-bottom: 4mm;
        }

        .location-title {
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .location-option {
            display: flex;
            align-items: center;
            margin-left: 10px;
            margin-bottom: 1mm;
        }

        .checkbox {
            width: 13px;
            height: 13px;
            border: 1px solid #000;
            margin-right: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }

        .checkbox.checked {
            font-weight: bold;
        }

        .note {
            margin-top: 4mm;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }

        .form-number {
            font-size: 10px;
            color: #666;
            margin-top: 3mm;
        }

        /* 인쇄 설정 */
        @media print {
            body {
                background: white;
            }

            #printArea {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }
        }

        /* 컨트롤 바 */
        .controls {
            width: 210mm;
            min-width: 210mm;
            margin: 10px auto;
            padding: 15px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        .controls-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 20px;
        }

        .controls .info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 18px;
            color: #333;
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

        .right-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
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
            min-width: 280px;
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
            max-height: 250px;
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
            white-space: nowrap;
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

        .print-page {
            width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 5mm;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 0;
            position: relative;
            min-height: 297mm;
            box-sizing: border-box;
            page-break-after: always;
        }

        .print-page:last-child {
            page-break-after: auto;
        }

        .print-page .crop-h, .print-page .crop-v {
            position: absolute;
        }

        .print-page .crop-h {
            top: 50%;
            left: 0;
            right: 0;
            height: 0;
            border-top: 1px dashed #999;
        }

        .print-page .crop-v {
            left: 50%;
            top: 0;
            bottom: 0;
            width: 0;
            border-left: 1px dashed #999;
        }

        .card.excluded {
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <div class="controls-row">
            <div class="info">
                <a href="?year=<?php echo $prevYear; ?>&week=<?php echo $prevWeek; ?>" class="nav-btn">&lt;</a>
                <strong><?php echo $year; ?>년 <?php echo $week; ?>주차</strong>
                <a href="?year=<?php echo $nextYear; ?>&week=<?php echo $nextWeek; ?>" class="nav-btn">&gt;</a>
            </div>
            <div class="right-controls">
                <div class="multi-select-container">
                    <div class="select-box" onclick="toggleCheckboxes()">
                        <span id="select-text">전체 선택됨 (<?php echo count($assignments); ?>)</span>
                    </div>
                    <div class="checkboxes" id="checkboxes">
                        <?php foreach ($assignments as $idx => $item):
                            $name = '';
                            if (is_array($item['assigned']) && !empty($item['assigned'][0])) {
                                $name = $item['assigned'][0];
                            }
                            $displayName = $name ? $name : '(이름 없음)';
                            $taskTitle = isset($item['title']) ? $item['title'] : '';
                        ?>
                        <label>
                            <input type="checkbox" checked onchange="toggleCard(<?php echo $idx; ?>)" data-index="<?php echo $idx; ?>" />
                            <?php echo htmlspecialchars($displayName . ' - ' . $taskTitle); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="print-btn" onclick="printSelected()">🖨️ 인쇄하기</button>
            </div>
        </div>
    </div>

    <div id="printArea">
    <?php
    // 4개씩 페이지로 나누기
    $chunks = array_chunk($assignments, 4, true);
    foreach ($chunks as $pageIndex => $pageAssignments):
    ?>
        <div class="print-page" data-page="<?php echo $pageIndex; ?>">
            <div class="crop-h"></div>
            <div class="crop-v"></div>
        <?php
        foreach ($pageAssignments as $idx => $item):
            $name = '';
            $assistant = '';

            if (is_array($item['assigned'])) {
                $name = isset($item['assigned'][0]) ? $item['assigned'][0] : '';
                $assistant = isset($item['assigned'][1]) ? $item['assigned'][1] : '';
            }

            $taskNumber = $item['title'];
        ?>
            <div class="card" data-index="<?php echo $idx; ?>">
                <div class="card-title">
                    <span>그리스도인 생활과 봉사</span>
                    <span>집회 과제</span>
                </div>

                <div class="field">
                    <span class="field-label">이름:</span>
                    <span class="field-value"><?php echo htmlspecialchars($name); ?></span>
                </div>

                <div class="field">
                    <span class="field-label">보조자:</span>
                    <span class="field-value"><?php echo htmlspecialchars($assistant); ?></span>
                </div>

                <div class="field">
                    <span class="field-label">일자:</span>
                    <span class="field-value"><?php echo htmlspecialchars($meetingDate); ?></span>
                </div>

                <div class="field">
                    <span class="field-label">과제 번호:</span>
                    <span class="field-value"><?php echo htmlspecialchars($taskNumber); ?></span>
                </div>

                <div class="location-section">
                    <div class="location-title">과제를 수행할 장소:</div>
                    <div class="location-option">
                        <span class="checkbox checked">✓</span> 회관
                    </div>
                    <div class="location-option">
                        <span class="checkbox"></span> 보조 교실 1
                    </div>
                    <div class="location-option">
                        <span class="checkbox"></span> 보조 교실 2
                    </div>
                </div>

                <div class="note">
                    <strong>학생이 유의할 점:</strong> 「생활과 봉사 집회 교재」에서 과제를 위한 근거 자료와 학습 요점을 찾아볼 수 있습니다. 과제에 대한 지침을 「그리스도인 생활과 봉사 집회 지침」(S-38)에서 살펴보시기 바랍니다.
                </div>

                <div class="form-number">S-89-KO 11/23</div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    </div>

    <?php if (empty($assignments)): ?>
    <div id="printArea" style="display: flex; align-items: center; justify-content: center;">
        <p style="font-size: 16px; color: #666;">이번 주차에 과제가 없습니다.</p>
    </div>
    <?php endif; ?>

    <script>
    // 드롭다운 외부 클릭시 닫기
    document.addEventListener('click', function(e) {
        var container = document.querySelector('.multi-select-container');
        var checkboxes = document.getElementById('checkboxes');
        if (container && !container.contains(e.target)) {
            checkboxes.classList.remove('show');
        }
    });

    function toggleCheckboxes() {
        var checkboxes = document.getElementById('checkboxes');
        checkboxes.classList.toggle('show');
    }

    function toggleCard(index) {
        var card = document.querySelector('.card[data-index="' + index + '"]');
        if (card) {
            card.classList.toggle('excluded');
        }
        updateSelectText();
    }

    function updateSelectText() {
        var checkboxes = document.querySelectorAll('#checkboxes input[type="checkbox"]');
        var checkedCount = 0;
        var total = checkboxes.length;

        for (var i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                checkedCount++;
            }
        }

        var selectText = document.getElementById('select-text');
        if (checkedCount === total) {
            selectText.textContent = '전체 선택됨 (' + total + ')';
        } else if (checkedCount === 0) {
            selectText.textContent = '선택 없음';
        } else {
            selectText.textContent = checkedCount + '개 선택됨';
        }
    }

    function printSelected() {
        var checkboxes = document.querySelectorAll('#checkboxes input[type="checkbox"]');
        var cards = document.querySelectorAll('.card');

        // 선택되지 않은 카드 숨기기
        for (var i = 0; i < checkboxes.length; i++) {
            var index = checkboxes[i].getAttribute('data-index');
            var card = document.querySelector('.card[data-index="' + index + '"]');
            if (card) {
                if (checkboxes[i].checked) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            }
        }

        // 인쇄
        window.print();

        // 인쇄 후 모든 카드 다시 표시 (excluded 클래스 유지)
        for (var j = 0; j < cards.length; j++) {
            cards[j].style.display = 'flex';
        }
    }
    </script>
</body>
</html>
