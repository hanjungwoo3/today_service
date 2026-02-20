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
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>청소/마이크/안내인/연사음료 계획표 - 관리자</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Malgun Gothic', sans-serif;
            background: #fff8e1;
            color: #333;
            font-size: 14px;
            position: relative;
        }
        body::before {
            content: '관리자 전용';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 80px;
            font-weight: 900;
            color: rgba(0,0,0,0.04);
            pointer-events: none;
            z-index: 0;
            white-space: nowrap;
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
        .header-actions { display: flex; gap: 6px; align-items: center; }
        .header-btn {
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: white;
            color: #555;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
        }
        .header-btn:hover { background: #f0f0f0; }
        .header-btn.active { background: #4CAF50; color: white; border-color: #4CAF50; }

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
            cursor: pointer;
        }
        .duty-table tr:last-child td { border-bottom: none; }

        .col-month { width: 40px; font-weight: 700; cursor: default; white-space: nowrap; }
        .col-group { width: 36px; }
        .col-period { width: 80px; font-size: 12px; cursor: default; }
        .col-name { width: auto; }

        .group-cell { font-weight: 700; font-size: 14px; color: #2e7d32; }
        .month-separator td { border-bottom: 2px solid #ccc; }
        .cell-empty { color: #ccc; }

        td.editable:hover {
            background: #e3f2fd;
            outline: 1px dashed #90caf9;
        }
        td.editing { padding: 2px; }
        td.editing input[type="text"] {
            width: 4.5em;
            padding: 3px 2px;
            border: 1px solid #42a5f5;
            border-radius: 3px;
            font-size: 12px;
            font-family: inherit;
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px rgba(66,165,245,0.2);
            text-align: center;
        }

        .bottom-actions {
            margin-top: 20px;
            border-top: 1px solid #e0e0e0;
            padding-top: 15px;
        }
        .action-card {
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .action-card.normal { background: #f8f9ff; border: 1px solid #e0e0e0; }
        .action-card.info { background: #f0f8ff; border: 1px solid #b3d9ff; }
        .action-card-title { font-weight: 600; font-size: 14px; color: #333; margin-bottom: 6px; }
        .action-card-desc { font-size: 12px; color: #666; margin-bottom: 8px; line-height: 1.4; }
        .action-card-btn {
            width: 100%;
            display: block;
            text-align: center;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .action-card-btn.preview { background: #e0e0e0; color: #333; }
        .action-card-btn.preview:hover { background: #d5d5d5; }
        .action-card-btn.print { background: #4fc3f7; color: white; }
        .action-card-btn.print:hover { background: #29b6f6; }

        .save-toast {
            position: fixed;
            top: 12px;
            right: 12px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            z-index: 9999;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s, transform 0.3s;
            pointer-events: none;
        }
        .save-toast.show { opacity: 1; transform: translateY(0); }
        .save-toast.saving { background: #e3f2fd; color: #1565c0; }
        .save-toast.success { background: #e8f5e9; color: #2e7d32; }
        .save-toast.error { background: #ffebee; color: #c62828; }

        @media (max-width: 768px) {
            body { overflow-x: auto; }
            .container { padding: 6px; min-width: 520px; }
            .duty-table { font-size: 11px; min-width: 520px; }
            .duty-table th { padding: 6px 2px; font-size: 10px; }
            .duty-table td { padding: 4px 2px; font-size: 11px; }
            .page-title { font-size: 15px; }
            .bottom-actions { position: sticky; left: 0; width: calc(100vw - 12px); max-width: calc(100vw - 12px); }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1 class="page-title">청소/마이크/안내인/연사음료 <span style="font-size:12px;color:#888;font-weight:400;">관리자</span></h1>
        <div class="header-actions">
            <?php for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++): ?>
                <a href="?year=<?php echo $y; ?>"
                   class="header-btn <?php echo $y === $year ? 'active' : ''; ?>"><?php echo $y; ?>년</a>
            <?php endfor; ?>
        </div>
    </div>

    <table class="duty-table" id="dutyTable">
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
        <tbody>
            <?php for ($m = 1; $m <= 12; $m++):
                $month = isset($months[(string)$m]) ? $months[(string)$m] : array();
                $fh = isset($month['first_half']) ? $month['first_half'] : array();
                $sh = isset($month['second_half']) ? $month['second_half'] : array();
            ?>
                <tr data-month="<?php echo $m; ?>" data-half="first">
                    <td class="col-month" rowspan="2"><?php echo $m; ?>월</td>
                    <td class="col-group group-cell editable" rowspan="2"
                        data-month="<?php echo $m; ?>" data-field="cleaning_group"><?php echo htmlspecialchars($month['cleaning_group'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="col-period">1일 - 15일</td>
                    <td class="editable" data-month="<?php echo $m; ?>" data-half="first" data-field="mic1"><?php echo htmlspecialchars($fh['mic1'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="editable" data-month="<?php echo $m; ?>" data-half="first" data-field="mic2"><?php echo htmlspecialchars($fh['mic2'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="editable" data-month="<?php echo $m; ?>" data-half="first" data-field="mic_assist"><?php echo htmlspecialchars($fh['mic_assist'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="editable" data-month="<?php echo $m; ?>" data-half="first" data-field="att_hall1"><?php echo htmlspecialchars($fh['att_hall1'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="editable" data-month="<?php echo $m; ?>" data-half="first" data-field="att_hall2"><?php echo htmlspecialchars($fh['att_hall2'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="editable" data-month="<?php echo $m; ?>" data-half="first" data-field="att_entrance"><?php echo htmlspecialchars($fh['att_entrance'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="editable" rowspan="2" data-month="<?php echo $m; ?>" data-field="drink_main"><?php echo htmlspecialchars($month['drink_main'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="editable" rowspan="2" data-month="<?php echo $m; ?>" data-field="drink_assist"><?php echo htmlspecialchars($month['drink_assist'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                </tr>
                <tr class="month-separator" data-month="<?php echo $m; ?>" data-half="second">
                    <td class="col-period">16일 - 말일</td>
                    <td class="editable" data-month="<?php echo $m; ?>" data-half="second" data-field="mic1"><?php echo htmlspecialchars($sh['mic1'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="editable" data-month="<?php echo $m; ?>" data-half="second" data-field="mic2"><?php echo htmlspecialchars($sh['mic2'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="editable" data-month="<?php echo $m; ?>" data-half="second" data-field="mic_assist"><?php echo htmlspecialchars($sh['mic_assist'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="editable" data-month="<?php echo $m; ?>" data-half="second" data-field="att_hall1"><?php echo htmlspecialchars($sh['att_hall1'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="editable" data-month="<?php echo $m; ?>" data-half="second" data-field="att_hall2"><?php echo htmlspecialchars($sh['att_hall2'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="editable" data-month="<?php echo $m; ?>" data-half="second" data-field="att_entrance"><?php echo htmlspecialchars($sh['att_entrance'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div class="bottom-actions">
        <div class="action-card normal">
            <div class="action-card-title">사용자모드로 보기</div>
            <p class="action-card-desc">현재 입력한 내용을 사용자 화면에서 확인할 수 있습니다. 수정한 내용은 자동 저장됩니다.</p>
            <a href="duty_view.php?year=<?php echo $year; ?>" class="action-card-btn preview">사용자모드로 보기</a>
        </div>

        <div class="action-card info">
            <div class="action-card-title">프린트하기</div>
            <p class="action-card-desc">계획표를 인쇄용 페이지로 확인합니다.</p>
            <a href="duty_print.php?year=<?php echo $year; ?>" class="action-card-btn print">프린트하기</a>
        </div>
    </div>
</div>

<div class="save-toast" id="saveToast"></div>

<script>
(function() {
    var editingCell = null;
    var currentYear = <?php echo $year; ?>;

    // 현재 데이터를 메모리에 유지
    var dutyData = <?php echo json_encode($data, JSON_UNESCAPED_UNICODE); ?>;

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function getCellValue(td) {
        var month = td.getAttribute('data-month');
        var field = td.getAttribute('data-field');
        var half = td.getAttribute('data-half');

        if (!dutyData.months[month]) return '';

        if (field === 'cleaning_group' || field === 'drink_main' || field === 'drink_assist') {
            return dutyData.months[month][field] || '';
        }

        var halfKey = half === 'second' ? 'second_half' : 'first_half';
        if (!dutyData.months[month][halfKey]) return '';
        return dutyData.months[month][halfKey][field] || '';
    }

    function setCellValue(td, value) {
        var month = td.getAttribute('data-month');
        var field = td.getAttribute('data-field');
        var half = td.getAttribute('data-half');

        if (!dutyData.months[month]) {
            dutyData.months[month] = {
                cleaning_group: '',
                first_half: { mic1: '', mic2: '', mic_assist: '', att_hall1: '', att_hall2: '', att_entrance: '' },
                second_half: { mic1: '', mic2: '', mic_assist: '', att_hall1: '', att_hall2: '', att_entrance: '' },
                drink_main: '', drink_assist: ''
            };
        }

        if (field === 'cleaning_group' || field === 'drink_main' || field === 'drink_assist') {
            dutyData.months[month][field] = value;
        } else {
            var halfKey = half === 'second' ? 'second_half' : 'first_half';
            if (!dutyData.months[month][halfKey]) {
                dutyData.months[month][halfKey] = { mic1: '', mic2: '', mic_assist: '', att_hall1: '', att_hall2: '', att_entrance: '' };
            }
            dutyData.months[month][halfKey][field] = value;
        }
    }

    function renderCell(td) {
        var value = getCellValue(td);
        if (td.getAttribute('data-field') === 'cleaning_group') {
            td.innerHTML = escapeHtml(value) || '<span class="cell-empty">-</span>';
        } else {
            td.innerHTML = escapeHtml(value) || '<span class="cell-empty">-</span>';
        }
    }

    function startEdit(td) {
        if (editingCell === td) return;
        if (editingCell) finishEdit(editingCell);

        var value = getCellValue(td);
        editingCell = td;
        td.classList.add('editing');
        td.innerHTML = '<input type="text" value="' + escapeHtml(value) + '" />';
        var input = td.querySelector('input');
        input.focus();
        input.select();

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                finishEdit(td);
                // Tab 처럼 다음 셀로 이동
                var next = getNextEditable(td);
                if (next) startEdit(next);
            }
            if (e.key === 'Escape') {
                editingCell = null;
                td.classList.remove('editing');
                renderCell(td);
            }
            if (e.key === 'Tab') {
                e.preventDefault();
                finishEdit(td);
                var target = e.shiftKey ? getPrevEditable(td) : getNextEditable(td);
                if (target) startEdit(target);
            }
        });
    }

    function finishEdit(td) {
        if (!td || !td.classList.contains('editing')) return;
        var input = td.querySelector('input');
        if (input) {
            setCellValue(td, input.value.trim());
        }
        editingCell = null;
        td.classList.remove('editing');
        renderCell(td);
        autoSave();
    }

    function getNextEditable(td) {
        var all = Array.from(document.querySelectorAll('#dutyTable td.editable'));
        var idx = all.indexOf(td);
        return idx >= 0 && idx < all.length - 1 ? all[idx + 1] : null;
    }

    function getPrevEditable(td) {
        var all = Array.from(document.querySelectorAll('#dutyTable td.editable'));
        var idx = all.indexOf(td);
        return idx > 0 ? all[idx - 1] : null;
    }

    // 셀 클릭
    document.getElementById('dutyTable').addEventListener('click', function(e) {
        var td = e.target.closest('td.editable');
        if (td) {
            startEdit(td);
            e.stopPropagation();
        }
    });

    // 외부 클릭
    document.addEventListener('click', function(e) {
        if (editingCell && !editingCell.contains(e.target)) {
            finishEdit(editingCell);
        }
    });

    // 자동 저장
    var saveTimer = null;
    function autoSave() {
        if (saveTimer) clearTimeout(saveTimer);
        saveTimer = setTimeout(doSave, 300);
    }

    function showToast(text, type) {
        var toast = document.getElementById('saveToast');
        toast.textContent = text;
        toast.className = 'save-toast ' + type + ' show';
        setTimeout(function() { toast.classList.remove('show'); }, 1500);
    }

    function doSave() {
        var formData = new FormData();
        formData.append('action', 'save');
        formData.append('year', currentYear);
        formData.append('data', JSON.stringify(dutyData));

        fetch('duty_api.php', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.success) {
                    showToast('저장됨', 'success');
                } else {
                    showToast('저장 실패: ' + (result.error || ''), 'error');
                }
            })
            .catch(function(err) {
                showToast('오류: ' + err.message, 'error');
            });
    }
})();
</script>
</body>
</html>
