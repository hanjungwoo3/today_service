<?php include_once('../header.php');?>
<?php check_accessible('guide');?>

<?php
if(GUIDE_STATISTICS_USE != 'use'){
  echo '<script> location.href="'.BASE_PATH.'/"; </script>';
  exit;
}
?>

<?php
$mb_id = mb_id();
$sql = "SELECT ms_id, ma_id, ms_week, ms_time, mp_name, g_name, ms_guide, ms_guide2
        FROM ".MEETING_SCHEDULE_TABLE." ms INNER JOIN ".MEETING_PLACE_TABLE." mp ON ms.mp_id = mp.mp_id LEFT JOIN ".GROUP_TABLE." g ON ms.g_id = g.g_id
        ORDER BY ms_week, ms_time, g_name, mp_name, ms_id ASC";
$result = $mysqli->query($sql);
?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">인도자 <span class="d-xl-none">통계</span></h1>
  <?php echo header_menu('guide','통계'); ?>
</header>

<?php echo footer_menu('인도자'); ?>

<div id="container" class="container-fluid">
  <select class="form-control" name="guide_ms_id">
    <?php
    if($result->num_rows>0):
      $ms_ids = explode(',', get_ms_id_by_guide($mb_id));
      while($row = $result->fetch_assoc()){
        if(is_admin($mb_id) || in_array($row['ms_id'], $ms_ids)){
          if(!$ms_id) $ms_id = $row['ms_id'];
          echo "<option value='".$row['ms_id']."' ".get_selected_text($row['ms_id'], $ms_id).">".'('.$row['ms_id'].') '.get_week_text($row['ms_week']).' '.get_meeting_data_text($row['ms_time'], $row['g_name'], $row['mp_name']);
          if($row['ma_id']) echo ' [회중일정]';
          echo '</option>';
        }
      }
    else:?>
    <option value="">선택할 모임이 없습니다.</option>
    <?php endif;?>
  </select>

  <div id="guide_statistics">
    <?php include_once('guide_statistics_sub.php'); ?>
  </div>
</div>

<?php include_once('../footer.php');?>
