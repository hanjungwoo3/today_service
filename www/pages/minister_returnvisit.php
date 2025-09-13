<?php include_once('../header.php');?>

<?php
if(RETURNVISIT_USE != 'use'){
  echo '<script> location.href="'.BASE_PATH.'/"; </script>';
  exit;
}
?>

<?php
$c_territory_type = unserialize(TERRITORY_TYPE);
$mb_id = mb_id();

$sql = "SELECT * FROM ".HOUSE_TABLE." h INNER JOIN ".TERRITORY_TABLE." t ON h.tt_id = t.tt_id WHERE h.mb_id = '{$mb_id}' ORDER BY h.h_address1, h.h_address2 desc";
$result = $mysqli->query($sql);

$sql = "SELECT * FROM ".TELEPHONE_HOUSE_TABLE." tph INNER JOIN ".TELEPHONE_TABLE." tp ON tph.tp_id = tp.tp_id WHERE tph.mb_id = '{$mb_id}'";
$result_t = $mysqli->query($sql);
?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">봉사자 <span class="d-xl-none">재방문</span></h1>
  <?php echo header_menu('minister','재방문'); ?>
</header>

<?php echo footer_menu('봉사자'); ?>

<div id="container" class="container-fluid">
  <nav class="navbar navbar-light bg-light mb-4">
    <small class="mb-0 text-secondary"><span class="text-danger"><?=RETURN_VISIT_EXPIRATION?RETURN_VISIT_EXPIRATION:'3'?>개월</span>간 관리되지 않는 재방은 자동 중단됩니다</small>
  </nav>

  <div id="minister-returnvisit">
    <?php if($result->num_rows == 0 && $result_t->num_rows == 0 ){ 
      echo '<div class="text-center align-middle p-5 text-secondary" >재방문이 없습니다</div>';
    }else{
    ?>
    <?php while ($row = $result->fetch_assoc()):?>
      <?php $h_id = $row['h_id'];?>
      <div class="list-group mb-2" rv_index="h_<?=$h_id?>">
        <div class="list-group-item d-flex justify-content-between p-2 border-light">
          <div class="flex-grow-1">
            <div>
              <span class="badge badge-pill badge-<?=$row['tt_type']=='편지'?'info':'success'?> badge-outline align-middle"><?=$row['tt_type']?></span>
              <span class="badge badge-pill badge-secondary align-middle "><?=get_house_condition_text($row['h_condition'])?></span>
            </div>
            <div>
              <?php if(in_array($row['tt_type'],array('아파트','빌라','추가2'))):?>
                <?php
                if($row['tt_type'] == '아파트'){
                  $address2_text = $c_territory_type['type_2'][2]?$c_territory_type['type_2'][2]:'동';
                  $address3_text = $c_territory_type['type_2'][3]?$c_territory_type['type_2'][3]:'호';
                }elseif($row['tt_type'] == '빌라'){
                  $address2_text = $c_territory_type['type_3'][2]?$c_territory_type['type_3'][2]:'동';
                  $address3_text = $c_territory_type['type_3'][3]?$c_territory_type['type_3'][3]:'호';
                }else{
                  $address2_text = $c_territory_type['type_8'][2]?$c_territory_type['type_8'][2]:'';
                  $address3_text = $c_territory_type['type_8'][3]?$c_territory_type['type_8'][3]:'';
                }
                ?>
                <span><?=$row['h_address1']?> <?php if($row['h_address2']) echo $row['h_address2'].$address2_text?> <?php if($row['h_address3']) echo $row['h_address3'].$address3_text?></span>
              <?php else:?>
                <div>
                  <?=$row['h_address5']?>
                </div>
                <div>
                  <small><?=$row['h_address1']?> <?=$row['h_address2']?> <?=$row['h_address3']?> <?=$row['h_address4']?></small> <?php if($row['h_address1']) echo kakao_menu($row['h_address1'].' '.$row['h_address2']);?>
                </div> 
              <?php endif;?>
            </div>
            <div class="text-secondary">
              <?php
              $sql_sub = "SELECT rv_datetime FROM ".RETURN_VISIT_TABLE." WHERE h_id = '{$h_id}' AND rv_transfer = '0' ORDER BY rv_datetime desc";
              $result_sub = $mysqli->query($sql_sub);
              if($result_sub->num_rows > 0){ // 최근방문
                $row_sub = $result_sub->fetch_assoc();
                echo '<div><small>마지막으로 <span class="text-info">'.timeAgo($row_sub['rv_datetime']).'</span> 방문</small></div>';
                echo '<div class="mt-n1"><small>('.get_datetime_text($row_sub['rv_datetime']).')</small></div>';
              }
             ?>
            </div>
          </div>
          <div class="flex-shrink-0 align-self-center">
            <div class="dropdown dropup">
              <button class="btn btn-outline-secondary" type="button" id="ex<?=$row['tt_id']?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-three-dots-vertical "></i>
              </button>
              <div class="dropdown-menu dropdown-menu-right" aria-labelledby="ex<?=$row['tt_id']?>" >
                <a class="dropdown-item" href="<?=BASE_PATH?>/pages/minister_returnvisit_management.php?h_id=<?=$h_id?>">관리</a>
                <button class="dropdown-item" type="button" onclick="returnvisit('territory','stop',<?=$h_id?>)">중단</button>
                <button class="dropdown-item" type="button" onclick="returnvisit('territory','transfer',<?=$h_id?>)">양도</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile;?>

    <?php while ($row=$result_t->fetch_assoc()):?>
      <?php $tph_id = $row['tph_id'];?>
      <div class="list-group mb-2" rv_index="tph_<?=$tph_id?>">
        <div class="list-group-item d-flex justify-content-between p-2 border-light">
          <div class="flex-grow-1">
            <div>
              <span class="badge badge-pill badge-warning badge-outline align-middle">전화</span>
              <span class="badge badge-pill badge-secondary align-middle"><?=get_house_condition_text($row['tph_condition'])?></span>
            </div>
            <div>
              <div><?=$row['tph_name']?></div>
              <div>
                <small><?=$row['tph_address']?></small>
              </div>
              <div>
                <small><?=$row['tph_number']?></small>
                <?php if($row['tph_name']): ?>
                <span>
                  <a href="tel:<?=$row['tph_number']?>" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-telephone"></i>
                  </a>
                </span>
                <?php endif;?>
              </div>
            </div>
            <div class="text-secondary">
              <?php
              $sql_sub = "SELECT tprv_datetime FROM ".TELEPHONE_RETURN_VISIT_TABLE." WHERE tph_id = '{$tph_id}' AND tprv_transfer = '0' ORDER BY tprv_datetime desc";
              $result_sub = $mysqli->query($sql_sub);
              if($result_sub->num_rows > 0){ // 최근방문
                $row_sub = $result_sub->fetch_assoc();
                echo '<div><small>마지막으로 <span class="text-info">'.timeAgo($row_sub['tprv_datetime']).'</span> 방문</small></div>';
                echo '<div class="mt-n1"><small>('.get_datetime_text($row_sub['tprv_datetime']).')</small></div>';
              }
             ?>
            </div>
          </div>
          <div class="flex-shrink-0 align-self-center">
            <div class="dropdown dropup">
              <button class="btn btn-outline-secondary" type="button" id="ex<?=$row['tp_id']?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-three-dots-vertical"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-right" aria-labelledby="ex<?=$row['tp_id']?>" >
                <a class="dropdown-item" href="<?=BASE_PATH?>/pages/minister_returnvisit_management_telephone.php?tph_id=<?=$tph_id?>">관리</a>
                <button class="dropdown-item" type="button" onclick="returnvisit('telephone','stop',<?=$tph_id?>)">중단</button>
                <button class="dropdown-item" type="button" onclick="returnvisit('telephone','transfer',<?=$tph_id?>)">양도</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile;?>
  <?php
  }
  ?>
  </div>
</div>

<?php include_once('../footer.php');?>
