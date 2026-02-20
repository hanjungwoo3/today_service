<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">호별봉사 전도인 기록</h1>
  <p class="text-white today-info mb-0 ml-md-auto">
    <a href="<?=BASE_PATH?>/pages/board.php" class="btn btn-outline-light btn-sm">
      <i class="bi bi-arrow-left"></i> 공지
    </a>
  </p>
</header>

<?php echo footer_menu('공지'); ?>

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
