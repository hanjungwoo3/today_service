<?php include_once('../config.php'); ?>

<?php
$table = isset($_POST['table']) ? $_POST['table'] : (isset($_GET['table']) ? $_GET['table'] : '');
$pid = isset($_POST['pid']) ? intval($_POST['pid']) : (isset($_GET['pid']) ? intval($_GET['pid']) : 0);

if ($table == 'territory') {
  $memo = get_territory_memo($pid);
} elseif ($table == 'telephone') {
  $memo = get_telephone_memo($pid);
} else {
  $memo = '';
}

// DB 저장 시 이스케이프된 백슬래시를 제거하고, 출력 시 HTML 이스케이프
$memo_safe = htmlspecialchars(stripslashes((string) (isset($memo) ? $memo : '')), ENT_QUOTES, 'UTF-8');
?>

<form id="memo-form" action="" method="post">
  <div class="form-group mb-4">
    <input type="hidden" name="work" value="memo">
    <input type="hidden" name="table" value="<?= $table ?>">
    <input type="hidden" name="pid" value="<?= $pid ?>">
    <label for="content" class="bmd-label-floating">내용</label>
    <textarea class="form-control" name="memo" rows="4"><?= $memo_safe ?></textarea>
  </div>
  <div class="form-group" style="text-align:right;">
    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save"></i> 저장</button>
  </div>
</form>