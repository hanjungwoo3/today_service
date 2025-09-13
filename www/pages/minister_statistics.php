<?php include_once('../header.php');?>

<?php
$month = date("n");
$first_d = mktime(0,0,0, date("m"), 1, date("Y"));
$year = ($month >= 9)?date("Y", strtotime("+1 year", $first_d)):date("Y");
?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">봉사자 <span  class="d-xl-none">나의 통계</span></h1>
  <?php echo header_menu('minister','나의 통계'); ?>
</header>

<?php echo footer_menu('봉사자'); ?>

<div id="container" class="container-fluid">
  <h4 class="my-3 font-weight-bold">
    <form id="mininster_stat_search">
      <select class="form-control w-auto d-inline" name="st_year">
        <?php for($i=$year; $i >= 2018; $i--) echo '<option value="'.$i.'">'.$i.'</option>';?>
      </select>
      봉사 연도 통계
    </form>
  </h4>

  <div id="minister_stat">
    <?php include_once('minister_statistics_sub.php'); ?>
  </div>
</div>

<?php include_once('../footer.php');?>
