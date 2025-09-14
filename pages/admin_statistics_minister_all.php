<?php include_once('../config.php');?>

<?php
$c_meeting_schedule_type_use = unserialize(MEETING_SCHEDULE_TYPE_USE);
$where = array();
$hminister_array = array();
$jminister_array = array();
$today = date("Y-m-d");
$yearmonth = date("Y-m");
$yearmonth_string = date("Y년 n월");
$fday = date("Y-m-01"); //이번달 1일
$eday = date("Y-m-d"); //이번달 현재 마지막날

if(!empty($mb_month)){  //날짜
  $mb_yearmonth = date("Y-m", strtotime($mb_month));
  $mb_yearmonth_string = date("Y년 n월", strtotime($mb_month));
  if($yearmonth !=  $mb_yearmonth){
    $yearmonth = $mb_yearmonth;
    $yearmonth_string = $mb_yearmonth_string;
    $fday = date("Y-m-01", strtotime($mb_month));
    $eday = date("Y-m-t", strtotime($mb_month));
  }
}
$month_where = "m_date >= '{$fday}' AND m_date <= '{$eday}' AND";

$where[] = "mb_position != '3'"; //순회감독자 제외
if(!empty($mb_sex)){
  $where[] = "mb_sex = '".$mb_sex."'"; //형제-자매
}
$where = $where?'WHERE '.implode(' AND ',$where):'';

//total
$sql = "SELECT count(mb_id) as total FROM ".MEMBER_TABLE." ".$where;
$result = $mysqli->query($sql);
$row = $result->fetch_assoc();

$minister_count_array = array();

$mb_sql = "SELECT mb_id, mb_name, mb_movein_date, mb_moveout_date FROM ".MEMBER_TABLE." ".$where." ORDER BY mb_name";
$mb_result = $mysqli->query($mb_sql);
while ($mow = $mb_result->fetch_assoc()){
  if(empty_date($mow['mb_moveout_date'])) $mow['mb_moveout_date'] = '2300-01-01';
  $mb_moveout_date = date("Y-m", strtotime($mow['mb_moveout_date']));
  if($eday >= $mow['mb_movein_date'] && $yearmonth <= $mb_moveout_date){
    $m_sql = "SELECT * FROM (SELECT
    (SELECT count(DISTINCT m_date) as count FROM ".MEETING_TABLE." WHERE FIND_IN_SET({$mow['mb_id']},mb_id) AND ".$month_where." ms_type = '1' AND m_cancle = '0') 1count,
    (SELECT count(DISTINCT m_date) as count FROM ".MEETING_TABLE." WHERE FIND_IN_SET({$mow['mb_id']},mb_id) AND ".$month_where." ms_type = '2' AND m_cancle = '0') 2count,
    (SELECT count(DISTINCT m_date) as count FROM ".MEETING_TABLE." WHERE FIND_IN_SET({$mow['mb_id']},mb_id) AND ".$month_where." ms_type = '3' AND m_cancle = '0') 3count,
    (SELECT count(DISTINCT m_date) as count FROM ".MEETING_TABLE." WHERE FIND_IN_SET({$mow['mb_id']},mb_id) AND ".$month_where." ms_type = '4' AND m_cancle = '0') 4count,
    (SELECT count(DISTINCT m_date) as count FROM ".MEETING_TABLE." WHERE FIND_IN_SET({$mow['mb_id']},mb_id) AND ".$month_where." ms_type = '5' AND m_cancle = '0') 5count,
    (SELECT count(DISTINCT m_date) as count FROM ".MEETING_TABLE." WHERE FIND_IN_SET({$mow['mb_id']},mb_id) AND ".$month_where." ms_type = '6' AND m_cancle = '0') 6count
    ) T;";
    $m_result = $mysqli->query($m_sql);
    $minister = $m_result->fetch_assoc();

    $minister_count_array[1][$minister['1count']][] = $mow['mb_name'];
    $minister_count_array[2][$minister['2count']][] = $mow['mb_name'];
    $minister_count_array[3][$minister['3count']][] = $mow['mb_name'];
    $minister_count_array[4][$minister['4count']][] = $mow['mb_name'];
    $minister_count_array[5][$minister['5count']][] = $mow['mb_name'];
    $minister_count_array[6][$minister['6count']][] = $mow['mb_name'];

  }
}
?>

<h6 class=" text-secondary "><?=$yearmonth_string?> 호별</h6>

<table class="table table-bordered mb-5">
  <colgroup>
    <col style="width:90px;">
    <col style="width:90px;">
  </colgroup>
  <thead class="thead-light text-center">
    <tr>
      <th scope="col">참여 횟수</th>
      <th scope="col">전도인 수</th>
      <th scope="col">전도인 이름</th>
    </tr>
  </thead>
  <tbody class="text-center">
    <?php if(!empty($minister_count_array[1])): ksort($minister_count_array[1]); ?>
      <?php foreach ($minister_count_array[1] as $key => $value) :?>
        <tr>
          <th scope="row" class="bg-light align-middle"><?=$key?>회</th>
          <td class="align-middle">
            <div><?=count($value)?>명</div>
            <small class="text-muted">(<?=get_percent(count($value), $row['total']).'%';?>)</small>
          </td>
          <td class="text-left text-muted align-middle"><?=implode(", ",$value)?></td>
        </tr>
      <?php endforeach;?>
    <?php endif; ?>
  </tbody>
</table>

<h6 class=" text-secondary "><?=$yearmonth_string?> 전시대</h6>

<table class="table table-bordered mb-5">
  <colgroup>
    <col style="width:90px;">
    <col style="width:90px;">
  </colgroup>
  <thead class="thead-light text-center">
    <tr>
      <th scope="col">참여 횟수</th>
      <th scope="col">전도인 수</th>
      <th scope="col">전도인 이름</th>
    </tr>
  </thead>
  <tbody class="text-center">
  <?php if(!empty($minister_count_array[2])): ksort($minister_count_array[2]); ?>
      <?php foreach ($minister_count_array[2] as $key => $value) :?>
        <tr>
          <th scope="row" class="bg-light align-middle"><?=$key?>회</th>
          <td class="align-middle">
            <div><?=count($value)?>명</div>
            <small class="text-muted">(<?=get_percent(count($value), $row['total']).'%';?>)</small>
          </td>
          <td class="text-left text-muted align-middle"><?=implode(", ",$value)?></td>
        </tr>
      <?php endforeach;?>
    <?php endif; ?>
  </tbody>
</table>

<?php if(!isset($c_meeting_schedule_type_use[3]) || $c_meeting_schedule_type_use[3] === 'use'): ?>

<h6 class=" text-secondary "><?=$yearmonth_string?> <?=get_meeting_schedule_type_text(3)?></h6>

<table class="table table-bordered mb-5">
  <colgroup>
    <col style="width:90px;">
    <col style="width:90px;">
  </colgroup>
  <thead class="thead-light text-center">
    <tr>
      <th scope="col">참여 횟수</th>
      <th scope="col">전도인 수</th>
      <th scope="col">전도인 이름</th>
    </tr>
  </thead>
  <tbody class="text-center">
    <?php if(!empty($minister_count_array[3])): ksort($minister_count_array[3]); ?>
      <?php foreach ($minister_count_array[3] as $key => $value) :?>
        <tr>
          <th scope="row" class="bg-light align-middle"><?=$key?>회</th>
          <td class="align-middle">
            <div><?=count($value)?>명</div>
            <small class="text-muted">(<?=get_percent(count($value), $row['total']).'%';?>)</small>
          </td>
          <td class="text-left text-muted align-middle"><?=implode(", ",$value)?></td>
        </tr>
      <?php endforeach;?>
    <?php endif; ?>
  </tbody>
</table>

<?php endif; ?>
<?php if(!isset($c_meeting_schedule_type_use[4]) || $c_meeting_schedule_type_use[4] === 'use'): ?>

<h6 class=" text-secondary "><?=$yearmonth_string?> <?=get_meeting_schedule_type_text(4)?></h6>

<table class="table table-bordered mb-5">
  <colgroup>
    <col style="width:90px;">
    <col style="width:90px;">
  </colgroup>
  <thead class="thead-light text-center">
    <tr>
      <th scope="col">참여 횟수</th>
      <th scope="col">전도인 수</th>
      <th scope="col">전도인 이름</th>
    </tr>
  </thead>
  <tbody class="text-center">
    <?php if(!empty($minister_count_array[4])): ksort($minister_count_array[4]); ?>
      <?php foreach ($minister_count_array[4] as $key => $value) :?>
        <tr>
          <th scope="row" class="bg-light align-middle"><?=$key?>회</th>
          <td class="align-middle">
            <div><?=count($value)?>명</div>
            <small class="text-muted">(<?=get_percent(count($value), $row['total']).'%';?>)</small>
          </td>
          <td class="text-left text-muted align-middle"><?=implode(", ",$value)?></td>
        </tr>
      <?php endforeach;?>
    <?php endif; ?>
  </tbody>
</table>

<?php endif; ?>
<?php if(!isset($c_meeting_schedule_type_use[5]) || $c_meeting_schedule_type_use[5] === 'use'): ?>

<h6 class=" text-secondary "><?=$yearmonth_string?> <?=get_meeting_schedule_type_text(5)?></h6>

<table class="table table-bordered mb-5">
  <colgroup>
    <col style="width:90px;">
    <col style="width:90px;">
  </colgroup>
  <thead class="thead-light text-center">
    <tr>
      <th scope="col">참여 횟수</th>
      <th scope="col">전도인 수</th>
      <th scope="col">전도인 이름</th>
    </tr>
  </thead>
  <tbody class="text-center">
    <?php if(!empty($minister_count_array[5])): ksort($minister_count_array[5]); ?>
      <?php foreach ($minister_count_array[5] as $key => $value) :?>
        <tr>
          <th scope="row" class="bg-light align-middle"><?=$key?>회</th>
          <td class="align-middle">
            <div><?=count($value)?>명</div>
            <small class="text-muted">(<?=get_percent(count($value), $row['total']).'%';?>)</small>
          </td>
          <td class="text-left text-muted align-middle"><?=implode(", ",$value)?></td>
        </tr>
      <?php endforeach;?>
    <?php endif; ?>
  </tbody>
</table>

<?php endif; ?>
<?php if(!isset($c_meeting_schedule_type_use[6]) || $c_meeting_schedule_type_use[6] === 'use'): ?>

<h6 class=" text-secondary "><?=$yearmonth_string?> <?=get_meeting_schedule_type_text(6)?></h6>

<table class="table table-bordered mb-5">
  <colgroup>
    <col style="width:90px;">
    <col style="width:90px;">
  </colgroup>
  <thead class="thead-light text-center">
    <tr>
      <th scope="col">참여 횟수</th>
      <th scope="col">전도인 수</th>
      <th scope="col">전도인 이름</th>
    </tr>
  </thead>
  <tbody class="text-center">
    <?php if(!empty($minister_count_array[6])): ksort($minister_count_array[6]); ?>
      <?php foreach ($minister_count_array[6] as $key => $value) :?>
        <tr>
          <th scope="row" class="bg-light align-middle"><?=$key?>회</th>
          <td class="align-middle">
            <div><?=count($value)?>명</div>
            <small class="text-muted">(<?=get_percent(count($value), $row['total']).'%';?>)</small>
          </td>
          <td class="text-left text-muted align-middle"><?=implode(", ",$value)?></td>
        </tr>
      <?php endforeach;?>
    <?php endif; ?>
  </tbody>
</table>
<?php endif; ?>
