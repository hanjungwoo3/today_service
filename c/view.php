<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/lib/helpers.php';

$now = new DateTimeImmutable('now');
$year = (int)($_GET['year'] ?? $now->format('Y'));
$month = (int)($_GET['month'] ?? $now->format('n'));

[$year, $month] = normalizeYearMonth($year, $month);

$calendarData = loadCalendarData($year, $month);
$weeks = buildCalendarWeeks($year, $month);
$today = new DateTimeImmutable('now');

?>
<!doctype html>
<html lang="ko">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars((string)$year, ENT_QUOTES) ?>년 <?= htmlspecialchars((string)$month, ENT_QUOTES) ?>월 일정</title>
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
      }

      .container {
        max-width: 340px;
        margin: 0 auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        overflow: visible;
        padding-right: 35px;
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
        font-size: 12px;
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
        font-size: 15px;
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
      }

      .weekday {
        padding: 6px 2px;
        text-align: center;
        font-size: 10px;
        font-weight: 600;
        color: #64748b;
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
        font-size: 10px;
        display: flex;
        flex-direction: column;
        position: relative;
      }

      .day:nth-child(7n) {
        border-right: none;
      }

      .time-labels {
        position: absolute;
        top: 24px;
        right: -28px;
        display: flex;
        flex-direction: column;
        gap: 1px;
        z-index: 10;
      }

      .time-label {
        font-size: 11px;
        font-weight: 400;
        color: #d1d5db;
        padding: 0;
        line-height: 1.3;
        height: 14.3px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
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
        border: 3px solid rgba(220, 38, 38, 0.6) !important;
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
        font-size: 11px;
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
        background: rgba(74, 109, 167, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 600;
        color: rgba(74, 109, 167, 0.35);
        padding: 3px;
        word-break: break-word;
        text-align: center;
        z-index: 0;
        pointer-events: none;
      }

      .note:empty {
        display: none;
      }

      .names {
        display: flex;
        flex-direction: column;
        gap: 1px;
        position: relative;
        z-index: 1;
      }

      .name {
        font-size: 11px;
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

      /* Name font colors */
      .name-bg-white {
        color: #1e293b;
      }

      .name-bg-green {
        color: #16a34a;
        font-weight: 600;
      }

      .name-bg-blue {
        color: #2563eb;
        font-weight: 600;
      }

      .name-bg-red {
        color: #dc2626;
        font-weight: 600;
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
        padding: 3px 0;
        font-size: 10px;
        line-height: 1.4;
      }

      .note-item:last-child {
        padding-bottom: 0;
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
        font-size: 10px;
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
        font-weight: 600;
      }

      .schedule-table .color-blue {
        color: #2563eb;
        font-weight: 600;
      }

      .schedule-table .color-red {
        color: #dc2626;
        font-weight: 600;
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
          max-width: 100%;
        }

        .header {
          padding: 10px 8px;
        }

        .header .title {
          font-size: 13px;
        }

        .header .nav-btn {
          font-size: 11px;
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
          $prevDate = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->modify('-1 month');
          $nextDate = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->modify('+1 month');
        ?>
        <a href="?year=<?= $prevDate->format('Y') ?>&month=<?= $prevDate->format('n') ?>" class="nav-btn">[이전]</a>
        <a href="?year=<?= $now->format('Y') ?>&month=<?= $now->format('n') ?>" class="title"><?= htmlspecialchars((string)$year, ENT_QUOTES) ?>년 <?= htmlspecialchars((string)$month, ENT_QUOTES) ?>월 봉사 일정</a>
        <a href="?year=<?= $nextDate->format('Y') ?>&month=<?= $nextDate->format('n') ?>" class="nav-btn">[다음]</a>
      </div>

      <?php
        // 이번 달 일정 메모 수집
        $monthNotes = [];
        foreach ($calendarData['dates'] as $dateKey => $entry) {
          $noteText = trim($entry['note'] ?? '');
          if (!empty($noteText)) {
            // 날짜가 현재 월에 속하는지 확인
            $entryDate = new DateTimeImmutable($dateKey);
            if ((int)$entryDate->format('Y') === $year && (int)$entryDate->format('n') === $month) {
              $monthNotes[$dateKey] = $noteText;
            }
          }
        }
        ksort($monthNotes); // 날짜순 정렬
      ?>

      <?php if (!empty($monthNotes)): ?>
        <div class="monthly-notes">
          <ul class="notes-list">
            <?php foreach ($monthNotes as $dateKey => $noteText): ?>
              <?php
                $noteDate = new DateTimeImmutable($dateKey);
                $day = (int)$noteDate->format('j');
                $weekdayNum = (int)$noteDate->format('w');
                $weekdays = ['일', '월', '화', '수', '목', '금', '토'];
                $weekday = $weekdays[$weekdayNum];
              ?>
              <li class="note-item">
                <span class="note-date"><?= $month ?>월 <?= $day ?>일(<?= $weekday ?>) :</span>
                <span class="note-text"><?= nl2br(htmlspecialchars($noteText, ENT_QUOTES)) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="weekdays">
        <?php foreach (['일','월','화','수','목','금','토'] as $weekday): ?>
          <div class="weekday"><?= $weekday ?></div>
        <?php endforeach; ?>
      </div>

      <div class="calendar">
        <?php foreach ($weeks as $week): ?>
          <?php foreach ($week as $date): ?>
            <?php
              $isCurrentMonth = (int)$date->format('n') === $month;
              $dateKey = $date->format('Y-m-d');
              $assignments = $calendarData['dates'][$dateKey] ?? ['note' => '', 'names' => ['', '', '']];
              $dayClass = getDayClass($date, $today, $isCurrentMonth);
              $numberClass = getDayNumberClass($date, $today, $isCurrentMonth);
              $note = $assignments['note'] ?? '';
              $names = $assignments['names'] ?? ['', '', ''];
              $isSaturday = (int)$date->format('w') === 6;
              $colors = getScheduleColorForDay($calendarData['schedule_guide'], $date);
            ?>
            <div class="day <?= $dayClass ?>">
              <?php if ($isCurrentMonth && !empty(trim($note))): ?>
                <div class="note"><?= htmlspecialchars(trim($note), ENT_QUOTES) ?></div>
              <?php endif; ?>
              <div class="day-num <?= $numberClass ?>"><?= $date->format('j') ?></div>
              <?php if ($isCurrentMonth): ?>
                <div class="names">
                  <?php foreach ($names as $i => $name): ?>
                    <div class="name name-bg-<?= htmlspecialchars($colors[$i], ENT_QUOTES) ?>"><?= htmlspecialchars(trim($name), ENT_QUOTES) ?></div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <?php if ($isSaturday): ?>
                <div class="time-labels">
                  <div class="time-label">오전</div>
                  <div class="time-label">오후</div>
                  <div class="time-label">저녁</div>
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
              <th>요일</th>
              <th>오전</th>
              <th>오후</th>
              <th>저녁</th>
            </tr>
          </thead>
          <tbody>
            <?php 
              $dayLabels = ['월요일' => 'monday', '화요일' => 'tuesday', '수요일' => 'wednesday', 
                            '목요일' => 'thursday', '금요일' => 'friday', '토요일' => 'saturday', '일요일' => 'sunday'];
              $scheduleGuide = $calendarData['schedule_guide'];
              $todayWeekday = (int)$today->format('w');
              $todayDayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
              $todayDayKey = $todayDayNames[$todayWeekday];
            ?>
            <?php foreach ($dayLabels as $dayLabel => $dayKey): ?>
              <?php 
                $isToday = $dayKey === $todayDayKey;
                $morningData = $scheduleGuide[$dayKey]['morning'] ?? ['text' => '', 'color' => 'white'];
                $afternoonData = $scheduleGuide[$dayKey]['afternoon'] ?? ['text' => '', 'color' => 'white'];
                $eveningData = $scheduleGuide[$dayKey]['evening'] ?? ['text' => '', 'color' => 'white'];
                
                // 모든 시간대가 비어있는지 확인
                $hasContent = !empty(trim($morningData['text'])) || !empty(trim($afternoonData['text'])) || !empty(trim($eveningData['text']));
                
                // 내용이 있는 요일만 표시
                if (!$hasContent) continue;
              ?>
              <tr class="<?= $isToday ? 'today-row' : '' ?>">
                <td class="day-label"><?= $dayLabel ?></td>
                <td class="schedule-cell color-<?= htmlspecialchars($morningData['color'], ENT_QUOTES) ?>">
                  <?= nl2br(htmlspecialchars($morningData['text'], ENT_QUOTES)) ?>
                </td>
                <td class="schedule-cell color-<?= htmlspecialchars($afternoonData['color'], ENT_QUOTES) ?>">
                  <?= nl2br(htmlspecialchars($afternoonData['text'], ENT_QUOTES)) ?>
                </td>
                <td class="schedule-cell color-<?= htmlspecialchars($eveningData['color'], ENT_QUOTES) ?>">
                  <?= nl2br(htmlspecialchars($eveningData['text'], ENT_QUOTES)) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </body>
</html>

