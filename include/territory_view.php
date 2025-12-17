<?php include_once('../config.php');?>

<?php
$c_territory_type = unserialize(TERRITORY_TYPE);

// tt_id 안전 조회
$tt_id = isset($_POST['tt_id']) ? intval($_POST['tt_id']) : (isset($_GET['tt_id']) ? intval($_GET['tt_id']) : 0);
if(!$tt_id){
  echo '<div class="p-3 text-danger">잘못된 요청입니다. (tt_id 없음)</div>';
  exit;
}

$sql = "SELECT * FROM ".TERRITORY_TABLE." WHERE tt_id = {$tt_id}";
$result = $mysqli->query($sql);
$row = $result->fetch_assoc();
if(!$row){
  echo '<div class="p-3 text-danger">구역 정보를 찾을 수 없습니다.</div>';
  exit;
}

if( !empty_date($row['tt_assigned_date']) || $row['mb_id']){
  if($row['tt_assigned']){
    $assigned_group_arr = get_assigned_group_name($row['tt_assigned'],$row['tt_assigned_group']);
    $minister = (is_array($assigned_group_arr) == 1)?implode(' <span class="mx-1">|</span> ', $assigned_group_arr):$assigned_group_arr;
  }
  if($row['mb_id']) $minister = get_member_name($row['mb_id']);
}

// 테이블 라벨
if($row['tt_type'] == '아파트'){
  $address1_label = $c_territory_type['type_2'][1]?$c_territory_type['type_2'][1]:'아파트명';
  $address2_label = $c_territory_type['type_2'][2]?$c_territory_type['type_2'][2]:'동';
  $address3_label = $c_territory_type['type_2'][3]?$c_territory_type['type_2'][3]:'호';
}elseif($row['tt_type'] == '빌라'){
  $address1_label = $c_territory_type['type_3'][1]?$c_territory_type['type_3'][1]:'빌라명';
  $address2_label = $c_territory_type['type_3'][2]?$c_territory_type['type_3'][2]:'동';
  $address3_label = $c_territory_type['type_3'][3]?$c_territory_type['type_3'][3]:'호';
}elseif($row['tt_type'] == '편지'){
  $address1_label = '주소';
  $address2_label = $c_territory_type['type_5'][4]?$c_territory_type['type_5'][4]:'우편번호';
  $address3_label = $c_territory_type['type_5'][5]?$c_territory_type['type_5'][5]:'이름';
}elseif($row['tt_type'] == '일반'){
  $address1_label = '주소';
  $address2_label = $c_territory_type['type_1'][4]?$c_territory_type['type_1'][4]:'층';
  $address3_label = $c_territory_type['type_1'][5]?$c_territory_type['type_1'][5]:'이름/호';
}elseif($row['tt_type'] == '격지'){
  $address1_label = '주소';
  $address2_label = $c_territory_type['type_4'][4]?$c_territory_type['type_4'][4]:'층';
  $address3_label = $c_territory_type['type_4'][5]?$c_territory_type['type_4'][5]:'이름/호';
}elseif($row['tt_type'] == '추가1'){
  $address1_label = '주소';
  $address2_label = $c_territory_type['type_7'][4]?$c_territory_type['type_7'][4]:'';
  $address3_label = $c_territory_type['type_7'][5]?$c_territory_type['type_7'][5]:'';
}elseif($row['tt_type'] == '추가2'){
  $address1_label = $c_territory_type['type_8'][1]?$c_territory_type['type_8'][1]:'';
  $address2_label = $c_territory_type['type_8'][2]?$c_territory_type['type_8'][2]:'';
  $address3_label = $c_territory_type['type_8'][3]?$c_territory_type['type_8'][3]:'';
}else{
  $address1_label = '주소';
  $address2_label = '층';
  $address3_label = '이름/호';
}
?>

<div class="territory-view" tt_id="<?=$tt_id?>" update_page="<?=$update_page?>" update_wrap_id="<?=$update_wrap_id?>">
  <div class="territory-view-header">
    <div class="territory_header" >
      <p class="mb-0 text-center text-secondary">모든기록 실시간 저장 중</p>
      <div class="px-3 py-2 align-middle clearfix">
        <button type="button" class="btn btn-sm btn-outline-danger align-middle" onclick="memo_work('territory',<?=$tt_id?>);"><i class="bi bi-pencil"></i> 특이사항 전달</button>
        <button type="button" class="btn btn-sm btn-outline-primary mb-0 align-middle float-right" onclick="close_territory_view(<?=$tt_id?>);" name="button" data-dismiss="modal"><i class="bi bi-door-open"></i>나가기</button>
        <!-- <button type="button" name="button" onclick="close_territory_view(<?=$tt_id?>);" class="btn btn-sm btn-outline-secondary mb-0 align-middle float-right mr-2" data-dismiss="modal">나가기</button> -->
      </div>
      <div class="p-3 align-middle clearfix border-top" role="alert" data-toggle="collapse" href="#collapseView" aria-expanded="false" aria-controls="collapseView">
        <span class="align-middle h6" >
          [<?=$row['tt_num']?>] <?=$row['tt_name']?>
        </span>
        <i class="bi bi-caret-down-square-fill float-right"></i>
      </div>
    </div>

    <div class="collapse" id="collapseView">
      <table class="table table-sm m-0">
        <colgroup>
          <col width="100px">
          <col>
        </colgroup>
        <tbody>
          <tr>
            <th class="bg-light text-center align-middle">주소 1</th>
            <td>
            <span class="align-middle"><?=$row['tt_address']?></span>
            <?php if($row['tt_address']) echo kakao_menu($row['tt_address']);?>
            </td>
          </tr>
          <tr>
            <th class="bg-light text-center align-middle">주소 2</th>
            <td>
              <span class="align-middle"><?=$row['tt_address2']?></span>
              <?php if($row['tt_address2']) echo kakao_menu($row['tt_address2']);?>
            </td>
          </tr>
          <tr>
            <th class="bg-light text-center"><?=get_status_text($row['tt_status'])?></th>
            <td class="text-center"><?=!empty_date($row['tt_start_date'])?$row['tt_start_date']:'';?> ~ <?=!empty_date($row['tt_end_date'])?$row['tt_end_date']:'';?></td>
          </tr>
          <tr>
            <td colspan="2" class="text-center"><?=!empty($minister)?$minister:'-';?></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="px-3 py-2 align-left clearfix border-top">
      <button type="button" class="btn btn-sm btn-outline-secondary align-middle mb-0 mr-2" onclick="territory_work('record',<?=$tt_id?>,'','','');"><i class="bi bi-list-task"></i> 봉사 기록</button>
      <?php if($row['tt_polygon']): ?>
        <button type="button" onclick="map_view('territory', <?=$tt_id?>)" class="btn btn-sm btn-outline-secondary mb-0 align-middle">
        <i class="bi bi-geo-alt"></i> 지도보기
        </button>
      <?php endif; ?>
    </div>
    <table id="fixed-head" class="table table-sm">
      <colgroup>
        <col width="50px">
        <col>
        <col>
        <col width="<?=$row['tt_type']=='편지'?'100px':'50px'?>">
        <?php if($row['tt_type'] != '편지'): ?>
        <col width="50px">
        <?php endif; ?>
        <col width="50px">
      </colgroup>
      <thead class="thead-light">
        <tr>
          <th colspan="3"><?=$address1_label?></th>
          <?php if($row['tt_type'] == '편지'): ?>
            <th rowspan="2">발송</th>
          <?php else: ?>
            <th rowspan="2">만남</th>
          <?php endif; ?>
          <?php if($row['tt_type'] != '편지'): ?>
            <th rowspan="2">부재</th>
          <?php endif; ?>
          <th rowspan="2">특이<br>사항</th>
        </tr>
        <tr>
          <th>&nbsp;</th>
          <th><?=$address2_label?></th>
          <th><?=$address3_label?></th>
        </tr>
      </thead>
    </table>
  </div>
  <div class="territory-view-body">
    <table class="table">
      <colgroup>
        <col width="50px">
        <col>
        <col>
        <col width="<?=$row['tt_type']=='편지'?'100px':'50px'?>">
        <?php if($row['tt_type'] != '편지'): ?>
        <col width="50px">
        <?php endif; ?>
        <col width="50px">
      </colgroup>
      <tbody>
      </tbody>
    </table>
    <div class="loading_img">
      <img src="../img/preloader2.gif">
    </div>
  </div>
</div>

<script type="text/javascript">
var timeout = '';
function territory_view_update(silent = false){

  var tt_type = '<?=$row['tt_type']?>';
  var tt_id = $('#territory-view-modal .territory-view').attr('tt_id');
  $.ajax({
                    url: BASE_PATH+'/include/territory_view_list.php',
    data: {
      'tt_id': tt_id,
      'tt_type':tt_type
    },
    type: 'POST',
    dataType: 'html',
    beforeSend: function(xhr){
      if(!silent){
        $('.territory-view .territory-view-body table tbody').css('opacity','0.5');
        var h=($('.territory-view-body').height()/2)+$('.territory-view-body').scrollTop();
        $(".loading_img").css({'top':h-30});
        $('.loading_img').show();
      }
    },
    success: function(result){
      $('.territory-view .territory-view-body table tbody').html(result);
    },
    complete: function(xhr, textStatus){
      if(!silent){
        $('.loading_img').hide();
        $('.territory-view table tbody').css('opacity','1');
      }
    }
  });
  timeout = setTimeout(territory_view_update,20000);
}

$(document).ready(function(){
  territory_view_update();

  var height = $('.territory-view-header').outerHeight();
  $('.territory-view-body').css('top', height+'px');

  $( window ).bind("resize", function(){
    var height = $('.territory-view-header').outerHeight();
    $('.territory-view-body').css('top', height+'px');
  });

  $('#collapseView').on('shown.bs.collapse', function () {
    $('.territory-view-body').addClass('collapsed');
  });

  $('#collapseView').on('hidden.bs.collapse', function () {
    $('.territory-view-body').removeClass('collapsed');
  });

  $('#collapseView').on('show.bs.collapse', function () {
    $('.territory_header div i').removeClass('bi-caret-down-square-fill');
    $('.territory_header div i').addClass('bi-caret-up-square-fill');
  });

  $('#collapseView').on('hide.bs.collapse', function () {
    $('.territory_header div i').removeClass('bi-caret-up-square-fill');
    $('.territory_header div i').addClass('bi-caret-down-square-fill');
  });
});
</script>
