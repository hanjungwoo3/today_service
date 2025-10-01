<?php
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/lib/helpers.php';

// 관리자 권한 체크 (선택적)
$is_admin = false;
if (file_exists(dirname(__FILE__) . '/../config.php')) {
    require_once dirname(__FILE__) . '/../config.php';
    if (function_exists('mb_id') && function_exists('is_admin')) {
        $is_admin = is_admin(mb_id());
    }
}

// 관리자가 아니면 view.php로 리다이렉트
if (!$is_admin) {
    header('Location: view.php' . (isset($_GET['year']) && isset($_GET['month']) ? '?year='.$_GET['year'].'&month='.$_GET['month'] : ''));
    exit;
}

$now = new DateTime('now');
$year = (int)(isset($_GET['year']) ? $_GET['year'] : $now->format('Y'));
$month = (int)(isset($_GET['month']) ? $_GET['month'] : $now->format('n'));

list($year, $month) = normalizeYearMonth($year, $month);

$calendarData = loadCalendarData($year, $month);
$weeks = buildCalendarWeeks($year, $month);
$today = new DateTime('now');

$status = isset($_GET['status']) ? $_GET['status'] : null;
$message = null;
if ($status === 'saved') {
    $message = '저장되었습니다.';
} elseif ($status === 'error') {
    $message = '문제가 발생했습니다. 다시 시도해주세요.';
}

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
    <div class="app-shell" data-year="<?php echo htmlspecialchars((string)$year, ENT_QUOTES); ?>" data-month="<?php echo htmlspecialchars((string)$month, ENT_QUOTES); ?>">
      <header class="toolbar">
        <button type="button" id="prevMonth">이전 달</button>
        <h1 id="jumpToday" style="cursor: pointer;"><?php echo htmlspecialchars((string)$year, ENT_QUOTES); ?>년 <?php echo htmlspecialchars((string)$month, ENT_QUOTES); ?>월 봉사 일정</h1>
        <button type="button" id="nextMonth">다음 달</button>
      </header>

      <?php if ($message): ?>
        <div class="status-bar <?php echo $status === 'saved' ? 'success' : 'error'; ?>">
          <?php echo htmlspecialchars((string)$message, ENT_QUOTES); ?>
        </div>
      <?php endif; ?>

      <form id="calendarForm">
        <input type="hidden" name="year" value="<?php echo htmlspecialchars((string)$year, ENT_QUOTES); ?>" />
        <input type="hidden" name="month" value="<?php echo htmlspecialchars((string)$month, ENT_QUOTES); ?>" />

        <div class="calendar-grid">
          <?php foreach (array('일','월','화','수','목','금','토') as $weekday): ?>
            <div class="weekday-header"><?php echo $weekday; ?></div>
          <?php endforeach; ?>

          <?php foreach ($weeks as $week): ?>
            <?php foreach ($week as $date): ?>
              <?php
                $isCurrentMonth = (int)$date->format('n') === $month;
                $dateKey = $date->format('Y-m-d');
                $assignments = isset($calendarData['dates'][$dateKey]) ? $calendarData['dates'][$dateKey] : array('note' => '', 'names' => array('', '', ''));
                $dayClass = getDayClass($date, $today, $isCurrentMonth);
                $numberClass = getDayNumberClass($date, $today, $isCurrentMonth);
                $colors = getScheduleColorForDay($calendarData['schedule_guide'], $date);
              ?>
              <div
                class="day-cell <?php echo $dayClass; ?>"
                data-date="<?php echo $dateKey; ?>"
              >
                <div class="day-header">
                  <span class="day-number <?php echo $numberClass; ?>"><?php echo $date->format('j'); ?></span>
                  <?php if (!$isCurrentMonth): ?>
                    <span class="outside">&nbsp;</span>
                  <?php endif; ?>
                </div>
                <?php if ($isCurrentMonth): ?>
                  <input
                    type="text"
                    name="entries[<?php echo $dateKey; ?>][note]"
                    value="<?php echo htmlspecialchars((string)(isset($assignments['note']) ? $assignments['note'] : ''), ENT_QUOTES); ?>"
                    placeholder="일정 메모"
                    class="assignment-note"
                  />
                  <div class="assignments">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                      <input
                        type="text"
                        name="entries[<?php echo $dateKey; ?>][names][<?php echo $i; ?>]"
                        value="<?php echo htmlspecialchars((string)(isset($assignments['names'][$i]) ? $assignments['names'][$i] : ''), ENT_QUOTES); ?>"
                        placeholder="이름 <?php echo $i + 1; ?>"
                        class="assignment-input bg-<?php echo htmlspecialchars($colors[$i], ENT_QUOTES); ?>"
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
              $dayLabels = array('월요일' => 'monday', '화요일' => 'tuesday', '수요일' => 'wednesday', 
                            '목요일' => 'thursday', '금요일' => 'friday', '토요일' => 'saturday', '일요일' => 'sunday');
              $timeLabels = array('오전' => 'morning', '오후' => 'afternoon', '저녁' => 'evening');
              $scheduleGuide = $calendarData['schedule_guide'];
            ?>
            <?php foreach ($dayLabels as $dayLabel => $dayKey): ?>
              <div class="guide-day">
                <h3 class="guide-day-title"><?php echo $dayLabel; ?></h3>
                <?php foreach ($timeLabels as $timeLabel => $timeKey): ?>
                  <?php 
                    $guideData = isset($scheduleGuide[$dayKey][$timeKey]) ? $scheduleGuide[$dayKey][$timeKey] : array('text' => '', 'color' => 'white');
                    $guideColor = isset($guideData['color']) ? $guideData['color'] : 'white';
                    $guideText = isset($guideData['text']) ? $guideData['text'] : '';
                    
                    $bgColor = '#f9fbff';
                    $borderStyle = 'border: 1px solid #cbd5e1;';
                    if ($guideColor === 'green') {
                        $bgColor = '#86efac';
                        $borderStyle = '';
                    } elseif ($guideColor === 'blue') {
                        $bgColor = '#93c5fd';
                        $borderStyle = '';
                    } elseif ($guideColor === 'red') {
                        $bgColor = '#fca5a5';
                        $borderStyle = '';
                    }
                  ?>
                  <div class="guide-time-row">
                    <label class="guide-time-label"><?php echo $timeLabel; ?></label>
                    <textarea
                      name="schedule_guide[<?php echo $dayKey; ?>][<?php echo $timeKey; ?>][text]"
                      placeholder="안내 메시지"
                      class="guide-textarea"
                      rows="4"
                    ><?php echo htmlspecialchars((string)$guideText, ENT_QUOTES); ?></textarea>
                    <input
                      type="hidden"
                      name="schedule_guide[<?php echo $dayKey; ?>][<?php echo $timeKey; ?>][color]"
                      value="<?php echo htmlspecialchars((string)$guideColor, ENT_QUOTES); ?>"
                      class="color-value"
                    />
                    <div class="color-select-wrapper">
                      <button type="button" class="color-select-trigger">
                        <span class="selected-color" data-color="<?php echo htmlspecialchars((string)$guideColor, ENT_QUOTES); ?>" style="background: <?php echo $bgColor; ?>; <?php echo $borderStyle; ?>"></span>
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
