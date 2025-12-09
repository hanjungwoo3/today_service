<?php include_once('../config.php');?>

<?php

function excel_update($arr_h_id, $gubun, $field, $arr_field_item){

	$sql = "$gubun$field = \r\n";
	$sql .= "        case \r\n";
	for ($num = 0; $num < count($arr_h_id); $num++)
	{
		$sql .= "             when h_id = " . $arr_h_id[$num] . " then '" . $arr_field_item[$num] ."'\r\n";
	}
	$sql .= "             else ".$field."\r\n";
	$sql .= "        end\r\n";

	return $sql;

}


if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
	require_once 'territory_excel_upload_php8.php';
	exit;
}

require_once dirname(__FILE__) . '/../classes/PHPExcel.php'; // PHPExcel.php을 불러와야 하며, 경로는 사용자의 설정에 맞게 수정해야 한다.
$objPHPExcel = new PHPExcel();
require_once dirname(__FILE__) . '/../classes/PHPExcel/IOFactory.php'; // IOFactory.php을 불러와야 하며, 경로는 사용자의 설정에 맞게 수정해야 한다.

// 읽어들일 엑셀 파일의 경로와 파일명을 지정한다.
//$filename = './map_13_426.xlsx';
$filename = $_FILES['excel']['tmp_name'];

$sql="SELECT MAX(h_id) FROM ".HOUSE_TABLE." WHERE tt_id=".$pid;
$result = $mysqli->query($sql);
$max_fetch = $result->fetch_row();
$max_list_idx=$max_fetch[0]+1;


try {
  // 업로드 된 엑셀 형식에 맞는 Reader객체를 만든다.
    $objReader = PHPExcel_IOFactory::createReaderForFile($filename);
    // 읽기전용으로 설정
    $objReader->setReadDataOnly(true);
    // 엑셀파일을 읽는다
    $objExcel = $objReader->load($filename);
    // 첫번째 시트를 선택
    $objExcel->setActiveSheetIndex(0);
    $objWorksheet = $objExcel->getActiveSheet();
    $rowIterator = $objWorksheet->getRowIterator();
    foreach ($rowIterator as $row) { // 모든 행에 대해서
               $cellIterator = $row->getCellIterator();
               $cellIterator->setIterateOnlyExistingCells(false);
    }
    $maxRow = $objWorksheet->getHighestRow();

	$intNEW=0; $intUPD=0; $intDEL=0;
	$strNEW=""; $strUPD=""; $strDEL="";
	// 업데이트용 문자열 구분자
	$updateDelimiter = '@@SEP@@';

	$strEXCEL = "<table border=1>\r\n";

    for ($i = 2 ; $i <= $maxRow ; $i++) {
		$nud					= $objWorksheet->getCell('A' . $i)->getValue(); // A열
		$h_id			= $objWorksheet->getCell('B' . $i)->getValue(); // B열
		// 주소/건물번호 컬럼은 표시 문자열로 읽어 하이픈 등이 보존되도록 처리
		$h_address1			= $objWorksheet->getCell('C' . $i)->getFormattedValue(); // C열
		$h_address2			= $objWorksheet->getCell('D' . $i)->getFormattedValue(); // D열
		$h_address3		= $objWorksheet->getCell('E' . $i)->getFormattedValue(); // E열
    $h_address4			= $objWorksheet->getCell('F' . $i)->getFormattedValue(); // F열
    $h_address5			= $objWorksheet->getCell('G' . $i)->getFormattedValue(); // G열
		$h_order			= $objWorksheet->getCell('H' . $i)->getValue(); // H열
		$h_visit			= $objWorksheet->getCell('I' . $i)->getValue(); // I열
		$h_condition			= $objWorksheet->getCell('J' . $i)->getValue(); // J열

    // 특수문자 제거
    $nud = upload_filter($nud);
    $h_id			= upload_filter($h_id);
		$h_address1			= upload_filter($h_address1);
		$h_address2			= upload_filter($h_address2);
		$h_address3		= upload_filter($h_address3);
    $h_address4			= upload_filter($h_address4);
    $h_address5			= upload_filter($h_address5);
		$h_order			= upload_filter($h_order);
		$h_visit			= upload_filter($h_visit);
		$h_condition			= upload_filter($h_condition);

    if(empty($nud)){ continue; }

		$strEXCEL .= "<tr>\r\n";
		$strEXCEL .= "	<td>".$nud."</td>\r\n";
		$strEXCEL .= "	<td>".$h_id."</td>\r\n";
		$strEXCEL .= "	<td>".$h_address1."</td>\r\n";
		$strEXCEL .= "	<td>".$h_address2."</td>\r\n";
		$strEXCEL .= "	<td>".$h_address3."</td>\r\n";
    $strEXCEL .= "	<td>".$h_address4."</td>\r\n";
    $strEXCEL .= "	<td>".$h_address5."</td>\r\n";
		$strEXCEL .= "	<td>".$h_order."</td>\r\n";
		$strEXCEL .= "	<td>".$h_visit."</td>\r\n";
		$strEXCEL .= "	<td>".$h_condition."</td>\r\n";
		$strEXCEL .= "</tr>\r\n";


		if (strtoupper($nud)=="N" || strtoupper($nud)=="n"){

			if ($intNEW>0) $strNEW.="union \r\n";

			if (empty($h_order)) $h_order = $i-1;

			$strNEW .= "select '$pid','$h_address1','$h_address2','$h_address3','$h_address4','$h_address5','$h_condition','$h_visit','','$h_order',0 \r\n";

			$intNEW++;
			$max_list_idx++;

		}elseif(strtoupper($nud)=="U" || strtoupper($nud)=="u"){

			if ($intUPD>0){
  			$strUPD.=",";
        $str_h_id .= ",";
			}

			$strUPD.=$h_id;
      // h_id 목록은 쉼표(,)로만 구분하여 WHERE IN 절에서 사용한다.
      $str_h_id .= $h_id;

			$str_h_address1 .= ($intUPD > 0 ? $updateDelimiter : '') . $h_address1;
			$str_h_address2 .= ($intUPD > 0 ? $updateDelimiter : '') . $h_address2;
			$str_h_address3 .= ($intUPD > 0 ? $updateDelimiter : '') . $h_address3;
      $str_h_address4 .= ($intUPD > 0 ? $updateDelimiter : '') . $h_address4;
      $str_h_address5 .= ($intUPD > 0 ? $updateDelimiter : '') . $h_address5;
			$str_h_condition .= ($intUPD > 0 ? $updateDelimiter : '') . $h_condition;
			$str_h_visit .= ($intUPD > 0 ? $updateDelimiter : '') . $h_visit;
			$str_h_order .= ($intUPD > 0 ? $updateDelimiter : '') . $h_order;

			$intUPD++;

		}elseif(strtoupper($nud)=="D" || strtoupper($nud)=="d"){

			if ($intDEL>0) $strDEL.=",";

			$strDEL.=$h_id;
			$intDEL++;

		}
  }

	$strEXCEL .= "</table>\r\n";

	if ($strNEW){

		$sql = "INSERT INTO ".HOUSE_TABLE."(tt_id, h_address1, h_address2, h_address3, h_address4, h_address5, h_condition, h_visit, h_visit_old, h_order, mb_id)\r\n";
		$sql .= $strNEW;
		$result=$mysqli->query($sql);
	}

	if ($strUPD){

		$arr_h_id =			explode(",", $str_h_id);
		$arr_h_address1 =		explode($updateDelimiter, $str_h_address1);
		$arr_h_address2 =		explode($updateDelimiter, $str_h_address2);
		$arr_h_address3 =		explode($updateDelimiter, $str_h_address3);
    $arr_h_address4 =		explode($updateDelimiter, $str_h_address4);
    $arr_h_address5 =		explode($updateDelimiter, $str_h_address5);
		$arr_h_condition =		explode($updateDelimiter, $str_h_condition);
		$arr_h_visit =		explode($updateDelimiter, $str_h_visit);
		$arr_h_order =		explode($updateDelimiter, $str_h_order);

		$sql = " UPDATE ".HOUSE_TABLE." \r\n";
		$sql .= "    SET \r\n";

		$sql .= excel_update($arr_h_id, " ", "h_address1",	     $arr_h_address1);
		$sql .= excel_update($arr_h_id, ",", "h_address2",	     $arr_h_address2);
		$sql .= excel_update($arr_h_id, ",", "h_address3",     $arr_h_address3);
    $sql .= excel_update($arr_h_id, ",", "h_address4",     $arr_h_address4);
    $sql .= excel_update($arr_h_id, ",", "h_address5",     $arr_h_address5);
		$sql .= excel_update($arr_h_id, ",", "h_condition",	     $arr_h_condition);
		$sql .= excel_update($arr_h_id, ",", "h_visit",	     $arr_h_visit);
		$sql .= excel_update($arr_h_id, ",", "h_order",	     $arr_h_order);

		$sql .= " WHERE tt_id='$pid' AND h_id IN ($str_h_id) \r\n";

		$result=$mysqli->query($sql);

	}

	if ($strDEL){

		$sql = "DELETE FROM ".HOUSE_TABLE." WHERE tt_id='$pid' AND h_id IN ($strDEL)";
		$result=$mysqli->query($sql);

	}

	//작업완료 후 업로드 된 엑셀 파일 삭제
	// @unlink($filename);

}
 catch (exception $e) {
    echo 'error 엑셀파일을 읽는도중 오류가 발생하였습니다.';
}

?>
