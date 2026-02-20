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
$displayStartDate = isset($data['display_start_date']) ? $data['display_start_date'] : '';

$today = (new DateTime())->format('Y-m-d');

// 시작일 계산
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

$talks = array();
$hiddenTalks = array();
foreach ($allTalks as $talk) {
    if ($talk['date'] >= $startDate) {
        $talks[] = $talk;
    } else {
        $hiddenTalks[] = $talk;
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>공개 강연 계획표 - 관리자</title>
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
            max-width: 1024px;
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
        .page-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        .header-actions {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        /* 하단 액션 카드 */
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
        .action-card-title {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 6px;
        }
        .action-card-desc {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
            line-height: 1.4;
        }
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

        /* 테이블 */
        .talk-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .talk-table th {
            background: #4CAF50;
            color: white;
            padding: 10px 6px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
        }
        .talk-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #e8e8e8;
            font-size: 13px;
            vertical-align: top;
            cursor: pointer;
        }
        .talk-table tr:last-child td { border-bottom: none; }
        .talk-table tr:hover { background: #f9f9f9; }

        .col-date { width: 80px; text-align: center; white-space: nowrap; }
        .col-speaker { width: 70px; text-align: center; }
        .col-congregation { width: 90px; text-align: center; }
        .col-topic { min-width: 150px; width: 25%; }
        .col-chairman { width: 60px; text-align: center; }
        .col-reader { width: 60px; text-align: center; }
        .col-prayer { width: 60px; text-align: center; }
        .col-action { width: 28px; text-align: center; cursor: default; }

        .date-text { font-weight: 600; }

        .topic-circuit { background: #e8f5e9; }
        .topic-special { background: #fff3e0; }
        .topic-label {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .topic-label.circuit { background: #43a047; color: white; }
        .topic-label.special { background: #ef6c00; color: white; }
        .topic-text {
            display: block;
            line-height: 1.4;
            word-break: keep-all;
        }

        /* 순회방문/특별강연 행 배경 */
        tr.row-circuit { background: #e8f5e9; }
        tr.row-special { background: #fff3e0; }

        /* 지나간 날짜 */
        .past-row { opacity: 0.5; }

        /* 편집 모드 */
        td.editing {
            padding: 4px;
        }
        td.editing input[type="text"],
        td.editing input[type="date"] {
            width: 100%;
            padding: 4px;
            border: 1px solid #42a5f5;
            border-radius: 3px;
            font-size: 12px;
            font-family: inherit;
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px rgba(66,165,245,0.2);
        }
        .topic-edit-area {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .topic-type-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
        }
        .topic-type-row label {
            display: flex;
            align-items: center;
            gap: 2px;
            cursor: pointer;
            white-space: nowrap;
        }
        .topic-type-row input[type="checkbox"] { width: 13px; height: 13px; }
        .topic-fetch-row {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-left: 8px;
        }
        .topic-fetch-row input[type="number"] {
            width: 55px;
            padding: 2px 4px;
            font-size: 11px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        .topic-fetch-row button {
            padding: 2px 8px;
            font-size: 11px;
            border: 1px solid #4CAF50;
            background: #4CAF50;
            color: white;
            border-radius: 3px;
            cursor: pointer;
            white-space: nowrap;
        }
        .topic-fetch-row span {
            font-size: 10px;
            color: #999;
        }

        /* 셀 hover */
        td.editable:hover {
            background: #e3f2fd;
            outline: 1px dashed #90caf9;
        }

        /* 행 삭제 */
        .btn-remove-row {
            width: 20px; height: 20px;
            border: none; border-radius: 50%;
            background: #ef5350; color: white;
            font-size: 13px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            line-height: 1;
        }
        .btn-remove-row:hover { background: #d32f2f; }

        /* 행 추가 */
        .add-row-section { text-align: center; padding: 12px; }
        .btn-add-row {
            padding: 8px 20px;
            border: 1px dashed #aaa;
            border-radius: 4px;
            background: white;
            color: #666;
            font-size: 13px;
            cursor: pointer;
        }
        .btn-add-row:hover { background: #f5f5f5; border-color: #666; }

        /* 빈 셀 표시 */
        .cell-empty { color: #ccc; }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 15px;
        }

        /* 자동저장 토스트 */
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
        .save-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .save-toast.saving { background: #e3f2fd; color: #1565c0; }
        .save-toast.success { background: #e8f5e9; color: #2e7d32; }
        .save-toast.error { background: #ffebee; color: #c62828; }

        @media (max-width: 768px) {
            body { overflow-x: auto; }
            .container { padding: 6px; min-width: 540px; }
            .talk-table { font-size: 11px; min-width: 520px; }
            .talk-table th { padding: 6px 3px; font-size: 11px; }
            .talk-table td { padding: 4px 3px; font-size: 11px; }
            .col-date { width: 55px; }
            .col-speaker, .col-chairman, .col-reader, .col-prayer { width: 45px; }
            .col-congregation { width: 65px; }
            .page-title { font-size: 15px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1 class="page-title">공개 강연 계획표 <span style="font-size:12px;color:#888;font-weight:400;">관리자</span></h1>
    </div>

    <div class="action-card normal" style="margin-bottom:12px;">
        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <span style="font-weight:600; font-size:13px; white-space:nowrap;">출력 시작 날짜</span>
            <input type="date" id="displayStartDate" value="<?php echo htmlspecialchars($displayStartDate); ?>"
                   style="padding:4px 8px; border:1px solid #ddd; border-radius:4px; font-size:13px; font-family:inherit;" />
            <span id="startDateStatus" style="font-size:12px; color:#999;"></span>
        </div>
        <p style="font-size:12px; color:#888; margin-top:6px;">이 날짜부터 표시됩니다. 비워두면 지난주 일요일부터 표시됩니다.</p>
    </div>

    <table class="talk-table" id="talkTable">
        <thead>
            <tr>
                <th class="col-date">일자</th>
                <th class="col-speaker">연사</th>
                <th class="col-congregation">회중</th>
                <th class="col-topic">연제</th>
                <th class="col-chairman">사회</th>
                <th class="col-reader">낭독</th>
                <th class="col-prayer">기도</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="talkBody">
            <?php if (empty($talks)): ?>
                <tr id="emptyRow"><td colspan="8" class="empty-state">등록된 강연 일정이 없습니다.</td></tr>
            <?php else: ?>
                <?php foreach ($talks as $talk):
                    $isPast = $talk['date'] < $today;
                    $d = new DateTime($talk['date']);
                    $dateDisplay = $d->format('y/m/d');
                    $rowClass = '';
                    if ($talk['topic_type'] === 'circuit_visit') $rowClass = 'row-circuit';
                    elseif ($talk['topic_type'] === 'special_talk') $rowClass = 'row-special';
                ?>
                <tr class="talk-row <?php echo $isPast ? 'past-row' : ''; ?> <?php echo $rowClass; ?>"
                    data-date="<?php echo htmlspecialchars($talk['date']); ?>"
                    data-speaker="<?php echo htmlspecialchars($talk['speaker']); ?>"
                    data-congregation="<?php echo htmlspecialchars($talk['congregation']); ?>"
                    data-topic="<?php echo htmlspecialchars($talk['topic']); ?>"
                    data-topic-type="<?php echo htmlspecialchars($talk['topic_type']); ?>"
                    data-chairman="<?php echo htmlspecialchars($talk['chairman']); ?>"
                    data-reader="<?php echo htmlspecialchars($talk['reader']); ?>"
                    data-prayer="<?php echo htmlspecialchars($talk['prayer']); ?>">
                    <td class="col-date editable" data-field="date"><span class="date-text"><?php echo $dateDisplay; ?></span></td>
                    <td class="col-speaker editable" data-field="speaker"><?php echo htmlspecialchars($talk['speaker']) ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="col-congregation editable" data-field="congregation"><?php echo htmlspecialchars($talk['congregation']) ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="col-topic editable" data-field="topic">
                        <?php if ($talk['topic_type'] === 'circuit_visit'): ?>
                            <span class="topic-label circuit">순회 방문</span>
                        <?php elseif ($talk['topic_type'] === 'special_talk'): ?>
                            <span class="topic-label special">특별 강연</span>
                        <?php endif; ?>
                        <span class="topic-text"><?php echo htmlspecialchars($talk['topic']) ?: '<span class="cell-empty">-</span>'; ?></span>
                    </td>
                    <td class="col-chairman editable" data-field="chairman"><?php echo htmlspecialchars($talk['chairman']) ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="col-reader editable" data-field="reader"><?php echo htmlspecialchars($talk['reader']) ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="col-prayer editable" data-field="prayer"><?php echo htmlspecialchars($talk['prayer']) ?: '<span class="cell-empty">-</span>'; ?></td>
                    <td class="col-action">
                        <button type="button" class="btn-remove-row" onclick="removeRow(this)" title="삭제">&times;</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="add-row-section">
        <button type="button" class="btn-add-row" onclick="addRow()">+ 행 추가</button>
    </div>

    <div class="bottom-actions">
        <div class="action-card normal">
            <div class="action-card-title">사용자모드로 보기</div>
            <p class="action-card-desc">현재 입력한 내용을 사용자 화면에서 확인할 수 있습니다. 수정한 내용은 자동 저장됩니다.</p>
            <a href="talk_view.php" class="action-card-btn preview">👁️ 사용자모드로 보기</a>
        </div>

        <div id="newWindowGroup" style="display:none;" class="action-card normal">
            <a href="#" id="newWindowBtn" class="action-card-btn preview">↗ 새창으로 보기</a>
        </div>

        <div class="action-card info">
            <div class="action-card-title">프린트하기</div>
            <p class="action-card-desc">공개 강연 계획표를 인쇄용 페이지로 확인합니다. 인쇄할 행을 선택할 수 있습니다.</p>
            <a href="talk_print.php" class="action-card-btn print">🖨️ 프린트하기</a>
        </div>
    </div>
</div>

<div class="save-toast" id="saveToast"></div>

<script>
(function() {
    var editingCell = null;
    var hiddenTalks = <?php echo json_encode($hiddenTalks, JSON_UNESCAPED_UNICODE); ?>;

    var TOPIC_LIST = [
        "당신은 하느님을 얼마나 잘 아는가?",
        "당신은 마지막 날의 생존자가 될 것인가?",
        "여호와의 연합된 조직과 함께 전진하라",
        "우리 주위 세계에 나타나 있는 하느님에 대한 증거",
        "행복한 가정생활을 위한 확실한 조언",
        "노아 시대의 홍수와 당신",
        "\"부드러운 자비의 아버지\"를 본받으라",
        "자신을 위해서가 아니라 하느님의 뜻을 행하기 위하여 생활함",
        "하느님의 말씀을 듣고 행하는 사람이 되십시오",
        "언제나 정직하게 말하고 행동하십시오",
        "그리스도를 본받아 '세상에 속하지 마십시오'",
        "하느님은 권위에 대한 우리의 생각을 중요하게 여기신다",
        "성과 결혼에 대한 하느님의 견해",
        "깨끗한 백성은 여호와께 영예가 된다",
        "\"모든 사람에게 선한 일을 하십시오\"",
        "하느님과 계속 가까워지십시오",
        "자신이 가진 모든 것으로 하느님께 영광을 돌리십시오",
        "여호와를 당신의 산성으로 삼으십시오",
        "당신의 미래—어떻게 알 수 있는가?",
        "지금은 하느님께서 세상을 통치하실 때인가?",
        "왕국 마련 안에서 자신의 위치를 소중히 여기십시오",
        "당신은 여호와의 마련을 통해 유익을 얻고 있는가?",
        "우리의 삶에는 분명 목적이 있다",
        "당신은 '값진 진주'를 발견했는가?",
        "세상의 영을 물리치십시오!",
        "하느님은 당신을 소중히 여기시는가?",
        "결혼 생활의 행복한 출발",
        "결혼 생활에서 존중심과 사랑을 나타내십시오",
        "자녀를 기르는 일에 따르는 책임과 축복",
        "가족 간의 의사소통을 개선하는 방법",
        "당신은 영적 필요를 느끼는가?",
        "일상생활의 염려에 어떻게 대처할 수 있는가?",
        "정의로운 세상—과연 올 것인가?",
        "당신은 생존을 위해 \"표\"를 받았는가?",
        "당신도 영원히 살 수 있다!",
        "현 생명이 인생의 전부인가?",
        "하느님의 길로 걷는 것이 정말 유익한가?",
        "세상 끝을 어떻게 생존할 수 있는가?",
        "예수 그리스도—세상을 이기시는 분",
        "가까운 미래에 일어날 일",
        "그대로 서서 여호와의 구원을 보십시오",
        "사랑이 증오를 이길 수 있는가?",
        "하느님이 요구하시는 것—우리에게 언제나 유익하다",
        "예수의 가르침은 당신에게 어떤 유익을 줄 수 있는가?",
        "생명에 이르는 길을 따르십시오",
        "끝까지 확신을 굳게 유지하십시오",
        "좋은 소식을 믿으십시오",
        "그리스도인들의 충성—시험받고 있다",
        "이 땅이 과연 다시 깨끗해질 수 있는가?",
        "지혜로운 결정—어떻게 내릴 수 있는가?",
        "진리가 당신의 생활을 변화시키고 있는가?",
        "당신의 하느님은 누구인가?",
        "당신의 생각은 하느님의 생각과 일치한가?",
        "하느님과 그분의 약속에 대한 믿음을 길러 나가십시오",
        "하느님 앞에서 어떻게 좋은 이름을 얻을 수 있는가?",
        "우리가 신뢰할 수 있는 지도자는 누구인가?",
        "박해를 견디는 일",
        "누가 그리스도의 참제자인가?",
        "당신은 심은 대로 거둘 것이다",
        "당신의 삶의 목적은 무엇인가?",
        "당신은 누구의 약속을 신뢰하는가?",
        "어디에서 진정한 희망을 발견할 수 있는가?",
        "진리를 찾을 수 있습니까?",
        "당신은 '쾌락을 사랑하는 사람'이 될 것인가, '하느님을 사랑하는 사람'이 될 것인가?",
        "분노가 가득한 세상에서 평화를 이루는 방법",
        "당신은 수확하는 일에 참여할 것인가?",
        "여호와의 말씀과 그분의 창조물에 대해 묵상하십시오",
        "계속 서로 기꺼이 용서하십시오",
        "왜 자기희생적인 사랑을 나타내야 하는가?",
        "왜 하느님을 신뢰해야 하는가?",
        "깨어 있으십시오—왜 그리고 어떻게?",
        "사랑—참그리스도인 회중을 알아볼 수 있는 표",
        "지혜의 마음을 얻으십시오",
        "여호와께서는 우리를 살펴보고 계신다",
        "개인 생활에서 여호와의 통치권을 지지하십시오",
        "오늘날의 문제들에 대처하는 데 성서 원칙이 도움이 되는가?",
        "후대에 힘쓰십시오",
        "기쁜 마음으로 여호와를 섬기십시오",
        "당신은 하느님의 친구가 될 것인가, 세상의 친구가 될 것인가?",
        "과학과 성경—당신은 어느 쪽에 희망을 두는가?",
        "누가 제자 삼는 일을 할 자격이 있는가?",
        "여호와와 그리스도—삼위 일체의 일부인가?",
        "그리스도인은 십계명을 지켜야 하는가?",
        "당신은 이 세계의 운명을 피할 것인가?",
        "폭력적인 세상에서 전해지고 있는 좋은 소식",
        "하느님께서 들으시는 기도",
        "당신과 하느님과의 관계는 어떠한가?",
        "성서의 표준에 따라 생활해야 할 이유",
        "진리에 목마른 사람은 오십시오!",
        "참생명을 얻기 위해 힘써 노력하십시오!",
        "메시아의 임재와 그의 통치",
        "세상사에서 종교의 역할",
        "자연재해—언제 사라질 것인가?",
        "참 종교는 인간 사회의 필요를 충족시켜 준다",
        "영매술에 속지 마십시오!",
        "종교의 미래는 어떠할 것인가?",
        "구부러진 세대 가운데서 나무랄 데 없는 상태를 유지함",
        "이 세상의 장면은 변하고 있다",
        "성서를 신뢰할 수 있는 이유",
        "영원히 지속될 튼튼한 우정을 기르는 방법",
        "여호와—\"위대한 창조주\"",
        "예언의 말씀에 주의를 기울이십시오",
        "어떻게 진정한 기쁨을 누릴 수 있는가?",
        "부모 여러분—여러분은 내화 재료로 건축하고 있습니까?",
        "모든 환난 중에 위로를 받음",
        "땅을 파멸시키는 일로 인해 오게 될 하느님의 보응",
        "당신은 훈련받은 양심을 통해 유익을 얻고 있는가?",
        "당신도 확신을 가지고 미래를 맞이할 수 있다!",
        "하느님의 왕국은 가까웠다",
        "하느님을 첫째 자리에 둘 때 가정 생활에서 성공할 수 있다",
        "인류를 완전히 치료하는 일—어떻게 가능한가?",
        "이기적인 세상에서 사랑을 나타내는 방법",
        "청소년들은 어떻게 행복하고 성공적인 삶을 살 수 있는가?",
        "하느님의 경이로운 창조물들을 인식함",
        "사탄의 올가미로부터 우리 자신을 보호하는 방법",
        "친구를 지혜롭게 선택하라!",
        "선으로 악을 이기는 방법",
        "여호와의 관점에서 청소년을 바라봄",
        "그리스도인은 세상과 분리되어 있다—그것이 유익한 이유",
        "지금 하느님의 통치권에 복종해야 하는 이유",
        "세계적인 형제들로 이루어진 조직과 함께 대재난에서 생존하십시오",
        "세계 평화—무슨 근원으로부터?",
        "그리스도인들이 달라야 하는 이유",
        "성서의 저자가 하느님임을 확신할 수 있는 근거",
        "인류에게 대속물이 필요한 이유",
        "누가 구원을 받을 수 있는가?",
        "사람이 죽으면 어떻게 되는가?",
        "지옥은 실제로 불타는 고초의 장소인가?",
        "삼위일체는 성경의 가르침인가?",
        "땅은 영원히 있을 것이다",
        "마귀에 맞서 굳게 서 있으십시오!",
        "부활—죽음에 대한 승리!",
        "인간의 기원—무엇을 믿느냐가 중요한가?",
        "그리스도인은 안식일을 지켜야 하는가?",
        "생명과 피의 신성함",
        "하느님은 숭배에서 형상을 사용하는 것을 승인하시는가?",
        "성서의 기적들은 실제로 일어났는가?",
        "타락한 세상에서 건전한 정신으로 살라",
        "과학 세계에서의 하느님의 지혜",
        "예수 그리스도는 실제로 누구인가?",
        "인간 창조물이 신음하는 일—언제 끝날 것인가?",
        "여호와께 도피해야 하는 이유",
        "모든 위로의 하느님을 신뢰하라",
        "그리스도의 지도를 받는 충성스러운 회중",
        "누가 우리 하느님 여호와와 같은가?",
        "여호와를 찬양하기 위하여 교육을 사용하라",
        "여호와의 구원의 능력을 신뢰하라",
        "당신은 생명에 대한 하느님의 견해를 가지고 있는가?",
        "당신은 하느님과 함께 걷고 있는가?",
        "이 세상은 멸망될 것인가?",
        "여호와는 자신의 백성을 위한 안전한 산성이시다",
        "실제로 있을 아마겟돈—왜? 언제?",
        "외경스러운 날을 가깝게 여기십시오!",
        "저울에 달린 인간 통치",
        "바빌론의 심판 시간은 도래하였는가?",
        "심판 날—두려워할 때인가, 아니면 희망을 가질 때인가?",
        "참 그리스도인들이 하느님의 가르침을 단장하는 방법",
        "용기를 내어 여호와를 신뢰하여라",
        "위험한 세상에서 안전을 찾는 일",
        "당신의 그리스도인 신분을 지키라!",
        "예수께서는 왜 고난을 겪고 죽으셨는가?",
        "어둠의 세상으로부터의 구출",
        "왜 참 하느님을 두려워해야 하는가?",
        "하느님은 지금도 통제력을 행사하시는가?",
        "당신은 누구의 가치관을 소중히 여기는가?",
        "진정한 믿음이란 무엇이며 어떻게 나타낼 수 있는가?",
        "무분별한 세상에서 지혜롭게 행동하라",
        "이 혼란스러운 세상에서도 안전을 느낄 수 있다!",
        "왜 성서의 인도를 받아야 하는가?",
        "누가 인류를 통치할 자격이 있는가?",
        "당신도 지금부터 영원히 평화로운 삶을 누릴 수 있다!",
        "우리는 하느님 앞에서 어떤 신분을 가지고 있는가?",
        "하느님의 관점에서 참종교가 과연 있는가?",
        "하느님의 신세계—누가 들어갈 수 있는가?",
        "성서가 하느님의 말씀이라는 증거는 무엇인가?",
        "진정한 평화와 안전—언제 있을 것인가?",
        "고난의 때에 어디에서 도움을 얻을 수 있는가?",
        "충절의 길로 걸으라",
        "세상의 환상적인 것을 멀리하고, 왕국의 실제적인 것을 추구하라",
        "부활 희망이 우리 자신에게 실제적이어야 하는 이유",
        "끝은 우리가 생각하는 것보다 가까운가?",
        "하느님의 왕국이 지금 우리를 위해 하고 있는 일",
        "무가치한 것들을 보지 말고 물리치십시오!",
        "죽으면 모든 것이 끝나는가?",
        "진리가 우리의 생활에 영향을 미치는가?",
        "하느님의 행복한 백성과 연합하십시오",
        "사랑의 하느님께서 왜 악을 허용하시는가?",
        "당신은 여호와께 확신을 두고 있는가?",
        "하느님과 함께 걸으면 지금부터 영원히 축복을 받는다",
        "하느님께서 약속하신 완전하고 행복한 가정",
        "사랑과 믿음이 세상을 이기는 방법",
        "당신은 영원한 생명에 이르는 길을 걷고 있는가?",
        "세계적 고난의 때에 있을 구출",
        "경건한 지혜는 우리에게 어떻게 유익을 주는가?",
        "믿음과 용기를 가지고 미래를 직면함",
        "누가 이 땅을 회복시킬 것인가?",
        "\"지혜의 마음\"을 얻으십시오"
    ];

    // 로컬 날짜 포맷 (UTC 변환 방지)
    function formatLocalDate(d) {
        var yyyy = d.getFullYear();
        var mm = ('0' + (d.getMonth() + 1)).slice(-2);
        var dd = ('0' + d.getDate()).slice(-2);
        return yyyy + '-' + mm + '-' + dd;
    }

    // 다음 일요일 계산
    function getNextSunday(dateStr) {
        var d = new Date(dateStr + 'T00:00:00');
        var dow = d.getDay();
        if (dow === 0) {
            d.setDate(d.getDate() + 7);
        } else {
            d.setDate(d.getDate() + (7 - dow));
        }
        return formatLocalDate(d);
    }

    // 날짜 표시 포맷 (YY/MM/DD)
    function formatDateDisplay(dateStr) {
        if (!dateStr) return '-';
        var d = new Date(dateStr + 'T00:00:00');
        var yy = String(d.getFullYear()).slice(-2);
        var mm = ('0' + (d.getMonth() + 1)).slice(-2);
        var dd = ('0' + d.getDate()).slice(-2);
        return yy + '/' + mm + '/' + dd;
    }

    // 셀 렌더 (읽기 모드)
    function renderCell(td) {
        var row = td.closest('tr');
        var field = td.getAttribute('data-field');
        var value = row.getAttribute('data-' + field.replace('_', '-'));

        if (field === 'date') {
            td.innerHTML = '<span class="date-text">' + formatDateDisplay(value) + '</span>';
        } else if (field === 'topic') {
            var topicType = row.getAttribute('data-topic-type');
            var html = '';
            if (topicType === 'circuit_visit') html += '<span class="topic-label circuit">순회 방문</span>';
            else if (topicType === 'special_talk') html += '<span class="topic-label special">특별 강연</span>';
            html += '<span class="topic-text">' + (escapeHtml(value) || '<span class="cell-empty">-</span>') + '</span>';
            td.innerHTML = html;
        } else {
            td.innerHTML = escapeHtml(value) || '<span class="cell-empty">-</span>';
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // 행 배경색 업데이트
    function updateRowStyle(row) {
        var topicType = row.getAttribute('data-topic-type');
        row.classList.remove('row-circuit', 'row-special');
        if (topicType === 'circuit_visit') row.classList.add('row-circuit');
        else if (topicType === 'special_talk') row.classList.add('row-special');
    }

    // 셀 클릭 → 편집 모드
    function startEdit(td) {
        if (editingCell === td) return;
        if (editingCell) finishEdit(editingCell);

        var row = td.closest('tr');
        var field = td.getAttribute('data-field');
        var value = row.getAttribute('data-' + field.replace('_', '-'));
        editingCell = td;
        td.classList.add('editing');

        if (field === 'date') {
            td.innerHTML = '<input type="date" value="' + (value || '') + '" />';
            var input = td.querySelector('input');
            input.focus();
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') { finishEdit(td); }
                if (e.key === 'Escape') { cancelEdit(td, value); }
            });
        } else if (field === 'topic') {
            var topicType = row.getAttribute('data-topic-type');
            var html = '<div class="topic-edit-area">';
            html += '<div class="topic-type-row">';
            html += '<label><input type="checkbox" name="circuit_visit" ' + (topicType === 'circuit_visit' ? 'checked' : '') + ' /> 순회방문</label>';
            html += '<label><input type="checkbox" name="special_talk" ' + (topicType === 'special_talk' ? 'checked' : '') + ' /> 특별강연</label>';
            html += '<span class="topic-fetch-row">';
            html += '<input type="number" min="1" max="' + TOPIC_LIST.length + '" placeholder="번호" />';
            html += '<button type="button">가져오기</button>';
            html += '</span>';
            html += '</div>';
            html += '<input type="text" value="' + escapeHtml(value) + '" />';
            html += '</div>';
            td.innerHTML = html;

            var textInput = td.querySelector('input[type="text"]');
            var numInput = td.querySelector('input[type="number"]');
            var fetchBtn = td.querySelector('.topic-fetch-row button');

            textInput.focus();
            textInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') { finishEdit(td); }
                if (e.key === 'Escape') { cancelEdit(td, value); }
            });

            fetchBtn.addEventListener('click', function() {
                var num = parseInt(numInput.value);
                if (num >= 1 && num <= TOPIC_LIST.length) {
                    textInput.value = TOPIC_LIST[num - 1];
                    numInput.value = '';
                } else {
                    alert('1~' + TOPIC_LIST.length + ' 범위의 번호를 입력해주세요.');
                }
            });
            numInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    fetchBtn.click();
                }
                if (e.key === 'Escape') { cancelEdit(td, value); }
            });

            // 체크박스 상호 배타
            var cbCircuit = td.querySelector('input[name="circuit_visit"]');
            var cbSpecial = td.querySelector('input[name="special_talk"]');
            cbCircuit.addEventListener('change', function() {
                if (this.checked) cbSpecial.checked = false;
            });
            cbSpecial.addEventListener('change', function() {
                if (this.checked) cbCircuit.checked = false;
            });
        } else {
            td.innerHTML = '<input type="text" value="' + escapeHtml(value) + '" />';
            var input = td.querySelector('input');
            input.focus();
            input.select();
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') { finishEdit(td); }
                if (e.key === 'Escape') { cancelEdit(td, value); }
            });
        }
    }

    // 편집 완료
    function finishEdit(td) {
        if (!td || !td.classList.contains('editing')) return;
        var row = td.closest('tr');
        var field = td.getAttribute('data-field');

        if (field === 'topic') {
            var textInput = td.querySelector('input[type="text"]');
            var cbCircuit = td.querySelector('input[name="circuit_visit"]');
            var cbSpecial = td.querySelector('input[name="special_talk"]');
            if (textInput) row.setAttribute('data-topic', textInput.value.trim());
            var topicType = 'normal';
            if (cbCircuit && cbCircuit.checked) topicType = 'circuit_visit';
            else if (cbSpecial && cbSpecial.checked) topicType = 'special_talk';
            row.setAttribute('data-topic-type', topicType);
            updateRowStyle(row);
        } else {
            var input = td.querySelector('input');
            if (input) {
                var newVal = input.value.trim();
                row.setAttribute('data-' + field.replace('_', '-'), newVal);
                if (field === 'date') {
                    row.setAttribute('data-date', newVal);
                    sortRows();
                }
            }
        }

        editingCell = null;
        td.classList.remove('editing');
        renderCell(td);
        autoSave();
    }

    // 편집 취소
    function cancelEdit(td, originalValue) {
        editingCell = null;
        td.classList.remove('editing');
        renderCell(td);
    }

    // 행 정렬
    function sortRows() {
        var tbody = document.getElementById('talkBody');
        var rows = Array.from(tbody.querySelectorAll('.talk-row'));
        rows.sort(function(a, b) {
            return (a.getAttribute('data-date') || '').localeCompare(b.getAttribute('data-date') || '');
        });
        rows.forEach(function(r) { tbody.appendChild(r); });
    }

    // 셀 클릭 이벤트
    document.getElementById('talkTable').addEventListener('click', function(e) {
        var td = e.target.closest('td.editable');
        if (td) {
            startEdit(td);
            e.stopPropagation();
        }
    });

    // 외부 클릭 → 편집 완료
    document.addEventListener('click', function(e) {
        if (editingCell && !editingCell.contains(e.target)) {
            finishEdit(editingCell);
        }
    });

    // 행 추가
    window.addRow = function() {
        var tbody = document.getElementById('talkBody');
        var rows = tbody.querySelectorAll('.talk-row');
        var nextDate;

        if (rows.length > 0) {
            var lastRow = rows[rows.length - 1];
            var lastDate = lastRow.getAttribute('data-date');
            nextDate = lastDate ? getNextSunday(lastDate) : getNextSunday(formatLocalDate(new Date()));
        } else {
            nextDate = getNextSunday(formatLocalDate(new Date()));
        }

        var emptyRow = document.getElementById('emptyRow');
        if (emptyRow) emptyRow.remove();

        var tr = document.createElement('tr');
        tr.className = 'talk-row';
        tr.setAttribute('data-date', nextDate);
        tr.setAttribute('data-speaker', '');
        tr.setAttribute('data-congregation', '');
        tr.setAttribute('data-topic', '');
        tr.setAttribute('data-topic-type', 'normal');
        tr.setAttribute('data-chairman', '');
        tr.setAttribute('data-reader', '');
        tr.setAttribute('data-prayer', '');

        tr.innerHTML =
            '<td class="col-date editable" data-field="date"><span class="date-text">' + formatDateDisplay(nextDate) + '</span></td>' +
            '<td class="col-speaker editable" data-field="speaker"><span class="cell-empty">-</span></td>' +
            '<td class="col-congregation editable" data-field="congregation"><span class="cell-empty">-</span></td>' +
            '<td class="col-topic editable" data-field="topic"><span class="topic-text"><span class="cell-empty">-</span></span></td>' +
            '<td class="col-chairman editable" data-field="chairman"><span class="cell-empty">-</span></td>' +
            '<td class="col-reader editable" data-field="reader"><span class="cell-empty">-</span></td>' +
            '<td class="col-prayer editable" data-field="prayer"><span class="cell-empty">-</span></td>' +
            '<td class="col-action"><button type="button" class="btn-remove-row" onclick="removeRow(this)" title="삭제">&times;</button></td>';

        // 날짜순 삽입
        var inserted = false;
        var existingRows = tbody.querySelectorAll('.talk-row');
        for (var i = 0; i < existingRows.length; i++) {
            if ((existingRows[i].getAttribute('data-date') || '') > nextDate) {
                tbody.insertBefore(tr, existingRows[i]);
                inserted = true;
                break;
            }
        }
        if (!inserted) tbody.appendChild(tr);

        tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    // 행 삭제
    window.removeRow = function(btn) {
        if (!confirm('이 행을 삭제하시겠습니까?')) return;
        btn.closest('tr').remove();
        autoSave();
    };

    // 데이터 수집
    function collectData() {
        var rows = document.querySelectorAll('#talkBody .talk-row');
        var talks = [];
        rows.forEach(function(row) {
            talks.push({
                date: row.getAttribute('data-date') || '',
                speaker: row.getAttribute('data-speaker') || '',
                congregation: row.getAttribute('data-congregation') || '',
                topic: row.getAttribute('data-topic') || '',
                topic_type: row.getAttribute('data-topic-type') || 'normal',
                chairman: row.getAttribute('data-chairman') || '',
                reader: row.getAttribute('data-reader') || '',
                prayer: row.getAttribute('data-prayer') || ''
            });
        });
        return talks;
    }

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
        var talks = hiddenTalks.concat(collectData());
        var formData = new FormData();
        formData.append('action', 'save');
        formData.append('talks', JSON.stringify(talks));

        fetch('talk_api.php', { method: 'POST', body: formData })
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

    // 출력 시작 날짜 변경
    var startDateInput = document.getElementById('displayStartDate');
    var startDateStatus = document.getElementById('startDateStatus');

    if (startDateInput) {
        startDateInput.addEventListener('change', function() {
            var formData = new FormData();
            formData.append('action', 'set_start_date');
            formData.append('date', this.value);
            fetch('talk_api.php', { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(result) {
                    if (result.success) {
                        location.reload();
                    } else {
                        startDateStatus.textContent = '저장 실패';
                        startDateStatus.style.color = '#c62828';
                        setTimeout(function() { startDateStatus.textContent = ''; }, 2000);
                    }
                });
        });
    }

    // iframe 새창
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
    // 페이지 로드 시 빈 행 1개 기본 추가
    addRow();

})();
</script>
</body>
</html>
