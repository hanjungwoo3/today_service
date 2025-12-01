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
            margin: 10mm;
        }

        body {
            font-family: 'Malgun Gothic', '맑은 고딕', sans-serif;
            font-size: 13px;
            line-height: 1.4;
            background: #f5f5f5;
        }

        .card {
            border: none;
            padding: 10mm;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            text-align: center;
            font-size: 17px;
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
            padding-left: 3px;
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
            font-size: 11px;
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
                padding: 5mm;
            }

            .no-print {
                display: none !important;
            }
        }

        /* 컨트롤 바 */
        .controls {
            width: 100%;
            max-width: 210mm;
            margin: 10px auto;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-sizing: border-box;
        }

        .controls button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            white-space: nowrap;
        }

        .controls button:hover {
            background: #45a049;
        }

        .controls .info {
            font-size: 14px;
            color: #333;
            font-weight: bold;
        }

        .controls select {
            width: 100%;
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: white;
            box-sizing: border-box;
        }

        .controls select option {
            padding: 5px;
        }

        .controls-row {
            display: flex;
            align-items: stretch;
            gap: 10px;
            width: 100%;
        }

        .controls-row select {
            flex: 1;
        }

        #printArea {
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
        }

        #printArea .crop-h, #printArea .crop-v {
            position: absolute;
        }

        #printArea .crop-h {
            top: 50%;
            left: 0;
            right: 0;
            height: 0;
            border-top: 1px dashed #999;
        }

        #printArea .crop-v {
            left: 50%;
            top: 0;
            bottom: 0;
            width: 0;
            border-left: 1px dashed #999;
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <div class="info">
            <strong><?php echo $year; ?>년 <?php echo $week; ?>주차</strong> -
            과제 <?php echo count($assignments); ?>개
        </div>
        <div class="controls-row">
            <select id="assignmentSelect" multiple size="<?php echo min(count($assignments), 6); ?>">
                <?php foreach ($assignments as $idx => $item):
                    $name = '';
                    if (is_array($item['assigned']) && !empty($item['assigned'][0])) {
                        $name = $item['assigned'][0];
                    }
                    $displayName = $name ? $name : '(이름 없음)';
                    $taskTitle = isset($item['title']) ? $item['title'] : '';
                ?>
                <option value="<?php echo $idx; ?>" selected><?php echo htmlspecialchars($displayName . ' - ' . $taskTitle); ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="printSelected()">🖨️ 인쇄하기</button>
        </div>
    </div>

    <div id="printArea">
        <div class="crop-h"></div>
        <div class="crop-v"></div>
    <?php
    // 각 과제에 인덱스 추가
    foreach ($assignments as $idx => $item):
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

    <?php if (empty($assignments)): ?>
    <div id="printArea" style="display: flex; align-items: center; justify-content: center;">
        <p style="font-size: 16px; color: #666;">이번 주차에 과제가 없습니다.</p>
    </div>
    <?php endif; ?>

    <script>
    function printSelected() {
        var select = document.getElementById('assignmentSelect');
        var selectedValues = [];
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].selected) {
                selectedValues.push(select.options[i].value);
            }
        }

        // 모든 카드 숨기기
        var cards = document.querySelectorAll('.card');
        cards.forEach(function(card) {
            card.style.display = 'none';
        });

        // 선택된 카드만 표시
        selectedValues.forEach(function(idx) {
            var card = document.querySelector('.card[data-index="' + idx + '"]');
            if (card) {
                card.style.display = 'flex';
            }
        });

        // 인쇄
        window.print();

        // 인쇄 후 모든 카드 다시 표시
        cards.forEach(function(card) {
            card.style.display = 'flex';
        });
    }
    </script>
</body>
</html>
