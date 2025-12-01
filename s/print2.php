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

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 5mm;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 0;
            position: relative;
        }

        .card {
            border: none;
            padding: 10mm;
            display: flex;
            flex-direction: column;
        }

        /* 페이지 중앙 자르기 점선 */
        .crop-h, .crop-v {
            position: absolute;
        }

        .crop-h {
            top: 50%;
            left: 0;
            right: 0;
            height: 0;
            border-top: 1px dashed #999;
        }

        .crop-v {
            left: 50%;
            top: 0;
            bottom: 0;
            width: 0;
            border-left: 1px dashed #999;
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

            .page {
                margin: 0;
                padding: 5mm;
                page-break-after: always;
            }

            .no-print {
                display: none !important;
            }
        }

        /* 컨트롤 바 */
        .controls {
            max-width: 210mm;
            margin: 10px auto;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .controls button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .controls button:hover {
            background: #45a049;
        }

        .controls .info {
            font-size: 14px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <div class="info">
            <strong><?php echo $year; ?>년 <?php echo $week; ?>주차</strong> -
            과제 <?php echo count($assignments); ?>개
        </div>
        <button onclick="window.print()">🖨️ 인쇄하기</button>
    </div>

    <?php
    // 4개씩 페이지로 나누기
    $chunks = array_chunk($assignments, 4);

    foreach ($chunks as $pageAssignments):
    ?>
    <div class="page">
        <div class="crop-h"></div>
        <div class="crop-v"></div>
        <?php
        // 4개 카드 출력 (빈 칸도 포함)
        for ($i = 0; $i < 4; $i++):
            $item = isset($pageAssignments[$i]) ? $pageAssignments[$i] : null;
            $name = '';
            $assistant = '';

            if ($item && is_array($item['assigned'])) {
                $name = isset($item['assigned'][0]) ? $item['assigned'][0] : '';
                $assistant = isset($item['assigned'][1]) ? $item['assigned'][1] : '';
            }

            $taskNumber = $item ? $item['title'] : '';
        ?>
        <div class="card">
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
        <?php endfor; ?>
    </div>
    <?php endforeach; ?>

    <?php if (empty($assignments)): ?>
    <div class="page">
        <div style="grid-column: 1 / -1; grid-row: 1 / -1; display: flex; align-items: center; justify-content: center;">
            <p style="font-size: 16px; color: #666;">이번 주차에 과제가 없습니다.</p>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
