<?php include_once('../config.php');?>

<?php
if($tt_id):

  // tt_status 정보 가져오기
  $tt_status_sql = "SELECT tt_status FROM ".TERRITORY_TABLE." WHERE tt_id = {$tt_id}";
  $tt_status_result = $mysqli->query($tt_status_sql);
  $tt_status_row = $tt_status_result->fetch_assoc();
  $is_absence_status = strpos($tt_status_row['tt_status'], 'absence') !== false;

  $compare_address = '';
  $sql = "SELECT * FROM ".HOUSE_TABLE." WHERE tt_id = {$tt_id} order by h_order";
  $result = $mysqli->query($sql);
  while($r = $result->fetch_assoc()):

    $condition = get_house_condition_text($r['h_condition']);
    if($compare_address) $new_compare_address = ($tt_type == '아파트' || $tt_type == '빌라' || $tt_type == '추가2')?$r['h_address1'].$r['h_address2']:$r['h_address1'].$r['h_address2'].$r['h_address3'];
    ?>

    <?php if($compare_address != $new_compare_address):?>
    <tr class="bg-light border-bottom">
      <td colspan="6">&nbsp;</td>
    </tr>
    <?php endif;?>
    <tr class="gubun-odd">
      <?php if($tt_type == '아파트' || $tt_type == '빌라' || $tt_type == '추가2'): ?>
      <td colspan="3" class="pb-0"><?=$r['h_address1']?></td>
      <?php else: ?>
      <td colspan="3" class="pb-0">
        <?=$r['h_address1']?> <?=$r['h_address2']?> <?php if($r['h_address3']) echo '('.$r['h_address3'].')';?>
      </td>
      <?php endif; ?>
      <?php if($condition):?>
        <td colspan="<?=$tt_type=='편지'?'1':'2'?>" rowspan="2" style="text-align:center;" >
          <span class="condition-chip<?=$r['h_condition']?>" ><?=$condition?></span>
        </td>
      <?php else:?>
        <td rowspan="2" class="text-center">
          <label class="visit-check-label <?=($is_absence_status && $r['h_visit_old']=='Y')?'disabled':'';?>" <?=($is_absence_status && $r['h_visit_old']=='Y')?'title="이미 방문 완료된 세대입니다"':'';?>>
            <input type="checkbox" class="visit-check" name="h_visit" value="Y" <?=($r['h_visit']=='Y')?'checked="checked"':'';?> <?=($is_absence_status && $r['h_visit_old']=='Y')?'disabled="disabled"':'';?> onclick="<?=($is_absence_status && $r['h_visit_old']=='Y')?'return false;':'visit_check(\'territory\','.$r['h_id'].',this);'?>">
            <span class="visit-check-mark <?=$tt_type=='편지'?'letter':''?> <?=($is_absence_status && $r['h_visit_old']=='Y')?'disabled':'';?>"></span>
          </label>
        </td>
        <?php if($tt_type != '편지'):?>
        <td rowspan="2" class="text-center">
          <label class="visit-check-label <?=($is_absence_status && $r['h_visit_old']=='Y')?'disabled':'';?>" <?=($is_absence_status && $r['h_visit_old']=='Y')?'title="이미 방문 완료된 세대입니다"':'';?>>
            <input type="checkbox" class="visit-check" name="h_visit" value="N" <?=($r['h_visit']=='N')?'checked="checked"':'';?> <?=($is_absence_status && $r['h_visit_old']=='Y')?'disabled="disabled"':'';?> onclick="<?=($is_absence_status && $r['h_visit_old']=='Y')?'return false;':'visit_check(\'territory\','.$r['h_id'].',this);'?>">
            <span class="visit-check-mark <?=($is_absence_status && $r['h_visit_old']=='Y')?'disabled':'';?>"></span>
          </label>
        </td>
        <?php endif; ?>
      <?php endif; ?>
      <td style="text-align:center;"  rowspan="2">
        <?php if($condition):?>
        <button class="btn btn-outline-info btn-sm text-center" onclick="condition_work('territory', 'view', <?=$r['h_id']?>, '<?=addslashes($r['h_address1'].' '.$r['h_address2'].' '.$r['h_address3'])?>');"><i class="bi bi-bell"></i></button>
        <?php else:?>
        <button class="btn btn-outline-secondary btn-sm text-center" onclick="condition_work('territory', 'add', <?=$r['h_id']?>, '<?=addslashes($r['h_address1'].' '.$r['h_address2'].' '.$r['h_address3'])?>');"><i class="bi bi-pencil"></i></button>
      <?php endif; ?>
      </td>
    </tr>
    <tr class="gubun-even">
      <?php if($tt_type == '아파트' || $tt_type == '빌라' || $tt_type == '추가2'): ?>
        <td>&nbsp;</td>
        <td><?=$r['h_address2']?></td>
        <td><?=$r['h_address3']?></td>
      <?php else: ?>
        <td class="text-left">
          <?php if($r['h_address1']) echo kakao_menu($r['h_address1'].' '.$r['h_address2']);?>
        </td>
        <td><?=$r['h_address4']?></td>
        <td><?=$r['h_address5']?></td>
      <?php endif; ?>
    </tr>

    <?php $compare_address = ($tt_type == '아파트' || $tt_type == '빌라' || $tt_type == '추가2')?$r['h_address1'].$r['h_address2']:$r['h_address1'].$r['h_address2'].$r['h_address3'];?>
  <?php endwhile;?>
<?php endif;?>
