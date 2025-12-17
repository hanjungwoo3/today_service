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
  <div id="meeting_calendar" class="mb-2"></div>

  <div id="meeting_calendar_schedule"></div>
</div>

<script>
  function getLocalDateYMD(){
    const now = new Date();
    return [
      now.getFullYear(),
      String(now.getMonth() + 1).padStart(2, '0'),
      String(now.getDate()).padStart(2, '0')
    ].join('-');
  }

  document.addEventListener('DOMContentLoaded', () => {
    const localYmd = getLocalDateYMD();
    const toYear = localYmd.split('-')[0];
    const toMonth = String(parseInt(localYmd.split('-')[1], 10)); // 앞자리 0 제거

    // 로컬 날짜 기준으로 초기 데이터 로드
    pageload_custom(BASE_PATH+'/pages/meeting_calendar.php?s_date='+localYmd+'&toYear='+toYear+'&toMonth='+toMonth, '#meeting_calendar');
    pageload_custom(BASE_PATH+'/pages/meeting_calendar_schedule.php?s_date='+localYmd, '#meeting_calendar_schedule');
  });
</script>

<?php include_once('../footer.php'); ?>
