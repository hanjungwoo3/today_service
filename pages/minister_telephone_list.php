<?php include_once('../config.php');?>

<?php
$c_minister_assign_expiration = MINISTER_TELEPHONE_ASSIGN_EXPIRATION?MINISTER_TELEPHONE_ASSIGN_EXPIRATION:'7';
$mb_id = mb_id();
$date = date("Y-m-d", strtotime("-".$c_minister_assign_expiration." days"));
?>
<nav class="navbar navbar-light bg-light mb-4">
  <small class="mb-0 text-secondary">최근 <?=$c_minister_assign_expiration?>일 내에 배정받은 구역이 보입니다</small>
</nav>
<?php
// 배정된 호별 구역
$sql = "SELECT tp_id, tp_name, tp_assigned, tp_assigned_group, tp_assigned_date, tp_num, tp_status
        FROM ".TELEPHONE_TABLE." WHERE FIND_IN_SET({$mb_id},tp_assigned) AND mb_id = 0 AND tp_assigned_date > '{$date}'
        ORDER BY tp_assigned_date DESC, tp_num+0 ASC, tp_num ASC";
$result = $mysqli->query($sql);
if($result->num_rows > 0){
  while ($row=$result->fetch_assoc()) {
    $tp_id = $row['tp_id'];
    $telephone_progress = get_telephone_progress($tp_id);
    if ($telephone_progress['total'] != 0) {
        $progress = floor((($telephone_progress['visit']+$telephone_progress['absence'])/$telephone_progress['total'])*100);
    } else {
        $progress = 0; // 또는 0 대신 다른 기본값을 설정할 수 있습니다.
    }
    $tp_assigned_group = '';
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
           <span class=" align-middle"><?=$row['tp_name']?></span>
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
         
         <?php if(!empty_date($row['tp_assigned_date'])):?>
           <div class="mt-1">
             <small class="text-secondary"><?=get_datetime_text($row['tp_assigned_date'])?> 배정</small>
           </div>
         <?php endif;?>
       </div>
       <div class="align-self-center flex-shrink-0">
         <button type="button" class="btn btn-outline-secondary" onclick="open_telephone_view(<?=$tp_id?>,'start')">시작</button>
       </div>
     </div>
    </div>
  <?php
  }
}else{
  echo '<div class="text-center align-middle p-5 text-secondary" >배정받은 구역이 없습니다</div>';
}
?>
