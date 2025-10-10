<?php include_once('../config.php');?>
<?php check_accessible('admin');?>

<?php
$ms_week = 0;
$ms_type = 0;
$ms_time = date("H:i", strtotime("09:00"));
$ms_start_time = date("H:i", strtotime("00:00"));
$ms_finish_time = date("H:i", strtotime("00:00"));
$ms_guide = '';
$ms_guide2 = '';
$g_id = 0;
$mp_id = 0;
$copy_ms_id = 0;
$ms_limit = '';
$c_meeting_schedule_type_attend_limit = unserialize(MEETING_SCHEDULE_TYPE_ATTEND_LIMIT);

// 안전 가드 및 기본 초기화
$ms_id = isset($ms_id) ? $ms_id : 0;
$ma_id = isset($ma_id) ? $ma_id : 0;
$gu_id1 = array();
$gu_id2 = array();

$guide_sql ="SELECT * FROM ".MEMBER_TABLE." WHERE mb_moveout_date = '0000-00-00' ORDER BY mb_sex, mb_name";
$group = get_group_data_all();
$mp = get_meeting_place_data_all();

$ms_sql = "SELECT ms_id, ma_id, ms_week, ms_time, mp_name, g_name
           FROM ".MEETING_SCHEDULE_TABLE." ms LEFT JOIN ".MEETING_PLACE_TABLE." mp ON ms.mp_id = mp.mp_id LEFT JOIN ".GROUP_TABLE." g ON ms.g_id = g.g_id
           ORDER BY ms_week, ms_time, mp_name, g_name, ms_id ASC";
$ms_result = $mysqli->query($ms_sql);

if($ms_id){
  $row = get_meeting_schedule_data($ms_id);

  if(is_array($row) && !empty($row)){
    $ms_week = $row['ms_week'];
    $ms_type = $row['ms_type'];
    $ms_time = $row['ms_time'];
    $g_id = $row['g_id'];
    $mp_id = $row['mp_id'];
    $copy_ms_id = $row['copy_ms_id'];
    $ms_limit = $row['ms_limit'];

    if(!empty($row['ms_guide'])){
      $ms_guide = $row['ms_guide'];
      $guide_list_1 = get_guide_data($ms_guide);
      if(is_array($guide_list_1)){
        foreach ($guide_list_1 as $value) $gu_id1[] = $value['name'];
      }
      $gu_id1_string = !empty($gu_id1) ? implode(",", $gu_id1) : '';
    }

    if(!empty($row['ms_guide2'])){
      $ms_guide2 = $row['ms_guide2'];
      $guide_list_2 = get_guide_data($ms_guide2);
      if(is_array($guide_list_2)){
        foreach ($guide_list_2 as $value) $gu_id2[] = $value['name'];
      }
      $gu_id2_string = !empty($gu_id2) ? implode(",", $gu_id2) : '';
    }

    if(isset($row) && (($row['ms_start_time'] != $row['ms_finish_time']) || !empty_date($row['ms_start_time']))){
      $ms_start_time = $row['ms_start_time'];
      $ms_finish_time = $row['ms_finish_time'];
    }
  }
}
?>
<div class="container-fluid">
  <form id="admin_meeting_form" method="POST">
    <input type="hidden" name="ms_id" value="<?=$ms_id?>">
    <input type="hidden" name="ma_id" value="<?=$ma_id?$ma_id:0?>">

    <div class="form-group row">
      <label for="week" class="col-4 col-form-label">요일</label>
      <div class="col-8">
        <select class="form-control select-custom font-weight-bold" name="week" id="week">
          <option value="8">-- 요일 선택 --</option>
          <option <?=get_selected_text($ms_week, 1);?> value="1">월</option>
          <option <?=get_selected_text($ms_week, 2);?> value="2">화</option>
          <option <?=get_selected_text($ms_week, 3);?> value="3">수</option>
          <option <?=get_selected_text($ms_week, 4);?> value="4">목</option>
          <option <?=get_selected_text($ms_week, 5);?> value="5">금</option>
          <option <?=get_selected_text($ms_week, 6);?> value="6">토</option>
          <option <?=get_selected_text($ms_week, 7);?> value="7">일</option>
        </select>
      </div>
    </div>

    <div class="form-group row">
      <label for="type" class="col-4 col-form-label">모임 형태</label>
      <div class="col-8">
        <select class="form-control select-custom font-weight-bold" name="type" id="type">
          <option value="0">-- 모임 형태 선택 --</option>
          <?php echo get_meeting_schedule_type_options($ms_type); ?>

        </select>
      </div>
    </div>

    <div class="form-group row">
      <label for="time" class="col-4 col-form-label">모임 시간</label>
      <div class="col-8">
        <input type="time" class="form-control time-custom font-weight-bold" name="time" id="time" value="<?=$ms_time?>" style="padding-left:1rem;">
      </div>
    </div>

    <div class="form-group row">
      <label for="st_time" class="col-4 col-form-label">시작 시간</label>
      <div class="col-8">
        <input type="time" class="form-control time-custom font-weight-bold" name="st_time" id="st_time" value="<?=$ms_start_time?>" style="padding-left:1rem;" onchange="timemin();">
      </div>
    </div>

    <div class="form-group row">
      <label for="fi_time" class="col-4 col-form-label">종료 시간</label>
      <div class="col-8">
        <input type="time" class="form-control time-custom font-weight-bold" name="fi_time" id="fi_time" value="<?=$ms_finish_time?>" style="padding-left:1rem;" onchange="timemax();">
      </div>
    </div>

    <div class="form-group row">
      <label for="guide1" class="col-4 col-form-label">인도자</label>
      <div class="col-8">
        <select class="form-control select-custom" name="guide1[]" select_name="guide1" id="guide1" multiple>
          <option value='0' disabled>-- 인도자 선택 --</option>
          <?php
          $guide_result = $mysqli->query($guide_sql);
          if($guide_result && $guide_result->num_rows > 0){
            while($g1 = $guide_result->fetch_assoc()){
              $m_idx = $g1['mb_id'];
              $selected = check_include_guide($m_idx, $ms_guide)?'selected="selected"':'';
              echo "<option value='".$m_idx."' ".$selected." > ".$g1['mb_name']." </option>";
            }
          }
          ?>
        </select>
      </div>
    </div>

    <div class="form-group row">
      <label for="guide2" class="col-4 col-form-label">보조자</label>
      <div class="col-8">
        <select class="form-control select-custom" name="guide2[]" select_name="guide2" id="guide2" multiple>
          <option value='0' disabled>-- 보조자 선택 --</option>
          <?php
          $guide_result = $mysqli->query($guide_sql);
          if($guide_result && $guide_result->num_rows > 0){
            while($g2 = $guide_result->fetch_assoc()){
              $m_idx2 = $g2['mb_id'];
              $selected = check_include_guide($m_idx2, $ms_guide2)?'selected="selected"':'';
              echo "<option value='".$m_idx2."' ".$selected." > ".$g2['mb_name']." </option>";
            }
          }
          ?>
        </select>
      </div>
    </div>

    <div class="form-group row">
      <label for="group" class="col-4 col-form-label">봉사 집단</label>
      <div class="col-8">
        <select class="form-control select-custom font-weight-bold" name="group" id="group">
          <option value='0'>-- 봉사 집단 선택 --</option>
          <?php foreach ($group as $key => $value) echo "<option value='".$key."' ".get_selected_text($key, $g_id).">".$value."</option>";?>
        </select>
        <small class="text-muted">집단 봉사를 위한 모임 계획을 만들 때 사용합니다.</small>
      </div>
    </div>

    <div class="form-group row">
      <label for="place" class="col-4 col-form-label">모임 장소</label>
      <div class="col-8">
        <select class="form-control select-custom font-weight-bold" name="place" id="place">
          <option value='0'>-- 모임 장소 선택 --</option>
          <?php foreach ($mp as $key => $value) echo "<option value='".$key."' ".get_selected_text($key, $mp_id).">[".$value['mp_name']."] ".$value['mp_address']."</option>";?>
        </select>
      </div>
    </div>

    <div class="form-group row">
      <label for="copy_ms_id" class="col-4 col-form-label">구역 복사</label>
      <div class="col-8">
        <select class="form-control select-custom font-weight-bold" name="copy_ms_id" id="copy_ms_id">
          <option value='0'>-- 구역 복사 선택 --</option>
          <?php
          if($ms_result && $ms_result->num_rows > 0){
          while($ms = $ms_result->fetch_assoc()){
            echo "<option value='".$ms['ms_id']."' ".get_selected_text($ms['ms_id'], $copy_ms_id).">".'('.$ms['ms_id'].') '.get_week_text($ms['ms_week']).' '.get_meeting_data_text($ms['ms_time'], $ms['g_name'], $ms['mp_name']);
            if($ms['ma_id']) echo ' [회중일정]';
            echo '</option>';
          }
          }
          ?>
        </select>
        <small class="text-muted">다른 모임 계획에 분배 되어있는 구역들을 그대로 사용할 수 있습니다.</small>
      </div>
    </div>

    <div class="form-group row" for="ms_limit">
      <label for="ms_limit" class="col-4 col-form-label">지원자 수 제한</label>
      <div class="col-8">
        <div class="mb-2">
          <div class="custom-control custom-radio">
            <input type="radio" id="ms_limit_default" name="ms_limit_type" class="custom-control-input" value="default" <?= empty($ms_limit) ? 'checked' : '' ?>>
            <label class="custom-control-label" for="ms_limit_default">기본값 따름</label>
          </div>
          <div class="custom-control custom-radio">
            <input type="radio" id="ms_limit_custom" name="ms_limit_type" class="custom-control-input" value="custom" <?= !empty($ms_limit) ? 'checked' : '' ?>>
            <label class="custom-control-label" for="ms_limit_custom">직접 설정</label>
          </div>
        </div>
        
        <div class="input-group" id="ms_limit_input" style="display: <?= !empty($ms_limit) ? 'flex' : 'none' ?>;">
          <input type="number" class="form-control font-weight-bold" name="ms_limit_display" id="ms_limit_display" value="<?= !empty($ms_limit) ? $ms_limit : '' ?>" min="1">
          <input type="hidden" name="ms_limit" id="ms_limit" value="<?= !empty($ms_limit) ? $ms_limit : '' ?>">
          <div class="input-group-append">
            <span class="input-group-text">명</span>
          </div>
        </div>
        
        <small class="text-muted">
          이 모임 계획에서만 적용될 지원자 수 제한입니다.<br>
          * 제한 없음은 빈칸으로 두세요.
        </small>
      </div>
    </div>

    <script>
    $(document).ready(function() {
      $('input[name="ms_limit_type"]').change(function() {
        if($(this).val() == 'custom') {
          $('#ms_limit_input').show();
          updateMsLimit();
        } else {
          $('#ms_limit_input').hide();
          $('#ms_limit').val('');
          $('#ms_limit_display').val('');
        }
      });

      $('#ms_limit_display').on('input', function() {
        updateMsLimit();
      });

      $(document).on('click', function(e) {
        if(!$(e.target).is('#ms_limit_display')) {
          if($('#ms_limit_display').val() == '') {
            if($('input[name="ms_limit_type"]:checked').val() == 'custom') {
              $('#ms_limit').val('0');
            } else {
              $('#ms_limit').val('');
            }
          }
        }
      });

      function updateMsLimit() {
        var val = $('#ms_limit_display').val();
        if(val && parseInt(val) >= 1) {
          $('#ms_limit').val(val);
        } else {
          if($('input[name="ms_limit_type"]:checked').val() == 'custom') {
            $('#ms_limit').val('0');
          } else {
            $('#ms_limit').val('');
          }
          $('#ms_limit_display').val('');
        }
      }
    });
    </script>

    <div class="mt-2 text-right">
      <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save"></i> 저장</button>
    </div>

  </form>
</div>
