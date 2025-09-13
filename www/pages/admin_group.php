<?php include_once('../header.php');?>
<?php check_accessible('admin'); ?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">관리자 <span class="d-xl-none">집단 관리</span></h1>
  <?php echo header_menu('admin','집단 관리'); ?>
</header>

<?php echo footer_menu('관리자');?>

<div id="container" class="container-fluid">
	<form id="admin_group_form" class="multiple_add_section" data-url="group">
		<div class="table-responsive">
			<table class="table mb-0" style="min-width: 300px;">
				<colgroup>
          <col style="width:50px;">
					<col>
					<col style="width:120px;">
				</colgroup>
				<thead class="thead-light text-center">
					<tr>
            <th>ID</th>
						<th>집단 이름</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody class="text-center">
				<?php foreach(get_group_data_all() as $key => $value): ?>
  				<tr>
            <td class="align-middle">
  						<?=$key?>
  					</td>
  					<td>
  						<input type="text" class="form-control" name="group[u][<?=$key?>][name]" value="<?=$value?>" required>
  					</td>
  					<td>
  						<button id="data_delete" del_id="<?=$key?>" type="button" class="btn btn-outline-danger">
  							<i class="bi bi-trash"></i> 삭제
  						</button>
  					</td>
  				</tr>
  			<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="clearfix mt-4">
			<button id="data_add" type="button" class="btn btn-outline-primary" work="edit">
				<i class="bi bi-plus-circle-dotted"></i> 추가
			</button>
			<button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-save"></i> 저장</button>
		</div>
	</form>
</div>

<?php include_once('../footer.php'); ?>
