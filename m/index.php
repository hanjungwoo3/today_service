<?php
date_default_timezone_set('Asia/Seoul');

// 로컬 개발 모드 체크
$localConfigFile = __DIR__ . '/config.php';
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}

// 로컬 모드가 아닐 때만 관리자 권한 체크
if (!defined('LOCAL_MODE') || LOCAL_MODE !== true) {
    $is_admin = false;
    if (file_exists(dirname(__FILE__) . '/../config.php')) {
        if (session_status() === PHP_SESSION_NONE) {
            $secure_cookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            session_set_cookie_params([
                'lifetime' => 3600,
                'path'     => '/',
                'secure'   => $secure_cookie,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
        require_once dirname(__FILE__) . '/../config.php';
        if (function_exists('mb_id') && function_exists('get_member_position')) {
            $is_admin = (get_member_position(mb_id()) == '2'); // 장로만
        }
    }

    if (!$is_admin) {
        header('Location: ../index.php');
        exit;
    }
}
// LOCAL_MODE일 때는 m/config.php에서 이미 DB 연결됨

$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_period = isset($_GET['period']) ? $_GET['period'] : '6m';
$group_size = isset($_GET['group']) ? (int)$_GET['group'] : 4;
$selected_meeting = isset($_GET['meeting']) ? (int)$_GET['meeting'] : 0;

// 해당 날짜의 호별(ms_type=1) 모임 목록 조회
$meetings = [];
$sql = "SELECT m_id, ms_time, mp_name, mb_id FROM t_meeting WHERE m_date = '{$selected_date}' AND ms_type = 1 ORDER BY ms_time";
$result = $mysqli->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // 참석자 수 계산
        $ids = preg_split('/[,\s]+/', trim($row['mb_id']));
        $ids = array_filter($ids, function($id) { return !empty($id) && is_numeric($id); });
        $row['count'] = count($ids);
        $meetings[] = $row;
    }
}

// 선택된 모임이 없거나 유효하지 않으면 첫번째 모임 선택
if ($selected_meeting == 0 && !empty($meetings)) {
    $selected_meeting = $meetings[0]['m_id'];
}

// 기간 옵션
$period_options = [
    '1w' => ['label' => '일주일', 'days' => 7],
    '1m' => ['label' => '1개월', 'days' => 30],
    '3m' => ['label' => '3개월', 'days' => 90],
    '6m' => ['label' => '6개월', 'days' => 180],
    '1y' => ['label' => '1년', 'days' => 365],
];
$period_days = isset($period_options[$selected_period]) ? $period_options[$selected_period]['days'] : 180;

// 선택된 호별 모임의 참석자 조회
$attendees = [];
if ($selected_meeting > 0) {
    $sql = "SELECT mb_id as attend_ids FROM t_meeting WHERE m_id = {$selected_meeting}";
    $result = $mysqli->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // 쉼표 또는 공백으로 분리
        $ids = preg_split('/[,\s]+/', trim($row['attend_ids']));
        foreach ($ids as $id) {
            $id = trim($id);
            if (!empty($id) && is_numeric($id)) {
                $attendees[] = (int)$id;
            }
        }
        $attendees = array_unique($attendees);
    }
}

// 참석자들의 정보 조회
$members = ['M' => [], 'W' => []];
$member_info = []; // mb_id => ['name' => '', 'sex' => '']
if (!empty($attendees)) {
    $ids_str = implode(',', $attendees);
    $sql = "SELECT mb_id, mb_name, mb_sex FROM t_member WHERE mb_id IN ({$ids_str}) ORDER BY mb_sex DESC, mb_name ASC";
    $result = $mysqli->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sex = $row['mb_sex'] ?: 'M';
            $members[$sex][] = $row;
            $member_info[$row['mb_id']] = ['name' => $row['mb_name'], 'sex' => $sex];
        }
    }
}

// 해당 날짜에 이미 배정된 구역 정보 조회 (호별 구역 + 공개증거)
$assigned_groups = ['M' => [], 'W' => []];
$assigned_members = ['M' => [], 'W' => []]; // 이미 배정된 사람들 ID 목록
$seen_groups = ['M' => [], 'W' => []]; // 중복 체크용

// 배정 처리 함수
function processAssignment($group_ids, $tt_name, &$attendees, &$member_info, &$assigned_groups, &$assigned_members, &$seen_groups) {
    $group_attendees = array_values(array_intersect($group_ids, $attendees));
    if (count($group_attendees) >= 1) {
        // 중복 체크 (전체 그룹 기준)
        sort($group_attendees);
        $group_key = implode(',', $group_attendees);

        // 그룹 내 성별 분류
        $members_by_sex = ['M' => [], 'W' => []];
        foreach ($group_attendees as $gid) {
            if (isset($member_info[$gid])) {
                $sex = $member_info[$gid]['sex'];
                $members_by_sex[$sex][] = $gid;
            }
        }

        $type = (strpos($tt_name, '공개증거') !== false) ? '공개' : '호별';

        // 각 성별에 해당하는 멤버가 있으면 해당 성별 배정완료에 추가
        foreach (['M', 'W'] as $sex) {
            if (!empty($members_by_sex[$sex])) {
                // 해당 성별 기준으로 중복 체크
                if (isset($seen_groups[$sex][$group_key])) {
                    continue;
                }
                $seen_groups[$sex][$group_key] = true;

                // 전체 그룹 정보 (모든 멤버 포함)
                $group_info = [];
                foreach ($group_attendees as $gid) {
                    if (isset($member_info[$gid])) {
                        $group_info[] = ['id' => $gid, 'name' => $member_info[$gid]['name']];
                    }
                }

                // 해당 성별 멤버만 배정완료 처리
                foreach ($members_by_sex[$sex] as $gid) {
                    $assigned_members[$sex][] = $gid;
                }

                if (!empty($group_info)) {
                    $assigned_groups[$sex][] = ['members' => $group_info, 'type' => $type];
                }
            }
        }
    }
}

// 1. t_territory에서 현재 배정 조회 (선택한 모임 기준)
if ($selected_meeting > 0) {
    $sql = "SELECT tt_id, tt_name, tt_assigned FROM t_territory WHERE tt_assigned_date = '{$selected_date}' AND tt_assigned != '' AND m_id = {$selected_meeting}";
    $result = $mysqli->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $group_ids = array_filter(array_map('trim', explode(',', $row['tt_assigned'])), function($id) {
                return !empty($id) && is_numeric($id);
            });
            $group_ids = array_map('intval', $group_ids);
            processAssignment($group_ids, $row['tt_name'], $attendees, $member_info, $assigned_groups, $assigned_members, $seen_groups);
        }
    }

    // 2. t_territory_record에서 배정 기록 조회 (선택한 모임 기준)
    $sql = "SELECT r.ttr_assigned_num, t.tt_name
            FROM t_territory_record r
            JOIN t_territory t ON r.tt_id = t.tt_id
            WHERE r.ttr_assigned_date = '{$selected_date}' AND r.ttr_assigned_num != '' AND r.m_id = {$selected_meeting}";
    $result = $mysqli->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $group_ids = array_filter(array_map('trim', explode(',', $row['ttr_assigned_num'])), function($id) {
                return !empty($id) && is_numeric($id);
            });
            $group_ids = array_map('intval', $group_ids);
            processAssignment($group_ids, $row['tt_name'], $attendees, $member_info, $assigned_groups, $assigned_members, $seen_groups);
        }
    }
}
$assigned_members['M'] = array_unique($assigned_members['M']);
$assigned_members['W'] = array_unique($assigned_members['W']);

// 짝 횟수 계산 함수
function getPairCount($mysqli, $mb_id1, $mb_id2, $days = 180) {
    $date_from = date('Y-m-d', strtotime("-{$days} days"));

    // t_territory에서 조회
    $sql = "SELECT COUNT(*) as cnt FROM t_territory
            WHERE FIND_IN_SET({$mb_id1}, tt_assigned)
            AND FIND_IN_SET({$mb_id2}, tt_assigned)
            AND tt_assigned_date >= '{$date_from}'
            AND tt_assigned_date != '0000-00-00'";
    $result = $mysqli->query($sql);
    $count = 0;
    if ($result) {
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];
    }

    // t_territory_record에서도 조회 (ttr_assigned_num에 ID가 저장됨)
    $sql2 = "SELECT COUNT(*) as cnt FROM t_territory_record
             WHERE FIND_IN_SET({$mb_id1}, ttr_assigned_num)
             AND FIND_IN_SET({$mb_id2}, ttr_assigned_num)
             AND ttr_assigned_date >= '{$date_from}'
             AND ttr_assigned_date != '0000-00-00'";
    $result2 = $mysqli->query($sql2);
    if ($result2) {
        $row2 = $result2->fetch_assoc();
        $count += (int)$row2['cnt'];
    }

    return $count;
}

// 같은 성별 간 짝 횟수 매트릭스 계산
$pair_matrix = ['M' => [], 'W' => []];
foreach (['M', 'W'] as $sex) {
    $member_list = $members[$sex];
    $count = count($member_list);
    for ($i = 0; $i < $count; $i++) {
        for ($j = $i + 1; $j < $count; $j++) {
            $id1 = $member_list[$i]['mb_id'];
            $id2 = $member_list[$j]['mb_id'];
            if (!isset($pair_matrix[$sex][$id1])) {
                $pair_matrix[$sex][$id1] = [];
            }
            if (!isset($pair_matrix[$sex][$id2])) {
                $pair_matrix[$sex][$id2] = [];
            }
            $pair_count = getPairCount($mysqli, $id1, $id2, $period_days);
            $pair_matrix[$sex][$id1][$id2] = $pair_count;
            $pair_matrix[$sex][$id2][$id1] = $pair_count;
        }
    }
}

// 짝 추천 함수: 짝 횟수가 적은 사람들끼리 그룹핑
function recommendGroups($members, $pair_matrix, $group_size) {
    if (count($members) < 2) return [];

    // 멤버 ID를 이름과 매핑
    $id_to_name = [];
    $member_ids = [];
    foreach ($members as $m) {
        $id_to_name[$m['mb_id']] = $m['mb_name'];
        $member_ids[] = $m['mb_id'];
    }

    // 전체 인원이 그룹 크기 이하면 한 그룹으로
    if (count($member_ids) <= $group_size) {
        $group_info = [];
        $total_pair_count = 0;
        foreach ($member_ids as $id) {
            $group_info[] = ['id' => $id, 'name' => $id_to_name[$id]];
        }
        for ($i = 0; $i < count($member_ids); $i++) {
            for ($j = $i + 1; $j < count($member_ids); $j++) {
                $total_pair_count += isset($pair_matrix[$member_ids[$i]][$member_ids[$j]]) ? $pair_matrix[$member_ids[$i]][$member_ids[$j]] : 0;
            }
        }
        return [['members' => $group_info, 'pair_count' => $total_pair_count]];
    }

    // 랜덤 셔플
    shuffle($member_ids);

    $groups = [];
    $assigned = [];

    while (count($assigned) < count($member_ids)) {
        // 아직 배정 안된 사람 중 첫번째 선택
        $available = array_diff($member_ids, $assigned);
        if (empty($available)) break;

        $available = array_values($available);
        shuffle($available);

        $group = [];
        $first = $available[0];
        $group[] = $first;
        $assigned[] = $first;

        // 그룹에 사람 추가
        while (count($group) < $group_size && count($assigned) < count($member_ids)) {
            $remaining = array_diff($member_ids, $assigned);
            if (empty($remaining)) break;

            // 현재 그룹원들과 짝 횟수 합이 가장 적은 사람 찾기
            $best_candidate = null;
            $best_score = PHP_INT_MAX;
            $candidates = [];

            foreach ($remaining as $candidate) {
                $score = 0;
                foreach ($group as $member) {
                    $score += isset($pair_matrix[$member][$candidate]) ? $pair_matrix[$member][$candidate] : 0;
                }
                $candidates[] = ['id' => $candidate, 'score' => $score];
            }

            // 점수순 정렬 후 같은 점수 내에서 랜덤 선택
            usort($candidates, function($a, $b) {
                return $a['score'] - $b['score'];
            });

            // 최소 점수와 같은 점수를 가진 후보들 중 랜덤 선택
            $min_score = $candidates[0]['score'];
            $min_candidates = array_filter($candidates, function($c) use ($min_score) {
                return $c['score'] === $min_score;
            });
            $min_candidates = array_values($min_candidates);
            shuffle($min_candidates);

            $best_candidate = $min_candidates[0]['id'];
            $group[] = $best_candidate;
            $assigned[] = $best_candidate;
        }

        // 그룹 정보 저장 (이름과 함께)
        $group_info = [];
        $total_pair_count = 0;
        foreach ($group as $id) {
            $group_info[] = ['id' => $id, 'name' => $id_to_name[$id]];
        }

        // 그룹 내 짝 횟수 합계 계산
        for ($i = 0; $i < count($group); $i++) {
            for ($j = $i + 1; $j < count($group); $j++) {
                $total_pair_count += isset($pair_matrix[$group[$i]][$group[$j]]) ? $pair_matrix[$group[$i]][$group[$j]] : 0;
            }
        }

        $groups[] = ['members' => $group_info, 'pair_count' => $total_pair_count];
    }

    return $groups;
}

// 추천 그룹 생성 (이미 배정된 사람들 제외)
$recommended_groups = ['M' => [], 'W' => []];
$single_unassigned = ['M' => null, 'W' => null]; // 미배정 1명인 경우
foreach (['M', 'W'] as $sex) {
    // 이미 배정된 사람 제외한 멤버 목록
    $unassigned_members = array_filter($members[$sex], function($m) use ($assigned_members, $sex) {
        return !in_array((int)$m['mb_id'], $assigned_members[$sex]);
    });
    $unassigned_members = array_values($unassigned_members); // 인덱스 재정렬
    if (count($unassigned_members) >= 2) {
        $recommended_groups[$sex] = recommendGroups($unassigned_members, $pair_matrix[$sex], $group_size);
    } elseif (count($unassigned_members) == 1) {
        $single_unassigned[$sex] = $unassigned_members[0];
    }
}
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>짝 배정 현황</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>" />
    <style>
        .date-selector {
            display: flex;
            align-items: center;
            gap: 8px 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .date-selector .filter-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .date-selector input[type="date"],
        .date-selector select {
            padding: 8px 12px;
            border: 1px solid #e0e7ff;
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
        }
        @media (max-width: 768px) {
            .date-selector {
                gap: 6px 10px;
            }
            .date-selector label {
                font-size: 13px;
            }
            .date-selector input[type="date"],
            .date-selector select {
                padding: 6px 8px;
                font-size: 13px;
            }
            .date-selector .filter-group {
                flex: 0 0 auto;
            }
        }
        .meeting-info {
            padding: 8px 12px;
            background: #f0fdf4;
            border-radius: 8px;
            font-size: 14px;
            color: #166534;
        }
        .search-btn {
            padding: 8px 16px;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .search-btn:hover {
            background: #2563eb;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 12px;
            padding: 8px 12px;
            border-radius: 8px;
        }
        .section-title.male {
            background: #dbeafe;
            color: #1e40af;
        }
        .section-title.female {
            background: #fce7f3;
            color: #be185d;
        }
        .matrix-container {
            overflow: auto;
            max-height: 70vh;
            max-width: 100%;
        }
        .pair-matrix {
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
        }
        .pair-matrix th, .pair-matrix td {
            border: 1px solid #e0e7ff;
            padding: 6px 8px;
            text-align: center;
            white-space: nowrap;
        }
        .pair-matrix thead th {
            background: #f8fafc;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .pair-matrix thead th:first-child {
            left: 0;
            z-index: 3;
        }
        .pair-matrix td.name {
            background: #f8fafc;
            font-weight: 600;
            text-align: left;
            position: sticky;
            left: 0;
            z-index: 1;
        }
        .pair-matrix td.count-0 {
            background: #fef2f2;
            color: #991b1b;
        }
        .pair-matrix td.count-1 {
            background: #fefce8;
            color: #854d0e;
        }
        .pair-matrix td.count-2 {
            background: #f0fdf4;
            color: #166534;
        }
        .pair-matrix td.count-3plus {
            background: #eff6ff;
            color: #1e40af;
        }
        .pair-matrix td.self {
            background: #e5e7eb;
            color: #9ca3af;
        }
        .recommend-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .group-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .group-card {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: #fff;
            border: 1px solid #e0e7ff;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .group-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background: #3b82f6;
            color: #fff;
            border-radius: 50%;
            font-size: 12px;
            font-weight: 600;
        }
        .group-members {
            font-size: 14px;
            font-weight: 500;
        }
        .group-pair-count {
            font-size: 12px;
            color: #6b7280;
        }
        .recommend-box {
            margin-top: 16px;
            padding: 12px;
            background: #f0fdf4;
            border-radius: 10px;
        }
        .recommend-title {
            font-size: 14px;
            font-weight: 600;
            color: #166534;
            margin-bottom: 10px;
        }
        .refresh-btn {
            font-size: 11px;
            padding: 3px 8px;
            background: #fff;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 8px;
        }
        .refresh-btn:hover {
            background: #dcfce7;
        }
        .no-data {
            padding: 20px;
            text-align: center;
            color: #6b7280;
            background: #f9fafb;
            border-radius: 8px;
        }
        .single-member {
            padding: 10px 14px;
            background: #fff;
            border: 1px solid #e0e7ff;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
        }
        .pair-matrix td.name.assigned,
        .pair-matrix thead th.assigned {
            background: #fef3c7 !important;
        }
        .assigned-box {
            margin-bottom: 12px;
            padding: 12px;
            background: #fefce8;
            border: 1px solid #fde047;
            border-radius: 10px;
        }
        .assigned-title {
            font-size: 13px;
            font-weight: 600;
            color: #854d0e;
            margin-bottom: 8px;
        }
        .unassigned-box {
            padding: 12px;
            background: #f0fdf4;
            border-radius: 10px;
        }
        .unassigned-title {
            font-size: 13px;
            font-weight: 600;
            color: #166534;
            margin-bottom: 8px;
        }
        .type-badge {
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 4px;
            margin-left: 4px;
            background: #e5e7eb;
            color: #4b5563;
        }
        .help-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .help-title {
            font-size: 14px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 12px;
        }
        .help-content {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .help-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .help-label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 4px;
        }
        .help-text {
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
        }
        .help-text b {
            color: #334155;
        }
        .help-color {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            margin-right: 4px;
        }
        .legend {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
        }
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1px solid #e0e7ff;
        }
        .back-link {
            margin-bottom: 16px;
        }
        .back-link a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <header class="toolbar">
            <div></div>
            <h1>짝 배정 현황 (최근 <?php echo $period_options[$selected_period]['label']; ?>)</h1>
            <div></div>
        </header>

        <form method="get" class="date-selector" id="filterForm">
            <div class="filter-group">
                <label for="date">봉사일:</label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" onchange="updateMeetings(this.value)">
            </div>
            <div class="filter-group">
                <label for="meeting">모임:</label>
                <select id="meeting" name="meeting">
                    <?php if (empty($meetings)): ?>
                        <option value="">호별 모임 없음</option>
                    <?php else: ?>
                        <?php foreach ($meetings as $m): ?>
                            <option value="<?php echo $m['m_id']; ?>" <?php echo $selected_meeting == $m['m_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(substr($m['ms_time'], 0, 5) . ' ' . $m['mp_name'] . ' (' . $m['count'] . '명)'); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="period">기간:</label>
                <select id="period" name="period">
                    <?php foreach ($period_options as $key => $opt): ?>
                        <option value="<?php echo $key; ?>" <?php echo $selected_period === $key ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($opt['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="group">인원:</label>
                <select id="group" name="group">
                    <option value="2" <?php echo $group_size === 2 ? 'selected' : ''; ?>>2명</option>
                    <option value="3" <?php echo $group_size === 3 ? 'selected' : ''; ?>>3명</option>
                    <option value="4" <?php echo $group_size === 4 ? 'selected' : ''; ?>>4명</option>
                </select>
            </div>
            <button type="submit" class="search-btn">조회</button>
        </form>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: #fef2f2;"></div>
                <span>0회</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #fefce8;"></div>
                <span>1회</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #f0fdf4;"></div>
                <span>2회</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #eff6ff;"></div>
                <span>3회 이상</span>
            </div>
        </div>

        <?php if (empty($meetings)): ?>
            <div class="no-data">
                <?php echo htmlspecialchars($selected_date); ?> 에 호별 봉사 모임이 없습니다.
            </div>
        <?php elseif (empty($attendees)): ?>
            <div class="no-data">
                선택한 모임에 등록된 참석자가 없습니다.
            </div>
        <?php else: ?>

            <!-- 형제 섹션 -->
            <?php if (count($members['M']) > 0): ?>
            <div class="section">
                <div class="section-title male">형제 (<?php echo count($members['M']); ?>명)</div>
                <?php if (count($members['M']) == 1): ?>
                    <div class="single-member"><?php echo htmlspecialchars($members['M'][0]['mb_name']); ?></div>
                <?php else: ?>
                    <div class="matrix-container">
                        <table class="pair-matrix">
                            <thead>
                                <tr>
                                    <th></th>
                                    <?php foreach ($members['M'] as $m): ?>
                                        <th class="<?php echo in_array((int)$m['mb_id'], $assigned_members['M']) ? 'assigned' : ''; ?>"><?php echo htmlspecialchars($m['mb_name']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members['M'] as $m1): ?>
                                    <tr>
                                        <td class="name <?php echo in_array((int)$m1['mb_id'], $assigned_members['M']) ? 'assigned' : ''; ?>"><?php echo htmlspecialchars($m1['mb_name']); ?></td>
                                        <?php foreach ($members['M'] as $m2): ?>
                                            <?php if ($m1['mb_id'] == $m2['mb_id']): ?>
                                                <td class="self">-</td>
                                            <?php else: ?>
                                                <?php
                                                    $cnt = isset($pair_matrix['M'][$m1['mb_id']][$m2['mb_id']])
                                                           ? $pair_matrix['M'][$m1['mb_id']][$m2['mb_id']] : 0;
                                                    $class = 'count-' . ($cnt >= 3 ? '3plus' : $cnt);
                                                ?>
                                                <td class="<?php echo $class; ?>"><?php echo $cnt; ?></td>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- 형제 추천 -->
                <?php if (!empty($assigned_groups['M']) || !empty($recommended_groups['M']) || $single_unassigned['M']): ?>
                <div class="recommend-container">
                    <?php if (!empty($assigned_groups['M'])): ?>
                    <div class="assigned-box">
                        <div class="assigned-title">배정 완료 (<?php echo count($assigned_groups['M']); ?>팀)</div>
                        <div class="group-list">
                            <?php foreach ($assigned_groups['M'] as $idx => $group): ?>
                                <div class="group-card">
                                    <span class="group-number" style="background:#eab308;"><?php echo $idx + 1; ?></span>
                                    <span class="group-members">
                                        <?php
                                            $names = array_map(function($m) { return $m['name']; }, $group['members']);
                                            echo htmlspecialchars(implode(', ', $names));
                                        ?>
                                    </span>
                                    <span class="type-badge"><?php echo $group['type']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($recommended_groups['M'])): ?>
                    <div class="unassigned-box">
                        <div class="unassigned-title">추천 짝 (<?php echo $group_size; ?>명씩) <button type="button" onclick="location.reload();" class="refresh-btn">다시 추천</button></div>
                        <div class="group-list">
                            <?php foreach ($recommended_groups['M'] as $idx => $group): ?>
                                <div class="group-card">
                                    <span class="group-number"><?php echo $idx + 1; ?></span>
                                    <span class="group-members">
                                        <?php
                                            $names = array_map(function($m) { return $m['name']; }, $group['members']);
                                            echo htmlspecialchars(implode(', ', $names));
                                        ?>
                                    </span>
                                    <span class="group-pair-count">(<?php echo $group['pair_count']; ?>회)</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php elseif ($single_unassigned['M']): ?>
                    <div class="unassigned-box">
                        <div class="unassigned-title">미배정</div>
                        <div class="group-list">
                            <div class="group-card">
                                <span class="group-number">1</span>
                                <span class="group-members"><?php echo htmlspecialchars($single_unassigned['M']['mb_name']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- 자매 섹션 -->
            <?php if (count($members['W']) > 0): ?>
            <div class="section">
                <div class="section-title female">자매 (<?php echo count($members['W']); ?>명)</div>
                <?php if (count($members['W']) == 1): ?>
                    <div class="single-member"><?php echo htmlspecialchars($members['W'][0]['mb_name']); ?></div>
                <?php else: ?>
                    <div class="matrix-container">
                        <table class="pair-matrix">
                            <thead>
                                <tr>
                                    <th></th>
                                    <?php foreach ($members['W'] as $m): ?>
                                        <th class="<?php echo in_array((int)$m['mb_id'], $assigned_members['W']) ? 'assigned' : ''; ?>"><?php echo htmlspecialchars($m['mb_name']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members['W'] as $m1): ?>
                                    <tr>
                                        <td class="name <?php echo in_array((int)$m1['mb_id'], $assigned_members['W']) ? 'assigned' : ''; ?>"><?php echo htmlspecialchars($m1['mb_name']); ?></td>
                                        <?php foreach ($members['W'] as $m2): ?>
                                            <?php if ($m1['mb_id'] == $m2['mb_id']): ?>
                                                <td class="self">-</td>
                                            <?php else: ?>
                                                <?php
                                                    $cnt = isset($pair_matrix['W'][$m1['mb_id']][$m2['mb_id']])
                                                           ? $pair_matrix['W'][$m1['mb_id']][$m2['mb_id']] : 0;
                                                    $class = 'count-' . ($cnt >= 3 ? '3plus' : $cnt);
                                                ?>
                                                <td class="<?php echo $class; ?>"><?php echo $cnt; ?></td>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- 자매 추천 -->
                <?php if (!empty($assigned_groups['W']) || !empty($recommended_groups['W']) || $single_unassigned['W']): ?>
                <div class="recommend-container">
                    <?php if (!empty($assigned_groups['W'])): ?>
                    <div class="assigned-box">
                        <div class="assigned-title">배정 완료 (<?php echo count($assigned_groups['W']); ?>팀)</div>
                        <div class="group-list">
                            <?php foreach ($assigned_groups['W'] as $idx => $group): ?>
                                <div class="group-card">
                                    <span class="group-number" style="background:#eab308;"><?php echo $idx + 1; ?></span>
                                    <span class="group-members">
                                        <?php
                                            $names = array_map(function($m) { return $m['name']; }, $group['members']);
                                            echo htmlspecialchars(implode(', ', $names));
                                        ?>
                                    </span>
                                    <span class="type-badge"><?php echo $group['type']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($recommended_groups['W'])): ?>
                    <div class="unassigned-box">
                        <div class="unassigned-title">추천 짝 (<?php echo $group_size; ?>명씩) <button type="button" onclick="location.reload();" class="refresh-btn">다시 추천</button></div>
                        <div class="group-list">
                            <?php foreach ($recommended_groups['W'] as $idx => $group): ?>
                                <div class="group-card">
                                    <span class="group-number"><?php echo $idx + 1; ?></span>
                                    <span class="group-members">
                                        <?php
                                            $names = array_map(function($m) { return $m['name']; }, $group['members']);
                                            echo htmlspecialchars(implode(', ', $names));
                                        ?>
                                    </span>
                                    <span class="group-pair-count">(<?php echo $group['pair_count']; ?>회)</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php elseif ($single_unassigned['W']): ?>
                    <div class="unassigned-box">
                        <div class="unassigned-title">미배정</div>
                        <div class="group-list">
                            <div class="group-card">
                                <span class="group-number">1</span>
                                <span class="group-members"><?php echo htmlspecialchars($single_unassigned['W']['mb_name']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php endif; ?>

        <!-- 기능 설명 -->
        <div class="help-section">
            <div class="help-title">사용 안내</div>
            <div class="help-content">
                <div class="help-item">
                    <div class="help-label">검색 방법</div>
                    <div class="help-text">
                        <b>봉사일</b>을 선택하면 해당 날짜의 호별 모임 목록이 자동으로 표시됩니다.<br>
                        <b>모임</b>을 선택하고 <b>조회</b> 버튼을 누르면 해당 모임 참석자들의 짝 현황이 표시됩니다.
                    </div>
                </div>
                <div class="help-item">
                    <div class="help-label">필터 옵션</div>
                    <div class="help-text">
                        <b>기간</b>: 짝 횟수를 계산할 기간 (최근 1주일 ~ 1년)<br>
                        <b>인원</b>: 추천 짝을 몇 명씩 그룹으로 만들지 설정
                    </div>
                </div>
                <div class="help-item">
                    <div class="help-label">짝 횟수 매트릭스</div>
                    <div class="help-text">
                        표에서 두 사람이 교차하는 셀의 숫자는 선택한 기간 동안 함께 구역 봉사를 한 횟수입니다.<br>
                        <span class="help-color" style="background:#fef2f2;">빨강(0회)</span>
                        <span class="help-color" style="background:#fefce8;">노랑(1회)</span>
                        <span class="help-color" style="background:#f0fdf4;">초록(2회)</span>
                        <span class="help-color" style="background:#eff6ff;">파랑(3회+)</span>
                    </div>
                </div>
                <div class="help-item">
                    <div class="help-label">추천 짝</div>
                    <div class="help-text">
                        함께 봉사한 횟수가 적은 사람들끼리 그룹을 만들어 추천합니다.<br>
                        <b>(숫자)회</b>: 해당 그룹 내 멤버들이 서로 함께 봉사한 총 횟수의 합계입니다.<br>
                        예: 3명 그룹에서 A-B가 1회, B-C가 0회, A-C가 2회면 총 (3회)로 표시됩니다.
                    </div>
                </div>
                <div class="help-item">
                    <div class="help-label">다시 추천</div>
                    <div class="help-text">
                        같은 점수의 후보가 여러 명일 때 랜덤으로 선택하므로, 버튼을 누르면 다른 조합이 나올 수 있습니다.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    function updateMeetings(date) {
        fetch('api/meetings.php?date=' + date)
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('meeting');
                select.innerHTML = '';
                if (data.length === 0) {
                    select.innerHTML = '<option value="">호별 모임 없음</option>';
                } else {
                    data.forEach(m => {
                        const option = document.createElement('option');
                        option.value = m.id;
                        option.textContent = m.label;
                        select.appendChild(option);
                    });
                }
            });
    }
    </script>
</body>
</html>
