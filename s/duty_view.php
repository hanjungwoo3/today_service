<?php
header('Cache-Control: no-cache, no-store, must-revalidate');
date_default_timezone_set('Asia/Seoul');

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
            max-width: 720px;
            margin: 0 auto;
            padding: 10px;
        }
        .month-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            margin-bottom: 8px;
        }
        .page-title { font-size: 18px; font-weight: 700; color: #333; }

        .month-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 0;
            overflow: hidden;
        }
        .month-card.current { border: 2px solid #ef4444; }
        .month-card:not(.current) { border: 1px solid #e0e0e0; }
        .month-header {
            padding: 6px 10px;
            font-weight: 700;
            font-size: 14px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #e8ecf0;
        }
        .month-header .header-info {
            display: flex;
            gap: 6px;
            font-size: 11px;
            font-weight: 500;
            color: #666;
            margin-left: auto;
        }
        .month-header .header-info .cleaning-group {
            color: #2e7d32;
            font-weight: 700;
        }
        .month-card.current .month-header {
            color: #ef4444;
        }
        .month-body { padding: 1px; }
        .half-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            background: #f8f9ff;
            border: 1px solid #e8ecf0;
            border-radius: 6px;
            overflow: hidden;
            font-size: 12px;
        }
        .half-table th {
            background: #eef1f6;
            font-size: 10px;
            font-weight: 600;
            color: #888;
            padding: 2px 3px;
            text-align: center;
            border-bottom: 1px solid #e8ecf0;
        }
        .half-table th.active-half {
            background: #fee2e2;
            color: #ef4444;
        }
        .half-table td.active-half {
            background: #fff5f5;
        }
        .half-table td {
            padding: 2px 3px;
            border: 1px solid #e8ecf0;
            text-align: left;
            vertical-align: middle;
        }
        .half-table th {
            border: 1px solid #e8ecf0;
        }
        .half-table td.row-label {
            font-weight: 600;
            color: #555;
            font-size: 11px;
            text-align: right;
            white-space: nowrap;
            background: #eef1f6;
        }
        .cleaning-group {
            color: #2e7d32;
            font-weight: 700;
        }

        .admin-link { grid-column: span 2; }

        .my-name {
            background: linear-gradient(135deg, #ef4444, #f97316);
            color: white;
            padding: 1px 6px;
            border-radius: 3px;
            font-weight: 700;
        }

        @media (max-width: 600px) {
            .container { padding: 6px; }
            .month-grid {
                grid-template-columns: 1fr;
            }
            .admin-link { grid-column: span 1; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="month-grid">
    <?php for ($m = 1; $m <= 12; $m++):
        if ($year === $currentYear && $m < $currentMonth) continue;
        $month = isset($months[(string)$m]) ? $months[(string)$m] : array();
        $fh = isset($month['first_half']) ? $month['first_half'] : array();
        $sh = isset($month['second_half']) ? $month['second_half'] : array();
        $isCurrent = ($year === $currentYear && $m === $currentMonth);
        $cardClass = 'month-card' . ($isCurrent ? ' current' : '');
        $firstHalfActive = ($isCurrent && $currentDay <= 15) ? ' active-half' : '';
        $secondHalfActive = ($isCurrent && $currentDay > 15) ? ' active-half' : '';

        // 연사음료 표시
        $dm = trim($month['drink_main'] ?? '');
        $da = trim($month['drink_assist'] ?? '');
        $drinkDisplay = '';
        if (!empty($dm)) $drinkDisplay = hl($dm, $loggedInUserName);
        if (!empty($da)) $drinkDisplay .= ' (' . hl($da, $loggedInUserName) . ')';
    ?>
    <div class="<?php echo $cardClass; ?>">
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
            <?php
                // 상반기/하반기 데이터 준비
                $halfData = array();
                foreach (array(array('label' => '상반기 (1-15일)', 'data' => $fh, 'activeClass' => $firstHalfActive), array('label' => '하반기 (16-말일)', 'data' => $sh, 'activeClass' => $secondHalfActive)) as $half) {
                    $h = $half['data'];
                    $m1 = trim($h['mic1'] ?? ''); $m2 = trim($h['mic2'] ?? ''); $ma = trim($h['mic_assist'] ?? '');
                    $micMain = array();
                    if (!empty($m1)) $micMain[] = hl($m1, $loggedInUserName);
                    if (!empty($m2)) $micMain[] = hl($m2, $loggedInUserName);
                    $micDisplay = implode(', ', $micMain);
                    if (!empty($ma)) $micDisplay .= ' (' . hl($ma, $loggedInUserName) . ')';

                    $hallNames = array();
                    $h1 = trim($h['att_hall1'] ?? ''); $h2 = trim($h['att_hall2'] ?? '');
                    if (!empty($h1)) $hallNames[] = hl($h1, $loggedInUserName);
                    if (!empty($h2)) $hallNames[] = hl($h2, $loggedInUserName);

                    $entrance = trim($h['att_entrance'] ?? '');
                    $halfData[] = array(
                        'label' => $half['label'], 'activeClass' => $half['activeClass'],
                        'mic' => !empty(trim($micDisplay)) ? $micDisplay : '-',
                        'hall' => !empty($hallNames) ? implode(', ', $hallNames) : '-',
                        'entrance' => !empty($entrance) ? hl($entrance, $loggedInUserName) : '-',
                    );
                }
            ?>
            <table class="half-table">
                <thead>
                    <tr>
                        <th></th>
                        <th class="<?php echo trim($halfData[0]['activeClass']); ?>"><?php echo $halfData[0]['label']; ?></th>
                        <th class="<?php echo trim($halfData[1]['activeClass']); ?>"><?php echo $halfData[1]['label']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="row-label">마이크</td>
                        <td class="<?php echo trim($halfData[0]['activeClass']); ?>"><?php echo $halfData[0]['mic']; ?></td>
                        <td class="<?php echo trim($halfData[1]['activeClass']); ?>"><?php echo $halfData[1]['mic']; ?></td>
                    </tr>
                    <tr>
                        <td class="row-label">청중석 안내</td>
                        <td class="<?php echo trim($halfData[0]['activeClass']); ?>"><?php echo $halfData[0]['hall']; ?></td>
                        <td class="<?php echo trim($halfData[1]['activeClass']); ?>"><?php echo $halfData[1]['hall']; ?></td>
                    </tr>
                    <tr>
                        <td class="row-label">출입구 안내</td>
                        <td class="<?php echo trim($halfData[0]['activeClass']); ?>"><?php echo $halfData[0]['entrance']; ?></td>
                        <td class="<?php echo trim($halfData[1]['activeClass']); ?>"><?php echo $halfData[1]['entrance']; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endfor; ?>

    <?php if ($is_elder): ?>
    <div class="admin-link" style="background: #f8f9ff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px; overflow: hidden;">
        <div style="font-weight: 600; font-size: 13px; color: #333; margin-bottom: 4px;">관리자모드</div>
        <p style="font-size: 11px; color: #666; margin-bottom: 6px; line-height: 1.4;">청소 집단, 마이크, 안내인, 연사음료 배정을 수정할 수 있습니다.</p>
        <a href="duty_admin.php?year=<?php echo $year; ?>" style="display: block; text-align: center; padding: 6px 12px; background: #e0e0e0; color: #333; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 12px;">관리자모드로 보기</a>
    </div>
    <?php endif; ?>
    </div>
</div>
</body>
</html>
