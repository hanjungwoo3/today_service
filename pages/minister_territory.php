<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">봉사자 <span class="d-xl-none">나의 구역</span></h1>
  <?php echo header_menu('minister','나의 구역'); ?>
</header>

<?php echo footer_menu('봉사자'); ?>

<div id="container" class="container-fluid">
  <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">일반</span></h5>
  <div id="minister-territory-list">
    <?php include_once('minister_territory_list.php'); ?>
  </div>
  <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">전화</span></h5>
  <div id="minister-telephone-list">
    <?php include_once('minister_telephone_list.php'); ?>
  </div>
  <h5 class="border-bottom mt-4 mb-3 pb-2 clearfix"><span class="align-middle mt-2 d-inline-block">편지</span></h5>
  <div id="minister-letter-list">
    <?php include_once('minister_letter_list.php'); ?>
  </div>
</div>

<?php include_once('../footer.php');?>
