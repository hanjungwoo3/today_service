<?php
// Include the PhpSpreadsheet library
require_once __DIR__.'/../classes/Psr/SimpleCache/CacheInterface.php';
require_once __DIR__.'/../classes/PhpSpreadsheet-2.2.2/PhpSpreadsheet/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// 읽어들일 엑셀 파일의 경로와 파일명을 지정한다.
$filename = $_FILES['excel']['tmp_name'];

$sql="SELECT MAX(h_id) FROM ".HOUSE_TABLE." WHERE tt_id=".$pid;
$result = $mysqli->query($sql);
$max_fetch = $result->fetch_row();
$max_list_idx=$max_fetch[0]+1;

try {
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

	$intNEW=0;
    $intUPD=0;
    $intDEL=0;
	$strNEW="";
    $strUPD="";
    $strDEL="";
	$strComma="";

	$str_h_address1 = '';
	$str_h_address2 = '';
	$str_h_address3 = '';
	$str_h_address4 = '';
	$str_h_address5 = '';
	$str_h_condition = '';
	$str_h_visit = '';
	$str_h_order = '';
	$str_h_id = '';

    for ($i = 2 ; $i <= $maxRow ; $i++) {
		$nud			= $worksheet->getCell('A' . $i)->getValue(); // A열
		$h_id			= $worksheet->getCell('B' . $i)->getValue(); // B열
		// 주소/건물번호 컬럼은 포맷된 표시 문자열로 읽어 하이픈 등이 보존되도록 처리
		$h_address1		= $worksheet->getCell('C' . $i)->getFormattedValue(); // C열
		$h_address2		= $worksheet->getCell('D' . $i)->getFormattedValue(); // D열
		$h_address3		= $worksheet->getCell('E' . $i)->getFormattedValue(); // E열
        $h_address4		= $worksheet->getCell('F' . $i)->getFormattedValue(); // F열
        $h_address5		= $worksheet->getCell('G' . $i)->getFormattedValue(); // G열
		$h_order		= $worksheet->getCell('H' . $i)->getValue(); // H열
		$h_visit		= $worksheet->getCell('I' . $i)->getValue(); // I열
		$h_condition	= $worksheet->getCell('J' . $i)->getValue(); // J열

        // 특수문자 제거
        $nud            = upload_filter($nud);
        $h_id			= upload_filter($h_id);
		$h_address1		= upload_filter($h_address1);
		$h_address2		= upload_filter($h_address2);
		$h_address3		= upload_filter($h_address3);
        $h_address4		= upload_filter($h_address4);
        $h_address5		= upload_filter($h_address5);
		$h_order		= upload_filter($h_order);
		$h_visit		= upload_filter($h_visit);
		$h_condition	= upload_filter($h_condition);

        if(empty($nud)){ continue; }

		if (strtoupper($nud ?? '')=="N"){

			if ($intNEW>0) $strNEW.="union \r\n";

			if (empty($h_order)) $h_order = $i-1;

			$strNEW .= "select '$pid','$h_address1','$h_address2','$h_address3','$h_address4','$h_address5','$h_condition','$h_visit','','$h_order',0 \r\n";

			$intNEW++;
			$max_list_idx++;

		}elseif(strtoupper($nud ?? '')=="U"){

			if ($intUPD>0){
                $strComma=",";
                $strUPD.=",";
			}

			$strUPD.=$h_id;

			$str_h_address1.=$strComma.$h_address1;
			$str_h_address2.=$strComma.$h_address2;
			$str_h_address3.=$strComma.$h_address3;
            $str_h_address4.=$strComma.$h_address4;
            $str_h_address5.=$strComma.$h_address5;
			$str_h_condition.=$strComma.$h_condition;
			$str_h_visit.=$strComma.$h_visit;
			$str_h_order.=$strComma.$h_order;
			$str_h_id.=$strComma.$h_id;

			$intUPD++;

		}elseif(strtoupper($nud ?? '')=="D"){

			if ($intDEL>0) $strDEL.=",";

			$strDEL.=$h_id;
			$intDEL++;

		}
    }

	if ($strNEW){
		$sql = "INSERT INTO ".HOUSE_TABLE."(tt_id, h_address1, h_address2, h_address3, h_address4, h_address5, h_condition, h_visit, h_visit_old, h_order, mb_id)\r\n";
		$sql .= $strNEW;
		$result=$mysqli->query($sql);
	}

	if ($strUPD){
		$arr_h_id           =   explode(",",$str_h_id);
		$arr_h_address1     =   explode(",",$str_h_address1);
		$arr_h_address2     =   explode(",",$str_h_address2);
		$arr_h_address3     =	explode(",",$str_h_address3);
        $arr_h_address4     =	explode(",",$str_h_address4);
        $arr_h_address5     =	explode(",",$str_h_address5);
		$arr_h_condition    =	explode(",",$str_h_condition);
		$arr_h_visit        =	explode(",",$str_h_visit);
		$arr_h_order        =	explode(",",$str_h_order);

		$sql = " UPDATE ".HOUSE_TABLE." \r\n";
		$sql .= "    SET \r\n";

		$sql .= excel_update($arr_h_id, " ", "h_address1", $arr_h_address1);
		$sql .= excel_update($arr_h_id, ",", "h_address2", $arr_h_address2);
		$sql .= excel_update($arr_h_id, ",", "h_address3", $arr_h_address3);
        $sql .= excel_update($arr_h_id, ",", "h_address4", $arr_h_address4);
        $sql .= excel_update($arr_h_id, ",", "h_address5", $arr_h_address5);
		$sql .= excel_update($arr_h_id, ",", "h_condition", $arr_h_condition);
		$sql .= excel_update($arr_h_id, ",", "h_visit",	$arr_h_visit);
		$sql .= excel_update($arr_h_id, ",", "h_order",	$arr_h_order);

		$sql .= " WHERE tt_id=$pid AND h_id IN ($str_h_id) \r\n";
		$result=$mysqli->query($sql);
	}

	if ($strDEL){
		$sql = "DELETE FROM ".HOUSE_TABLE." WHERE tt_id=$pid AND h_id IN ($strDEL)";
		$result=$mysqli->query($sql);
	}

} catch(Exception $e) {
	print_r($e);
    echo 'error 엑셀파일을 읽는도중 오류가 발생하였습니다.';
}
?>