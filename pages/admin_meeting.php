<?php include_once('../header.php');?>
<?php check_accessible('admin');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">관리자 <span class="d-xl-none">모임 계획 관리</span></h1>
  <?php echo header_menu('admin','모임 계획 관리'); ?>
</header>

<?php echo footer_menu('관리자'); ?>

<div id="container" class="container-fluid">

  <div class="clearfix">
    <button type="button" class="btn btn-outline-primary float-right" onclick="meeting_work('add','','');">
      <i class="bi bi-plus-circle-dotted"></i> 추가
    </button>
  </div>

  <div id="admin_meeting_list">
    <?php include_once('admin_meeting_list.php'); ?>
  </div>

</div>

<?php include_once('../footer.php'); ?>
