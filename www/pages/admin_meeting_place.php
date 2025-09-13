<?php include_once('../header.php');?>
<?php check_accessible('admin');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">관리자 <span class="d-xl-none">모임 장소 관리</span></h1>
  <?php echo header_menu('admin','모임 장소 관리'); ?>
</header>

<?php echo footer_menu('관리자');?>

<div id="container" class="container-fluid">

  <form id="admin_meeting_place_form" class="multiple_add_section" data-url="meeting_place">
    <div class="table-responsive">
      <table class="table mb-0" style="min-width: 600px;">
        <colgroup>
          <col style="width:250px;">
          <col>
          <col style="width:120px;">
        </colgroup>
        <thead class="thead-light text-center">
          <tr>
            <th>장소 이름</th>
            <th>주소</th>
            <th>&nbsp;</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach (get_meeting_place_data_all() as $id => $mp):?>
          <tr>
            <td>
              <input type="text" class="form-control" name="meeting_place[u][<?=$id?>][name]" value="<?=$mp['mp_name']?>" required>
            </td>
            <td>
              <input type="text" class="form-control" name="meeting_place[u][<?=$id?>][address]" value="<?=$mp['mp_address']?>">
            </td>
            <td>
              <?php echo delete_meeting_place_data($id);?>
            </td>
          </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>
    <div class="clearfix mt-4">
			<button id="data_add" type="button" class="btn btn-outline-primary">
				<i class="bi bi-plus-circle-dotted"></i> 추가
			</button>
			<button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-save"></i> 저장</button>
    </div>
  </form>

</div>

<?php include_once('../footer.php');?>
