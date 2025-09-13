<?php
ini_set('memory_limit','-1');
set_time_limit(0);

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
date_default_timezone_set('Asia/Seoul');

if (PHP_SAPI == 'cli')
	die('This example should only be run from a Web Browser');

require_once __DIR__.'/../classes/Psr/SimpleCache/CacheInterface.php';
require_once __DIR__.'/../classes/ZipStream-PHP-3.1.0/src/ZipStream.php';
require_once __DIR__.'/../classes/PhpSpreadsheet-2.2.2/PhpSpreadsheet/autoload.php';

spl_autoload_register(function ($class) {
    // 프로젝트의 기본 디렉토리 경로를 설정합니다.
    $prefix = 'ZipStream\\';
    $base_dir = __DIR__.'/../classes/ZipStream-PHP-3.1.0/src/';

    // 클래스 이름에서 네임스페이스 접두사를 제거합니다.
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // 클래스가 네임스페이스 접두사와 일치하지 않으면 넘어갑니다.
        return;
    }

    // 남은 클래스 이름 부분을 가져옵니다.
    $relative_class = substr($class, $len);

    // 파일 경로를 만듭니다.
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // 파일이 존재하면 로드합니다.
    if (file_exists($file)) {
        require $file;
    }
});

use ZipStream\ZipStream; // 필요시 추가
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// 새로운 Spreadsheet 객체 생성
$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();

$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_MEDIUM,
        ],
    ],
];

$styleArray2 = [
    'borders' => [
        'inside' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];

$number = 5;
$column_cnt = 'J'; // 컬럼명을 출력할 총 열
$record_start_row = array(); // 구역기록 출력 시작 행
$record_start_column = array(); // 구역기록 출력 시작 열
$end_date = array(); // 마지막으로 완료한 날짜

// 초기 세팅
$sql = "SELECT tp_id, tp_num FROM ".TELEPHONE_TABLE." ORDER BY tp_num+0 ASC, tp_num ASC";
$result = $mysqli->query($sql);
while($row=$result->fetch_assoc()){

	// 변수 세팅
	$tp_id = $row['tp_id'];
	$tp_num = $row['tp_num'];

	// 컬럼 숫자 세팅
	$top_number = $number;
	$number++;
	$bottom_number = $number;

	// 구역기록 출력 시작 행 세팅
	$record_start_column[$tp_id] = $top_number;

	// 구역기록 출력 시작 열 세팅
	$record_start_row[$tp_id] = 'C';

	$end_date[$tp_id] = '';

	// 구역번호 입력
	$activeWorksheet->setCellValue("A".$top_number, $tp_num);

	// 셀 합치기
	$activeWorksheet->mergeCells("A".$top_number.':A'.$bottom_number);
	$activeWorksheet->mergeCells("B".$top_number.':B'.$bottom_number);

    // 셀 높이 설정
    $activeWorksheet->getDefaultRowDimension()->setRowHeight(16);

	// 셀 넓이 설정
	$activeWorksheet->getColumnDimension('A')->setWidth(8);
	$activeWorksheet->getColumnDimension('B')->setWidth(9);

	$number++;
}

// TELEPHONE RECORD 테이블 - 기간 내 배정 날짜가 포함되는 기록만 조회
$sql = "SELECT tpr.tp_id, tpr_start_date, tpr_end_date, tpr_assigned, tpr_assigned_date, tpr_mb_name
				FROM ".TELEPHONE_RECORD_TABLE." tpr INNER JOIN ".TELEPHONE_TABLE." tp ON tpr.tp_id = tp.tp_id
				WHERE (tpr_assigned_date >= '".$tp_sdate."' AND tpr_assigned_date <= '".$tp_fdate."')
				ORDER BY tpr.tp_id ASC";
$result = $mysqli->query($sql);
while($row=$result->fetch_assoc()){

	// 변수 세팅
	$tp_id = $row['tp_id'];
	$tpr_start_date= empty_date($row['tpr_start_date'])?'':$row['tpr_start_date'];
	$tpr_end_date = empty_date($row['tpr_end_date'])?'':$row['tpr_end_date'];
	$tpr_assigned = $row['tpr_assigned'];
	$tpr_assigned_date = empty_date($row['tpr_assigned_date'])?'':$row['tpr_assigned_date'];
	$tpr_mb_name = $row['tpr_mb_name'];

	// RECORD에 있는 구역번호가 삭제된 구역의 구역번호일때
	if(empty($record_start_column[$tp_id])){
		continue;
	}

	// 시작 열 값 세팅
	$start_row = $record_start_row[$tp_id]; // 미리 생성된 배열에서 시작 숫자 가져옴
	$left_alpabet = $start_row;
	$start_row++;
	$right_alpabet = $start_row;

	// 시작 행 값 세팅
	$start_column = $record_start_column[$tp_id];

	// 배정 날짜가 기간 내에 있으면 출력 (이미 SQL에서 필터링됨)
	if($tpr_assigned_date){

		// 개인구역인지 아닌지
        if (!empty($tpr_mb_name)) { // 개인구역 배정받아서 봉사함
            $activeWorksheet->setCellValue($left_alpabet . $start_column, $tpr_mb_name);
        } else { // 일반배정
            $assigned_members = filter_assigned_member_array($tpr_assigned);
            $first_member = !empty($assigned_members) ? $assigned_members[0] : '';
            $activeWorksheet->setCellValue($left_alpabet . $start_column, $first_member);
        }

		// 시작날짜, 마친날짜 입력
		if($tpr_assigned_date){
			$activeWorksheet->setCellValue($left_alpabet.($start_column+1), date('y.n.j',strtotime($tpr_assigned_date))); // 시작날짜
		}else{
			if($tpr_mb_name) $activeWorksheet->setCellValue($left_alpabet.($start_column+1), date('y.n.j',strtotime($tpr_start_date)));
		}
		if($tpr_end_date){
			$activeWorksheet->setCellValue($right_alpabet.($start_column+1), date('y.n.j',strtotime($tpr_end_date))); // 마친날짜
			if(empty($end_date[$tp_id]) || $end_date[$tp_id] < $tpr_end_date) $end_date[$tp_id] = $tpr_end_date;
		}

		// 셀 합치기
		$activeWorksheet->mergeCells($left_alpabet.$start_column.':'.$right_alpabet.$start_column);

		// 선 굵기
		$activeWorksheet->getStyle($left_alpabet.$start_column.':'.$right_alpabet.($start_column+1))->applyFromArray($styleArray); // 테두리 진한선
		$activeWorksheet->getStyle($left_alpabet.$start_column.':'.$right_alpabet.($start_column+1))->applyFromArray($styleArray2); // 내부 연한선

		// 구역기록 출력 시작 행 업데이트
		$start_row++;
		$record_start_row[$tp_id] = $start_row;
	}

}

// TELEPHONE 테이블 - 현재 배정된 구역 중 기간 내 배정 날짜가 포함되는 것만
$sql = "SELECT tp_id, tp_start_date, tp_end_date, tp_assigned, tp_assigned_date, mb_id, tp_mb_date
				FROM ".TELEPHONE_TABLE."
				WHERE (tp_assigned_date >= '".$tp_sdate."' AND tp_assigned_date <= '".$tp_fdate."')";
$result = $mysqli->query($sql);
while($row=$result->fetch_assoc()){

	// 변수 세팅
	$tp_id = $row['tp_id'];
	$tp_start_date= empty_date($row['tp_start_date'])?'':$row['tp_start_date'];
	$tp_end_date = empty_date($row['tp_end_date'])?'':$row['tp_end_date'];
	$tp_assigned = $row['tp_assigned'];
	$tp_assigned_date = empty_date($row['tp_assigned_date'])?'':$row['tp_assigned_date'];
	$mb_id = $row['mb_id'];

	// 시작 열 값 세팅
	$start_row = $record_start_row[$tp_id]; // 미리 생성된 배열에서 시작 숫자 가져옴
	$left_alpabet = $start_row;
	$start_row++;
	$right_alpabet = $start_row;

	// 시작 행 값 세팅
	$start_column = $record_start_column[$tp_id];

	// 배정 날짜가 기간 내에 있으면 출력 (이미 SQL에서 필터링됨)
	if($tp_assigned_date){

        // 개인구역인지 아닌지
        if (!empty($mb_id)) { // 개인구역 배정받아서 봉사함
            $member_name = get_member_name($mb_id);
            $activeWorksheet->setCellValue($left_alpabet . $start_column, $member_name);
        } else { // 일반배정
            $assigned_members = filter_assigned_member_array($tp_assigned);
            $first_member = !empty($assigned_members) ? $assigned_members[0] : '';
            $activeWorksheet->setCellValue($left_alpabet . $start_column, $first_member);
        }

		// 시작날짜, 마친날짜 입력
		if($tp_assigned_date){
			$activeWorksheet->setCellValue($left_alpabet.($start_column+1), date('y.n.j',strtotime($tp_assigned_date))); // 시작날짜
		}else{
			if($row['mb_id']){
				if($row['tp_mb_date'] && !empty_date($row['tp_mb_date'])){
					$activeWorksheet->setCellValue($left_alpabet.($start_column+1), date('y.n.j',strtotime($row['tp_mb_date'])));
				}else{
					$activeWorksheet->setCellValue($left_alpabet.($start_column+1), date('y.n.j',strtotime($tp_start_date)));
				}
			}
		}
		if($tp_end_date){
			$activeWorksheet->setCellValue($right_alpabet.($start_column+1), date('y.n.j',strtotime($tp_end_date))); // 마친날짜
			if(empty($end_date[$tp_id]) || $end_date[$tp_id] < $tp_end_date) $end_date[$tp_id] = $tp_end_date;
		}

		// 셀 합치기
		$activeWorksheet->mergeCells($left_alpabet.$start_column.':'.$right_alpabet.$start_column);

		// 선 굵기
		$activeWorksheet->getStyle($left_alpabet.$start_column.':'.$right_alpabet.($start_column+1))->applyFromArray($styleArray); // 테두리 진한선
		$activeWorksheet->getStyle($left_alpabet.$start_column.':'.$right_alpabet.($start_column+1))->applyFromArray($styleArray2); // 내부 연한선

		// 구역기록 출력 시작 행 업데이트
		$start_row++;
		$record_start_row[$tp_id] = $start_row;

    if($column_cnt < $start_row) $column_cnt = $start_row;
	}

}

// 마지막으로 완료한 날짜
foreach ($end_date as $tp_id => $date) {

	// 시작 행 값 세팅
	$start_column = $record_start_column[$tp_id];

	// 마지막 완료 날짜 포맷팅
	$formatted_date = '';
	if($date && !empty_date($date)) {
		$formatted_date = date('y.m.d', strtotime($date));
	}
	$activeWorksheet->setCellValue("B".$start_column, $formatted_date);

	// 선 굵기
	$activeWorksheet->getStyle("A".$start_column.':'."B".($start_column+1))->applyFromArray($styleArray);
	$activeWorksheet->getStyle("A".$start_column.':'."B".($start_column+1))->applyFromArray($styleArray2); // 내부 연한선

}

//상단 컬럼명
$activeWorksheet->mergeCells('A1:'.$column_cnt.'1');
$activeWorksheet->mergeCells("A2:B2");
$activeWorksheet->mergeCells("A3:A4");
$activeWorksheet->mergeCells("B3:B4");

// A3 셀에 텍스트 줄바꿈 설정
$activeWorksheet->getStyle("A3")->getAlignment()->setWrapText(true);

// B3 셀에 텍스트 줄바꿈 설정
$activeWorksheet->getStyle("B3")->getAlignment()->setWrapText(true);

$activeWorksheet->setCellValue('A1', '구역 배정 기록')
->setCellValue('A2', '봉사 연도: ')
->setCellValue('A3', '구역 번호')
->setCellValue('B3', '마지막으로 완료한 날짜');

$activeWorksheet->getStyle("A3:B4")->applyFromArray($styleArray); // 테두리 진한선
$activeWorksheet->getStyle("A3:B4")->applyFromArray($styleArray2); // 내부 연한선

for ($i='C'; $i < $column_cnt; $i++) {
    $left_alpabet = $i;
    $i++;
    $right_alpabet = $i;
    $activeWorksheet->getColumnDimension($left_alpabet)->setWidth(8);
    $activeWorksheet->getColumnDimension($right_alpabet)->setWidth(8);
    $activeWorksheet->mergeCells($left_alpabet.'3:'.$right_alpabet.'3');

    $activeWorksheet
    ->setCellValue($left_alpabet.'3', '배정된 전도인')
    ->setCellValue($left_alpabet.'4', '배정 날짜')
    ->setCellValue($right_alpabet.'4', '완료 날짜');

    $activeWorksheet->getStyle($left_alpabet.'3:'.$right_alpabet.'4')->applyFromArray($styleArray); // 테두리 진한선
    $activeWorksheet->getStyle($left_alpabet.'3:'.$right_alpabet.'4')->applyFromArray($styleArray2); // 내부 연한선
}

$activeWorksheet->getStyle('A1:' . $column_cnt . $number)->applyFromArray([
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER,
    ],
]);

//전체 행 높이
for($i = 1; $i <= $record_start_column[$tp_id]; $i ++) {
    $activeWorksheet->getRowDimension($i)->setRowHeight(16);
}

//폰트 스타일
$activeWorksheet->getStyle("A1:C2")->getFont()->setBold(true);

$activeWorksheet->getStyle('A1:'.$column_cnt.'1')->getFont()->setSize(12);
$activeWorksheet->getStyle("A2:B2")->getFont()->setSize(10);
$activeWorksheet->getStyle("A3:".$right_alpabet."4")->getFont()->setSize(8);
$activeWorksheet->getStyle("A5:".$right_alpabet.$bottom_number)->getFont()->setSize(10);

unset($styleArray);
unset($styleArray2);

// Rename worksheet
$activeWorksheet->setTitle('전화구역임명기록('.$tp_sdate.'_'.$tp_fdate.')');

//엑셀 파일 다운로드를 위한 설정
$excelFileName = iconv('UTF-8', 'EUC-KR', '전화구역임명기록('.$tp_sdate.'_'.$tp_fdate.').xlsx');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"{$excelFileName}\"; filename*=UTF-8''{$excelFileName}");
header('Cache-Control: max-age=0');

// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

// 엑셀 파일 생성 및 출력
$writer = new Xlsx($spreadsheet);
ob_end_clean();
$writer->save('php://output');
exit;

