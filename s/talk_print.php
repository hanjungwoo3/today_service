<?php
date_default_timezone_set('Asia/Seoul');

// 관리자 권한 체크
$is_admin = false;
if (file_exists(dirname(__FILE__) . '/../config.php')) {
    @require_once dirname(__FILE__) . '/../config.php';
    if (function_exists('mb_id') && function_exists('is_admin')) {
        $is_admin = is_admin(mb_id());
    }
}

if (!$is_admin) {
    header('Location: talk_view.php');
    exit;
}

require_once dirname(__FILE__) . '/talk_api.php';

$manager = new TalkDataManager();
$data = $manager->load();
$allTalks = $data['talks'];

// 관리자 설정 시작일 또는 지난주 일요일
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

// 시작일 이후 데이터만 필터
$talks = array();
foreach ($allTalks as $talk) {
    if ($talk['date'] >= $startDate) {
        $talks[] = $talk;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>공개 강연 계획표 - 인쇄</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            font-family: 'Malgun Gothic', 'Dotum', sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: #000;
            font-size: 14px;
        }

        /* 컨트롤 */
        .controls {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            width: 210mm;
            min-width: 210mm;
            margin-left: auto;
            margin-right: auto;
        }
        .controls-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* 멀티 셀렉트 */
        .multi-select-container { position: relative; width: 200px; }
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
        .select-box:hover { background: #e9ecef; }
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
            left: 0; right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            z-index: 100;
            max-height: 250px;
            overflow-y: auto;
            margin-top: 4px;
        }
        .checkboxes.show { display: block; }
        .checkboxes label {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }
        .checkboxes label:last-child { border-bottom: none; }
        .checkboxes label:hover { background: #f5f5f5; }
        .checkboxes input { margin-right: 10px; }

        .right-controls {
            display: flex;
            align-items: center;
            gap: 10px;
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
            gap: 4px;
            white-space: nowrap;
        }
        .print-btn:hover { background: #45a049; }
        .back-btn {
            padding: 8px 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: white;
            color: #555;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
        }
        .back-btn:hover { background: #f5f5f5; }

        /* 문서 */
        .page-container {
            width: 210mm;
            min-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 3px solid #333;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }
        .congregation-name { font-size: 16px; font-weight: bold; }
        .doc-title { font-size: 24px; font-weight: bold; }

        /* 테이블 */
        .talk-print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .talk-print-table th {
            background: #4CAF50;
            color: white;
            padding: 8px 6px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            border: 1px solid #388E3C;
        }
        .talk-print-table td {
            padding: 6px;
            border: 1px solid #ddd;
            vertical-align: middle;
            font-size: 12px;
        }
        .talk-print-table tr.excluded {
            opacity: 0.3;
            filter: grayscale(100%);
        }
        .talk-print-table tr:hover { background: #f9f9f9; }

        .pt-col-date { width: 65px; text-align: center; white-space: nowrap; }
        .pt-col-speaker { width: 60px; text-align: center; }
        .pt-col-congregation { width: 80px; text-align: center; font-size: 11px; }
        .pt-col-topic { }
        .pt-col-chairman { width: 55px; text-align: center; }
        .pt-col-reader { width: 55px; text-align: center; }
        .pt-col-prayer { width: 55px; text-align: center; }

        .topic-circuit-bg { background: #e8f5e9; }
        .topic-special-bg { background: #fff3e0; }
        .topic-type-label {
            font-weight: 700;
            font-size: 11px;
            display: block;
        }
        .topic-type-label.circuit { color: #2e7d32; }
        .topic-type-label.special { color: #e65100; }

        @media (max-width: 600px) {
            body { overflow-x: auto; }
        }

        @media print {
            body { background: white; padding: 0; margin: 0; }
            .controls { display: none !important; }
            .talk-print-table tr.excluded { display: none !important; }
            .page-container { box-shadow: none; padding: 0; margin: 0; width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="controls">
        <div class="controls-row">
            <div class="multi-select-container">
                <div class="select-box" onclick="toggleCheckboxes()">
                    <span id="select-text">행 선택</span>
                </div>
                <div class="checkboxes" id="checkboxes">
                    <?php foreach ($talks as $index => $talk):
                        $d = new DateTime($talk['date']);
                    ?>
                        <label>
                            <input type="checkbox" checked onchange="toggleRow(<?php echo $index; ?>)" />
                            <?php echo $d->format('y/m/d'); ?>
                            <?php echo htmlspecialchars($talk['speaker']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="right-controls">
                <button onclick="window.print()" class="print-btn">
                    <i class="bi bi-printer"></i> 인쇄하기
                </button>
                <a href="talk_admin.php" class="back-btn">돌아가기</a>
            </div>
        </div>
    </div>

    <div class="page-container">
        <div class="doc-header">
            <div class="congregation-name"><?=defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : ''?></div>
            <div class="doc-title">공개 강연 계획표</div>
        </div>

        <table class="talk-print-table">
            <thead>
                <tr>
                    <th class="pt-col-date">일자</th>
                    <th class="pt-col-speaker">연사</th>
                    <th class="pt-col-congregation">회중</th>
                    <th class="pt-col-topic">연제</th>
                    <th class="pt-col-chairman">사회</th>
                    <th class="pt-col-reader">낭독</th>
                    <th class="pt-col-prayer">기도</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($talks as $index => $talk):
                    $d = new DateTime($talk['date']);
                    $dateDisplay = $d->format('y/m/d');
                    $topicBg = '';
                    if ($talk['topic_type'] === 'circuit_visit') $topicBg = 'topic-circuit-bg';
                    elseif ($talk['topic_type'] === 'special_talk') $topicBg = 'topic-special-bg';
                ?>
                <tr id="print-row-<?php echo $index; ?>">
                    <td class="pt-col-date"><strong><?php echo $dateDisplay; ?></strong></td>
                    <td class="pt-col-speaker"><?php echo htmlspecialchars($talk['speaker']); ?></td>
                    <td class="pt-col-congregation"><?php echo htmlspecialchars($talk['congregation']); ?></td>
                    <td class="pt-col-topic <?php echo $topicBg; ?>">
                        <?php if ($talk['topic_type'] === 'circuit_visit'): ?>
                            <span class="topic-type-label circuit">[순회 방문 - 공개 강연]</span>
                        <?php elseif ($talk['topic_type'] === 'special_talk'): ?>
                            <span class="topic-type-label special">[특별 강연]</span>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($talk['topic']); ?>
                    </td>
                    <td class="pt-col-chairman"><?php echo htmlspecialchars($talk['chairman']); ?></td>
                    <td class="pt-col-reader"><?php echo htmlspecialchars($talk['reader']); ?></td>
                    <td class="pt-col-prayer"><?php echo htmlspecialchars($talk['prayer']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        var expanded = false;
        var totalRows = <?php echo count($talks); ?>;

        function toggleCheckboxes() {
            var cb = document.getElementById('checkboxes');
            expanded = !expanded;
            cb.classList.toggle('show', expanded);
        }

        document.addEventListener('click', function(e) {
            if (expanded && !document.querySelector('.multi-select-container').contains(e.target)) {
                document.getElementById('checkboxes').classList.remove('show');
                expanded = false;
            }
        });

        function toggleRow(index) {
            var row = document.getElementById('print-row-' + index);
            if (row) row.classList.toggle('excluded');
            updateSelectText();
        }

        function updateSelectText() {
            var selected = 0;
            for (var i = 0; i < totalRows; i++) {
                var row = document.getElementById('print-row-' + i);
                if (!row.classList.contains('excluded')) selected++;
            }
            var text = document.getElementById('select-text');
            if (selected === totalRows) text.textContent = '모두 선택됨';
            else if (selected === 0) text.textContent = '선택 없음';
            else text.textContent = selected + '/' + totalRows + '개 선택됨';
        }

        document.addEventListener('DOMContentLoaded', updateSelectText);
    </script>
</body>
</html>
