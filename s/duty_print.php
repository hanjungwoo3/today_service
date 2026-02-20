<?php
date_default_timezone_set('Asia/Seoul');

$is_admin = false;
if (file_exists(dirname(__FILE__) . '/../config.php')) {
    @require_once dirname(__FILE__) . '/../config.php';
    if (function_exists('mb_id') && function_exists('is_admin')) {
        $is_admin = is_admin(mb_id());
    }
}

if (!$is_admin) {
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
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>청소/마이크/안내인/연사음료 계획표 - 인쇄</title>
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

        .controls {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            width: 297mm;
            min-width: 297mm;
            margin-left: auto;
            margin-right: auto;
        }
        .controls-row {
            display: flex;
            justify-content: flex-end;
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
        }
        .back-btn:hover { background: #f5f5f5; }

        .page-container {
            width: 297mm;
            min-width: 297mm;
            margin: 0 auto;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .doc-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 3px solid #333;
        }
        .doc-title {
            font-size: 22px;
            font-weight: bold;
        }
        .doc-year {
            font-size: 14px;
            color: #666;
            margin-top: 4px;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .print-table th {
            padding: 6px 3px;
            font-weight: 600;
            text-align: center;
            border: 1px solid #999;
            white-space: nowrap;
        }
        .print-table .h1 th { background: #333; color: white; font-size: 11px; }
        .print-table .h2 th { background: #666; color: white; font-size: 10px; }
        .print-table td {
            padding: 5px 3px;
            border: 1px solid #ccc;
            text-align: center;
            vertical-align: middle;
            font-size: 11px;
        }
        .print-table .month-cell {
            font-weight: 700;
            font-size: 12px;
        }
        .print-table .group-cell {
            font-weight: 700;
            color: #2e7d32;
            font-size: 12px;
        }
        .print-table .period-cell {
            font-size: 10px;
            color: #555;
        }

        @media print {
            body { background: white; padding: 0; margin: 0; }
            .controls { display: none !important; }
            .page-container {
                box-shadow: none;
                padding: 10mm;
                margin: 0;
                width: 100%;
            }
            @page {
                size: A4 landscape;
                margin: 5mm;
            }
        }
    </style>
</head>
<body>
    <div class="controls">
        <div class="controls-row">
            <button onclick="window.print()" class="print-btn">
                <i class="bi bi-printer"></i> 인쇄하기
            </button>
            <a href="duty_admin.php?year=<?php echo $year; ?>" class="back-btn">돌아가기</a>
        </div>
    </div>

    <div class="page-container">
        <div class="doc-header">
            <div class="doc-title">청소 집단 / 마이크 전달 / 안내인 / 연사음료 계획표</div>
            <div class="doc-year"><?php echo $year; ?>년<?php echo defined('SITE_NAME') ? ' — ' . htmlspecialchars(SITE_NAME) : ''; ?></div>
        </div>

        <table class="print-table">
            <thead>
                <tr class="h1">
                    <th rowspan="2" style="width:40px;"></th>
                    <th rowspan="2" style="width:40px;">회관<br>청소<br>집단</th>
                    <th colspan="4">청중 마이크</th>
                    <th colspan="3">안내인</th>
                    <th colspan="2">연사 음료</th>
                </tr>
                <tr class="h2">
                    <th style="width:70px;">날짜</th>
                    <th>마이크1</th>
                    <th>마이크2</th>
                    <th>보조</th>
                    <th>청중석</th>
                    <th>청중석</th>
                    <th>출입구</th>
                    <th>담당자1</th>
                    <th>보조</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($m = 1; $m <= 12; $m++):
                    $month = isset($months[(string)$m]) ? $months[(string)$m] : array();
                    $fh = isset($month['first_half']) ? $month['first_half'] : array();
                    $sh = isset($month['second_half']) ? $month['second_half'] : array();
                ?>
                    <tr>
                        <td class="month-cell" rowspan="2"><?php echo $m; ?>월</td>
                        <td class="group-cell" rowspan="2"><?php echo htmlspecialchars($month['cleaning_group'] ?? ''); ?></td>
                        <td class="period-cell">1일 - 15일</td>
                        <td><?php echo htmlspecialchars($fh['mic1'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($fh['mic2'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($fh['mic_assist'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($fh['att_hall1'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($fh['att_hall2'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($fh['att_entrance'] ?? ''); ?></td>
                        <td rowspan="2"><?php echo htmlspecialchars($month['drink_main'] ?? ''); ?></td>
                        <td rowspan="2"><?php echo htmlspecialchars($month['drink_assist'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <td class="period-cell">16일 - 말일</td>
                        <td><?php echo htmlspecialchars($sh['mic1'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($sh['mic2'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($sh['mic_assist'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($sh['att_hall1'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($sh['att_hall2'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($sh['att_entrance'] ?? ''); ?></td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
