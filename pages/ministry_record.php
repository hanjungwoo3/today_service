<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">호별봉사 짝 배정</h1>
</header>

<?php echo footer_menu('오늘의 봉사'); ?>

<style>
iframe.auto-height { width:100%; border:none; min-height:calc(100vh - 110px); }
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
    try { f.style.height = f.contentWindow.document.documentElement.scrollHeight + 'px'; } catch(e){}
  }
  f.addEventListener('load', function(){
    resize();
    try { new MutationObserver(resize).observe(f.contentWindow.document.body, {childList:true, subtree:true}); } catch(e){}
  });
})();
</script>

<?php include_once('../footer.php');?>
