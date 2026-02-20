<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">봉사인도 계획표</h1>
  <p class="text-white today-info mb-0 ml-md-auto">
    <a href="<?=BASE_PATH?>/pages/board.php" class="btn btn-outline-light btn-sm">
      <i class="bi bi-arrow-left"></i> 공지
    </a>
  </p>
</header>

<?php echo footer_menu('공지'); ?>

<?php
  $_calParams = '';
  if (isset($_GET['year']) && isset($_GET['month'])) {
      $_calParams = '?year=' . (int)$_GET['year'] . '&month=' . (int)$_GET['month'];
  }
?>
<div id="container" class="container-fluid p-0">
  <iframe src="<?=BASE_PATH?>/c/view.php<?=$_calParams?>"
          style="width:100%; height:calc(100vh - 110px); border:none;">
  </iframe>
</div>

<?php include_once('../footer.php');?>
