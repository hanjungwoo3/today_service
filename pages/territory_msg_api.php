<?php
include_once(__DIR__ . '/../config.php');

header('Content-Type: application/json; charset=utf-8');

define('TERRITORY_MSG_TABLE', 't_territory_message');
define('TERRITORY_MSG_READ_TABLE', 't_territory_message_read');

$current_mb_id = mb_id();
if (!$current_mb_id) {
    http_response_code(401);
    echo json_encode(['error' => '로그인이 필요합니다.']);
    exit;
}

$current_mb_name = get_member_name($current_mb_id);
$action = isset($_POST['action']) ? $_POST['action'] : '';

// 권한 확인: 해당 구역에 배정된 사용자인지 (T=호별, D=전시대)
function verify_territory_access($tt_id, $mb_id, $type = 'T') {
    global $mysqli;
    $tt_id = intval($tt_id);
    $mb_id = intval($mb_id);
    if ($type === 'D') {
        $sql = "SELECT d_id FROM " . DISPLAY_TABLE . " WHERE d_id = {$tt_id} AND FIND_IN_SET({$mb_id}, d_assigned)";
    } else {
        $sql = "SELECT tt_id FROM " . TERRITORY_TABLE . " WHERE tt_id = {$tt_id} AND FIND_IN_SET({$mb_id}, tt_assigned)";
    }
    $result = $mysqli->query($sql);
    return ($result && $result->num_rows > 0);
}

$msg_type = isset($_POST['type']) ? $_POST['type'] : 'T';
if (!in_array($msg_type, ['T', 'D'])) $msg_type = 'T';

// 오래된 메시지 자동 정리 (1/50 확률로 실행)
if (rand(1, 50) === 1) {
    // 1) 배정일이 지난 호별구역 메시지 삭제
    $cleanup_sql = "DELETE m FROM " . TERRITORY_MSG_TABLE . " m
                    INNER JOIN " . TERRITORY_TABLE . " t ON m.tt_id = t.tt_id AND m.tm_type = 'T'
                    WHERE t.tt_assigned_date < CURDATE()";
    $mysqli->query($cleanup_sql);

    // 1-2) 배정일이 지난 전시대 메시지 삭제
    $cleanup_sql_d = "DELETE m FROM " . TERRITORY_MSG_TABLE . " m
                      INNER JOIN " . DISPLAY_TABLE . " d ON m.tt_id = d.d_id AND m.tm_type = 'D'
                      WHERE d.d_assigned_date < CURDATE()";
    $mysqli->query($cleanup_sql_d);

    // 2) 안전망: 하루 이상 된 메시지 삭제 (구역이 삭제된 경우 등)
    $cleanup_sql2 = "DELETE FROM " . TERRITORY_MSG_TABLE . "
                     WHERE tm_datetime < DATE_SUB(NOW(), INTERVAL 1 DAY)";
    $mysqli->query($cleanup_sql2);

    // 읽음 테이블도 정리
    $cleanup_sql3 = "DELETE r FROM " . TERRITORY_MSG_READ_TABLE . " r
                     LEFT JOIN " . TERRITORY_MSG_TABLE . " m ON r.tt_id = m.tt_id AND r.tm_type = m.tm_type
                     WHERE m.tt_id IS NULL";
    $mysqli->query($cleanup_sql3);
}

switch ($action) {

    // 안 읽은 쪽지 수 일괄 조회
    case 'unread_counts':
        $tt_ids_raw = isset($_POST['tt_ids']) ? $_POST['tt_ids'] : '';
        $tt_ids = array_filter(array_map('intval', explode(',', $tt_ids_raw)));
        if (empty($tt_ids)) {
            echo json_encode(['counts' => new stdClass()]);
            exit;
        }
        $tt_ids_csv = implode(',', $tt_ids);
        $mb_id = intval($current_mb_id);
        $safe_type = $mysqli->real_escape_string($msg_type);

        $sql = "SELECT m.tt_id, COUNT(*) as unread
                FROM " . TERRITORY_MSG_TABLE . " m
                LEFT JOIN " . TERRITORY_MSG_READ_TABLE . " r
                    ON m.tt_id = r.tt_id AND r.tm_type = m.tm_type AND r.mb_id = {$mb_id}
                WHERE m.tt_id IN ({$tt_ids_csv})
                  AND m.tm_type = '{$safe_type}'
                  AND m.tm_id > COALESCE(r.last_read_id, 0)
                GROUP BY m.tt_id";
        $result = $mysqli->query($sql);

        $counts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $counts[$row['tt_id']] = intval($row['unread']);
            }
        }
        echo json_encode(['counts' => empty($counts) ? new stdClass() : $counts]);
        break;

    // 쪽지 패널 열기: 최근 50건 로드
    case 'load':
        $tt_id = intval(isset($_POST['tt_id']) ? $_POST['tt_id'] : 0);
        if (!verify_territory_access($tt_id, $current_mb_id, $msg_type)) {
            http_response_code(403);
            echo json_encode(['error' => '권한이 없습니다.']);
            exit;
        }
        $safe_type = $mysqli->real_escape_string($msg_type);

        $sql = "SELECT tm_id, tt_id, mb_id, mb_name, tm_message, tm_datetime
                FROM " . TERRITORY_MSG_TABLE . "
                WHERE tt_id = {$tt_id} AND tm_type = '{$safe_type}'
                ORDER BY tm_id DESC LIMIT 50";
        $result = $mysqli->query($sql);

        $messages = [];
        $last_id = 0;
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['tm_id'] = intval($row['tm_id']);
                $row['mb_id'] = intval($row['mb_id']);
                $messages[] = $row;
                if ($row['tm_id'] > $last_id) $last_id = $row['tm_id'];
            }
        }
        $messages = array_reverse($messages); // 오래된 순서로

        // 읽음 포인터 갱신
        if ($last_id > 0) {
            $mb_id = intval($current_mb_id);
            $sql = "INSERT INTO " . TERRITORY_MSG_READ_TABLE . " (tt_id, tm_type, mb_id, last_read_id)
                    VALUES ({$tt_id}, '{$safe_type}', {$mb_id}, {$last_id})
                    ON DUPLICATE KEY UPDATE last_read_id = GREATEST(last_read_id, {$last_id})";
            $mysqli->query($sql);
        }

        echo json_encode(['messages' => $messages, 'last_id' => $last_id]);
        break;

    // 폴링: last_id 이후 새 메시지만
    case 'poll':
        $tt_id = intval(isset($_POST['tt_id']) ? $_POST['tt_id'] : 0);
        $last_id = intval(isset($_POST['last_id']) ? $_POST['last_id'] : 0);

        if (!verify_territory_access($tt_id, $current_mb_id, $msg_type)) {
            http_response_code(403);
            echo json_encode(['error' => '권한이 없습니다.']);
            exit;
        }
        $safe_type = $mysqli->real_escape_string($msg_type);

        $sql = "SELECT tm_id, tt_id, mb_id, mb_name, tm_message, tm_datetime
                FROM " . TERRITORY_MSG_TABLE . "
                WHERE tt_id = {$tt_id} AND tm_type = '{$safe_type}' AND tm_id > {$last_id}
                ORDER BY tm_id ASC";
        $result = $mysqli->query($sql);

        $messages = [];
        $new_last_id = $last_id;
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['tm_id'] = intval($row['tm_id']);
                $row['mb_id'] = intval($row['mb_id']);
                $messages[] = $row;
                if ($row['tm_id'] > $new_last_id) $new_last_id = $row['tm_id'];
            }
        }

        // 읽음 포인터 갱신
        if ($new_last_id > $last_id) {
            $mb_id = intval($current_mb_id);
            $sql = "INSERT INTO " . TERRITORY_MSG_READ_TABLE . " (tt_id, tm_type, mb_id, last_read_id)
                    VALUES ({$tt_id}, '{$safe_type}', {$mb_id}, {$new_last_id})
                    ON DUPLICATE KEY UPDATE last_read_id = GREATEST(last_read_id, {$new_last_id})";
            $mysqli->query($sql);
        }

        echo json_encode(['messages' => $messages, 'last_id' => $new_last_id]);
        break;

    // 메시지 전송
    case 'send':
        $tt_id = intval(isset($_POST['tt_id']) ? $_POST['tt_id'] : 0);
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';

        if (!verify_territory_access($tt_id, $current_mb_id, $msg_type)) {
            http_response_code(403);
            echo json_encode(['error' => '권한이 없습니다.']);
            exit;
        }

        if ($message === '' || mb_strlen($message) > 500) {
            echo json_encode(['error' => '메시지는 1~500자 사이여야 합니다.']);
            exit;
        }

        $mb_id = intval($current_mb_id);
        $escaped_name = $mysqli->real_escape_string($current_mb_name);
        $escaped_msg = $mysqli->real_escape_string($message);
        $safe_type = $mysqli->real_escape_string($msg_type);

        $sql = "INSERT INTO " . TERRITORY_MSG_TABLE . " (tt_id, tm_type, mb_id, mb_name, tm_message)
                VALUES ({$tt_id}, '{$safe_type}', {$mb_id}, '{$escaped_name}', '{$escaped_msg}')";

        if ($mysqli->query($sql)) {
            $tm_id = intval($mysqli->insert_id);

            // 읽음 포인터 갱신 (자기 메시지는 읽은 것으로)
            $sql = "INSERT INTO " . TERRITORY_MSG_READ_TABLE . " (tt_id, tm_type, mb_id, last_read_id)
                    VALUES ({$tt_id}, '{$safe_type}', {$mb_id}, {$tm_id})
                    ON DUPLICATE KEY UPDATE last_read_id = GREATEST(last_read_id, {$tm_id})";
            $mysqli->query($sql);

            echo json_encode([
                'success' => true,
                'tm_id' => $tm_id,
                'tm_datetime' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode(['error' => '저장에 실패했습니다.']);
        }
        break;

    default:
        echo json_encode(['error' => '알 수 없는 액션입니다.']);
}
?>
