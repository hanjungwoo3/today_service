<?php
// 호환 버전 : ( php8.3 | 10.6.17-MariaDB-log ),( PHP7.3 | 5.5.5-10.1.13-MariaDB )
// mysql 버전 : 10.6.17-MariaDB-log
include_once(__DIR__.'/config_custom.php'); // 커스텀 설정
include_once(__DIR__.'/config_table.php'); // 테이블 설정

define('DEBUG_MODE', false); // 배포 환경에서는 false로 설정

// 프로젝트가 하위 폴더에서도 동작하도록 BASE_PATH 설정
$script_name = $_SERVER['SCRIPT_NAME'];
$current_dir = dirname($script_name);

// pages, include, v_data 폴더에서 실행되는 경우
if (strpos($script_name, '/pages/') !== false || 
    strpos($script_name, '/include/') !== false || 
    strpos($script_name, '/v_data/') !== false) {
    $base_path = dirname($current_dir);
} else {
    $base_path = $current_dir;
}

// 루트 디렉토리인 경우 빈 문자열로 설정
if ($base_path === '/') {
    $base_path = '';
}

define('BASE_PATH', $base_path);

define('ICON', BASE_PATH.'/icons/icon-jw-n.png'); // 아이콘
define('VERSION','2025082015'); // js, css 버전
define('APP_VERSION','2.5.5'); // 앱 버전 (대규모변경).(보이는부분&기능추가).(보이지않는부분)

// 보안설정이나 프레임이 달라도 쿠키가 통하도록 설정
header('P3P: CP="ALL CURa ADMa DEVa TAIa OUR BUS IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE LOC OTC"');

// extract($_GET); 명령으로 인해 page.php?_POST[var1]=data1&_POST[var2]=data2 와 같은 코드가 _POST 변수로 사용되는 것을 막음
$ext_arr = array ('PHP_SELF', '_ENV', '_GET', '_POST', '_FILES', '_SERVER', '_COOKIE', '_SESSION', '_REQUEST',
                  'HTTP_ENV_VARS', 'HTTP_GET_VARS', 'HTTP_POST_VARS', 'HTTP_POST_FILES', 'HTTP_SERVER_VARS',
                  'HTTP_COOKIE_VARS', 'HTTP_SESSION_VARS', 'GLOBALS','mysqli');
$ext_cnt = count($ext_arr);
for ($i=0; $i<$ext_cnt; $i++) {
    // POST, GET 으로 선언된 전역변수가 있다면 unset() 시킴
    if (isset($_GET[$ext_arr[$i]]))  unset($_GET[$ext_arr[$i]]);
    if (isset($_POST[$ext_arr[$i]])) unset($_POST[$ext_arr[$i]]);
}

// multi-dimensional array에 사용자지정 함수적용
function array_map_deep($fn, $array){
  if(is_array($array)){
    foreach($array as $key => $value) $array[$key] = (is_array($value))?array_map_deep($fn, $value):call_user_func($fn, $value);
  }else{
    $array = call_user_func($fn, $array);
  }

  return $array;
}

// SQL Injection 대응 문자열 필터링
function sql_escape_string($str){
  $str = call_user_func('addslashes', $str);

  return $str;
}

//==============================================================================
// SQL Injection 등으로 부터 보호를 위해 sql_escape_string() 적용
//------------------------------------------------------------------------------

// sql_escape_string 적용
$_POST    = array_map_deep('sql_escape_string',  $_POST);
$_GET     = array_map_deep('sql_escape_string',  $_GET);
$_COOKIE  = array_map_deep('sql_escape_string',  $_COOKIE);
$_REQUEST = array_map_deep('sql_escape_string',  $_REQUEST);
//==============================================================================

@extract($_GET);
@extract($_POST);
@extract($_SERVER);

session_cache_expire(60); // 60분으로 단축
session_start();

$mysqli = new mysqli($host, $user, $password, $dbname);
// 연결 오류 발생 시 스크립트 종료
if ($mysqli->connect_errno) {
  die('Connect Error: '.$mysqli->connect_error);
}

include_once(__DIR__.'/core/class.core.php');
include_once(__DIR__.'/core/class.territory.php');
include_once(__DIR__.'/core/class.telephone.php');
include_once(__DIR__.'/functions.php');

// 옵션
define('SITE_NAME',get_site_option('site_name'));
define('DEFAULT_ADDRESS',get_site_option('default_address')); // 정확한 주소검색을 위한 기본주소 설정
define('DEFAULT_LOCATION',get_site_option('default_location')); // 다음지도 기본 위치
define('ABSENCE_USE',get_site_option('absence_use')); // 부재자봉사 사용
define('DISPLAY_USE',get_site_option('display_use')); // 전시대봉사 사용
define('RETURNVISIT_USE',get_site_option('returnvisit_use')); // 재방문봉사 사용
define('DUPLICATE_ATTEND_LIMIT',get_site_option('duplicate_attend_limit')); // 같은 시간대 중복 참석/지원 제한
define('ATTEND_USE',get_site_option('attend_use')); // 홈 호별참석버튼 사용
define('ATTEND_DISPLAY_USE',get_site_option('attend_display_use')); // 홈 전시대참석버튼 사용
define('MINISTER_ATTEND_USE',get_site_option('minister_attend_use')); // 봉사자 참석버튼 사용
define('MINISTER_DISPLAY_ATTEND_USE',get_site_option('minister_display_attend_use')); // 봉사자 참석버튼 사용
define('MINISTER_SCHEDULE_EVENT_USE',get_site_option('minister_schedule_event_use')); // 나의봉사 > 일정사용
define('MINISTER_SCHEDULE_REPORT_USE',get_site_option('minister_schedule_report_use')); // 나의봉사 > 보고사용
define('MINISTER_STATISTICS_USE',get_site_option('minister_statistics_use')); // 나의통계 사용
define('GUIDE_STATISTICS_USE',get_site_option('guide_statistics_use')); // 인도자 통계 사용
define('TERRITORY_BOUNDARY',get_site_option('territory_boundary'));
if(get_site_option('attend_before') == ''){ define('ATTEND_BEFORE',45); }else{ define('ATTEND_BEFORE',get_site_option('attend_before')); } // 참석버튼 노출시간 전
if(get_site_option('attend_after') == ''){ define('ATTEND_AFTER',15); }else{ define('ATTEND_AFTER',get_site_option('attend_after')); } // 참석버튼 노출시간 후
if(get_site_option('attend_display_before') == ''){ define('ATTEND_DISPLAY_BEFORE',45); }else{ define('ATTEND_DISPLAY_BEFORE',get_site_option('attend_display_before')); } // 전시대 참석버튼 노출시간 전
if(get_site_option('attend_display_after') == ''){ define('ATTEND_DISPLAY_AFTER',15); }else{ define('ATTEND_DISPLAY_AFTER',get_site_option('attend_display_after')); } // 전시대 참석버튼 노출시간 후
define('MEETING_SCHEDULE_TYPE_ATTEND_LIMIT',get_site_option('meeting_schedule_type_attend_limit')); // 모임형태 별 지원자 수 제한
define('GUIDE_CARD_ORDER',get_site_option('guide_card_order'));
define('MAP_API_KEY',get_site_option('map_api_key'));
define('BOARD_ITEM_PER_PAGE',get_site_option('board_item_per_page'));
define('GUIDE_MEETING_CONTENTS',get_site_option('guide_meeting_contents'));
define('GUIDE_MEETING_CONTENTS_USE',get_site_option('guide_meeting_contents_use'));
define('GUIDE_APPOINT_USE',get_site_option('guide_appoint_use')); // 당일 인도자 지정 기능
define('GUIDE_ASSIGNED_GROUP_USE',get_site_option('guide_assigned_group_use')); // 짝 기능
define('TERRITORY_TYPE',get_site_option('territory_type')); // 구역형태 설정
define('TERRITORY_TYPE_USE',get_site_option('territory_type_use')); // 구역형태 사용 설정
define('MEETING_SCHEDULE_TYPE',get_site_option('meeting_schedule_type')); // 봉사형태 설정
define('MEETING_SCHEDULE_TYPE_USE',get_site_option('meeting_schedule_type_use')); // 봉사형태 사용 설정
define('TERRITORY_COMPLETE_PERCENT',get_site_option('territory_complete_percent')); // 구역카드 완료 기준
define('HOUSE_CONDITION',get_site_option('house_condition')); // 특이사항 설정
define('HOUSE_CONDITION_USE',get_site_option('house_condition_use')); // 특이사항 사용 설정
define('RETURN_VISIT_EXPIRATION',get_site_option('return_visit_expiration')); // 재방문 자동중단 기간
define('TERRITORY_ITEM_PER_PAGE',get_site_option('territory_item_per_page'));
define('ADMIN_TERRITORY_SORT',get_site_option('admin_territory_sort')); // 구역카드 정렬
define('MINISTER_ASSIGN_EXPIRATION',get_site_option('minister_assign_expiration')); // 호별 봉사 가능 기간
define('MINISTER_TELEPHONE_ASSIGN_EXPIRATION',get_site_option('minister_telephone_assign_expiration')); // 전화 봉사 가능 기간
define('MINISTER_LETTER_ASSIGN_EXPIRATION',get_site_option('minister_letter_assign_expiration')); // 편지 봉사 가능 기간
define('SHOW_ATTEND_USE',get_site_option('show_attend_use')); // 모임 정보 팝업에서 참석자 보여주기
?>
