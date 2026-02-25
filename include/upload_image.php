<?php
$date = date("Y/m/");
$path = $_SERVER["DOCUMENT_ROOT"] . '/upload/' . $date;
if (!is_dir($path))
  mkdir($path, 0777, true);

if ($_FILES['file']['name']) {
  if (!$_FILES['file']['error']) {
    // 기존의 고작 100~200 사이의 경우의 수만 지닌 덮어쓰기 유발 버그를 고도화된 uniqid() 난수로 교체
    $name = md5(uniqid(rand(), true));

    // 파일명 파싱 방식 안정화 (기존 explode 버그 수정)
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $filename = $name . '.' . $ext;

    $destination = $path . $filename; //change this directory
    $location = $_FILES["file"]["tmp_name"];
    move_uploaded_file($location, $destination);
    echo '/upload/' . $date . $filename;//change this URL
  } else {
    echo $message = '파일 에러 발생!:  ' . $_FILES['file']['error'];
  }
}
?>