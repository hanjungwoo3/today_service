<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">봉사인도 계획표</h1>
</header>

<?php echo footer_menu('오늘의 봉사'); ?>

<?php
  $_calParams = '';
  if (isset($_GET['year']) && isset($_GET['month'])) {
      $_calParams = '?year=' . (int)$_GET['year'] . '&month=' . (int)$_GET['month'];
  }
?>
<style>html, body { overflow: hidden; }</style>
<div id="container" class="container-fluid p-0">
  <iframe src="<?=BASE_PATH?>/c/view.php<?=$_calParams?>"
          style="width:100%; height:calc(100vh - 110px); border:none;">
  </iframe>
</div>

<?php include_once('../footer.php');?>
