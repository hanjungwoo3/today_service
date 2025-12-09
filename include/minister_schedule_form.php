<?php include_once('../config.php');?>

<?php
$color = array('btn-dark', 'btn-primary', 'btn-info', 'btn-success', 'btn-warning', 'btn-danger', 'btn-secondary');
if(empty($s_date)) $s_date = date('Y-m-d');
$s_datetime = date('Y-m-d\TH:i:s', strtotime($s_date));

if($me_id){
  $me_sql = "SELECT * FROM ".MINISTER_EVENT_TABLE." WHERE me_id = '{$me_id}'";
  $me_result = $mysqli->query($me_sql);
  $me = $me_result->fetch_assoc();
  $me_date =  date('Y-m-d', strtotime($me['me_date']));
  $me_date2 =  date('Y-m-d', strtotime($me['me_date2']));
  $me_datetime = str_replace(" ", "T", $me['me_date']);
  $me_datetime2 = str_replace(" ", "T", $me['me_date2']);
}

$d_display = 'd-none';
$dt_display = '';
$checked = '';
if(isset($me['me_switch']) && $me['me_switch'] == 1){
  $d_display = '';
  $dt_display = 'd-none';
  $checked = 'checked="checked"';
}
?>

<form id="minister_event_form">
  <input type="hidden" name="mb_id" value="<?=mb_id()?>">
  <input type="hidden" name="me_id" value="<?=$me_id?>">
  <input type="hidden" name="work" value="event">

  <div class="form-group row">
    <label class="col-4 col-md-2 col-form-label">색상</label>
    <div class="col-8 col-md-10">
      <div class="btn-group btn-group-lg btn-group-toggle w-100" data-toggle="buttons">
        <?php for ($i=0; $i < 7; $i++):?>
        <label class="btn <?=$color[$i]?> <?=($me['me_color'] == $i)?'active':'';?>">
          <input type="radio" name="color" value="<?=$i?>" <?=get_checked_text($me['me_color'], $i);?>>
        </label>
        <?php endfor;?>
      </div>
    </div>
  </div>

  <div class="form-group row">
    <label class="col-4 col-md-2 col-form-label">제목</label>
    <div class="col-8 col-md-10">
      <input type="text" class="form-control" name="title" placeholder="제목" value="<?=isset($me['me_title'])?$me['me_title']:''?>" autofocus required>
    </div>
  </div>

  <div class="form-group row text-right">
    <div class="col-12 col-md-12">
      <div class="custom-control custom-switch">
        <input type="checkbox" name="timeswitch" class="custom-control-input" id="timeswitch" value="1" <?=$checked?>>
        <label class="custom-control-label" for="timeswitch" onclick="timetoggle();">하루종일</label>
      </div>
    </div>
  </div>

  <div class="form-group row">
    <label class="col-4 col-md-2 col-form-label">시작</label>
    <div class="col-8 col-md-10">
      <input type="date" name="date" class="form-control <?=$d_display?>" value="<?php echo isset($me_date)?$me_date:$s_date;?>" onchange="datemin();">
      <input type="datetime-local" name="datetime" class="form-control <?=$dt_display?>" value="<?php echo isset($me_datetime)?$me_datetime:$s_datetime;?>" onchange="datemin();">
    </div>
  </div>

  <div class="form-group row">
    <label class="col-4 col-md-2 col-form-label">종료</label>
    <div class="col-8 col-md-10">
      <input type="date" name="date2" class="form-control <?=$d_display?>" value="<?php echo isset($me_date2)?$me_date2:$s_date;?>" onchange="datemax();">
      <input type="datetime-local" name="datetime2" class="form-control <?=$dt_display?>" value="<?php echo isset($me_datetime2)?$me_datetime2:$s_datetime;?>" onchange="datemax();">
    </div>
  </div>

  <div class="form-group row">
    <label class="col-4 col-md-2 col-form-label">내용</label>
    <div class="col-8 col-md-10">
      <textarea class="form-control" name="content" maxlength="100" rows="2" placeholder="일정내용"><?=isset($me['me_content'])?$me['me_content']:''?></textarea>
    </div>
  </div>

  <div class="text-right">
    <button class="btn btn-outline-primary" type="submit" ><i class="bi bi-save"></i> 저장</button>
  </div>
</form>
