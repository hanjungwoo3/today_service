<?php include_once('../config.php');?>

<?php $sql_array = array('ma_auto = 1', 'ma_auto = 0');?>
<?php $sql_oreder_array = array('ma_week DESC, ma_weekday DESC', 'ma_date DESC, ma_date2');?>

<div class="table-responsive">
  <table class="table mb-0" style="min-width: 330px;">
    <colgroup>
      <col style="width:33px;">
      <col>
      <col style="width:70px;">
    </colgroup>
    <tbody>
    <?php for($a=0; $a < 2; $a++):?>

      <?php $ma_sql = "SELECT * FROM ".MEETING_ADD_TABLE." WHERE ".$sql_array[$a]." ORDER BY ".$sql_oreder_array[$a].", ma_title";?>
      <?php $ma_result = $mysqli->query($ma_sql);?>
      <?php if($ma_result->num_rows > 0):?>
        <?php while($mar = $ma_result->fetch_assoc()):?>
        <?php $ma_id = $mar['ma_id'];?>
        <tr>
          <td class="align-middle px-2"><i class="bi bi-circle-fill" style="color:<?=$mar['ma_color']?>;"></i></td>
          <td class="align-middle px-0">
            <div><?=$mar['ma_title']?> <span class="badge badge-secondary"><?=get_meeting_schedule_count($ma_id)?></span></div>
            <div>
              <small class="text-secondary">
                <?=get_admin_addschedule_date($mar['ma_auto'], $mar['ma_switch'], $mar['ma_date'], $mar['ma_date2'], $mar['ma_week'], $mar['ma_weekday']);?>
              </small>
            </div>
          </td>
          <td class="align-middle text-center px-2">
            <div class="dropdown">
              <button class="btn btn-outline-secondary" type="button" id="ex<?=$ma_id?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-three-dots-vertical "></i>
              </button>
              <div class="dropdown-menu dropdown-menu-left" aria-labelledby="ex<?=$ma_id?>" >
                <button class="dropdown-item" type="button" onclick="addschedule_work('edit',<?=$ma_id?>);">수정</button>
                <button class="dropdown-item" type="button" onclick="addschedule_work('del',<?=$ma_id?>);">삭제</button>
              </div>
            </div>
          </td>
        </tr>
        <?php endwhile;?>
      <?php endif;?>

    <?php endfor;?>
    </tbody>
  </table>
</div>
