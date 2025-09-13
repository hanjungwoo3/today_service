<?php include_once('../header.php');?>

<?php
if(empty($tph_id)){ // tph_id 가 넘어오지 않을때
  echo '잘못된 접근입니다.';
  exit;
}

$a = false;
$transfer_mb_id = 0;
$tmp_mb_id = 0;
$mb_id = mb_id();

$sql2 = "SELECT tph_type, tph_name, tph_address, tph_condition, tph_number, tph.mb_id as tph_mb_id
         FROM ".TELEPHONE_TABLE." tp INNER JOIN ".TELEPHONE_HOUSE_TABLE." tph ON tp.tp_id = tph.tp_id
         WHERE tph_id = {$tph_id}";
$result2 = $mysqli->query($sql2);
$row2=$result2->fetch_assoc();

$condition = $row2['tph_condition'];

// 자신의 재방문이 아니면
if($row2['tph_mb_id'] != $mb_id){
  echo '잘못된 접근입니다.';
  exit;
}

$sql = "SELECT * FROM ".TELEPHONE_RETURN_VISIT_TABLE." WHERE tph_id = {$tph_id} ORDER BY tprv_datetime desc, tprv_id desc";  // 모든 재방문 기록 보기
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
      <?php if($row2['tph_address'] || $row2['tph_name']):?>
        <div class="text-right mb-1">
          <?php if($row2['tph_name']) echo '<span class="d-inline-block">'.$row2['tph_name'].'</span>';?>
          <?php if($row2['tph_address']) echo '<div><small class="d-inline-block">'.$row2['tph_address'].'</small></div>';?>
        </div>
      <?php endif;?>
      <?php if($row2['tph_number']): ?>
        <div class="text-right">
          <small><?=$row2['tph_number']?></small>
          <span>
            <a href="tel:<?=$row2['tph_number']?>" class="btn btn-outline-info btn-sm">
              <i class="bi bi-telephone"></i>
            </a>
          </span>
        </div>
      <?php endif;?>
    </h5>
  </nav>

  <div class="border-bottom">
    <div class="row py-4">
      <label class="col-4 col-md-2 col-form-label">재방/연구</label>
      <div class="col-8 col-md-10 text-right">
        <div class="btn-group btn-group-toggle" data-toggle="buttons">
          <label for="optionone" class="btn btn-outline-secondary <?=($condition==1)?'active':''?>" onclick="returnvisit_change_study('telephone', 1, <?=$tph_id?>);">
            <input name="radio" type="radio" value="optionone" id="optionone" <?=($condition==1)?'checked="checked"':''?>> 재방
          </label>
          <label for="optiontwo" class="btn btn-outline-secondary <?=($condition==2)?'active':''?>" onclick="returnvisit_change_study('telephone', 2, <?=$tph_id?>);">
            <input name="radio" type="radio" value="optiontwo" id="optiontwo" <?=($condition==2)?'checked="checked"':''?>> 연구
          </label>
        </div>
      </div>
    </div>
  </div>

  <div class="my-3">
    <form id="returnvisitmemo_form" method="post">
      <input type="hidden" name="work" value="add_return_visit">
      <input type="hidden" name="pid" value="<?=$tph_id?>">
      <input type="hidden" name="table" value="telephone">
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
        if($row['mb_id'] != $mb_id && $transfer_mb_id != $row['mb_id'] && $row['tprv_transfer'] != 1) continue;
      }

      if($row['tprv_transfer'] == 1){ // 양도컨텐츠면
        if($a) break; // 마지막 양도해준 사람의 기록까지만 볼 수 있게
        $transfer_mb_id = $row['mb_id'];
        $a = true;
        ?>
        <div class="list-group-item">
          <?=get_datetime_text($row['tprv_datetime'])?> 양도
        </div>
        <?php
      }else{
        $tmp_mb_id = $row['mb_id'];
        $rv_datetime = date('Y-m-d\TH:i', strtotime($row['tprv_datetime']));
        ?>
        <div class="list-group-item returnvisit_list" rv_index="<?=$row['tprv_id']?>">
          <div class="d-flex justify-content-between">
            <div class="flex-grow-1">
              <div>
                <?=get_datetime_text($row['tprv_datetime'])?>
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
                <input type="hidden" name="rv_id" value="<?=$row['tprv_id']?>">
                <input type="hidden" name="table" value="telephone">
                <div class="form-group row">
                  <label for="datetime_<?=$row['tprv_id']?>" class="col-4 col-md-2 col-form-label">일시</label>
                  <div class="col-8 col-md-10">
                    <input type="datetime-local" class="form-control" name="datetime" value="<?=$rv_datetime?>" id="datetime_<?=$row['tprv_id']?>" required>
                  </div>
                </div>
                <div class="form-group row">
                  <label for="rv_content_<?=$row['tprv_id']?>" class="col-12 col-form-label">내용</label>
                  <div class="col-12">
                    <textarea class="form-control" rows="4" name="rv_content" id="rv_content_<?=$row['tprv_id']?>" required><?=$row['tprv_content']?></textarea>
                  </div>
                </div>
                <div class="form-group">
                  <button type="button" class="btn btn-outline-danger" onclick="delete_returnvisit('telephone',<?=$row['tph_id']?>,<?=$row['tprv_id']?>);"><i class="bi bi-trash"></i> 삭제</button>
                  <button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-pencil-square"></i> 수정</button>
                </div>
              </form>
            <?php else: ?>
              <div class="mb-2">
                <?=$row['tprv_content']?>
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
