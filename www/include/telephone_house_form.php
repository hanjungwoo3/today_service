<?php include_once('../config.php');?>
<?php check_accessible('admin');?>

<?php
$c_territory_type = unserialize(TERRITORY_TYPE);
$tp_data = get_telephone_data($tp_id);

$sql = "SELECT * FROM ".TELEPHONE_HOUSE_TABLE." WHERE tp_id = {$tp_id} ORDER BY tph_order ASC";
$result = $mysqli->query($sql);
?>

<div class="container-fluid">
  <h6>[<?=$tp_data['tp_num']?>] <?=$tp_data['tp_name']?></h6>
  <form id="telephone_house_form">
    <input type="hidden" name="work" value="telephone_house">
    <input type="hidden" name="tp_id" value="<?=$tp_id?>">
    <div class="table-responsive">
      <table class="table mb-0 table-striped" style="min-width: 1200px;">
        <colgroup>
          <col style="width:40px;">
          <col style="width:60px;">
          <col style="width:90px;">
          <col style="width:180px;">
          <col style="width:140px;">
          <col style="width:140px;">
          <col style="">
          <col style="width:100px;">
          <col style="width:80px;">
          <col style="width:40px;">
        </colgroup>
        <thead class="thead-light">
          <tr>
            <th class="text-center"></th>
            <th class="text-center">기존순서</th>
            <th class="text-center">순서이동</th>
            <th class="text-center">전화</th>
            <th class="text-center"><?=!empty($c_territory_type['type_6'][2])?$c_territory_type['type_6'][2]:'업종';?></th>
            <th class="text-center"><?=!empty($c_territory_type['type_6'][3])?$c_territory_type['type_6'][3]:'상호';?></th>
            <th class="text-center"><?=!empty($c_territory_type['type_6'][4])?$c_territory_type['type_6'][4]:'주소';?></th>
            <th class="text-center">특이사항</th>
            <th class="text-center">삭제 &nbsp;<input type="checkbox" class="align-middle" onclick="if($(this).is(':checked')){ $('#telephone_house_form tbody input[type=checkbox]').prop('checked', true); }else{ $('#telephone_house_form input[type=checkbox]').prop('checked', false); }"></th>
            <th class="text-center"></th>
          </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
          <?php $tph_id = $row['tph_id'];?>
          <tr>
            <td class="text-center dragHandle align-middle"><i class="bi bi-grip-vertical"></i></td>
            <td class="text-center align-middle"><?=$row['tph_order']?></td>
            <td class="text-center align-middle">
              <button type="button" class="btn btn-outline-secondary btn-sm align-middle border-0 p-0" onclick="$(this).parent().parent().prev().before($(this).parent().parent());"><i class="bi bi-caret-up-fill h4"></i></button>
              <button type="button" class="btn btn-outline-secondary btn-sm align-middle border-0 p-0" onclick="$(this).parent().parent().next().after($(this).parent().parent());"><i class="bi bi-caret-down-fill h4"></i></button>
            </td>
            <td><input type="text" value="<?=$row['tph_number']?>" class="form-control" name="telephone_house[<?=$tph_id;?>][tph_number]"></td>
            <td><input type="text" value="<?=$row['tph_type']?>" class="form-control" name="telephone_house[<?=$tph_id;?>][tph_type]"></td>
            <td><input type="text" value="<?=$row['tph_name']?>" class="form-control" name="telephone_house[<?=$tph_id;?>][tph_name]"></td>
            <td><input type="text" value="<?=$row['tph_address']?>" class="form-control" name="telephone_house[<?=$tph_id;?>][tph_address]"></td>
            <td class="text-center align-middle">
              <span class="condition-chip<?=$row['tph_condition']?>" ><?=get_house_condition_text($row['tph_condition'])?></span>
            </td>
            <td class="text-center align-middle"><input type="checkbox" class="align-middle" name="telephone_house[<?=$tph_id;?>][delete]" value="delete"></td>
            <td class="text-center dragHandle align-middle"><i class="bi bi-grip-vertical"></i></td>
          </tr>
        <?php endwhile;?>
        </tbody>
      </table>
    </div>
    <div class="mt-4">
      <button type="button" class="btn btn-outline-primary float-left" onclick="telephone_house_add();"><i class="bi bi-plus-circle-dotted"></i> 추가</button>
      <button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-save"></i> 저장</button>
    </div>
  </form>
</div>

<script type="text/javascript">
  $(document).ready(function() {
    $("#telephone_house_form table").tableDnD({
      onDragClass: "myDrag",
      dragHandle: ".dragHandle"
    });

    $("#telephone_house_form tr").hover(function() {
            $(this.cells[0]).addClass('showDragHandle');
            $(this.cells[9]).addClass('showDragHandle');
      }, function() {
            $(this.cells[0]).removeClass('showDragHandle');
            $(this.cells[9]).removeClass('showDragHandle');
      });
  });
</script>
