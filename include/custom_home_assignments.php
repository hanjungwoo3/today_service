<?php
/**
 * 홈 화면 하단 - 나의 배정 특권 표시
 * 1. 봉사인도 캘린더 (c/) - 오늘 이후 내 이름이 있는 날짜
 * 2. 평일집회 (s/) - 나에게 배정된 특권
 */

$_homeUserName = get_member_name(mb_id());
if (empty($_homeUserName)) return;

// ── 1. 봉사인도 캘린더 수집 ──
$_calendarAssignments = array();

$_helpersPath = __DIR__ . '/../c/lib/helpers.php';
if (file_exists($_helpersPath)) {
    require_once $_helpersPath;

    $_today = new DateTime();
    $_todayStr = $_today->format('Y-m-d');
    $_timeLabels = array('새벽', '오전', '오후', '저녁');
    $_dayLabels = array('일', '월', '화', '수', '목', '금', '토');

    // 이번 달 + 다음 달 검색
    for ($_offset = 0; $_offset <= 1; $_offset++) {
        $_dt = clone $_today;
        if ($_offset > 0) $_dt->modify("+{$_offset} month");
        $_calYear = (int)$_dt->format('Y');
        $_calMonth = (int)$_dt->format('n');
        $_calData = loadCalendarData($_calYear, $_calMonth);

        foreach ($_calData['dates'] as $_dateStr => $_entry) {
            if ($_dateStr < $_todayStr) continue;

            foreach ($_entry['names'] as $_idx => $_name) {
                if (!empty(trim($_name)) && trim($_name) === $_homeUserName) {
                    $_d = new DateTime($_dateStr);
                    $_calendarAssignments[] = array(
                        'date' => $_dateStr,
                        'label' => $_d->format('Y') . '년 ' . (int)$_d->format('m') . '월 ' . (int)$_d->format('d') . '일 ' . $_dayLabels[(int)$_d->format('w')] . '요일 ' . $_timeLabels[$_idx]
                    );
                }
            }
        }
    }

    // 날짜순 정렬
    usort($_calendarAssignments, function($a, $b) {
        return strcmp($a['date'], $b['date']);
    });
}

// ── 2. 평일집회 배정 수집 ──
$_meetingAssignments = array();

$_apiPath = __DIR__ . '/../s/api.php';
if (file_exists($_apiPath)) {
    require_once $_apiPath;

    $_mgr = new MeetingDataManager();
    $_curDate = new DateTime();
    $_curYear = (int)$_curDate->format('Y');
    $_curWeek = (int)$_curDate->format('W');
    $_dow = (int)$_curDate->format('N');
    $_meetingDay = $_mgr->getMeetingWeekday();

    $_startYear = $_curYear;
    $_startWeek = $_curWeek;
    if ($_dow > $_meetingDay) {
        $_nwd = clone $_curDate;
        $_nwd->modify('+1 week');
        $_startYear = (int)$_nwd->format('o');
        $_startWeek = (int)$_nwd->format('W');
    }

    $_allWeeks = $_mgr->getAvailableWeeks();

    foreach ($_allWeeks as $_wi) {
        if ($_wi['year'] > $_startYear || ($_wi['year'] == $_startYear && $_wi['week'] >= $_startWeek)) {
            $_wd = $_mgr->load($_wi['year'], $_wi['week']);
            if (!$_wd || !empty($_wd['no_meeting'])) continue;

            $_dateRange = '';
            if (!empty($_wd['date'])) {
                $_dateRange = $_wd['date'];
            } else {
                $_jan4 = new DateTime($_wi['year'] . '-01-04');
                $_jan4Day = $_jan4->format('N');
                $_ws = clone $_jan4;
                $_ws->modify('-' . ($_jan4Day - 1) . ' days');
                $_ws->modify('+' . (($_wi['week'] - 1) * 7) . ' days');
                $_we = clone $_ws;
                $_we->modify('+6 days');
                $_dateRange = $_ws->format('n월j일') . '-' . $_we->format('j일');
            }

            $_weekItems = array();

            // 소개말, 시작기도
            if (!empty($_wd['assignments'])) {
                $_openings = array(
                    'opening_remarks' => array('label' => '소개말', 'order' => 0),
                    'opening_prayer' => array('label' => '시작 기도', 'order' => 1)
                );
                foreach ($_openings as $_k => $_info) {
                    if (!empty($_wd['assignments'][$_k]) && trim($_wd['assignments'][$_k]) === $_homeUserName) {
                        $_weekItems[] = array('title' => $_info['label'], 'section' => '', 'sectionType' => '', 'order' => $_info['order']);
                    }
                }
            }

            // 프로그램 항목
            if (!empty($_wd['program'])) {
                $_pi = 0;
                foreach ($_wd['program'] as $_item) {
                    $_found = false;
                    if (is_array($_item['assigned'])) {
                        foreach ($_item['assigned'] as $_an) {
                            if (!empty(trim($_an)) && trim($_an) === $_homeUserName) { $_found = true; break; }
                        }
                    } elseif (!empty($_item['assigned']) && trim($_item['assigned']) === $_homeUserName) {
                        $_found = true;
                    }

                    if ($_found) {
                        $_sn = '';
                        $_st = isset($_item['section']) ? $_item['section'] : '';
                        if ($_st === 'treasures') $_sn = isset($_wd['sections']['treasures']) ? $_wd['sections']['treasures'] : '성경에 담긴 보물';
                        elseif ($_st === 'ministry') $_sn = isset($_wd['sections']['ministry']) ? $_wd['sections']['ministry'] : '야외 봉사에 힘쓰십시오';
                        elseif ($_st === 'living') $_sn = isset($_wd['sections']['living']) ? $_wd['sections']['living'] : '그리스도인 생활';

                        $_weekItems[] = array('title' => $_item['title'], 'section' => $_sn, 'sectionType' => $_st, 'order' => 2 + $_pi);
                    }
                    $_pi++;
                }
            }

            // 맺음말, 마치는기도
            if (!empty($_wd['assignments'])) {
                $_closings = array(
                    'closing_remarks' => array('label' => '맺음말', 'order' => 1000),
                    'closing_prayer' => array('label' => '마치는 기도', 'order' => 1001)
                );
                foreach ($_closings as $_k => $_info) {
                    if (!empty($_wd['assignments'][$_k]) && trim($_wd['assignments'][$_k]) === $_homeUserName) {
                        $_weekItems[] = array('title' => $_info['label'], 'section' => '', 'sectionType' => '', 'order' => $_info['order']);
                    }
                }
            }

            if (!empty($_weekItems)) {
                usort($_weekItems, function($a, $b) { return $a['order'] - $b['order']; });
                // 주차의 실제 집회 날짜 계산 (정렬용)
                $_jan4m = new DateTime($_wi['year'] . '-01-04');
                $_jan4Dm = $_jan4m->format('N');
                $_wsm = clone $_jan4m;
                $_wsm->modify('-' . ($_jan4Dm - 1) . ' days');
                $_wsm->modify('+' . (($_wi['week'] - 1) * 7) . ' days');
                $_wsm->modify('+' . ($_meetingDay - 1) . ' days');
                $_meetingAssignments[] = array(
                    'sortDate' => $_wsm->format('Y-m-d'),
                    'dateRange' => $_dateRange,
                    'year' => $_wi['year'],
                    'week' => $_wi['week'],
                    'items' => $_weekItems
                );
            }
        }
    }
}

// ── 3. 공개 강연 배정 수집 ──
$_talkAssignments = array();

$_talkApiPath = __DIR__ . '/../s/talk_api.php';
if (file_exists($_talkApiPath)) {
    require_once $_talkApiPath;

    $_talkMgr = new TalkDataManager();
    $_talkData = $_talkMgr->load();
    $_roleLabels = array('speaker' => '연사', 'chairman' => '사회', 'reader' => '낭독', 'prayer' => '기도');

    if (!empty($_talkData['talks'])) {
        foreach ($_talkData['talks'] as $_talk) {
            if (empty($_talk['date']) || $_talk['date'] < $_todayStr) continue;

            $_myRoles = array();
            foreach ($_roleLabels as $_key => $_label) {
                if (!empty($_talk[$_key]) && trim($_talk[$_key]) === $_homeUserName) {
                    $_myRoles[] = $_label;
                }
            }

            if (!empty($_myRoles)) {
                $_td = new DateTime($_talk['date']);
                $_talkAssignments[] = array(
                    'date' => $_talk['date'],
                    'roles' => implode(', ', $_myRoles),
                    'label' => $_td->format('Y') . '년 ' . (int)$_td->format('m') . '월 ' . (int)$_td->format('d') . '일 ' . $_dayLabels[(int)$_td->format('w')] . '요일'
                );
            }
        }
    }
}

// ── 4. 청중마이크/안내인/연사음료 배정 수집 ──
$_dutyAssignments = array();

$_dutyApiPath = __DIR__ . '/../s/duty_api.php';
if (file_exists($_dutyApiPath)) {
    require_once $_dutyApiPath;

    $_dutyMgr = new DutyDataManager();
    $_curYear = (int)$_today->format('Y');
    $_curMonth = (int)$_today->format('n');
    $_curDay = (int)$_today->format('j');

    $_dutyRoleLabels = array(
        'mic1' => '마이크', 'mic2' => '마이크', 'mic_assist' => '마이크 보조',
        'att_hall1' => '청중석 안내', 'att_hall2' => '청중석 안내', 'att_entrance' => '출입구 안내',
        'drink_main' => '연사음료 담당자', 'drink_assist' => '연사음료 보조'
    );

    // 이번 달 + 다음 달 검색
    for ($_dOffset = 0; $_dOffset <= 1; $_dOffset++) {
        $_dDt = clone $_today;
        if ($_dOffset > 0) $_dDt->modify('+1 month');
        $_dYear = (int)$_dDt->format('Y');
        $_dMonth = (int)$_dDt->format('n');

        $_dutyData = $_dutyMgr->load($_dYear);
        $_monthData = isset($_dutyData['months'][(string)$_dMonth]) ? $_dutyData['months'][(string)$_dMonth] : null;
        if (!$_monthData) continue;

        $_lastDay = (int)date('t', mktime(0, 0, 0, $_dMonth, 1, $_dYear));

        // 상반기 (1일-15일)
        $_fhEnd = sprintf('%04d-%02d-15', $_dYear, $_dMonth);
        if ($_fhEnd >= $_todayStr) {
            $_fh = isset($_monthData['first_half']) ? $_monthData['first_half'] : array();
            $_myDutyRoles = array();
            foreach (array('mic1','mic2','mic_assist','att_hall1','att_hall2','att_entrance') as $_dk) {
                if (!empty($_fh[$_dk]) && trim($_fh[$_dk]) === $_homeUserName) {
                    $_myDutyRoles[] = $_dutyRoleLabels[$_dk];
                }
            }
            $_myDutyRoles = array_unique($_myDutyRoles);
            if (!empty($_myDutyRoles)) {
                $_fhActive = ($_dYear == $_curYear && $_dMonth == $_curMonth && $_curDay <= 15);
                $_dutyAssignments[] = array(
                    'sortDate' => sprintf('%04d-%02d-01', $_dYear, $_dMonth),
                    'roles' => implode(', ', $_myDutyRoles),
                    'label' => $_dMonth . '월 1일 - 15일',
                    'active' => $_fhActive
                );
            }
        }

        // 하반기 (16일-말일)
        $_shEnd = sprintf('%04d-%02d-%02d', $_dYear, $_dMonth, $_lastDay);
        if ($_shEnd >= $_todayStr) {
            $_sh = isset($_monthData['second_half']) ? $_monthData['second_half'] : array();
            $_myDutyRoles = array();
            foreach (array('mic1','mic2','mic_assist','att_hall1','att_hall2','att_entrance') as $_dk) {
                if (!empty($_sh[$_dk]) && trim($_sh[$_dk]) === $_homeUserName) {
                    $_myDutyRoles[] = $_dutyRoleLabels[$_dk];
                }
            }
            $_myDutyRoles = array_unique($_myDutyRoles);
            if (!empty($_myDutyRoles)) {
                $_shActive = ($_dYear == $_curYear && $_dMonth == $_curMonth && $_curDay > 15);
                $_dutyAssignments[] = array(
                    'sortDate' => sprintf('%04d-%02d-16', $_dYear, $_dMonth),
                    'roles' => implode(', ', $_myDutyRoles),
                    'label' => $_dMonth . '월 16일 - ' . $_lastDay . '일',
                    'active' => $_shActive
                );
            }
        }

        // 연사음료 (월 단위)
        $_mEnd = sprintf('%04d-%02d-%02d', $_dYear, $_dMonth, $_lastDay);
        if ($_mEnd >= $_todayStr) {
            $_myDrinkRoles = array();
            foreach (array('drink_main','drink_assist') as $_dk) {
                if (!empty($_monthData[$_dk]) && trim($_monthData[$_dk]) === $_homeUserName) {
                    $_myDrinkRoles[] = $_dutyRoleLabels[$_dk];
                }
            }
            if (!empty($_myDrinkRoles)) {
                $_drinkActive = ($_dYear == $_curYear && $_dMonth == $_curMonth);
                $_dutyAssignments[] = array(
                    'sortDate' => sprintf('%04d-%02d-01', $_dYear, $_dMonth),
                    'roles' => implode(', ', $_myDrinkRoles),
                    'label' => $_dMonth . '월',
                    'active' => $_drinkActive
                );
            }
        }
    }
}

// ── 5. 이번 달/다음 달 청소집단 확인 (내 집단일 때만) ──
$_cleaningLines = array();
if (isset($_dutyMgr)) {
    $_myGid = get_member_group(mb_id());
    $_myGroupName = $_myGid ? get_group_name($_myGid) : '';

    if (!empty($_myGroupName)) {
        $_curM = (int)$_today->format('n');
        $_curY = (int)$_today->format('Y');
        $_dutyDataCur = $_dutyMgr->load($_curY);

        $_curCG = isset($_dutyDataCur['months'][(string)$_curM]['cleaning_group']) ? $_dutyDataCur['months'][(string)$_curM]['cleaning_group'] : '';
        if (!empty($_curCG) && $_curCG === $_myGroupName) {
            $_cleaningLines[] = '이번달(' . $_curM . '월)은 <strong style="color:#d32f2f;">' . htmlspecialchars($_curCG) . '집단</strong>이 회관청소';
        }

        $_nextDt = clone $_today;
        $_nextDt->modify('+1 month');
        $_nextM = (int)$_nextDt->format('n');
        $_nextY = (int)$_nextDt->format('Y');
        $_dutyDataNext = ($_nextY === $_curY) ? $_dutyDataCur : $_dutyMgr->load($_nextY);

        $_nextCG = isset($_dutyDataNext['months'][(string)$_nextM]['cleaning_group']) ? $_dutyDataNext['months'][(string)$_nextM]['cleaning_group'] : '';
        if (!empty($_nextCG) && $_nextCG === $_myGroupName) {
            $_cleaningLines[] = '다음달(' . $_nextM . '월)은 <strong style="color:#d32f2f;">' . htmlspecialchars($_nextCG) . '집단</strong>이 회관청소';
        }
    }
}

// ── 통합 정렬 ──
$_allItems = array();

foreach ($_calendarAssignments as $_ca) {
    $_allItems[] = array('type' => 'calendar', 'sortDate' => $_ca['date'], 'data' => $_ca);
}
foreach ($_meetingAssignments as $_ma) {
    $_allItems[] = array('type' => 'meeting', 'sortDate' => $_ma['sortDate'], 'data' => $_ma);
}
foreach ($_talkAssignments as $_ta) {
    $_allItems[] = array('type' => 'talk', 'sortDate' => $_ta['date'], 'data' => $_ta);
}
foreach ($_dutyAssignments as $_da) {
    $_allItems[] = array('type' => 'duty', 'sortDate' => $_da['sortDate'], 'data' => $_da);
}

if (empty($_allItems)) return;

usort($_allItems, function($a, $b) {
    return strcmp($a['sortDate'], $b['sortDate']);
});
?>

<style>
.home-assignments-section {
    margin: 16px 12px 8px;
    padding: 0;
    background: white;
    border-radius: 8px;
}
.home-assignments-title {
    font-size: 17px;
    font-weight: 700;
    color: #666;
    margin-bottom: 4px;
    padding: 5px 6px;
}
.home-assignment-item {
    display: block;
    padding: 5px 6px;
    margin-bottom: 4px;
    background: #f9f9f9;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-size: 15px;
    line-height: 1.5;
    text-decoration: none;
    color: inherit;
    transition: background 0.2s;
    position: relative;
}
.home-assignment-dday {
    position: absolute;
    top: 4px;
    right: 6px;
    font-size: 13px;
    color: #999;
    font-weight: 600;
}
.home-assignment-dday.is-today {
    color: #e53935;
}
.home-assignment-item:hover {
    background: #efefef;
    text-decoration: none;
    color: inherit;
}
.home-assignment-item.today {
    background: #fff3f3;
}
.home-assignment-date {
    font-weight: 600;
    color: #666;
    display: inline;
}
.home-assignment-content {
    display: block;
}
.home-assignment-line {
    margin-bottom: 4px;
}
.home-assignment-line:last-child {
    margin-bottom: 0;
}
.home-assignment-section {
    color: #999;
    font-size: 14px;
    margin-bottom: 2px;
}
.home-assignment-line:first-child .home-assignment-section {
    display: inline;
}
.home-assignment-line:first-child .home-assignment-title {
    display: block;
}
.home-assignment-title {
    color: #333;
    font-size: 14px;
    word-break: keep-all;
    overflow-wrap: break-word;
    line-height: 1.4;
}
.home-assignment-title.type-treasures,
.home-assignment-section.type-treasures { color: #00796B; font-weight: 700; }
.home-assignment-title.type-ministry,
.home-assignment-section.type-ministry { color: #A86500; font-weight: 700; }
.home-assignment-title.type-living,
.home-assignment-section.type-living { color: #8E201D; font-weight: 700; }
.home-assignment-title.type-calendar { color: #1565C0; font-weight: 700; }
.home-assignment-section.type-calendar { color: #1565C0; font-weight: 700; }
.home-assignment-title.type-talk { color: #6A1B9A; font-weight: 700; }
.home-assignment-section.type-talk { color: #6A1B9A; font-weight: 700; }
.home-assignment-title.type-duty { color: #00838F; font-weight: 700; }
.home-assignment-section.type-duty { color: #00838F; font-weight: 700; }
.home-assignment-item.duty-active { background: #fff3f3; border-color: #f5c6c6; }
</style>

<div class="home-assignments-section">
    <div class="home-assignments-title">📋 오늘 이후 나에게 배정된 특권<?php if (!empty($_cleaningLines)): ?> <span style="font-size:11px; font-weight:500; color:#333;"><?php echo implode(' / ', $_cleaningLines); ?></span><?php endif; ?></div>

    <?php foreach ($_allItems as $_item): ?>
        <?php
            $_isToday = ($_item['sortDate'] === $_todayStr);
            $_diffDays = (int)(new DateTime($_todayStr))->diff(new DateTime($_item['sortDate']))->days;
            $_ddayText = $_isToday ? '오늘' : $_diffDays . '일 후';
        ?>
        <?php if ($_item['type'] === 'calendar'): ?>
        <?php $_cd = new DateTime($_item['data']['date']); ?>
        <a href="<?=BASE_PATH?>/pages/service_guide_calendar.php?year=<?=(int)$_cd->format('Y')?>&month=<?=(int)$_cd->format('n')?>" class="home-assignment-item<?= $_isToday ? ' today' : '' ?>">
            <span class="home-assignment-dday<?= $_isToday ? ' is-today' : '' ?>"><?= $_ddayText ?></span>
            <div class="home-assignment-content">
                <div class="home-assignment-line">
                    <div class="home-assignment-section type-calendar">봉사인도</div>
                    <div class="home-assignment-title type-calendar"><?=htmlspecialchars($_item['data']['label'])?></div>
                </div>
            </div>
        </a>
        <?php elseif ($_item['type'] === 'talk'): ?>
        <a href="<?=BASE_PATH?>/pages/public_talk.php" class="home-assignment-item<?= $_isToday ? ' today' : '' ?>">
            <span class="home-assignment-dday<?= $_isToday ? ' is-today' : '' ?>"><?= $_ddayText ?></span>
            <div class="home-assignment-content">
                <div class="home-assignment-line">
                    <div class="home-assignment-section type-talk">공개 강연 (<?=htmlspecialchars($_item['data']['roles'])?>)</div>
                    <div class="home-assignment-title type-talk"><?=htmlspecialchars($_item['data']['label'])?></div>
                </div>
            </div>
        </a>
        <?php elseif ($_item['type'] === 'duty'): ?>
        <?php
            $_dutyActive = !empty($_item['data']['active']);
            $_dutyStartDate = new DateTime($_item['sortDate']);
            $_dutyDiffDays = (int)(new DateTime($_todayStr))->diff($_dutyStartDate)->days;
            $_dutyDdayText = $_dutyActive ? '수행중' : $_dutyDiffDays . '일 후부터';
        ?>
        <a href="<?=BASE_PATH?>/pages/duty_schedule.php" class="home-assignment-item<?= $_dutyActive ? ' duty-active' : '' ?>">
            <span class="home-assignment-dday<?= $_dutyActive ? ' is-today' : '' ?>"><?= $_dutyDdayText ?></span>
            <div class="home-assignment-content">
                <div class="home-assignment-line">
                    <div class="home-assignment-section type-duty"><?=htmlspecialchars($_item['data']['roles'])?></div>
                    <div class="home-assignment-title type-duty"><?=htmlspecialchars($_item['data']['label'])?></div>
                </div>
            </div>
        </a>
        <?php else: ?>
        <?php $_ma = $_item['data']; ?>
        <a href="<?=BASE_PATH?>/pages/meeting_program.php?year=<?=$_ma['year']?>&week=<?=$_ma['week']?>" class="home-assignment-item<?= $_isToday ? ' today' : '' ?>">
            <span class="home-assignment-dday<?= $_isToday ? ' is-today' : '' ?>"><?= $_ddayText ?></span>
            <div class="home-assignment-content">
                <?php $_first = true; ?>
                <?php foreach ($_ma['items'] as $_mi): ?>
                <div class="home-assignment-line">
                    <?php if ($_first && !empty($_mi['section'])): ?>
                        <span class="home-assignment-date"><?=htmlspecialchars($_ma['dateRange'])?> </span>
                        <?php $_sc = 'home-assignment-section'; if (!empty($_mi['sectionType'])) $_sc .= ' type-'.$_mi['sectionType']; ?>
                        <div class="<?=$_sc?>"><?=htmlspecialchars($_mi['section'])?></div>
                        <?php $_tc = 'home-assignment-title'; if (!empty($_mi['sectionType'])) $_tc .= ' type-'.$_mi['sectionType']; ?>
                        <div class="<?=$_tc?>"><?=htmlspecialchars($_mi['title'])?></div>
                        <?php $_first = false; ?>
                    <?php elseif ($_first && empty($_mi['section'])): ?>
                        <div class="home-assignment-date" style="display:block; margin-bottom:2px;"><?=htmlspecialchars($_ma['dateRange'])?></div>
                        <div class="home-assignment-section"><?=htmlspecialchars($_mi['title'])?></div>
                        <?php $_first = false; ?>
                    <?php else: ?>
                        <?php $_tc = 'home-assignment-title'; $_sc = 'home-assignment-section'; if (!empty($_mi['sectionType'])) { $_tc .= ' type-'.$_mi['sectionType']; $_sc .= ' type-'.$_mi['sectionType']; } ?>
                        <?php if (!empty($_mi['section'])): ?>
                            <div class="<?=$_sc?>"><?=htmlspecialchars($_mi['section'])?></div>
                            <div class="<?=$_tc?>"><?=htmlspecialchars($_mi['title'])?></div>
                        <?php else: ?>
                            <div class="<?=$_sc?>"><?=htmlspecialchars($_mi['title'])?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </a>
        <?php endif; ?>
    <?php endforeach; ?>

</div>
