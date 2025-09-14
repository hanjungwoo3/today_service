<?php include_once('../config.php');?>

<?php
$ma_id = isset($ma_id)?$ma_id:0;
$week_array = array('월요일','화요일','수요일','목요일','금요일','토요일','일요일', '미배정');

for($a=1; $a<=8; $a++){
  $sql = "SELECT ms_id, ms_time, ms_type, mp_name, g_name
          FROM ".MEETING_SCHEDULE_TABLE." ms LEFT JOIN ".MEETING_PLACE_TABLE." mp ON ms.mp_id = mp.mp_id LEFT JOIN ".GROUP_TABLE." g ON ms.g_id = g.g_id
          WHERE ma_id = '{$ma_id}' AND ms_week = {$a} ORDER BY ms_time, g_name, mp_name";
  $result = $mysqli->query($sql);
  ?>

  <h5><?php echo $week_array[$a-1]?></h5>
  <div class="table-responsive mb-4">
    <table class="table mb-0" style="min-width: 300px;">
      <colgroup>
        <col style="width:50px;">
        <col>
        <col style="width:90px;">
        <col style="width:70px;">
      </colgroup>
      <thead class="thead-light">
        <tr>
          <th class="text-center">ID</th>
          <th class="fixed"></th>
          <th class="text-center">모임 형태</th>
          <th>&nbsp;</th>
        </tr>
      </thead>
      <tbody>
      <?php if($result->num_rows > 0):?>
        <?php while($row = $result->fetch_assoc()):?>
          <?php $ms_id = $row['ms_id'];?>
          <tr>
            <td class="align-middle text-center"><?=$row['ms_id']?></td>
            <td class="align-middle"><?php echo get_meeting_data_text($row['ms_time'], $row['g_name'], $row['mp_name']);?></td>
            <td class="text-center align-middle"><?=get_meeting_schedule_type_text($row['ms_type'])?></td>
            <td class="text-center align-middle">
              <div class="dropdown">
                <button class="btn btn-outline-secondary" type="button" id="ex<?=$ms_id?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="bi bi-three-dots-vertical "></i>
                </button>
                <div class="dropdown-menu dropdown-menu-left" aria-labelledby="ex<?=$ms_id?>" >
                  <button class="dropdown-item" type="button" onclick="meeting_work('edit',<?=$ms_id?>, <?=$ma_id?>);">수정</button>
                  <button class="dropdown-item" type="button" onclick="meeting_work('del',<?=$ms_id?>, <?=$ma_id?>);">삭제</button>
                </div>
              </div>
            </td>
          </tr>
        <?php endwhile;?>
      <?php else:?>
        <tr>
          <td colspan="4" class="text-center align-middle text-secondary">모임 계획이 존재하지 않습니다</td>
        </tr>
      <?php endif;?>
      </body>
    </table>
  </div>
<?php
}
?>
