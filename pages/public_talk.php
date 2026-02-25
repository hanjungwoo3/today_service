<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">주말집회(공개강연,파수대) 계획표</h1>
</header>

<?php echo footer_menu('오늘의 봉사'); ?>

<style>
body { background: #f5f5f5 !important; }
iframe.auto-height { width:100%; border:none; min-height:calc(100vh - 110px); }
</style>
<div id="container" class="container-fluid p-0">
  <iframe class="auto-height" src="<?=BASE_PATH?>/s/talk_view.php?embed=1"></iframe>
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
