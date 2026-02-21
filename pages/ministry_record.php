<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">호별봉사 짝 배정</h1>
</header>

<?php echo footer_menu('오늘의 봉사'); ?>

<?php
  $iframe_params = [];
  if (!empty($_GET['date'])) $iframe_params['date'] = $_GET['date'];
  if (!empty($_GET['meeting'])) $iframe_params['meeting'] = $_GET['meeting'];
  $iframe_qs = !empty($iframe_params) ? '?' . http_build_query($iframe_params) : '';
?>
<div id="container" class="container-fluid p-0">
  <iframe src="<?=BASE_PATH?>/m/<?=$iframe_qs?>"
          style="width:100%; height:calc(100vh - 110px); border:none;">
  </iframe>
</div>

<?php include_once('../footer.php');?>
