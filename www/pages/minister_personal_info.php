<?php include_once('../header.php');?>

<?php $mb_id = mb_id();?>
<?php $mb = get_member_data($mb_id);?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">봉사자 <span class="d-xl-none">나의 설정</span></h1>
  <?php echo header_menu('minister','나의 설정'); ?>
</header>

<?php echo footer_menu('봉사자');?>

<div id="container" class="container-fluid">
  <form id="minister_info">
    <input type="hidden" name="mb_id" value="<?=$mb_id?>">
    <input type="hidden" name="work" value="info">
    <div class="form-group row">
      <label class="col-4 col-md-2 col-form-label">휴대폰</label>
      <div class="col-8 col-md-10">
        <input class="form-control p-1" name="mb_hp" type="tel" value="<?=isset($mb['mb_hp'])?decrypt($mb['mb_hp']):'';?>" pattern="[0-9]{11}" placeholder="전화번호 01019141935">
      </div>
    </div>
    <div class="form-group row">
      <label class="col-4 col-md-2 col-form-label">주소</label>
      <div class="col-8 col-md-10">
        <input class="form-control p-1" name="mb_address" type="text" value="<?=isset($mb['mb_address'])?decrypt($mb['mb_address']):'';?>" placeholder="주소">
      </div>
    </div>
    <div class="form-group row">
      <label class="col-4 col-md-2 col-form-label">새 비밀번호</label>
      <div class="col-8 col-md-10">
        <input class="form-control p-1" name="mb_password" type="text" value="">
        <small class="text-muted">
          비밀번호 변경시에만 입력하세요.
        </small>
      </div>
    </div>
    <!-- 글자 크기 설정 UI -->
    <!-- <div class="form-group row align-items-center">
      <label class="col-4 col-md-2 col-form-label">글자 크기</label>
      <div class="col-8 col-md-10">
        <select class="form-control" name="font_size" id="font_size_select">
          <option value="" <?=empty($mb['font_size'])?'selected':''?>>기본값</option>
          <option value="large" <?=$mb['font_size']=='large'?'selected':''?>>크게</option>
          <option value="xlarge" <?=$mb['font_size']=='xlarge'?'selected':''?>>아주 크게</option>
        </select>
        <small class="text-muted">글자 크기를 선택하면 사이트 전체에 적용됩니다.</small>
      </div>
    </div> -->
    <div>
      <button type="button" class="btn btn-outline-danger" onclick="logout();"><i class="bi bi-door-closed"></i> 로그아웃</button>
      <button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-save"></i> 저장</button>
    </div>
  </form>
</div>

<?php include_once('../footer.php'); ?>
