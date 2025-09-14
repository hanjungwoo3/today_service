<?php include_once('../config.php');?>

<?php
// 필수 파라미터 검증
if(empty($table) || empty($pid) || empty($work)) {
    echo '<div class="alert alert-danger">필수 정보가 누락되었습니다.</div>';
    exit;
}

$mb_id = mb_id();
$c_house_condition_use = unserialize(HOUSE_CONDITION_USE);

if($work == 'view'): // 상태보기

  $condition_info = get_condition_info($table,$pid); // 기록된 상태 관련 정보

  if($condition_info['mb_id'] == $mb_id):
    if(!in_array($condition_info['condition'],array(1,2))):?>
      <form class="" action="" method="post">
        <input type="hidden" name="table" value="<?=$table?>">
        <input type="hidden" name="work" value="edit">
        <input type="hidden" name="pid" value="<?=$pid?>">
        <input type="hidden" name="hm_id" value="<?=$condition_info['hm_id']?>">

        <div class="form-group row">
          <label for="condition" class="col-4 col-form-label">상태</label>
          <div class="col-8">
            <select id="condition" class="form-control" name="condition" required>
              <option value="">선택</option>
              <?php for ($i=3; $i < 11; $i++) if($c_house_condition_use[$i] && $c_house_condition_use[$i] == 'use') echo '<option value="'.$i.'" '.get_selected_text($condition_info['condition'], $i).'>'.get_house_condition_text($i).'</option>';?>
            </select>
          </div>
        </div>

        <div class="form-group row">
          <label for="content" class="col-12 col-md-12 col-form-label">내용</label>
          <div class="col-12 col-md-12">
            <textarea class="form-control" id="content" name="content" rows="3" required><?=$condition_info['content']?></textarea>
          </div>
        </div>

        <div>
          <button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-save"></i> 저장</button>
          <button type="button" onclick="condition_delete('<?=$table?>',<?=$pid?>);" class="btn btn-outline-danger float-left"><i class="bi bi-trash"></i> 삭제</button>
        </div>
      </form>
      <?php else:?>
       <div class="alert alert-info" role="alert">재방문관리는 봉사자탭 에서 가능합니다.</div>
       <div>
          <button type="button" onclick="condition_delete('<?=$table?>',<?=$pid?>);" class="btn btn-outline-danger float-left"><i class="bi bi-x-circle"></i> 재방문 취소</button>
          <a href="<?=BASE_PATH?>/pages/minister_returnvisit.php" class="btn btn-outline-info float-right">재방문관리로 이동</a>
       </div>
     <?php endif;?>
   <?php else:?>
    <table class="table table-bordered">
      <tbody>
        <tr>
          <th class="text-dark bg-light">기록 상태</th>
          <td><?=get_house_condition_text($condition_info['condition'])?></td>
        </tr>
        <?php if($condition_info['cdate']): ?>
        <tr>
          <th  class="text-dark bg-light">기록 시간</th>
          <td><?=date('Y년 m월 d일 H:i:s',strtotime($condition_info['cdate']))?></td>
        </tr>
        <?php endif; ?>
        <tr>
          <th class="text-dark bg-light">기록 전도인</th>
          <td><?=$condition_info['mb_name']?></td>
        </tr>
        <?php if($condition_info['content']): ?>
        <tr>
          <th class="text-dark bg-light">기록 내용</th>
          <td><?=$condition_info['content']?></td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?php if(is_admin($mb_id)): ?>
    <div class="text-right">
      <button type="button" onclick="condition_delete('<?=$table?>',<?=$pid?>);" class="btn btn-outline-danger"><i class="bi bi-trash"></i> 삭제</button>
    </div>
    <?php endif; ?>
  <?php endif;?>

<?php elseif($work == 'add'): // 상태추가  ?>

  <form class="" action="" method="post">
    <input type="hidden" name="table" value="<?=$table?>">
    <input type="hidden" name="work" value="add">
    <input type="hidden" name="pid" value="<?=$pid?>">

    <div class="form-group row">
      <label for="condition" class="col-4 col-form-label">상태</label>
      <div class="col-8">
        <select id="condition" class="form-control" name="condition" required>
          <option value="">선택</option>
          <?php if(RETURNVISIT_USE == 'use'): ?>
          <option value="1"><?=get_house_condition_text('1')?></option>
          <option value="2"><?=get_house_condition_text('2')?></option>
          <?php endif; ?>
          <?php for ($i=3; $i < 11; $i++) if($c_house_condition_use[$i] && $c_house_condition_use[$i] == 'use') echo '<option value="'.$i.'">'.get_house_condition_text($i).'</option>';?>
        </select>
      </div>
    </div>

    <div class="form-group row" style="display:none;">
      <label for="datetime" class="col-4 col-form-label">방문 날짜/시간</label>
      <div class="col-8">
        <input type="datetime-local" id="datetime" class="form-control" name="datetime" value="<?=date('Y-m-d\TH:i')?>" >
      </div>
    </div>

    <div class="form-group row">
      <label for="content" class="col-12 col-form-label">내용</label>
      <div class="col-12">
        <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
      </div>
    </div>

    <div class="text-right">
      <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save"></i> 저장</button>
    </div>
  </form>

<?php endif;?>
