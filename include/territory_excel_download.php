<?php include_once('../config.php');?>

<?php
if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
	require_once 'territory_excel_download_php8.php';
	exit;
}
/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2015 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2015 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    ##VERSION##, ##DATE##
 */

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
date_default_timezone_set('Asia/Seoul');

if (PHP_SAPI == 'cli')
	die('This example should only be run from a Web Browser');

/** Include PHPExcel */
require_once dirname(__FILE__) . '/../classes/PHPExcel.php';


// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
// $objPHPExcel->getProperties()->setCreator("JW MINISTRY")
// 							 ->setLastModifiedBy("JW MINISTRY")
// 							 ->setTitle("MINISTRY MAP")
// 							 ->setSubject("MINISTRY MAP LIST")
// 							 ->setDescription("THE FIELD SERVICE")
// 							 ->setKeywords("FIELD SERVICE MAP LIST")
// 							 ->setCategory("MAP LIST");


// Add some data
//$objPHPExcel->setActiveSheetIndex(0)
//            ->setCellValue('A1', 'Hello')
//            ->setCellValue('B2', 'world!')
//            ->setCellValue('C1', 'Hello')
//            ->setCellValue('D2', 'world!');

// $sheet = $objPHPExcel->getActiveSheet();
// $sheet->mergeCells('A1:B1');
// $sheet->mergeCells('C1:D1');

$c_territory_type = unserialize(TERRITORY_TYPE);

$sql = "SELECT tt_type, tt_num FROM ".TERRITORY_TABLE." WHERE tt_id = '{$tt_id}'";
$result = $mysqli->query($sql);
$territory = $result->fetch_assoc();

$territory_type = $territory['tt_type']; //구역타입 예) 일반,아파트
$territory_num = upload_filter($territory['tt_num']);

// 세대 정보
if($territory_type == '일반'){ // 일반 구역일때
	$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', '신규/수정/삭제')
		->setCellValue('B1', '고유번호')
		->setCellValue('C1', '길이름')
		->setCellValue('D1', '건물번호')
		->setCellValue('E1', $c_territory_type['type_1'][3]?$c_territory_type['type_1'][3]:'상세주소')
		->setCellValue('F1', $c_territory_type['type_1'][4]?$c_territory_type['type_1'][4]:'층')
		->setCellValue('G1', $c_territory_type['type_1'][5]?$c_territory_type['type_1'][5]:'이름/호')
		->setCellValue('H1', '순서')
		->setCellValue('I1', '만남/부재')
		->setCellValue('J1', '상태');
}elseif($territory_type == '아파트'){ // 아파트 구역일떄
	$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', '신규/수정/삭제')
		->setCellValue('B1', '고유번호')
		->setCellValue('C1', $c_territory_type['type_2'][1]?$c_territory_type['type_2'][1]:'아파트명')
		->setCellValue('D1', $c_territory_type['type_2'][2]?$c_territory_type['type_2'][2]:'동')
		->setCellValue('E1', $c_territory_type['type_2'][3]?$c_territory_type['type_2'][3]:'호')
		->setCellValue('F1', '')
		->setCellValue('G1', '')
		->setCellValue('H1', '순서')
		->setCellValue('I1', '만남/부재')
		->setCellValue('J1', '상태');
}elseif($territory_type == '빌라'){ // 빌라 구역일때
	$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', '신규/수정/삭제')
		->setCellValue('B1', '고유번호')
	  ->setCellValue('C1', $c_territory_type['type_3'][1]?$c_territory_type['type_3'][1]:'빌라명')
	  ->setCellValue('D1', $c_territory_type['type_3'][2]?$c_territory_type['type_3'][2]:'동')
	  ->setCellValue('E1', $c_territory_type['type_3'][3]?$c_territory_type['type_3'][3]:'호')
		->setCellValue('F1', '')
		->setCellValue('G1', '')
	  ->setCellValue('H1', '순서')
	  ->setCellValue('I1', '만남/부재')
	  ->setCellValue('J1', '상태');
}elseif($territory_type == '격지'){ // 격지 구역일때
	$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', '신규/수정/삭제')
		->setCellValue('B1', '고유번호')
	  ->setCellValue('C1', '길이름')
		->setCellValue('D1', '건물번호')
		->setCellValue('E1', $c_territory_type['type_4'][3]?$c_territory_type['type_4'][3]:'상세주소')
	  ->setCellValue('F1', $c_territory_type['type_4'][4]?$c_territory_type['type_4'][4]:'층')
	  ->setCellValue('G1', $c_territory_type['type_4'][5]?$c_territory_type['type_4'][5]:'이름/호')
	  ->setCellValue('H1', '순서')
	  ->setCellValue('I1', '만남/부재')
	  ->setCellValue('J1', '상태');
}elseif($territory_type == '편지'){ // 편지 구역일때
	$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', '신규/수정/삭제')
		->setCellValue('B1', '고유번호')
	  ->setCellValue('C1', '길이름')
		->setCellValue('D1', '건물번호')
		->setCellValue('E1', $c_territory_type['type_5'][3]?$c_territory_type['type_5'][3]:'상세주소')
	  ->setCellValue('F1', $c_territory_type['type_5'][4]?$c_territory_type['type_5'][4]:'우편번호')
	  ->setCellValue('G1', $c_territory_type['type_5'][5]?$c_territory_type['type_5'][5]:'이름')
	  ->setCellValue('H1', '순서')
	  ->setCellValue('I1', '발송/미발송')
	  ->setCellValue('J1', '상태');
}elseif($territory_type == '추가1'){ // 추가1 구역일때
	$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', '신규/수정/삭제')
		->setCellValue('B1', '고유번호')
	  ->setCellValue('C1', '길이름')
		->setCellValue('D1', '건물번호')
		->setCellValue('E1', $c_territory_type['type_7'][3]?$c_territory_type['type_7'][3]:'')
	  ->setCellValue('F1', $c_territory_type['type_7'][4]?$c_territory_type['type_7'][4]:'')
	  ->setCellValue('G1', $c_territory_type['type_7'][5]?$c_territory_type['type_7'][5]:'')
	  ->setCellValue('H1', '순서')
	  ->setCellValue('I1', '만남/부재')
	  ->setCellValue('J1', '상태');
}elseif($territory_type == '추가2'){ // 추가2 구역일때
	$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', '신규/수정/삭제')
		->setCellValue('B1', '고유번호')
	  ->setCellValue('C1', $c_territory_type['type_8'][1]?$c_territory_type['type_8'][1]:'')
	  ->setCellValue('D1', $c_territory_type['type_8'][2]?$c_territory_type['type_8'][2]:'')
	  ->setCellValue('E1', $c_territory_type['type_8'][3]?$c_territory_type['type_8'][3]:'')
		->setCellValue('F1', '')
		->setCellValue('G1', '')
	  ->setCellValue('H1', '순서')
	  ->setCellValue('I1', '만남/부재')
	  ->setCellValue('J1', '상태');
}

$sql = "SELECT * FROM ".HOUSE_TABLE." WHERE tt_id = {$tt_id} order by h_order";
$result = $mysqli->query($sql);

$i=2;
while($rs=$result->fetch_assoc()){

	$h_id = $rs['h_id'];
	$h_address1 = upload_filter($rs['h_address1']);
	$h_address2 = upload_filter($rs['h_address2']);
	$h_address3 = upload_filter($rs['h_address3']);
    $h_address4 = upload_filter($rs['h_address4']);
    $h_address5 = upload_filter($rs['h_address5']);
    // 특정 구역 유형(type_2: 아파트, type_3: 빌라, type_8: 추가2)은 F/G 컬럼 미사용 → 비움
    if(in_array($territory_type, array('아파트','빌라','추가2'))){
        $h_address4 = '';
        $h_address5 = '';
    }
	$h_order = $rs['h_order'];
	$h_visit = $rs['h_visit'];
	$h_condition = $rs['h_condition'];

	$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue("A$i", "")
				->setCellValue("B$i", $h_id)
				->setCellValue("C$i", $h_address1)
				->setCellValue("D$i", $h_address2)
				->setCellValue("E$i", $h_address3)
				->setCellValue("F$i", $h_address4)
				->setCellValue("G$i", $h_address5)
				->setCellValue("H$i", $h_order)
				->setCellValue("I$i", $h_visit)
				->setCellValue("J$i", $h_condition);

	$i++;
}

// Miscellaneous glyphs, UTF-8
$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('M1', '*설정값 안내')

            ->setCellValue('M3', '신규/수정/삭제')
            ->setCellValue('N3', '값')
            ->setCellValue('M4', '신규')
            ->setCellValue('N4', 'N')
            ->setCellValue('M5', '수정')
            ->setCellValue('N5', 'U')
            ->setCellValue('M6', '삭제')
            ->setCellValue('N6', 'D')

            ->setCellValue('M8', '고유번호')
            ->setCellValue('N8', '프로그램 자동생성 (수정X)')
						->setCellValue('N9', '신규는 공백')

            ->setCellValue('M11', '순서')
            ->setCellValue('N11', '오름차순으로 정렬됨(1,2,3,...)')
						->setCellValue('N12', '비어있으면 맨 밑으로')

            ->setCellValue('M14', '만남/부재')
            ->setCellValue('N14', '값')
            ->setCellValue('M15', '만남')
            ->setCellValue('N15', 'Y')
            ->setCellValue('M16', '부재')
            ->setCellValue('N16', 'N')

            ->setCellValue('M18', '상태')
            ->setCellValue('N18', '값')
            ->setCellValue('M19', get_house_condition_text(1))
            ->setCellValue('N19', '1')
						->setCellValue('M20', get_house_condition_text(2))
						->setCellValue('N20', '2')
            ->setCellValue('M21', get_house_condition_text(3))
            ->setCellValue('N21', '3')
						->setCellValue('M22', get_house_condition_text(4))
						->setCellValue('N22', '4')
						->setCellValue('M23', get_house_condition_text(5))
						->setCellValue('N23', '5')
						->setCellValue('M24', get_house_condition_text(6))
						->setCellValue('N24', '6')
						->setCellValue('M25', get_house_condition_text(7))
						->setCellValue('N25', '7')
						->setCellValue('M26', get_house_condition_text(8))
						->setCellValue('N26', '8')
						->setCellValue('M27', get_house_condition_text(9))
						->setCellValue('N27', '9')
						->setCellValue('M28', get_house_condition_text(10))
						->setCellValue('N28', '10');

$styleArray = array(
  'borders' => array(
    'allborders' => array(
      'style' => PHPExcel_Style_Border::BORDER_THIN
    )
  )
);

$objPHPExcel->getActiveSheet()->getStyle('A1:J1')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('M3:N6')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('M8:N9')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('M11:N12')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('M14:N16')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('M18:N28')->applyFromArray($styleArray);
unset($styleArray);

$fill_color = 'bedfef';

//배경색 적용
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>$fill_color)
		),
	),
	'A1:J1'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>$fill_color)
		),
	),
	'M3'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>$fill_color)
		),
	),
	'M8'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>$fill_color)
		),
	),
	'M11'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>$fill_color)
		),
	),
	'M14'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>$fill_color)
		),
	),
	'M18'
);

// 정렬
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
		array(
			'alignment' => array(
			'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
		)
	),
	'A1:J200'
);

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);

$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(30);


// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('구역');


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);


// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$territory_num.'.xlsx"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
ob_end_clean();
$objWriter->save('php://output');
exit;
