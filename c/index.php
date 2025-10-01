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

$status = $_GET['status'] ?? null;
$message = match ($status) {
    'saved' => '저장되었습니다.',
    'error' => '문제가 발생했습니다. 다시 시도해주세요.',
    default => null,
};

?>
<!doctype html>
<html lang="ko">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>월간 일정 관리</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script defer src="assets/js/app.js"></script>
  </head>
  <body>
    <div class="app-shell" data-year="<?= htmlspecialchars((string)$year, ENT_QUOTES) ?>" data-month="<?= htmlspecialchars((string)$month, ENT_QUOTES) ?>">
      <header class="toolbar">
        <button type="button" id="prevMonth">이전 달</button>
        <h1 id="jumpToday" style="cursor: pointer;"><?= htmlspecialchars((string)$year, ENT_QUOTES) ?>년 <?= htmlspecialchars((string)$month, ENT_QUOTES) ?>월 봉사 일정</h1>
        <button type="button" id="nextMonth">다음 달</button>
      </header>

      <?php if ($message): ?>
        <div class="status-bar <?= $status === 'saved' ? 'success' : 'error' ?>">
          <?= htmlspecialchars((string)$message, ENT_QUOTES) ?>
        </div>
      <?php endif; ?>

      <form id="calendarForm">
        <input type="hidden" name="year" value="<?= htmlspecialchars((string)$year, ENT_QUOTES) ?>" />
        <input type="hidden" name="month" value="<?= htmlspecialchars((string)$month, ENT_QUOTES) ?>" />

        <div class="calendar-grid">
          <?php foreach (['일','월','화','수','목','금','토'] as $weekday): ?>
            <div class="weekday-header"><?= $weekday ?></div>
          <?php endforeach; ?>

          <?php foreach ($weeks as $week): ?>
            <?php foreach ($week as $date): ?>
              <?php
                $isCurrentMonth = (int)$date->format('n') === $month;
                $dateKey = $date->format('Y-m-d');
                $assignments = $calendarData['dates'][$dateKey] ?? ['note' => '', 'names' => ['', '', '']];
                $dayClass = getDayClass($date, $today, $isCurrentMonth);
                $numberClass = getDayNumberClass($date, $today, $isCurrentMonth);
                $colors = getScheduleColorForDay($calendarData['schedule_guide'], $date);
              ?>
              <div
                class="day-cell <?= $dayClass ?>"
                data-date="<?= $dateKey ?>"
              >
                <div class="day-header">
                  <span class="day-number <?= $numberClass ?>"><?= $date->format('j') ?></span>
                  <?php if (!$isCurrentMonth): ?>
                    <span class="outside">&nbsp;</span>
                  <?php endif; ?>
                </div>
                <?php if ($isCurrentMonth): ?>
                  <input
                    type="text"
                    name="entries[<?= $dateKey ?>][note]"
                    value="<?= htmlspecialchars((string)($assignments['note'] ?? ''), ENT_QUOTES) ?>"
                    placeholder="일정 메모"
                    class="assignment-note"
                  />
                  <div class="assignments">
                    <?php for ($i = 0; $i < 3; $i += 1): ?>
                      <input
                        type="text"
                        name="entries[<?= $dateKey ?>][names][<?= $i ?>]"
                        value="<?= htmlspecialchars((string)($assignments['names'][$i] ?? ''), ENT_QUOTES) ?>"
                        placeholder="이름 <?= $i + 1 ?>"
                        class="assignment-input bg-<?= htmlspecialchars($colors[$i], ENT_QUOTES) ?>"
                      />
                    <?php endfor; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>

        <div class="schedule-guide-section">
          <h2 class="guide-title">요일별 시간대 안내</h2>
          <div class="guide-grid">
            <?php 
              $dayLabels = ['월요일' => 'monday', '화요일' => 'tuesday', '수요일' => 'wednesday', 
                            '목요일' => 'thursday', '금요일' => 'friday', '토요일' => 'saturday', '일요일' => 'sunday'];
              $timeLabels = ['오전' => 'morning', '오후' => 'afternoon', '저녁' => 'evening'];
              $scheduleGuide = $calendarData['schedule_guide'];
            ?>
            <?php foreach ($dayLabels as $dayLabel => $dayKey): ?>
              <div class="guide-day">
                <h3 class="guide-day-title"><?= $dayLabel ?></h3>
                <?php foreach ($timeLabels as $timeLabel => $timeKey): ?>
                  <?php 
                    $guideData = $scheduleGuide[$dayKey][$timeKey] ?? ['text' => '', 'color' => 'white'];
                  ?>
                  <div class="guide-time-row">
                    <label class="guide-time-label"><?= $timeLabel ?></label>
                    <textarea
                      name="schedule_guide[<?= $dayKey ?>][<?= $timeKey ?>][text]"
                      placeholder="안내 메시지"
                      class="guide-textarea"
                      rows="4"
                    ><?= htmlspecialchars((string)($guideData['text'] ?? ''), ENT_QUOTES) ?></textarea>
                    <input
                      type="hidden"
                      name="schedule_guide[<?= $dayKey ?>][<?= $timeKey ?>][color]"
                      value="<?= htmlspecialchars((string)($guideData['color'] ?? 'white'), ENT_QUOTES) ?>"
                      class="color-value"
                    />
                    <div class="color-select-wrapper">
                      <button type="button" class="color-select-trigger">
                        <span class="selected-color" data-color="<?= htmlspecialchars((string)($guideData['color'] ?? 'white'), ENT_QUOTES) ?>" style="background: <?= match($guideData['color'] ?? 'white') {
                          'green' => '#86efac',
                          'blue' => '#93c5fd',
                          'red' => '#fca5a5',
                          default => '#f9fbff'
                        } ?>; <?= ($guideData['color'] ?? 'white') === 'white' ? 'border: 1px solid #cbd5e1;' : '' ?>"></span>
                      </button>
                      <div class="color-dropdown">
                        <button type="button" class="color-option" data-color="white" style="background: #f9fbff; border: 1px solid #cbd5e1;"></button>
                        <button type="button" class="color-option" data-color="green" style="background: #86efac;"></button>
                        <button type="button" class="color-option" data-color="blue" style="background: #93c5fd;"></button>
                        <button type="button" class="color-option" data-color="red" style="background: #fca5a5;"></button>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="footer-actions">
          <button type="button" id="loadPrevMonth" class="load-prev-btn">이전달 값 불러오기</button>
          <button type="button" id="updateHolidays" class="update-holidays-btn">공휴일 업데이트</button>
          <button type="submit" id="saveBtn">저장하기</button>
        </div>
      </form>
    </div>
  </body>
</html>


