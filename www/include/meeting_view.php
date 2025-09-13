<?php include_once('../config.php');?>

<?php
$i = 0;
$week = date('N', strtotime($s_date));
$ma_id = get_addschedule_id($s_date);

// 당일 모임장소 출력
$sql = "SELECT 
ms.ms_id, 
COALESCE(m.ms_time,ms.ms_time) AS ms_time,  
COALESCE(m.mp_name,mp.mp_name) AS mp_name, 
mp.mp_address, 
COALESCE(m.ms_type,ms.ms_type) AS ms_type, 
g.g_name, 
ms.ms_guide, 
ms.ms_guide2 
FROM 
".MEETING_SCHEDULE_TABLE." ms
LEFT JOIN 
".MEETING_PLACE_TABLE." mp ON ms.mp_id = mp.mp_id
LEFT JOIN 
".GROUP_TABLE." g ON ms.g_id = g.g_id
LEFT JOIN 
".MEETING_TABLE." m ON ms.ms_id = m.ms_id 
AND m.m_date = '{$s_date}'
WHERE (ms.ma_id IN({$ma_id}) OR ms.ma_id = '0') AND ms.ms_week = '{$week}'
        ORDER BY ms.ms_time, g.g_name, mp.mp_name";


$result = $mysqli->query($sql);

if($result->num_rows > 0):
  while($row = $result->fetch_assoc()):
    $m = get_meeting_data(get_meeting_id($s_date, $row['ms_id']));
    if($m['m_cancle'] != 2):?>
      <div class="card bg-light mb-2">
        <div class="card-header p-2">
          <div class=" d-flex flex-nowrap justify-content-between">
            <div>
              <?=get_meeting_data_text($row['ms_time'], $row['g_name'], $row['mp_name'])?>
              <?php if($m['m_cancle'] == 1) echo '<span class="badge badge-pill badge-light text-danger align-middle">취소됨</span>';?>
            </div>
            <div class="flex-shrink-0 align-self-center pl-2"><?=get_meeting_schedule_type_text($row['ms_type'])?></div>
          </div>
        </div>
        <div class="card-body py-0 px-1">
          <?php if($m['m_cancle'] == 1 && !empty($m['m_cancle_reason'])): ?>
          <div class="alert alert-danger mt-1 mb-0" role="alert">
            <?=$m['m_cancle_reason']?>
          </div>
          <?php endif; ?>
          <div class="my-2">
          <?php
          if(!empty($m['m_guide'])){
            foreach (get_guide_data($m['m_guide']) as $value) echo '<a class="btn btn-outline-primary btn-sm m-1" href="tel:'.$value['hp'].'"><i class="bi bi-telephone"></i> '.$value['name'].'</a>';
          }else{
            if($m['ms_guide']) foreach (get_guide_data($m['ms_guide']) as $value) echo '<a class="btn btn-outline-primary btn-sm m-1" href="tel:'.$value['hp'].'"><i class="bi bi-telephone"></i> '.$value['name'].'</a>';
            if($m['ms_guide2']) foreach (get_guide_data($m['ms_guide2']) as $value) echo '<a class="btn btn-outline-secondary btn-sm m-1" href="tel:'.$value['hp'].'"><i class="bi bi-telephone"></i> '.$value['name'].'</a>';
          }
          ?>
          </div>
          <div class="my-2 mx-1">
            <?=$row['mp_address']?>
            <button class="btn btn-sm btn-outline-secondary" onclick="kakao_navi('<?=DEFAULT_ADDRESS.' '.$row['mp_address']?>','<?=$row['mp_name']?>');">
              <i class="bi bi-cursor"></i> 길찾기
            </button>
          </div>
        </div>
      </div>
      <?php
      $i++;
    endif;
  endwhile;?>
  <?php if($i == 0) echo '<div class="text-center align-middle p-5 text-secondary">오늘 마련된 봉사모임이 없습니다</div>';?>
<?php else:?>
  <div class="text-center align-middle p-5 text-secondary">오늘 마련된 봉사모임이 없습니다</div>
<?php endif;?>
