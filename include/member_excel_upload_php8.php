<?php 
/**
 * 회원 정보 엑셀 업로드 처리 (PHP 8.2 이상 버전)
 * 
 * 이 파일은 엑셀 파일을 통해 회원 정보를 일괄 업로드하는 기능을 제공합니다.
 * 보안을 위해 입력값 검증과 데이터 암호화를 수행합니다.
 */

// Include the PhpSpreadsheet library
require_once __DIR__.'/../classes/Psr/SimpleCache/CacheInterface.php';
require_once __DIR__.'/../classes/PhpSpreadsheet-2.2.2/PhpSpreadsheet/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// 읽어들일 엑셀 파일의 경로와 파일명을 지정한다.
$filename = $_FILES['excel']['tmp_name'];

try {
    // 트랜잭션 시작
    $mysqli->begin_transaction();

    // 업로드 된 엑셀 형식에 맞는 Reader객체를 만든다.
    $objReader = IOFactory::createReaderForFile($filename);
    // 읽기전용으로 설정
    $objReader->setReadDataOnly(true);
    // 엑셀파일을 읽는다
    $spreadsheet = $objReader->load($filename);
    // 첫번째 시트를 선택
    $worksheet = $spreadsheet->getActiveSheet();
    $rowIterator = $worksheet->getRowIterator();

    foreach ($rowIterator as $row) { // 모든 행에 대해서
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
    }
    
    $maxRow = $worksheet->getHighestRow();

    $intNEW = 0; 
    $intUPD = 0; 
    $intDEL = 0;
    $strNEW = ""; 
    $strUPD = ""; 
    $strDEL = "";
    $strComma = "";

    $str_mb_id = '';
    $str_mb_name = '';
    $str_mb_pw = '';
    $str_mb_hp = '';
    $str_mb_sex = '';
    $str_mb_position = '';
    $str_mb_pioneer = '';
    $str_mb_address = '';
    $str_mb_display = '';
    $str_g_id = '';
    $str_mb_movein_date = '';
    $str_mb_moveout_date = '';

    for ($i = 2 ; $i <= $maxRow ; $i++) {
        $nud            = $worksheet->getCell('A' . $i)->getValue(); // A열
        $mb_id          = $worksheet->getCell('B' . $i)->getValue(); // B열
        $mb_name        = $worksheet->getCell('C' . $i)->getValue(); // C열
        $mb_pw          = $worksheet->getCell('D' . $i)->getValue(); // D열
        $mb_hp          = $worksheet->getCell('E' . $i)->getValue(); // E열
        $mb_sex         = $worksheet->getCell('F' . $i)->getValue(); // F열
        $mb_address     = $worksheet->getCell('G' . $i)->getValue(); // G열
        $mb_position    = $worksheet->getCell('H' . $i)->getValue(); // H열
        $mb_pioneer		= $worksheet->getCell('I' . $i)->getValue(); // I열
        $mb_display		= $worksheet->getCell('J' . $i)->getValue(); // J열
        $g_id			= $worksheet->getCell('K' . $i)->getValue(); // K열
        $mb_movein_date = $worksheet->getCell('L' . $i)->getValue();
        $mb_movein_date = !empty($mb_movein_date) ? time_convert_EXCEL_to_PHP($mb_movein_date) : '0000-00-00'; // L열
        $mb_moveout_date = $worksheet->getCell('M' . $i)->getValue();
        $mb_moveout_date= !empty($mb_moveout_date) ? time_convert_EXCEL_to_PHP($mb_moveout_date) : '0000-00-00'; // M열

        // N 처리 시 기본값 설정
        if (strtoupper($nud ?? '') == "N") {
            // mb_name만 있으면 처리하고, 나머지는 기본값으로 설정
            if (empty($mb_name)) continue; // mb_name이 없으면 건너뛰기
            if (empty($mb_pw)) continue; // mb_pw이 없으면 건너뛰기
            
            // 기본값 설정
            $mb_sex = !empty($mb_sex) ? $mb_sex : 'M'; // 성별 기본값: 형제
            $mb_position = !empty($mb_position) ? $mb_position : ''; // 직책 기본값: 전도인
            $mb_pioneer = !empty($mb_pioneer) ? $mb_pioneer : '1'; // 파이오니아 기본값: 전도인
            $mb_display = !empty($mb_display) ? $mb_display : '0'; // 전시대 기본값: 미선정
            $g_id = !empty($g_id) ? $g_id : '0'; // 봉사집단 기본값: 없음
            $mb_movein_date = !empty($mb_movein_date) ? $mb_movein_date : '0000-00-00'; // 전입날짜 기본값
            $mb_moveout_date = !empty($mb_moveout_date) ? $mb_moveout_date : '0000-00-00'; // 전출날짜 기본값
        }

        // 전화번호 숫자만 추출
        if ($mb_hp) {
            $mb_hp = preg_replace('/[^0-9]/', '', $mb_hp);
        }

        $hash       = $mb_pw?password_hash($mb_pw, PASSWORD_BCRYPT):'';
        $mb_hp      = $mb_hp?encrypt($mb_hp):'';
        $mb_address = $mb_address?encrypt($mb_address):'';

		// 특수문자 제거
		$nud            = upload_filter($nud);
		$mb_id			= upload_filter($mb_id);
		$mb_name		= upload_filter($mb_name);
		$mb_pw		    = upload_filter($mb_pw);
		$mb_hp		    = upload_filter($mb_hp);
		$mb_sex		    = upload_filter($mb_sex);
		$mb_address		= upload_filter($mb_address);
		$mb_position	= upload_filter($mb_position);
		$mb_pioneer		= upload_filter($mb_pioneer);
		$mb_display	    = upload_filter($mb_display);
		$g_id	        = upload_filter($g_id);
		$mb_movein_date	= upload_filter($mb_movein_date);
		$mb_moveout_date	= upload_filter($mb_moveout_date);

		if(empty($nud)){ continue; }

		if (strtoupper($nud ?? '')=="N"){
			if ($intNEW>0) $strNEW.="union \r\n";
			$strNEW .= "select '{$mb_name}','{$hash}','{$mb_hp}','{$mb_sex}','{$mb_position}','','{$mb_pioneer}','{$mb_address}','{$mb_display}','{$g_id}','{$mb_movein_date}','{$mb_moveout_date}','' \r\n";
			$intNEW++;
		}elseif(strtoupper($nud ?? '')=="U"){
			if ($intUPD>0){
				$strComma=",";
				$strUPD.=",";
			}

			$strUPD.=$mb_id;

			$str_mb_id.=$strComma.$mb_id;
			$str_mb_name.=$strComma.$mb_name;
  		    if($hash) $str_mb_pw.=$strComma.$hash;
			$str_mb_hp.=$strComma.$mb_hp;
			$str_mb_sex.=$strComma.$mb_sex;
			$str_mb_position.=$strComma.$mb_position;
			$str_mb_pioneer.=$strComma.$mb_pioneer;
			$str_mb_address.=$strComma.$mb_address;
  		    $str_mb_display.=$strComma.$mb_display;
  		    $str_g_id.=$strComma.$g_id;
  		    $str_mb_movein_date.=$strComma.$mb_movein_date;
  		    $str_mb_moveout_date.=$strComma.$mb_moveout_date;

			$intUPD++;
		}elseif(strtoupper($nud ?? '')=="D"){
			if ($intDEL>0) $strDEL.=",";
			$strDEL.=$mb_id;
			$intDEL++;
		}
    }

    if ($strNEW){
		$sql = "INSERT INTO ".MEMBER_TABLE."(mb_name, mb_hash, mb_hp, mb_sex, mb_position, mb_auth, mb_pioneer, mb_address, mb_display, g_id, mb_movein_date, mb_moveout_date, font_size)\r\n";
		$sql .= $strNEW;
		$result = $mysqli->query($sql);
        if (!$result) {
            throw new Exception("데이터 입력 중 오류가 발생했습니다: " . $mysqli->error);
        }
	}

    if ($strUPD){
		$arr_mb_id = explode(",",$str_mb_id);
		$arr_mb_name = explode(",",$str_mb_name);
  	    if(!empty($str_mb_pw)) $arr_mb_pw = explode(",",$str_mb_pw);
		$arr_mb_hp = explode(",",$str_mb_hp);
		$arr_mb_sex =	explode(",",$str_mb_sex);
		$arr_mb_position = explode(",",$str_mb_position);
		$arr_mb_pioneer = explode(",",$str_mb_pioneer);
		$arr_mb_address = explode(",",$str_mb_address);
  	    $arr_mb_display = explode(",",$str_mb_display);
  	    $arr_g_id = explode(",",$str_g_id);
  	    $arr_mb_movein_date =	explode(",",$str_mb_movein_date);
  	    $arr_mb_moveout_date =	explode(",",$str_mb_moveout_date);

		$sql = " UPDATE ".MEMBER_TABLE." \r\n";
		$sql .= "    SET \r\n";

		$sql .= excel_update($arr_mb_id, " ", "mb_name", $arr_mb_name);
  	    if(isset($arr_mb_pw)) $sql .= excel_update($arr_mb_id, ",", "mb_hash", $arr_mb_pw);
		$sql .= excel_update($arr_mb_id, ",", "mb_hp", $arr_mb_hp);
		$sql .= excel_update($arr_mb_id, ",", "mb_sex", $arr_mb_sex);
		$sql .= excel_update($arr_mb_id, ",", "mb_position", $arr_mb_position);
  	    $sql .= excel_update($arr_mb_id, ",", "mb_pioneer", $arr_mb_pioneer);
		$sql .= excel_update($arr_mb_id, ",", "mb_address", $arr_mb_address);
  	    $sql .= excel_update($arr_mb_id, ",", "mb_display", $arr_mb_display);
  	    $sql .= excel_update($arr_mb_id, ",", "g_id", $arr_g_id);
  	    $sql .= excel_update($arr_mb_id, ",", "mb_movein_date", $arr_mb_movein_date);
  	    $sql .= excel_update($arr_mb_id, ",", "mb_moveout_date", $arr_mb_moveout_date);

		$sql .= " WHERE mb_id IN ($str_mb_id) \r\n";
		$result = $mysqli->query($sql);
        if (!$result) {
            throw new Exception("데이터 수정 중 오류가 발생했습니다: " . $mysqli->error);
        }
	}

    if ($strDEL){
		$sql = "DELETE FROM ".MEMBER_TABLE." WHERE mb_id IN ($strDEL)";
		$result = $mysqli->query($sql);
        if (!$result) {
            throw new Exception("데이터 삭제 중 오류가 발생했습니다: " . $mysqli->error);
        }
	}

    // 트랜잭션 커밋
    $mysqli->commit();
    echo "success 데이터가 성공적으로 처리되었습니다.";

} catch(Exception $e) {
    // 트랜잭션 롤백
    $mysqli->rollback();
    echo "error " . $e->getMessage();
} finally {
    // 임시 파일 삭제
    if (file_exists($filename)) {
        @unlink($filename);
    }
}
?>