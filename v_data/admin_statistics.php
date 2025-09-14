<?php
include_once('../config.php');
check_accessible('admin');

header('Content-Type: application/json; charset=utf-8');

// 1. 전도인(회원) 데이터
$members = [];
$res = $mysqli->query("SELECT * FROM ".MEMBER_TABLE);
while($row = $res->fetch_assoc()) $members[] = $row;

// 2. 봉사 모임 데이터
$meetings = [];
$res = $mysqli->query("SELECT * FROM ".MEETING_TABLE);
while($row = $res->fetch_assoc()) $meetings[] = $row;

// 3. 모임 스케줄 데이터
$schedules = [];
$res = $mysqli->query("SELECT * FROM ".MEETING_SCHEDULE_TABLE);
while($row = $res->fetch_assoc()) $schedules[] = $row;

// 4. 구역 데이터
$territories = [];
$res = $mysqli->query("SELECT * FROM ".TERRITORY_TABLE);
while($row = $res->fetch_assoc()) $territories[] = $row;

// 5. 전화 구역 데이터
$telephones = [];
$res = $mysqli->query("SELECT * FROM ".TELEPHONE_TABLE);
while($row = $res->fetch_assoc()) $telephones[] = $row;

// 6. 봉사집단 데이터
$groups = [];
$res = $mysqli->query("SELECT * FROM ".GROUP_TABLE);
while($row = $res->fetch_assoc()) $groups[] = $row;

// 7. 전시대 데이터
$displays = [];
$res = $mysqli->query("SELECT * FROM ".DISPLAY_TABLE);
while($row = $res->fetch_assoc()) $displays[] = $row;

// 8. 호별구역 데이터
$territory_records = [];
$res = $mysqli->query("SELECT * FROM ".TERRITORY_RECORD_TABLE);
while($row = $res->fetch_assoc()) $territory_records[] = $row;

// 9. 전화구역 데이터
$telephone_records = [];
$res = $mysqli->query("SELECT * FROM ".TELEPHONE_RECORD_TABLE);
while($row = $res->fetch_assoc()) $telephone_records[] = $row;

// 10. 세대 데이터
$houses = [];
$res = $mysqli->query("SELECT * FROM ".HOUSE_TABLE);
while($row = $res->fetch_assoc()) $houses[] = $row;

// 11. 세대 메모 데이터
$house_memos = [];
$res = $mysqli->query("SELECT * FROM ".HOUSE_MEMO_TABLE);
while($row = $res->fetch_assoc()) $house_memos[] = $row;

// 12. 재방문 기록 데이터
$return_visits = [];
$res = $mysqli->query("SELECT * FROM ".RETURN_VISIT_TABLE);
while($row = $res->fetch_assoc()) $return_visits[] = $row;

// 13. 전화구역 세대 데이터
$telephone_houses = [];
$res = $mysqli->query("SELECT * FROM ".TELEPHONE_HOUSE_TABLE);
while($row = $res->fetch_assoc()) $telephone_houses[] = $row;

// 14. 전화구역 세대 메모 데이터
$telephone_house_memos = [];
$res = $mysqli->query("SELECT * FROM ".TELEPHONE_HOUSE_MEMO_TABLE);
while($row = $res->fetch_assoc()) $telephone_house_memos[] = $row;

// 15. 전화구역 재방문 기록 데이터
$telephone_return_visits = [];
$res = $mysqli->query("SELECT * FROM ".TELEPHONE_RETURN_VISIT_TABLE);
while($row = $res->fetch_assoc()) $telephone_return_visits[] = $row;


// 필요시 추가 데이터도 여기에...

echo json_encode([
    'members'     => $members,
    'meetings'    => $meetings,
    'schedules'   => $schedules,
    'territories' => $territories,
    'telephones'  => $telephones,
    'displays'    => $displays,
    'territory_records' => $territory_records,
    'telephone_records' => $telephone_records,
    'houses' => $houses,
    'house_memos' => $house_memos,
    'return_visits' => $return_visits,
    
], JSON_UNESCAPED_UNICODE);

exit;
