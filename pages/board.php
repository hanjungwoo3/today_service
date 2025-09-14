<?php include_once('../header.php');?>

<?php $auth = isset($auth)?$auth:1;?>
<?php $board_title = array('','봉사자','파이오니아','인도자','봉사의 종','장로','관리자','질문과 대답');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">공지
    <span class="d-xl-none"><?=$board_title[$auth]?></span>
  </h1>
  <?php echo header_menu('board',$auth);?>
</header>

<?php echo footer_menu('공지'); ?>

<div id="container" class="container-fluid">
  <div id="board_frame">
    <?php include_once('board_list.php'); ?>
  </div>
</div>

<?php include_once('../footer.php'); ?>
