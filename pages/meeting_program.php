<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">평일집회 계획표</h1>
</header>

<?php echo footer_menu('오늘의 봉사'); ?>

<style>
@media (max-width: 768px) {
  #container { max-width: 600px; margin: 0 auto; }
}
iframe.auto-height { width:100%; border:none; min-height:calc(100vh - 110px); }
</style>
<?php
  $_meetParams = 'embed=1';
  if (isset($_GET['year']) && isset($_GET['week'])) {
      $_meetParams .= '&year=' . (int)$_GET['year'] . '&week=' . (int)$_GET['week'];
  }
?>
<div id="container" class="container-fluid p-0">
  <iframe class="auto-height" src="<?=BASE_PATH?>/s/view.php?<?=$_meetParams?>"></iframe>
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
})();
</script>

<?php include_once('../footer.php');?>
