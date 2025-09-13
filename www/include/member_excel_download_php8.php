<?php

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

// 구역 정보 설정
$activeWorksheet->setCellValue('A1', '신규/수정/삭제')
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

$i = 2;
while ($mb = $result->fetch_assoc()) {
    $mb_id = $mb['mb_id'];
    $mb_name = $mb['mb_name'];
    $mb_hp = decrypt($mb['mb_hp']);  //전화번호
    $mb_sex = $mb['mb_sex'];
    $mb_position = $mb['mb_position']; //직분
    $mb_pioneer = $mb['mb_pioneer'];  //파이오니아
    $mb_address = decrypt($mb['mb_address']);
    $mb_display = $mb['mb_display'];  //권한
    $g_id = $mb['g_id'];  //봉사집단
    $mb_movein_date = empty_date($mb['mb_movein_date']) ? '' : $mb['mb_movein_date'];  //전입날짜
    $mb_moveout_date = empty_date($mb['mb_moveout_date']) ? '' : $mb['mb_moveout_date'];  //전입날짜

    $activeWorksheet->setCellValue("B$i", $mb_id)
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
}

// 셀 값 설정
$activeWorksheet->setCellValue('P1', '*설정값 안내')
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
      ->setCellValueExplicit('Q18', '1', DataType::TYPE_STRING)
      ->setCellValue('P19', get_member_position_text(2))
      ->setCellValueExplicit('Q19', '2', DataType::TYPE_STRING)
      ->setCellValue('P20', get_member_position_text(3))
      ->setCellValueExplicit('Q20', '3', DataType::TYPE_STRING)
      ->setCellValue('P22', '파이오니아')
      ->setCellValue('Q22', '값')
      ->setCellValue('P23', get_member_pioneer_text(1))
      ->setCellValueExplicit('Q23', '1', DataType::TYPE_STRING)
      ->setCellValue('P24', get_member_pioneer_text(2))
      ->setCellValueExplicit('Q24', '2', DataType::TYPE_STRING)
      ->setCellValue('P25', get_member_pioneer_text(3))
      ->setCellValueExplicit('Q25', '3', DataType::TYPE_STRING)
      ->setCellValue('P26', get_member_pioneer_text(4))
      ->setCellValueExplicit('Q26', '4', DataType::TYPE_STRING)
      ->setCellValue('P28', '전시대 선정')
      ->setCellValue('Q28', '값')
      ->setCellValue('P29', '가능')
      ->setCellValueExplicit('Q29', '0', DataType::TYPE_STRING)
      ->setCellValue('P30', '불가능')
      ->setCellValueExplicit('Q30', '1', DataType::TYPE_STRING)
      ->setCellValue('P32', '봉사집단')
      ->setCellValue('Q32', '값')
      ->setCellValue('P33', '봉사집단')
      ->setCellValueExplicit('Q33', '봉사집단 ID', DataType::TYPE_STRING)
      ->setCellValue('P35', '전입/전출날짜')
      ->setCellValue('Q35', '값')
      ->setCellValue('P36', '날짜 형식')
      ->setCellValueExplicit('Q36', 'YYYY-MM-DD', DataType::TYPE_STRING);

// 스타일 설정 (테두리)
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];
$activeWorksheet->getStyle('A1:M1')->applyFromArray($styleArray);
$activeWorksheet->getStyle('P3:Q6')->applyFromArray($styleArray);
$activeWorksheet->getStyle('P8:Q9')->applyFromArray($styleArray);
$activeWorksheet->getStyle('P11:Q11')->applyFromArray($styleArray);
$activeWorksheet->getStyle('P13:Q15')->applyFromArray($styleArray);
$activeWorksheet->getStyle('P17:Q20')->applyFromArray($styleArray);
$activeWorksheet->getStyle('P22:Q26')->applyFromArray($styleArray);
$activeWorksheet->getStyle('P28:Q28')->applyFromArray($styleArray);
$activeWorksheet->getStyle('P28:Q30')->applyFromArray($styleArray);
$activeWorksheet->getStyle('P32:Q33')->applyFromArray($styleArray);
$activeWorksheet->getStyle('P35:Q36')->applyFromArray($styleArray);
unset($styleArray);

// 배경색 적용
$fillColor = [
    'fillType' => Fill::FILL_SOLID,
    'startColor' => ['rgb' => 'bedfef']
];
$activeWorksheet->getStyle('A1:M1')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('P3')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('P8')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('P11')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('P13')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('P17')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('P22')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('P28')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('P32')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('P35')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('P32')->applyFromArray(['fill' => $fillColor]);
$activeWorksheet->getStyle('P32')->applyFromArray(['fill' => $fillColor]);

// 스타일 배열 정의
$styleArray = [
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER,
    ],
];
// 스타일을 셀 범위에 적용
$activeWorksheet->getStyle('A1:M300')->applyFromArray($styleArray);
unset($styleArray);

// D열에 회색 배경색 적용
$activeWorksheet->getStyle('B2:B300')->applyFromArray([
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E8E8E8'],  // 회색 색상 코드
    ],
]);

// 셀 크기 조정
$activeWorksheet->getColumnDimension('A')->setWidth(15);
$activeWorksheet->getColumnDimension('B')->setWidth(10);
$activeWorksheet->getColumnDimension('C')->setWidth(15);
$activeWorksheet->getColumnDimension('D')->setWidth(15);
$activeWorksheet->getColumnDimension('E')->setWidth(20);
$activeWorksheet->getColumnDimension('F')->setWidth(7);
$activeWorksheet->getColumnDimension('G')->setWidth(40);
$activeWorksheet->getColumnDimension('H')->setWidth(10);
$activeWorksheet->getColumnDimension('I')->setWidth(10);
$activeWorksheet->getColumnDimension('J')->setWidth(10);
$activeWorksheet->getColumnDimension('K')->setWidth(15);
$activeWorksheet->getColumnDimension('L')->setWidth(20);
$activeWorksheet->getColumnDimension('M')->setWidth(20);
$activeWorksheet->getColumnDimension('P')->setWidth(20);
$activeWorksheet->getColumnDimension('Q')->setWidth(20);

//엑셀 파일 다운로드를 위한 설정
$excelFileName = iconv('UTF-8', 'EUC-KR', '전도인명단'.'_'.date('ymd').'.xlsx');
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
?>