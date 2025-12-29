<?php
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/lib/helpers.php';

// 로컬 개발 모드 체크
$localConfigFile = __DIR__ . '/config.php';
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}

// 로컬 모드가 아닐 때만 관리자 권한 체크
if (!defined('LOCAL_MODE') || LOCAL_MODE !== true) {
    $is_admin = false;
    if (file_exists(dirname(__FILE__) . '/../config.php')) {
        // PHP 8.x 호환: 상위 config.php를 로드하기 전에 세션 쿠키 path를 루트로 설정
        // (config.php에서 SCRIPT_NAME 기반으로 path가 /c로 설정되는 문제 방지)
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

    // 관리자가 아니면 view.php로 리다이렉트
    if (!$is_admin) {
        header('Location: view.php' . (isset($_GET['year']) && isset($_GET['month']) ? '?year='.$_GET['year'].'&month='.$_GET['month'] : ''));
        exit;
    }
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
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>" />
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
                $assignments = isset($calendarData['dates'][$dateKey]) ? $calendarData['dates'][$dateKey] : array('note' => '', 'names' => array('', '', '', ''));
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
                    <?php
                      $timePlaceholders = array('새벽', '오전', '오후', '저녁');
                      for ($i = 0; $i < 4; $i++):
                    ?>
                      <input
                        type="text"
                        name="entries[<?php echo $dateKey; ?>][names][<?php echo $i; ?>]"
                        value="<?php echo htmlspecialchars((string)(isset($assignments['names'][$i]) ? $assignments['names'][$i] : ''), ENT_QUOTES); ?>"
                        placeholder="<?php echo $timePlaceholders[$i]; ?>"
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
              $timeLabels = array('새벽' => 'dawn', '오전' => 'morning', '오후' => 'afternoon', '저녁' => 'evening');
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

        <div class="save-section">
          <button type="submit" id="saveBtn" class="save-btn">저장하기</button>
        </div>

        <div class="utility-buttons-section">
          <div class="utility-button-group">
            <button type="button" id="loadPrevMonth" class="utility-btn">이전달 값 불러오기</button>
            <p class="utility-description">지난 달 입력했던 일정 메모와 이름들을 현재 달력으로 복사만 합니다. 복사한 후 "저장하기" 버튼을 눌러야 적용됩니다.</p>
          </div>

          <div class="utility-button-group">
            <button type="button" id="updateHolidays" class="utility-btn">공휴일 업데이트</button>
            <p class="utility-description">공휴일인 경우 구글 달력을 참조해서 날짜가 붉은 색 숫자로 표시됩니다. 공휴일이 적용되지 않을 경우 [공휴일 업데이트] 버튼을 클릭하면 sync 할 수 있습니다.</p>
          </div>

          <div class="utility-button-group">
            <div class="link-copy-row">
              <?php
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
                $baseUrl = $protocol . '://' . $host . dirname($scriptName);
                $viewUrl = $baseUrl . '/view.php?year=' . $year . '&month=' . $month;
              ?>
              <a href="view.php?year=<?php echo $year; ?>&month=<?php echo $month; ?>" id="viewCalendarBtn" class="utility-btn view-calendar-btn" style="text-decoration: none;"><span id="viewCalendarBtnText">사용자모드로 보기</span></a>
              <button type="button" id="copyViewLink" class="utility-btn" data-url="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES); ?>">사용자모드 URL 복사</button>
            </div>
            <p class="utility-description">사용자모드로 볼 수 있는 링크를 클립보드에 복사합니다. 다른 사람들과 공유할 때 사용하세요.</p>
          </div>
        </div>
      </form>
    </div>
    
    <script>
      // 저장하기 버튼 confirm
      document.getElementById('calendarForm').addEventListener('submit', function(e) {
        if (!confirm('저장하시겠습니까?')) {
          e.preventDefault();
          return false;
        }
      });
      
      // 사용자모드로 보기 링크 복사 버튼
      document.getElementById('copyViewLink').addEventListener('click', function() {
        var viewUrl = this.getAttribute('data-url');

        // 클립보드 복사
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(viewUrl).then(function() {
            alert('링크가 복사되었습니다!\n' + viewUrl);
          }).catch(function(err) {
            fallbackCopyTextToClipboard(viewUrl);
          });
        } else {
          fallbackCopyTextToClipboard(viewUrl);
        }
      });
      
      // 구형 브라우저용 복사 함수
      function fallbackCopyTextToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.top = 0;
        textArea.style.left = 0;
        textArea.style.width = "2em";
        textArea.style.height = "2em";
        textArea.style.padding = 0;
        textArea.style.border = "none";
        textArea.style.outline = "none";
        textArea.style.boxShadow = "none";
        textArea.style.background = "transparent";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
          var successful = document.execCommand('copy');
          if (successful) {
            alert('링크가 복사되었습니다!\n' + text);
          } else {
            alert('복사에 실패했습니다. 링크를 직접 복사해주세요:\n' + text);
          }
        } catch (err) {
          alert('복사에 실패했습니다. 링크를 직접 복사해주세요:\n' + text);
        }
        
        document.body.removeChild(textArea);
      }
      
      // iframe 안에서만 사용자모드로 보기 버튼 새창으로 열기
      (function() {
        const isInIframe = window.self !== window.top;
        const viewCalendarBtn = document.getElementById('viewCalendarBtn');
        const viewCalendarBtnText = document.getElementById('viewCalendarBtnText');
        
        if (isInIframe && viewCalendarBtn) {
          viewCalendarBtnText.textContent = '사용자모드로 보기 ↗';
          viewCalendarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.open(this.href, '_blank', 'noopener,noreferrer');
          });
        }
      })();
    </script>
  </body>
</html>
