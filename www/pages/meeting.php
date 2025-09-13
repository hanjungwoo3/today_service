<?php include_once('../header.php');?>

<?php
// 전시대 페이지 접근 권한 확인
check_accessible('display');
?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">전시대</h1>
</header>

<?php echo footer_menu('전시대'); ?>

<div id="container" class="container-fluid">
  <div id="meeting_calendar" class="mb-2">
    <?php include_once('meeting_calendar.php'); ?>
  </div>

  <div id="meeting_calendar_schedule">
    <?php include_once('meeting_calendar_schedule.php'); ?>
  </div>
</div>

<?php include_once('../footer.php'); ?>
