<?php include_once('../header.php');?>

<?php
if(empty($h_id)){ // h_id 가 넘어오지 않을때
  echo '잘못된 접근입니다.';
  exit;
}

$a = false; // 양도여부
$transfer_mb_id = 0; // 마지막으로 나에게 양도해준 전도인 아이디
$tmp_mb_id = 0;
$mb_id = mb_id();
$c_territory_type = unserialize(TERRITORY_TYPE);

$sql2 = "SELECT tt.tt_type, h.h_address1, h.h_address2, h.h_address3, h.h_address4, h.h_address5, h.h_condition, h.mb_id
         FROM ".TERRITORY_TABLE." tt INNER JOIN ".HOUSE_TABLE." h ON tt.tt_id = h.tt_id
         WHERE h.h_id = {$h_id}";
$result2 = $mysqli->query($sql2);
$row2=$result2->fetch_assoc();

// 자신의 재방문이 아니면
if($row2['mb_id'] != $mb_id){
  echo '잘못된 접근입니다.';
  exit;
}

$condition = $row2['h_condition'];

$sql = "SELECT * FROM ".RETURN_VISIT_TABLE." WHERE h_id = {$h_id} ORDER BY rv_datetime desc, rv_id desc"; // 모든 재방문 기록 보기
$result = $mysqli->query($sql);
?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">봉사자 <span class="d-xl-none">재방문</span></h1>
  <?php echo header_menu('minister','재방문'); ?>
</header>

<?php echo footer_menu('봉사자'); ?>

<div id="container" class="container-fluid">
  <nav class="navbar navbar-light bg-light">
    <a class="navbar-brand" href="minister_returnvisit.php"><i class="bi bi-arrow-left"></i></a>
    <h5 class="float-right mb-0 w-75 clearfix">
      <?php
      if(in_array($row2['tt_type'],array('아파트','빌라','추가2'))):?>
        <?php
        if($row2['tt_type'] == '아파트'){
          $address2_text = $c_territory_type['type_2'][2]?$c_territory_type['type_2'][2]:'동';
          $address3_text = $c_territory_type['type_2'][3]?$c_territory_type['type_2'][3]:'호';
        }elseif($row2['tt_type'] == '빌라'){
          $address2_text = $c_territory_type['type_3'][2]?$c_territory_type['type_3'][2]:'동';
          $address3_text = $c_territory_type['type_3'][3]?$c_territory_type['type_3'][3]:'호';
        }else{
          $address2_text = $c_territory_type['type_8'][2]?$c_territory_type['type_8'][2]:'';
          $address3_text = $c_territory_type['type_8'][3]?$c_territory_type['type_8'][3]:'';
        }
        ?>
        <div class="text-right">
          <small>
            <?php if(!empty($row2['h_address1'])) echo $row2['h_address1']; ?>
            <?php if(!empty($row2['h_address2'])) echo $row2['h_address2'].$address2_text; ?>
            <?php if(!empty($row2['h_address3'])) echo $row2['h_address3'].$address3_text; ?>
          </small>
        </div>
      <?php else:?>
        <div class="text-right mb-1">
          <?=$row2['h_address5']?>
        </div>
        <div class="text-right">
          <small><?php echo $row2['h_address1'].' '.$row2['h_address2'].' '.$row2['h_address3'].' '.$row2['h_address4']; ?></small><?php if($row2['h_address1']) echo kakao_menu($row2['h_address1'].' '.$row2['h_address2']);?>
        </div>
      <?php endif;?> 
    </h5>
  </nav>

  <div class="border-bottom">
    <div class="row py-4">
      <label class="col-4 col-md-2 col-form-label">재방/연구</label>
      <div class="col-8 col-md-10 text-right">
        <div class="btn-group btn-group-toggle" data-toggle="buttons">
          <label for="optionone" class="btn btn-outline-secondary <?=($condition==1)?'active':''?>" onclick="returnvisit_change_study('territory', 1, <?=$h_id?>);">
            <input name="radio" type="radio" value="optionone" id="optionone" <?=($condition==1)?'checked="checked"':''?>> 재방
          </label>
          <label for="optiontwo" class="btn btn-outline-secondary <?=($condition==2)?'active':''?>" onclick="returnvisit_change_study('territory', 2, <?=$h_id?>);">
            <input name="radio" type="radio" value="optiontwo" id="optiontwo" <?=($condition==2)?'checked="checked"':''?>> 연구
          </label>
        </div>
      </div>
    </div>
  </div>

  <div class="my-3">
    <form id="returnvisitmemo_form" method="post">
      <input type="hidden" name="work" value="add_return_visit">
      <input type="hidden" name="pid" value="<?=$h_id?>">
      <input type="hidden" name="table" value="territory">
      <div class="form-group row">
        <label class="col-4 col-md-2 col-form-label">일시</label>
        <div class="col-8 col-md-10">
          <input type="datetime-local" class="form-control" name="datetime" value="<?=date('Y-m-d\TH:i')?>" required>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-12 col-form-label">내용</label>
        <div class="col-12">
          <textarea class="form-control" rows="4" name="content" required></textarea>
        </div>
      </div>
      <div class="text-right">
        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save"></i> 저장</button>
      </div>
    </form>
  </div>

  <div id="returnvisitmemo_update" class="list-group">
    <?php while ($row=$result->fetch_assoc()) : // 재방기록이 나의 기록이거나 나에게 양도해준 전도인의 기록일때만 보여질 수 있게?>
      <?php
      if($transfer_mb_id){
        if($row['mb_id'] != $mb_id && $transfer_mb_id != $row['mb_id'] && $row['rv_transfer'] != 1) continue;
      }

      if($row['rv_transfer'] == 1){ // 양도컨텐츠면
        if($a) break; //마지막 양도해준 사람의 기록까지만 볼 수 있게
        $transfer_mb_id = $row['mb_id'];
        $a = true;
        ?>
        <div class="list-group-item">
          <?=get_datetime_text($row['rv_datetime'])?> 양도
        </div>
        <?php
      }else{
        $tmp_mb_id = $row['mb_id'];
        $rv_datetime = date('Y-m-d\TH:i', strtotime($row['rv_datetime']));
        ?>
        <div class="list-group-item returnvisit_list" rv_index="<?=$row['rv_id']?>">
          <div class="d-flex justify-content-between">
            <div class="flex-grow-1">
              <div>
                <?=get_datetime_text($row['rv_datetime'])?>
              </div>
              <div>
                방문 전도인 : <?=get_member_name($row['mb_id'])?>
              </div>
            </div>
            <div class="flex-shrink-0 align-self-center">
              <button type="button" name="button" class="btn btn-outline-secondary" onclick="$(this).parent().parent().next().toggle();"><i class="bi bi-lightbulb"></i></button>
            </div>
          </div>
          <div class="border-top pt-2 mt-2" style="display:none;">
            <?php if($row['mb_id'] == $mb_id): ?>
              <form method="post">
                <input type="hidden" name="work" value="update_return_visit">
                <input type="hidden" name="rv_id" value="<?=$row['rv_id']?>">
                <input type="hidden" name="table" value="territory">
                <div class="form-group row">
                  <label for="datetime_<?=$row['rv_id']?>" class="col-4 col-md-2 col-form-label">일시</label>
                  <div class="col-8 col-md-10">
                    <input type="datetime-local" class="form-control" name="datetime" value="<?=$rv_datetime?>" id="datetime_<?=$row['rv_id']?>" required>
                  </div>
                </div>
                <div class="form-group row">
                  <label for="rv_content_<?=$row['rv_id']?>" class="col-12 col-form-label">내용</label>
                  <div class="col-12">
                    <textarea class="form-control" rows="4" name="rv_content" id="rv_content_<?=$row['rv_id']?>" required><?=$row['rv_content']?></textarea>
                  </div>
                </div>
                <div class="form-group">
                  <button type="button" class="btn btn-outline-danger" onclick="delete_returnvisit('territory',<?=$row['h_id']?>,<?=$row['rv_id']?>);"><i class="bi bi-trash"></i> 삭제</button>
                  <button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-pencil-square"></i> 수정</button>
                </div>
              </form>
            <?php else: ?>
              <div class="mb-2">
                <?=$row['rv_content']?>
              </div>
              <small class="text-muted">다른 전도인이 기록한 내용은 수정할 수 없습니다.</small>
            <?php endif; ?>
          </div>
        </div>
      <?php
      }
    endwhile;?>
  </div>
</div>

<?php include_once('../footer.php');?>
