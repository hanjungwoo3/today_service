<?php

date_default_timezone_set('Asia/Seoul');
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__) . '/../lib/helpers.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

if ($method === 'GET') {
    $year = (int)(isset($_GET['year']) ? $_GET['year'] : date('Y'));
    $month = (int)(isset($_GET['month']) ? $_GET['month'] : date('n'));
    list($year, $month) = normalizeYearMonth($year, $month);

    if (isset($_GET['backups'])) {
        $backupDir = getBackupDir($year, $month);
        if (!is_dir($backupDir)) {
            echo json_encode(array('backups' => array()));
            exit;
        }
        $files = scandir($backupDir);
        $filtered = array();
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filtered[] = $file;
            }
        }
        rsort($filtered);
        echo json_encode(array('backups' => $filtered));
        exit;
    }

    $data = loadCalendarData($year, $month);
    echo json_encode($data);
    exit;
}

if ($method === 'POST') {
    $input = file_get_contents('php://input');
    if ($input === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(array('success' => false, 'error' => '요청 본문을 읽을 수 없습니다.'));
        exit;
    }

    $payload = json_decode($input, true);
    if (!is_array($payload) || !isset($payload['year']) || !isset($payload['month']) || !isset($payload['entries'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(array('success' => false, 'error' => '잘못된 요청입니다.'));
        exit;
    }

    $year = (int)$payload['year'];
    $month = (int)$payload['month'];
    list($year, $month) = normalizeYearMonth($year, $month);

    $entries = $payload['entries'];
    if (!is_array($entries)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(array('success' => false, 'error' => '데이터 형식이 올바르지 않습니다.'));
        exit;
    }

    $dates = array();
    foreach ($entries as $date => $entry) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) {
            continue;
        }
        $dates[$date] = normalizeDayEntry($entry);
    }
    
    $scheduleGuide = isset($payload['schedule_guide']) && is_array($payload['schedule_guide']) 
        ? $payload['schedule_guide'] 
        : getDefaultScheduleGuide();

    if (!is_dir(STORAGE_DIR)) {
        mkdir(STORAGE_DIR, 0775, true);
    }

    $filePath = getMonthFilePath($year, $month);
    $backupDir = getBackupDir($year, $month);

    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0775, true);
    }

    if (file_exists($filePath)) {
        $now = new DateTime('now');
        $timestamp = $now->format('Ymd_His');
        $backupPath = $backupDir . '/' . $timestamp . '.json';
        copy($filePath, $backupPath);
    }

    $dataToSave = array(
        'dates' => $dates,
        'schedule_guide' => $scheduleGuide
    );
    
    $write = file_put_contents($filePath, json_encode($dataToSave));
    if ($write === false) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(array('success' => false, 'error' => '저장에 실패했습니다.'));
        exit;
    }

    // 6개월 이상 된 백업 파일 정리
    $cutoff = new DateTime();
    $cutoff->modify('-6 months');
    $cutoffTimestamp = $cutoff->getTimestamp();
    $backupBaseDir = STORAGE_DIR . '/backups';
    if (is_dir($backupBaseDir)) {
        $monthDirs = glob($backupBaseDir . '/*', GLOB_ONLYDIR);
        foreach ($monthDirs as $monthDir) {
            $files = glob($monthDir . '/*.json');
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTimestamp) {
                    @unlink($file);
                }
            }
            // 빈 디렉토리 삭제
            $remaining = glob($monthDir . '/*');
            if (empty($remaining)) {
                @rmdir($monthDir);
            }
        }
    }

    echo json_encode(array('success' => true));
    exit;
}

header('HTTP/1.1 405 Method Not Allowed');
echo json_encode(array('success' => false, 'error' => '허용되지 않은 메서드입니다.'));
