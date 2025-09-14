<?php include_once('../config.php');?>

<?php
if(!in_array($auth, get_member_board_auth(mb_id()))){
  echo '잘못된 접근입니다.';
  exit;
}

$b_guide = array();

$board_title = array('','봉사자','파이오니아','인도자','봉사의 종','장로','관리자','질문과 대답');
$work = 'add';
if(!empty($b_id)){
  $work = 'edit';
  $sql = "SELECT * FROM ".BOARD_TABLE." WHERE b_id = '$b_id'";
  $result = $mysqli->query($sql);
  $row = $result->fetch_assoc();
  $b_guide = explode(" ", $row['b_guide']);
}
?>

<nav class="navbar navbar-light bg-light mb-4">
  <a class="navbar-brand" href="#" page="board" data-var="auth" data-val="<?=$auth?>" id="form-cancel"><i class="bi bi-arrow-left"></i></a>
  <h4 class="float-right mb-0">공지 <?=$work=='edit'?'수정':'추가'?></h4>
</nav>

<form>
  <input type="hidden" name="work" value="<?=$work?>">
  <input type="hidden" name="b_id" value="<?=$b_id?>">
  <input type="hidden" name="auth" value="<?=$auth?>">

  <div class="form-group row">
    <label for="b_notice" class="col-4 col-md-2 col-form-label">중요 글</label>
    <div class="col-8 col-md-10">
      <select class="form-control" id="b_notice" name="notice">
        <option value=''>일반</option>
        <option value='1' <?=($row['b_notice'] && $row['b_notice'] == 1)?'selected="selected"':'';?>>중요</option>
      </select>
    </div>
  </div>
  <div class="form-group row">
    <label for="b_guide" class="col-4 col-md-2 col-form-label">게시판</label>
    <div class="col-8 col-md-10">
      <select class="form-control" id="b_guide" name="b_guide[]" multiple>
        <option value='' disabled>게시판선택</option>
        <option value='1' <?=(in_array("1", $b_guide))?'selected="selected"':'';?>><?=$board_title[1];?></option>
        <option value='7' <?=(in_array("7", $b_guide))?'selected="selected"':'';?>><?=$board_title[7];?></option>
        <option value='2' <?=(in_array("2", $b_guide))?'selected="selected"':'';?>><?=$board_title[2];?></option>
        <option value='3' <?=(in_array("3", $b_guide))?'selected="selected"':'';?>><?=$board_title[3];?></option>
        <option value='4' <?=(in_array("4", $b_guide))?'selected="selected"':'';?>><?=$board_title[4];?></option>
        <option value='5' <?=(in_array("5", $b_guide))?'selected="selected"':'';?>><?=$board_title[5];?></option>
        <option value='6' <?=(in_array("6", $b_guide))?'selected="selected"':'';?>><?=$board_title[6];?></option>
      </select>
    </div>
  </div>
  <div class="form-group row">
    <label for="b_title" class="col-4 col-md-2 col-form-label">제목</label>
    <div class="col-8 col-md-10">
      <input type="text" class="form-control p-1" id="b_title" name="title" value="<?=isset($row['b_title'])?$row['b_title']:''?>" required>
    </div>
  </div>
	<div class="form-group py-2">
		  <textarea id="editor" name="content" rows="8" cols="40" class="form-control"><?=isset($row['b_content'])?$row['b_content']:''?></textarea>
  </div>
  <div class="clearfix">
    <button itype="submit" class="btn btn-outline-primary float-right"><i class="bi bi-save"></i> 저장</button>
  </div>
</form>

<script>
$(document).ready(function() {
  $('#editor').summernote({
    lang: 'ko-KR',
    height:400,
    toolbar: [
      ['fontname', ['fontname']],
      ['fontsize', ['fontsize']],
      ['font', ['bold', 'underline', 'strikethrough', 'clear']],
      ['color', ['color']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['table', ['table']],
      ['insert', ['link', 'picture']],
      ['view', ['codeview', 'help']]
    ],
    callbacks: {
      onImageUpload: function(files) {
        for(let i=0; i < files.length; i++) {
          $.upload(files[i]);
        }
      }
    }
  });
  $.upload = function (file) {
    let out = new FormData();
    out.append('file', file, file.name);
    $.ajax({
      method: 'POST',
      url: '../include/upload_image.php',
      contentType: false,
      cache: false,
      processData: false,
      data: out,
      success: function (img) {
        $('#editor').summernote('insertImage', img);
      },
      error: function (jqXHR, textStatus, errorThrown) {
          console.error(textStatus + " " + errorThrown);
      }
    });
  };
});
</script>
