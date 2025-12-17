<?php include_once('../header.php');?>
<?php check_accessible('guide');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">인도자 <span class="d-xl-none">모임</span></h1>
  <?php echo header_menu('guide','모임'); ?>
</header>

<?php echo footer_menu('인도자'); ?>

<div id="container" class="container-fluid">

  <nav class="navbar navbar-light bg-light mb-2">
    <small class="float-right mb-1 mt-1 text-secondary">모임 일시와 장소를 확인해 주세요<br></small>
  </nav>

  <small class="text-secondary">날짜 선택</small>
  <div class="input-group mb-4" id="guide_history_date">
    <input type="date" class="form-control" id="dateInput">
    <div class="input-group-append">
      <button class="btn btn-outline-secondary" type="button">오늘날짜로</button>
    </div>
  </div>

  <div id="guide_history_list">
  </div>
</div>

<script>
    // 페이지가 로드된 후 실행
    document.addEventListener('DOMContentLoaded', () => {
      const dateInput = document.getElementById('dateInput');
      const today = new Date();

      const year = today.getFullYear();
      const month = String(today.getMonth() + 1).padStart(2, '0'); // 월은 0부터 시작하므로 +1
      const day = String(today.getDate()).padStart(2, '0');

      const formattedDate = `${year}-${month}-${day}`;
      dateInput.value = formattedDate;

      // 초기 로딩 시에도 로컬 날짜를 기준으로 리스트를 불러와 서버 타임존 차이로 하루 당겨지는 현상 방지
      pageload_custom(BASE_PATH + '/pages/guide_history_list.php?s_date=' + formattedDate, '#guide_history_list');

    });

</script>

<?php include_once('../footer.php'); ?>
