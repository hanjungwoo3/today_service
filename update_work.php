<!DOCTYPE html>
<html lang="ko">

<head>
    <title>오늘의 봉사</title>
    <meta charset="utf-8">
    <meta name="description" content="JW Ministry" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, shrink-to-fit=no, minimum-scale=1.0, user-scalable=no">
    <link rel="stylesheet"
        href="<?= dirname($_SERVER['SCRIPT_NAME']) ?>/lib/bootstrap-4.5.3-dist/css/bootstrap.min.css">
    <script src="<?= dirname($_SERVER['SCRIPT_NAME']) ?>/js/jquery-3.5.1.min.js"></script>
    <script type="text/javascript"
        src="<?= dirname($_SERVER['SCRIPT_NAME']) ?>/lib/bootstrap-4.5.3-dist/js/bootstrap.min.js"></script>
    <style>
        @font-face {
            font-family: 'NanumSquare';
            font-weight: 400;
            src: url(../font/NanumSquare/NanumSquareR.eot);
            src: url(../font/NanumSquare/NanumSquareR.eot?#iefix) format('embedded-opentype'),
                url(../font/NanumSquare/NanumSquareR.woff) format('woff'),
                url(../font/NanumSquare/NanumSquareR.ttf) format('truetype');
        }

        @font-face {
            font-family: 'NanumSquare';
            font-weight: 700;
            src: url(../font/NanumSquare/NanumSquareB.eot);
            src: url(../font/NanumSquare/NanumSquareB.eot?#iefix) format('embedded-opentype'),
                url(../font/NanumSquare/NanumSquareB.woff) format('woff'),
                url(../font/NanumSquare/NanumSquareB.ttf) format('truetype');
        }

        @font-face {
            font-family: 'NanumSquare';
            font-weight: 800;
            src: url(../font/NanumSquare/NanumSquareEB.eot);
            src: url(../font/NanumSquare/NanumSquareEB.eot?#iefix) format('embedded-opentype'),
                url(../font/NanumSquare/NanumSquareEB.woff) format('woff'),
                url(../font/NanumSquare/NanumSquareEB.ttf) format('truetype');
        }

        @font-face {
            font-family: 'NanumSquare';
            font-weight: 300;
            src: url(../font/NanumSquare/NanumSquareL.eot);
            src: url(../font/NanumSquare/NanumSquareL.eot?#iefix) format('embedded-opentype'),
                url(../font/NanumSquare/NanumSquareL.woff) format('woff'),
                url(../font/NanumSquare/NanumSquareL.ttf) format('truetype');
        }

        html,
        html>* {
            font-family: 'NanumSquare', sans-serif;
        }
    </style>
</head>

<body>
    <?php

    // multi-dimensional array에 사용자지정 함수적용
    function array_map_deep($fn, $array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = array_map_deep($fn, $value);
                } else {
                    $array[$key] = call_user_func($fn, $value);
                }
            }
        } else {
            $array = call_user_func($fn, $array);
        }

        return $array;
    }


    // SQL Injection 대응 문자열 필터링
    function sql_escape_string($str)
    {

        $str = call_user_func('addslashes', $str);

        return $str;
    }

    //==============================================================================
// SQL Injection 등으로 부호를 위해 sql_escape_string() 적용
//------------------------------------------------------------------------------
    
    // sql_escape_string 적용
    $_POST = array_map_deep('sql_escape_string', $_POST);
    $_GET = array_map_deep('sql_escape_string', $_GET);
    $_COOKIE = array_map_deep('sql_escape_string', $_COOKIE);
    $_REQUEST = array_map_deep('sql_escape_string', $_REQUEST);
    //==============================================================================
    
    if (!file_exists('config_custom.php')) {
        echo "'config_custom.php' 파일이 존재하지 않습니다.";
        exit;
    }

    include_once('config_custom.php'); // 커스텀 설정
    
    if (empty($host) || empty($user) || empty($password) || empty($dbname)) {
        echo '<script> alert("\'config_custom.php\' 파일내의 정보가 비어있습니다."); location.href="' . dirname($_SERVER['SCRIPT_NAME']) . '/update.php"; </script>';
        exit;
    }

    if (empty($_POST['type']) || empty($_POST['db_name']) || empty($_POST['db_password'])) {
        echo '잘못된 접근입니다.';
        exit;
    }

    if ($_POST['db_name'] != $user) {
        echo '<script> alert("데이터베이스 아이디 또는 비밀번호가 올바르지 않습니다."); location.href="' . dirname($_SERVER['SCRIPT_NAME']) . '/update.php"; </script>';
        exit;
    }

    if ($_POST['db_password'] != $password) {
        echo '<script> alert("데이터베이스 아이디 또는 비밀번호가 올바르지 않습니다."); location.href="' . dirname($_SERVER['SCRIPT_NAME']) . '/update.php"; </script>';
        exit;
    }

    $mysqli = new mysqli($host, $user, $password, $dbname);
    // 연결 오류 발생 시 스크립트 종료
    if ($mysqli->connect_errno) {
        die('Connect Error: ' . $mysqli->connect_error);
    }

    if (!file_exists('config_table.php')) {
        echo "'config_table.php' 파일이 존재하지 않습니다.";
        exit;
    }

    include_once('config_table.php'); // 테이블 설정
    
    $type = $_POST['type'];

    if ($type == 'new') {
        if (empty($_POST['name']) || empty($_POST['password']) || empty($_POST['password_confirm'])) {
            echo '<script> alert("필요한 정보를 입력하지 않았습니다."); location.href="' . dirname($_SERVER['SCRIPT_NAME']) . '/update.php"; </script>';
            exit;
        }

        if ($_POST['password'] != $_POST['password_confirm']) {
            echo '<script> alert("관리자 비밀번호와 비밀번호 확인이 일치하지 않습니다."); location.href="' . dirname($_SERVER['SCRIPT_NAME']) . '/update.php"; </script>';
            exit;
        }
    }

    /* 존재하지 않는 테이블 생성 */
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . BOARD_TABLE . " (
    `b_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `mb_id` int(11) NOT NULL COMMENT '작성자고유번호',
    `b_title` varchar(50) NOT NULL COMMENT '제목',
    `b_content` mediumtext NOT NULL COMMENT '내용',
    `b_guide` varchar(50) NOT NULL COMMENT '봉사자용 인도자용 구분',
    `read_mb` text NOT NULL COMMENT '읽은 member_id',
    `b_notice` varchar(1) NOT NULL COMMENT '공지여부',
    `create_datetime` datetime NOT NULL COMMENT '생성날짜',
    `update_datetime` datetime NOT NULL COMMENT '수정날짜',
    PRIMARY KEY (`b_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . DISPLAY_TABLE . " (
    `d_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `dp_id` int(11) NOT NULL COMMENT '전시대 장소',
    `d_assigned` text NOT NULL COMMENT '배정된전도인',
    `d_assigned_date` date NOT NULL COMMENT '배정된날짜',
    `d_assigned_group` text NOT NULL COMMENT '배정된 짝 당 수',
    `m_id` int(11) NOT NULL COMMENT 'meeting 고유번호',
    `dp_address` varchar(255) NOT NULL COMMENT '전시대 장소 주소',
    `dp_name` varchar(50) NOT NULL COMMENT '전시대 장소 이름',
    `dp_num` int(11) NOT NULL DEFAULT '1' COMMENT '전시대 팀 번호',
    PRIMARY KEY (`d_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . DISPLAY_PLACE_TABLE . " (
    `dp_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `dp_address` varchar(255) NOT NULL COMMENT '장소사진',
    `dp_name` varchar(50) NOT NULL COMMENT '장소명',
    `dp_count` int(11) NOT NULL DEFAULT '1' COMMENT '전시대 장소 개수',
    `ms_id` int(11) NOT NULL DEFAULT '0' COMMENT '모임아이디',
    `d_ms_all` int(11) NOT NULL DEFAULT '0' COMMENT '모임형태',
    PRIMARY KEY (`dp_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . GROUP_TABLE . " (
    `g_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `g_name` varchar(255) NOT NULL COMMENT '집단이름',
    PRIMARY KEY (`g_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . HOUSE_TABLE . " (
    `h_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `tt_id` int(11) NOT NULL COMMENT '구역고유번호',
    `h_address1` varchar(50) NOT NULL COMMENT '주소1',
    `h_address2` varchar(50) NOT NULL COMMENT '주소2',
    `h_address3` varchar(50) NOT NULL COMMENT '주소3',
    `h_address4` varchar(50) NOT NULL COMMENT '주소4',
    `h_address5` varchar(50) NOT NULL COMMENT '주소5',
    `h_condition` varchar(50) NOT NULL COMMENT '상태',
    `h_visit` varchar(1) NOT NULL COMMENT '만남여부',
    `h_visit_old` varchar(1) NOT NULL COMMENT '이전 만남여부 ',
    `h_order` int(11) NOT NULL COMMENT '순서',
    `mb_id` int(11) NOT NULL COMMENT '재방문 전도인 ID',
    PRIMARY KEY (`h_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . HOUSE_MEMO_TABLE . " (
    `hm_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `h_id` int(11) NOT NULL COMMENT 'house고유번호',
    `hm_content` varchar(255) NOT NULL COMMENT '내용',
    `mb_id` int(11) NOT NULL COMMENT 'member고유번호',
    `hm_condition` varchar(50) NOT NULL COMMENT '상태',
    `create_datetime` datetime NOT NULL COMMENT '생성날짜',
    `update_datetime` datetime NOT NULL COMMENT '수정날짜',
    PRIMARY KEY (`hm_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . MEETING_TABLE . " (
    `m_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `mb_id` text NOT NULL COMMENT '참여 멤버',
    `ms_id` int(11) NOT NULL COMMENT '봉사모임계획',
    `m_date` date NOT NULL COMMENT '모임 날짜',
    `m_cancle` int(1) NOT NULL COMMENT '봉사모임취소여부',
    `m_cancle_reason` text NOT NULL COMMENT '봉사모임취소사유',
    `m_contents` text NOT NULL COMMENT '모임 내용 기록',
    `m_guide` varchar(50) NOT NULL COMMENT '당일 인도자',
    `ms_guide` varchar(100) NOT NULL COMMENT '모임 스케쥴 인도자',
    `ms_guide2` varchar(100) NOT NULL COMMENT '모임 스케쥴 보조자',
    `ms_week` int(2) NOT NULL COMMENT '모임 스케쥴 요일',
    `ms_time` time NOT NULL COMMENT '모임 시간',
    `ms_type` int(1) NOT NULL COMMENT '봉사형태',
    `g_id` int(11) NOT NULL COMMENT '집단아이디',
    `mp_id` int(11) NOT NULL COMMENT '모임장소 고유번호',
    `mp_name` varchar(50) NOT NULL COMMENT '장소명',
    `mp_address` varchar(255) NOT NULL COMMENT '주소',    
    `m_start_time` time NOT NULL COMMENT '봉사시작시간',
    `m_finish_time` time NOT NULL COMMENT '봉사마치는시간',
    PRIMARY KEY (`m_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

    $mysqli->query("CREATE TABLE IF NOT EXISTS " . MEETING_ADD_TABLE . " (
    `ma_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '추가계획고유번호',
    `ma_title` varchar(255) NOT NULL COMMENT '추가계획이름',
    `ma_date` datetime NOT NULL COMMENT '시작 날짜시간',
    `ma_date2` datetime NOT NULL COMMENT '마치는 날짜시간',
    `ma_switch` int(1) NOT NULL COMMENT 'date인지 datetime인지 구분',
    `ma_week` int(1) NOT NULL COMMENT '몇째주',
    `ma_weekday` int(1) NOT NULL COMMENT '몇요일',
    `ma_auto` int(1) NOT NULL COMMENT '자동날짜사용여부',
    `ma_content` varchar(500) NOT NULL COMMENT '전달사항',
    `ma_color` varchar(11) NOT NULL COMMENT '회중일정 색상',
    PRIMARY KEY (`ma_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . MEETING_PLACE_TABLE . " (
    `mp_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `mp_name` varchar(50) NOT NULL COMMENT '장소명',
    `mp_address` varchar(255) NOT NULL COMMENT '주소',
    PRIMARY KEY (`mp_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . MEETING_SCHEDULE_TABLE . " (
    `ms_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `ma_id` int(11) NOT NULL COMMENT '추가계획id',
    `g_id` int(11) NOT NULL COMMENT '집단아이디',
    `mp_id` int(11) NOT NULL COMMENT '모임 장소 번호',
    `ms_guide` varchar(100) NOT NULL COMMENT '인도자',
    `ms_guide2` varchar(100) NOT NULL COMMENT '보조자',
    `ms_week` int(2) NOT NULL COMMENT '모임 요일',
    `ms_time` time NOT NULL COMMENT '모임시간',
    `ms_start_time` time NOT NULL COMMENT '봉사시작시간',
    `ms_finish_time` time NOT NULL COMMENT '봉사마치는시간',
    `ms_type` int(1) NOT NULL COMMENT '봉사형태',
    `copy_ms_id` int(11) NOT NULL COMMENT '구역을복사할 모임일정 ID',
    `ms_limit` text NOT NULL COMMENT '참석자 제한',
    PRIMARY KEY (`ms_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . MEMBER_TABLE . " (
    `mb_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `mb_name` varchar(50) NOT NULL COMMENT '이름',
    `mb_hash` varchar(255) NOT NULL COMMENT '비밀번호 해시',
    `mb_hp` varchar(255) NOT NULL COMMENT '연락처',
    `mb_sex` varchar(1) NOT NULL COMMENT '성별',
    `mb_position` varchar(1) NOT NULL COMMENT '직분',
    `mb_pioneer` varchar(1) NOT NULL COMMENT '파이오니아',
    `mb_auth` varchar(10) NOT NULL COMMENT '권한',
    `mb_display` int(11) NOT NULL COMMENT '전시대 선정/미선정',
    `mb_address` varchar(255) NOT NULL COMMENT '주소',
    `g_id` int(11) NOT NULL COMMENT '집단아이디',
    `mb_movein_date` date NOT NULL COMMENT '전입날짜',
    `mb_moveout_date` date NOT NULL COMMENT '전출날짜',
    `font_size` varchar(10) NOT NULL DEFAULT '' COMMENT '글자 크기',
    PRIMARY KEY (`mb_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . MINISTER_EVENT_TABLE . " (
    `me_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `me_date` datetime NOT NULL COMMENT '일정시작하는날짜',
    `me_date2` datetime NOT NULL COMMENT '일정이끝나는날짜',
    `me_switch` int(1) NOT NULL COMMENT 'date인지 datetime인지 구분',
    `me_title` varchar(50) NOT NULL,
    `me_color` int(2) NOT NULL COMMENT '일정색상',
    `me_content` varchar(300) NOT NULL,
    `mb_id` int(11) NOT NULL COMMENT '작성자',
    PRIMARY KEY (`me_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . MINISTER_REPORT_TABLE . " (
    `mr_id` int(11) NOT NULL AUTO_INCREMENT,
    `mb_id` int(11) NOT NULL COMMENT '작성자',
    `mr_date` date NOT NULL COMMENT '봉사보고작성날짜',
    `mr_hour` int(5) NOT NULL COMMENT '봉사시간(시)',
    `mr_min` int(2) NOT NULL COMMENT '봉사시간(분)',
    `mr_pub` int(5) NOT NULL COMMENT '전한출판물',
    `mr_video` int(5) NOT NULL COMMENT '보여준동영상',
    `mr_return` int(5) NOT NULL COMMENT '재방',
    `mr_study` int(5) NOT NULL COMMENT '연구',
    PRIMARY KEY (`mr_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . OPTION_TABLE . " (
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '아이디',
    `name` text NOT NULL COMMENT '옵션명',
    `value` text NOT NULL COMMENT '옵션값',
    PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . RETURN_VISIT_TABLE . " (
    `rv_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `mb_id` int(11) NOT NULL COMMENT 'member고유번호',
    `h_id` int(11) NOT NULL COMMENT 'house고유번호',
    `rv_content` text NOT NULL COMMENT '재방문 기록',
    `rv_datetime` datetime NOT NULL COMMENT '방문날짜/시간',
    `rv_transfer` int(11) NOT NULL COMMENT '양도',
    `create_datetime` datetime NOT NULL COMMENT '생성날짜',
    `update_datetime` datetime NOT NULL COMMENT '수정날짜',
    PRIMARY KEY (`rv_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . TELEPHONE_TABLE . " (
    `tp_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `tp_num` varchar(50) NOT NULL COMMENT '구역번호',
    `tp_name` varchar(50) NOT NULL COMMENT '구역이름',
    `mb_id` int(11) NOT NULL COMMENT '개인구역전도인id',
    `tp_mb_date` date NOT NULL COMMENT '개인구역 임명날짜',
    `tp_assigned` text NOT NULL COMMENT '배정된 전도인',
    `tp_assigned_date` date NOT NULL COMMENT '배정된 날짜',
    `tp_assigned_group` text NOT NULL COMMENT '배정된 짝 당 수',
    `tp_start_date` date NOT NULL COMMENT '봉사시작날짜',
    `tp_end_date` date NOT NULL COMMENT '봉사마친날짜',
    `m_id` int(11) NOT NULL COMMENT '배정된 모임 아이디',
    `tp_memo` text NOT NULL COMMENT '비고',
    `ms_id` int(11) NOT NULL COMMENT '모임아이디',
    `tp_status` varchar(255) NOT NULL COMMENT '호별/부재 상태',
    `tp_ms_all` int(11) NOT NULL COMMENT '전체/호별/공개증거 배정',
    `create_datetime` datetime NOT NULL COMMENT '생성날짜',
    `update_datetime` datetime NOT NULL COMMENT '수정날짜',
    PRIMARY KEY (`tp_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . TELEPHONE_HOUSE_TABLE . " (
    `tph_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'telephone_house 고유번호',
    `tp_id` int(11) NOT NULL COMMENT 'telephone 고유번호',
    `tph_number` varchar(50) NOT NULL COMMENT '전화번호',
    `tph_type` varchar(50) NOT NULL COMMENT '업종',
    `tph_name` varchar(255) NOT NULL COMMENT '상호',
    `tph_address` varchar(255) NOT NULL COMMENT '주소',
    `tph_order` int(11) NOT NULL COMMENT '순서',
    `tph_condition` varchar(50) NOT NULL COMMENT '상태',
    `tph_visit` varchar(1) NOT NULL COMMENT '만남여부',
    `tph_visit_old` varchar(1) NOT NULL COMMENT '이전 만남여부 ',
    `mb_id` int(11) NOT NULL COMMENT '재방문 전도인 ID',
    PRIMARY KEY (`tph_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . TELEPHONE_HOUSE_MEMO_TABLE . " (
    `tphm_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `tph_id` int(11) NOT NULL COMMENT 'telephone_house 고유번호',
    `tphm_content` varchar(255) NOT NULL COMMENT '내용',
    `mb_id` int(11) NOT NULL COMMENT '기록 전도인 ID',
    `tphm_condition` varchar(50) NOT NULL COMMENT '상태',
    `create_datetime` datetime NOT NULL COMMENT '생성날짜',
    `update_datetime` datetime NOT NULL COMMENT '수정날짜',
    PRIMARY KEY (`tphm_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

    $mysqli->query("CREATE TABLE IF NOT EXISTS " . TELEPHONE_RECORD_TABLE . " (
    `tpr_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `tp_id` int(11) NOT NULL COMMENT 'telephone고유번호',
    `tpr_assigned` text NOT NULL COMMENT '배정된전도인',
    `tpr_assigned_num` text NOT NULL COMMENT '배정된전도인 고유번호',
    `tpr_assigned_date` date NOT NULL COMMENT '배정된날짜',
    `tpr_assigned_group` text NOT NULL COMMENT '배정된 짝 당 수',
    `tpr_start_date` date NOT NULL COMMENT '봉사시작날짜',
    `tpr_end_date` date NOT NULL COMMENT '봉사마친날짜',
    `tpr_status` varchar(255) NOT NULL COMMENT '호별/부재 상태',
    `tpr_mb_name` varchar(50) NOT NULL COMMENT '개인구역 전도인 이름',
    `m_id` int(11) NOT NULL COMMENT '배정된 모임 아이디',
    `record_m_id` int(11) NOT NULL COMMENT '기록을 한 모임 아이디',
    `create_datetime` datetime NOT NULL COMMENT '생성날짜',
    `update_datetime` datetime NOT NULL COMMENT '수정날짜',
    PRIMARY KEY (`tpr_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . TELEPHONE_RETURN_VISIT_TABLE . " (
    `tprv_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `mb_id` int(11) NOT NULL COMMENT '재방문 전도인 ID',
    `tph_id` int(11) NOT NULL COMMENT 'telephone_house 고유번호',
    `tprv_content` varchar(255) NOT NULL COMMENT '재방문 기록',
    `tprv_datetime` datetime NOT NULL COMMENT '방문 날짜/시간',
    `tprv_transfer` int(11) NOT NULL COMMENT '양도',
    `create_datetime` datetime NOT NULL COMMENT '생성날짜',
    `update_datetime` datetime NOT NULL COMMENT '수정날짜',
    PRIMARY KEY (`tprv_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . TERRITORY_TABLE . " (
    `tt_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `tt_num` varchar(255) NOT NULL COMMENT '구역번호',
    `tt_name` varchar(255) NOT NULL COMMENT '구역명',
    `tt_type` varchar(50) NOT NULL COMMENT '구역형태',
    `mb_id` int(11) NOT NULL COMMENT '개인구역',
    `tt_mb_date` date NOT NULL COMMENT '개인구역 임명날짜',
    `tt_polygon` text NOT NULL COMMENT '경계',
    `tt_assigned` text NOT NULL COMMENT '배정된전도인',
    `tt_assigned_date` date NOT NULL COMMENT '배정날짜',
    `tt_assigned_group` text NOT NULL COMMENT '배정된 짝 당 수',
    `tt_address` varchar(255) NOT NULL COMMENT '대표주소',
    `tt_address2` varchar(255) NOT NULL COMMENT '대표주소2',
    `tt_start_date` date NOT NULL COMMENT '봉사시작날짜',
    `tt_end_date` date NOT NULL COMMENT '봉사마친날짜',
    `m_id` int(11) NOT NULL COMMENT '배정된 모임 아이디',
    `tt_memo` text NOT NULL COMMENT '구역추가요청',
    `ms_id` int(11) NOT NULL COMMENT '모임아이디',
    `tt_status` varchar(255) NOT NULL COMMENT '호별/부재 상태',
    `tt_ms_all` int(11) NOT NULL COMMENT '전체/호별/공개증거 배정',
    `create_datetime` datetime NOT NULL COMMENT '생성날짜',
    `update_datetime` datetime NOT NULL COMMENT '수정날짜',
    PRIMARY KEY (`tt_id`),
    KEY `tt_ms_all` (`tt_ms_all`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . TERRITORY_RECORD_TABLE . " (
    `ttr_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '고유번호',
    `tt_id` int(11) NOT NULL COMMENT '구역 고유번호',
    `ttr_assigned` text NOT NULL COMMENT '배정된전도인',
    `ttr_assigned_num` text NOT NULL COMMENT '배정된전도인 고유번호',
    `ttr_assigned_date` date NOT NULL COMMENT '배정된날짜',
    `ttr_assigned_group` text NOT NULL COMMENT '배정된 짝 당 수',
    `ttr_start_date` date NOT NULL COMMENT '봉사시작날짜',
    `ttr_end_date` date NOT NULL COMMENT '봉사마친날짜',
    `ttr_status` varchar(255) NOT NULL COMMENT '호별/부재 상태',
    `ttr_mb_name` varchar(50) NOT NULL COMMENT '개인구역 전도인 이름',
    `m_id` int(11) NOT NULL COMMENT '배정된 모임 아이디',
    `record_m_id` int(11) NOT NULL COMMENT '기록을 한 모임 아이디',
    `create_datetime` datetime NOT NULL COMMENT '생성날짜',
    `update_datetime` datetime NOT NULL COMMENT '수정날짜',
    PRIMARY KEY (`ttr_id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS " . WORK_LOG_TABLE . " (
    `wl_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '작업로그 고유번호',
    `wl_cdate` datetime NOT NULL COMMENT '작업실행 날짜/시간',
    `wl_key` varchar(255) NOT NULL COMMENT '작업실행 키값',
    PRIMARY KEY (`wl_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

    /* 구역 쪽지 테이블 (커스텀 추가) */
    $mysqli->query("CREATE TABLE IF NOT EXISTS `t_territory_message` (
    `tm_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `tt_id` int(10) unsigned NOT NULL,
    `tm_type` char(1) NOT NULL DEFAULT 'T',
    `mb_id` int(10) unsigned NOT NULL,
    `mb_name` varchar(50) NOT NULL,
    `tm_message` text NOT NULL,
    `tm_datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`tm_id`),
    KEY `idx_tt_type_datetime` (`tt_id`,`tm_type`,`tm_datetime`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $mysqli->query("CREATE TABLE IF NOT EXISTS `t_territory_message_read` (
    `tt_id` int(10) unsigned NOT NULL,
    `tm_type` char(1) NOT NULL DEFAULT 'T',
    `mb_id` int(10) unsigned NOT NULL,
    `last_read_id` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`tt_id`,`tm_type`,`mb_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 푸시 알림 구독 테이블
    $mysqli->query("CREATE TABLE IF NOT EXISTS `" . PUSH_SUBSCRIPTION_TABLE . "` (
    `ps_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `mb_id` int(10) unsigned NOT NULL,
    `ps_endpoint` text NOT NULL,
    `ps_auth` varchar(255) NOT NULL,
    `ps_p256dh` varchar(255) NOT NULL,
    `ps_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ps_id`),
    KEY `idx_mb_id` (`mb_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");



    if ($type == 'new') {

        $name = $_POST['name'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $mysqli->query("INSERT INTO " . MEMBER_TABLE . " (`mb_id`, `mb_name`, `mb_hash`, `mb_hp`, `mb_sex`, `mb_position`, `mb_pioneer`, `mb_auth`, `mb_display`, `mb_address`, `g_id`, `mb_movein_date`, `mb_moveout_date`, `font_size`) VALUES
(1, '" . $name . "', '" . $password . "', '', 'M', '', '', '1', 0, '', 0, '0000-00-00', '0000-00-00', '');");

        ?>
        <script>
            alert("설치가 완료되었습니다.");
            window.location.href = "<?= dirname($_SERVER['SCRIPT_NAME']) ?>/"; // 메인 페이지로 이동
        </script>
        <?php

    } elseif ($type == 'update') {

        if (!$mysqli->query("SHOW COLUMNS FROM " . DISPLAY_TABLE . " LIKE 'd_assigned_group'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . DISPLAY_TABLE . " ADD `d_assigned_group` text NOT NULL COMMENT '배정된 짝 당 수';");
        }

        if (!$mysqli->query("SHOW COLUMNS FROM " . DISPLAY_TABLE . " LIKE 'dp_num'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . DISPLAY_TABLE . " ADD `dp_num` int(11) NOT NULL DEFAULT '1' COMMENT '전시대 팀 번호';");
        }

        if (!$mysqli->query("SHOW COLUMNS FROM " . DISPLAY_PLACE_TABLE . " LIKE 'dp_count'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . DISPLAY_PLACE_TABLE . " ADD `dp_count` int(11) NOT NULL DEFAULT '1' COMMENT '전시대 장소 개수';");
        }

        if (!$mysqli->query("SHOW COLUMNS FROM " . MEETING_TABLE . " LIKE 'g_id'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . MEETING_TABLE . " ADD `g_id` int(11) NOT NULL COMMENT '집단아이디';");
        }

        if (!$mysqli->query("SHOW COLUMNS FROM " . MEETING_SCHEDULE_TABLE . " LIKE 'g_id'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . MEETING_SCHEDULE_TABLE . " ADD `g_id` int(11) NOT NULL COMMENT '집단아이디';");
        }

        if ($mysqli->query("SHOW COLUMNS FROM " . MEMBER_TABLE . " LIKE 'mb_vietnam'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . MEMBER_TABLE . " DROP `mb_vietnam`;");
        }

        if (!$mysqli->query("SHOW COLUMNS FROM " . MEMBER_TABLE . " LIKE 'g_id'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . MEMBER_TABLE . " ADD `g_id` int(11) NOT NULL COMMENT '집단아이디';");
        }

        if (!$mysqli->query("SHOW COLUMNS FROM " . TELEPHONE_TABLE . " LIKE 'tp_assigned_group'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TELEPHONE_TABLE . " ADD `tp_assigned_group` text NOT NULL COMMENT '배정된 짝 당 수';");
        }

        if (!$mysqli->query("SHOW COLUMNS FROM " . TELEPHONE_RECORD_TABLE . " LIKE 'tpr_assigned_group'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TELEPHONE_RECORD_TABLE . " ADD `tpr_assigned_group` text NOT NULL COMMENT '배정된 짝 당 수';");
        }

        if (!$mysqli->query("SHOW COLUMNS FROM " . TERRITORY_TABLE . " LIKE 'tt_assigned_group'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TERRITORY_TABLE . " ADD `tt_assigned_group` text NOT NULL COMMENT '배정된 짝 당 수';");
        }

        if (!$mysqli->query("SHOW COLUMNS FROM " . TERRITORY_RECORD_TABLE . " LIKE 'ttr_assigned_group'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TERRITORY_RECORD_TABLE . " ADD `ttr_assigned_group` text NOT NULL COMMENT '배정된 짝 당 수';");
        }

        // 2.2.0 추가
        if (!$mysqli->query("SHOW COLUMNS FROM " . MEETING_SCHEDULE_TABLE . " LIKE 'ms_limit'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . MEETING_SCHEDULE_TABLE . " ADD `ms_limit` text NOT NULL COMMENT '참석자 제한';");
        }

        if (!$mysqli->query("SHOW COLUMNS FROM " . MEETING_TABLE . " LIKE 'm_guide'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . MEETING_TABLE . " ADD `m_guide` varchar(50) NOT NULL COMMENT '당일 인도자' AFTER m_contents;");
        }

        if ($mysqli->query("SHOW COLUMNS FROM " . WORK_LOG_TABLE . " LIKE 'wl_id'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . WORK_LOG_TABLE . " MODIFY `wl_id` int(11) NOT NULL AUTO_INCREMENT;");
        }

        // 2.4.0 추가
        if (!$mysqli->query("SHOW COLUMNS FROM " . MEETING_TABLE . " LIKE 'm_cancle_reason'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . MEETING_TABLE . " ADD `m_cancle_reason` text NOT NULL COMMENT '봉사모임취소사유' AFTER m_cancle;");
        }
        if ($mysqli->query("SHOW COLUMNS FROM " . MEETING_TABLE . " LIKE 'ma_title'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . MEETING_TABLE . " DROP `ma_title`;");
        }

        // 2.4.4 추가
        if ($mysqli->query("SHOW COLUMNS FROM " . TERRITORY_TABLE . " LIKE 'tt_cdate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TERRITORY_TABLE . " CHANGE `tt_cdate` `create_datetime` datetime NOT NULL COMMENT '생성날짜';");
        }
        if ($mysqli->query("SHOW COLUMNS FROM " . TERRITORY_TABLE . " LIKE 'tt_udate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TERRITORY_TABLE . " CHANGE `tt_udate` `update_datetime` datetime NOT NULL COMMENT '수정날짜';");
        }

        if (!$mysqli->query("SHOW COLUMNS FROM " . TELEPHONE_TABLE . " LIKE 'create_datetime'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TELEPHONE_TABLE . " ADD `create_datetime` datetime NOT NULL COMMENT '생성날짜';");
        }
        if (!$mysqli->query("SHOW COLUMNS FROM " . TELEPHONE_TABLE . " LIKE 'update_datetime'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TELEPHONE_TABLE . " ADD `update_datetime` datetime NOT NULL COMMENT '수정날짜';");
        }

        if ($mysqli->query("SHOW COLUMNS FROM " . BOARD_TABLE . " LIKE 'b_cdate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . BOARD_TABLE . " CHANGE `b_cdate` `create_datetime` datetime NOT NULL COMMENT '생성날짜';");
        }
        if ($mysqli->query("SHOW COLUMNS FROM " . BOARD_TABLE . " LIKE 'b_udate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . BOARD_TABLE . " CHANGE `b_udate` `update_datetime` datetime NOT NULL COMMENT '수정날짜';");
        }

        if ($mysqli->query("SHOW COLUMNS FROM " . HOUSE_MEMO_TABLE . " LIKE 'hm_cdate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . HOUSE_MEMO_TABLE . " CHANGE `hm_cdate` `create_datetime` datetime NOT NULL COMMENT '생성날짜';");
        }
        if ($mysqli->query("SHOW COLUMNS FROM " . HOUSE_MEMO_TABLE . " LIKE 'hm_udate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . HOUSE_MEMO_TABLE . " CHANGE `hm_udate` `update_datetime` datetime NOT NULL COMMENT '수정날짜';");
        }

        if ($mysqli->query("SHOW COLUMNS FROM " . TELEPHONE_HOUSE_MEMO_TABLE . " LIKE 'tphm_cdate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TELEPHONE_HOUSE_MEMO_TABLE . " CHANGE `tphm_cdate` `create_datetime` datetime NOT NULL COMMENT '생성날짜';");
        }
        if ($mysqli->query("SHOW COLUMNS FROM " . TELEPHONE_HOUSE_MEMO_TABLE . " LIKE 'tphm_udate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TELEPHONE_HOUSE_MEMO_TABLE . " CHANGE `tphm_udate` `update_datetime` datetime NOT NULL COMMENT '수정날짜';");
        }

        if ($mysqli->query("SHOW COLUMNS FROM " . RETURN_VISIT_TABLE . " LIKE 'rv_cdate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . RETURN_VISIT_TABLE . " CHANGE `rv_cdate` `create_datetime` datetime NOT NULL COMMENT '생성날짜';");
        }
        if ($mysqli->query("SHOW COLUMNS FROM " . RETURN_VISIT_TABLE . " LIKE 'rv_udate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . RETURN_VISIT_TABLE . " CHANGE `rv_udate` `update_datetime` datetime NOT NULL COMMENT '수정날짜';");
        }

        if ($mysqli->query("SHOW COLUMNS FROM " . TELEPHONE_RETURN_VISIT_TABLE . " LIKE 'tprv_cdate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TELEPHONE_RETURN_VISIT_TABLE . " CHANGE `tprv_cdate` `create_datetime` datetime NOT NULL COMMENT '생성날짜';");
        }
        if ($mysqli->query("SHOW COLUMNS FROM " . TELEPHONE_RETURN_VISIT_TABLE . " LIKE 'tprv_udate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TELEPHONE_RETURN_VISIT_TABLE . " CHANGE `tprv_udate` `update_datetime` datetime NOT NULL COMMENT '수정날짜';");
        }

        if ($mysqli->query("SHOW COLUMNS FROM " . TERRITORY_RECORD_TABLE . " LIKE 'ttr_cdate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TERRITORY_RECORD_TABLE . " CHANGE `ttr_cdate` `create_datetime` datetime NOT NULL COMMENT '생성날짜';");
        }
        if ($mysqli->query("SHOW COLUMNS FROM " . TERRITORY_RECORD_TABLE . " LIKE 'ttr_udate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TERRITORY_RECORD_TABLE . " CHANGE `ttr_udate` `update_datetime` datetime NOT NULL COMMENT '수정날짜';");
        }

        if ($mysqli->query("SHOW COLUMNS FROM " . TELEPHONE_RECORD_TABLE . " LIKE 'tpr_cdate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TELEPHONE_RECORD_TABLE . " CHANGE `tpr_cdate` `create_datetime` datetime NOT NULL COMMENT '생성날짜';");
        }
        if ($mysqli->query("SHOW COLUMNS FROM " . TELEPHONE_RECORD_TABLE . " LIKE 'tpr_udate'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . TELEPHONE_RECORD_TABLE . " CHANGE `tpr_udate` `update_datetime` datetime NOT NULL COMMENT '수정날짜';");
        }

        // 2.4.6 추가 (구역지도 polygon 데이터에서 역슬래시 제거)
        $mysqli->query("UPDATE " . TERRITORY_TABLE . " SET `tt_polygon` = REPLACE(`tt_polygon`, '\\\\', '')");

        if (!$mysqli->query("SHOW COLUMNS FROM " . MEMBER_TABLE . " LIKE 'font_size'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . MEMBER_TABLE . " ADD `font_size` varchar(10) NOT NULL DEFAULT '' COMMENT '글자 크기';");
        }

        // 2.5.0 추가
        if (!$mysqli->query("SHOW COLUMNS FROM " . DISPLAY_PLACE_TABLE . " LIKE 'ms_id'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . DISPLAY_PLACE_TABLE . " ADD `ms_id` int(11) NOT NULL DEFAULT '0' COMMENT '모임아이디';");
        }
        if (!$mysqli->query("SHOW COLUMNS FROM " . DISPLAY_PLACE_TABLE . " LIKE 'd_ms_all'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . DISPLAY_PLACE_TABLE . " ADD `d_ms_all` int(11) NOT NULL DEFAULT '0' COMMENT '모임형태';");
        }

        if (!$mysqli->query("SHOW COLUMNS FROM " . MEETING_TABLE . " LIKE 'm_start_time'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . MEETING_TABLE . " ADD `m_start_time` time NOT NULL COMMENT '봉사시작시간';");
        }
        if (!$mysqli->query("SHOW COLUMNS FROM " . MEETING_TABLE . " LIKE 'm_finish_time'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . MEETING_TABLE . " ADD `m_finish_time` time NOT NULL COMMENT '봉사마치는시간';");
        }


        // 2.5.11 추가
        if (!$mysqli->query("SHOW COLUMNS FROM " . MEETING_TABLE . " LIKE 'ms_limit'")->fetch_row()) {
            $mysqli->query("ALTER TABLE " . MEETING_TABLE . " ADD `ms_limit` int(11) DEFAULT 0 COMMENT '지원자 수 제한' AFTER g_id;");
            $mysqli->query("UPDATE " . MEETING_TABLE . " m INNER JOIN " . MEETING_SCHEDULE_TABLE . " ms ON m.ms_id = ms.ms_id SET m.ms_limit = IF(ms.ms_limit = '', -1, ms.ms_limit);");
        }

        // 2.5.12 추가
        if ($mysqli->query("SHOW COLUMNS FROM " . MEETING_TABLE . " LIKE 'ms_limit'")->fetch_row()) {
            // 한번만 실행되도록 로그 확인
            $check_log = $mysqli->query("SELECT count(*) FROM " . WORK_LOG_TABLE . " WHERE wl_key = 'batch_fix_ms_limit_2_5_12'");
            $log_row = $check_log->fetch_row();
            if ($log_row[0] == 0) {
                $mysqli->query("UPDATE " . MEETING_TABLE . " m INNER JOIN " . MEETING_SCHEDULE_TABLE . " ms ON m.ms_id = ms.ms_id SET m.ms_limit = -1 WHERE ms.ms_limit = '' AND m.ms_limit = 0;");
                $mysqli->query("INSERT INTO " . WORK_LOG_TABLE . " (wl_cdate, wl_key) VALUES (NOW(), 'batch_fix_ms_limit_2_5_12')");
            }
        }
        ?>
        <script>
            alert("업데이트가 완료되었습니다.");
            window.location.href = "<?= ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/"; // 메인 페이지로 이동
        </script>
        <?php

    } elseif ($type == 'insert_admin') {

        if (empty($_POST['name']) || empty($_POST['password']) || empty($_POST['password_confirm'])) {
            $redirect_url = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/update.php';
            echo '<script> alert("필요한 정보를 입력하지 않았습니다."); location.href="' . $redirect_url . '"; </script>';
            exit;
        }

        if ($_POST['password'] != $_POST['password_confirm']) {
            $redirect_url = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/update.php';
            echo '<script> alert("관리자 비밀번호와 비밀번호 확인이 일치하지 않습니다."); location.href="' . $redirect_url . '"; </script>';
            exit;
        }

        $name = $_POST['name'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $mysqli->query("INSERT INTO " . MEMBER_TABLE . " ( `mb_name`, `mb_hash`, `mb_hp`, `mb_sex`, `mb_position`, `mb_pioneer`, `mb_auth`, `mb_display`, `mb_address`, `g_id`, `mb_movein_date`, `mb_moveout_date`, `font_size`) VALUES
    ('" . $name . "', '" . $password . "', '', 'M', '', '', '1', 0, '', 0, '0000-00-00', '0000-00-00', '');");

        ?>
        <script>
            alert("관리자 계정 생성이 완료되었습니다.");
            window.location.href = "<?= ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/"; // 메인 페이지로 이동
        </script>
        <?php

    }

    ?>
</body>

</html>