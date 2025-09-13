<?php include_once('../header.php');?>
<?php check_accessible('admin');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">관리자 <span class="d-xl-none">회중 일정 관리</span></h1>
  <?php echo header_menu('admin','회중 일정 관리'); ?>
</header>

<?php echo footer_menu('관리자'); ?>

<div id="container" class="container-fluid">
  <div class="mb-3 clearfix">
    <button type="button" class="btn btn-outline-primary float-right" onclick="addschedule_work('add','');">
      <i class="bi bi-plus-circle-dotted"></i> 추가
    </button>
  </div>

  <div id="admin_addschedule_list">
    <?php include_once('admin_addschedule_list.php'); ?>
  </div>
</div>

<?php include_once('../footer.php'); ?>
