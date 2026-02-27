<?php
/**
 * Push 알림 구독 관리 API
 * 액션: subscribe, unsubscribe, status
 */
include_once(__DIR__ . '/../config.php');

header('Content-Type: application/json; charset=utf-8');

$current_mb_id = mb_id();
if (!$current_mb_id) {
    http_response_code(401);
    echo json_encode(['error' => '로그인이 필요합니다.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'subscribe':
        $endpoint = isset($_POST['endpoint']) ? trim($_POST['endpoint']) : '';
        $auth = isset($_POST['auth']) ? trim($_POST['auth']) : '';
        $p256dh = isset($_POST['p256dh']) ? trim($_POST['p256dh']) : '';

        if (!$endpoint || !$auth || !$p256dh) {
            echo json_encode(['error' => '필수 값이 누락되었습니다.']);
            exit;
        }

        $mb_id = intval($current_mb_id);
        $escaped_endpoint = $mysqli->real_escape_string($endpoint);
        $escaped_auth = $mysqli->real_escape_string($auth);
        $escaped_p256dh = $mysqli->real_escape_string($p256dh);

        // 기존 동일 endpoint가 있으면 업데이트, 없으면 삽입
        $check_sql = "SELECT ps_id FROM " . PUSH_SUBSCRIPTION_TABLE . "
                      WHERE mb_id = {$mb_id} AND ps_endpoint = '{$escaped_endpoint}'";
        $check_result = $mysqli->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            $ps_id = $check_result->fetch_assoc()['ps_id'];
            $sql = "UPDATE " . PUSH_SUBSCRIPTION_TABLE . "
                    SET ps_auth = '{$escaped_auth}', ps_p256dh = '{$escaped_p256dh}', ps_created = NOW()
                    WHERE ps_id = {$ps_id}";
        } else {
            $sql = "INSERT INTO " . PUSH_SUBSCRIPTION_TABLE . " (mb_id, ps_endpoint, ps_auth, ps_p256dh)
                    VALUES ({$mb_id}, '{$escaped_endpoint}', '{$escaped_auth}', '{$escaped_p256dh}')";
        }

        if ($mysqli->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => '구독 저장에 실패했습니다. (' . $mysqli->error . ')']);
        }
        break;

    case 'unsubscribe':
        $endpoint = isset($_POST['endpoint']) ? trim($_POST['endpoint']) : '';
        $mb_id = intval($current_mb_id);
        $escaped_endpoint = $mysqli->real_escape_string($endpoint);

        if ($endpoint) {
            $sql = "DELETE FROM " . PUSH_SUBSCRIPTION_TABLE . "
                    WHERE mb_id = {$mb_id} AND ps_endpoint = '{$escaped_endpoint}'";
        } else {
            // endpoint 없으면 해당 사용자의 모든 구독 삭제
            $sql = "DELETE FROM " . PUSH_SUBSCRIPTION_TABLE . " WHERE mb_id = {$mb_id}";
        }

        $mysqli->query($sql);
        echo json_encode(['success' => true]);
        break;

    case 'status':
        $mb_id = intval($current_mb_id);
        $sql = "SELECT COUNT(*) as cnt FROM " . PUSH_SUBSCRIPTION_TABLE . " WHERE mb_id = {$mb_id}";
        $result = $mysqli->query($sql);
        $row = $result->fetch_assoc();
        echo json_encode(['subscribed' => intval($row['cnt']) > 0, 'count' => intval($row['cnt'])]);
        break;

    default:
        echo json_encode(['error' => '알 수 없는 액션입니다.']);
}
?>
