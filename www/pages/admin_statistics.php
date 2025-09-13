<?php include_once('../header.php');?>
<?php check_accessible('admin');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">관리자 <span class="d-xl-none">통계</span></h1>
  <?php echo header_menu('admin','통계'); ?>
</header>

<?php echo footer_menu('관리자'); ?>

<div id="container" class="container-fluid">
  <ul class="nav nav-tabs" id="admin_statistics_tab">
    <li class="nav-item">
      <a class="nav-link active" href="#" url="admin_statistics_member">전도인</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#" url="admin_statistics_meeting">참여자</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#" url="admin_statistics_territory">일반</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#" url="admin_statistics_telephone">전화</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#" url="admin_statistics_letter">편지</a>
    </li>
  </ul>
  <div id="admin_statistics_view">
    <?php include_once('admin_statistics_member.php'); ?>
  </div>
</div>

<?php include_once('../footer.php');?>
