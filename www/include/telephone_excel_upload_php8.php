<?php
// Include the PhpSpreadsheet library
require_once __DIR__.'/../classes/Psr/SimpleCache/CacheInterface.php';
require_once __DIR__.'/../classes/PhpSpreadsheet-2.2.2/PhpSpreadsheet/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// 읽어들일 엑셀 파일의 경로와 파일명을 지정한다.
$filename = $_FILES['excel']['tmp_name'];

$sql="SELECT MAX(tph_id) FROM ".TELEPHONE_HOUSE_TABLE." WHERE tp_id=".$pid;
$result = $mysqli->query($sql);
$max_fetch = $result->fetch_row();
$max_list_idx = $max_fetch[0]+1;

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

	$str_tph_number = '';
	$str_tph_type = '';
	$str_tph_name = '';
	$str_tph_address = '';
	$str_tph_order = '';
	$str_tph_id = '';

    for ($i = 2 ; $i <= $maxRow ; $i++) {
		$nud			= $worksheet->getCell('A' . $i)->getValue(); // A열
		$tph_id			= $worksheet->getCell('B' . $i)->getValue(); // B열
		$tph_number		= $worksheet->getCell('C' . $i)->getValue(); // C열
		$tph_type		= $worksheet->getCell('D' . $i)->getValue(); // D열
		$tph_name		= $worksheet->getCell('E' . $i)->getValue(); // E열
        $tph_address	= $worksheet->getCell('F' . $i)->getValue(); // F열
		$tph_order		= $worksheet->getCell('G' . $i)->getValue(); // G열

        // 특수문자 제거
        $nud = upload_filter($nud);
        $tph_id	= upload_filter($tph_id);
		$tph_number	= upload_filter($tph_number);
		$tph_type	= upload_filter($tph_type);
		$tph_name	= upload_filter($tph_name);
        $tph_address	= upload_filter($tph_address);
        $tph_order = upload_filter($tph_order);

        if(empty($nud)){ continue; }

		if (strtoupper($nud ?? '')=="N"){

			if ($intNEW>0) $strNEW.="union \r\n";

			if (empty($tph_order)) $tph_order = $i-4;

			$strNEW .= "select '$pid','$tph_number','$tph_type','$tph_name','$tph_address','$tph_order','','','',0 \r\n";

			$intNEW++;
			$max_list_idx++;

		}elseif(strtoupper($nud ?? '')=="U"){

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

		}elseif(strtoupper($nud ?? '')=="D"){

			if ($intDEL>0) $strDEL.=",";

			$strDEL.=$tph_id;
			$intDEL++;

		}
    }

	if ($strNEW){
		$sql = "INSERT INTO ".TELEPHONE_HOUSE_TABLE."(tp_id, tph_number, tph_type, tph_name, tph_address, tph_order, tph_condition, tph_visit, tph_visit_old, mb_id)\r\n";
		$sql .= $strNEW;
		$result=$mysqli->query($sql);
	}

	if ($strUPD){
		$arr_tph_id         = explode(",",$str_tph_id);
		$arr_tph_number     = explode(",",$str_tph_number);
		$arr_tph_type       = explode(",",$str_tph_type);
		$arr_tph_name       = explode(",",$str_tph_name);
        $arr_tph_address    = explode(",",$str_tph_address);
        $arr_tph_order      = explode(",",$str_tph_order);

		$sql = " UPDATE ".TELEPHONE_HOUSE_TABLE." \r\n";
		$sql .= "    SET \r\n";

		$sql .= excel_update($arr_tph_id, " ", "tph_number", $arr_tph_number);
		$sql .= excel_update($arr_tph_id, ",", "tph_type", $arr_tph_type);
		$sql .= excel_update($arr_tph_id, ",", "tph_name", $arr_tph_name);
        $sql .= excel_update($arr_tph_id, ",", "tph_address", $arr_tph_address);
        $sql .= excel_update($arr_tph_id, ",", "tph_order", $arr_tph_order);

		$sql .= " WHERE tp_id=$pid AND tph_id IN ($str_tph_id) \r\n";
		$result=$mysqli->query($sql);
	}

	if ($strDEL!=""){
		$sql = "DELETE FROM ".TELEPHONE_HOUSE_TABLE." WHERE tp_id=$pid AND tph_id IN ($strDEL)";
		$result=$mysqli->query($sql);
	}

} catch (Exception $e) {
    echo 'error 엑셀파일을 읽는도중 오류가 발생하였습니다.';
}

​?>