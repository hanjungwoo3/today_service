<?php include_once('../config.php'); ?>
<?php check_accessible('admin'); ?>

<?php
$c_territory_type = unserialize(TERRITORY_TYPE);
$tt_data = get_territory_data($tt_id);

// 테이블 라벨
if ($tt_data['tt_type'] == '아파트') {
  $address1_label = $c_territory_type['type_2'][1] ? $c_territory_type['type_2'][1] : '아파트명';
  $address2_label = $c_territory_type['type_2'][2] ? $c_territory_type['type_2'][2] : '동';
  $address3_label = $c_territory_type['type_2'][3] ? $c_territory_type['type_2'][3] : '호';
  $address4_label = '';
  $address5_label = '';
} elseif ($tt_data['tt_type'] == '빌라') {
  $address1_label = $c_territory_type['type_3'][1] ? $c_territory_type['type_3'][1] : '빌라명';
  $address2_label = $c_territory_type['type_3'][2] ? $c_territory_type['type_3'][2] : '동';
  $address3_label = $c_territory_type['type_3'][3] ? $c_territory_type['type_3'][3] : '호';
  $address4_label = '';
  $address5_label = '';
} elseif ($tt_data['tt_type'] == '편지') {
  $address1_label = '길이름';
  $address2_label = '건물번호';
  $address3_label = $c_territory_type['type_5'][3] ? $c_territory_type['type_5'][3] : '상세주소';
  $address4_label = $c_territory_type['type_5'][4] ? $c_territory_type['type_5'][4] : '우편번호';
  $address5_label = $c_territory_type['type_5'][5] ? $c_territory_type['type_5'][5] : '이름';
} elseif ($tt_data['tt_type'] == '일반') {
  $address1_label = '길이름';
  $address2_label = '건물번호';
  $address3_label = $c_territory_type['type_1'][3] ? $c_territory_type['type_1'][3] : '상세주소';
  $address4_label = $c_territory_type['type_1'][4] ? $c_territory_type['type_1'][4] : '층';
  $address5_label = $c_territory_type['type_1'][5] ? $c_territory_type['type_1'][5] : '이름/호';
} elseif ($tt_data['tt_type'] == '격지') {
  $address1_label = '길이름';
  $address2_label = '건물번호';
  $address3_label = $c_territory_type['type_4'][3] ? $c_territory_type['type_4'][3] : '상세주소';
  $address4_label = $c_territory_type['type_4'][4] ? $c_territory_type['type_4'][4] : '층';
  $address5_label = $c_territory_type['type_4'][5] ? $c_territory_type['type_4'][5] : '이름/호';
} elseif ($tt_data['tt_type'] == '추가1') {
  $address1_label = '길이름';
  $address2_label = '건물번호';
  $address3_label = $c_territory_type['type_7'][3] ? $c_territory_type['type_7'][3] : '';
  $address4_label = $c_territory_type['type_7'][4] ? $c_territory_type['type_7'][4] : '';
  $address5_label = $c_territory_type['type_7'][5] ? $c_territory_type['type_7'][5] : '';
} elseif ($tt_data['tt_type'] == '추가2') {
  $address1_label = $c_territory_type['type_8'][1] ? $c_territory_type['type_8'][1] : '';
  $address2_label = $c_territory_type['type_8'][2] ? $c_territory_type['type_8'][2] : '';
  $address3_label = $c_territory_type['type_8'][3] ? $c_territory_type['type_8'][3] : '';
  $address4_label = '';
  $address5_label = '';
} else {
  $address1_label = '';
  $address2_label = '';
  $address3_label = '';
  $address4_label = '';
  $address5_label = '';
}

$sql = "SELECT * FROM " . HOUSE_TABLE . " WHERE tt_id = {$tt_id} ORDER BY h_order ASC";
$result = $mysqli->query($sql);
?>

<div class="container-fluid">
  <h6>[<?= $tt_data['tt_num'] ?>] <?= $tt_data['tt_name'] ?></h6>
  <form id="territory_house_form">
    <input type="hidden" name="work" value="territory_house">
    <!-- <input type="hidden" name="update_page" value="<?= $update_page ?>">
    <input type="hidden" name="update_wrap_id" value="<?= $update_wrap_id ?>"> -->
    <input type="hidden" name="tt_id" value="<?= $tt_id ?>">
    <div class="table-responsive">
      <table class="table mb-0 table-striped" style="min-width: 1300px;">
        <colgroup>
          <col style="width:40px;">
          <col style="width:60px;">
          <col style="width:90px;">
          <col style="">
          <col style="width:140px;">
          <col style="width:140px;">
          <col style="width:140px;">
          <col style="width:140px;">
          <col style="width:100px;">
          <col style="width:80px;">
          <col style="width:40px;">
        </colgroup>
        <thead class="thead-light">
          <tr>
            <th class="text-center"></th>
            <th class="text-center">기존순서</th>
            <th class="text-center">순서이동</th>
            <th class="text-center"><?= $address1_label ?></th>
            <th class="text-center"><?= $address2_label ?></th>
            <th class="text-center"><?= $address3_label ?></th>
            <th class="text-center"><?= $address4_label ?></th>
            <th class="text-center"><?= $address5_label ?></th>
            <th class="text-center">특이사항</th>
            <th class="text-center">삭제 &nbsp;<input type="checkbox" class="align-middle"
                onclick="if($(this).is(':checked')){ $('#territory_house_form tbody input[type=checkbox]').prop('checked', true); }else{ $('#territory_house_form input[type=checkbox]').prop('checked', false); }">
            </th>
            <th class="text-center"></th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <?php $h_id = $row['h_id']; ?>
            <tr>
              <td class="text-center dragHandle align-middle"><i class="bi bi-grip-vertical"></i></td>
              <td class="text-center align-middle"><?= $row['h_order'] ?></td>
              <td class="text-center align-middle">
                <button type="button" class="btn btn-outline-secondary btn-sm align-middle border-0 p-0"
                  onclick="$(this).parent().parent().prev().before($(this).parent().parent());"><i
                    class="bi bi-caret-up-fill h4"></i></button>
                <button type="button" class="btn btn-outline-secondary btn-sm align-middle border-0 p-0"
                  onclick="$(this).parent().parent().next().after($(this).parent().parent());"><i
                    class="bi bi-caret-down-fill h4"></i></button>
              </td>
              <td><input type="text" value="<?= $row['h_address1'] ?>" class="form-control"
                  name="territory_house[<?= $h_id; ?>][h_address1]"></td>
              <td><input type="text" value="<?= $row['h_address2'] ?>" class="form-control"
                  name="territory_house[<?= $h_id; ?>][h_address2]"></td>
              <td><input type="text" value="<?= $row['h_address3'] ?>" class="form-control"
                  name="territory_house[<?= $h_id; ?>][h_address3]"></td>
              <td><input type="text" value="<?= $row['h_address4'] ?>" class="form-control"
                  name="territory_house[<?= $h_id; ?>][h_address4]"></td>
              <td><input type="text" value="<?= $row['h_address5'] ?>" class="form-control"
                  name="territory_house[<?= $h_id; ?>][h_address5]"></td>
              <td class="text-center align-middle">
                <span
                  class="condition-chip<?= $row['h_condition'] ?>"><?= get_house_condition_text($row['h_condition']) ?></span>
              </td>
              <td class="text-center align-middle"><input type="checkbox" class="align-middle"
                  name="territory_house[<?= $h_id; ?>][delete]" value="delete"></td>
              <td class="text-center dragHandle align-middle"><i class="bi bi-grip-vertical"></i></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <div class="mt-4">
      <button type="button" class="btn btn-outline-primary float-left" onclick="territory_house_add();"><i
          class="bi bi-plus-circle-dotted"></i> 추가</button>
      <button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-save"></i> 저장</button>
    </div>
  </form>
</div>

<script type="text/javascript">
  $(document).ready(function () {
    $("#territory_house_form table").tableDnD({
      onDragClass: "myDrag",
      dragHandle: ".dragHandle"
    });

    $("#territory_house_form tr").hover(function () {
      $(this.cells[0]).addClass('showDragHandle');
      $(this.cells[10]).addClass('showDragHandle');
    }, function () {
      $(this.cells[0]).removeClass('showDragHandle');
      $(this.cells[10]).removeClass('showDragHandle');
    });


  });
</script>