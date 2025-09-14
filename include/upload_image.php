<?php
$date = date("Y/m/");
$path = $_SERVER["DOCUMENT_ROOT"].'/upload/'.$date;
if (!is_dir($path)) mkdir($path, 0777, true);

if($_FILES['file']['name']) {
  if(!$_FILES['file']['error']) {
    $name = md5(rand(100, 200));
    $ext = explode('.', $_FILES['file']['name']);
    $filename = $name . '.' . $ext[1];
    $destination = $path.$filename; //change this directory
    $location = $_FILES["file"]["tmp_name"];
    move_uploaded_file($location, $destination);
    echo '/upload/'.$date.$filename;//change this URL
  }else{
    echo  $message = '파일 에러 발생!:  '.$_FILES['file']['error'];
  }
}
?>
