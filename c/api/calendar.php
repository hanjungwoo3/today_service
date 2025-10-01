<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Seoul');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/helpers.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $year = (int)($_GET['year'] ?? date('Y'));
    $month = (int)($_GET['month'] ?? date('n'));
    [$year, $month] = normalizeYearMonth($year, $month);

    if (isset($_GET['backups'])) {
        $backupDir = getBackupDir($year, $month);
        if (!is_dir($backupDir)) {
            echo json_encode(['backups' => []]);
            exit;
        }
        $files = array_values(array_filter(scandir($backupDir), static fn($file) => !in_array($file, ['.', '..'], true)));
        rsort($files);
        echo json_encode(['backups' => $files]);
        exit;
    }

    $data = loadCalendarData($year, $month);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $input = file_get_contents('php://input');
    if ($input === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '요청 본문을 읽을 수 없습니다.']);
        exit;
    }

    $payload = json_decode($input, true);
    if (!is_array($payload) || !isset($payload['year'], $payload['month'], $payload['entries'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '잘못된 요청입니다.']);
        exit;
    }

    $year = (int)$payload['year'];
    $month = (int)$payload['month'];
    [$year, $month] = normalizeYearMonth($year, $month);

    $entries = $payload['entries'];
    if (!is_array($entries)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '데이터 형식이 올바르지 않습니다.']);
        exit;
    }

    $dates = [];
    foreach ($entries as $date => $entry) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) {
            continue;
        }
        $dates[$date] = normalizeDayEntry($entry);
    }
    
    // Handle schedule_guide
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
        $timestamp = (new DateTimeImmutable('now'))->format('Ymd_His');
        $backupPath = $backupDir . '/' . $timestamp . '.json';
        copy($filePath, $backupPath);
    }

    $dataToSave = [
        'dates' => $dates,
        'schedule_guide' => $scheduleGuide
    ];
    
    $write = file_put_contents($filePath, json_encode($dataToSave, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    if ($write === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '저장에 실패했습니다.']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => '허용되지 않은 메서드입니다.']);

