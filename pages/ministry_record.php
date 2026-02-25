<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">호별봉사 짝 배정</h1>
</header>

<?php echo footer_menu('오늘의 봉사'); ?>

<style>
iframe.auto-height { width:100%; border:none; min-height:calc(100vh - 110px); overflow:hidden; }
</style>
<?php
  $iframe_params = [];
  if (!empty($_GET['date'])) $iframe_params['date'] = $_GET['date'];
  if (!empty($_GET['meeting'])) $iframe_params['meeting'] = $_GET['meeting'];
  $iframe_qs = !empty($iframe_params) ? '?' . http_build_query($iframe_params) : '';
?>
<div id="container" class="container-fluid p-0">
  <iframe class="auto-height" src="<?=BASE_PATH?>/m/<?=$iframe_qs?>"></iframe>
</div>

<script>
(function(){
  var f = document.querySelector('iframe.auto-height');
  function resize(){
    try {
      var doc = f.contentWindow.document;
      f.style.height = doc.documentElement.scrollHeight + 'px';
    } catch(e){}
  }
  f.addEventListener('load', function(){
    try {
      var doc = f.contentWindow.document;
      // iframe 내부 min-height 제거 → scrollHeight가 실제 콘텐츠 높이 반영
      doc.documentElement.style.minHeight = '0';
      doc.documentElement.style.overflowX = 'hidden';
      doc.body.style.minHeight = '0';
      doc.body.style.overflowX = 'hidden';
      var shell = doc.querySelector('.app-shell');
      if (shell) shell.style.minHeight = '0';
      // 세로 max-height 제거 → 바깥 스크롤로 통합 (가로 스크롤은 container 내부 유지)
      var mc = doc.querySelectorAll('.matrix-container');
      mc.forEach(function(el){ el.style.maxHeight = 'none'; });
    } catch(e){}
    resize();
    try { new MutationObserver(resize).observe(f.contentWindow.document.body, {childList:true, subtree:true}); } catch(e){}
  });
  window.addEventListener('resize', resize);
})();
</script>

<?php include_once('../footer.php');?>
