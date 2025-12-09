<?php include_once('../config.php');?>

<?php
// PHP 버전 체크: 7.4 이상이면 PhpSpreadsheet 버전 사용 (Deprecated 경고 방지)
// PHP 7.4부터 curly brace 인덱싱이 deprecated되어 PHPExcel에서 경고 발생
$php_version_check = version_compare(PHP_VERSION, '7.4.0', '>=');
$php8_file = __DIR__ . '/member_excel_download_php8.php';

if ($php_version_check && file_exists($php8_file)) {
	require_once $php8_file;
	exit;
} elseif ($php_version_check && !file_exists($php8_file)) {
	// PHP 7.4 이상인데 php8 파일이 없으면 에러
	die('Error: PHP ' . PHP_VERSION . ' requires member_excel_download_php8.php file, but it was not found.');
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
// error_reporting(E_ALL);
// ini_set('display_errors', TRUE);
// ini_set('display_startup_errors', TRUE);
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

// 구역 정보
$objPHPExcel->setActiveSheetIndex(0)
							->setCellValue('A1', '신규/수정/삭제')
							->setCellValue('B1', '고유번호')
							->setCellValue('C1', '이름')
							->setCellValue('D1', '비밀번호')
							->setCellValue('E1', '전화번호')
							->setCellValue('F1', '성별')
							->setCellValue('G1', '주소')
							->setCellValue('H1', '직책')
							->setCellValue('I1', '파이오니아')
							->setCellValue('J1', '전시대 선정')
							->setCellValue('K1', '집단 ID')
							->setCellValue('L1', '전입날짜')
							->setCellValue('M1', '전출날짜');


$sql = "SELECT * FROM ".MEMBER_TABLE." ORDER BY (CASE WHEN mb_moveout_date = '0000-00-00' THEN 1 ELSE 2 END), mb_name ASC";
$result = $mysqli->query($sql);

$i=2;
while($mb=$result->fetch_assoc()){

	$mb_id = $mb['mb_id'];
	$mb_name = $mb['mb_name'];
	$mb_hp = decrypt($mb['mb_hp']);  //전화번호
	$mb_sex = $mb['mb_sex'];
	$mb_position = $mb['mb_position']; //직분
	$mb_pioneer = $mb['mb_pioneer'];  //파이오니아
	$mb_address = decrypt($mb['mb_address']);
	$mb_display = $mb['mb_display'];  //권한
	$g_id = $mb['g_id'];  //봉사집단
	$mb_movein_date = empty_date($mb['mb_movein_date'])?'':$mb['mb_movein_date'];  //전입날짜
	$mb_moveout_date = empty_date($mb['mb_moveout_date'])?'':$mb['mb_moveout_date'];  //전입날짜

	$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue("B$i", $mb_id)
				->setCellValue("C$i", $mb_name)
				->setCellValue("D$i", '')
				->setCellValue("E$i", $mb_hp)
				->setCellValue("F$i", $mb_sex)
				->setCellValue("G$i", $mb_address)
				->setCellValue("H$i", $mb_position)
				->setCellValue("I$i", $mb_pioneer)
				->setCellValue("J$i", $mb_display)
				->setCellValue("K$i", $g_id)
				->setCellValue("L$i", $mb_movein_date)
				->setCellValue("M$i", $mb_moveout_date);

	$i++;
};

// Miscellaneous glyphs, UTF-8
$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('P1', '*설정값 안내')

            ->setCellValue('P3', '신규/수정/삭제')
            ->setCellValue('Q3', '값')
            ->setCellValue('P4', '신규')
            ->setCellValue('Q4', 'N')
            ->setCellValue('P5', '수정')
            ->setCellValue('Q5', 'U')
            ->setCellValue('P6', '삭제')
            ->setCellValue('Q6', 'D')

            ->setCellValue('P8', '고유번호 (변경금지)')
            ->setCellValue('Q8', '각 항목의 고유값')
            ->setCellValue('Q9', '신규는 공백')

						->setCellValue('P11', '비밀번호')
            ->setCellValue('Q11', '변경만 가능')

            ->setCellValue('P13', '성별')
            ->setCellValue('Q13', '값')
						->setCellValue('P14', '형제')
						->setCellValue('Q14', 'M')
						->setCellValue('P15', '자매')
						->setCellValue('Q15', 'W')

						->setCellValue('P17', '직책')
						->setCellValue('Q17', '값')
						->setCellValue('P18', get_member_position_text(1))
						->setCellValueExplicit('Q18', '1', PHPExcel_Cell_DataType::TYPE_STRING)
						->setCellValue('P19', get_member_position_text(2))
						->setCellValueExplicit('Q19', '2', PHPExcel_Cell_DataType::TYPE_STRING)
						->setCellValue('P20', get_member_position_text(3))
						->setCellValueExplicit('Q20', '3', PHPExcel_Cell_DataType::TYPE_STRING)

						->setCellValue('P22', '파이오니아')
						->setCellValue('Q22', '값')
						->setCellValue('P23', get_member_pioneer_text(1))
						->setCellValueExplicit('Q23', '1', PHPExcel_Cell_DataType::TYPE_STRING)
						->setCellValue('P24', get_member_pioneer_text(2))
						->setCellValueExplicit('Q24', '2', PHPExcel_Cell_DataType::TYPE_STRING)
						->setCellValue('P25', get_member_pioneer_text(3))
						->setCellValueExplicit('Q25', '3', PHPExcel_Cell_DataType::TYPE_STRING)
						->setCellValue('P26', get_member_pioneer_text(4))
						->setCellValueExplicit('Q26', '4', PHPExcel_Cell_DataType::TYPE_STRING)

						->setCellValue('P28', '전시대 선정')
						->setCellValue('Q28', '값')
						->setCellValue('P29', '가능')
						->setCellValueExplicit('Q29', '0', PHPExcel_Cell_DataType::TYPE_STRING)
						->setCellValue('P30', '불가능')
						->setCellValueExplicit('Q30', '1', PHPExcel_Cell_DataType::TYPE_STRING)

						->setCellValue('P32', '봉사집단')
						->setCellValue('Q32', '값')
						->setCellValue('P33', '봉사집단')
						->setCellValueExplicit('Q33', '봉사집단 ID', PHPExcel_Cell_DataType::TYPE_STRING)

						->setCellValue('P35', '전입/전출날짜')
						->setCellValue('Q35', '값')
						->setCellValue('P36', '날짜 형식')
						->setCellValueExplicit('Q36', 'YYYY-MM-DD', PHPExcel_Cell_DataType::TYPE_STRING);


$styleArray = array(
  'borders' => array(
    'allborders' => array(
      'style' => PHPExcel_Style_Border::BORDER_THIN
    )
  )
);

$objPHPExcel->getActiveSheet()->getStyle('A1:M1')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('P3:Q6')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('P8:Q9')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('P11:Q11')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('P13:Q15')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('P17:Q20')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('P22:Q26')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('P28:Q28')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('P28:Q30')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('P32:Q33')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getStyle('P35:Q36')->applyFromArray($styleArray);
unset($styleArray);

//배경색 적용
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>'bedfef')
		),
	),
	'A1:M1'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>'bedfef')
		),
	),
	'P3'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>'bedfef')
		),
	),
	'P8'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>'bedfef')
		),
	),
	'P11'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>'bedfef')
		),
	),
	'P13'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>'bedfef')
		),
	),
	'P17'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>'bedfef')
		),
	),
	'P22'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>'bedfef')
		),
	),
	'P28'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>'bedfef')
		),
	),
	'P32'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>'bedfef')
		),
	),
	'P35'
);
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
	array(
		'fill' => array(
			'type'  => PHPExcel_Style_Fill::FILL_SOLID,
			'color' => array('rgb'=>'E8E8E8')
		),
	),
	'B2:B300'
);

// 정렬
$objPHPExcel->getActiveSheet()->duplicateStyleArray(
		array(
			'alignment' => array(
			'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
		)
	),
	'A1:M300'
);

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(7);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(40);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);


// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('전도인명단');


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);


// Redirect output to a client’s web browser (Excel2007)
$excelFileName = iconv('UTF-8', 'EUC-KR', '전도인명단'.'_'.date('ymd'));
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$excelFileName.'.xlsx"');
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
?>
