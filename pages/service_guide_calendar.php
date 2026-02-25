<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">봉사인도 계획표</h1>
</header>

<?php echo footer_menu('오늘의 봉사'); ?>

<style>
iframe.auto-height { width:100%; border:none; min-height:calc(100vh - 110px); overflow:hidden; }
</style>
<?php
  $_calParams = '';
  if (isset($_GET['year']) && isset($_GET['month'])) {
      $_calParams = '?year=' . (int)$_GET['year'] . '&month=' . (int)$_GET['month'];
  }
?>
<div id="container" class="container-fluid p-0">
  <iframe class="auto-height" src="<?=BASE_PATH?>/c/view.php<?=$_calParams?>"></iframe>
</div>

<script>
(function(){
  var f = document.querySelector('iframe.auto-height');
  function resize(){
    try { f.style.height = f.contentWindow.document.documentElement.scrollHeight + 'px'; } catch(e){}
  }
  f.addEventListener('load', function(){
    resize();
    try { new MutationObserver(resize).observe(f.contentWindow.document.body, {childList:true, subtree:true}); } catch(e){}
  });
  window.addEventListener('resize', resize);
})();
</script>

<?php include_once('../footer.php');?>
