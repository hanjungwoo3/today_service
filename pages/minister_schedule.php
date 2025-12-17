<?php include_once('../header.php'); ?>
<script>
  // 서버 타임존 기반 초기 페인트를 숨겨 로컬 날짜로만 보이게 함
  document.documentElement.style.visibility = 'hidden';
</script>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">봉사자 <span class="d-xl-none">나의 봉사</span></h1>
  <?php echo header_menu('minister','나의 봉사'); ?>
</header>

<?php echo footer_menu('봉사자'); ?>

<div id="container" class="container-fluid">
  <div id="minister_calendar" class="mb-2"></div>

  <div id="minister_calendar_schedule"></div>

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
    pageload_custom(BASE_PATH+'/pages/minister_calendar.php?s_date='+localYmd+'&toYear='+toYear+'&toMonth='+toMonth, '#minister_calendar');
    pageload_custom(BASE_PATH+'/pages/minister_calendar_schedule.php?s_date='+localYmd, '#minister_calendar_schedule');

    // 화면 표시
    document.documentElement.style.visibility = 'visible';
  });
</script>

<?php include_once('../footer.php');?>
