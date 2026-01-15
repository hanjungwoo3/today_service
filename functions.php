<?php
function xss_filter($data)
{
	$data = is_null($data) ? '' : trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	$data = str_replace("'", "", $data);
	$data = str_replace("=", "", $data);
	$data = str_replace("&", "", $data);
	$data = str_replace("", "", $data);
	$data = str_replace("-", "", $data);
	$data = str_replace("#", "", $data);
	$data = str_replace(" ", "", $data);
	$data = str_replace(";", "", $data);
	return $data;
}

// 업로드 특수문자 제거
function upload_filter($data)
{
	$data = is_null($data) ? '' : trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	$data = str_replace("'", "", $data);
	$data = str_replace("=", "", $data);
	$data = str_replace("\"", "", $data);
	$data = str_replace("#", "", $data);
	$data = str_replace(";", "", $data);
	$data = str_replace("%", "", $data);
	$data = str_replace("^", "", $data);
	$data = str_replace("<br>", " ", $data);
	$data = str_replace("?", " ", $data);
	$data = str_replace("*", " ", $data);
	$data = str_replace("[", " ", $data);
	$data = str_replace("]", " ", $data);
	return $data;
}

function session_check()
{
	global $PHP_SELF, $mysqli;
	$basename = basename($PHP_SELF);

	if ($basename != 'login.php' && $basename != 'logincheck.php') {

		if (empty($_SESSION['mb_id'])) {
			if (!empty($_COOKIE["jw_ministry"])) {
				$jw_ministry = explode('|', $_COOKIE["jw_ministry"]);
				$m_name = $jw_ministry[0];
				$m_pw = $jw_ministry[1];

				$sql = "SELECT mb_id, mb_name, mb_hash FROM " . MEMBER_TABLE . " WHERE mb_name='{$m_name}'";
				$result = $mysqli->query($sql);
				if ($result->num_rows > 0) {
					$rs = $result->fetch_assoc();
					if (password_verify($m_pw, $rs['mb_hash'])) {
						$_SESSION['mb_id'] = $rs["mb_id"];
					} else {
						echo "<script>document.location.href='" . BASE_PATH . "/login.php';</script>";
						exit;
					}
				} else {
					echo "<script>document.location.href='" . BASE_PATH . "/login.php';</script>";
					exit;
				}
			} else {
				echo "<script>document.location.href='" . BASE_PATH . "/login.php';</script>";
				exit;
			}
			echo "<script>document.location.href='" . BASE_PATH . "/login.php';</script>";
			exit;
		}
	} else {
		if (isset($_SESSION['mb_id'])) {
			echo "<script>document.location.href='" . BASE_PATH . "/';</script>";
			exit;
		} else {
			if (isset($_COOKIE["jw_ministry"])) {
				$jw_ministry = explode('|', $_COOKIE["jw_ministry"]);
				$m_name = $jw_ministry[0];
				$m_pw = $jw_ministry[1];
				$sql = "SELECT mb_id, mb_name, mb_hash FROM " . MEMBER_TABLE . " WHERE mb_name='{$m_name}'";
				$result = $mysqli->query($sql);
				if ($result->num_rows > 0) {
					$rs = $result->fetch_assoc();
					if (password_verify($m_pw, $rs['mb_hash'])) {
						$_SESSION['mb_id'] = $rs["mb_id"];
						echo "<script>document.location.href='" . BASE_PATH . "/';</script>";
						exit;
					}
				}
			}
		}
	}

}

function mb_id()
{
	if (isset($_SESSION['mb_id'])) {
		return $_SESSION['mb_id'];
	} else {
		return 0;
	}
}

// 특이사항 변환
function get_house_condition_text($condition)
{
	$c_house_condition = unserialize(HOUSE_CONDITION);
	switch ($condition) {
		case '1':
			$return = '재방';
			break;
		case '2':
			$return = '연구';
			break;
		case '3':
			$return = !empty($c_house_condition[3]) ? $c_house_condition[3] : 'JW';
			break;
		case '4':
			$return = !empty($c_house_condition[4]) ? $c_house_condition[4] : '없는집';
			break;
		case '5':
			$return = !empty($c_house_condition[5]) ? $c_house_condition[5] : '수정요청';
			break;
		case '6':
			$return = !empty($c_house_condition[6]) ? $c_house_condition[6] : '심한반대';
			break;
		case '7':
			$return = !empty($c_house_condition[7]) ? $c_house_condition[7] : '외국인';
			break;
		case '8':
			$return = !empty($c_house_condition[8]) ? $c_house_condition[8] : '기타';
			break;
		case '9':
			$return = !empty($c_house_condition[9]) ? $c_house_condition[9] : '별도구역';
			break;
		case '10':
			$return = !empty($c_house_condition[10]) ? $c_house_condition[10] : '추가';
			break;
		default:
			$return = '';
			break;
	}

	return $return;
}

// 전도인 직책 변환
function get_member_position_text($position)
{
	switch ($position) {
		case '1':
			$return = '봉사의 종';
			break;
		case '2':
			$return = '장로';
			break;
		case '3':
			$return = '순회감독자';
			break;
		default:
			$return = '';
			break;
	}

	return $return;
}

// 전도인 파이오니아 변환
function get_member_pioneer_text($pioneer)
{
	switch ($pioneer) {
		case '1':
			$return = '전도인';
			break;
		case '2':
			$return = '정규';
			break;
		case '3':
			$return = '특별';
			break;
		case '4':
			$return = '선교인';
			break;
		default:
			$return = '';
			break;
	}

	return $return;
}

// 요일 텍스트 구하기
function get_week_text($week)
{
	switch ($week) {
		case '1':
			$return = '월';
			break;
		case '2':
			$return = '화';
			break;
		case '3':
			$return = '수';
			break;
		case '4':
			$return = '목';
			break;
		case '5':
			$return = '금';
			break;
		case '6':
			$return = '토';
			break;
		case '7':
			$return = '일';
			break;
		case '8':
			$return = '미배정';
			break;
		default:
			$return = '';
			break;
	}

	return $return;
}

// 요일 텍스트 구하기
function get_week_number_text($week)
{
	switch ($week) {
		case '1':
			$return = '첫째 주';
			break;
		case '2':
			$return = '둘째 주';
			break;
		case '3':
			$return = '셋째 주';
			break;
		case '4':
			$return = '넷째 주';
			break;
		case '5':
			$return = '다섯째 주';
			break;
		case '6':
			$return = '마지막 주';
			break;
		default:
			$return = '';
			break;
	}

	return $return;
}

// 일치 여부 확인하기
function get_selected_text($var, $compare)
{
	$return = ($var == $compare) ? 'selected="selected"' : '';
	return $return;
}
function get_checked_text($var, $compare)
{
	$return = ($var == $compare) ? 'checked="checked"' : '';
	return $return;
}
function get_active_text($var, $compare)
{
	$return = ($var == $compare) ? 'active' : '';
	return $return;
}
function get_current_text($var, $compare)
{
	$return = ($var == $compare) ? ' <span class="sr-only">(current)</span>' : '';
	return $return;
}

// 날짜값이 비었는지 확인
function empty_date($date)
{
	$return = false;
	if (empty($date) || $date == '0000-00-00')
		$return = true;

	return $return;
}

function check_value($value)
{
	if ($value) {
		return $value;
	} else {
		return '';
	}
}

//퍼센트 함수
function get_percent($range, $total)
{
	$return = 0;
	if ($range != 0 && $total != 0)
		$return = round(($range / $total) * 100, 2);

	return $return;
}

//전화번호 '-' 붙이기
function get_hp_text($mb_hp)
{
	// 하이픈 제거
	$mb_hp = preg_replace('/[^0-9]/', '', $mb_hp);

	$mb_hp_count = strlen($mb_hp);
	switch ($mb_hp_count) {
		case 10:
			$return = substr($mb_hp, 0, 3) . "-" . substr($mb_hp, 3, 3) . "-" . substr($mb_hp, 6, 4);
			break;
		default:
			$return = substr($mb_hp, 0, 3) . "-" . substr($mb_hp, 3, 4) . "-" . substr($mb_hp, 7, 4);
			break;
	}

	return $return;
}

// 일시 텍스트 출력
function get_datetime_text($datetime)
{
	// 요일 매핑 (영어 요일을 한글 약어로 변환)
	$days = [
		'Sun' => '일',
		'Mon' => '월',
		'Tue' => '화',
		'Wed' => '수',
		'Thu' => '목',
		'Fri' => '금',
		'Sat' => '토'
	];

	// 입력값이 없는 경우 처리
	if (!$datetime)
		return '';

	// 날짜와 시간 분리
	$dayEnglish = date('D', strtotime($datetime));
	$dayKorean = $days[$dayEnglish];
	$currentYear = date('Y');
	$dateYear = date('Y', strtotime($datetime));
	$hour = date('H', strtotime($datetime));
	$time = date('h:i', strtotime($datetime));
	$isPM = ($hour >= 12) ? '오후' : '오전';

	// 날짜 텍스트 생성
	if ($currentYear == $dateYear) {
		// 같은 연도일 경우
		$dateText = date('n월 d일', strtotime($datetime)) . " ({$dayKorean})";
	} else {
		// 다른 연도일 경우
		$dateText = date('Y년 n월 d일', strtotime($datetime)) . " ({$dayKorean})";
	}

	// 입력 값이 날짜만 있는 경우
	if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $datetime)) {
		return $dateText;
	}

	// 입력 값이 시간만 있는 경우
	if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $datetime)) {
		return "{$isPM} {$time}";
	}

	// 입력 값이 날짜와 시간이 있는 경우
	return "{$dateText} {$isPM} {$time}";
}

// 현재로부터 몇일 전?
function timeAgo($datetime)
{
	$now = new DateTime(); // 현재 시간
	$date = new DateTime($datetime); // 입력된 시간
	$interval = $now->diff($date); // 시간 차이 계산

	// 결과 포맷
	$days = $interval->days; // 일수
	$hours = $interval->h;   // 시간
	$minutes = $interval->i; // 분

	// 조건별로 표시
	if ($days > 0) {
		return "{$days}일 {$hours}시간 전";
	} elseif ($hours > 0) {
		return "{$hours}시간 {$minutes}분 전";
	} else {
		return "{$minutes}분 전";
	}
}

//봉사모임 정보 텍스트 구하기
function get_meeting_data_text($ms_time, $g_name, $mp_name)
{
	$g_name = $g_name ? '[' . $g_name . '집단] ' : '';
	$return = get_datetime_text($ms_time) . ' ' . $g_name . $mp_name;

	return $return;
}

// 봉사모임 옵션 출력
function get_meeting_option()
{
	global $mysqli;

	$return = '';
	$sql = "SELECT ms_id, ma_id, ms_week, ms_time, mp_name, g_name
          FROM " . MEETING_SCHEDULE_TABLE . " ms LEFT JOIN " . MEETING_PLACE_TABLE . " mp ON ms.mp_id = mp.mp_id LEFT JOIN " . GROUP_TABLE . " g ON ms.g_id = g.g_id
          ORDER BY ms_week, ms_time, g_name, mp_name, ms_id ASC";
	$result = $mysqli->query($sql);
	while ($ms = $result->fetch_assoc()) {
		$return .= '<option value="' . $ms['ms_id'] . '">(' . $ms['ms_id'] . ') ' . get_week_text($ms['ms_week']) . ' ' . get_meeting_data_text($ms['ms_time'], $ms['g_name'], $ms['mp_name']);
		if ($ms['ma_id'])
			$return .= ' [회중일정]';
		$return .= '</option>';
	}

	return $return;
}

function get_territory_progress($tt_id)
{
	global $mysqli;

	$sql = "SELECT count(*) FROM " . HOUSE_TABLE . " WHERE tt_id = {$tt_id}";
	$h_result = $mysqli->query($sql);
	$h_row = $h_result->fetch_row();
	$total_house = $h_row[0]; // 전체 집 수

	// 특이사항이 있는 집 (진행률 계산에서 제외)
	// 숫자로 변환하여 0보다 크면 특이사항이 있는 것으로 간주 (1~10)
	$sql = "SELECT count(*) FROM " . HOUSE_TABLE . " 
		WHERE tt_id = {$tt_id} 
		AND h_condition IS NOT NULL 
		AND CAST(h_condition AS UNSIGNED) > 0";
	$h_result = $mysqli->query($sql);
	$h_row = $h_result->fetch_row();
	$condition_house = $h_row[0];

	// 만남 집 수: h_visit = 'Y'이고 특이사항이 없는 집 (NULL, 0, 빈 문자열 등)
	$sql = "SELECT count(*) FROM " . HOUSE_TABLE . " 
        WHERE tt_id = {$tt_id} 
        AND h_visit = 'Y' 
        AND (h_condition IS NULL OR CAST(h_condition AS UNSIGNED) = 0)";
	$h_result = $mysqli->query($sql);
	$h_row = $h_result->fetch_row();
	$visit_house = $h_row[0];

	// 부재 집 수: h_visit = 'N'이고 특이사항이 없는 집
	$sql = "SELECT count(*) FROM " . HOUSE_TABLE . " 
        WHERE tt_id = {$tt_id} 
        AND h_visit = 'N' 
        AND (h_condition IS NULL OR CAST(h_condition AS UNSIGNED) = 0)";
	$h_result = $mysqli->query($sql);
	$h_row = $h_result->fetch_row();
	$absence_house = $h_row[0];

	return array(
		'total' => $total_house,
		'visit' => $visit_house,
		'absence' => $absence_house,
		'condition' => $condition_house
	);

}

function get_telephone_progress($tp_id)
{
	global $mysqli;

	$sql = "SELECT count(*) FROM " . TELEPHONE_HOUSE_TABLE . " WHERE tp_id = {$tp_id}";
	$tph_result = $mysqli->query($sql);
	$tph_row = $tph_result->fetch_row();
	$total_house = $tph_row[0]; // 전체 집 수

	// 특이사항이 있는 집 (진행률 계산에서 제외)
	// 숫자로 변환하여 0보다 크면 특이사항이 있는 것으로 간주
	$sql = "SELECT count(*) FROM " . TELEPHONE_HOUSE_TABLE . " 
		WHERE tp_id = {$tp_id} 
		AND tph_condition IS NOT NULL 
		AND CAST(tph_condition AS UNSIGNED) > 0";
	$tph_result = $mysqli->query($sql);
	$tph_row = $tph_result->fetch_row();
	$condition_house = $tph_row[0];


	// 만남 집 수: tph_visit = 'Y'이고 특이사항이 없는 집
	$sql = "SELECT count(*) FROM " . TELEPHONE_HOUSE_TABLE . " 
        WHERE tp_id = {$tp_id} 
        AND tph_visit = 'Y' 
        AND (tph_condition IS NULL OR CAST(tph_condition AS UNSIGNED) = 0)";
	$tph_result = $mysqli->query($sql);
	$tph_row = $tph_result->fetch_row();
	$visit_house = $tph_row[0];

	// 부재 집 수: tph_visit = 'N'이고 특이사항이 없는 집
	$sql = "SELECT count(*) FROM " . TELEPHONE_HOUSE_TABLE . " 
        WHERE tp_id = {$tp_id} 
        AND tph_visit = 'N' 
        AND (tph_condition IS NULL OR CAST(tph_condition AS UNSIGNED) = 0)";
	$tph_result = $mysqli->query($sql);
	$tph_row = $tph_result->fetch_row();
	$absence_house = $tph_row[0];

	return array(
		'total' => $total_house,
		'visit' => $visit_house,
		'absence' => $absence_house,
		'condition' => $condition_house
	);

}

// 전체 봉사집단 구하기
function get_group_data_all()
{
	global $mysqli;

	$return = array();
	$sql = "SELECT * FROM " . GROUP_TABLE . " ORDER BY g_name";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc())
			$return[$row['g_id']] = $row['g_name'];
	}

	return $return;
}

// ID 로 봉사 집단 이름 구하기
function get_group_name($g_id)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT g_name FROM " . GROUP_TABLE . " WHERE g_id = {$g_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$return = $row['g_name'];
	}

	return $return;
}


// ID 로 봉사집단에 속한 전도인 전체 구하기
function get_group_member_data($g_id)
{
	global $mysqli;

	$return = array();
	$sql = "SELECT * FROM " . MEMBER_TABLE . " WHERE g_id = {$g_id} ORDER BY mb_name";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc())
			$return[] = $row['mb_id'];
	}

	return $return;
}

function get_member_data($mb_id)
{
	global $mysqli;

	$return = array();
	$sql = "SELECT * FROM " . MEMBER_TABLE . " WHERE mb_id = {$mb_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0)
		$return = $result->fetch_assoc();

	return $return;
}

// ID 로 전도인 이름 구하기
function get_member_name($mb_id)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT mb_name FROM " . MEMBER_TABLE . " WHERE mb_id = {$mb_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$return = $row['mb_name'];
	}

	return $return;
}

// ID들 로 부터 전도인 이름들 구하기
function get_member_names($mb_ids)
{
	global $mysqli;

	$return = array();
	$sql = "SELECT mb_name FROM " . MEMBER_TABLE . " WHERE mb_id IN ({$mb_ids}) ORDER BY FIELD(mb_id, {$mb_ids})";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc())
			$return[] = $row['mb_name'];
	}

	return $return;
}

// 배정된 전도인 이름 구하기
function get_assigned_member_name($assigned_value)
{
	global $mysqli;

	$return = '';
	if ($assigned_value) {
		$assigned = get_member_names($assigned_value);
		$return = implode(', ', $assigned);
	}

	return $return;
}

// 구역에 배정된 전도인 이름 배열로 구하기
function filter_assigned_member_array($assigned_string)
{

	$return_assigned = array();
	$assigned_array = explode(',', $assigned_string);

	foreach ($assigned_array as $member) {
		if (is_numeric($member)) {
			$return_assigned[] = get_member_name($member);
		} else {
			$return_assigned[] = $member;
		}
	}

	return $return_assigned;
}

// 전시대 봉사 참여 가능/불가능 여부
function get_member_display($mb_id)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT mb_display FROM " . MEMBER_TABLE . " WHERE mb_id = {$mb_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$return = $row['mb_display'];
	}

	return $return;
}

// 전도인의 전출 날짜 구하기
function get_member_moveout_date($mb_id)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT mb_moveout_date FROM " . MEMBER_TABLE . " WHERE mb_id = {$mb_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$return = $row['mb_moveout_date'];
	}

	return $return;
}

// 전출여부
function is_moveout($mb_id)
{
	global $mysqli;

	$return = false;
	$sql = "SELECT mb_moveout_date FROM " . MEMBER_TABLE . " WHERE mb_id = '$mb_id'";
	$result = $mysqli->query($sql);

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		if ($row['mb_moveout_date'] && $row['mb_moveout_date'] != '0000-00-00')
			$return = true;
	}

	return $return;
}

// 전도인 옵션 출력
function get_member_option($mb_id)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT * FROM " . MEMBER_TABLE . " WHERE mb_moveout_date = '0000-00-00' ORDER BY mb_name ASC";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$return .= '<option value="' . $row['mb_id'] . '" ' . get_selected_text($row['mb_id'], $mb_id) . '>' . $row['mb_name'] . '</option>';
		}
	}

	return $return;
}

// ID 로 전도인 집단 ID 구하기
function get_member_group($mb_id)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT g_id FROM " . MEMBER_TABLE . " WHERE mb_id = {$mb_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$return = $row['g_id'];
	}

	return $return;
}

// ID 로 부터 전도인 권한 구하기
function get_member_auth($mb_id)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT mb_auth FROM " . MEMBER_TABLE . " WHERE mb_id = {$mb_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$return = $row['mb_auth'];
	}

	return $return;
}

// ID 로 부터 전도인의 공지 열람 권한 구하기
function get_member_board_auth($mb_id)
{
	$auth = array(1, 7);
	if (get_member_auth($mb_id) == 1 || get_member_pioneer($mb_id) > 1)
		$auth[] = 2;	//파이오니아
	if (get_member_auth($mb_id) == 1 || is_guide($mb_id))
		$auth[] = 3; //인도자
	if (get_member_auth($mb_id) == 1 || get_member_position($mb_id) >= 1)
		$auth[] = 4; //봉사의 종
	if (get_member_auth($mb_id) == 1 || get_member_position($mb_id) >= 2)
		$auth[] = 5; //장로
	if (is_admin($mb_id))
		$auth[] = 6;  //관리자

	return $auth;
}

// 전도인 권한 텍스트 구하기
function get_member_auth_text($mb_auth)
{
	$return = '';
	switch ($mb_auth) {
		case 1:
			$return = '주 관리자';
			break;
		case 2:
			$return = '보조 관리자';
			break;
	}
	return $return;
}

// ID 로 부터 전도인 직책 구하기
function get_member_position($mb_id)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT mb_position FROM " . MEMBER_TABLE . " WHERE mb_id = {$mb_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$return = $row['mb_position'];
	}

	return $return;
}

// ID 로 부터 전도인 파이오니아 여부 구하기
function get_member_pioneer($mb_id)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT mb_pioneer FROM " . MEMBER_TABLE . " WHERE mb_id = {$mb_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$return = $row['mb_pioneer'];
	}

	return $return;
}

// 관리자 여부 구하기
function is_admin($mb_id)
{

	$return = false;
	if (get_member_auth($mb_id) == '1' || get_member_auth($mb_id) == '2')
		$return = true;

	return $return;
}

// 현재 사용자가 페이지에 접근 가능한지 확인
function check_accessible($page)
{
	if ($page == 'super') {
		if (get_member_auth(mb_id()) != '1') {
			echo '<script> alert("현재 페이지에 접근할 수 없습니다."); location.href="' . BASE_PATH . '/"; </script>';
			exit;
		}
	} elseif ($page == 'admin') {
		if (!is_admin(mb_id())) {
			echo '<script> alert("관리자 페이지에 접근할 수 없습니다."); location.href="' . BASE_PATH . '/"; </script>';
			exit;
		}
	} elseif ($page == 'guide') {
		if (!is_guide(mb_id()) && !is_admin(mb_id())) {
			echo '<script> alert("인도자 페이지에 접근할 수 없습니다."); location.href="' . BASE_PATH . '/"; </script>';
			exit;
		}
	} elseif ($page == 'display') {
		// 전시대 기능이 비활성화되어 있거나, 전시대 미선정이거나, 전출 전도인인 경우 접근 제한
		if (DISPLAY_USE != 'use' || get_member_display(mb_id()) == 1 || is_moveout(mb_id())) {
			echo '<script> alert("전시대 페이지에 접근할 수 없습니다."); location.href="' . BASE_PATH . '/"; </script>';
			exit;
		}
	}
}

// 인도자 여부 구하기
function is_guide($mb_id)
{
	global $mysqli;

	$return = false;
	$today = date('Y-m-d');

	$sql = "SELECT * FROM (SELECT
				(SELECT count(m_id) FROM " . MEETING_TABLE . " WHERE m_date >= '{$today}' AND m_guide = '{$mb_id}') s1,
				(SELECT count(ms_id) FROM " . MEETING_SCHEDULE_TABLE . " WHERE FIND_IN_SET({$mb_id},ms_guide) || FIND_IN_SET({$mb_id},ms_guide2)) s2) T;";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0)
		$row = $result->fetch_assoc();

	if ($row['s1'] > 0 || $row['s2'] > 0)
		$return = true;

	return $return;
}

function get_ms_id_by_guide($mb_id)
{
	global $mysqli;

	$return = array();
	$today = date('Y-m-d');

	$sql = "SELECT ms_id FROM " . MEETING_TABLE . " WHERE m_date >= '{$today}' AND m_guide = '{$mb_id}'";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0)
		while ($row = $result->fetch_assoc())
			$return[] = $row['ms_id'];

	$sql = "SELECT ms_id FROM " . MEETING_SCHEDULE_TABLE . " WHERE FIND_IN_SET({$mb_id},ms_guide) || FIND_IN_SET({$mb_id},ms_guide2)";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0)
		while ($row = $result->fetch_assoc())
			$return[] = $row['ms_id'];

	$return = implode(',', array_unique($return));

	return $return;
}

// ID 로 부터 인도자 보조자 정보 구하기
function get_guide_data($guide)
{
	global $mysqli;

	// 숫자와 콤마만 허용하도록 정제
	$safe = preg_replace('/[^0-9,]/', '', (string) $guide);
	$safe = trim($safe, ',');

	if ($safe === '') {
		return array();
	}

	$return = array();
	$sql = "SELECT mb_name, mb_hp FROM " . MEMBER_TABLE . " WHERE mb_id IN ({$safe}) ORDER BY mb_name";
	$result = $mysqli->query($sql);
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$return[] = array(
				'name' => $row['mb_name'],
				'hp' => decrypt($row['mb_hp'])
			);
		}
	}
	return $return;
}

// 해당 봉사모임의 인도자인지 확인하기
function check_include_guide($mb_id, $ms_guide)
{
	$guide = explode(",", $ms_guide);
	$return = (in_array($mb_id, $guide)) ? true : false;
	return $return;
}

// 인도자 옵션 출력
function get_guide_option($mb_id)
{
	global $mysqli;

	$return = '';
	$mb_sex = '';

	$sql = "SELECT mb_id, mb_name, mb_sex FROM " . MEMBER_TABLE . " WHERE mb_moveout_date = '0000-00-00' ORDER BY mb_sex ASC, mb_name ASC";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {

			if (empty($row['mb_sex'])) {
				$row['mb_sex'] = 'M';
			}

			if ($mb_sex != $row['mb_sex']) {
				$mb_sex = $row['mb_sex'];
				$return .= '<optgroup label="' . ($row['mb_sex'] == 'M' ? '형제' : '자매') . '">';
			}

			if (is_admin(mb_id()) || (!is_admin(mb_id()) && is_guide($row['mb_id']))) {
				$return .= '<option value="' . $row['mb_id'] . '" ' . get_selected_text($row['mb_id'], $mb_id) . '>' . $row['mb_name'] . '</option>';
			}

			if ($mb_sex != $row['mb_sex']) {
				$mb_sex = $row['mb_sex'];
				$return .= '</optgroup>';
			}
		}
	}

	return $return;
}

// 회중일정관리 o째주 o요일의 날짜 구하기
function getNthWeekday($year, $month, $week, $weekday)
{ // 주의 지정이 옳은지 확인함

	if ($week < 1 || $week > 5)
		return false;
	if ($weekday < 0 || $weekday > 6)
		return false; // 요일의 지정이 옳은지 확인함
	$weekdayOfFirst = (int) date('w', mktime(0, 0, 0, $month, 1, $year)); // 지정한 년 월의 첫째 날(1일)의 요일을 구함
	$firstDay = $weekday - $weekdayOfFirst + 1; // 첫 째 날의 월요일을 바탕으로 o번째 o요일의 날짜를 구함
	if ($firstDay <= 0)
		$firstDay += 7;
	$resultDay = $firstDay + 7 * ($week - 1); //7의 배수를 가산해 제o주의 o요일의 날짜를 구함
	if (!checkdate($month, $resultDay, $year))
		return false; // 마지막으로 처리 결과가 올바른 날짜인지 확인함.

	return $resultDay;
}

// 날짜에 따른 회중일정 id 구하기
function get_addschedule_id($s_date)
{
	global $mysqli;

	$ma_array = [];
	$auto_year = date('Y', strtotime($s_date));
	$auto_month = date('m', strtotime($s_date));

	$ma_sql = "SELECT * FROM " . MEETING_ADD_TABLE . " WHERE (DATE(ma_date) <= '{$s_date}' AND DATE(ma_date2) >= '{$s_date}') OR (ma_auto = 1 AND ma_week != '' AND ma_weekday != '')";
	$ma_result = $mysqli->query($ma_sql);
	if ($ma_result->num_rows > 0) {
		while ($ma = $ma_result->fetch_assoc()) {
			$date = '';
			if ($ma['ma_auto'] == 1) {
				$week = ($ma['ma_week'] == 6) ? 5 : $ma['ma_week'];
				$weekday = ($ma['ma_weekday'] == 7) ? 0 : $ma['ma_weekday'];

				$day = getNthWeekday($auto_year, $auto_month, $week, $weekday);
				if ($day == false && $ma['ma_week'] == 6) {
					$week = 4;
					$day = getNthWeekday($auto_year, $auto_month, $week, $weekday);
				}
				if ($day !== false) {
					$date = date('Y-m-d', mktime(0, 0, 0, $auto_month, $day, $auto_year));
					if ($date == $s_date)
						$ma_array[] = $ma['ma_id'];
				}
			} else {
				$ma_array[] = $ma['ma_id'];
			}
		}
	}
	$ma_id = empty($ma_array) ? 'NULL' : implode(',', $ma_array);

	return $ma_id;
}

// 날짜에 따른 회중일정 id 구하기
function get_addschedule_id_sub($s_date)
{
	global $mysqli;

	$ma_array = [];
	$today = date('Y-m-d');

	$ma_sql = "SELECT * FROM " . MEETING_ADD_TABLE . " WHERE (DATE(ma_date) <= '{$s_date}' AND DATE(ma_date2) >= '{$s_date}') OR (ma_auto = 1 AND ma_week != '' AND ma_weekday != '')";
	$ma_result = $mysqli->query($ma_sql);
	if ($ma_result->num_rows > 0) {
		while ($ma = $ma_result->fetch_assoc()) {
			$date = '';
			$auto_year = date('Y', strtotime($s_date));
			$auto_month = date('m', strtotime($s_date));

			if ($ma['ma_auto'] == 1) {
				$week = ($ma['ma_week'] == 6) ? 5 : $ma['ma_week'];
				$weekday = ($ma['ma_weekday'] == 7) ? 0 : $ma['ma_weekday'];

				$day = getNthWeekday($auto_year, $auto_month, $week, $weekday);
				if ($day == false && $ma['ma_week'] == 6) {
					$week = 4;
					$day = getNthWeekday($auto_year, $auto_month, $week, $weekday);
				}
				if ($day !== false) {
					$date = date('Y-m-d', mktime(0, 0, 0, $auto_month, $day, $auto_year));
					if ($date == $s_date)
						$ma_array[] = $ma['ma_id'];
				}
			} else {
				$ma_array[] = $ma['ma_id'];
			}
		}
	}
	$ma_id = empty($ma_array) ? 'NULL' : implode(',', $ma_array);

	return $ma_id;
}

// 회중일정 이름 구하기
function get_addschedule_title($ma_id)
{
	global $mysqli;

	$ma_sql = "SELECT ma_title FROM " . MEETING_ADD_TABLE . " WHERE ma_id = " . $ma_id;
	$ma_result = $mysqli->query($ma_sql);
	if ($ma_result->num_rows > 0) {
		$row = $ma_result->fetch_assoc();
		$ma_title = $row['ma_title']; // ma_title 값 가져오기
	}
	return $ma_title;

}

// 회중일정 날짜 구하기
function get_addschedule_date($ma_auto, $ma_switch, $ma_date, $ma_date2, $s_date)
{
	global $mysqli;

	if ($ma_auto == 1) {
		$return = $s_date;
	} else {
		if ($ma_switch == 0) {
			$time1 = date('H:i', strtotime($ma_date));
			$time2 = date('H:i', strtotime($ma_date2));
		}
		$month1 = $ma_date ? date('Y-m-d', strtotime($ma_date)) : '';
		$month2 = $ma_date2 ? date('Y-m-d', strtotime($ma_date2)) : '';
		if ($month1 == $month2) {
			if ($time1 && $time2) {
				$return = ($time1 == $time2) ? $month1 . ' ' . $time1 : $month1 . ' (' . $time1 . ' ~ ' . $time2 . ')';
			} elseif (empty($time1) && empty($time2)) {
				$return = $month1;
			}
		} else {
			$return = (!empty($time1 or $time2)) ? $month1 . ' (' . $time1 . ') ~ ' . $month2 . ' (' . $time2 . ')' : $month1 . ' ~ ' . $month2;
		}
	}

	return $return;
}

// 회중일정 날짜 구하기
function get_admin_addschedule_date($ma_auto, $ma_switch, $ma_date, $ma_date2, $ma_week, $ma_weekday)
{
	global $mysqli;

	if ($ma_auto == 1) {
		$return = (empty($ma_week) || empty($ma_weekday)) ? '미배정' : '매월 ' . get_week_number_text($ma_week) . ' ' . get_week_text($ma_weekday) . '요일';
	} else {
		if ($ma_date == '0000-00-00 00:00:00' && $ma_date2 == '0000-00-00 00:00:00') {
			$return = '미배정';
		} else {

			$time1 = '';
			$time2 = '';

			if ($ma_switch == 0) {
				$time1 = date('H:i', strtotime($ma_date));
				$time2 = date('H:i', strtotime($ma_date2));
			}
			$month1 = $ma_date ? date('Y년 n월 j일', strtotime($ma_date)) : '';
			$month2 = $ma_date2 ? date('Y년 n월 j일', strtotime($ma_date2)) : '';
			if ($month1 == $month2) {
				if ($time1 && $time2) {
					$return = ($time1 == $time2) ? $month1 . ' ' . $time1 : $month1 . ' ' . $time1 . ' ~ ' . $time2;
				} elseif (empty($time1) && empty($time2))
					$return = $month1;
			} else {
				$return = (!empty($time1 or $time2)) ? $month1 . ' ' . $time1 . ' ~ ' . $month2 . ' ' . $time2 : $month1 . ' ~ ' . $month2;
			}
		}
	}

	return $return;
}

// 작업로그 작성
function insert_work_log($wl_key)
{
	global $mysqli;

	$current_datetime = date("Y-m-d H:i:s");
	$sql = "INSERT INTO " . WORK_LOG_TABLE . " (wl_cdate, wl_key) VALUES ('{$current_datetime}', '{$wl_key}')";
	$mysqli->query($sql);
}

function get_copy_ms_id($ms_ids)
{
	global $mysqli;

	$return = array();
	$sql = "SELECT copy_ms_id FROM " . MEETING_SCHEDULE_TABLE . " WHERE FIND_IN_SET(ms_id, '" . $ms_ids . "')";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0)
		while ($row = $result->fetch_assoc())
			$return[] = $row['copy_ms_id'];
	$return = implode(',', array_unique($return));

	return $return;
}

function get_meeting_schedule_data($ms_id)
{
	global $mysqli;

	$return = array();
	$sql = "SELECT * FROM " . MEETING_SCHEDULE_TABLE . " WHERE ms_id = {$ms_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0)
		$return = $result->fetch_assoc();

	return $return;
}

//각 회중일정에 생성된 봉사모임 개수
function get_meeting_schedule_count($ma_id)
{
	global $mysqli;

	$sql = "SELECT count(ms_id) as ms_count FROM " . MEETING_SCHEDULE_TABLE . " WHERE ma_id = '{$ma_id}'";
	$result = $mysqli->query($sql);
	$row = $result->fetch_assoc();
	$return = ($row['ms_count'] > 0) ? $row['ms_count'] : 0;

	return $return;
}

// 전체 봉사모임장소 출력
function get_meeting_place_data_all()
{
	global $mysqli;

	$return = array();
	$sql = "SELECT * FROM " . MEETING_PLACE_TABLE . " ORDER BY mp_name";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc())
			$return[$row['mp_id']] = array('mp_name' => $row['mp_name'], 'mp_address' => $row['mp_address']);
	}

	return $return;
}

// ID로 봉사모임장소 정보 출력
function get_meeting_place_data($mp_id)
{
	global $mysqli;

	$return = array();
	$sql = "SELECT * FROM " . MEETING_PLACE_TABLE . " WHERE mp_id = {$mp_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0)
		$return = $result->fetch_assoc();

	return $return;
}

// 봉사모임장소 삭제
function delete_meeting_place_data($mp_id)
{
	global $mysqli;

	$return = '<button type="button" class="btn btn-outline-danger"';
	$sql = "SELECT ms_id, ms_week, ms_type, ms_time, mp_name, g_name, ma_id
						 FROM " . MEETING_SCHEDULE_TABLE . " ms LEFT JOIN " . MEETING_PLACE_TABLE . " mp ON ms.mp_id = mp.mp_id LEFT JOIN " . GROUP_TABLE . " g ON ms.g_id = g.g_id
						 WHERE ms.mp_id = '{$mp_id}'
						 ORDER BY ms_week, ms_time, mp_name, g_name, ms_id ASC";
	$result = $mysqli->query($sql);

	if ($result->num_rows > 0) {
		$msg = '해당 모임 장소가 다음의 모임 계획에서 사용되고 있습니다.\\n\\n';
		while ($ms = $result->fetch_assoc()) {
			if ($ms['ma_id'] > 0) { // 회중일정
				$ma_title = get_addschedule_title($ms['ma_id']);
				$msg .= '• [회중 일정] ' . $ma_title . ' > (ID : ' . $ms['ms_id'] . ') ' . get_meeting_data_text($ms['ms_time'], $ms['g_name'], $ms['mp_name']) . '\\n';
			} else {
				$msg .= '• (ID : ' . $ms['ms_id'] . ') ' . get_meeting_data_text($ms['ms_time'], $ms['g_name'], $ms['mp_name']) . '\\n';
			}
		}
		$msg .= '\\n해당 모임 계획의 모임 장소 변경 후, 재시도 해 주시기 바랍니다.';

		$return .= ' onclick="alert(\'' . $msg . '\');"';
	} else {
		$return .= ' id="data_delete" del_id="' . $mp_id . '"';
	}

	$return .= '><i class="bi bi-trash"></i> 삭제</button>';

	return $return;
}

// 모임 아이디 구하기
function get_meeting_id($meeting_date, $ms_id)
{
	global $mysqli;

	// 모임데이터가 생성되있는지 확인 후 없으면 생성
	$sql = "SELECT m_id FROM " . MEETING_TABLE . " WHERE m_date = '{$meeting_date}' AND ms_id = {$ms_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		return $row['m_id'];
	} else {
		$ms = get_meeting_schedule_data($ms_id);
		$mp = get_meeting_place_data($ms['mp_id']);

		// ms_start_time, ms_finish_time이 있는지 확인하고 안전하게 처리
		$ms_start_time = isset($ms['ms_start_time']) ? $ms['ms_start_time'] : '';
		$ms_finish_time = isset($ms['ms_finish_time']) ? $ms['ms_finish_time'] : '';

		$ms_limit = (isset($ms['ms_limit']) && $ms['ms_limit'] !== '') ? $ms['ms_limit'] : 0;
		$sql = "INSERT INTO " . MEETING_TABLE . " (ms_limit, mb_id, m_cancle, m_cancle_reason, m_contents, m_guide, m_date, ms_id, ms_time, ms_week, ms_type, ms_guide, ms_guide2, g_id, mp_id, mp_name, mp_address, m_start_time, m_finish_time)
        VALUES ('{$ms_limit}', '', 0, '', '', '', '{$meeting_date}', '{$ms['ms_id']}', '{$ms['ms_time']}', '{$ms['ms_week']}', '{$ms['ms_type']}', '{$ms['ms_guide']}', '{$ms['ms_guide2']}', '{$ms['g_id']}', '{$mp['mp_id']}', '{$mp['mp_name']}', '{$mp['mp_address']}', '{$ms_start_time}', '{$ms_finish_time}')";
		$mysqli->query($sql);

		return $mysqli->insert_id;
	}
}

// 모임 아이디로 모임 데이터 구하기
function get_meeting_data($m_id)
{
	global $mysqli;

	$return = array();
	$sql = "SELECT * FROM " . MEETING_TABLE . " WHERE m_id = {$m_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0)
		$return = $result->fetch_assoc();

	return $return;
}

// 모임 아이디로 모임 날짜 구하기
function get_meeting_date($m_id)
{
	global $mysqli;

	$return = array();
	$sql = "SELECT m_date FROM " . MEETING_TABLE . " WHERE m_id = {$m_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0)
		$return = $result->fetch_assoc();

	return $return['m_date'];
}

// 모임 참석한 전도인 구하기
function get_member_of_meeting($m_id)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT mb_id FROM " . MEETING_TABLE . " WHERE m_id = {$m_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$return = $row['mb_id'];
	}

	return $return;
}

// 모임 배정된 전도인 구하기
function get_assigned_member_of_meeting($m_id)
{
	global $mysqli;

	$assigned_ministers = array();
	$sql2 = "SELECT tt_assigned FROM " . TERRITORY_TABLE . " WHERE m_id = " . $m_id;
	$result2 = $mysqli->query($sql2);
	if ($result2->num_rows > 0) {
		while ($row2 = $result2->fetch_assoc()) {
			$tt_assigned = explode(',', $row2['tt_assigned']);
			foreach ($tt_assigned as $key => $member_id)
				$assigned_ministers[] = $member_id; // 배정된 전도인에 해당 전도인 추가
		}
	}
	$sql2 = "SELECT tp_assigned FROM " . TELEPHONE_TABLE . " WHERE m_id = " . $m_id;
	$result2 = $mysqli->query($sql2);
	if ($result2->num_rows > 0) {
		while ($row2 = $result2->fetch_assoc()) {
			$tp_assigned = explode(',', $row2['tp_assigned']);
			foreach ($tp_assigned as $key => $member_id)
				$assigned_ministers[] = $member_id; // 배정된 전도인에 해당 전도인 추가
		}
	}
	$sql2 = "SELECT d_assigned FROM " . DISPLAY_TABLE . " WHERE m_id = " . $m_id;
	$result2 = $mysqli->query($sql2);
	if ($result2->num_rows > 0) {
		while ($row2 = $result2->fetch_assoc()) {
			$d_assigned = explode(',', $row2['d_assigned']);
			foreach ($d_assigned as $key => $member_id)
				$assigned_ministers[] = $member_id; // 배정된 전도인에 해당 전도인 추가
		}
	}

	$assigned_ministers = array_unique($assigned_ministers);
	$assigned_ministers = array_filter($assigned_ministers);

	return $assigned_ministers;
}

// 봉사 짝끼리 묶어서 배열로 내보내기
function get_assigned_group($assigned_members, $assigned_group)
{
	$assigned_group = explode(',', $assigned_group);
	$assigned_group = array_filter($assigned_group);
	$assigned_members = explode(',', $assigned_members);
	$assigned_members = array_filter($assigned_members);

	if (empty($assigned_group)) {
		return $assigned_members;
	} else {

		if (count($assigned_group) == 1) {
			$arr = array();
			while ($assigned_members)
				$arr[] = array_splice($assigned_members, 0, $assigned_group[0]);
			return $arr;
		} else {
			$arr = array();
			foreach ($assigned_group as $group)
				$arr[] = array_splice($assigned_members, 0, $group);

			if ($assigned_members) {
				foreach ($assigned_members as $member)
					$arr[] = $member;
			}
			return $arr;
		}
	}
}

// 봉사 짝끼리 묶어서 이름으로 내보내기
function get_assigned_group_name($assigned_members, $assigned_group)
{
	$assigned_group = explode(',', $assigned_group);
	$assigned_group = array_filter($assigned_group);

	if (empty($assigned_group)) {
		return get_assigned_member_name($assigned_members);
	} else {
		$assigned_members = explode(',', $assigned_members);
		$assigned_members = array_filter($assigned_members);

		if (count($assigned_group) == 1) {
			$arr = array();
			while ($assigned_members)
				$arr[] = get_assigned_member_name(implode(',', array_splice($assigned_members, 0, $assigned_group[0])));
			return $arr;
		} else {
			$arr = array();
			foreach ($assigned_group as $group)
				$arr[] = get_assigned_member_name(implode(',', array_splice($assigned_members, 0, $group)));

			if ($assigned_members) {
				foreach ($assigned_members as $member)
					$arr[] = get_member_name($member);
			}
			return $arr;
		}
	}
}

// 지원되어있는 전도인이 더이상 데이터베이스에 남아있지 않을때
function remove_moveout_mb_id($arr)
{
	global $mysqli;

	foreach ($arr as $key => $value) {
		if (!empty($value)) {
			$sql = "select EXISTS (select * from " . MEMBER_TABLE . " where mb_id = " . $value . ") as success;";
			$result = $mysqli->query($sql);
			if ($result->num_rows > 0) {
				$member = $result->fetch_assoc();
				if ($member['success'] == 0)
					unset($arr[$key]);
			}
		}
	}

	return $arr;
}

function get_condition_info($table, $pid)
{
	global $mysqli;

	$return = array(
		'condition' => '',
		'mb_id' => '',
		'mb_name' => '',
		'cdate' => '',
		'content' => '',
		'hm_id' => ''
	);

	if ($table == 'territory') {

		$sql = "SELECT h_condition, mb_id FROM " . HOUSE_TABLE . " WHERE h_id = {$pid}";
		$result = $mysqli->query($sql);
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			$return['condition'] = $row['h_condition'];
			$mb_id = $row['mb_id'];

			if (in_array($row['h_condition'], array(1, 2))) { // 상태가 재방 또는 연구일때
				$return['mb_id'] = $mb_id;
				$return['mb_name'] = get_member_name($mb_id);
			} else {
				$sql = "SELECT m.mb_id,m.mb_name,hm.create_datetime,hm.hm_content,hm.hm_id FROM " . MEMBER_TABLE . " m INNER JOIN " . HOUSE_MEMO_TABLE . " hm ON m.mb_id = hm.mb_id
								WHERE hm.h_id = {$pid} ORDER BY hm.hm_id DESC";
				$result = $mysqli->query($sql);
				if ($result->num_rows > 0) {
					$row = $result->fetch_assoc();
					$return['mb_id'] = $row['mb_id'];
					$return['mb_name'] = $row['mb_name'];
					$return['cdate'] = $row['create_datetime'];
					$return['content'] = $row['hm_content'];
					$return['hm_id'] = $row['hm_id'];
				}
			}
		}

	} elseif ($table == 'telephone') {

		$sql = "SELECT tph_condition, mb_id FROM " . TELEPHONE_HOUSE_TABLE . " WHERE tph_id = {$pid}";
		$result = $mysqli->query($sql);
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			$return['condition'] = $row['tph_condition'];
			$mb_id = $row['mb_id'];

			if (in_array($row['tph_condition'], array(1, 2))) { // 상태가 재방 또는 연구일때
				$return['mb_id'] = $mb_id;
				$return['mb_name'] = get_member_name($mb_id);
			} else {
				$sql = "SELECT m.mb_id,m.mb_name,tphm.create_datetime,tphm.tphm_content,tphm.tphm_id FROM " . MEMBER_TABLE . " m INNER JOIN " . TELEPHONE_HOUSE_MEMO_TABLE . " tphm ON m.mb_id = tphm.mb_id WHERE tphm.tph_id = {$pid} ORDER BY tphm.tphm_id DESC";
				$result = $mysqli->query($sql);
				if ($result->num_rows > 0) {
					$row = $result->fetch_assoc();
					$return['mb_id'] = $row['mb_id'];
					$return['mb_name'] = $row['mb_name'];
					$return['cdate'] = $row['create_datetime'];
					$return['content'] = $row['tphm_content'];
					$return['hm_id'] = $row['tphm_id'];
				}
			}
		}

	}

	return $return;
}

function get_territory_data($tt_id)
{
	global $mysqli;

	$return = array();
	$sql = "SELECT * FROM " . TERRITORY_TABLE . " WHERE tt_id = {$tt_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0)
		$return = $result->fetch_assoc();

	return $return;
}

function get_telephone_data($tp_id)
{
	global $mysqli;

	$return = array();
	$sql = "SELECT * FROM " . TELEPHONE_TABLE . " WHERE tp_id = {$tp_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0)
		$return = $result->fetch_assoc();

	return $return;
}

// 구역 봉사상태 텍스트 출력
function get_status_text($status)
{
	switch ($status) {
		case 'unassigned':
			$status = '미배정';
			break;
		case 'reassign':
			$status = '재배정';
			break;
		case 'absence':
			$status = '부재자 첫 배정';
			break;
		case 'absence_reassign':
			$status = '부재자 재배정';
			break;
		default:
			$status = '첫 배정';
			break;
	}

	return $status;
}

// 구역형태 텍스트 출력
function get_type_text($type)
{
	$c_territory_type = unserialize(TERRITORY_TYPE);

	switch ($type) {
		case '일반':
			$type = $c_territory_type['type_1'][0] ? $c_territory_type['type_1'][0] : '일반';
			break;
		case '아파트':
			$type = $c_territory_type['type_2'][0] ? $c_territory_type['type_2'][0] : '아파트';
			break;
		case '빌라':
			$type = $c_territory_type['type_3'][0] ? $c_territory_type['type_3'][0] : '빌라';
			break;
		case '격지':
			$type = $c_territory_type['type_4'][0] ? $c_territory_type['type_4'][0] : '격지';
			break;
		case '추가1':
			$type = $c_territory_type['type_7'][0] ? $c_territory_type['type_7'][0] : '추가1';
			break;
		case '추가2':
			$type = $c_territory_type['type_8'][0] ? $c_territory_type['type_8'][0] : '추가2';
			break;
	}

	return $type;
}

// 구역형태 옵션 출력
function get_territory_type_options($use, $tt_type)
{
	$c_territory_type_use = unserialize(TERRITORY_TYPE_USE);
	$mb_id = mb_id();

	$type = array(
		'type_1' => '일반',
		'type_2' => '아파트',
		'type_3' => '빌라',
		'type_4' => '격지',
		'type_7' => '추가1',
		'type_8' => '추가2',
	);

	$return = '';
	if ($use == 'search') {
		$return = '<option value="전체">전체</option>';
		foreach ($type as $key => $value) {
			if (is_admin($mb_id)) {
				$return .= '<option value="' . $value . '">' . ($c_territory_type_use[$key] == 'use' ? '' : '[미사용] ') . get_type_text($value) . '</option>';
			} else {
				if (!isset($c_territory_type_use[$key]) || !empty(($c_territory_type_use[$key]))) {
					$return .= '<option value="' . $value . '">' . ($c_territory_type_use[$key] == 'use' ? '' : '[미사용] ') . get_type_text($value) . '</option>';
				}
			}
		}
	} elseif ($use == 'edit') {
		foreach ($type as $key => $value) {
			if (is_admin($mb_id)) {
				$return .= '<option value="' . $value . '" ' . get_selected_text($tt_type, $value) . '>' . ($c_territory_type_use[$key] == 'use' ? '' : '[미사용] ') . get_type_text($value) . '</option>';
			} else {
				if (!isset($c_territory_type_use[$key]) || !empty(($c_territory_type_use[$key]))) {
					$return .= '<option value="' . $value . '" ' . get_selected_text($tt_type, $value) . '>' . ($c_territory_type_use[$key] == 'use' ? '' : '[미사용] ') . get_type_text($value) . '</option>';
				}
			}
		}
	}

	return $return;
}

// 봉사형태 옵션 출력
function get_meeting_schedule_type_options($ms_type)
{
	$c_meeting_schedule_type_use = unserialize(MEETING_SCHEDULE_TYPE_USE);
	$type = array('1', '2', '3', '4', '5', '6');

	$return = '';
	foreach ($type as $value) {
		$type_text = get_meeting_schedule_type_text($value);

		// 미사용 모임형태인지 확인
		if (isset($c_meeting_schedule_type_use[$value]) && $c_meeting_schedule_type_use[$value] !== 'use') {
			$type_text = '[미사용] ' . $type_text;
		}

		$return .= '<option value="' . $value . '" ' . get_selected_text($ms_type, $value) . '>' . $type_text . '</option>';
	}

	return $return;
}

// 봉사형태 텍스트 출력
function get_meeting_schedule_type_text($ms_type)
{
	$c_meeting_schedule_type = unserialize(MEETING_SCHEDULE_TYPE);
	$type = '';

	switch ($ms_type) {
		case '1':
			$type = '호별';
			break;
		case '2':
			$type = '전시대';
			break;
		case '3':
			$type = !empty($c_meeting_schedule_type['3']) ? $c_meeting_schedule_type['3'] : '추가1';
			break;
		case '4':
			$type = !empty($c_meeting_schedule_type['4']) ? $c_meeting_schedule_type['4'] : '추가2';
			break;
		case '5':
			$type = !empty($c_meeting_schedule_type['5']) ? $c_meeting_schedule_type['5'] : '추가3';
			break;
		case '6':
			$type = !empty($c_meeting_schedule_type['6']) ? $c_meeting_schedule_type['6'] : '추가4';
			break;
	}

	return $type;
}

// 신규 공지 등록 시 new 표시 여부
function board_new($mb_id)
{
	global $mysqli;

	$return = '';
	$where = get_member_board_auth($mb_id);
	$where = 'WHERE b_guide IN (' . implode(',  ', $where) . ')';
	$sql = "SELECT * FROM " . BOARD_TABLE . " " . $where . " ORDER BY b_guide, create_datetime DESC";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$read = $row['read_mb'] ? explode(" ", $row['read_mb']) : array();
			if (!(in_array($mb_id, $read))) {
				$auth = explode(" ", $row['b_guide']);
				$return = $auth[0];
				break;
			}
		}
	}
	return $return;
}

function header_menu($active_menu, $active_page)
{
	ob_start(); ?>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup"
			aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
			<svg class="bi" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd"
					d="M2.5 11.5A.5.5 0 0 1 3 11h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4A.5.5 0 0 1 3 7h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4A.5.5 0 0 1 3 3h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z">
				</path>
			</svg>
		</button>
		<div class="collapse navbar-collapse" id="navbarNavAltMarkup">
			<div class="navbar-nav">
				<?php
				$page_arr = array();
				if ($active_menu == 'minister'): // 봉사자
					$page_arr[] = array('나의 봉사', BASE_PATH . '/pages/minister_schedule.php');
					if (RETURNVISIT_USE == 'use'):
						$page_arr[] = array('재방문', BASE_PATH . '/pages/minister_returnvisit.php');
					endif;
					$page_arr[] = array('나의 구역', BASE_PATH . '/pages/minister_territory.php');
					// $page_arr[] = array('전화구역', '/pages/minister_telephone.php');
					// $page_arr[] = array('편지구역', '/pages/minister_letter.php');
					$page_arr[] = array('개인 구역', BASE_PATH . '/pages/minister_personal.php');
					if (MINISTER_STATISTICS_USE == 'use'):
						$page_arr[] = array('나의 통계', BASE_PATH . '/pages/minister_statistics.php');
					endif;
					if (!is_moveout(mb_id())):
						$page_arr[] = array('회중 봉사', BASE_PATH . '/pages/minister_meeting_schedule.php');
					endif;
					$page_arr[] = array('나의 설정', BASE_PATH . '/pages/minister_personal_info.php');
				elseif ($active_menu == 'board'):  // 공지
					$board_title = array('', '봉사자', '파이오니아', '인도자', '봉사의 종', '장로', '관리자', '질문과 대답');
					$board_arr[] = 1;
					$board_arr[] = 7;
					if (in_array(2, get_member_board_auth(mb_id()))):
						$board_arr[] = 2;
					endif;
					if (in_array(3, get_member_board_auth(mb_id()))):
						$board_arr[] = 3;
					endif;
					if (in_array(4, get_member_board_auth(mb_id()))):
						$board_arr[] = 4;
					endif;
					if (in_array(5, get_member_board_auth(mb_id()))):
						$board_arr[] = 5;
					endif;
					if (in_array(6, get_member_board_auth(mb_id()))):
						$board_arr[] = 6;
					endif;
					foreach (array_filter($board_arr) as $value)
						echo '<a class="nav-item nav-link ' . get_active_text($active_page, $value) . '" href="' . BASE_PATH . '/pages/board.php?auth=' . $value . '">' . $board_title[$value] . get_current_text($active_page, $value) . '</a>';
				elseif ($active_menu == 'guide'): // 인도자
					$page_arr[] = array('모임', BASE_PATH . '/pages/guide_history.php');
					$page_arr[] = array('구역', BASE_PATH . '/pages/guide_territory.php');
					if (GUIDE_STATISTICS_USE == 'use'):
						$page_arr[] = array('통계', BASE_PATH . '/pages/guide_statistics.php');
					endif;
				elseif ($active_menu == 'admin'): // 관리자
					$page_arr[] = array('전도인 관리', BASE_PATH . '/pages/admin_member.php');
					$page_arr[] = array('일반 구역 관리', BASE_PATH . '/pages/admin_territory.php');
					$page_arr[] = array('전화 구역 관리', BASE_PATH . '/pages/admin_telephone.php');
					$page_arr[] = array('편지 구역 관리', BASE_PATH . '/pages/admin_letter.php');
					$page_arr[] = array('세대 관리', BASE_PATH . '/pages/admin_house.php');
					$page_arr[] = array('전시대 장소 관리', BASE_PATH . '/pages/admin_display_place.php');
					$page_arr[] = array('집단 관리', BASE_PATH . '/pages/admin_group.php');
					$page_arr[] = array('모임 장소 관리', BASE_PATH . '/pages/admin_meeting_place.php');
					$page_arr[] = array('모임 계획 관리', BASE_PATH . '/pages/admin_meeting.php');
					$page_arr[] = array('회중 일정 관리', BASE_PATH . '/pages/admin_addschedule.php');
					$page_arr[] = array('통계', BASE_PATH . '/pages/admin_statistics.php');
					if (get_member_auth(mb_id()) == 1):
						$page_arr[] = array('설정', BASE_PATH . '/pages/admin_setting.php');
					endif;
				endif;
				foreach (array_filter($page_arr) as $value)
					echo '<a class="nav-item nav-link ' . get_active_text($active_page, $value[0]) . '" href="' . $value[1] . '">' . $value[0] . get_current_text($active_page, $value[0]) . '</a>';
				?>
			</div>
		</div>
		<?php
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
}

function footer_menu($active_page)
{
	$navclass = 3;

	if (DISPLAY_USE == 'use')
		$navclass++;
	if (is_guide(mb_id()) || is_admin(mb_id()))
		$navclass++;
	if (is_admin(mb_id()))
		$navclass++;
	if (is_moveout(mb_id()))
		$navclass = 2;

	ob_start();
	?>
		<footer class="footer nav-<?= $navclass ?>">
			<ul class="nav nav-tabs">
				<li class="nav-item">
					<a class="nav-link <?= get_active_text($active_page, '오늘의 봉사'); ?>" href="<?= BASE_PATH ?>/" title="홈"
						aria-label="홈">
						<svg class="icon-home-off" width="32px" height="32px" viewBox="0 0 32 32" version="1.1"
							xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
							<title>홈</title>
							<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
								<g id="Artboard-Copy" transform="translate(-15.000000, -703.000000)">
									<g id="001_off" transform="translate(15.000000, 703.000000)">
										<rect id="Rectangle" fill-opacity="0" fill="#FFFFFF" x="0" y="0" width="32" height="32">
										</rect>
										<g id="Group" transform="translate(3.000000, 4.000000)" stroke="#111111"
											stroke-linejoin="round" stroke-width="1.5">
											<polyline id="Path" stroke-linecap="round" points="0 13 13 0 26 13"></polyline>
											<polyline id="Path-2" points="19 6.01985765 19 1 23 1 23 10"></polyline>
											<polyline id="Path-3" stroke-linecap="round"
												points="4 12.4977436 4 24.4977436 22 24.4977436 22 12.4977436"></polyline>
											<polyline id="Path-4" points="10 25 10 16 16 16 16 25"></polyline>
										</g>
									</g>
								</g>
							</g>
						</svg>
						<svg class="icon-home-on" width="32px" height="32px" viewBox="0 0 32 32" version="1.1"
							xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
							<title>홈</title>
							<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
								<g id="Artboard-Copy-2" transform="translate(-15.000000, -703.000000)">
									<g id="" transform="translate(15.000000, 703.000000)">
										<rect id="Rectangle" fill-opacity="0" fill="#FFFFFF" x="0" y="0" width="32" height="32">
										</rect>
										<polygon id="Path-6" fill-opacity="0.2" fill="#C9DEFF"
											points="7 28.4977436 13 28.4977436 13 20 19 20 19 28.4977436 25 28.4977436 25 13.3811121 17.5047728 5.48374646 7 16">
										</polygon>
										<g id="Group" transform="translate(3.000000, 4.000000)" stroke="#4A6DA7"
											stroke-linejoin="round" stroke-width="1.5">
											<polyline id="Path" stroke-linecap="round" points="0 13 13 0 26 13"></polyline>
											<polyline id="Path-2" points="19 6.01985765 19 1 23 1 23 10"></polyline>
											<polyline id="Path-3" stroke-linecap="round"
												points="4 12.4977436 4 24.4977436 22 24.4977436 22 12.4977436"></polyline>
											<polyline id="Path-4" points="10 25 10 16 16 16 16 25"></polyline>
										</g>
									</g>
								</g>
							</g>
						</svg>
						<span>홈</span>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= get_active_text($active_page, '봉사자'); ?>"
						href="<?= BASE_PATH ?>/pages/minister_schedule.php" title="봉사자" aria-label="봉사자">
						<svg class="icon-minister-off" width="32px" height="32px" viewBox="0 0 32 32" version="1.1"
							xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
							<title>봉사자</title>
							<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
								<g id="Artboard-Copy" transform="translate(-75.000000, -703.000000)">
									<g id="icon" transform="translate(15.000000, 703.000000)">
										<g id="Group-3" transform="translate(60.000000, 0.000000)">
											<rect id="Rectangle-Copy" fill-opacity="0" fill="#FFFFFF" x="0" y="0" width="32"
												height="32"></rect>
											<g id="Group" transform="translate(4.000000, 5.000000)" stroke="#111111"
												stroke-linejoin="round" stroke-width="1.5">
												<g id="Group-2">
													<polyline id="Path-4"
														transform="translate(12.000000, 13.000000) scale(1, -1) translate(-12.000000, -13.000000) "
														points="10 15 10 11 14 11 14 15"></polyline>
													<polygon id="Path-3" stroke-linecap="round" points="0 3 0 21 24 21 24 3">
													</polygon>
													<polyline id="Path" stroke-linecap="round"
														transform="translate(12.000000, 9.000000) scale(1, -1) translate(-12.000000, -9.000000) "
														points="3 12 12 6 21 12"></polyline>
													<polyline id="Path-4" points="8 3 8 0 16 0 16 3"></polyline>
												</g>
											</g>
										</g>
									</g>
								</g>
							</g>
						</svg>
						<svg class="icon-minister-on <?= get_active_text($active_page, '봉사자'); ?>" width="32px" height="32px"
							viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg"
							xmlns:xlink="http://www.w3.org/1999/xlink">
							<title>봉사자</title>
							<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
								<g id="Artboard-Copy-2" transform="translate(-75.000000, -703.000000)">
									<g id="icon" transform="translate(15.000000, 703.000000)">
										<g id="Group-3" transform="translate(60.000000, 0.000000)">
											<rect id="Rectangle-Copy" fill-opacity="0" fill="#FFFFFF" x="0" y="0" width="32"
												height="32"></rect>
											<g id="Group-2" transform="translate(4.000000, 5.000000)">
												<path
													d="M2,21.0139971 L24,21.0139971 L24,5.01399714 L4,5.01399714 C2.8954305,5.01399714 2,5.90942764 2,7.01399714 L2,21.0139971 L2,21.0139971 Z"
													id="Path-6" fill-opacity="0.2" fill="#C9DEFF"></path>
												<polyline id="Path-4" stroke="#4A6DA7" stroke-width="1.5"
													stroke-linejoin="round"
													transform="translate(12.000000, 13.000000) scale(1, -1) translate(-12.000000, -13.000000) "
													points="10 15 10 11 14 11 14 15"></polyline>
												<polygon id="Path-3" stroke="#4A6DA7" stroke-width="1.5" stroke-linecap="round"
													stroke-linejoin="round" points="0 3 0 21 24 21 24 3"></polygon>
												<polyline id="Path" stroke="#4A6DA7" stroke-width="1.5" stroke-linecap="round"
													stroke-linejoin="round"
													transform="translate(12.000000, 9.000000) scale(1, -1) translate(-12.000000, -9.000000) "
													points="3 12 12 6 21 12"></polyline>
												<polyline id="Path-4" stroke="#4A6DA7" stroke-width="1.5"
													stroke-linejoin="round" points="8 3 8 0 16 0 16 3"></polyline>
											</g>
										</g>
									</g>
								</g>
							</g>
						</svg>
						<span>봉사자</span>
					</a>
				</li>
				<?php if (DISPLAY_USE == 'use'): // 전시대 사용 ?>
						<?php if ((get_member_display(mb_id()) == 0 || get_member_display(mb_id()) == '') && !is_moveout(mb_id())): // 전시대 참여가 가능하고,전출전도인이 아니면 ?>
								<li class="nav-item">
									<a class="nav-link <?= get_active_text($active_page, '전시대'); ?>" href="<?= BASE_PATH ?>/pages/meeting.php"
										title="전시대" aria-label="전시대">
										<svg class="icon-display-off" width="32px" height="32px" viewBox="0 0 32 32" version="1.1"
											xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
											<title>전시대</title>
											<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
												<g id="Artboard-Copy" transform="translate(-135.000000, -703.000000)">
													<g id="icon" transform="translate(15.000000, 703.000000)">
														<g id="Group-3" transform="translate(120.000000, 0.000000)">
															<rect id="Rectangle-Copy" fill-opacity="0" fill="#FFFFFF" x="0" y="0" width="32"
																height="32"></rect>
															<g id="Group" transform="translate(7.000000, 2.000000)" stroke="#111111"
																stroke-linejoin="round" stroke-width="1.5">
																<g id="Group-2">
																	<path
																		d="M1,28 L1,26.5 C1,25.6715729 1.67157288,25 2.5,25 C3.32842712,25 4,25.6715729 4,26.5 L4,28 L4,28"
																		id="Path-4"
																		transform="translate(2.500000, 26.500000) scale(1, -1) translate(-2.500000, -26.500000) ">
																	</path>
																	<path
																		d="M14,28 L14,26.5 C14,25.6715729 14.6715729,25 15.5,25 C16.3284271,25 17,25.6715729 17,26.5 L17,28 L17,28"
																		id="Path-4-Copy"
																		transform="translate(15.500000, 26.500000) scale(1, -1) translate(-15.500000, -26.500000) ">
																	</path>
																	<polygon id="Path-3" stroke-linecap="round" points="0 4 0 25 18 25 18 4">
																	</polygon>
																	<polyline id="Path-4" stroke-linecap="round" points="3 12 3 8 15 8 15 12">
																	</polyline>
																	<polyline id="Path-4-Copy-2" stroke-linecap="round"
																		points="3 19 3 15 15 15 15 19"></polyline>
																	<polyline id="Path-4" points="6 4 6 0 12 0 12 4"></polyline>
																</g>
															</g>
														</g>
													</g>
												</g>
											</g>
										</svg>
										<svg class="icon-display-on" width="32px" height="32px" viewBox="0 0 32 32" version="1.1"
											xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
											<title>전시대</title>
											<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
												<g id="Artboard-Copy-2" transform="translate(-135.000000, -703.000000)">
													<g id="icon" transform="translate(15.000000, 703.000000)">
														<g id="Group-3" transform="translate(120.000000, 0.000000)">
															<rect id="Rectangle-Copy" fill-opacity="0" fill="#FFFFFF" x="0" y="0" width="32"
																height="32"></rect>
															<g id="Group-2" transform="translate(7.000000, 2.000000)">
																<path
																	d="M2.3,25 L17.3,25 L17.3,7 L4.3,7 C3.1954305,7 2.3,7.8954305 2.3,9 L2.3,25 L2.3,25 Z"
																	id="Path-6" fill-opacity="0.2" fill="#C9DEFF"></path>
																<path
																	d="M1,28 L1,26.5 C1,25.6715729 1.67157288,25 2.5,25 C3.32842712,25 4,25.6715729 4,26.5 L4,28 L4,28"
																	id="Path-4" stroke="#4A6DA7" stroke-width="1.5" stroke-linejoin="round"
																	transform="translate(2.500000, 26.500000) scale(1, -1) translate(-2.500000, -26.500000) ">
																</path>
																<path
																	d="M14,28 L14,26.5 C14,25.6715729 14.6715729,25 15.5,25 C16.3284271,25 17,25.6715729 17,26.5 L17,28 L17,28"
																	id="Path-4-Copy" stroke="#4A6DA7" stroke-width="1.5" stroke-linejoin="round"
																	transform="translate(15.500000, 26.500000) scale(1, -1) translate(-15.500000, -26.500000) ">
																</path>
																<polygon id="Path-3" stroke="#4A6DA7" stroke-width="1.5" stroke-linecap="round"
																	stroke-linejoin="round" points="0 4 0 25 18 25 18 4"></polygon>
																<polyline id="Path-4" stroke="#4A6DA7" stroke-width="1.5" stroke-linecap="round"
																	stroke-linejoin="round" points="3 11 3 7 15 7 15 11"></polyline>
																<polyline id="Path-4-Copy-2" stroke="#4A6DA7" stroke-width="1.5"
																	stroke-linecap="round" stroke-linejoin="round"
																	points="3 18.6363636 3 14.6363636 15 14.6363636 15 18.6363636"></polyline>
																<polyline id="Path-4" stroke="#4A6DA7" stroke-width="1.5"
																	stroke-linejoin="round" points="6 4 6 0 12 0 12 4"></polyline>
															</g>
														</g>
													</g>
												</g>
											</g>
										</svg>
										<span>전시대</span>
									</a>
								</li>
						<?php endif; ?>
				<?php endif; ?>
				<?php if (!is_moveout(mb_id())): // 전출전도인이 아니면 ?>
						<li class="nav-item">
							<a class="nav-link <?= get_active_text($active_page, '공지'); ?>" href="<?= BASE_PATH ?>/pages/board.php"
								title="공지사항" aria-label="공지사항">
								<svg class="icon-board-off" width="32px" height="32px" viewBox="0 0 32 32" version="1.1"
									xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
									<title>공지사항</title>
									<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
										<g id="Artboard-Copy" transform="translate(-195.000000, -703.000000)">
											<g id="icon" transform="translate(15.000000, 703.000000)">
												<g id="004_off" transform="translate(180.000000, 0.000000)">
													<rect id="Rectangle" fill-opacity="0" fill="#FFFFFF" x="0" y="0" width="32"
														height="32"></rect>
													<g id="Group" transform="translate(6.000000, 6.000000)" stroke="#111111"
														stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
														<polyline id="Path-3"
															transform="translate(1.000000, 10.000000) rotate(90.000000) translate(-1.000000, -10.000000) "
															points="-2 9 -2 11 4 11 4 9"></polyline>
														<line x1="20" y1="9" x2="20" y2="11" id="Path-3-Copy-2"
															transform="translate(20.000000, 10.000000) rotate(90.000000) translate(-20.000000, -10.000000) ">
														</line>
														<line x1="18.5" y1="4.5" x2="19.5" y2="6.5" id="Path-3-Copy-3"
															transform="translate(19.000000, 5.500000) rotate(90.000000) translate(-19.000000, -5.500000) ">
														</line>
														<line x1="18.5" y1="13.5" x2="19.5" y2="15.5" id="Path-3-Copy-4"
															transform="translate(19.000000, 14.500000) scale(-1, 1) rotate(90.000000) translate(-19.000000, -14.500000) ">
														</line>
														<polyline id="Path-3-Copy"
															transform="translate(8.500000, 10.000000) rotate(90.000000) translate(-8.500000, -10.000000) "
															points="15.5975395 4.5 -1.5 4.5 3.5 15.5 13.5 15.5 18.5 4.5"></polyline>
													</g>
												</g>
											</g>
										</g>
									</g>
								</svg>
								<svg class="icon-board-on" width="32px" height="32px" viewBox="0 0 32 32" version="1.1"
									xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
									<title>공지사항</title>
									<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
										<g id="Artboard-Copy-2" transform="translate(-195.000000, -703.000000)">
											<g id="icon" transform="translate(15.000000, 703.000000)">
												<g id="004_on" transform="translate(180.000000, 0.000000)">
													<rect id="Rectangle" fill-opacity="0" fill="#FFFFFF" x="0" y="0" width="32"
														height="32"></rect>
													<g id="Group" transform="translate(6.000000, 6.000000)">
														<path
															d="M3,14.6363636 L14,20 L14,2.15463772 L4.19377532,6.47415692 C3.46826423,6.79373547 3,7.51168002 3,8.30445792 L3,14.6363636 L3,14.6363636 Z"
															id="Path-6" fill-opacity="0.2" fill="#C9DEFF"></path>
														<polyline id="Path-3" stroke="#4A6DA7" stroke-width="1.5" stroke-linecap="round"
															stroke-linejoin="round"
															transform="translate(1.000000, 10.000000) rotate(90.000000) translate(-1.000000, -10.000000) "
															points="-2 9 -2 11 4 11 4 9"></polyline>
														<line x1="20" y1="9" x2="20" y2="11" id="Path-3-Copy-2" stroke="#4A6DA7"
															stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
															transform="translate(20.000000, 10.000000) rotate(90.000000) translate(-20.000000, -10.000000) ">
														</line>
														<line x1="18.5" y1="4.5" x2="19.5" y2="6.5" id="Path-3-Copy-3" stroke="#4A6DA7"
															stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
															transform="translate(19.000000, 5.500000) rotate(90.000000) translate(-19.000000, -5.500000) ">
														</line>
														<line x1="18.5" y1="13.5" x2="19.5" y2="15.5" id="Path-3-Copy-4"
															stroke="#4A6DA7" stroke-width="1.5" stroke-linecap="round"
															stroke-linejoin="round"
															transform="translate(19.000000, 14.500000) scale(-1, 1) rotate(90.000000) translate(-19.000000, -14.500000) ">
														</line>
														<polyline id="Path-3-Copy" stroke="#4A6DA7" stroke-width="1.5"
															stroke-linecap="round" stroke-linejoin="round"
															transform="translate(8.500000, 10.000000) rotate(90.000000) translate(-8.500000, -10.000000) "
															points="15.5975395 4.5 -1.5 4.5 3.5 15.5 13.5 15.5 18.5 4.5"></polyline>
													</g>
												</g>
											</g>
										</g>
									</g>
								</svg>
								<span>공지사항</span>
							</a>
						</li>
				<?php endif; ?>
				<?php
				if ((is_guide(mb_id()) || is_admin(mb_id())) && !is_moveout(mb_id())):
					?>
						<li class="nav-item">
							<a class="nav-link <?= get_active_text($active_page, '인도자'); ?>"
								href="<?= BASE_PATH ?>/pages/guide_history.php" title="인도자" aria-label="인도자">
								<svg class="icon-guide-off" width="32px" height="32px" viewBox="0 0 32 32" version="1.1"
									xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
									<title>인도자</title>
									<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
										<g id="Artboard-Copy" transform="translate(-255.000000, -703.000000)">
											<g id="icon" transform="translate(15.000000, 703.000000)">
												<g id="005_off" transform="translate(240.000000, 0.000000)">
													<rect id="Rectangle-Copy-4" fill-opacity="0" fill="#FFFFFF" x="0" y="0" width="32"
														height="32"></rect>
													<path
														d="M6.97799789,28 L6,21.7190187 L13.1142801,18.0681078 L13.1142801,15.4132012 L10.7637448,12.122038 L10.7637448,7.74091542 C11.8884394,5.24697181 13.486547,4 15.5580674,4 C18.6653481,4 20.2302418,7.74091542 20.2302418,7.74091542 C20.2302418,7.74091542 20.2302418,9.20128961 20.2302418,12.122038 L17.8131939,15.4132012 L17.8131939,18.0681078 L26,21.7190187 L25.0220021,28"
														id="Path-5" stroke="#111111" stroke-width="1.5" stroke-linecap="round"
														stroke-linejoin="round"></path>
													<polyline id="Path-3-Copy-4" stroke="#111111" stroke-width="1.5"
														stroke-linecap="round" stroke-linejoin="round"
														transform="translate(14.570231, 24.184230) scale(-1, 1) rotate(90.000000) translate(-14.570231, -24.184230) "
														points="10.7544609 22.7544609 12.9258856 25.6139997 18.3860003 25.6139997">
													</polyline>
													<line x1="16.3997042" y1="18.9955503" x2="19.4395895" y2="22.4955503"
														id="Path-3-Copy-5" stroke="#111111" stroke-width="1.5" stroke-linecap="round"
														stroke-linejoin="round"
														transform="translate(17.697627, 20.797923) rotate(90.000000) translate(-17.697627, -20.797923) ">
													</line>
												</g>
											</g>
										</g>
									</g>
								</svg>
								<svg class="icon-guide-on" width="32px" height="32px" viewBox="0 0 32 32" version="1.1"
									xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
									<title>인도자</title>
									<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
										<g id="Artboard-Copy-2" transform="translate(-255.000000, -703.000000)">
											<g id="icon" transform="translate(15.000000, 703.000000)">
												<g id="005_on" transform="translate(240.000000, 0.000000)">
													<rect id="Rectangle-Copy-4" fill-opacity="0" fill="#FFFFFF" x="0" y="0" width="32"
														height="32"></rect>
													<path
														d="M7,28 L25,28 L26,21.8495492 L19.9508565,19.5 L16,22.7187021 L13.1404612,20.4195466 L9.05378133,22.3014939 C8.48330209,22.5642039 8.06936348,23.0796362 7.93597773,23.6933717 L7,28 L7,28 Z"
														id="Path-6" fill-opacity="0.2" fill="#C9DEFF"></path>
													<path
														d="M6.97799789,28 L6,21.7190187 L13.1142801,18.0681078 L13.1142801,15.4132012 L10.7637448,12.122038 L10.7637448,7.74091542 C11.8884394,5.24697181 13.486547,4 15.5580674,4 C18.6653481,4 20.2302418,7.74091542 20.2302418,7.74091542 C20.2302418,7.74091542 20.2302418,9.20128961 20.2302418,12.122038 L17.8131939,15.4132012 L17.8131939,18.0681078 L26,21.7190187 L25.0220021,28"
														id="Path-5" stroke="#4A6DA7" stroke-width="1.5" stroke-linecap="round"
														stroke-linejoin="round"></path>
													<polyline id="Path-3-Copy-4" stroke="#4A6DA7" stroke-width="1.5"
														stroke-linecap="round" stroke-linejoin="round"
														transform="translate(14.570231, 24.184230) scale(-1, 1) rotate(90.000000) translate(-14.570231, -24.184230) "
														points="10.7544609 22.7544609 12.9258856 25.6139997 18.3860003 25.6139997">
													</polyline>
													<line x1="16.3997042" y1="18.9955503" x2="19.4395895" y2="22.4955503"
														id="Path-3-Copy-5" stroke="#4A6DA7" stroke-width="1.5" stroke-linecap="round"
														stroke-linejoin="round"
														transform="translate(17.697627, 20.797923) rotate(90.000000) translate(-17.697627, -20.797923) ">
													</line>
												</g>
											</g>
										</g>
									</g>
								</svg>
								<span>인도자</span>
							</a>
						</li>
				<?php endif; ?>
				<?php if (is_admin(mb_id()) && !is_moveout(mb_id())): ?>
						<li class="nav-item">
							<a class="nav-link <?= get_active_text($active_page, '관리자'); ?>"
								href="<?= BASE_PATH ?>/pages/admin_member.php" title="관리자" aria-label="관리자">
								<svg class="icon-admin-off" width="32px" height="32px" viewBox="0 0 32 32" version="1.1"
									xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
									<title>관리자</title>
									<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
										<g id="Artboard-Copy" transform="translate(-315.000000, -703.000000)">
											<g id="icon" transform="translate(15.000000, 703.000000)">
												<g id="006_off" transform="translate(300.000000, 0.000000)">
													<rect id="Rectangle-Copy-5" fill-opacity="0" fill="#FFFFFF" x="0" y="0" width="32"
														height="32"></rect>
													<g id="Group-4" transform="translate(5.000000, 5.000000)" stroke="#111111"
														stroke-width="1.5">
														<path
															d="M11,0.75 C11.4253464,0.75 11.8446511,0.776338265 12.2564226,0.827531165 L13.2157621,3.30349893 C14.1130274,3.56190674 14.9465732,3.97486393 15.6871901,4.51204482 L18.1797212,3.73526786 C18.7838743,4.33787044 19.315355,5.01576967 19.7595784,5.75403291 L18.4640209,8.05078438 C18.8110322,8.89695018 19.0232765,9.81469468 19.0738211,10.7761811 L21.2191117,12.27247 C21.1335861,13.1516942 20.9414855,13.9989949 20.6560397,14.8007341 L18.083407,15.2014646 C17.6366693,16.0256955 17.0552729,16.7636229 16.3693514,17.3843558 L16.559383,20.0338956 C15.8573454,20.4958572 15.0964309,20.8729625 14.2905779,21.1508022 L12.3674511,19.3406202 C11.9292202,19.4157891 11.4789801,19.4548717 11.0197734,19.4548717 C10.549189,19.4548717 10.0880211,19.4138281 9.63954441,19.3349669 L7.70952567,21.1508379 C6.90356386,20.8729705 6.14255169,20.4958111 5.440433,20.0337746 L5.63175872,17.3493494 C4.96447506,16.7377196 4.39779526,16.0144522 3.9599463,15.2084793 L1.34397427,14.8007733 C1.05851376,13.9990028 0.86640583,13.1516667 0.780881957,12.2724049 L2.96731105,10.7469909 C3.01973096,9.81129525 3.22531752,8.91737079 3.5591796,8.09094494 L2.24095933,5.75313933 C2.68506267,5.01524138 3.21634248,4.33766169 3.82023285,3.73531371 L6.340084,4.52096391 C7.07104958,3.98869034 7.89294501,3.57759651 8.77757501,3.31696044 L9.74336335,0.827557773 C10.1552037,0.776347391 10.5745799,0.75 11,0.75 Z"
															id="Combined-Shape"></path>
														<path
															d="M11.8384558,14.9119815 C12.2356664,14.8272595 12.6110434,14.6835728 12.9549863,14.4905222 C14.1753689,13.8055374 15,12.4990702 15,11 C15,8.790861 13.209139,7 11,7 C8.790861,7 7,8.790861 7,11 C7,11.5994585 7.13186637,12.168118 7.3681741,12.6785534 C7.7254563,13.4502992 8.32148632,14.0889476 9.06147749,14.499712"
															id="Oval" stroke-linecap="round"
															transform="translate(11.000000, 10.955991) rotate(30.000000) translate(-11.000000, -10.955991) ">
														</path>
													</g>
												</g>
											</g>
										</g>
									</g>
								</svg>
								<svg class="icon-admin-on" width="32px" height="32px" viewBox="0 0 32 32" version="1.1"
									xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
									<title>관리자</title>
									<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
										<g id="Artboard-Copy-2" transform="translate(-315.000000, -703.000000)">
											<g id="icon" transform="translate(15.000000, 703.000000)">
												<g id="006_on" transform="translate(300.000000, 0.000000)">
													<rect id="Rectangle-Copy-5" fill-opacity="0" fill="#FFFFFF" x="0" y="0" width="32"
														height="32"></rect>
													<g id="Group-4" transform="translate(5.000000, 5.000000)">
														<circle id="Oval" fill-opacity="0.2" fill="#C9DEFF" cx="11" cy="11" r="6">
														</circle>
														<path
															d="M11,0.75 C11.4253464,0.75 11.8446511,0.776338265 12.2564226,0.827531165 L13.2157621,3.30349893 C14.1130274,3.56190674 14.9465732,3.97486393 15.6871901,4.51204482 L18.1797212,3.73526786 C18.7838743,4.33787044 19.315355,5.01576967 19.7595784,5.75403291 L18.4640209,8.05078438 C18.8110322,8.89695018 19.0232765,9.81469468 19.0738211,10.7761811 L21.2191117,12.27247 C21.1335861,13.1516942 20.9414855,13.9989949 20.6560397,14.8007341 L18.083407,15.2014646 C17.6366693,16.0256955 17.0552729,16.7636229 16.3693514,17.3843558 L16.559383,20.0338956 C15.8573454,20.4958572 15.0964309,20.8729625 14.2905779,21.1508022 L12.3674511,19.3406202 C11.9292202,19.4157891 11.4789801,19.4548717 11.0197734,19.4548717 C10.549189,19.4548717 10.0880211,19.4138281 9.63954441,19.3349669 L7.70952567,21.1508379 C6.90356386,20.8729705 6.14255169,20.4958111 5.440433,20.0337746 L5.63175872,17.3493494 C4.96447506,16.7377196 4.39779526,16.0144522 3.9599463,15.2084793 L1.34397427,14.8007733 C1.05851376,13.9990028 0.86640583,13.1516667 0.780881957,12.2724049 L2.96731105,10.7469909 C3.01973096,9.81129525 3.22531752,8.91737079 3.5591796,8.09094494 L2.24095933,5.75313933 C2.68506267,5.01524138 3.21634248,4.33766169 3.82023285,3.73531371 L6.340084,4.52096391 C7.07104958,3.98869034 7.89294501,3.57759651 8.77757501,3.31696044 L9.74336335,0.827557773 C10.1552037,0.776347391 10.5745799,0.75 11,0.75 Z"
															id="Combined-Shape" stroke="#4A6DA7" stroke-width="1.5"></path>
														<path
															d="M11.8384558,14.9119815 C12.2356664,14.8272595 12.6110434,14.6835728 12.9549863,14.4905222 C14.1753689,13.8055374 15,12.4990702 15,11 C15,8.790861 13.209139,7 11,7 C8.790861,7 7,8.790861 7,11 C7,11.5994585 7.13186637,12.168118 7.3681741,12.6785534 C7.7254563,13.4502992 8.32148632,14.0889476 9.06147749,14.499712"
															id="Oval" stroke="#4A6DA7" stroke-width="1.5" stroke-linecap="round"
															transform="translate(11.000000, 10.955991) rotate(30.000000) translate(-11.000000, -10.955991) ">
														</path>
													</g>
												</g>
											</g>
										</g>
									</g>
								</svg>
								<span>관리자</span>
							</a>
						</li>
				<?php endif; ?>
			</ul>
		</footer>
		<?php
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
}

function kakao_menu($address)
{
	ob_start(); ?>
		<button type="button" class="btn btn-outline-info btn-sm ml-1" data-toggle="dropdown" aria-haspopup="true"
			aria-expanded="false">
			<i class="bi bi-geo-alt"></i>
		</button>
		<div class="dropdown-menu">
			<button class="dropdown-item" type="button"
				onclick="daum_roadview('<?= DEFAULT_ADDRESS . ' ' . $address ?>')">로드뷰</button>
			<button class="dropdown-item" type="button"
				onclick="kakao_navi('<?= DEFAULT_ADDRESS . ' ' . $address ?>','<?= $address ?>');">길찾기</button>
			<button class="dropdown-item" type="button"
				onclick="map_view('house','<?= DEFAULT_ADDRESS . ' ' . $address ?>')">지도보기</button>
		</div>
		<?php
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
}


function site_work()
{
	global $mysqli;

	if (!RETURN_VISIT_EXPIRATION || RETURN_VISIT_EXPIRATION > 0) {
		$strtotime = RETURN_VISIT_EXPIRATION == '' ? strtotime("-3 month") : strtotime("-" . RETURN_VISIT_EXPIRATION . " month");

		// 호별구역 재방문 자동중단
		$sql = "SELECT mb_id, h_id FROM " . HOUSE_TABLE . " WHERE mb_id <> 0";
		$result = $mysqli->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$sql2 = "SELECT rv_datetime FROM " . RETURN_VISIT_TABLE . " WHERE h_id = {$row['h_id']} ORDER BY rv_datetime DESC LIMIT 1";
				$result2 = $mysqli->query($sql2);
				if ($result2->num_rows > 0) {
					while ($row2 = $result2->fetch_assoc()) {
						// 최종방문일자가 [3]개월 이전일떄
						if ($row2['rv_datetime'] < date("Y-m-d H:i:s", $strtotime)) {
							$sql = "UPDATE " . HOUSE_TABLE . " SET mb_id = 0, h_condition = '' WHERE h_id = {$row['h_id']}";
							$mysqli->query($sql);

							$sql = "DELETE FROM " . RETURN_VISIT_TABLE . " WHERE h_id = {$row['h_id']}";
							$mysqli->query($sql);
							insert_work_log('호별구역 재방문 자동중단  mb_id: ' . $row['mb_id'] . ' h_id: ' . $row['h_id']);
						}
					}
				}
			}
		}

		// 전화구역 재방문 자동중단
		$sql = "SELECT tph_id FROM " . TELEPHONE_HOUSE_TABLE . " WHERE mb_id <> 0";
		$result = $mysqli->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$sql2 = "SELECT tprv_datetime FROM " . TELEPHONE_RETURN_VISIT_TABLE . " WHERE tph_id = {$row['tph_id']} ORDER BY tprv_datetime DESC LIMIT 1";
				$result2 = $mysqli->query($sql2);
				if ($result2->num_rows > 0) {
					while ($row2 = $result2->fetch_assoc()) {
						// 최종방문일자가 3개월 이전일떄
						if ($row2['tprv_datetime'] < date("Y-m-d H:i:s", $strtotime)) {
							$sql = "UPDATE " . TELEPHONE_HOUSE_TABLE . " SET mb_id = 0, tph_condition = '' WHERE tph_id = {$row['tph_id']}";
							$mysqli->query($sql);

							$sql = "DELETE FROM " . TELEPHONE_RETURN_VISIT_TABLE . " WHERE tph_id = {$row['tph_id']}";
							$mysqli->query($sql);
							insert_work_log('전화구역 재방문 자동중단  mb_id: ' . $row['mb_id'] . ' tph_id: ' . $row['tph_id']);
						}
					}
				}
			}
		}
	}

}

// 호별구역카드 비고 구하기
function get_territory_memo($tt_id)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT tt_memo FROM " . TERRITORY_TABLE . " WHERE tt_id = {$tt_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$return = $row['tt_memo'];
	}

	return $return;
}

// 전화구역카드 비고 구하기
function get_telephone_memo($tp_id)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT tp_memo FROM " . TELEPHONE_TABLE . " WHERE tp_id = {$tp_id}";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$return = $row['tp_memo'];
	}

	return $return;
}

// 호별 구역카드 리셋
// 중요: 이 함수는 봉사기록(TERRITORY_RECORD_TABLE)을 삭제하지 않으며, 
// 리셋 전의 봉사 정보를 봉사기록 테이블에 저장합니다.
function territory_reset($tt_id, $new_status = '', $record_m_id = 0)
{
	global $mysqli;

	// 입력값 검증
	if (!is_numeric($tt_id) || $tt_id <= 0) {
		return false;
	}

	if (!is_numeric($record_m_id) || $record_m_id < 0) {
		$record_m_id = 0;
	}

	$territory = new Territory($mysqli);

	if ($tt_id) {
		$current_datetime = date("Y-m-d H:i:s"); // 현재 datetime

		// 개인구역을 제외한 구역 추출
		$tt_sql = "SELECT tt_id, tt_assigned, tt_assigned_date, tt_assigned_group, tt_start_date, tt_end_date, tt_status, mb_id, m_id FROM " . TERRITORY_TABLE . " WHERE tt_id = {$tt_id}";
		$tt_result = $mysqli->query($tt_sql);
		if ($tt_result->num_rows > 0) {
			$tt_row = $tt_result->fetch_assoc();
			$tt_start_date = $tt_row['tt_start_date'];
			$tt_end_date = $tt_row['tt_end_date'];
			$tt_assigned_date = $tt_row['tt_assigned_date'];
			$tt_status = $tt_row['tt_status'];
			$tt_assigned_group = $tt_row['tt_assigned_group'];
			$m_id = $tt_row['m_id'];

			// 봉사를 시작했을떄만 기록이 남도록
			// 봉사기록은 TERRITORY_RECORD_TABLE에 저장되며, 리셋 후에도 보존됩니다.
			if (!empty_date($tt_start_date)) {

				// 배정된 전도인 아이디를 이름으로 일괄 변경해서 변수에 담음
				$ttr_assigned = '';
				if ($tt_row['tt_assigned']) {
					$ttr_assigned = array();
					$tt_assigned = explode(',', $tt_row['tt_assigned']);
					// explode()는 항상 배열을 반환하므로 배열 체크 불필요
					foreach ($tt_assigned as $mb_id) {
						$member_name = get_member_name($mb_id);
						$ttr_assigned[] = $member_name ? $member_name : $mb_id;
					}
					$ttr_assigned = implode(',', $ttr_assigned);
				}

				// 개인구역 전도인 아이디를 이름으로 변경
				$ttr_mb_name = $tt_row['mb_id'] ? get_member_name($tt_row['mb_id']) : '';

				// 트랜잭션 시작하여 중복 저장 완전 방지
				$mysqli->begin_transaction();

				try {
					// 중복 기록 방지: 동일 방문(핵심 필드 기준)이 이미 있는지 확인
					// record_m_id도 포함하여 더 엄격하게 체크
					$dup_sql = "SELECT COUNT(*) FROM " . TERRITORY_RECORD_TABLE . " 
						WHERE tt_id = ? 
						AND ttr_assigned_num = ? 
						AND ttr_assigned_date = ? 
						AND ttr_assigned_group = ? 
						AND ttr_start_date = ? 
						AND ttr_end_date = ? 
						AND ttr_status = ? 
						AND m_id = ? 
						AND record_m_id = ?
						FOR UPDATE";
					if ($stmt_dup = $mysqli->prepare($dup_sql)) {
						$assigned_num = $tt_row['tt_assigned'];
						$stmt_dup->bind_param(
							"sssssssii",
							$tt_id,
							$assigned_num,
							$tt_assigned_date,
							$tt_assigned_group,
							$tt_start_date,
							$tt_end_date,
							$tt_status,
							$m_id,
							$record_m_id
						);
						$stmt_dup->execute();
						$stmt_dup->bind_result($dup_count);
						$stmt_dup->fetch();
						$stmt_dup->close();
					} else {
						$mysqli->rollback();
						return false;
					}

					if ((int) $dup_count === 0) {
						// TERRITORY_RECORD_TABLE 에 기록 추가 (Prepared Statement)
						$ins_sql = "INSERT INTO " . TERRITORY_RECORD_TABLE . " 
							(tt_id, ttr_assigned, ttr_assigned_num, ttr_assigned_date, ttr_assigned_group, create_datetime, update_datetime, ttr_start_date, ttr_end_date, ttr_status, ttr_mb_name, m_id, record_m_id)
							VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
						if ($stmt_ins = $mysqli->prepare($ins_sql)) {
							$stmt_ins->bind_param(
								"issssssssssii",
								$tt_id,
								$ttr_assigned,
								$tt_row['tt_assigned'],
								$tt_assigned_date,
								$tt_assigned_group,
								$current_datetime,
								$current_datetime,
								$tt_start_date,
								$tt_end_date,
								$tt_status,
								$ttr_mb_name,
								$m_id,
								$record_m_id
							);
							$stmt_ins->execute();
							$stmt_ins->close();
						} else {
							$mysqli->rollback();
							return false;
						}
					}

					// 트랜잭션 커밋
					$mysqli->commit();
				} catch (Exception $e) {
					// 에러 발생 시 롤백
					$mysqli->rollback();
					return false;
				}

			}

			// 구역 배정정보 리셋 *단 개인구역(mb_id 에 값이 존재시)일때는 리셋하지 않는다
			$updateData = array(
				'tt_assigned' => '',
				'tt_assigned_date' => '0000-00-00',
				'tt_assigned_group' => '',
				'tt_start_date' => '0000-00-00',
				'tt_end_date' => '0000-00-00',
				'tt_status' => $new_status,
				'm_id' => 0,
			);

			try {
				$updateId = $territory->update($tt_id, $updateData);
			} catch (Exception $e) {
				return false;
			}
		}

	}

	return true;
}

// 호별구역카드 세대 리셋
function territory_house_reset($tt_id)
{
	global $mysqli;

	if ($tt_id) {
		// 집 방문/부재 체크 리셋
		$sql = "UPDATE " . HOUSE_TABLE . " SET h_visit = '', h_visit_old = '' WHERE tt_id = {$tt_id}";
		$mysqli->query($sql);
	}
}

// 호별구역카드 세대 방문정보 업데이트
function territory_house_update($tt_id, $restore = '', $new_status = '', $old_status = null, $is_completed = false)
{
	global $mysqli;

	// 세대 방문정보 복구하기 (이전으로 되돌려놓기)
	if ($restore == 'restore') {
		// h_visit_old가 있는 경우에만 복구 (비어있으면 체크박스가 비워지지 않도록)
		$sql = "UPDATE " . HOUSE_TABLE . " SET h_visit = h_visit_old, h_visit_old = '' WHERE tt_id = {$tt_id} AND h_visit_old != ''";
		$mysqli->query($sql);
	} else { // 세대 방문정보 업데이트
		// 이전 상태가 전달되지 않은 경우(null)에만 DB에서 조회 (빈 문자열도 유효한 값)
		if ($old_status === null) {
			$sql = "SELECT tt_status FROM " . TERRITORY_TABLE . " WHERE tt_id = {$tt_id}";
			$result = $mysqli->query($sql);
			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				$old_status = $row['tt_status'];
			} else {
				$old_status = '';
			}
		}

		// 상태가 실제로 변경되었는지 확인
		// $old_status가 빈 문자열이거나 'reassign'인 경우 absence가 아님
		$old_is_absence = !empty($old_status) && strpos($old_status, 'absence') !== false;
		$new_is_absence = !empty($new_status) && strpos($new_status, 'absence') !== false;

		// 완료 여부가 전달되지 않은 경우 DB에서 조회
		if (func_num_args() < 5) {
			$sql = "SELECT tt_end_date FROM " . TERRITORY_TABLE . " WHERE tt_id = {$tt_id}";
			$result = $mysqli->query($sql);
			$is_completed = false;
			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				$is_completed = !empty($row['tt_end_date']) && $row['tt_end_date'] != '0000-00-00';
			}
		}

		// 전체(absence 없음) → 부재(absence 포함)로 변경된 경우
		if (!$old_is_absence && $new_is_absence) {
			$sql = "UPDATE " . HOUSE_TABLE . " SET h_visit_old = h_visit WHERE tt_id = {$tt_id}";
			$mysqli->query($sql);

			// 전체에서 부재로 변경된 경우에만 부재 체크박스 비움
			$sql = "UPDATE " . HOUSE_TABLE . " SET h_visit = '' WHERE tt_id = {$tt_id} AND h_visit = 'N'";
			$mysqli->query($sql);
		}
		// 부재 완료 상태에서 재배정하는 경우 (부재 → 부재 재배정, 완료 상태)
		elseif ($old_is_absence && $new_is_absence && $is_completed) {
			// 만남 집 비활성화: 전체일 때 만남(h_visit_old='Y') + 부재일 때 만남(h_visit='Y') 모두 포함
			// h_visit='Y'이거나 h_visit_old='Y'인 경우 모두 h_visit_old='Y'로 설정
			$sql = "UPDATE " . HOUSE_TABLE . " SET h_visit_old = 'Y' WHERE tt_id = {$tt_id} AND (h_visit = 'Y' OR h_visit_old = 'Y')";
			$mysqli->query($sql);

			// 부재 체크박스 비우기 전에 h_visit_old에 저장 (배정 취소 시 복구를 위해)
			$sql = "UPDATE " . HOUSE_TABLE . " SET h_visit_old = 'N' WHERE tt_id = {$tt_id} AND h_visit = 'N' AND (h_visit_old = '' OR h_visit_old IS NULL)";
			$mysqli->query($sql);

			// 부재 체크박스 비우기 (다시 봉사할 수 있게)
			$sql = "UPDATE " . HOUSE_TABLE . " SET h_visit = '' WHERE tt_id = {$tt_id} AND h_visit = 'N'";
			$mysqli->query($sql);
		}
	}

}

//전화 구역카드 리셋
// 전화 구역카드 리셋
// 중요: 이 함수는 봉사기록(TELEPHONE_RECORD_TABLE)을 삭제하지 않으며, 
// 리셋 전의 봉사 정보를 봉사기록 테이블에 저장합니다.
function telephone_reset($tp_id, $new_status = '', $record_m_id = 0)
{
	global $mysqli;

	// 입력값 검증
	if (!is_numeric($tp_id) || $tp_id <= 0) {
		return false;
	}

	if (!is_numeric($record_m_id) || $record_m_id < 0) {
		$record_m_id = 0;
	}

	$telephone = new Telephone($mysqli);

	if ($tp_id) {
		$current_datetime = date("Y-m-d H:i:s"); // 현재 datetime

		$tp_sql = "SELECT tp_id, tp_assigned, tp_assigned_date, tp_assigned_group, tp_start_date, tp_end_date, tp_status, mb_id, m_id FROM " . TELEPHONE_TABLE . " WHERE tp_id = {$tp_id}";
		$tp_result = $mysqli->query($tp_sql);
		if ($tp_result->num_rows > 0) {
			$tp_row = $tp_result->fetch_assoc();

			$tp_start_date = $tp_row['tp_start_date'];
			$tp_end_date = $tp_row['tp_end_date'];
			$tp_assigned_date = $tp_row['tp_assigned_date'];
			$tp_status = $tp_row['tp_status'];
			$tp_assigned_group = $tp_row['tp_assigned_group'];
			$m_id = $tp_row['m_id'];

			// 봉사를 시작했을떄만 기록이 남도록
			// 봉사기록은 TELEPHONE_RECORD_TABLE에 저장되며, 리셋 후에도 보존됩니다.
			if (!empty_date($tp_start_date)) {

				// 배정된 전도인 아이디를 이름으로 일괄 변경해서 변수에 담음
				$tpr_assigned = '';
				if ($tp_row['tp_assigned']) {
					$tpr_assigned = array();
					$tp_assigned = explode(',', $tp_row['tp_assigned']);
					// explode()는 항상 배열을 반환하므로 배열 체크 불필요
					foreach ($tp_assigned as $mb_id) {
						$member_name = get_member_name($mb_id);
						$tpr_assigned[] = $member_name ? $member_name : $mb_id;
					}
					$tpr_assigned = implode(',', $tpr_assigned);
				}

				// 개인구역 전도인 아이디를 이름으로 변경
				$tpr_mb_name = $tp_row['mb_id'] ? get_member_name($tp_row['mb_id']) : '';

				// 트랜잭션 시작하여 중복 저장 완전 방지
				$mysqli->begin_transaction();

				try {
					// 중복 기록 방지: 동일 방문(핵심 필드 기준)이 이미 있는지 확인
					// record_m_id도 포함하여 더 엄격하게 체크
					$dup_sql = "SELECT COUNT(*) FROM " . TELEPHONE_RECORD_TABLE . " 
						WHERE tp_id = ? 
						AND tpr_assigned_num = ? 
						AND tpr_assigned_date = ? 
						AND tpr_assigned_group = ? 
						AND tpr_start_date = ? 
						AND tpr_end_date = ? 
						AND tpr_status = ? 
						AND m_id = ? 
						AND record_m_id = ?
						FOR UPDATE";
					if ($stmt_dup = $mysqli->prepare($dup_sql)) {
						$assigned_num = $tp_row['tp_assigned'];
						$stmt_dup->bind_param(
							"issssssii",
							$tp_id,
							$assigned_num,
							$tp_assigned_date,
							$tp_assigned_group,
							$tp_start_date,
							$tp_end_date,
							$tp_status,
							$m_id,
							$record_m_id
						);
						$stmt_dup->execute();
						$stmt_dup->bind_result($dup_count);
						$stmt_dup->fetch();
						$stmt_dup->close();
					} else {
						$mysqli->rollback();
						return false;
					}

					if ((int) $dup_count === 0) {
						// TELEPHONE_RECORD_TABLE 에 기록 추가 (prepared statement 사용)
						$sql = "INSERT INTO " . TELEPHONE_RECORD_TABLE . " (tp_id, tpr_assigned, tpr_assigned_num, tpr_assigned_date, tpr_assigned_group, create_datetime, update_datetime, tpr_start_date, tpr_end_date, tpr_status, tpr_mb_name, m_id, record_m_id)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
						$stmt2 = $mysqli->prepare($sql);
						if ($stmt2) {
							$stmt2->bind_param(
								"issssssssssii",
								$tp_id,
								$tpr_assigned,
								$tp_row['tp_assigned'],
								$tp_assigned_date,
								$tp_assigned_group,
								$current_datetime,
								$current_datetime,
								$tp_start_date,
								$tp_end_date,
								$tp_status,
								$tpr_mb_name,
								$m_id,
								$record_m_id
							);
							$stmt2->execute();
							$stmt2->close();
						} else {
							$mysqli->rollback();
							return false;
						}
					}

					// 트랜잭션 커밋
					$mysqli->commit();
				} catch (Exception $e) {
					// 에러 발생 시 롤백
					$mysqli->rollback();
					return false;
				}
			}

			// 구역 배정정보 리셋 *단 개인구역(mb_id 에 값이 존재시)일때는 리셋하지 않는다
			$updateData = array(
				'tp_assigned' => '',
				'tp_assigned_date' => '0000-00-00',
				'tp_assigned_group' => '',
				'tp_start_date' => '0000-00-00',
				'tp_end_date' => '0000-00-00',
				'tp_status' => $new_status,
				'm_id' => 0,
			);

			try {
				$updateId = $telephone->update($tp_id, $updateData);
			} catch (Exception $e) {
				return false;
			}
		}
	}

	return true;
}

// 전화구역카드 세대 리셋
function telephone_house_reset($tp_id)
{
	global $mysqli;

	if ($tp_id) {
		// 집 만남/부재 체크 리셋
		$sql = "UPDATE " . TELEPHONE_HOUSE_TABLE . " SET tph_visit = '' WHERE tp_id = {$tp_id}";
		$mysqli->query($sql);
	}
}

// 전화구역카드 세대 방문정보 업데이트
function telephone_house_update($tp_id, $restore = '', $new_status = '', $old_status = null, $is_completed = false)
{
	global $mysqli;

	// 세대 방문정보 복구하기 (이전으로 되돌려놓기)
	if ($restore == 'restore') {
		// tph_visit_old가 있는 경우에만 복구 (비어있으면 체크박스가 비워지지 않도록)
		$sql = "UPDATE " . TELEPHONE_HOUSE_TABLE . " SET tph_visit = tph_visit_old, tph_visit_old = '' WHERE tp_id = {$tp_id} AND tph_visit_old != ''";
		$mysqli->query($sql);
	} else { // 세대 방문정보 업데이트
		// 이전 상태가 전달되지 않은 경우(null)에만 DB에서 조회 (빈 문자열도 유효한 값)
		if ($old_status === null) {
			$sql = "SELECT tp_status FROM " . TELEPHONE_TABLE . " WHERE tp_id = {$tp_id}";
			$result = $mysqli->query($sql);
			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				$old_status = $row['tp_status'];
			} else {
				$old_status = '';
			}
		}

		// 상태가 실제로 변경되었는지 확인
		$old_is_absence = !empty($old_status) && strpos($old_status, 'absence') !== false;
		$new_is_absence = !empty($new_status) && strpos($new_status, 'absence') !== false;

		// 완료 여부가 전달되지 않은 경우 DB에서 조회
		if (func_num_args() < 5) {
			$sql = "SELECT tp_end_date FROM " . TELEPHONE_TABLE . " WHERE tp_id = {$tp_id}";
			$result = $mysqli->query($sql);
			$is_completed = false;
			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				$is_completed = !empty($row['tp_end_date']) && $row['tp_end_date'] != '0000-00-00';
			}
		}

		// 전체(absence 없음) → 부재(absence 포함)로 변경된 경우
		if (!$old_is_absence && $new_is_absence) {
			$sql = "UPDATE " . TELEPHONE_HOUSE_TABLE . " SET tph_visit_old = tph_visit WHERE tp_id = {$tp_id}";
			$mysqli->query($sql);

			// 전체에서 부재로 변경된 경우에만 부재 체크박스 비움
			$sql = "UPDATE " . TELEPHONE_HOUSE_TABLE . " SET tph_visit = '' WHERE tp_id = {$tp_id} AND tph_visit = 'N'";
			$mysqli->query($sql);
		}
		// 부재 완료 상태에서 재배정하는 경우 (부재 → 부재 재배정, 완료 상태)
		elseif ($old_is_absence && $new_is_absence && $is_completed) {
			// 만남 집 비활성화: 전체일 때 만남(tph_visit_old='Y') + 부재일 때 만남(tph_visit='Y') 모두 포함
			// tph_visit='Y'이거나 tph_visit_old='Y'인 경우 모두 tph_visit_old='Y'로 설정
			$sql = "UPDATE " . TELEPHONE_HOUSE_TABLE . " SET tph_visit_old = 'Y' WHERE tp_id = {$tp_id} AND (tph_visit = 'Y' OR tph_visit_old = 'Y')";
			$mysqli->query($sql);

			// 부재 체크박스 비우기 전에 tph_visit_old에 저장 (배정 취소 시 복구를 위해)
			$sql = "UPDATE " . TELEPHONE_HOUSE_TABLE . " SET tph_visit_old = 'N' WHERE tp_id = {$tp_id} AND tph_visit = 'N' AND (tph_visit_old = '' OR tph_visit_old IS NULL)";
			$mysqli->query($sql);

			// 부재 체크박스 비우기 (다시 봉사할 수 있게)
			$sql = "UPDATE " . TELEPHONE_HOUSE_TABLE . " SET tph_visit = '' WHERE tp_id = {$tp_id} AND tph_visit = 'N'";
			$mysqli->query($sql);
		}
	}

}

// 가장 최근의 봉사 기록 구하기
function get_latest_record($table, $pid)
{
	global $mysqli;

	$return = array();
	if ($table == 'territory') {
		$sql = "SELECT ttr_assigned_date, ttr_start_date, ttr_end_date FROM " . TERRITORY_RECORD_TABLE . " WHERE tt_id = {$pid} ORDER BY create_datetime DESC LIMIT 1";
	} elseif ($table == 'telephone') {
		$sql = "SELECT tpr_assigned_date, tpr_start_date, tpr_end_date FROM " . TELEPHONE_RECORD_TABLE . " WHERE tp_id = {$pid} ORDER BY create_datetime DESC LIMIT 1";
	}
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0)
		$return = $result->fetch_assoc();

	return $return;
}

// 모든 지난 방문 기록 얻기
function get_all_past_records($table, $pid)
{
	global $mysqli;

	$return = array();
	if ($table == 'territory') {

		// 그 다음 record 데이터 가져오기
		$sql = "SELECT ttr_id, ttr_start_date, ttr_end_date, ttr_status, ttr_assigned, ttr_assigned_date
		FROM " . TERRITORY_RECORD_TABLE . " 
		WHERE tt_id = {$pid} 
		ORDER BY create_datetime ASC";
		$result = $mysqli->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$return[] = array(
					'id' => $row['ttr_id'],
					'table' => 'territory_record',
					'start_date' => $row['ttr_start_date'],
					'end_date' => $row['ttr_end_date'],
					'status' => $row['ttr_status'],
					'assigned' => $row['ttr_assigned'],
					'assigned_date' => $row['ttr_assigned_date']
				);
			}
		}

		// 먼저 territory_table 데이터 가져오기
		$sql = "SELECT tt_id, tt_start_date, tt_end_date, tt_status, tt_assigned, tt_assigned_date
                FROM " . TERRITORY_TABLE . " WHERE tt_id = {$pid}";
		$result = $mysqli->query($sql);
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();

			// tt_assigned는 멤버 ID이므로 이름으로 변환
			$assigned_names = get_assigned_member_name($row['tt_assigned']);

			$return[] = array(
				'id' => $row['tt_id'],
				'table' => 'territory',
				'start_date' => $row['tt_start_date'],
				'end_date' => $row['tt_end_date'],
				'status' => $row['tt_status'],
				'assigned' => $assigned_names,
				'assigned_date' => $row['tt_assigned_date']
			);
		}

	} elseif ($table == 'telephone') {

		// 그 다음 record 데이터 가져오기
		$sql = "SELECT tpr_id, tpr_start_date, tpr_end_date, tpr_status, tpr_assigned, tpr_assigned_date
		FROM " . TELEPHONE_RECORD_TABLE . " 
		WHERE tp_id = {$pid} 
		ORDER BY create_datetime ASC";
		$result = $mysqli->query($sql);
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$return[] = array(
					'id' => $row['tpr_id'],
					'table' => 'telephone_record',
					'start_date' => $row['tpr_start_date'],
					'end_date' => $row['tpr_end_date'],
					'status' => $row['tpr_status'],
					'assigned' => $row['tpr_assigned'],
					'assigned_date' => $row['tpr_assigned_date']
				);
			}
		}

		// 먼저 telephone_table 데이터 가져오기
		$sql = "SELECT tp_id, tp_start_date, tp_end_date, tp_status, tp_assigned, tp_assigned_date
                FROM " . TELEPHONE_TABLE . " WHERE tp_id = {$pid}";
		$result = $mysqli->query($sql);
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();

			// tp_assigned는 멤버 ID이므로 이름으로 변환
			$assigned_names = get_assigned_member_name($row['tp_assigned']);

			$return[] = array(
				'id' => $row['tp_id'],
				'table' => 'telephone',
				'start_date' => $row['tp_start_date'],
				'end_date' => $row['tp_end_date'],
				'status' => $row['tp_status'],
				'assigned' => $assigned_names,
				'assigned_date' => $row['tp_assigned_date']
			);
		}

	}

	// 방문별로 데이터 재가공
	$visits = array();
	$current_visit = null;
	$current_records = array();

	$prev_status = null;

	foreach ($return as $record) {
		$status = $record['status'];

		// 새로운 방문 시작
		if ($current_visit === null) {
			$current_visit = strpos($status, 'absence') !== false ? '부재' : '전체';
			$current_records[] = array(
				'id' => $record['id'],
				'table' => $record['table'],
				'start_date' => $record['start_date'],
				'end_date' => $record['end_date'],
				'assigned' => $record['assigned'],
				'assigned_date' => $record['assigned_date']
			);
			$prev_status = $status;
			continue;
		}

		// 현재 record가 전체방문이나 부재방문이고
		// 이전 record도 전체방문이나 부재방문이었다면 
		// 새로운 방문으로 저장
		if (
			$status === '' || $status === 'absence' || // 새로운 방문 시작 조건
			!( // 같은 방문으로 묶이는 조건의 반대
				($status === '' && $prev_status === 'reassign') || // 전체방문 그룹
				($prev_status === '' && $status === 'reassign') ||
				($status === 'absence' && $prev_status === 'absence_reassign') || // 부재방문 그룹
				($prev_status === 'absence' && $status === 'absence_reassign')
			)
		) {
			// 진행상태 계산 - records의 모든 기록을 확인
			$progress_status = 'incomplete'; // 기본값
			if (!empty($current_records)) {
				$has_any_start = false;
				$has_any_end = false;

				// 모든 기록을 확인
				foreach ($current_records as $rec) {
					$has_start = isset($rec['start_date']) && !empty($rec['start_date']) && $rec['start_date'] !== '0000-00-00';
					$has_end = isset($rec['end_date']) && !empty($rec['end_date']) && $rec['end_date'] !== '0000-00-00';

					if ($has_start)
						$has_any_start = true;
					if ($has_end)
						$has_any_end = true;
				}

				// 전체 진행상태 판단
				if ($has_any_start && $has_any_end) {
					$progress_status = 'completed';
				} elseif ($has_any_start) {
					$progress_status = 'in_progress';
				}
				// $has_any_start가 false면 'incomplete' (기본값)
			}

			// 새로운 방문으로 저장
			$visits[] = array(
				'visit' => $current_visit,
				'progress' => $progress_status,
				'records' => array_reverse($current_records)
			);

			// 새로운 방문 시작
			$current_visit = strpos($status, 'absence') !== false ? '부재' : '전체';
			$current_records = array(
				array(
					'id' => $record['id'],
					'table' => $record['table'],
					'start_date' => $record['start_date'],
					'end_date' => $record['end_date'],
					'assigned' => $record['assigned'],
					'assigned_date' => $record['assigned_date']
				)
			);
		} else {
			$current_records[] = array(
				'id' => $record['id'],
				'table' => $record['table'],
				'start_date' => $record['start_date'],
				'end_date' => $record['end_date'],
				'assigned' => $record['assigned'],
				'assigned_date' => $record['assigned_date']
			);
		}

		$prev_status = $status;
	}

	// 마지막 방문 추가
	if ($current_visit !== null) {
		// 진행상태 계산 - records의 모든 기록을 확인
		$progress_status = 'incomplete'; // 기본값
		if (!empty($current_records)) {
			$has_any_start = false;
			$has_any_end = false;

			// 모든 기록을 확인
			foreach ($current_records as $rec) {
				$has_start = isset($rec['start_date']) && !empty($rec['start_date']) && $rec['start_date'] !== '0000-00-00';
				$has_end = isset($rec['end_date']) && !empty($rec['end_date']) && $rec['end_date'] !== '0000-00-00';

				if ($has_start)
					$has_any_start = true;
				if ($has_end)
					$has_any_end = true;
			}

			// 전체 진행상태 판단
			if ($has_any_start && $has_any_end) {
				$progress_status = 'completed';
			} elseif ($has_any_start) {
				$progress_status = 'in_progress';
			}
			// $has_any_start가 false면 'incomplete' (기본값)
		}

		$visits[] = array(
			'visit' => $current_visit,
			'progress' => $progress_status,
			'records' => array_reverse($current_records)
		);
	}

	// 방문 기록 역순으로 정렬
	$visits = array_reverse($visits);

	return $visits;
}



// 사이트설정 불러오기
function get_site_option($name)
{
	global $mysqli;

	$return = '';
	$sql = "SELECT value FROM " . OPTION_TABLE . " WHERE name = '{$name}'";
	$result = $mysqli->query($sql);

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$return = $row['value'];
	}

	return $return;
}

// 사이트설정 업데이트
function set_site_option($name, $value)
{
	global $mysqli;

	$sql = "SELECT value FROM " . OPTION_TABLE . " WHERE name = '{$name}'";
	$result = $mysqli->query($sql);

	if ($result->num_rows > 0) {
		$sql = "UPDATE " . OPTION_TABLE . " SET value = '{$value}' WHERE name = '{$name}'";
		$mysqli->query($sql);
	} else {
		$sql = "INSERT INTO " . OPTION_TABLE . " (name, value) VALUES ('{$name}', '{$value}')";
		$result = $mysqli->query($sql);
	}

}

// 암호화 함수
function encrypt($str)
{
	$salt = md5('1234567890');
	$length = strlen($salt);

	$length2 = strlen($str);
	$result = '';
	for ($i = 0; $i < $length2; $i++) {
		$char = substr($str, $i, 1);
		$keychar = substr($salt, ($i % $length) - 1, 1);
		$char = chr(ord($char) + ord($keychar));
		$result .= $char;
	}
	return base64_encode($result);
}

// 복호화 함수
function decrypt($str)
{
	$salt = md5('1234567890');
	$length = strlen($salt);

	$result = '';
	$str = base64_decode($str);
	$length2 = strlen($str);
	for ($i = 0; $i < $length2; $i++) {
		$char = substr($str, $i, 1);
		$keychar = substr($salt, ($i % $length) - 1, 1);
		$char = chr(ord($char) - ord($keychar));
		$result .= $char;
	}
	return $result;
}

function utf8_ord($ch)
{
	$len = strlen($ch);
	if ($len <= 0)
		return false;
	$h = ord($ch[0]);
	if ($h <= 0x7F)
		return $h;
	if ($h < 0xC2)
		return false;
	if ($h <= 0xDF && $len > 1)
		return ($h & 0x1F) << 6 | (ord($ch[1]) & 0x3F);
	if ($h <= 0xEF && $len > 2)
		return ($h & 0x0F) << 12 | (ord($ch[1]) & 0x3F) << 6 | (ord($ch[2]) & 0x3F);
	if ($h <= 0xF4 && $len > 3)
		return ($h & 0x0F) << 18 | (ord($ch[1]) & 0x3F) << 12 | (ord($ch[2]) & 0x3F) << 6 | (ord($ch[3]) & 0x3F);
	return false;
}

function cho_hangul($str)
{
	$cho = array("ㄱ", "ㄲ", "ㄴ", "ㄷ", "ㄸ", "ㄹ", "ㅁ", "ㅂ", "ㅃ", "ㅅ", "ㅆ", "ㅇ", "ㅈ", "ㅉ", "ㅊ", "ㅋ", "ㅌ", "ㅍ", "ㅎ");
	$result = "";
	$code = utf8_ord(mb_substr($str, 0, 1, 'UTF-8')) - 44032;
	if ($code > -1 && $code < 11172) {
		$cho_idx = $code / 588;
		$result .= $cho[$cho_idx];
	}
	return $result;
}

/**
 * 모임 일정의 지원자 수 제한 정보를 반환
 * @param int $ms_id 모임 일정 ID
 * @return mixed 제한이 없으면 빈 문자열(''), 있으면 제한 인원 수(1 이상의 정수)
 */
function get_meeting_schedule_attend_limit($ms_id)
{
	global $mysqli;

	// 모임 정보 조회
	$sql = "SELECT ms_type, ms_limit 
            FROM " . MEETING_SCHEDULE_TABLE . " 
            WHERE ms_id = '{$ms_id}'
            LIMIT 1";
	$result = $mysqli->query($sql);
	$row = $result->fetch_assoc();

	$ms_type = isset($row['ms_type']) ? (int) $row['ms_type'] : 1;
	$ms_limit = isset($row['ms_limit']) ? $row['ms_limit'] : '';

	// ms_limit가 0이나 음수면 제한 없음으로 강제 적용
	if ($ms_limit === '0') {
		return '';
	}

	// ms_limit가 빈값이면 기본 설정값 적용
	if ($ms_limit === '' || (int) $ms_limit <= 0) {
		$limit_options = unserialize(MEETING_SCHEDULE_TYPE_ATTEND_LIMIT);
		$default_limit = isset($limit_options[$ms_type]) ? $limit_options[$ms_type] : '';
		return empty($default_limit) ? '' : (int) $default_limit;
	}

	// ms_limit가 있으면 해당 값 적용
	return (int) $ms_limit;
}


/**
 * 모임 지원자 수 제한 구하기 (스냅샷 우선)
 * @param string $s_date 모임 날짜
 * @param int $ms_id 모임 일정 ID
 * @return mixed 제한이 없으면 빈 문자열(''), 있으면 제한 인원 수
 */
function get_meeting_limit($s_date, $ms_id)
{
	if (!$ms_id)
		return '';
	$m_id = get_meeting_id($s_date, $ms_id);
	$m = get_meeting_data($m_id);

	// 개별 모임 설정(스냅샷)이 있으면 우선 사용
	if (isset($m['ms_limit']) && $m['ms_limit'] !== '' && $m['ms_limit'] !== null) {
		return ($m['ms_limit'] == 0) ? '' : $m['ms_limit'];
	}

	// 없으면 계획 설정 따름
	return get_meeting_schedule_attend_limit($ms_id);
}
?>