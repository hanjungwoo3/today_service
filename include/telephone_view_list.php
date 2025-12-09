<?php include_once('../config.php');?>

<?php
if($tp_id){

  // tp_status 정보 가져오기
  $tp_status_sql = "SELECT tp_status FROM ".TELEPHONE_TABLE." WHERE tp_id = {$tp_id}";
  $tp_status_result = $mysqli->query($tp_status_sql);
  $tp_status_row = $tp_status_result->fetch_assoc();
  $is_absence_status = strpos($tp_status_row['tp_status'], 'absence') !== false;

  $sql = "SELECT * FROM ".TELEPHONE_HOUSE_TABLE." WHERE tp_id = {$tp_id} order by tph_order";
  $h_result = $mysqli->query($sql);

  while($r = $h_result->fetch_assoc()) {
    $tph_id = $r['tph_id'];
    $condition = get_house_condition_text($r['tph_condition']);?>
    <tr class="gubun-odd">
      <td style="word-wrap: break-word; word-break: break-word; overflow-wrap: break-word;">
        <div style="padding-right: 5px;"><?=$r['tph_name']?></div>
      </td>
      <td style="word-wrap: break-word; word-break: break-word; overflow-wrap: break-word;">
        <div style="padding-right: 5px;"><?=$r['tph_type']?></div>
      </td>
      <?php if($condition):?>
        <td colspan="2" rowspan="2" style="text-align:center; vertical-align: middle; min-width: 100px; width: 100px;">
          <div style="word-wrap: break-word; word-break: break-word; overflow-wrap: break-word; padding: 2px;">
            <span class="condition-chip<?=$r['tph_condition']?>" ><?=$condition?></span>
          </div>
        </td>
      <?php else:?>
        <td rowspan="2" class="text-center" style="vertical-align: middle; min-width: 50px; width: 50px; position: relative;">
          <label class="visit-check-label <?=($is_absence_status && $r['tph_visit_old']=='Y')?'disabled':'';?>" <?=($is_absence_status && $r['tph_visit_old']=='Y')?'title="이미 방문 완료된 세대입니다"':'';?>>
            <input type="checkbox" class="visit-check" name="tph_visit" value="Y" <?=($r['tph_visit']=='Y')?'checked="checked"':'';?> <?=($is_absence_status && $r['tph_visit_old']=='Y')?'disabled="disabled"':'';?> onclick="<?=($is_absence_status && $r['tph_visit_old']=='Y')?'return false;':'visit_check(\'telephone\','.$tph_id.',this);'?>">
            <span class="visit-check-mark <?=($is_absence_status && $r['tph_visit_old']=='Y')?'disabled':'';?>"></span>
          </label>
        </td>
        <td rowspan="2" class="text-center" style="vertical-align: middle; min-width: 50px; width: 50px; position: relative;">
          <label class="visit-check-label <?=($is_absence_status && $r['tph_visit_old']=='Y')?'disabled':'';?>" <?=($is_absence_status && $r['tph_visit_old']=='Y')?'title="이미 방문 완료된 세대입니다"':'';?>>
            <input type="checkbox" class="visit-check" name="tph_visit" value="N" <?=($r['tph_visit']=='N')?'checked="checked"':'';?> <?=($is_absence_status && $r['tph_visit_old']=='Y')?'disabled="disabled"':'';?> onclick="<?=($is_absence_status && $r['tph_visit_old']=='Y')?'return false;':'visit_check(\'telephone\','.$tph_id.',this);'?>">
            <span class="visit-check-mark <?=($is_absence_status && $r['tph_visit_old']=='Y')?'disabled':'';?>"></span>
          </label>
        </td>
      <?php endif;?>
      <td style="text-align:center; vertical-align: middle; min-width: 50px; width: 50px; position: relative;" rowspan="2">
        <?php if($condition):?>
          <button class="btn btn-outline-info btn-sm text-center" onclick="condition_work('telephone', 'view', <?=$r['tph_id']?>, '<?=$r['tph_number']?>');"><i class="bi bi-bell"></i></button>
        <?php else:?>
          <button class="btn btn-outline-secondary btn-sm text-center" onclick="condition_work('telephone', 'add', <?=$r['tph_id']?>, '<?=$r['tph_number']?>');"><i class="bi bi-pencil"></i></button>
        <?php endif;?>
      </td>
    </tr>
    <tr class="gubun-even">
      <td class="text-left" colspan="2" style="word-wrap: break-word; word-break: break-word; overflow-wrap: break-word;">
        <div style="padding-right: 5px;">
          <?php if($r['tph_address']) echo kakao_menu($r['tph_address']);?>
          <a href="tel:<?=$r['tph_number']?>" class="btn btn-sm btn-outline-info"><i class="bi bi-telephone"></i></a>
          <button type="button" class="btn btn-sm btn-outline-info" onclick="show_tel_info(<?=$tph_id;?>);"><i class="bi bi-info-circle"></i></button>
        </div>
      </td>
    </tr>
    <?php
  }
}
?>
