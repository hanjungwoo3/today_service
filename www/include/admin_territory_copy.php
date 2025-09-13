<?php include_once('../config.php');?>

<form method="post" enctype="multipart/form-data" id="copy-territory">
  <input type="hidden" name="table" value="<?=$table?>">
  <input type="hidden" name="pid" value="<?=$pid?>">
  <input type="hidden" name="work" value="copy">
  <div class="form-group row">
    <label for="inputPassword" class="col-sm-8 col-form-label">복제할 구역 개수</label>
    <div class="col-sm-4">
      <input type="number" name="count" min="1" max="100" class="form-control" value="1">
    </div>
  </div>
  <button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-stickies"></i> 복제</button>
</form>
