<?php include_once('../config.php'); ?>
<?php check_accessible('admin'); ?>
<?php $isMobileSafari = preg_match('/(iPod|iPhone|iPad)/', $_SERVER['HTTP_USER_AGENT']); ?>

<?php
$today = date('Y-m-d'); //오늘날짜
$datetime = date('Y-m-d\TH:i'); // 기존 strftime과 동일한 포맷

if ($ma_id) {
  $work = 'edit';
  $ma_sql = "SELECT * FROM " . MEETING_ADD_TABLE . " WHERE ma_id = '{$ma_id}' ORDER BY DATE(ma_date) DESC";
  $ma_result = $mysqli->query($ma_sql);
  $mar = $ma_result->fetch_assoc();

  $ma_week = $mar['ma_week'];
  $ma_weekday = $mar['ma_weekday'];
  $ma_date = date('Y-m-d', strtotime($mar['ma_date']));
  $ma_date2 = date('Y-m-d', strtotime($mar['ma_date2']));
  $ma_datetime = str_replace(" ", "T", $mar['ma_date']);
  $ma_datetime2 = str_replace(" ", "T", $mar['ma_date2']);
  $color = $mar['ma_color'];
} else {
  $work = 'add';
  $rand = array_merge(range(0, 9), range('a', 'f'));
  $color = '#' . $rand[rand(0, 15)] . $rand[rand(0, 15)] . $rand[rand(0, 15)] . $rand[rand(0, 15)] . $rand[rand(0, 15)] . $rand[rand(0, 15)];
}

$auto_hover = '';
$auto_checked = '';
$da_display = 'd-none';
$ds_display = '';
if (isset($mar['ma_auto']) && $mar['ma_auto'] == 1) {
  $auto_hover = 'hover';
  $auto_checked = 'checked="checked"';
  $da_display = '';
  $ds_display = 'd-none';
}

$d_display = 'd-none';
$dt_display = '';
$hover = '';
$checked = '';
if (isset($mar['ma_switch']) && $mar['ma_switch'] == 1) {
  $d_display = '';
  $dt_display = 'd-none';
  $hover = 'hover';
  $checked = 'checked="checked"';
}
?>

<div class="container-fluid">
  <form id="admin_addschedule_form">
    <input type="hidden" name="ma_id" value="<?= $ma_id ?>">
    <input type="hidden" name="work" value="<?= $work ?>">

    <div class="form-group row">
      <label for="title" class="col-4 col-md-2 col-form-label">일정 이름</label>
      <div class="col-8 col-md-10">
        <input type="text" class="form-control" name="title" id="title" placeholder="제목"
          value="<?= isset($mar['ma_title']) ? $mar['ma_title'] : '' ?>" autofocus required>
      </div>
    </div>

    <div class="form-group row">
      <label for="<?= ($isMobileSafari == true) ? 'colorPicker' : 'colorPicker-android'; ?>"
        class="col-4 col-md-2 col-form-label">색상</label>
      <div class="col-8 col-md-10">
        <?= ($isMobileSafari == true) ? '<div><input id="colorPicker" class="form-control" type="text" name="color" value="' . $color . '" /></div>' : '<input id="colorPicker-android" class="form-control" type="color" name="color" value="' . $color . '" />'; ?>
      </div>
    </div>

    <div class="form-group row">
      <div class="col-12 col-md-12">
        <div class="custom-control custom-switch">
          <input type="checkbox" name="autoswitch" class="custom-control-input" id="autoswitch" value="1"
            <?= $auto_checked ?>>
          <label class="custom-control-label" for="autoswitch" onclick="autotoggle();">자동 설정</label>
        </div>
      </div>
    </div>

    <div id="ma_auto" class="border rounded p-4 <?= $da_display ?>">

      <div class="form-group row">
        <label for="week" class="col-4 col-md-2 col-form-label">주일</label>
        <div class="col-8 col-md-10">
          <select class="select-custom form-control" name="week" id="week">
            <option value="0">주일선택</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_week'], 1) : ''; ?> value="1">첫째주</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_week'], 2) : ''; ?> value="2">둘째주</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_week'], 3) : ''; ?> value="3">셋째주</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_week'], 4) : ''; ?> value="4">넷째주</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_week'], 5) : ''; ?> value="5">다섯째주</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_week'], 6) : ''; ?> value="6">마지막주</option>
          </select>
        </div>
      </div>

      <div class="form-group row mb-0">
        <label for="weekday" class="col-4 col-md-2 col-form-label">요일</label>
        <div class="col-8 col-md-10">
          <select class="select-custom form-control" name="weekday" id="weekday">
            <option value="0">요일선택</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_weekday'], 1) : ''; ?> value="1">월요일</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_weekday'], 2) : ''; ?> value="2">화요일</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_weekday'], 3) : ''; ?> value="3">수요일</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_weekday'], 4) : ''; ?> value="4">목요일</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_weekday'], 5) : ''; ?> value="5">금요일</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_weekday'], 6) : ''; ?> value="6">토요일</option>
            <option <?= isset($mar['ma_week']) ? get_selected_text($mar['ma_weekday'], 7) : ''; ?> value="7">일요일</option>
          </select>
        </div>
      </div>

    </div>

    <div id="ma_timeswitch" class="border rounded p-4 <?= $ds_display ?>">

      <div class="form-group row">
        <div class="col-12 col-md-12">
          <div class="custom-control custom-switch">
            <input type="checkbox" name="timeswitch" class="custom-control-input" id="timeswitch" value="1"
              <?= $checked ?>>
            <label class="custom-control-label" for="timeswitch" onclick="timetoggle();">하루 종일</label>
          </div>
        </div>
      </div>

      <div class="form-group row">
        <label class="col-4 col-md-2 col-form-label">시작</label>
        <div class="col-8 col-md-10">
          <input type="date" name="date" class="form-control <?= $d_display ?>"
            value="<?= isset($ma_date) ? $ma_date : $today; ?>" max="9999-12-31">
          <input type="datetime-local" name="datetime" class="form-control <?= $dt_display ?>"
            value="<?= isset($ma_datetime) ? $ma_datetime : $datetime; ?>" max="9999-12-31T23:59">
        </div>
      </div>

      <div class="form-group row mb-0">
        <label class="col-4 col-md-2 col-form-label">종료</label>
        <div class="col-8 col-md-10">
          <input type="date" name="date2" class="form-control <?= $d_display ?>"
            value="<?= isset($ma_date2) ? $ma_date2 : $today; ?>" max="9999-12-31">
          <input type="datetime-local" name="datetime2" class="form-control <?= $dt_display ?>"
            value="<?= isset($ma_datetime2) ? $ma_datetime2 : $datetime; ?>" max="9999-12-31T23:59">
        </div>
      </div>

    </div>

    <div class="form-group row">
      <label for="content" class="col-12 col-md-12 col-form-label">내용</label>
      <div class="col-12 col-md-12">
        <textarea class="form-control" name="content" id="content" maxlength="100"
          rows="3"><?= isset($mar['ma_content']) ? $mar['ma_content'] : '' ?></textarea>
      </div>
    </div>

    <?php if ($ma_id): ?>
      <div class="card">
        <div class="text-dark" data-toggle="collapse" data-target="#collapseOne<?= $ma_id ?>" aria-expanded="true"
          aria-controls="collapseOne<?= $ma_id ?>">
          <div class="card-header text-center p-2">모임 계획 관리</div>
        </div>
        <div id="collapseOne<?= $ma_id ?>" class="collapse p-3 show">
          <div class="clearfix">
            <button type="button" class="btn btn-outline-primary float-right"
              onclick="meeting_work('add','',<?= $ma_id ?>);">
              <i class="bi bi-plus-circle-dotted"></i> 추가
            </button>
          </div>
          <div id="admin_meeting_list">
            <?php include_once('../pages/admin_meeting_list.php'); ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="mt-2 text-right">
      <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save"></i> 저장</button>
    </div>

  </form>
</div>

<script type="text/javascript">
  $(function () {
    $('#colorPicker').colorPicker({ pickerDefault: "<?= $color ?>", transparency: true });

    // 연도 입력 4자리 제한 (브라우저 기본 max 속성이 키보드 입력을 완전히 막지 못하는 경우 대비)
    $('input[type="date"], input[type="datetime-local"]').on('input', function() {
      var val = $(this).val();
      if(val) {
        var year = val.split('-')[0];
        if(year.length > 4) {
             // 4자리를 초과하면 앞 4자리만 남기고 자름
             var newYear = year.substring(0, 4);
             var newVal = val.replace(year, newYear);
             $(this).val(newVal);
        }
      }
    });
  });
</script>