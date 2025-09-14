<?php
if(!empty($_FILES)){


	$req_idx = $_REQUEST["idx"];

    
    $targetDir = "../../upload/";
    $fileName = $_FILES['file']['name'];
    $targetFile = $targetDir.$fileName;
    

	//[
	//같은 파일명이 있지 않게 하기위해 파일명을 절대 중복이 불가능하게 만든다.
	mt_srand((double)microtime()*1000000);
	$new_file_name = time() . mt_rand(10000,99999);

	//확장자를 이용하여 업로드 가능한 파일인지 체크한다.
	$temp_name = explode(".",$_FILES['file']['name']);
	$ext = strtolower($temp_name[sizeof($temp_name)-1]);

	$file_name = $new_file_name . '.' . $ext; //파일 이름뒤에 확장자를 붙인다.
    $targetFile = $targetDir.$file_name;
	//]


    if(move_uploaded_file($_FILES['file']['tmp_name'],$targetFile)){
		echo $file_name;
    }
    
}else{

	echo "file upload error ";

}
?>