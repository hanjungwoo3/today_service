<?php
include_once('../header.php');

check_accessible('super');

if(isset($mb_id)){
  $mb = get_member_data($mb_id);
}

?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">관리자 <span class="d-xl-none">전도인 관리</span></h1>
  <?php echo header_menu('admin','전도인 관리'); ?>
</header>

<?php echo footer_menu('관리자'); ?>

<div id="container" class="container-fluid">

  <nav class="navbar navbar-light bg-light mb-4">
    <a class="navbar-brand" href="#" page="admin_member" id="form-cancel"><i class="bi bi-arrow-left"></i></a>
    <h4 class="float-right mb-0">전도인 <?=isset($mb_id)?'수정':'추가'?></h4>
  </nav>

  <form id="member-form" method="post">
    <input type="hidden" name="mb_id" value="<?=isset($mb_id)?$mb_id:''?>">

    <div class="form-group row">
      <label for="mb_name" class="col-4 col-md-2 col-form-label">이름</label>
      <div class="col-8 col-md-10">
        <input type="text" class="form-control p-1" id="mb_name" name="mb_name" value="<?=isset($mb['mb_name'])?$mb['mb_name']:''?>" required>
        <div class="mb_name_alert"></div>
      </div>
    </div>

    <div class="form-group row">
      <label for="mb_password" class="col-4 col-md-2 col-form-label">비밀번호</label>
      <div class="col-8 col-md-10">
        <input type="text" class="form-control p-1" id="mb_password" value="" name="mb_password" <?php if(empty($mb_id)) echo 'required';?>>
        <?php if(isset($mb_id)): ?>
        <small class="text-danger">
          비밀번호 변경할 때만 입력하세요.
        </small>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-group row">
      <label for="mb_hp" class="col-4 col-md-2 col-form-label">전화번호</label>
      <div class="col-8 col-md-10">
        <input type="tel" class="form-control p-1" id="mb_hp" name="mb_hp" value="<?=isset($mb['mb_hp'])?decrypt($mb['mb_hp']):''?>" pattern="[0-9]{11}|[0-9]{10}">
      </div>
    </div>

    <div class="form-group row">
      <label for="mb_address" class="col-4 col-md-2 col-form-label">주소</label>
      <div class="col-8 col-md-10">
        <input type="text" class="form-control p-1" id="mb_address" name="mb_address" value="<?=isset($mb['mb_address'])?decrypt($mb['mb_address']):''?>">
      </div>
    </div>

    <div class="form-group row">
      <label for="mb_auth" class="col-4 col-md-2 col-form-label">권한</label>
      <div class="col-8 col-md-10">
        <select class="form-control" id="mb_auth" name="mb_auth">
          <option value="">없음</option>
          <option value="1" <?php echo get_selected_text($mb['mb_auth'], 1);?>><?=get_member_auth_text(1)?></option>
          <option value="2" <?php echo get_selected_text($mb['mb_auth'], 2);?>><?=get_member_auth_text(2)?></option>
        </select>
      </div>
    </div>

    <div class="form-group row">
      <label for="mb_position" class="col-4 col-md-2 col-form-label">직책</label>
      <div class="col-8 col-md-10">
        <select class="form-control" id="mb_position" name="mb_position">
          <option value="">전도인</option>
          <option value="1" <?php echo get_selected_text($mb['mb_position'], 1);?>>봉사의 종</option>
          <option value="2" <?php echo get_selected_text($mb['mb_position'], 2);?>>장로</option>
          <option value="3" <?php echo get_selected_text($mb['mb_position'], 3);?>>순회 감독자</option>
        </select>
      </div>
    </div>

    <div class="form-group row">
      <label for="mb_pioneer" class="col-4 col-md-2 col-form-label">파이오니아</label>
      <div class="col-8 col-md-10">
        <select class="form-control" id="mb_pioneer" name="mb_pioneer">
          <option value="1" <?php echo get_selected_text($mb['mb_pioneer'], 1);?>>전도인</option>
          <option value="2" <?php echo get_selected_text($mb['mb_pioneer'], 2);?>>정규 파이오니아</option>
          <option value="3" <?php echo get_selected_text($mb['mb_pioneer'], 3);?>>특별 파이오니아</option>
          <option value="4" <?php echo get_selected_text($mb['mb_pioneer'], 4);?>>선교인</option>
        </select>
      </div>
    </div>

    <div class="form-group row">
      <label for="g_id" class="col-4 col-md-2 col-form-label">봉사 집단</label>
      <div class="col-8 col-md-10">
        <select class="form-control" id="g_id" name="g_id">
          <option value="">없음</option>
          <?php foreach (get_group_data_all() as $key => $value):?>
            <option value="<?=$key?>" <?php echo get_selected_text($mb['g_id'], $key);?>><?=$value?></option>
          <?php endforeach;?>
        </select>
      </div>
    </div>

    <div class="form-group row">
      <label for="mb_sex" class="col-4 col-md-2 col-form-label">성별</label>
      <div class="col-8 col-md-10">
        <select class="form-control" id="mb_sex" name="mb_sex">
          <option value="M" <?php echo get_selected_text($mb['mb_sex'], 'M');?>>형제</option>
          <option value="W" <?php echo get_selected_text($mb['mb_sex'], 'W');?>>자매</option>
        </select>
      </div>
    </div>

    <div class="form-group row">
      <label for="mb_display" class="col-4 col-md-2 col-form-label">전시대</label>
      <div class="col-8 col-md-10">
        <select class="form-control" id="mb_display" name="mb_display" aria-describedby="mb_display_help">
          <option value="0" <?php echo get_selected_text($mb['mb_display'], 0);?>>선정</option>
          <option value="1" <?php echo get_selected_text($mb['mb_display'], 1);?>>미선정</option>
        </select>
        <small id="mb_display_help" class="text-muted">
          '미선정'으로 설정시 전도인의 전시대 모임 참석/지원이 불가능합니다.
        </small>
      </div>
    </div>

    <div class="form-group row">
      <label for="mb_movein_date" class="col-4 col-md-2 col-form-label">전입 날짜</label>
      <div class="col-8 col-md-10">
        <input type="date" class="form-control p-1" id="mb_movein_date" name="mb_movein_date" value="<?=$mb['mb_movein_date']?>" >
      </div>
    </div>

    <div class="form-group row">
      <label for="mb_moveout_date" class="col-4 col-md-2 col-form-label">전출 날짜</label>
      <div class="col-8 col-md-10">
        <input type="date" class="form-control p-1" id="mb_moveout_date" name="mb_moveout_date" value="<?=$mb['mb_moveout_date']?>" >
      </div>
    </div>

    <div class="clearfix mt-4">
      <?php if(isset($mb_id)):?>
      <button type="button" class="btn btn-outline-danger" mb_id="<?=$mb_id?>" work="del" id="member-form-del"><i class="bi bi-trash"></i> 삭제</button>
      <?php endif;?>
      <button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-save"></i> 저장</button>
    </div>

  </form>
</div>

<?php include_once('../footer.php');?>
