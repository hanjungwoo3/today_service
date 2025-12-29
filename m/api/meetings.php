<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Seoul');

$localConfigFile = dirname(__FILE__) . '/../config.php';
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}

if (!defined('LOCAL_MODE') || LOCAL_MODE !== true) {
    if (file_exists(dirname(__FILE__) . '/../../config.php')) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once dirname(__FILE__) . '/../../config.php';
    }
} else {
    require_once dirname(__FILE__) . '/../../config.php';
}

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$meetings = [];
$sql = "SELECT m_id, ms_time, mp_name, mb_id FROM t_meeting WHERE m_date = '{$date}' AND ms_type = 1 ORDER BY ms_time";
$result = $mysqli->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // 참석자 수 계산
        $ids = preg_split('/[,\s]+/', trim($row['mb_id']));
        $ids = array_filter($ids, function($id) { return !empty($id) && is_numeric($id); });
        $count = count($ids);
        $meetings[] = [
            'id' => $row['m_id'],
            'label' => substr($row['ms_time'], 0, 5) . ' ' . $row['mp_name'] . ' (' . $count . '명)'
        ];
    }
}

echo json_encode($meetings);
