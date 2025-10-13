<?php include_once('../header.php');?>
<?php check_accessible('admin');?>

<?php $ms_options = get_meeting_option();?>
<?php $c_meeting_schedule_type_use = unserialize(MEETING_SCHEDULE_TYPE_USE); ?>
<?php $sql = "SELECT dp_id, dp_name, dp_address, dp_count, d_ms_all, ms_id FROM ".DISPLAY_PLACE_TABLE." ORDER BY dp_name ASC";?>
<?php $result = $mysqli->query($sql);?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">관리자 <span class="d-xl-none">전시대 장소 관리</span></h1>
  <?php echo header_menu('admin','전시대 장소 관리'); ?>
</header>

<?php echo footer_menu('관리자');?>

<div id="container" class="container-fluid">
	<form id="admin_display_assign" action="" method="post">
		<input type="hidden" name="work" value="assign">
		<div style="overflow:hidden;" class="mb-4">
			<div class="row">
				<div class="col-12 mb-3">선택한 전시대 장소들을</div>
			</div>
			<div class="row">
				<div class="col-12 align-self-center">
					<select class="form-control" name="ms_id">
						<option value="0">분배되지 않음</option>
						<optgroup label="모임 형태">
							<option value="all_3">전체</option>
							<option value="all_1"><?=get_meeting_schedule_type_text(1)?></option>
							<option value="all_2"><?=get_meeting_schedule_type_text(2)?></option>
							<option value="all_4"><?php if(empty($c_meeting_schedule_type_use[3])){ echo '[미사용] '; } ?><?=get_meeting_schedule_type_text(3)?></option>
							<option value="all_5"><?php if(empty($c_meeting_schedule_type_use[4])){ echo '[미사용] '; } ?><?=get_meeting_schedule_type_text(4)?></option>
							<option value="all_6"><?php if(empty($c_meeting_schedule_type_use[5])){ echo '[미사용] '; } ?><?=get_meeting_schedule_type_text(5)?></option>
							<option value="all_7"><?php if(empty($c_meeting_schedule_type_use[6])){ echo '[미사용] '; } ?><?=get_meeting_schedule_type_text(6)?></option>
						</optgroup>
						<optgroup label="모임 계획">
							<?php echo $ms_options;?>
						</optgroup>
					</select>
					<div class="mt-2">모임 계획 으로 <button type="submit" class="btn btn-outline-secondary" >분배</button></div>
				</div>
			</div>
		</div>
	</form>

	<form id="admin_display_form" class="multiple_add_section" data-url="display_place">
		<div class="table-responsive">
			<table class="table mb-0" style="min-width: 700px;">
				<colgroup>
					<col style="width:10px;">
					<col style="width:200px;">
					<col>
          <col style="width:80px;">
					<col style="width:140px;">
					<col style="width:120px;">
				</colgroup>
				<thead class="thead-light text-center">
					<tr>
						<th class="text-center align-middle"><input id="all_check" type="checkbox" onclick="if($(this).is(':checked')){ $('#admin_display_form input[type=checkbox]:not(#all_check)').prop('checked', true); $('#admin_display_form tbody tr').addClass('checked'); }else{ $('#admin_display_form input[type=checkbox]:not(#all_check)').prop('checked', false); $('#admin_display_form tbody tr').removeClass('checked'); }"></th>
						<th>장소 이름</th>
						<th>주소</th>
						<th>팀</th>
						<th>분배 상태<br/><small>요일 (모임 계획 ID)</small></th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
				<?php if($result->num_rows > 0):?>
          <?php while($row = $result->fetch_assoc()):?>
          <?php $dp_id = $row['dp_id'];?>
					<tr>
						<td class="text-center align-middle">
							<input type="checkbox" name="dp_id[]" value="<?=$dp_id?>" onchange="if($(this).is(':checked')){ $(this).parent().parent().addClass('checked'); }else{$(this).parent().parent().removeClass('checked');}">
						</td>
						<td>
							<input type="text" class="form-control" name="display_place[u][<?=$dp_id?>][name]" value="<?=$row['dp_name']?>" required>
						</td>
						<td>
              <input type="text" class="form-control" name="display_place[u][<?=$dp_id?>][address]" value="<?=$row['dp_address']?>">
						</td>
            <td>
              <input type="number" class="form-control" name="display_place[u][<?=$dp_id?>][count]" value="<?=$row['dp_count']?>" min=1 required>
						</td>
						<td class="text-center align-middle">
							<?php 
							$ms_id = isset($row['ms_id']) ? $row['ms_id'] : null;

							if(!empty($row['d_ms_all'])) {
								// 모임 형태별 전체 분배된 경우
								switch ($row['d_ms_all']) {
									case '1': $ms_id_text = get_meeting_schedule_type_text(1); break;
									case '2': $ms_id_text = get_meeting_schedule_type_text(2); break;
									case '3': $ms_id_text = '전체'; break;
									case '4': $ms_id_text = get_meeting_schedule_type_text(3); break;
									case '5': $ms_id_text = get_meeting_schedule_type_text(4); break;
									case '6': $ms_id_text = get_meeting_schedule_type_text(5); break;
									case '7': $ms_id_text = get_meeting_schedule_type_text(6); break;
									default : $ms_id_text = $row['ms_id']?get_week_text($row['ms_week']).' '.'('.$row['ms_id'].')':'';
								}
								echo $ms_id_text;

							}elseif($ms_id && $ms_id > 0) {
								// 개별 모임 계획에 분배된 경우
								$ms_sql = "SELECT ms.ms_week, ms.ms_time, ms.ms_type FROM ".MEETING_SCHEDULE_TABLE." ms WHERE ms.ms_id = {$ms_id}";
								$ms_result = $mysqli->query($ms_sql);
								if($ms_result->num_rows > 0) {
									$ms_row = $ms_result->fetch_assoc();
									$week_text = get_week_text($ms_row['ms_week']);
									echo $week_text.' ('.$ms_id.')';
								}
							}
							?>
						</td>
						<td>
							<button id="data_delete" del_id="<?=$dp_id?>" type="button" class="btn btn-outline-danger">
								<i class="bi bi-trash"></i> 삭제
							</button>
						</td>
					</tr>
          <?php endwhile;?>
        <?php endif;?>
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





<?php include_once('../footer.php'); ?>
