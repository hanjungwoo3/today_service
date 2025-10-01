<?php
declare(strict_types=1);

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 1));
}

if (!defined('STORAGE_DIR')) {
    $storagePath = ROOT_DIR . '/storage';
    define('STORAGE_DIR', $storagePath);
}

function normalizeYearMonth(int $year, int $month): array
{
    $date = DateTimeImmutable::createFromFormat('!Y-n', sprintf('%04d-%d', $year, $month));
    if (!$date) {
        $date = new DateTimeImmutable();
    }
    return [(int)$date->format('Y'), (int)$date->format('n')];
}

function buildCalendarWeeks(int $year, int $month): array
{
    $firstDay = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    $start = $firstDay->modify('last sunday');
    $weeks = [];
    $current = $start;
    $foundLastWeek = false;

    for ($week = 0; $week < 6; $week++) {
        $weekDates = [];
        $hasCurrentMonth = false;
        
        for ($day = 0; $day < 7; $day++) {
            $weekDates[] = $current;
            if ((int)$current->format('n') === $month) {
                $hasCurrentMonth = true;
            }
            $current = $current->modify('+1 day');
        }
        
        // 현재 월의 날짜가 있으면 포함
        if ($hasCurrentMonth) {
            $weeks[] = $weekDates;
        } else if (count($weeks) > 0) {
            // 이미 주가 있고, 현재 주에 현재 월이 없으면 종료 (다음 달만 있음)
            break;
        }
    }

    return $weeks;
}

function loadCalendarData(int $year, int $month): array
{
    $path = getMonthFilePath($year, $month);
    if (!file_exists($path)) {
        return [
            'dates' => [],
            'schedule_guide' => getDefaultScheduleGuide()
        ];
    }

    $json = file_get_contents($path);
    if ($json === false) {
        return [
            'dates' => [],
            'schedule_guide' => getDefaultScheduleGuide()
        ];
    }

    $data = json_decode($json, true);
    if (!is_array($data) || !isset($data['dates']) || !is_array($data['dates'])) {
        return [
            'dates' => [],
            'schedule_guide' => getDefaultScheduleGuide()
        ];
    }

    return [
        'dates' => normalizeCalendarDates($data['dates']),
        'schedule_guide' => isset($data['schedule_guide']) && is_array($data['schedule_guide']) 
            ? $data['schedule_guide'] 
            : getDefaultScheduleGuide()
    ];
}

function getDefaultScheduleGuide(): array
{
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $times = ['morning', 'afternoon', 'evening'];
    $guide = [];
    
    foreach ($days as $day) {
        $guide[$day] = [];
        foreach ($times as $time) {
            $guide[$day][$time] = ['text' => '', 'color' => 'white'];
        }
    }
    
    return $guide;
}

function getScheduleColorForDay(array $scheduleGuide, DateTimeImmutable $date): array
{
    $weekday = (int)$date->format('w');
    $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    $dayName = $dayNames[$weekday];
    
    if (!isset($scheduleGuide[$dayName])) {
        return ['white', 'white', 'white'];
    }
    
    $daySchedule = $scheduleGuide[$dayName];
    return [
        $daySchedule['morning']['color'] ?? 'white',
        $daySchedule['afternoon']['color'] ?? 'white',
        $daySchedule['evening']['color'] ?? 'white'
    ];
}

function getMonthFilePath(int $year, int $month): string
{
    $monthKey = sprintf('%04d-%02d', $year, $month);
    return STORAGE_DIR . '/' . $monthKey . '.json';
}

function getBackupDir(int $year, int $month): string
{
    $monthKey = sprintf('%04d-%02d', $year, $month);
    return STORAGE_DIR . '/backups/' . $monthKey;
}

function getDayClass(DateTimeImmutable $date, DateTimeImmutable $today, bool $isCurrentMonth): string
{
    if (!$isCurrentMonth) {
        return 'outside';
    }
    
    $dateStr = $date->format('Y-m-d');
    $todayStr = $today->format('Y-m-d');
    
    if ($dateStr === $todayStr) {
        return 'today';
    }
    
    return $dateStr < $todayStr ? 'past' : 'future';
}

function getHolidaysFilePath(): string
{
    return STORAGE_DIR . '/holidays.json';
}

function loadHolidays(): array
{
    $filePath = getHolidaysFilePath();
    
    if (!file_exists($filePath)) {
        return [];
    }
    
    $json = @file_get_contents($filePath);
    if ($json === false) {
        return [];
    }
    
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function updateHolidaysFromIcs(): array
{
    $icsUrl = 'https://holidays.hyunbin.page/basic.ics';
    
    // 타임아웃 설정 (10초)
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $icsData = @file_get_contents($icsUrl, false, $context);
    
    if ($icsData === false) {
        return ['success' => false, 'error' => '공휴일 데이터를 가져올 수 없습니다.'];
    }
    
    $holidays = [];
    preg_match_all('/DTSTART;VALUE=DATE:(\d{8})/m', $icsData, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $dateStr) {
            $year = substr($dateStr, 0, 4);
            $month = substr($dateStr, 4, 2);
            $day = substr($dateStr, 6, 2);
            $holidays[] = sprintf('%s-%s-%s', $year, $month, $day);
        }
    }
    
    // 파일로 저장
    $filePath = getHolidaysFilePath();
    $json = json_encode($holidays, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    if (@file_put_contents($filePath, $json) === false) {
        return ['success' => false, 'error' => '공휴일 데이터를 저장할 수 없습니다.'];
    }
    
    return ['success' => true, 'count' => count($holidays)];
}

function isHoliday(DateTimeImmutable $date): bool
{
    static $holidays = null;
    
    if ($holidays === null) {
        $holidays = loadHolidays();
    }
    
    return in_array($date->format('Y-m-d'), $holidays, true);
}

function getDayNumberClass(DateTimeImmutable $date, DateTimeImmutable $today, bool $isCurrentMonth): string
{
    if (!$isCurrentMonth) {
        return 'default';
    }

    $dateStr = $date->format('Y-m-d');
    $todayStr = $today->format('Y-m-d');
    
    $weekday = (int)$date->format('w');
    $class = 'default';
    
    // 공휴일 확인 (일요일보다 우선)
    if (isHoliday($date)) {
        $class = 'sunday';
    } elseif ($weekday === 0) {
        $class = 'sunday';
    } elseif ($weekday === 6) {
        $class = 'saturday';
    }

    if ($dateStr === $todayStr) {
        $class = 'today';
    } elseif ($dateStr < $todayStr) {
        $class .= ' past';
    }

    return trim($class);
}

function normalizeCalendarDates(array $dates): array
{
    $normalized = [];
    foreach ($dates as $date => $entry) {
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            continue;
        }
        $normalized[$date] = normalizeDayEntry($entry);
    }

    return $normalized;
}

function normalizeDayEntry(mixed $entry): array
{
    $note = '';
    $names = ['', '', ''];

    if (is_array($entry)) {
        if (!array_is_list($entry)) {
            if (isset($entry['note'])) {
                $note = trim((string)$entry['note']);
            }
            if (isset($entry['names']) && is_array($entry['names'])) {
                $names = normalizeNamesArray($entry['names']);
            }
        } else {
            $names = normalizeNamesArray($entry);
        }
    }

    return [
        'note' => $note,
        'names' => $names,
    ];
}

function normalizeNamesArray(array $names): array
{
    $normalized = array_map(static fn($name) => trim((string)$name), $names);
    $normalized = array_slice($normalized, 0, 3);
    return array_pad($normalized, 3, '');
}

