<?php include_once('../config.php');?>

<?php
$month = date("n");
$first_d = mktime(0,0,0, date("m"), 1, date("Y"));
$year = ($month >= 9)?date("Y", strtotime("+1 year", $first_d)):date("Y");
?>

<h4 class="my-4 font-weight-bold">
  <form id="stat_meeting_year_search">
    <select class="form-control w-auto d-inline" name="st_year">
      <?php for ($i=$year; $i >= 2018; $i--) echo '<option value="'.$i.'">'.$i.'</option>';?>
    </select>
    봉사 연도 참여자 통계
  </form>
</h4>

<div id="statistics_meeting">
  <?php include_once('admin_statistics_meeting_sub.php'); ?>
</div>
