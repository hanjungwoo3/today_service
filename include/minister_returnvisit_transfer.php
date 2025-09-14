<?php include_once('../config.php');?>

<form action="" method="post" id="returnvisit-transfer-form">
  <input type="hidden" name="work" value="transfer">
  <input type="hidden" name="table" value="<?=$table?>">
  <input type="hidden" name="pid" value="<?=$pid?>">
  <div class="form-group row">
    <label class="col-4 col-form-label">전도인 선택</label>
    <div class="col-8">
      <select name="mb" class="form-control" required>
        <option value="">전도인을 선택해주세요</option>
         <?php echo get_member_option('');?>
      </select>
    </div>
  </div>
  <div class="text-right">
    <button type="submit" class="btn btn-outline-primary">양도하기</button>
  </div>
</form>
