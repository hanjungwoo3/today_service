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
$currentMonth = (int)date('n');
$currentDay = (int)date('j');
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
            background: #f5f5f5;
            color: #333;
            font-size: 14px;
            position: relative;
        }
        body::before {
            content: '관리자 모드';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 80px;
            font-weight: 900;
            color: rgba(239,68,68,0.06);
            pointer-events: none;
            z-index: 0;
            white-space: nowrap;
        }
        .container {
            max-width: 720px;
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

        .month-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        .month-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }
        .month-card.current { border: 2px solid #ef4444; }
        .month-card.current .month-header { color: #ef4444; }
        .month-header {
            padding: 6px 10px;
            font-weight: 700;
            font-size: 14px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #e8ecf0;
        }
        .month-header .header-info {
            display: flex;
            gap: 6px;
            font-size: 11px;
            font-weight: 500;
            color: #666;
            margin-left: auto;
            align-items: center;
        }
        .month-header .header-info .cleaning-group {
            color: #2e7d32;
            font-weight: 700;
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
            border: 1px solid #e8ecf0;
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
        .half-table td.row-label {
            font-weight: 600;
            color: #555;
            font-size: 11px;
            text-align: right;
            white-space: nowrap;
            background: #eef1f6;
        }

        /* 인라인 편집 */
        td.editable, span.editable { cursor: pointer; }
        td.editable:hover, span.editable:hover {
            background: #e3f2fd;
            outline: 1px dashed #90caf9;
            border-radius: 2px;
        }
        td.editing { padding: 1px; }
        td.editing input[type="text"] {
            width: 100%;
            padding: 2px 3px;
            border: 1px solid #42a5f5;
            border-radius: 3px;
            font-size: 11px;
            font-family: inherit;
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px rgba(66,165,245,0.2);
        }
        span.editable.editing input[type="text"] {
            width: 5em;
            padding: 1px 3px;
            border: 1px solid #42a5f5;
            border-radius: 3px;
            font-size: 11px;
            font-family: inherit;
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px rgba(66,165,245,0.2);
        }
        .header-editable {
            cursor: pointer;
            padding: 1px 4px;
            border-radius: 3px;
        }
        .header-editable:hover {
            background: rgba(0,0,0,0.1);
        }
        .header-editable.editing input[type="text"] {
            width: 4em;
            padding: 1px 3px;
            border: 1px solid #42a5f5;
            border-radius: 3px;
            font-size: 11px;
            font-family: inherit;
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px rgba(66,165,245,0.2);
        }
        .cell-empty { color: #ccc; }

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

        @media (max-width: 600px) {
            .container { padding: 6px; }
            .page-header { flex-wrap: wrap; }
            .header-actions { width: 100%; }
            .month-grid { grid-template-columns: 1fr; }
            .bottom-actions { position: sticky; left: 0; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header-actions" style="margin-bottom:8px;">
        <?php for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++): ?>
            <a href="?year=<?php echo $y; ?>"
               class="header-btn <?php echo $y === $year ? 'active' : ''; ?>"><?php echo $y; ?>년</a>
        <?php endfor; ?>
    </div>

    <div class="month-grid">
    <?php for ($m = 1; $m <= 12; $m++):
        $month = isset($months[(string)$m]) ? $months[(string)$m] : array();
        $fh = isset($month['first_half']) ? $month['first_half'] : array();
        $sh = isset($month['second_half']) ? $month['second_half'] : array();
        $isCurrent = ($year === $currentYear && $m === $currentMonth);
        $firstHalfActive = ($isCurrent && $currentDay <= 15) ? ' active-half' : '';
        $secondHalfActive = ($isCurrent && $currentDay > 15) ? ' active-half' : '';
    ?>
    <div class="month-card<?php echo $isCurrent ? ' current' : ''; ?>">
        <div class="month-header">
            <span><?php echo $m; ?>월</span>
            <span class="header-info">
                <span>청소집단:<span class="header-editable cleaning-group" data-month="<?php echo $m; ?>" data-field="cleaning_group"><?php echo htmlspecialchars($month['cleaning_group'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span></span>
                <span>음료:<span class="header-editable" data-month="<?php echo $m; ?>" data-field="drink_main"><?php echo htmlspecialchars($month['drink_main'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span>
                (<span class="header-editable" data-month="<?php echo $m; ?>" data-field="drink_assist"><?php echo htmlspecialchars($month['drink_assist'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span>)</span>
            </span>
        </div>
        <div class="month-body">
            <table class="half-table">
                <thead>
                    <tr>
                        <th></th>
                        <th class="<?php echo trim($firstHalfActive); ?>">상반기 (1-15일)</th>
                        <th class="<?php echo trim($secondHalfActive); ?>">하반기 (16-말일)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="row-label">마이크</td>
                        <td class="<?php echo trim($firstHalfActive); ?>"><span class="editable" data-month="<?php echo $m; ?>" data-half="first" data-field="mic1"><?php echo htmlspecialchars($fh['mic1'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span>, <span class="editable" data-month="<?php echo $m; ?>" data-half="first" data-field="mic2"><?php echo htmlspecialchars($fh['mic2'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span> (<span class="editable" data-month="<?php echo $m; ?>" data-half="first" data-field="mic_assist"><?php echo htmlspecialchars($fh['mic_assist'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span>)</td>
                        <td class="<?php echo trim($secondHalfActive); ?>"><span class="editable" data-month="<?php echo $m; ?>" data-half="second" data-field="mic1"><?php echo htmlspecialchars($sh['mic1'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span>, <span class="editable" data-month="<?php echo $m; ?>" data-half="second" data-field="mic2"><?php echo htmlspecialchars($sh['mic2'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span> (<span class="editable" data-month="<?php echo $m; ?>" data-half="second" data-field="mic_assist"><?php echo htmlspecialchars($sh['mic_assist'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span>)</td>
                    </tr>
                    <tr>
                        <td class="row-label">청중석 안내</td>
                        <td class="<?php echo trim($firstHalfActive); ?>"><span class="editable" data-month="<?php echo $m; ?>" data-half="first" data-field="att_hall1"><?php echo htmlspecialchars($fh['att_hall1'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span>, <span class="editable" data-month="<?php echo $m; ?>" data-half="first" data-field="att_hall2"><?php echo htmlspecialchars($fh['att_hall2'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span></td>
                        <td class="<?php echo trim($secondHalfActive); ?>"><span class="editable" data-month="<?php echo $m; ?>" data-half="second" data-field="att_hall1"><?php echo htmlspecialchars($sh['att_hall1'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span>, <span class="editable" data-month="<?php echo $m; ?>" data-half="second" data-field="att_hall2"><?php echo htmlspecialchars($sh['att_hall2'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></span></td>
                    </tr>
                    <tr>
                        <td class="row-label">출입구 안내</td>
                        <td class="editable<?php echo $firstHalfActive; ?>" data-month="<?php echo $m; ?>" data-half="first" data-field="att_entrance"><?php echo htmlspecialchars($fh['att_entrance'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                        <td class="editable<?php echo $secondHalfActive; ?>" data-month="<?php echo $m; ?>" data-half="second" data-field="att_entrance"><?php echo htmlspecialchars($sh['att_entrance'] ?? '') ?: '<span class="cell-empty">-</span>'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endfor; ?>
    </div>

    <div class="bottom-actions">
        <div class="action-card normal">
            <div class="action-card-title">사용자모드로 보기</div>
            <p class="action-card-desc">현재 입력한 내용을 사용자 화면에서 확인할 수 있습니다. 수정한 내용은 자동 저장됩니다.</p>
            <a href="duty_view.php?year=<?php echo $year; ?>" class="action-card-btn preview">사용자모드로 보기</a>
        </div>

        <div class="action-card info">
            <div class="action-card-title">프린트하기</div>
            <p class="action-card-desc">계획표를 인쇄용 페이지로 확인합니다.</p>
            <a href="duty_print.php?year=<?php echo $year; ?>" target="_blank" class="action-card-btn print">프린트하기</a>
        </div>
    </div>
</div>

<div class="save-toast" id="saveToast"></div>

<script>
(function() {
    var editingCell = null;
    var currentYear = <?php echo $year; ?>;
    var dutyData = <?php echo json_encode($data, JSON_UNESCAPED_UNICODE); ?>;

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function getCellValue(el) {
        var month = el.getAttribute('data-month');
        var field = el.getAttribute('data-field');
        var half = el.getAttribute('data-half');

        if (!dutyData.months[month]) return '';

        if (field === 'cleaning_group' || field === 'drink_main' || field === 'drink_assist') {
            return dutyData.months[month][field] || '';
        }

        var halfKey = half === 'second' ? 'second_half' : 'first_half';
        if (!dutyData.months[month][halfKey]) return '';
        return dutyData.months[month][halfKey][field] || '';
    }

    function setCellValue(el, value) {
        var month = el.getAttribute('data-month');
        var field = el.getAttribute('data-field');
        var half = el.getAttribute('data-half');

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

    function renderCell(el) {
        var value = getCellValue(el);
        el.innerHTML = escapeHtml(value) || '<span class="cell-empty">-</span>';
    }

    function startEdit(el) {
        if (editingCell === el) return;
        if (editingCell) finishEdit(editingCell);

        var value = getCellValue(el);
        editingCell = el;
        el.classList.add('editing');
        el.innerHTML = '<input type="text" value="' + escapeHtml(value) + '" />';
        var input = el.querySelector('input');
        input.focus();
        input.select();

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                finishEdit(el);
                var next = getNextEditable(el);
                if (next) startEdit(next);
            }
            if (e.key === 'Escape') {
                editingCell = null;
                el.classList.remove('editing');
                renderCell(el);
            }
            if (e.key === 'Tab') {
                e.preventDefault();
                finishEdit(el);
                var target = e.shiftKey ? getPrevEditable(el) : getNextEditable(el);
                if (target) startEdit(target);
            }
        });
    }

    function finishEdit(el) {
        if (!el || !el.classList.contains('editing')) return;
        var input = el.querySelector('input');
        if (input) {
            setCellValue(el, input.value.trim());
        }
        editingCell = null;
        el.classList.remove('editing');
        renderCell(el);
        autoSave();
    }

    function getNextEditable(el) {
        var all = Array.from(document.querySelectorAll('.half-table .editable'));
        var idx = all.indexOf(el);
        return idx >= 0 && idx < all.length - 1 ? all[idx + 1] : null;
    }

    function getPrevEditable(el) {
        var all = Array.from(document.querySelectorAll('.half-table .editable'));
        var idx = all.indexOf(el);
        return idx > 0 ? all[idx - 1] : null;
    }

    // 테이블 셀 클릭
    document.addEventListener('click', function(e) {
        var spanEditable = e.target.closest('span.editable');
        var td = e.target.closest('td.editable');
        var headerEl = e.target.closest('.header-editable');

        if (spanEditable) {
            startEdit(spanEditable);
            e.stopPropagation();
        } else if (td) {
            startEdit(td);
            e.stopPropagation();
        } else if (headerEl) {
            startEdit(headerEl);
            e.stopPropagation();
        } else if (editingCell && !editingCell.contains(e.target)) {
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
