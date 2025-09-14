<?php include_once('../header.php');?>

<?php
$mb_id = mb_id();

$sql = "SELECT tt_id, tt_type, tt_num, tt_status, tt_name, tt_mb_date, tt_assigned, tt_assigned_group FROM ".TERRITORY_TABLE." WHERE mb_id = '{$mb_id}'  ORDER BY tt_mb_date DESC, tt_num+0 ASC, tt_num ASC";
$tt_result = $mysqli->query($sql);

$sql = "SELECT tp_id, tp_mb_date, tp_name, tp_num, tp_status, tp_assigned, tp_assigned_group FROM ".TELEPHONE_TABLE." WHERE mb_id = '{$mb_id}'  ORDER BY tp_mb_date DESC, tp_num+0 ASC, tp_num ASC";
$tp_result = $mysqli->query($sql);
?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">봉사자 <span class="d-xl-none">개인 구역</span></h1>
  <?php echo header_menu('minister','개인 구역'); ?>
</header>

<?php echo footer_menu('봉사자'); ?>

<div id="container" class="container-fluid">
<?php if($tt_result->num_rows || $tp_result->num_rows):?>
  <?php while ($row = $tt_result->fetch_assoc()):
    $tt_id = $row['tt_id'];
    $color_type = $row['tt_type']=='편지'?'badge-info':'badge-success';
    $territory_progress = get_territory_progress($tt_id);
    if ($territory_progress['total'] != 0) {
        $progress = floor((($territory_progress['visit']+$territory_progress['absence'])/$territory_progress['total'])*100);
    } else {
        $progress = 0;
    }
    $assigned_group_arr = get_assigned_group_name($row['tt_assigned'],$row['tt_assigned_group']);
    $assigned_group = (is_array($assigned_group_arr) == 1)?implode(' <span class="mx-1">|</span> ', $assigned_group_arr):$assigned_group_arr;
    
    $all_past_records = get_all_past_records('territory',$tt_id);
    ?>
    <div class="list-group mb-2">
      <div class="list-group-item d-flex flex-nowrap justify-content-between p-2 border-light">
        <div class="flex-grow-1 pr-2">
          <div class="mb-1">
            <span class="badge badge-pill <?=$color_type?> badge-outline px-1 align-middle"><?=$row['tt_num']?> · <?=get_type_text($row['tt_type'])?></span>
            <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle">
              <?=$row['tt_status'] == 'absence' || $row['tt_status'] == 'absence_reassign'?'<i class="bi bi-person-fill-slash"></i> 부재':'<i class="bi bi-people-fill"></i> 전체'?>
            
              <?php 
              // 방문 기록이 있는지 확인
              if(!empty($all_past_records)): ?>
                <?php 
                // 새로운 progress 키 사용
                if($all_past_records[0]['progress'] == 'completed'): ?>
                  <span class="text-success">완료</span>
                <?php 
                // 진행 중
                elseif($all_past_records[0]['progress'] == 'in_progress'): ?>
                  <span class="text-warning">진행 중</span>
                <?php endif; ?>
              <?php endif; ?>
            </span>
          </div>
          <div>
            <span class="align-middle"><?=$row['tt_name']?></span>
          </div>

          <div class="progress d-inline-flex align-middle w-100 mt-n1" style="height: 5px;">
            <div class="progress-bar <?= $progress == 100 ? 'bg-success' : 'bg-warning'?>" role="progressbar" style="width:<?=$progress.'%';?>" aria-valuenow="<?=$progress;?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>

          <div class="mt-n2">
            <small class="text-secondary d-inline-block">
              <?php if($row['tt_type'] == '편지'): ?>
                전체 <?=$territory_progress['total']?> · 발송 <?=$territory_progress['visit']?> · 남은 집 <?=$territory_progress['total'] - $territory_progress['visit']?>
              <?php else: ?>
                전체 <?=$territory_progress['total']?> · 만남 <?=$territory_progress['visit']?> · 부재 <?=$territory_progress['absence']?> · 남은 집 <?=$territory_progress['total'] - $territory_progress['visit'] - $territory_progress['absence']?>
              <?php endif; ?>
            </small>
          </div>

          <?php if($assigned_group) echo '<div class="assigned_group_name mt-1">'.$assigned_group.'</div>'; ?>
          
          <?php if(!empty_date($row['tt_mb_date'])):?>
            <div class="mt-1">
              <small class="text-secondary"><?=get_datetime_text($row['tt_mb_date'])?> 배정</small>
            </div>
          <?php endif;?>
        </div>
        <div class="align-self-center flex-shrink-0">
          <button type="button" class="btn btn-outline-secondary" onclick="open_territory_view(<?=$tt_id?>,'start')">시작</button>
        </div>
      </div>
    </div>
  <?php endwhile;?>

  <?php while ($row = $tp_result->fetch_assoc()) :
    $tp_id = $row['tp_id'];
    $telephone_progress = get_telephone_progress($tp_id);
    if ($telephone_progress['total'] != 0) {
        $progress = floor((($telephone_progress['visit']+$telephone_progress['absence'])/$telephone_progress['total'])*100);
    } else {
        $progress = 0;
    }
    $tp_assigned_group_arr = get_assigned_group_name($row['tp_assigned'],$row['tp_assigned_group']);
    $tp_assigned_group = (is_array($tp_assigned_group_arr) == 1)?implode(' <span class="mx-1">|</span> ', $tp_assigned_group_arr):$tp_assigned_group_arr;
    
    $all_past_records = get_all_past_records('telephone',$tp_id);
    ?>
    <div class="list-group mb-2">
      <div class="list-group-item d-flex flex-nowrap justify-content-between p-2 border-light">
        <div class="flex-grow-1 pr-2">
          <div class="mb-1">
            <span class="badge badge-pill badge-warning badge-outline px-1 align-middle"><?=$row['tp_num']?> · 전화</span>
            <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle">
              <?=$row['tp_status'] == 'absence' || $row['tp_status'] == 'absence_reassign'?'<i class="bi bi-person-fill-slash"></i> 부재':'<i class="bi bi-people-fill"></i> 전체'?>
            
              <?php 
              // 방문 기록이 있는지 확인
              if(!empty($all_past_records)): ?>
                <?php 
                // 새로운 progress 키 사용
                if($all_past_records[0]['progress'] == 'completed'): ?>
                  <span class="text-success">완료</span>
                <?php 
                // 진행 중
                elseif($all_past_records[0]['progress'] == 'in_progress'): ?>
                  <span class="text-warning">진행 중</span>
                <?php endif; ?>
              <?php endif; ?>
            </span>
          </div>
          <div>
            <span class="align-middle"><?=$row['tp_name']?></span>
          </div>

          <div class="progress d-inline-flex align-middle w-100 mt-n1" style="height: 5px;">
            <div class="progress-bar <?= $progress == 100 ? 'bg-success' : 'bg-warning'?>" role="progressbar" style="width:<?=$progress.'%';?>" aria-valuenow="<?=$progress;?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>

          <div class="mt-n2">
            <small class="text-secondary d-inline-block">
              전체 <?=$telephone_progress['total']?> · 만남 <?=$telephone_progress['visit']?> · 부재 <?=$telephone_progress['absence']?> · 남은 집 <?=$telephone_progress['total'] - $telephone_progress['visit'] - $telephone_progress['absence']?>
            </small>
          </div>

          <?php if($tp_assigned_group) echo '<div class="assigned_group_name mt-1">'.$tp_assigned_group.'</div>'; ?>
          
          <?php if(!empty_date($row['tp_mb_date'])):?>
            <div class="mt-1">
              <small class="text-secondary"><?=get_datetime_text($row['tp_mb_date'])?> 배정</small>
            </div>
          <?php endif;?>
        </div>
        <div class="align-self-center flex-shrink-0">
          <button type="button" class="btn btn-outline-secondary" onclick="open_telephone_view(<?=$tp_id?>,'start')">시작</button>
        </div>
      </div>
    </div>
  <?php endwhile;?>
<?php else:?>
  <div class="text-center align-middle p-5 text-secondary" >개인 구역이 없습니다</div>
<?php endif;?>
</div>

<?php include_once('../footer.php');?>
