<?php
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

$c_territory_type = unserialize(TERRITORY_TYPE);

$sql = "SELECT tt_type, tt_num FROM ".TERRITORY_TABLE." WHERE tt_id = '{$tt_id}'";
$result = $mysqli->query($sql);
$territory = $result->fetch_assoc();

$territory_type = $territory['tt_type']; //구역타입 예) 일반,아파트
$territory_num = upload_filter($territory['tt_num']);

// 세대 정보
if($territory_type == '일반'){ // 일반 구역일때
	$activeWorksheet->setCellValue('A1', '신규/수정/삭제')
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
	$activeWorksheet->setCellValue('A1', '신규/수정/삭제')
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
	$activeWorksheet->setCellValue('A1', '신규/수정/삭제')
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
	$activeWorksheet->setCellValue('A1', '신규/수정/삭제')
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
	$activeWorksheet->setCellValue('A1', '신규/수정/삭제')
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
	$activeWorksheet
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
	$activeWorksheet->setCellValue('A1', '신규/수정/삭제')
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
	$h_order = $rs['h_order'];
	$h_visit = $rs['h_visit'];
	$h_condition = $rs['h_condition'];

	$activeWorksheet->setCellValue("A$i", "")
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
$activeWorksheet->setCellValue('M1', '*설정값 안내')

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

$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];

$activeWorksheet->getStyle('A1:J1')->applyFromArray($styleArray);
$activeWorksheet->getStyle('M3:N6')->applyFromArray($styleArray);
$activeWorksheet->getStyle('M8:N9')->applyFromArray($styleArray);
$activeWorksheet->getStyle('M11:N12')->applyFromArray($styleArray);
$activeWorksheet->getStyle('M14:N16')->applyFromArray($styleArray);
$activeWorksheet->getStyle('M18:N28')->applyFromArray($styleArray);
unset($styleArray);

// 배경색 적용
$fillColor = [
    'fillType' => Fill::FILL_SOLID,
    'startColor' => ['rgb' => 'bedfef']
];

//배경색 적용
$activeWorksheet->getStyle('A1:J1')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('M3')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('M8')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('M11')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('M14')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('M18')->applyFromArray(['fill' => $fillColor]);

// 정렬
$styleArray = [
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER,
    ],
];

// 스타일을 셀 범위에 적용
$activeWorksheet->getStyle('A1:J200')->applyFromArray($styleArray);

$activeWorksheet->getColumnDimension('A')->setWidth(20);
$activeWorksheet->getColumnDimension('B')->setWidth(15);
$activeWorksheet->getColumnDimension('C')->setWidth(20);
$activeWorksheet->getColumnDimension('D')->setWidth(20);
$activeWorksheet->getColumnDimension('E')->setWidth(20);
$activeWorksheet->getColumnDimension('F')->setWidth(15);
$activeWorksheet->getColumnDimension('G')->setWidth(15);
$activeWorksheet->getColumnDimension('H')->setWidth(15);
$activeWorksheet->getColumnDimension('I')->setWidth(15);
$activeWorksheet->getColumnDimension('J')->setWidth(15);
$activeWorksheet->getColumnDimension('M')->setWidth(30);
$activeWorksheet->getColumnDimension('N')->setWidth(30);

// Rename worksheet
$activeWorksheet->setTitle('구역');

//엑셀 파일 다운로드를 위한 설정
$excelFileName = iconv('UTF-8', 'EUC-KR', $territory_num.'.xlsx');
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
