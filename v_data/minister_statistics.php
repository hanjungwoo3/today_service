<?php
include_once('../config.php');

header('Content-Type: application/json; charset=utf-8');

// 현재 로그인한 회원 ID
$mb_id = mb_id();

// 1. 봉사 모임 데이터 (전체 모임)
$meetings = [];
$sql = "SELECT * FROM ".MEETING_TABLE;
$res = $mysqli->query($sql);
if ($res) {
    while($row = $res->fetch_assoc()) {
        $meetings[] = $row;
    }
}

// 2. 봉사 보고서 데이터 (현재 사용자)
$reports = [];
$sql = "SELECT * FROM ".MINISTER_REPORT_TABLE." WHERE mb_id = '{$mb_id}'";
$res = $mysqli->query($sql);
if ($res) {
    while($row = $res->fetch_assoc()) {
        $reports[] = $row;
    }
}

// 3. 재방문 데이터 (현재 사용자)
$return_visits = [];
$sql = "SELECT * FROM ".RETURN_VISIT_TABLE." WHERE mb_id = '{$mb_id}'";
$res = $mysqli->query($sql);
if ($res) {
    while($row = $res->fetch_assoc()) {
        $return_visits[] = $row;
    }
}

// 응답 데이터
$response = [
    'meetings' => $meetings,
    'reports' => $reports,
    'return_visits' => $return_visits,
    'debug' => [
        'mb_id' => $mb_id,
        'meetings_count' => count($meetings),
        'reports_count' => count($reports),
        'return_visits_count' => count($return_visits)
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);

exit;
