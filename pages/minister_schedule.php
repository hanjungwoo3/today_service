<?php include_once('../header.php'); ?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">봉사자 <span class="d-xl-none">나의 봉사</span></h1>
  <?php echo header_menu('minister','나의 봉사'); ?>
</header>

<?php echo footer_menu('봉사자'); ?>

<div id="container" class="container-fluid">
  <div id="minister_calendar" class="mb-2">
    <?php include_once('minister_calendar.php'); ?>
  </div>

  <div id="minister_calendar_schedule">
    <?php include_once('minister_calendar_schedule.php'); ?>
  </div>

</div>

<?php include_once('../footer.php');?>
