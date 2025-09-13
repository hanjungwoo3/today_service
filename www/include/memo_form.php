<?php include_once('../config.php');?>

<?php
if($table == 'territory'){
  $memo = get_territory_memo($pid);
}elseif($table == 'telephone'){
  $memo = get_telephone_memo($pid);
}
?>

<form id="memo-form" action="" method="post">
  <div class="form-group mb-4">
    <input type="hidden" name="work" value="memo">
    <input type="hidden" name="table" value="<?=$table?>">
    <input type="hidden" name="pid" value="<?=$pid?>">
    <label for="content" class="bmd-label-floating">내용</label>
    <textarea class="form-control" name="memo" rows="4" ><?=$memo?></textarea>
  </div>
  <div class="form-group" style="text-align:right;">
    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save"></i> 저장</button>
  </div>
</form>
