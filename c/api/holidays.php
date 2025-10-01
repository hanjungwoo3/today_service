<?php

date_default_timezone_set('Asia/Seoul');
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__) . '/../lib/helpers.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

if ($method === 'POST') {
    $result = updateHolidaysFromIcs();
    
    if ($result['success']) {
        echo json_encode(array(
            'success' => true,
            'message' => "공휴일 데이터를 업데이트했습니다. ({$result['count']}개)"
        ));
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(array(
            'success' => false,
            'error' => $result['error']
        ));
    }
    exit;
}

header('HTTP/1.1 405 Method Not Allowed');
echo json_encode(array('success' => false, 'error' => '허용되지 않은 메서드입니다.'));
