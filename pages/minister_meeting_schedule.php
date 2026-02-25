<?php
include_once('../header.php');

// 봉사 집단 구하기
$group_data = get_group_data_all();
if (!empty($group_data)) {
	$group_data_max_key = max(array_keys($group_data));

	// g_id로 멤버 그룹화
	$g_members = array();
	$sql = "SELECT g_id, mb_name FROM " . MEMBER_TABLE . " WHERE mb_moveout_date = '0000-00-00' ORDER BY mb_name ASC";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$g_members[$row['g_id']][] = $row['mb_name'];
		}
	}
	// $g_members 배열에서 가장 긴 배열의 길이를 찾기
	$max_rows = 0;
	foreach ($g_members as $members) {
		$max_rows = max($max_rows, count($members));
	}
}
?>

<header class="navbar navbar-expand-xl fixed-top header">
	<h1 class="text-white mb-0  navbar-brand">봉사자 <span class="d-xl-none">회중 봉사</span></h1>
	<?php echo header_menu('minister', '회중 봉사'); ?>
</header>

<?php echo footer_menu('봉사자'); ?>

<div id="container" class="container-fluid minister_meeting_schedule">

	<?php if (!empty($group_data)): ?>

		<h5 class="border-bottom mt-4 mb-3 pb-2">집단</h5>

		<div class="table-responsive">

			<table class="table table-bordered">
				<colgroup>
					<?php foreach ($group_data as $g_id => $name): ?>
						<col style="width:150px">
					<?php endforeach; ?>
				</colgroup>
				<thead class="thead-light">
					<tr class="text-center">
						<?php foreach ($group_data as $g_id => $name): ?>
							<th scope="col" colspan=""><?= $name ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody class="text-center">
					<?php for ($i = 0; $i < $max_rows; $i++): ?>
						<tr>
							<?php foreach ($group_data as $g_id => $name): ?>
								<td class="align-middle ">
									<?php echo isset($g_members[$g_id][$i]) ? $g_members[$g_id][$i] : ''; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endfor; ?>
				</tbody>
			</table>

		</div>

	<?php endif; ?>

	<h5 class="border-bottom mt-4 mb-3 pb-2">구역</h5>
	<button type="button" class="btn btn-outline-success mb-4" onclick="statistics_map_view('minister');"><i
			class="bi bi-geo-alt"></i><span class="align-middle"> 구역전체지도</span></button>

</div>

<?php include_once('../footer.php'); ?>