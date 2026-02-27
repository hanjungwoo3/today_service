<?php include_once('config.php');?>
<?php
if(!isset($_SERVER["HTTPS"])){
	header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
	exit;
}?>
<?php session_check();?>
<?php site_work(); // 사이트 자동 작업?>

<?php
if(is_moveout(mb_id())){ // 전출전도인일때
  $access_pages = array(
    BASE_PATH.'/',
    BASE_PATH.'/pages/minister_schedule.php',
    BASE_PATH.'/pages/minister_territory.php',
    BASE_PATH.'/pages/minister_returnvisit.php',
    BASE_PATH.'/pages/minister_telephone.php',
    BASE_PATH.'/pages/minister_letter.php',
    BASE_PATH.'/pages/minister_personal.php',
    BASE_PATH.'/pages/minister_personal_info.php',
    BASE_PATH.'/pages/minister_statistics.php'
  ); 

  if(!in_array($_SERVER['REQUEST_URI'],$access_pages)){
    echo '<script> location.href="'.BASE_PATH.'/"; </script>';
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="ko">
  <head>
    <link rel="manifest" href="<?=BASE_PATH?>/manifest.php?ver=<?=VERSION?>" />
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
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/moonspam/NanumSquare@latest/nanumsquare.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="<?=BASE_PATH?>/css/stylev2.css?ver=<?=VERSION?>" >

    <!-- Full 버전 jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js" integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s" crossorigin="anonymous"></script>
    <script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=<?=MAP_API_KEY?>&libraries=services,drawing"></script>
    <script type="text/javascript" src="<?=BASE_PATH?>/js/jsColorPicker.min.js"></script>
    <script type="text/javascript" src="<?=BASE_PATH?>/js/jquery.tablednd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.0/lang/summernote-ko-KR.min.js"></script>

    <script src="//developers.kakao.com/sdk/js/kakao.min.js"></script>
    <script type='text/javascript'>
      //<![CDATA[
        // 사용할 앱의 JavaScript 키를 설정해 주세요.
        if (window.Kakao) {
          Kakao.init('<?=MAP_API_KEY?>');
        }
      //]]>
    </script>

     <!-- Vue.js Production (최신 버전) -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.14/dist/vue.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vuetify@2.6.16/dist/vuetify.min.js"></script>

    <!-- 관리자 통계 구글차트 -->
    <script type="text/javascript" src="<?=BASE_PATH?>/js/charts.js"></script>
    <script type="text/javascript" src="<?=BASE_PATH?>/js/script.js?ver=<?=VERSION?>"></script>
    <script type="text/javascript" src="<?=BASE_PATH?>/js/script_h.js?ver=<?=VERSION?>"></script>
    <script type="text/javascript" src="<?=BASE_PATH?>/js/push.js?ver=<?=VERSION?>"></script>

    <script>
    // BASE_PATH를 JavaScript에서 사용할 수 있도록 전역 변수 설정
    var BASE_PATH = '<?=BASE_PATH?>';
    
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?=BASE_PATH?>/sw.js')
        .then(registration => {
            console.log('Service Worker registered with scope:', registration.scope);
        })
        .catch(error => {
            console.error('Service Worker registration failed:', error);
        });
    }
  </script>

  </head>
  <body <?=$_SERVER['REQUEST_URI']==BASE_PATH.'/'?'class="bg-light"':''?>>