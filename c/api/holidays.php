<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Seoul');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/helpers.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $result = updateHolidaysFromIcs();
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => "공휴일 데이터를 업데이트했습니다. ({$result['count']}개)"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => '허용되지 않은 메서드입니다.']);

