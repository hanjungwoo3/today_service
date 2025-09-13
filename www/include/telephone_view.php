<?php include_once('../config.php');?>

<?php
$c_territory_type = unserialize(TERRITORY_TYPE);

$sql = "SELECT * FROM ".TELEPHONE_TABLE." WHERE tp_id = {$tp_id}";
$result = $mysqli->query($sql);
$row = $result->fetch_assoc();

if(!empty_date($row['tp_assigned_date']) || $row['mb_id']){
  if($row['tp_assigned']){
    $assigned_group_arr = get_assigned_group_name($row['tp_assigned'],$row['tp_assigned_group']);
    $minister = (is_array($assigned_group_arr) == 1)?implode(' <span class="mx-1">|</span> ', $assigned_group_arr):$assigned_group_arr;
  }
  if($row['mb_id']) $minister = get_member_name($row['mb_id']);
}
?>

<div class="telephone-view" tp_id="<?=$tp_id?>" update_page="<?=$update_page?>" update_wrap_id="<?=$update_wrap_id?>">
  <div class="telephone-view-header">
    <div class="telephone_header">
      <p class="mb-0 text-center text-secondary">모든기록 실시간 저장 중</p>
      <div class="px-3 py-2 align-middle clearfix">
        <button type="button" class="btn btn-sm btn-outline-danger align-middle" onclick="memo_work('telephone',<?=$tp_id?>);"><i class="bi bi-pencil"></i> 특이사항 전달</button>
        <button type="button" class="btn btn-sm btn-outline-primary mb-0 align-middle float-right" onclick="close_telephone_view(<?=$tp_id?>);" name="button" data-dismiss="modal"><i class="bi bi-door-open"></i>나가기</button>
      </div>
      <div class="p-3 align-middle clearfix border-top" role="alert" data-toggle="collapse" href="#collapseView" aria-expanded="false" aria-controls="collapseView">
        <span class="align-middle h6" >[<?=$row['tp_num']?>] <?=$row['tp_name']?></span>
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
            <th class="bg-light text-center"><?=get_status_text($row['tp_status'])?></th>
            <td class="text-center"><?=!empty_date($row['tp_start_date'])?$row['tp_start_date']:'';?> ~ <?=!empty_date($row['tp_end_date'])?$row['tp_end_date']:'';?></td>
          </tr>
          <tr>
            <td colspan="2" class="text-center"><?=$minister?$minister:'-';?></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="px-3 py-2 align-left clearfix border-top">
      <button type="button" class="btn btn-sm btn-outline-secondary align-middle mb-0 mr-2" onclick="telephone_work('record',<?=$tp_id?>,'','','');"><i class="bi bi-list-task"></i> 봉사 기록</button>
    </div>
    <table id="fixed-head" class="table table-sm">
      <colgroup>
        <col width="120px">
        <col>
        <col width="50px">
        <col width="50px">
        <col width="50px">
      </colgroup>
      <thead class="thead-light">
        <tr>
          <th><?=$c_territory_type['type_6'][3]?$c_territory_type['type_6'][3]:'상호'?></th>
          <th><?=$c_territory_type['type_6'][2]?$c_territory_type['type_6'][2]:'업종'?></th>
          <th rowspan="2">만남</th>
          <th rowspan="2">부재</th>
          <th rowspan="2">특이<br>사항</th>
        </tr>
        <tr>
          <th colspan="2">&nbsp;</th>
        </tr>
      </thead>
    </table>
  </div>
  <div class="telephone-view-body">
    <table class="table">
      <colgroup>
        <col width="120px">
        <col>
        <col width="50px">
        <col width="50px">
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
function telephone_view_update(){
  var tp_id = $('#telephone-view-modal .telephone-view').attr('tp_id');
  $.ajax({
                    url: BASE_PATH+'/include/telephone_view_list.php',
    data: {
      'tp_id': tp_id
    },
    type: 'POST',
    dataType: 'html',
    beforeSend: function(xhr){
      $('.telephone-view .telephone-view-body table tbody').css('opacity','0.5');
      var h=($('.telephone-view-body').height()/2)+$('.telephone-view-body').scrollTop();
      $(".loading_img").css({'top':h-30});
      $('.loading_img').show();
    },
    success: function(result){
      $('.telephone-view .telephone-view-body table tbody').html(result);
    },
    complete: function(xhr, textStatus){
      $('.loading_img').hide();
      $('.telephone-view table tbody').css('opacity','1');
    }
  });
  timeout = setTimeout(telephone_view_update,20000);
}

$(document).ready(function(){
  telephone_view_update();

  var height = $('.telephone-view-header').outerHeight();
  $('.telephone-view-body').css('top', height+'px');

  $( window ).bind("resize", function(){
    var height = $('.telephone-view-header').outerHeight();
    $('.telephone-view-body').css('top', height+'px');
  });

  $('#collapseView').on('shown.bs.collapse', function () {
    $('.telephone-view-body').addClass('collapsed');
  });

  $('#collapseView').on('hidden.bs.collapse', function () {
    $('.telephone-view-body').removeClass('collapsed');
  });

  $('#collapseView').on('show.bs.collapse', function () {
    $('.telephone_header div i').removeClass('bi-caret-down-square-fill');
    $('.telephone_header div i').addClass('bi-caret-up-square-fill');
  });

  $('#collapseView').on('hide.bs.collapse', function () {
    $('.telephone_header div i').removeClass('bi-caret-up-square-fill');
    $('.telephone_header div i').addClass('bi-caret-down-square-fill');
  });
});
</script>
