<?php
date_default_timezone_set('Asia/Seoul');

$is_elder = false;
if (file_exists(dirname(__FILE__) . '/../config.php')) {
    @require_once dirname(__FILE__) . '/../config.php';
    if (function_exists('mb_id') && function_exists('get_member_position')) {
        $is_elder = (get_member_position(mb_id()) >= '2');
    }
}

if (!$is_elder) {
    header('Location: duty_view.php');
    exit;
}

require_once dirname(__FILE__) . '/duty_api.php';

$currentYear = (int)date('Y');
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

$manager = new DutyDataManager();
$data = $manager->load($year);
$months = $data['months'];
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>청소/마이크/안내인/연사음료 계획표 - 인쇄</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
            font-family: 'Malgun Gothic', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f2f5;
            color: #333;
            font-size: 14px;
            padding: 20px;
            min-width: 900px;
        }
        .controls {
            width: 900px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .doc-title-preview {
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }
        .controls-btns { display: flex; gap: 8px; }
        .print-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }
        .print-btn:hover { background: #45a049; }

        .print-container {
            width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 2px solid #333;
        }
        .doc-header .doc-site { font-size: 15px; font-weight: 700; }
        .doc-header .doc-title { font-size: 15px; font-weight: 700; color: #555; }

        .month-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .month-card {
            border: 1px solid #ccc;
            border-radius: 6px;
            overflow: hidden;
        }
        .month-header {
            padding: 5px 8px;
            font-weight: 700;
            font-size: 14px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #ddd;
            background: #fafafa;
        }
        .month-header .header-info {
            display: flex;
            gap: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-left: auto;
        }
        .month-header .header-info .cleaning-group {
            color: #2e7d32;
            font-weight: 700;
        }
        .month-body { padding: 0; }

        .half-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .half-table th {
            background: #eef1f6;
            font-size: 11px;
            font-weight: 600;
            color: #888;
            padding: 3px 5px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .half-table td {
            padding: 3px 5px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }
        .half-table td.row-label {
            font-weight: 600;
            color: #555;
            font-size: 12px;
            text-align: right;
            white-space: nowrap;
            background: #eef1f6;
        }

        @media print {
            body { background: white; padding: 0; margin: 0; min-width: 0; }
            .controls { display: none !important; }
            .print-container {
                box-shadow: none;
                width: 100%;
                padding: 5mm;
                margin: 0;
                border-radius: 0;
            }
            .month-card { break-inside: avoid; }
            @page {
                size: A4 portrait;
                margin: 5mm;
            }
        }
    </style>
</head>
<body>
    <div class="controls">
        <span class="doc-title-preview"><?php echo $year; ?>년 계획표 인쇄</span>
        <div class="controls-btns">
            <button onclick="window.print()" class="print-btn">인쇄하기</button>
        </div>
    </div>

    <div class="print-container">
        <div class="doc-header">
            <span class="doc-site"><?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : ''; ?></span>
            <span class="doc-title"><?php echo $year; ?>년 청소집단/마이크/안내인/연사음료 계획표</span>
        </div>

        <div class="month-grid">
        <?php for ($m = 1; $m <= 12; $m++):
            $month = isset($months[(string)$m]) ? $months[(string)$m] : array();
            $fh = isset($month['first_half']) ? $month['first_half'] : array();
            $sh = isset($month['second_half']) ? $month['second_half'] : array();
            $dm = trim($month['drink_main'] ?? '');
            $da = trim($month['drink_assist'] ?? '');
            $drinkDisplay = '';
            if (!empty($dm)) $drinkDisplay = htmlspecialchars($dm);
            if (!empty($da)) $drinkDisplay .= ' (' . htmlspecialchars($da) . ')';
        ?>
        <div class="month-card">
            <div class="month-header">
                <span><?php echo $m; ?>월</span>
                <span class="header-info">
                    <?php $cg = trim($month['cleaning_group'] ?? ''); if (!empty($cg)): ?>
                        <span>청소집단:<span class="cleaning-group"><?php echo htmlspecialchars($cg); ?></span></span>
                    <?php endif; ?>
                    <?php if (!empty(trim($drinkDisplay))): ?>
                        <span>음료:<?php echo $drinkDisplay; ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="month-body">
                <table class="half-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>상반기 (1-15일)</th>
                            <th>하반기 (16-말일)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $fm1 = trim($fh['mic1'] ?? ''); $fm2 = trim($fh['mic2'] ?? ''); $fma = trim($fh['mic_assist'] ?? '');
                            $fMic = implode(', ', array_filter([$fm1, $fm2]));
                            if (!empty($fma)) $fMic .= ' (' . htmlspecialchars($fma) . ')'; else $fMic = htmlspecialchars($fMic);
                            $sm1 = trim($sh['mic1'] ?? ''); $sm2 = trim($sh['mic2'] ?? ''); $sma = trim($sh['mic_assist'] ?? '');
                            $sMic = implode(', ', array_filter([$sm1, $sm2]));
                            if (!empty($sma)) $sMic .= ' (' . htmlspecialchars($sma) . ')'; else $sMic = htmlspecialchars($sMic);

                            $fHall = implode(', ', array_filter([trim($fh['att_hall1'] ?? ''), trim($fh['att_hall2'] ?? '')]));
                            $sHall = implode(', ', array_filter([trim($sh['att_hall1'] ?? ''), trim($sh['att_hall2'] ?? '')]));
                        ?>
                        <tr>
                            <td class="row-label">마이크</td>
                            <td><?php echo $fMic; ?></td>
                            <td><?php echo $sMic; ?></td>
                        </tr>
                        <tr>
                            <td class="row-label">청중석 안내</td>
                            <td><?php echo htmlspecialchars($fHall); ?></td>
                            <td><?php echo htmlspecialchars($sHall); ?></td>
                        </tr>
                        <tr>
                            <td class="row-label">출입구 안내</td>
                            <td><?php echo htmlspecialchars($fh['att_entrance'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($sh['att_entrance'] ?? ''); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endfor; ?>
        </div>
    </div>
</body>
</html>
