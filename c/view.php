<?php

date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/lib/helpers.php';

// 로그인한 사용자 이름 가져오기
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

$now = new DateTime('now');
$year = (int)(isset($_GET['year']) ? $_GET['year'] : $now->format('Y'));
$month = (int)(isset($_GET['month']) ? $_GET['month'] : $now->format('n'));

list($year, $month) = normalizeYearMonth($year, $month);

$calendarData = loadCalendarData($year, $month);
$weeks = buildCalendarWeeks($year, $month);
$today = new DateTime('now');

?>
<!doctype html>
<html lang="ko">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars((string)$year, ENT_QUOTES); ?>년 <?php echo htmlspecialchars((string)$month, ENT_QUOTES); ?>월 일정</title>
    <style>
      * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      body {
        font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: #f8fafc;
        color: #1e293b;
        line-height: 1.3;
        padding: 4px;
        overflow-x: auto;
      }

      .container {
        max-width: 800px;
        min-width: 340px;
        margin: 0 auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        overflow: visible;
      }

      .header {
        background: #4a6da7;
        color: #fff;
        padding: 12px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
      }

      .header .nav-btn {
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 4px;
        transition: background 0.2s;
        white-space: nowrap;
      }

      .header .nav-btn:hover {
        background: rgba(255, 255, 255, 0.2);
      }

      .header .title {
        color: #fff;
        text-decoration: none;
        font-size: 17px;
        font-weight: 700;
        flex: 1;
        transition: opacity 0.2s;
      }

      .header .title:hover {
        opacity: 0.9;
      }

      .weekdays {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        background: #f1f5f9;
        border-bottom: 1px solid #e2e8f0;
        position: relative;
      }

      .weekday {
        padding: 6px 2px;
        text-align: center;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        border-right: 1px solid #e2e8f0;
      }
      
      .weekday:nth-child(7) {
        border-right: none;
      }

      .calendar {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        position: relative;
      }

      .day {
        min-height: 50px;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        padding: 3px;
        font-size: 12px;
        display: flex;
        flex-direction: column;
        position: relative;
      }

      .day:nth-child(7n) {
        border-right: none;
      }

      .day.outside {
        background: #fafafa;
        color: #cbd5e1;
      }

      .day.outside .day-num {
        color: #d1d5db;
      }

      .day.outside .name {
        color: #e5e7eb;
      }

      .day.today {
        border: 1px solid rgba(220, 38, 38, 0.6) !important;
        background: #fef2f2 !important;
      }

      .day.past {
        background: #f8fafc;
      }

      .day.past .name {
        color: #d1d5db;
      }

      .day-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 2px;
        position: relative;
        z-index: 2;
      }

      .day-num.default {
        color: #1e293b;
      }

      .day-num.saturday {
        color: #2563eb;
      }

      .day-num.sunday {
        color: #dc2626;
      }

      .day-num.today {
        background: linear-gradient(135deg, #ef4444, #f97316);
        color: #fff;
        font-weight: 700;
      }

      .day-num.past {
        background: none;
        color: #d1d5db;
      }

      .note {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0;
        padding: 3px;
        z-index: 0;
        pointer-events: none;
      }

      .names {
        display: flex;
        flex-direction: column;
        gap: 1px;
        position: relative;
        z-index: 1;
      }

      .name {
        font-size: 13px;
        color: #1e293b;
        padding: 0;
        font-weight: 500;
        line-height: 1.3;
        text-align: center;
        height: 14.3px;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .name:empty::before {
        content: '\00a0';
      }

      .name.my-name {
        background: linear-gradient(135deg, #ef4444, #f97316) !important;
        color: #fff !important;
        font-weight: 700;
        border-radius: 3px;
      }

      /* Name font colors */
      .name-bg-white {
        color: #1e293b;
      }

      .name-bg-green {
        color: #16a34a;
      }

      .name-bg-blue {
        color: #2563eb;
      }

      .name-bg-red {
        color: #dc2626;
      }

      /* Monthly Notes */
      .monthly-notes {
        margin-top: 0;
        padding: 6px 8px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
      }

      .notes-list {
        list-style: none;
        margin: 0;
        padding: 0;
      }

      .note-item {
        font-size: 12px;
        line-height: 1.4;
      }

      .note-date {
        font-weight: 600;
        color: #4a6da7;
        margin-right: 3px;
      }

      .note-text {
        color: #334155;
      }

      /* Schedule Table */
      .schedule-table-wrapper {
        margin-top: 16px;
        overflow-x: auto;
      }

      .schedule-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        background: #fff;
        border: 1px solid #e2e8f0;
      }

      .schedule-table th {
        background: #4a6da7;
        color: #fff;
        padding: 8px 4px;
        text-align: center;
        font-weight: 600;
        border: 1px solid #3a5d97;
      }

      .schedule-table td {
        padding: 6px 4px;
        border: 1px solid #e2e8f0;
        vertical-align: top;
        line-height: 1.4;
      }

      .schedule-table .day-label {
        font-weight: 600;
        text-align: center;
        background: #f8fafc;
        white-space: nowrap;
      }

      .schedule-table .schedule-cell {
        font-size: 9px;
        min-height: 30px;
      }

      .schedule-table .color-white {
        color: #1e293b;
      }

      .schedule-table .color-green {
        color: #16a34a;
      }

      .schedule-table .color-blue {
        color: #2563eb;
      }

      .schedule-table .color-red {
        color: #dc2626;
      }

      .schedule-table .today-row {
        background: #fef2f2;
      }

      .schedule-table .today-row .day-label {
        background: #fee2e2;
        color: #dc2626;
      }

      @media (max-width: 480px) {
        body {
          padding: 4px;
        }

        .container {
          border-radius: 8px;
          /* min-width: 340px 유지하여 가로 스크롤 발생 */
        }

        .header {
          padding: 10px 8px;
        }

        .header .title {
          font-size: 15px;
        }

        .header .nav-btn {
          font-size: 13px;
          padding: 3px 6px;
        }

        .day {
          min-height: 45px;
          padding: 2px;
        }
      }
      
    </style>
  </head>
  <body>
    <div class="container">
      <div class="header">
        <?php
          $prevDate = new DateTime(sprintf('%04d-%02d-01', $year, $month));
          $prevDate->modify('-1 month');
          $nextDate = new DateTime(sprintf('%04d-%02d-01', $year, $month));
          $nextDate->modify('+1 month');
        ?>
        <a href="?year=<?php echo $prevDate->format('Y'); ?>&month=<?php echo $prevDate->format('n'); ?>" class="nav-btn">[이전]</a>
        <a href="?year=<?php echo $now->format('Y'); ?>&month=<?php echo $now->format('n'); ?>" class="title"><?php echo htmlspecialchars((string)$year, ENT_QUOTES); ?>년 <?php echo htmlspecialchars((string)$month, ENT_QUOTES); ?>월</a>
        <a href="?year=<?php echo $nextDate->format('Y'); ?>&month=<?php echo $nextDate->format('n'); ?>" class="nav-btn">[다음]</a>
      </div>

      <?php
        $monthNotes = array();
        foreach ($calendarData['dates'] as $dateKey => $entry) {
          $noteText = trim(isset($entry['note']) ? $entry['note'] : '');
          if (!empty($noteText)) {
            $entryDate = new DateTime($dateKey);
            if ((int)$entryDate->format('Y') === $year && (int)$entryDate->format('n') === $month) {
              $monthNotes[$dateKey] = $noteText;
            }
          }
        }
        ksort($monthNotes);
        
        // 파스텔 색상 정의 (투명도 포함)
        $pastelColors = array(
          'rgba(255, 253, 208, 0.5)',  // 연한 노랑
          'rgba(207, 233, 255, 0.5)',  // 연한 파랑
          'rgba(255, 224, 224, 0.5)',  // 연한 빨강
          'rgba(220, 252, 231, 0.5)',  // 연한 그린
          'rgba(243, 232, 255, 0.5)',  // 연한 보라
          'rgba(255, 237, 213, 0.5)',  // 연한 오렌지
          'rgba(252, 231, 243, 0.5)',  // 연한 핑크
          'rgba(225, 245, 254, 0.5)'   // 연한 하늘색
        );
        
        // 메모별로 날짜들을 그룹화하고 색상 할당
        $noteGroups = array();
        $noteColors = array();
        $colorIndex = 0;
        
        foreach ($monthNotes as $dateKey => $noteText) {
          if (!isset($noteGroups[$noteText])) {
            $noteGroups[$noteText] = array();
            $noteColors[$noteText] = $pastelColors[$colorIndex % count($pastelColors)];
            $colorIndex++;
          }
          $noteGroups[$noteText][] = $dateKey;
        }
        
        // 각 메모에 대해 연속된 날짜 구간을 찾아 포맷팅
        $groupedNotes = array();
        $weekdays = array('일', '월', '화', '수', '목', '금', '토');
        
        foreach ($noteGroups as $noteText => $dateKeys) {
          $dateRanges = array();
          $i = 0;
          $count = count($dateKeys);
          
          while ($i < $count) {
            $startDate = new DateTime($dateKeys[$i]);
            $endDate = clone $startDate;
            $j = $i + 1;
            
            // 연속된 날짜 찾기
            while ($j < $count) {
              $nextDate = new DateTime($dateKeys[$j]);
              $dayDiff = ($nextDate->getTimestamp() - $endDate->getTimestamp()) / 86400;
              
              if ($dayDiff == 1) {
                $endDate = clone $nextDate;
                $j++;
              } else {
                break;
              }
            }
            
            // 날짜 범위를 텍스트로 변환
            $startDay = (int)$startDate->format('j');
            $startWeekdayNum = (int)$startDate->format('w');
            $startWeekday = $weekdays[$startWeekdayNum];
            
            if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')) {
              // 단일 날짜
              $dateRanges[] = $startDay . '일(' . $startWeekday . ')';
            } else {
              // 범위
              $endDay = (int)$endDate->format('j');
              $endWeekdayNum = (int)$endDate->format('w');
              $endWeekday = $weekdays[$endWeekdayNum];
              $dateRanges[] = $startDay . '일(' . $startWeekday . ')~' . $endDay . '일(' . $endWeekday . ')';
            }
            
            $i = $j;
          }
          
          // 모든 날짜 범위를 쉼표로 연결
          $groupedNotes[] = array(
            'dateRange' => $month . '월 ' . implode(', ', $dateRanges),
            'note' => $noteText,
            'color' => $noteColors[$noteText]
          );
        }
      ?>

      <?php if (!empty($groupedNotes)): ?>
        <div class="monthly-notes">
          <ul class="notes-list">
            <?php foreach ($groupedNotes as $noteGroup): ?>
              <li class="note-item" style="background: <?php echo htmlspecialchars($noteGroup['color'], ENT_QUOTES); ?>; padding: 8px 12px; border-radius: 6px; margin-bottom: 6px;">
                <span class="note-date"><?php echo htmlspecialchars($noteGroup['dateRange'], ENT_QUOTES); ?> :</span>
                <span class="note-text"><?php echo nl2br(htmlspecialchars($noteGroup['note'], ENT_QUOTES)); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="weekdays">
        <?php foreach (array('일','월','화','수','목','금','토') as $weekday): ?>
          <div class="weekday"><?php echo $weekday; ?></div>
        <?php endforeach; ?>
      </div>

      <div class="calendar">
        <?php foreach ($weeks as $week): ?>
          <?php foreach ($week as $date): ?>
            <?php
              $isCurrentMonth = (int)$date->format('n') === $month;
              $dateKey = $date->format('Y-m-d');
              $assignments = isset($calendarData['dates'][$dateKey]) ? $calendarData['dates'][$dateKey] : array('note' => '', 'names' => array('', '', '', ''));
              $dayClass = getDayClass($date, $today, $isCurrentMonth);
              $numberClass = getDayNumberClass($date, $today, $isCurrentMonth);
              $note = isset($assignments['note']) ? trim($assignments['note']) : '';
              $names = isset($assignments['names']) ? $assignments['names'] : array('', '', '', '');
              $isSaturday = (int)$date->format('w') === 6;
              $colors = getScheduleColorForDay($calendarData['schedule_guide'], $date);
              
              // 메모 배경색 찾기
              $noteBackgroundColor = '';
              if (!empty($note) && isset($noteColors[$note])) {
                $noteBackgroundColor = $noteColors[$note];
              }
            ?>
            <div class="day <?php echo $dayClass; ?>">
              <?php if ($isCurrentMonth && !empty($note)): ?>
                <div class="note" style="<?php echo !empty($noteBackgroundColor) ? 'background: ' . htmlspecialchars($noteBackgroundColor, ENT_QUOTES) . ';' : ''; ?>"><?php echo htmlspecialchars($note, ENT_QUOTES); ?></div>
              <?php endif; ?>
              <div class="day-num <?php echo $numberClass; ?>"><?php echo $date->format('j'); ?></div>
              <?php if ($isCurrentMonth): ?>
                <div class="names">
                  <?php foreach ($names as $i => $name): ?>
                    <?php 
                      $trimmedName = trim($name);
                      $isMyName = !empty($loggedInUserName) && !empty($trimmedName) && $loggedInUserName === $trimmedName;
                      $nameClass = 'name name-bg-' . htmlspecialchars($colors[$i], ENT_QUOTES);
                      if ($isMyName) {
                        $nameClass .= ' my-name';
                      }
                    ?>
                    <div class="<?php echo $nameClass; ?>"><?php echo htmlspecialchars($trimmedName, ENT_QUOTES); ?></div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>

      <div class="schedule-table-wrapper">
        <table class="schedule-table">
          <thead>
            <tr>
              <th></th>
              <th>새벽</th>
              <th>오전</th>
              <th>오후</th>
              <th>저녁</th>
            </tr>
          </thead>
          <tbody>
            <?php 
              $dayLabels = array('월' => 'monday', '화' => 'tuesday', '수' => 'wednesday', 
                            '목' => 'thursday', '금' => 'friday', '토' => 'saturday', '일' => 'sunday');
              $scheduleGuide = $calendarData['schedule_guide'];
              $todayWeekday = (int)$today->format('w');
              $todayDayNames = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
              $todayDayKey = $todayDayNames[$todayWeekday];
            ?>
            <?php foreach ($dayLabels as $dayLabel => $dayKey): ?>
              <?php
                $isToday = $dayKey === $todayDayKey;
                $dawnData = isset($scheduleGuide[$dayKey]['dawn']) ? $scheduleGuide[$dayKey]['dawn'] : array('text' => '', 'color' => 'white');
                $morningData = isset($scheduleGuide[$dayKey]['morning']) ? $scheduleGuide[$dayKey]['morning'] : array('text' => '', 'color' => 'white');
                $afternoonData = isset($scheduleGuide[$dayKey]['afternoon']) ? $scheduleGuide[$dayKey]['afternoon'] : array('text' => '', 'color' => 'white');
                $eveningData = isset($scheduleGuide[$dayKey]['evening']) ? $scheduleGuide[$dayKey]['evening'] : array('text' => '', 'color' => 'white');

                $hasContent = !empty(trim($dawnData['text'])) || !empty(trim($morningData['text'])) || !empty(trim($afternoonData['text'])) || !empty(trim($eveningData['text']));

                if (!$hasContent) continue;
              ?>
              <tr class="<?php echo $isToday ? 'today-row' : ''; ?>">
                <td class="day-label"><?php echo $dayLabel; ?></td>
                <td class="schedule-cell color-<?php echo htmlspecialchars($dawnData['color'], ENT_QUOTES); ?>">
                  <?php echo nl2br(htmlspecialchars($dawnData['text'], ENT_QUOTES)); ?>
                </td>
                <td class="schedule-cell color-<?php echo htmlspecialchars($morningData['color'], ENT_QUOTES); ?>">
                  <?php echo nl2br(htmlspecialchars($morningData['text'], ENT_QUOTES)); ?>
                </td>
                <td class="schedule-cell color-<?php echo htmlspecialchars($afternoonData['color'], ENT_QUOTES); ?>">
                  <?php echo nl2br(htmlspecialchars($afternoonData['text'], ENT_QUOTES)); ?>
                </td>
                <td class="schedule-cell color-<?php echo htmlspecialchars($eveningData['color'], ENT_QUOTES); ?>">
                  <?php echo nl2br(htmlspecialchars($eveningData['text'], ENT_QUOTES)); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      
      <?php if ($is_elder): ?>
      <div style="padding: 12px 8px;">
          <div style="background: #f8f9ff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px;">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                  <span style="font-weight: 600; font-size: 14px; color: #333;">관리자모드</span>
              </div>
              <p style="font-size: 12px; color: #666; margin-bottom: 8px; line-height: 1.4;">
                  봉사인도 일정을 추가, 수정, 삭제할 수 있습니다. 봉사 안내표와 메모를 관리하세요.
              </p>
              <a href="index.php?year=<?php echo $year; ?>&month=<?php echo $month; ?>" style="display: block; text-align: center; padding: 8px 16px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 13px;">관리자모드로 보기</a>
          </div>
      </div>
      <?php endif; ?>
    </div>
  </body>
</html>
