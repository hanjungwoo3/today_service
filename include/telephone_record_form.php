<?php include_once('../config.php');?>

<?php
$tp_data = get_telephone_data($tp_id);

// get_all_past_records 함수를 사용하여 방문 기록 가져오기
$all_past_records = get_all_past_records('telephone', $tp_id);

// get_all_past_records의 결과를 visits 배열로 변환
$visits = array();
foreach($all_past_records as $visit_data) {
    $visit_type = $visit_data['visit'];
    $records = $visit_data['records'];
    
    // 각 레코드에 필요한 필드 추가
    $formatted_records = array();
    foreach($records as $record) {
        $formatted_records[] = array(
            'tpr_id' => isset($record['id']) ? $record['id'] : null,
            'tpr_assigned_date' => isset($record['assigned_date']) ? $record['assigned_date'] : '',
            'tpr_start_date' => isset($record['start_date']) ? $record['start_date'] : '',
            'tpr_end_date' => isset($record['end_date']) ? $record['end_date'] : '',
            'tpr_mb_name' => isset($record['assigned']) ? $record['assigned'] : '',
            'tpr_assigned' => isset($record['assigned']) ? $record['assigned'] : '',
            'is_current' => $record['table'] == 'telephone'?true:false
        );
    }
    
    $visits[] = array(
        'type' => $visit_type,
        'records' => $formatted_records
    );
}
?>

<h6>[<?=$tp_data['tp_num']?>] <?=$tp_data['tp_name']?></h6>
<form id="telephone_record_form">
  <input type="hidden" name="work" value="telephone_record">
  <input type="hidden" name="tp_id" value="<?=$tp_id?>">
  <div class="table-responsive">
    <table class="table table-bordered" style="min-width: 800px;">
      <colgroup>
        <col style="width:50px;">
        <col style="width:100px;">
        <col style="width:100px;">
        <col style="width:200px;">
        <col>
      </colgroup>
      <thead class="thead-light">
        <tr>
          <?php if(is_admin(mb_id())): ?>
          <th class="text-center">&nbsp;</th>
          <?php endif; ?>
          <th class="text-center">방문 구분</th>
          <th class="text-center">배정 날짜</th>
          <th class="text-center">봉사 날짜</th>
          <th class="text-center">전도인</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($visits as $visit): ?>
        <?php 
        $visit_type = $visit['type'];
        $record_count = count($visit['records']);
        ?>
        <?php foreach($visit['records'] as $index => $record): ?>
          <?php 
          $is_current = isset($record['is_current']) && $record['is_current'];
          ?>
          <tr class="<?=$is_current ? 'table-primary' : ''?>">
            <?php if(is_admin(mb_id())): ?>
            <td class="text-center align-middle">
              <?php if(!$is_current): ?>
                <input type="checkbox" name="telephone_record[<?=$record['tpr_id']?>][delete]" value="delete">
              <?php endif; ?>
            </td>
            <?php endif; ?>
            <?php if($index === 0): ?>
            <td class="text-center align-middle" rowspan="<?=$record_count?>">
              <?php if($visit_type == '부재'): ?>
                <i class="bi bi-person-fill-slash"></i> 부재
              <?php else: ?>
                <i class="bi bi-people-fill"></i> 전체
              <?php endif; ?>
            </td>
            <?php endif; ?>
            <td class="text-center align-middle">
              <?=empty_date($record['tpr_assigned_date'])?'-':date('y.m.d',strtotime($record['tpr_assigned_date']));?>
            </td>
            <td class="text-center align-middle">
              <?php 
              $start_date = empty_date($record['tpr_start_date']) ? '' : date('y.m.d', strtotime($record['tpr_start_date']));
              $end_date = empty_date($record['tpr_end_date']) ? '' : date('y.m.d', strtotime($record['tpr_end_date']));
              
              if($start_date && $end_date) {
                  echo $start_date . ' ~ ' . $end_date;
              } elseif($start_date) {
                  echo $start_date . ' ~ ';
              } elseif($end_date) {
                  echo ' ~ ' . $end_date;
              } else {
                  echo '-';
              }
              ?>
            </td>
            <td class="text-left align-middle">
              <?php 
              if($is_current) {
                  $assigned_member_name = $record['tpr_mb_name'];
                  echo implode(', ', filter_assigned_member_array($assigned_member_name));
              } else {
                  echo $record['tpr_mb_name'] ? $record['tpr_mb_name'] : $record['tpr_assigned'];
              }
              ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if(is_admin(mb_id())): ?>
  <div class="text-right mt-3">
     <button type="submit" class="btn btn-outline-danger align-middle text-center"><i class="bi bi-trash"></i> 선택한 기록 삭제</button>
  </div>
  <?php endif; ?>
</form>
