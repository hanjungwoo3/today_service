<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">평일집회 계획표</h1>
</header>

<?php echo footer_menu('오늘의 봉사'); ?>

<style>
html, body { overflow: hidden; }
@media (max-width: 768px) {
  #container { max-width: 600px; margin: 0 auto; }
}
</style>
<?php
  $_meetParams = 'embed=1';
  if (isset($_GET['year']) && isset($_GET['week'])) {
      $_meetParams .= '&year=' . (int)$_GET['year'] . '&week=' . (int)$_GET['week'];
  }
?>
<div id="container" class="container-fluid p-0">
  <iframe src="<?=BASE_PATH?>/s/view.php?<?=$_meetParams?>"
          style="width:100%; height:calc(100vh - 110px); border:none;">
  </iframe>
</div>

<?php include_once('../footer.php');?>
