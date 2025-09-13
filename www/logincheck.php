<?php header('Content-Type: text/html; charset=utf-8');?>
<?php include_once('config.php');?>

<?php
$m_name = xss_filter($_POST["m_name"]);
$m_pw = xss_filter($_POST["m_pw"]);
if(isset($_POST["m_logincheck"])){
	$m_logincheck = xss_filter($_POST["m_logincheck"]);
}else{
	$m_logincheck = 'off';
}
$m_token = xss_filter($_POST["m_token"]);

if ($m_token != $_SESSION['m_token']){
	echo "<SCRIPT LANGUAGE='JavaScript'>
		alert('로그인 오류');
		history.back();
		</SCRIPT>";
		exit;
}

$sql="SELECT mb_id, mb_name, mb_hash FROM ".MEMBER_TABLE." WHERE mb_name='{$m_name}'";
$result=$mysqli->query($sql);

if($result->num_rows <= 0){
	echo "<SCRIPT LANGUAGE='JavaScript'>
		alert('등록된 전도인이 아니거나 비밀번호가 잘못되었습니다.');
		history.back();
		</SCRIPT>";
	exit;
}else{
	$rs = $result->fetch_assoc();

	if(password_verify($m_pw, $rs['mb_hash'])){

		$_SESSION['mb_id'] = $rs["mb_id"];

		if ($m_logincheck=="on"){
			$cookie_name = "jw_ministry";
			$cookie_value = $rs["mb_name"]."|".$m_pw;
			setcookie($cookie_name, $cookie_value, time() + (86400 * 365), '/'); // 86400 = 1 day
		}else{
			$cookie_name = "jw_ministry";
			setcookie($cookie_name, "", time() - 3600);
		}

		echo "<SCRIPT LANGUAGE='JavaScript'>
			location.href='".BASE_PATH."/';
			</SCRIPT>";
			exit;
	}else{
		echo "<SCRIPT LANGUAGE='JavaScript'>
			alert('등록된 전도인이 아니거나 비밀번호가 잘못되었습니다.');
			history.back();
			</SCRIPT>";
			exit;
	}

}

?>
