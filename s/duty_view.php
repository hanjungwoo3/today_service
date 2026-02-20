<?php
header('Cache-Control: no-cache, no-store, must-revalidate');
date_default_timezone_set('Asia/Seoul');

$loggedInUserName = '';
$is_admin = false;
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

require_once dirname(__FILE__) . '/duty_api.php';

$currentYear = (int)date('Y');
$year = $currentYear;
$currentMonth = (int)date('n');
$currentDay = (int)date('j');
$embed = isset($_GET['embed']) ? (int)$_GET['embed'] : 0;

$manager = new DutyDataManager();
$data = $manager->load($year);
$months = $data['months'];

function hl($name, $loggedIn) {
    if (empty(trim($name)) || empty($loggedIn)) return htmlspecialchars($name);
    if (trim($name) === $loggedIn) {
        return '<span class="my-name">' . htmlspecialchars($name) . '</span>';
    }
    return htmlspecialchars($name);
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>청소/마이크/안내인/연사음료 계획표</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Malgun Gothic', sans-serif;
            background: #f5f5f5;
            color: #333;
            font-size: 14px;
        }
        .container {
            max-width: 620px;
            margin: 0 auto;
            padding: 10px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            margin-bottom: 8px;
        }
        .page-title { font-size: 18px; font-weight: 700; color: #333; }
        .duty-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 16px;
        }
        .duty-table th {
            padding: 8px 4px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
        }
        .duty-table .header-row1 th { background: #333; color: white; }
        .duty-table .header-row2 th { background: #555; color: white; font-size: 11px; }
        .duty-table td {
            padding: 6px 4px;
            border-bottom: 1px solid #e8e8e8;
            font-size: 13px;
            text-align: center;
            vertical-align: middle;
        }
        .duty-table tr:last-child td { border-bottom: none; }
        .duty-table tr:hover { background: #f9f9f9; }

        .col-month { width: 40px; font-weight: 700; white-space: nowrap; }
        .col-group { width: 36px; }
        .col-period { width: 80px; font-size: 12px; }
        .col-name { width: auto; }

        .group-cell {
            font-weight: 700;
            font-size: 14px;
        }
        tbody.current-month td[rowspan] { background: #fff5f5; }
        tbody.current-month tr.active-half td { background: #fff5f5; }
        tbody.month-group td { border-bottom: 1px solid #e8e8e8; }
        tbody.month-group tr:last-child td { border-bottom: 1px solid #e8e8e8; }

        .my-name {
            background: linear-gradient(135deg, #ef4444, #f97316);
            color: white;
            padding: 1px 6px;
            border-radius: 3px;
            font-weight: 700;
        }

        .utility-section {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .utility-btn {
            padding: 8px 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            color: #555;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
        }
        .utility-btn:hover { background: #f5f5f5; }
        #newWindowGroup { display: none; margin-top: 8px; }

        @media (max-width: 768px) {
            body { overflow-x: auto; }
            .container { padding: 6px; min-width: 520px; }
            .duty-table { font-size: 11px; min-width: 520px; }
            .duty-table th { padding: 6px 2px; font-size: 10px; }
            .duty-table td { padding: 4px 2px; font-size: 11px; }
            .page-title { font-size: 15px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1 class="page-title">청소/마이크/안내인/연사음료 계획표</h1>
    </div>

    <table class="duty-table">
        <thead>
            <tr class="header-row1">
                <th rowspan="2" class="col-month"></th>
                <th rowspan="2" class="col-group">회관<br>청소<br>집단</th>
                <th colspan="4">청중 마이크</th>
                <th colspan="3">안내인</th>
                <th colspan="2">연사 음료</th>
            </tr>
            <tr class="header-row2">
                <th class="col-period">날짜</th>
                <th class="col-name">마이크1</th>
                <th class="col-name">마이크2</th>
                <th class="col-name">보조</th>
                <th class="col-name">청중석</th>
                <th class="col-name">청중석</th>
                <th class="col-name">출입구</th>
                <th class="col-name">담당자</th>
                <th class="col-name">보조</th>
            </tr>
        </thead>
            <?php for ($m = 1; $m <= 12; $m++):
                $month = isset($months[(string)$m]) ? $months[(string)$m] : array();
                $fh = isset($month['first_half']) ? $month['first_half'] : array();
                $sh = isset($month['second_half']) ? $month['second_half'] : array();
                $isCurrent = ($year === $currentYear && $m === $currentMonth);
                $tbodyClass = 'month-group' . ($isCurrent ? ' current-month' : '');
                $firstHalfActive = ($isCurrent && $currentDay <= 15) ? ' active-half' : '';
                $secondHalfActive = ($isCurrent && $currentDay > 15) ? ' active-half' : '';
            ?>
            <tbody class="<?php echo $tbodyClass; ?>">
                <tr class="<?php echo trim($firstHalfActive); ?>">
                    <td class="col-month" rowspan="2"><?php echo $m; ?>월</td>
                    <td class="col-group group-cell" rowspan="2" style="color:#2e7d32;"><?php echo htmlspecialchars($month['cleaning_group'] ?? ''); ?></td>
                    <td class="col-period">1일 - 15일</td>
                    <td><?php echo hl($fh['mic1'] ?? '', $loggedInUserName); ?></td>
                    <td><?php echo hl($fh['mic2'] ?? '', $loggedInUserName); ?></td>
                    <td><?php echo hl($fh['mic_assist'] ?? '', $loggedInUserName); ?></td>
                    <td><?php echo hl($fh['att_hall1'] ?? '', $loggedInUserName); ?></td>
                    <td><?php echo hl($fh['att_hall2'] ?? '', $loggedInUserName); ?></td>
                    <td><?php echo hl($fh['att_entrance'] ?? '', $loggedInUserName); ?></td>
                    <td rowspan="2"><?php echo hl($month['drink_main'] ?? '', $loggedInUserName); ?></td>
                    <td rowspan="2"><?php echo hl($month['drink_assist'] ?? '', $loggedInUserName); ?></td>
                </tr>
                <tr class="<?php echo trim($secondHalfActive); ?>">
                    <td class="col-period">16일 - 말일</td>
                    <td><?php echo hl($sh['mic1'] ?? '', $loggedInUserName); ?></td>
                    <td><?php echo hl($sh['mic2'] ?? '', $loggedInUserName); ?></td>
                    <td><?php echo hl($sh['mic_assist'] ?? '', $loggedInUserName); ?></td>
                    <td><?php echo hl($sh['att_hall1'] ?? '', $loggedInUserName); ?></td>
                    <td><?php echo hl($sh['att_hall2'] ?? '', $loggedInUserName); ?></td>
                    <td><?php echo hl($sh['att_entrance'] ?? '', $loggedInUserName); ?></td>
                </tr>
            </tbody>
            <?php endfor; ?>
    </table>

    <div class="utility-section">
        <?php if ($is_admin): ?>
            <a href="duty_admin.php?year=<?php echo $year; ?>" class="utility-btn">관리자모드로 보기</a>
        <?php endif; ?>
    </div>

    <div id="newWindowGroup">
        <a href="#" id="newWindowBtn" class="utility-btn">새창으로 보기 ↗</a>
    </div>
</div>

<script>
(function() {
    if (window.self !== window.top) {
        var group = document.getElementById('newWindowGroup');
        var btn = document.getElementById('newWindowBtn');
        if (group) group.style.display = '';
        if (btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                window.open(window.location.href, '_blank', 'noopener,noreferrer');
            });
        }
    }
})();
</script>
</body>
</html>
