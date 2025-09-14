<?php include_once('config.php');?>
<?php
if(!isset($_SERVER["HTTPS"])){
	header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
	exit;
}?>
<?php session_check();?>

<?php $_SESSION['m_token'] = md5(uniqid(mt_rand(), true));?>
<?php $m_token = $_SESSION['m_token'];?>

<!DOCTYPE html>
<html lang="ko">
  <head>
    <link rel="manifest" href="<?=BASE_PATH?>/manifest.php" />
    <meta charset="utf-8">
    <meta name="description" content="JW Ministry"/>
    <meta name="robots" content="noindex,nofollow"/>
    <!-- 안드로이드 홈화면추가시 상단 주소창 제거 -->
    <meta name="mobile-web-app-capable" content="yes">
    <!-- ios홈화면추가시 상단 주소창 제거 -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, shrink-to-fit=no, minimum-scale=1.0, user-scalable=no">
    <title>오늘의 봉사</title>
    
    <link rel="shortcut icon" href="<?=ICON?>" type="image/x-icon"/>
    <link rel="icon" href="<?=ICON?>" type="image/x-icon"/>

    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="<?=ICON?>" />
    <meta name="apple-mobile-web-app-title" content="오늘의 봉사">
    <meta name="apple-mobile-web-app-status-bar-style" content="#4a6da7">
    <!-- <meta name="apple-itunes-app" content="app-id=myAppStoreID"> -->
    <link rel="apple-touch-icon" href="<?=ICON?>">
    <link rel="apple-touch-startup-image" href="<?=ICON?>">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/gh/moonspam/NanumSquare@latest/nanumsquare.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="<?=BASE_PATH?>/css/stylev2.css?ver=<?=VERSION?>" >

    <!-- Full 버전 jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js" integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s" crossorigin="anonymous"></script>
    <script type="text/javascript" src="<?=BASE_PATH?>/js/script.js?ver=<?=VERSION?>"></script>
    <script type="text/javascript" src="<?=BASE_PATH?>/js/script_h.js?ver=<?=VERSION?>"></script>

  </head>
  <body>
    <div id="container" class="container-fluid">
      <div class="mt-5 p-5">
        <div class="mb-3"><img src="<?=ICON?>" alt="" class="rounded mx-auto d-block" width="80px"></div>
        <h5 class="text-center mb-4"><?=SITE_NAME?></h5>
        <form method="post" action="./logincheck.php">
          <input type="hidden" id="m_token" name="m_token" value="<?=$m_token?>">
          <div class="input-group mb-3">
            <input type="text" class="form-control" name="m_name" autocomplete="off" value="" placeholder="이름">
          </div>
          <div class="input-group mb-3">
            <input type="password" class="form-control" name="m_pw" value="" placeholder="비밀번호" style="font-family: arial;">
          </div>
          <div class="custom-control custom-checkbox mb-5">
            <input type="checkbox" id="m_logincheck" class="custom-control-input" name="m_logincheck">
            <label class="custom-control-label" for="m_logincheck">로그인 상태 유지</label>
          </div>
          <div class="text-center">
            <button type="submit" class="btn btn-outline-primary w-100">로그인</button>
          </div>
        </form>
      </div>
      <div class="text-secondary text-center mt-3"><small>앱 버전 <?=APP_VERSION?></small></div>
    </div>
  </body>
</html>
