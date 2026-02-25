<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">청소/마이크/안내인/연사음료 계획표</h1>
</header>

<?php echo footer_menu('오늘의 봉사'); ?>

<style>
html, body { overflow: hidden; }
body { background: #f5f5f5 !important; }
</style>
<div id="container" class="container-fluid p-0">
  <iframe src="<?=BASE_PATH?>/s/duty_view.php?embed=1"
          style="width:100%; height:calc(100vh - 110px); border:none;">
  </iframe>
</div>

<?php include_once('../footer.php');?>
