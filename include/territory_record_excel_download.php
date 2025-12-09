<?php include_once('../config.php');?>

<?php
// PHP 버전 체크: 7.4 이상이면 PhpSpreadsheet 버전 사용 (Deprecated 경고 방지)
// PHP 7.4부터 curly brace 인덱싱이 deprecated되어 PHPExcel에서 경고 발생
$php_version_check = version_compare(PHP_VERSION, '7.4.0', '>=');
$php8_file = __DIR__ . '/territory_record_excel_download_php8.php';

if ($php_version_check && file_exists($php8_file)) {
	require_once $php8_file;
	exit;
} elseif ($php_version_check && !file_exists($php8_file)) {
	// PHP 7.4 이상인데 php8 파일이 없으면 에러
	die('Error: PHP ' . PHP_VERSION . ' requires territory_record_excel_download_php8.php file, but it was not found.');
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

 ini_set('memory_limit','-1');
 set_time_limit(0);

date_default_timezone_set('Asia/Seoul');

if (PHP_SAPI == 'cli')
	die('This example should only be run from a Web Browser');

/** Include PHPExcel */
require_once dirname(__FILE__) . '/../classes/PHPExcel.php';

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();
$sheet = $objPHPExcel->getActiveSheet();

$styleArray = array(
  'borders' => array(
    'allborders' => array(
      'style' => PHPExcel_Style_Border::BORDER_MEDIUM
    )
  )
);

$styleArray2 = array(
  'borders' => array(
    'inside' => array(
      'style' => PHPExcel_Style_Border::BORDER_THIN
    )
  )
);

$number = 5;
$column_cnt = 'J'; // 컬럼명을 출력할 총 열
$record_start_row = array(); // 구역기록 출력 시작 행
$record_start_column = array(); // 구역기록 출력 시작 열
$end_date = array(); // 마지막으로 완료한 날짜

// 초기 세팅
$where = (isset($tt_type) && $tt_type == '편지')?"tt_type = '편지'":"tt_type != '편지'";
$sql = "SELECT tt_id, tt_num FROM ".TERRITORY_TABLE." WHERE ".$where." ORDER BY tt_num+0 ASC, tt_num ASC";
$result = $mysqli->query($sql);
while($row=$result->fetch_assoc()){

	// 변수 세팅
	$tt_id = $row['tt_id'];
	$tt_num = $row['tt_num'];

	// 컬럼 숫자 세팅
	$top_number = $number;
	$number++;
	$bottom_number = $number;

	// 구역기록 출력 시작 행 세팅
	$record_start_column[$tt_id] = $top_number;

	// 구역기록 출력 시작 열 세팅
	$record_start_row[$tt_id] = 'C';

	$end_date[$tt_id] = '';

	// 구역번호 입력
	$sheet->setCellValue("A".$top_number, $tt_num);

	// 셀 합치기
	$sheet->mergeCells("A".$top_number.':A'.$bottom_number);
	$sheet->mergeCells("B".$top_number.':B'.$bottom_number);

  // 셀 높이 설정
  $sheet->getDefaultRowDimension()->setRowHeight(16);

	// 셀 넓이 설정
	$sheet->getColumnDimension('A')->setWidth(8);
	$sheet->getColumnDimension('B')->setWidth(9);

	$number++;
}

// TERRITORY RECORD 테이블 - 기간 내 배정 날짜가 포함되는 기록만 조회
$sql = "SELECT ttr.tt_id, ttr_start_date, ttr_end_date, ttr_assigned, ttr_assigned_date, ttr_mb_name, ttr.create_datetime
				FROM ".TERRITORY_RECORD_TABLE." ttr INNER JOIN ".TERRITORY_TABLE." tt ON ttr.tt_id = tt.tt_id
				WHERE (ttr.ttr_assigned_date >= '".$tt_sdate."' AND ttr.ttr_assigned_date <= '".$tt_fdate."')
				    AND ".$where."
				ORDER BY ttr.tt_id ASC";
$result = $mysqli->query($sql);
while($row=$result->fetch_assoc()){

	// 변수 세팅
	$tt_id = $row['tt_id'];
	$ttr_start_date= empty_date($row['ttr_start_date'])?'':$row['ttr_start_date'];
	$ttr_end_date = empty_date($row['ttr_end_date'])?'':$row['ttr_end_date'];
	$ttr_assigned = $row['ttr_assigned'];
	$ttr_assigned_date = empty_date($row['ttr_assigned_date'])?'':$row['ttr_assigned_date'];
	$ttr_mb_name = $row['ttr_mb_name'];

	// RECORD에 있는 구역번호가 삭제된 구역의 구역번호일때
	if(empty($record_start_column[$tt_id])){
		continue;
	}

	// 시작 열 값 세팅
	$start_row = $record_start_row[$tt_id]; // 미리 생성된 배열에서 시작 숫자 가져옴
	$left_alpabet = $start_row;
	$start_row++;
	$right_alpabet = $start_row;

	// 시작 행 값 세팅
	$start_column = $record_start_column[$tt_id];

	// 배정 날짜가 기간 내에 있으면 출력 (이미 SQL에서 필터링됨)
	if($ttr_assigned_date){

		// 개인구역인지 아닌지
		if($ttr_mb_name){ // 개인구역 배정받아서 봉사함
			$sheet->setCellValue($left_alpabet.$start_column, $ttr_mb_name);
		}else{ // 일반배정
			$sheet->setCellValue($left_alpabet.$start_column, filter_assigned_member_array($ttr_assigned)[0]);
		}

		// 시작날짜, 마친날짜 입력
		if($ttr_assigned_date){
			$sheet->setCellValue($left_alpabet.($start_column+1), date('y.n.j',strtotime($ttr_assigned_date))); // 시작날짜
		}else{
			if($ttr_mb_name) $sheet->setCellValue($left_alpabet.($start_column+1), date('y.n.j',strtotime($ttr_start_date)));
		}
		if($ttr_end_date){
			$sheet->setCellValue($right_alpabet.($start_column+1), date('y.n.j',strtotime($ttr_end_date))); // 마친날짜
			if(empty($end_date[$tt_id]) || $end_date[$tt_id] < $ttr_end_date) $end_date[$tt_id] = $ttr_end_date;
		}

		// 셀 합치기
		$sheet->mergeCells($left_alpabet.$start_column.':'.$right_alpabet.$start_column);

		// 선 굵기
		$sheet->getStyle($left_alpabet.$start_column.':'.$right_alpabet.($start_column+1))->applyFromArray($styleArray); // 테두리 진한선
		$sheet->getStyle($left_alpabet.$start_column.':'.$right_alpabet.($start_column+1))->applyFromArray($styleArray2); // 내부 연한선

		// 구역기록 출력 시작 행 업데이트
		$start_row++;
		$record_start_row[$tt_id] = $start_row;
	}

}

// TERRITORY 테이블 - 현재 배정된 구역 중 기간 내 배정 날짜가 포함되는 것만
$sql = "SELECT tt_id, tt_start_date, tt_end_date, tt_assigned, tt_assigned_date, mb_id, tt_mb_date
				FROM ".TERRITORY_TABLE."
				WHERE (tt_assigned_date >= '".$tt_sdate."' AND tt_assigned_date <= '".$tt_fdate."')
				    AND ".$where;
$result = $mysqli->query($sql);
while($row=$result->fetch_assoc()){

	// 변수 세팅
	$tt_id = $row['tt_id'];
	$tt_start_date= empty_date($row['tt_start_date'])?'':$row['tt_start_date'];
	$tt_end_date = empty_date($row['tt_end_date'])?'':$row['tt_end_date'];
	$tt_assigned = $row['tt_assigned'];
	$tt_assigned_date = empty_date($row['tt_assigned_date'])?'':$row['tt_assigned_date'];
	$mb_id = $row['mb_id'];

	// 시작 열 값 세팅
	$start_row = $record_start_row[$tt_id]; // 미리 생성된 배열에서 시작 숫자 가져옴
	$left_alpabet = $start_row;
	$start_row++;
	$right_alpabet = $start_row;

	// 시작 행 값 세팅
	$start_column = $record_start_column[$tt_id];

	// 배정 날짜가 기간 내에 있으면 출력 (이미 SQL에서 필터링됨)
	if($tt_assigned_date){

		// 개인구역인지 아닌지
		if($mb_id){ // 개인구역 배정받아서 봉사함
			$member_name = get_member_name($mb_id);
			$sheet->setCellValue($left_alpabet.$start_column, $member_name);
		}else{ // 일반배정
			$sheet->setCellValue($left_alpabet.$start_column, filter_assigned_member_array($tt_assigned)[0]);
		}

		// 시작날짜, 마친날짜 입력
		if($tt_assigned_date){
			$sheet->setCellValue($left_alpabet.($start_column+1), date('y.n.j',strtotime($tt_assigned_date))); // 시작날짜
		}else{
			if($row['mb_id']){
				if($row['tt_mb_date'] && !empty_date($row['tt_mb_date'])){
					$sheet->setCellValue($left_alpabet.($start_column+1), date('y.n.j',strtotime($row['tt_mb_date'])));
				}else{
					$sheet->setCellValue($left_alpabet.($start_column+1), date('y.n.j',strtotime($tt_start_date)));
				}
			}
		}
		if($tt_end_date){
			$sheet->setCellValue($right_alpabet.($start_column+1), date('y.n.j',strtotime($tt_end_date))); // 마친날짜
			if(empty($end_date[$tt_id]) || $end_date[$tt_id] < $tt_end_date) $end_date[$tt_id] = $tt_end_date;
		}

		// 셀 합치기
		$sheet->mergeCells($left_alpabet.$start_column.':'.$right_alpabet.$start_column);

		// 선 굵기
		$sheet->getStyle($left_alpabet.$start_column.':'.$right_alpabet.($start_column+1))->applyFromArray($styleArray); // 테두리 진한선
		$sheet->getStyle($left_alpabet.$start_column.':'.$right_alpabet.($start_column+1))->applyFromArray($styleArray2); // 내부 연한선

		// 구역기록 출력 시작 행 업데이트
		$start_row++;
		$record_start_row[$tt_id] = $start_row;

    if($column_cnt < $start_row) $column_cnt = $start_row;
	}

}

// 마지막으로 완료한 날짜 계산 및 출력
foreach ($end_date as $tt_id => $date) {

	// 시작 행 값 세팅
	$start_column = $record_start_column[$tt_id];

  // 마지막 완료 날짜 포맷팅
  $formatted_date = '';
  if($date && !empty_date($date)) {
    $formatted_date = date('y.m.d', strtotime($date));
  }
  $sheet->setCellValue("B".$start_column, $formatted_date);

	// 선 굵기
	$sheet->getStyle("A".$start_column.':'."B".($start_column+1))->applyFromArray($styleArray);
	$sheet->getStyle("A".$start_column.':'."B".($start_column+1))->applyFromArray($styleArray2); // 내부 연한선

}

//상단 컬럼명
$sheet->mergeCells('A1:'.$column_cnt.'1');
$sheet->mergeCells("A2:B2");
$sheet->mergeCells("A3:A4");
$sheet->mergeCells("B3:B4");

$objPHPExcel->getActiveSheet()->getStyle("A3")->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle("B3")->getAlignment()->setWrapText(true);

$objPHPExcel->setActiveSheetIndex(0)
  ->setCellValue('A1', '구역 배정 기록')
  ->setCellValue('A2', '봉사 연도')
  ->setCellValue('A3', '구역 번호')
  ->setCellValue('B3', '마지막으로 완료한 날짜');

$sheet->getStyle("A3:B4")->applyFromArray($styleArray); // 테두리 진한선
$sheet->getStyle("A3:B4")->applyFromArray($styleArray2); // 내부 연한선

for ($i='C'; $i < $column_cnt; $i++) {
  $left_alpabet = $i;
  $i++;
  $right_alpabet = $i;
  $sheet->getColumnDimension($left_alpabet)->setWidth(8);
  $sheet->getColumnDimension($right_alpabet)->setWidth(8);
  $sheet->mergeCells($left_alpabet.'3:'.$right_alpabet.'3');

  $objPHPExcel->setActiveSheetIndex(0)
    ->setCellValue($left_alpabet.'3', '배정된 전도인')
    ->setCellValue($left_alpabet.'4', '배정 날짜')
    ->setCellValue($right_alpabet.'4', '완료 날짜');

  $sheet->getStyle($left_alpabet.'3:'.$right_alpabet.'4')->applyFromArray($styleArray); // 테두리 진한선
  $sheet->getStyle($left_alpabet.'3:'.$right_alpabet.'4')->applyFromArray($styleArray2); // 내부 연한선
}

$objPHPExcel->getActiveSheet()->duplicateStyleArray(
		array(
			'alignment' => array(
			'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
		)
	),
	'A1:'.$column_cnt.$number
);

//전체 행 높이
for($i = 1; $i <= $record_start_column[$tt_id]; $i ++) {
  $sheet->getRowDimension($i)->setRowHeight(16);
}

//폰트 스타일
$sheet->getStyle("A1:C2")->getFont()->setBold(true);

$sheet->getStyle('A1:'.$column_cnt.'1')->getFont()->setSize(12);
$sheet->getStyle("A2:B2")->getFont()->setSize(10);
$sheet->getStyle("A3:".$right_alpabet."4")->getFont()->setSize(8);
$sheet->getStyle("A5:".$right_alpabet.$bottom_number)->getFont()->setSize(10);

unset($styleArray);
unset($styleArray2);

// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('구역임명기록('.$tt_sdate.'_'.$tt_fdate.')');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="구역임명기록('.$tt_sdate.'_'.$tt_fdate.').xlsx"');
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
