<?php
/**
 * Push 수신 확인 (ACK) 엔드포인트
 * Service Worker에서 push 수신 시 호출
 */
header('Content-Type: text/plain; charset=utf-8');

$id = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']) : '';
if (!$id) {
    http_response_code(400);
    exit;
}

$logFile = __DIR__ . '/../c/storage/push_delivery.log';
$line = date('Y-m-d H:i:s') . "\tACK\t" . $id . "\n";
file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
echo 'ok';
