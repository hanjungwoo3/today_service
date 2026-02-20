<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">주말집회(공개강연,파수대) 계획표</h1>
  <p class="text-white today-info mb-0 ml-md-auto">
    <a href="<?=BASE_PATH?>/pages/board.php" class="btn btn-outline-light btn-sm">
      <i class="bi bi-arrow-left"></i> 공지
    </a>
  </p>
</header>

<?php echo footer_menu('공지'); ?>

<div id="container" class="container-fluid p-0">
  <iframe src="<?=BASE_PATH?>/s/talk_view.php?embed=1"
          style="width:100%; height:calc(100vh - 110px); border:none;">
  </iframe>
</div>

<?php include_once('../footer.php');?>
