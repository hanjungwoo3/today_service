<?php
include_once('../config.php');

$territory = new Territory($mysqli);
$telephone = new Telephone($mysqli);

if ($work) {

  if ($work == 'house_visit_update') { // HOUSE 만남 체크

    if ($table == 'territory') {

      $sql = "SELECT tt_id FROM " . HOUSE_TABLE . " WHERE h_id = {$pid}";
      $result = $mysqli->query($sql);
      $row = $result->fetch_assoc();
      $tt_id = $row['tt_id'];

      $sql = "SELECT tt_start_date, tt_assigned_date, mb_id FROM " . TERRITORY_TABLE . " WHERE tt_id = {$tt_id}";
      $result = $mysqli->query($sql);
      $row = $result->fetch_assoc();
      $tt_start_date = $row['tt_start_date'];
      $tt_assigned_date = $row['tt_assigned_date'];
      $mb_id = $row['mb_id'];

      // 방문 체크 업데이트
      $sql = "UPDATE " . HOUSE_TABLE . " SET h_visit = '{$visit}' WHERE h_id = {$pid}";
      $mysqli->query($sql);

      // 배정받은 날짜가 있거나 나의 개인구역일때만 시작날짜/마친날짜 업데이트
      if (($tt_assigned_date && !empty_date($tt_assigned_date)) || $mb_id == mb_id()) {

        // 시작날짜가 없다면 시작날짜를 오늘 날짜로
        if (empty($tt_start_date) || empty_date($tt_start_date)) {
          $tt_start_date_value = date("Y-m-d");
          $updateData = array(
            'tt_start_date' => $tt_start_date_value,
          );
          $updateId = $territory->update($tt_id, $updateData);
        }

        // 진행률이 90% 이상이면 완료날짜 표시
        $territory_complete_percent = TERRITORY_COMPLETE_PERCENT ? TERRITORY_COMPLETE_PERCENT : '90';
        $territory_progress = get_territory_progress($tt_id);
        $effective_total = $territory_progress['total'] - $territory_progress['condition'];
        $territory_progress_percent = ($effective_total > 0) ? floor((($territory_progress['visit'] + $territory_progress['absence']) / $effective_total) * 100) : 0;
        if ($territory_progress_percent >= $territory_complete_percent) {
          $tt_end_date_value = date("Y-m-d");
          $updateData = array(
            'tt_end_date' => $tt_end_date_value,
          );
          $updateId = $territory->update($tt_id, $updateData);
        }

      }

    } elseif ($table == 'telephone') {

      $sql = "SELECT tp_id FROM " . TELEPHONE_HOUSE_TABLE . " WHERE tph_id = {$pid}";
      $result = $mysqli->query($sql);
      $row = $result->fetch_assoc();
      $tp_id = $row['tp_id'];

      $sql = "SELECT tp_start_date, tp_assigned_date, mb_id FROM " . TELEPHONE_TABLE . " WHERE tp_id = {$tp_id}";
      $result = $mysqli->query($sql);
      $row = $result->fetch_assoc();
      $tp_start_date = $row['tp_start_date'];
      $tp_assigned_date = $row['tp_assigned_date'];
      $mb_id = $row['mb_id'];

      // 방문 체크 업데이트
      $sql = "UPDATE " . TELEPHONE_HOUSE_TABLE . " SET tph_visit = '{$visit}' WHERE tph_id = {$pid}";
      $mysqli->query($sql);

      // 배정받은 날짜가 있거나 나의 개인구역일때만 시작날짜/마친날짜 업데이트
      if (($tp_assigned_date && !empty_date($tp_assigned_date)) || $mb_id == mb_id()) {

        // 시작날짜가 없다면 시작날짜를 오늘 날짜로
        if (empty($tp_start_date) || empty_date($tp_start_date)) {
          $tp_start_date_value = date("Y-m-d");
          $updateData = array(
            'tp_start_date' => $tp_start_date_value,
          );
          $updateId = $telephone->update($tp_id, $updateData);
        }

        // 진행률이 90% 이상이면 완료날짜 표시
        $territory_complete_percent = TERRITORY_COMPLETE_PERCENT ? TERRITORY_COMPLETE_PERCENT : '90';
        $telephone_progress = get_telephone_progress($tp_id);
        $effective_total = $telephone_progress['total'] - $telephone_progress['condition'];
        $telephone_progress_percent = ($effective_total > 0) ? floor((($telephone_progress['visit'] + $telephone_progress['absence']) / $effective_total) * 100) : 0;
        if ($telephone_progress_percent >= $territory_complete_percent) {
          $tp_end_date_value = date("Y-m-d");
          $updateData = array(
            'tp_end_date' => $tp_end_date_value,
          );
          $updateId = $telephone->update($tp_id, $updateData);
        }

      }

    }

  } elseif ($work == 'show_tel_info') { // 전화구역 상세 정보보기

    $c_territory_type = unserialize(TERRITORY_TYPE);
    $sql = "SELECT * FROM " . TELEPHONE_HOUSE_TABLE . " WHERE tph_id = {$tph_id}";
    $result = $mysqli->query($sql);
    $row = $result->fetch_assoc();

    ob_start();
    ?>
    <table class="table table-bordered mb-0">
      <tbody>
        <tr>
          <th class="bg-light"><?= $c_territory_type['type_6'][3] ? $c_territory_type['type_6'][3] : '상호' ?></th>
          <td><?= $row['tph_name'] ?></td>
        </tr>
        <tr>
          <th class="bg-light">전화번호</th>
          <td><?= $row['tph_number'] ?></td>
        </tr>
        <tr>
          <th class="bg-light">주소</th>
          <td><?= $row['tph_address'] ?></td>
        </tr>
        <tr>
          <th class="bg-light"><?= $c_territory_type['type_6'][2] ? $c_territory_type['type_6'][2] : '업종' ?></th>
          <td><?= $row['tph_type'] ?></td>
        </tr>
      </tbody>
    </table>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    echo $html;

  } elseif ($work == 'territory_map_reset') {

    $sql = "UPDATE " . OPTION_TABLE . " SET value = '' WHERE name = 'territory_boundary'";
    $mysqli->query($sql);

  } elseif ($work == 'territory_reset') {
    // 중요: territory_reset() 함수는 봉사기록(TERRITORY_RECORD_TABLE)을 삭제하지 않으며,
    // 리셋 전의 봉사 정보를 봉사기록 테이블에 저장합니다.
    territory_reset($tt_id);
    territory_house_reset($tt_id);
    insert_work_log('territory_reset:' . $tt_id);

  } elseif ($work == 'telephone_reset') {
    // 중요: telephone_reset() 함수는 봉사기록(TELEPHONE_RECORD_TABLE)을 삭제하지 않으며,
    // 리셋 전의 봉사 정보를 봉사기록 테이블에 저장합니다.
    telephone_reset($tp_id);
    telephone_house_reset($tp_id);
    insert_work_log('telephone_reset:' . $tp_id);

  } elseif ($work == 'logout') { // 로그아웃

    setcookie("jw_ministry", "", time() - 3600);
    unset($_SESSION['mb_id']);

  } elseif ($work == 'memo') { // 구역 메모

    if ($table == 'territory') {

      $updateData = array(
        'tt_memo' => $memo,
      );
      $updateId = $territory->update($pid, $updateData);

    } elseif ($table == 'telephone') {

      $updateData = array(
        'tp_memo' => $memo,
      );
      $updateId = $telephone->update($pid, $updateData);

    }

  } elseif ($work == 'site_option_update') { // 사이트 옵션 업데이트
    set_site_option('site_name', $site_name);
    set_site_option('default_address', $default_address);
    set_site_option('default_location', $default_location);
    set_site_option('absence_use', $absence_use);
    set_site_option('display_use', $display_use);
    set_site_option('duplicate_attend_limit', $duplicate_attend_limit);
    set_site_option('attend_use', $attend_use);
    set_site_option('attend_display_use', $attend_display_use);
    set_site_option('minister_attend_use', $minister_attend_use);
    set_site_option('minister_display_attend_use', $minister_display_attend_use);
    set_site_option('minister_schedule_event_use', $minister_schedule_event_use);
    set_site_option('minister_schedule_report_use', $minister_schedule_report_use);
    set_site_option('minister_statistics_use', $minister_statistics_use);
    set_site_option('guide_statistics_use', $guide_statistics_use);
    set_site_option('territory_boundary', $territory_boundary);
    set_site_option('returnvisit_use', $returnvisit_use);
    set_site_option('attend_before', $attend_before);
    set_site_option('attend_after', $attend_after);
    set_site_option('attend_display_before', $attend_display_before);
    set_site_option('attend_display_after', $attend_display_after);
    set_site_option('meeting_schedule_type_attend_limit', serialize($meeting_schedule_type_attend_limit));
    set_site_option('guide_card_order', $guide_card_order);
    set_site_option('map_api_key', $map_api_key);
    set_site_option('board_item_per_page', $board_item_per_page);
    set_site_option('guide_meeting_contents', $guide_meeting_contents);
    set_site_option('guide_meeting_contents_use', $guide_meeting_contents_use);
    set_site_option('guide_appoint_use', $guide_appoint_use);
    set_site_option('guide_assigned_group_use', $guide_assigned_group_use);
    set_site_option('territory_type', serialize($territory_type));
    set_site_option('territory_type_use', serialize($territory_type_use));
    set_site_option('meeting_schedule_type', serialize($meeting_schedule_type));
    set_site_option('meeting_schedule_type_use', serialize($meeting_schedule_type_use));
    set_site_option('territory_complete_percent', $territory_complete_percent);
    set_site_option('house_condition', serialize($house_condition));
    set_site_option('house_condition_use', serialize($house_condition_use));
    set_site_option('return_visit_expiration', $return_visit_expiration);
    set_site_option('territory_item_per_page', $territory_item_per_page);
    set_site_option('admin_territory_sort', $admin_territory_sort);
    set_site_option('minister_assign_expiration', $minister_assign_expiration);
    set_site_option('minister_telephone_assign_expiration', $minister_telephone_assign_expiration);
    set_site_option('minister_letter_assign_expiration', $minister_letter_assign_expiration);
    set_site_option('show_attend_use', $show_attend_use);

  } elseif ($work == 'territory_start') {

    if (!empty($table) && !empty($id)) {
      if ($table == 'territory') {

        $sql = "SELECT tt_start_date FROM " . TERRITORY_TABLE . " WHERE tt_id = {$id}";
        $result = $mysqli->query($sql);
        $row = $result->fetch_assoc();
        $tt_start_date = $row['tt_start_date'];

        // 시작날짜가 없다면 시작날짜를 오늘 날짜로
        if (empty($tt_start_date) || empty_date($tt_start_date)) {
          $tt_start_date_value = date("Y-m-d");
          $updateData = array(
            'tt_start_date' => $tt_start_date_value,
          );
          $updateId = $territory->update($id, $updateData);
          echo json_encode(array('success' => true, 'updateId' => $updateId));
        } else {
          echo json_encode(array('success' => false, 'message' => 'Already started'));
        }

      } elseif ($table == 'telephone') {

        $sql = "SELECT tp_start_date FROM " . TELEPHONE_TABLE . " WHERE tp_id = {$id}";
        $result = $mysqli->query($sql);
        $row = $result->fetch_assoc();
        $tp_start_date = $row['tp_start_date'];

        // 시작날짜가 없다면 시작날짜를 오늘 날짜로
        if (empty($tp_start_date) || empty_date($tp_start_date)) {
          $tp_start_date_value = date("Y-m-d");
          $updateData = array(
            'tp_start_date' => $tp_start_date_value,
          );
          $updateId = $telephone->update($id, $updateData);
          echo json_encode(array('success' => true, 'updateId' => $updateId));
        } else {
          echo json_encode(array('success' => false, 'message' => 'Already started'));
        }

      }
    }

  }

}
?>