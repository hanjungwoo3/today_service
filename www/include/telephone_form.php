<?php include_once('../config.php');?>
<?php check_accessible('admin');?>

<?php
$work = 'add';
if($tp_id){
  $work = 'edit';
  $sql = "SELECT * FROM ".TELEPHONE_TABLE." WHERE tp_id = {$tp_id}";
  $result = $mysqli->query($sql);
  $row = $result->fetch_assoc();
}
?>

<div class="container-fluid">
  <form id="admin_telephone_form">
    <input type="hidden" name="work" value="<?=$work?>">
    <input type="hidden" name="tp_id" value="<?=$tp_id?>">

    <div class="form-group row">
      <label for="tp_num" class="col-4 col-md-2 col-form-label">구역 번호</label>
      <div class="col-8 col-md-10">
        <input type="text" class="form-control" id="tp_num" name="tp_num" value="<?=!empty($row['tp_num'])?$row['tp_num']:''?>" required>
      </div>
    </div>

    <div class="form-group row">
      <label for="tp_name" class="col-4 col-md-2 col-form-label">구역 이름</label>
      <div class="col-8 col-md-10">
        <input type="text" class="form-control" id="tp_name" name="tp_name" value="<?=!empty($row['tp_name'])?$row['tp_name']:''?>" required>
      </div>
    </div>

    <div class="form-group row">
      <label for="mb_id" class="col-4 col-md-2 col-form-label">개인 구역</label>
      <div class="col-8 col-md-10">
        <select class="form-control" id="mb_id" name="mb_id">
          <option value="0">선택 안 함</option>
          <?php echo get_member_option($row['mb_id']);?>
        </select>
      </div>
    </div>

    <div class="form-group row">
      <label for="tp_mb_date" class="col-4 col-md-2 col-form-label">개인 구역<br>배정 날짜</label>
      <div class="col-8 col-md-10">
        <input type="date" class="form-control" id="tp_mb_date" name="tp_mb_date" value="<?=!empty($row['tp_mb_date'])?$row['tp_mb_date']:'0000-00-00'?>">
      </div>
    </div>

    <div class="mt-2 text-right">
      <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save"></i> 저장</button>
    </div>
  </form>
</div>
