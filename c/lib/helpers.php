<?php

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

if (!defined('STORAGE_DIR')) {
    $storagePath = ROOT_DIR . '/storage';
    define('STORAGE_DIR', $storagePath);
}

function normalizeYearMonth($year, $month)
{
    $date = DateTime::createFromFormat('!Y-n', sprintf('%04d-%d', $year, $month));
    if (!$date) {
        $date = new DateTime();
    }
    return array((int)$date->format('Y'), (int)$date->format('n'));
}

function buildCalendarWeeks($year, $month)
{
    $firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month));
    $firstDay->modify('last sunday');
    $weeks = array();
    $current = clone $firstDay;

    for ($week = 0; $week < 6; $week++) {
        $weekDates = array();
        $hasCurrentMonth = false;
        
        for ($day = 0; $day < 7; $day++) {
            $weekDates[] = clone $current;
            if ((int)$current->format('n') === $month) {
                $hasCurrentMonth = true;
            }
            $current->modify('+1 day');
        }
        
        if ($hasCurrentMonth) {
            $weeks[] = $weekDates;
        } else if (count($weeks) > 0) {
            break;
        }
    }

    return $weeks;
}

function loadCalendarData($year, $month)
{
    $path = getMonthFilePath($year, $month);
    if (!file_exists($path)) {
        return array(
            'dates' => array(),
            'schedule_guide' => getDefaultScheduleGuide()
        );
    }

    $json = file_get_contents($path);
    if ($json === false) {
        return array(
            'dates' => array(),
            'schedule_guide' => getDefaultScheduleGuide()
        );
    }

    $data = json_decode($json, true);
    if (!is_array($data) || !isset($data['dates']) || !is_array($data['dates'])) {
        return array(
            'dates' => array(),
            'schedule_guide' => getDefaultScheduleGuide()
        );
    }

    return array(
        'dates' => normalizeCalendarDates($data['dates']),
        'schedule_guide' => isset($data['schedule_guide']) && is_array($data['schedule_guide']) 
            ? $data['schedule_guide'] 
            : getDefaultScheduleGuide()
    );
}

function getDefaultScheduleGuide()
{
    $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
    $times = array('morning', 'afternoon', 'evening');
    $guide = array();
    
    foreach ($days as $day) {
        $guide[$day] = array();
        foreach ($times as $time) {
            $guide[$day][$time] = array('text' => '', 'color' => 'white');
        }
    }
    
    return $guide;
}

function getScheduleColorForDay($scheduleGuide, $date)
{
    $weekday = (int)$date->format('w');
    $dayNames = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
    $dayName = $dayNames[$weekday];
    
    if (!isset($scheduleGuide[$dayName])) {
        return array('white', 'white', 'white');
    }
    
    $daySchedule = $scheduleGuide[$dayName];
    return array(
        isset($daySchedule['morning']['color']) ? $daySchedule['morning']['color'] : 'white',
        isset($daySchedule['afternoon']['color']) ? $daySchedule['afternoon']['color'] : 'white',
        isset($daySchedule['evening']['color']) ? $daySchedule['evening']['color'] : 'white'
    );
}

function getMonthFilePath($year, $month)
{
    $monthKey = sprintf('%04d-%02d', $year, $month);
    return STORAGE_DIR . '/' . $monthKey . '.json';
}

function getBackupDir($year, $month)
{
    $monthKey = sprintf('%04d-%02d', $year, $month);
    return STORAGE_DIR . '/backups/' . $monthKey;
}

function getDayClass($date, $today, $isCurrentMonth)
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

function getHolidaysFilePath()
{
    return STORAGE_DIR . '/holidays.json';
}

function loadHolidays()
{
    $filePath = getHolidaysFilePath();
    
    if (!file_exists($filePath)) {
        return array();
    }
    
    $json = @file_get_contents($filePath);
    if ($json === false) {
        return array();
    }
    
    $data = json_decode($json, true);
    return is_array($data) ? $data : array();
}

function updateHolidaysFromIcs()
{
    $icsUrl = 'https://holidays.hyunbin.page/basic.ics';
    
    // Try cURL first
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $icsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $icsData = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($icsData === false || empty($icsData)) {
            return array('success' => false, 'error' => '공휴일 데이터를 가져올 수 없습니다. cURL 오류: ' . $error);
        }
    } else {
        // Fallback to file_get_contents with SSL options
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 10,
                'ignore_errors' => true
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        ));
        
        $icsData = @file_get_contents($icsUrl, false, $context);
        
        if ($icsData === false) {
            return array('success' => false, 'error' => '공휴일 데이터를 가져올 수 없습니다. allow_url_fopen이 비활성화되었거나 SSL 지원이 없습니다.');
        }
    }
    
    $holidays = array();
    preg_match_all('/DTSTART;VALUE=DATE:(\d{8})/m', $icsData, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $dateStr) {
            $year = substr($dateStr, 0, 4);
            $month = substr($dateStr, 4, 2);
            $day = substr($dateStr, 6, 2);
            $holidays[] = sprintf('%s-%s-%s', $year, $month, $day);
        }
    }
    
    $filePath = getHolidaysFilePath();
    $json = json_encode($holidays);
    
    if (@file_put_contents($filePath, $json) === false) {
        return array('success' => false, 'error' => '공휴일 데이터를 저장할 수 없습니다.');
    }
    
    return array('success' => true, 'count' => count($holidays));
}

function isHoliday($date)
{
    static $holidays = null;
    
    if ($holidays === null) {
        $holidays = loadHolidays();
    }
    
    return in_array($date->format('Y-m-d'), $holidays, true);
}

function getDayNumberClass($date, $today, $isCurrentMonth)
{
    if (!$isCurrentMonth) {
        return 'other-month';
    }

    $dateStr = $date->format('Y-m-d');
    $todayStr = $today->format('Y-m-d');
    
    $weekday = (int)$date->format('w');
    $class = 'default';
    
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

function normalizeCalendarDates($dates)
{
    $normalized = array();
    foreach ($dates as $date => $entry) {
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            continue;
        }
        $normalized[$date] = normalizeDayEntry($entry);
    }

    return $normalized;
}

function isAssocArray($arr)
{
    if (!is_array($arr)) {
        return false;
    }
    $keys = array_keys($arr);
    return array_keys($keys) !== $keys;
}

function normalizeDayEntry($entry)
{
    $note = '';
    $names = array('', '', '');

    if (is_array($entry)) {
        if (isAssocArray($entry)) {
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

    return array(
        'note' => $note,
        'names' => $names,
    );
}

function normalizeNamesArray($names)
{
    $normalized = array();
    foreach ($names as $name) {
        $normalized[] = trim((string)$name);
    }
    $normalized = array_slice($normalized, 0, 3);
    return array_pad($normalized, 3, '');
}
