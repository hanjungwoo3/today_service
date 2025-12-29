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
        if (function_exists('mb_id') && function_exists('is_admin')) {
            $is_admin = is_admin(mb_id());
        }
    }

    if (!$is_admin) {
        header('Location: view.php');
        exit;
    }
} else {
    // 로컬 모드일 때 DB 연결
    require_once dirname(__FILE__) . '/../config.php';
}

$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_period = isset($_GET['period']) ? $_GET['period'] : '6m';
$group_size = isset($_GET['group']) ? (int)$_GET['group'] : 4;

// 기간 옵션
$period_options = [
    '1w' => ['label' => '일주일', 'days' => 7],
    '1m' => ['label' => '1개월', 'days' => 30],
    '3m' => ['label' => '3개월', 'days' => 90],
    '6m' => ['label' => '6개월', 'days' => 180],
    '1y' => ['label' => '1년', 'days' => 365],
];
$period_days = isset($period_options[$selected_period]) ? $period_options[$selected_period]['days'] : 180;

// 해당 날짜의 모임 참석자 조회
$attendees = [];
$sql = "SELECT m.mb_id as attend_ids FROM t_meeting m WHERE m.m_date = '{$selected_date}'";
$result = $mysqli->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // 쉼표 또는 공백으로 분리
        $ids = preg_split('/[,\s]+/', trim($row['attend_ids']));
        foreach ($ids as $id) {
            $id = trim($id);
            if (!empty($id) && is_numeric($id)) {
                $attendees[] = (int)$id;
            }
        }
    }
    $attendees = array_unique($attendees);
}

// 참석자들의 정보 조회
$members = ['M' => [], 'W' => []];
if (!empty($attendees)) {
    $ids_str = implode(',', $attendees);
    $sql = "SELECT mb_id, mb_name, mb_sex FROM t_member WHERE mb_id IN ({$ids_str}) ORDER BY mb_sex DESC, mb_name ASC";
    $result = $mysqli->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sex = $row['mb_sex'] ?: 'M';
            $members[$sex][] = $row;
        }
    }
}

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

    // t_territory_record에서도 조회
    $sql2 = "SELECT COUNT(*) as cnt FROM t_territory_record
             WHERE FIND_IN_SET({$mb_id1}, ttr_assigned)
             AND FIND_IN_SET({$mb_id2}, ttr_assigned)
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

// 추천 그룹 생성
$recommended_groups = ['M' => [], 'W' => []];
foreach (['M', 'W'] as $sex) {
    if (count($members[$sex]) >= 2) {
        $recommended_groups[$sex] = recommendGroups($members[$sex], $pair_matrix[$sex], $group_size);
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
            gap: 12px;
            margin-bottom: 20px;
        }
        .date-selector input[type="date"],
        .date-selector select {
            padding: 8px 12px;
            border: 1px solid #e0e7ff;
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
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
        .no-data {
            padding: 20px;
            text-align: center;
            color: #6b7280;
            background: #f9fafb;
            border-radius: 8px;
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
            <a href="index.php" style="text-decoration: none;">
                <button type="button">목록</button>
            </a>
            <h1>짝 배정 현황 (최근 <?php echo $period_options[$selected_period]['label']; ?>)</h1>
            <div></div>
        </header>

        <form method="get" class="date-selector">
            <label for="date">봉사 날짜:</label>
            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" onchange="this.form.submit()">
            <label for="period">조회 기간:</label>
            <select id="period" name="period" onchange="this.form.submit()">
                <?php foreach ($period_options as $key => $opt): ?>
                    <option value="<?php echo $key; ?>" <?php echo $selected_period === $key ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($opt['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="group">짝 인원:</label>
            <select id="group" name="group" onchange="this.form.submit()">
                <option value="2" <?php echo $group_size === 2 ? 'selected' : ''; ?>>2명</option>
                <option value="3" <?php echo $group_size === 3 ? 'selected' : ''; ?>>3명</option>
                <option value="4" <?php echo $group_size === 4 ? 'selected' : ''; ?>>4명</option>
            </select>
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

        <?php if (empty($attendees)): ?>
            <div class="no-data">
                <?php echo htmlspecialchars($selected_date); ?> 에 등록된 참석자가 없습니다.
            </div>
        <?php else: ?>

            <!-- 형제 섹션 -->
            <div class="section">
                <div class="section-title male">형제 (<?php echo count($members['M']); ?>명)</div>
                <?php if (count($members['M']) < 2): ?>
                    <div class="no-data">형제가 2명 미만입니다.</div>
                <?php else: ?>
                    <div class="matrix-container">
                        <table class="pair-matrix">
                            <thead>
                                <tr>
                                    <th></th>
                                    <?php foreach ($members['M'] as $m): ?>
                                        <th><?php echo htmlspecialchars($m['mb_name']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members['M'] as $m1): ?>
                                    <tr>
                                        <td class="name"><?php echo htmlspecialchars($m1['mb_name']); ?></td>
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
            </div>

            <!-- 자매 섹션 -->
            <div class="section">
                <div class="section-title female">자매 (<?php echo count($members['W']); ?>명)</div>
                <?php if (count($members['W']) < 2): ?>
                    <div class="no-data">자매가 2명 미만입니다.</div>
                <?php else: ?>
                    <div class="matrix-container">
                        <table class="pair-matrix">
                            <thead>
                                <tr>
                                    <th></th>
                                    <?php foreach ($members['W'] as $m): ?>
                                        <th><?php echo htmlspecialchars($m['mb_name']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members['W'] as $m1): ?>
                                    <tr>
                                        <td class="name"><?php echo htmlspecialchars($m1['mb_name']); ?></td>
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
            </div>

            <!-- 추천 짝 섹션 -->
            <div class="section">
                <div class="section-title" style="background: #f0fdf4; color: #166534;">
                    추천 짝 배정 (<?php echo $group_size; ?>명씩)
                    <button type="button" onclick="location.reload();" style="float: right; font-size: 12px; padding: 4px 8px;">다시 추천</button>
                </div>

                <?php if (!empty($recommended_groups['M']) || !empty($recommended_groups['W'])): ?>
                    <div class="recommend-container">
                        <?php if (!empty($recommended_groups['M'])): ?>
                            <div class="recommend-section">
                                <h4 style="color: #1e40af; margin: 12px 0 8px;">형제</h4>
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
                        <?php endif; ?>

                        <?php if (!empty($recommended_groups['W'])): ?>
                            <div class="recommend-section">
                                <h4 style="color: #be185d; margin: 12px 0 8px;">자매</h4>
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
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">추천할 짝이 없습니다.</div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
