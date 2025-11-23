<?php
include_once('../config.php');

function excel_update($arr_tph_id, $gubun, $field, $arr_field_item){

	$sql = "$gubun$field = \r\n";
	$sql .= "        case \r\n";
	for ($num = 0; $num < count($arr_tph_id); $num++)
	{
		$sql .= "             when tph_id = " . $arr_tph_id[$num] . " then '" . $arr_field_item[$num] ."'\r\n";
	}
	$sql .= "             else ".$field."\r\n";
	$sql .= "        end\r\n";

	return $sql;

}

if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
	require_once 'telephone_excel_upload_php8.php';
	exit;
}

require_once dirname(__FILE__) . '/../classes/PHPExcel.php'; // PHPExcel.php을 불러와야 하며, 경로는 사용자의 설정에 맞게 수정해야 한다.
$objPHPExcel = new PHPExcel();
require_once dirname(__FILE__) . '/../classes/PHPExcel/IOFactory.php'; // IOFactory.php을 불러와야 하며, 경로는 사용자의 설정에 맞게 수정해야 한다.

// 읽어들일 엑셀 파일의 경로와 파일명을 지정한다.
//$filename = './map_13_426.xlsx';
$filename = $_FILES['excel']['tmp_name'];

$sql="SELECT MAX(tph_id) FROM ".TELEPHONE_HOUSE_TABLE." WHERE tp_id=".$pid;
$result = $mysqli->query($sql);
$max_fetch = $result->fetch_row();

$max_list_idx = $max_fetch[0]+1;

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
	$strComma="";

	$strEXCEL = "<table border=1>\r\n";

    for ($i = 2 ; $i <= $maxRow ; $i++) {
		$nud					= $objWorksheet->getCell('A' . $i)->getValue(); // A열
		$tph_id			= $objWorksheet->getCell('B' . $i)->getValue(); // B열
		// 전화번호/주소는 포맷된 표시 문자열로 읽어 하이픈 등이 보존되도록 처리
		$tph_number			= $objWorksheet->getCell('C' . $i)->getFormattedValue(); // C열
		$tph_type			= $objWorksheet->getCell('D' . $i)->getValue(); // D열
		$tph_name		= $objWorksheet->getCell('E' . $i)->getValue(); // E열
    $tph_address			= $objWorksheet->getCell('F' . $i)->getFormattedValue(); // F열
		$tph_order			= $objWorksheet->getCell('G' . $i)->getValue(); // G열

    // 특수문자 제거
    $nud = upload_filter($nud);
    $tph_id	= upload_filter($tph_id);
		$tph_number	= upload_filter($tph_number);
		$tph_type	= upload_filter($tph_type);
		$tph_name	= upload_filter($tph_name);
    $tph_address	= upload_filter($tph_address);
    $tph_order = upload_filter($tph_order);

    if(empty($nud)){ continue; }

		$strEXCEL .= "<tr>\r\n";
		$strEXCEL .= "	<td>".$nud."</td>\r\n";
		$strEXCEL .= "	<td>".$tph_id."</td>\r\n";
		$strEXCEL .= "	<td>".$tph_number."</td>\r\n";
		$strEXCEL .= "	<td>".$tph_type."</td>\r\n";
		$strEXCEL .= "	<td>".$tph_name."</td>\r\n";
    $strEXCEL .= "	<td>".$tph_address."</td>\r\n";
    $strEXCEL .= "	<td>".$tph_order."</td>\r\n";
		$strEXCEL .= "</tr>\r\n";


		if (strtoupper($nud)=="N" || strtoupper($nud)=="n"){

			if ($intNEW>0) $strNEW.="union \r\n";

			if (empty($tph_order)) $tph_order = $i-4;

			$strNEW .= "select '$pid','$tph_number','$tph_type','$tph_name','$tph_address','$tph_order','','','',0 \r\n";

			$intNEW++;
			$max_list_idx++;

		}elseif(strtoupper($nud)=="U" || strtoupper($nud)=="u"){

			if ($intUPD>0){
  			$strComma=",";
  			$strUPD.=",";
			}

			$strUPD.=$tph_id;

			$str_tph_number.=$strComma.$tph_number;
			$str_tph_type.=$strComma.$tph_type;
			$str_tph_name.=$strComma.$tph_name;
      $str_tph_address.=$strComma.$tph_address;
      $str_tph_order.=$strComma.$tph_order;
      $str_tph_id.=$strComma.$tph_id;

			$intUPD++;

		}elseif(strtoupper($nud)=="D" || strtoupper($nud)=="d"){

			if ($intDEL>0) $strDEL.=",";

			$strDEL.=$tph_id;
			$intDEL++;

		}
  }

	$strEXCEL .= "</table>\r\n";

	if ($strNEW){

		$sql = "INSERT INTO ".TELEPHONE_HOUSE_TABLE."(tp_id, tph_number, tph_type, tph_name, tph_address, tph_order, tph_condition, tph_visit, tph_visit_old, mb_id)\r\n";
		$sql .= $strNEW;
		$result=$mysqli->query($sql);
	}

	if ($strUPD){

		$arr_tph_id =			explode(",",$str_tph_id);
		$arr_tph_number =		explode(",",$str_tph_number);
		$arr_tph_type =		explode(",",$str_tph_type);
		$arr_tph_name =		explode(",",$str_tph_name);
    $arr_tph_address =		explode(",",$str_tph_address);
    $arr_tph_order =		explode(",",$str_tph_order);

		$sql = " UPDATE ".TELEPHONE_HOUSE_TABLE." \r\n";
		$sql .= "    SET \r\n";

		$sql .= excel_update($arr_tph_id, " ", "tph_number", $arr_tph_number);
		$sql .= excel_update($arr_tph_id, ",", "tph_type", $arr_tph_type);
		$sql .= excel_update($arr_tph_id, ",", "tph_name", $arr_tph_name);
    $sql .= excel_update($arr_tph_id, ",", "tph_address", $arr_tph_address);
    $sql .= excel_update($arr_tph_id, ",", "tph_order", $arr_tph_order);

		$sql .= " WHERE tp_id='$pid' AND tph_id IN ($str_tph_id) \r\n";
		$result=$mysqli->query($sql);

	}

	if ($strDEL!=""){

		$sql = "DELETE FROM ".TELEPHONE_HOUSE_TABLE." WHERE tp_id='$pid' AND tph_id IN ($strDEL)";
		$result=$mysqli->query($sql);

	}

	//작업완료 후 업로드 된 엑셀 파일 삭제
	// @unlink($filename);

}
 catch (exception $e) {
    echo 'error 엑셀파일을 읽는도중 오류가 발생하였습니다.';
}
?>
