<?php include_once('header.php');?>

<?php
$mb_id = mb_id();
$today = date("Y-m-d");
$ma_id = get_addschedule_id($today);

$sql = "SELECT * FROM ".MEETING_ADD_TABLE." WHERE ma_id IN({$ma_id}) ORDER BY ma_auto DESC, ma_date DESC, ma_date2, ma_title";
$result = $mysqli->query($sql);

$sql = "SELECT m_id FROM ".MEETING_TABLE." WHERE m_date = '{$today}' AND m_cancle = '1'";
$cancle = $mysqli->query($sql);

?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">오늘의 봉사 <span><?=date('m.d').' '.get_week_text(date('N'));?></span></h1>
  <p class="text-white today-info mb-0 ml-md-auto">
    <?php if(!is_moveout($mb_id)): ?>
    <a onclick="open_meeting_view('<?=$today?>')" class="btn btn-outline-light btn-sm"><i class="bi bi-info-circle"></i> 모임정보</a>
    <?php endif; ?>
  </p>
</header>

<?php echo footer_menu('오늘의 봉사');?>

<!-- 지정된 기간 동안의 추가일정, 알림사항 -->
<?php if(!is_moveout($mb_id)): ?>
<div class="list-group list-group-flush" style="margin-top: -12px">
  <?php if($result->num_rows > 0):?>
    <?php while($mar = $result->fetch_assoc()):?>
      <div class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-light">
        <span>
          <span class="badge badge-light text-info align-middle">일정</span>
          <span class="align-middle"><?=$mar['ma_title']?></span>
        </span>
        <a href="<?=BASE_PATH?>/pages/minister_schedule.php#minister_event" class="btn btn-outline-secondary btn-sm badge">상세 보기</a>
      </div>
    <?php endwhile;?>
  <?php endif;?>

  <?php if($cancle->num_rows > 0):?>
  <div class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-light">
    <span>
      <span class="badge badge-light text-danger align-middle">알림</span>
      <span class="align-middle">취소된 봉사모임이 있습니다.</span>
    </span>
    <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm badge" onclick="open_meeting_view('<?=$today?>')">상세보기</a>
  </div>
  <?php endif;?>

  <?php if(board_new($mb_id)):?>
    <div class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-light">
      <span>
        <span class="badge badge-light text-warning align-middle">공지</span>
        <span class="align-middle">새로운 공지사항이 있습니다.</span>
      </span>
      <a href="<?=BASE_PATH?>/pages/board.php?auth=<?=board_new($mb_id)?>" class="btn btn-outline-secondary btn-sm badge">상세보기</a>
    </div>
  <?php endif;?>

</div>

<div id="today-service-list">
  <?php include_once('pages/today_service_list.php'); ?>
</div>

<script type="text/javascript">
  setInterval(function(){ // 5초마다 오늘의 봉사업데이트
    pageload_custom(BASE_PATH+'/pages/today_service_list.php','#today-service-list'); 
  },5000);
</script>
<?php endif; ?>

<?php include_once('footer.php');?>