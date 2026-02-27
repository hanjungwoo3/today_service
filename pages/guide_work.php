<?php
include_once('../config.php');

$territory = new Territory($mysqli);
$telephone = new Telephone($mysqli);

if ($work) {

  if ($work == 'assign') { // 구역 배정

    $assigned_date = get_meeting_date($m_id);
    $assigned_member = implode(',', $member);

    // 배정 알림 대상 수집
    $notify_territories = [];
    $notify_displays = [];

    if (!empty($territories)) {
      foreach ($territories as $id) {

        $sql = "SELECT tt_status, m_id, tt_assigned_date, tt_end_date FROM " . TERRITORY_TABLE . " WHERE tt_id = {$id} LIMIT 1";
        $result = $mysqli->query($sql);
        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {

            if (!empty($row['m_id']) && $row['m_id'] == $m_id) { // 구역이 배정되어있고, 배정된 모임 아이디가 현재 모임 아이디와 같을 때 (즉 당일의 구역을 재배정 할때)

              $updateData = array(
                'tt_assigned' => $assigned_member,
                'tt_assigned_group' => $assigned_group,
              );
              $updateId = $territory->update($id, $updateData);

            } else { // 오늘이 아닌 이전에 배정되었던 구역, 또는 배정이 된 적 없는 구역

              if ($row['tt_assigned_date'] == '0000-00-00' && empty($row['tt_status'])) { // 미배정

                $updateData = array(
                  'tt_assigned' => $assigned_member,
                  'tt_assigned_date' => $assigned_date,
                  'tt_start_date' => '0000-00-00',
                  'tt_end_date' => '0000-00-00',
                  'tt_status' => '',
                  'm_id' => $m_id,
                  'tt_assigned_group' => $assigned_group,
                );
                $updateId = $territory->update($id, $updateData);

              } else { // 1차배정, 재배정, 부재자

                // 이전 상태 저장 (territory_reset 호출 전에)
                $old_status = $row['tt_status'];
                // 완료 여부 저장 (territory_reset 호출 전에)
                $is_completed = !empty($row['tt_end_date']) && $row['tt_end_date'] != '0000-00-00';

                if ($is_completed) { // 구역 완료
                  $new_status = (ABSENCE_USE == 'use') ? 'absence' : 'reassign';
                } else { // 구역 미완료
                  if (ABSENCE_USE == 'use' && $row['tt_status'] == 'absence') { // 부재자 였다면, 미완료 라도 부재자 그대로 봉사할 수 있도록...
                    $new_status = 'absence_reassign';
                  } else {
                    $new_status = 'reassign';
                  }
                }

                territory_reset($id, $new_status, $m_id);

                $updateData = array(
                  'tt_assigned' => $assigned_member,
                  'tt_assigned_date' => $assigned_date,
                  'm_id' => $m_id,
                  'tt_assigned_group' => $assigned_group,
                );
                $updateId = $territory->update($id, $updateData);

              }

              // 이전 상태와 완료 여부를 전달하여 체크박스 비우기 여부 결정
              if (isset($old_status)) {
                territory_house_update($id, '', $new_status, $old_status, isset($is_completed) ? $is_completed : false);
              } else {
                territory_house_update($id, '', $new_status);
              }

            }

          }
        }
        $notify_territories[] = intval($id);
      }
    }

    if (!empty($telephones)) {
      foreach ($telephones as $id) {

        $sql = "SELECT tp_status, m_id, tp_assigned_date, tp_end_date FROM " . TELEPHONE_TABLE . " WHERE tp_id = {$id} LIMIT 1";
        $result = $mysqli->query($sql);
        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {

            if ($row['m_id'] && $row['m_id'] == $m_id) {

              $updateData = array(
                'tp_assigned' => $assigned_member,
                'tp_assigned_group' => $assigned_group,
              );
              $updateId = $telephone->update($id, $updateData);

            } else {

              if ($row['tp_assigned_date'] == '0000-00-00' && empty($row['tp_status'])) { // 미배정

                $updateData = array(
                  'tp_assigned' => $assigned_member,
                  'tp_assigned_date' => $assigned_date,
                  'tp_start_date' => '0000-00-00',
                  'tp_end_date' => '0000-00-00',
                  'tp_status' => '',
                  'm_id' => $m_id,
                  'tp_assigned_group' => $assigned_group,
                );
                $updateId = $telephone->update($id, $updateData);

              } else { // 1차배정, 재배정, 부재자

                // 이전 상태 저장 (telephone_reset 호출 전에)
                $old_status = $row['tp_status'];
                // 완료 여부 저장 (telephone_reset 호출 전에)
                $is_completed = !empty($row['tp_end_date']) && $row['tp_end_date'] != '0000-00-00';

                if ($is_completed) { // 구역 완료
                  $new_status = (ABSENCE_USE == 'use') ? 'absence' : 'reassign';
                } else { // 구역 미완료
                  if (ABSENCE_USE == 'use' && $row['tp_status'] == 'absence') { // 부재자 였다면, 미완료 라도 부재자 그대로 봉사할 수 있도록...
                    $new_status = 'absence_reassign';
                  } else {
                    $new_status = 'reassign';
                  }
                }

                telephone_reset($id, $new_status, $m_id);

                $updateData = array(
                  'tp_assigned' => $assigned_member,
                  'tp_assigned_date' => $assigned_date,
                  'm_id' => $m_id,
                  'tp_assigned_group' => $assigned_group,
                );
                $updateId = $telephone->update($id, $updateData);

              }

              // 이전 상태와 완료 여부를 전달하여 체크박스 비우기 여부 결정
              if (isset($old_status)) {
                telephone_house_update($id, '', $new_status, $old_status, isset($is_completed) ? $is_completed : false);
              } else {
                telephone_house_update($id, '', $new_status);
              }

            }

          }
        }
      }
    }

    if (!empty($displays)) {
      foreach ($displays as $id) {

        $explode_id = explode('_', $id);

        $sql = "SELECT dp_name, dp_address FROM " . DISPLAY_PLACE_TABLE . " WHERE dp_id = {$explode_id[0]} LIMIT 1";
        $result = $mysqli->query($sql);
        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {

            $d_sql = "SELECT d_id FROM " . DISPLAY_TABLE . " WHERE dp_id = {$explode_id[0]} AND dp_num = {$explode_id[1]} AND m_id = {$m_id} LIMIT 1";
            $d_result = $mysqli->query($d_sql);

            if ($d_result->num_rows > 0) {
              while ($d_row = $d_result->fetch_assoc()) {
                $sql = "UPDATE " . DISPLAY_TABLE . " SET d_assigned = '{$assigned_member}', d_assigned_group = '{$assigned_group}' WHERE d_id = {$d_row['d_id']}";
                $mysqli->query($sql);
              }
            } else {
              $sql = "INSERT INTO " . DISPLAY_TABLE . " (d_assigned, d_assigned_date, dp_id, m_id, dp_address, dp_name, d_assigned_group, dp_num) VALUES ('{$assigned_member}', '{$assigned_date}', '{$explode_id[0]}', '{$m_id}', '{$row['dp_address']}', '{$row['dp_name']}', '{$assigned_group}', {$explode_id[1]})";
              $mysqli->query($sql);
            }

          }
        }
        // display의 d_id를 수집 (마지막 INSERT/UPDATE 결과)
        $d_sql2 = "SELECT d_id FROM " . DISPLAY_TABLE . " WHERE dp_id = {$explode_id[0]} AND dp_num = {$explode_id[1]} AND m_id = {$m_id} LIMIT 1";
        $d_res2 = $mysqli->query($d_sql2);
        if ($d_res2 && $d_res2->num_rows > 0) {
          $notify_displays[] = intval($d_res2->fetch_assoc()['d_id']);
        }
      }
    }

    // 배정 완료 시스템 메시지 발송
    _send_assign_notification($notify_territories, 'T', $assigned_member, $assigned_group);
    _send_assign_notification($notify_displays, 'D', $assigned_member, $assigned_group);

  } elseif ($work == 'assign_cancel') { // 구역 배정 취소
    if ($table == 'territory') {
      $sql = "SELECT tt_status FROM " . TERRITORY_TABLE . " WHERE tt_id = {$pid}";
      $current_res = $mysqli->query($sql);
      $current_status = $current_res->num_rows > 0 ? $current_res->fetch_assoc()['tt_status'] : '';

      // 배정취소시 구역기록도 복구되도록
      $sql = "SELECT ttr_id, ttr_assigned_num, ttr_assigned_date, ttr_assigned_group, ttr_start_date, ttr_end_date, m_id, ttr_status FROM " . TERRITORY_RECORD_TABLE . " WHERE tt_id = {$pid} AND record_m_id = {$m_id} ORDER BY ttr_id DESC LIMIT 1";
      $result = $mysqli->query($sql);
      if ($result->num_rows > 0) { // 오늘 배정한 모임중 지난 배정기록이 있으면
        $row = $result->fetch_assoc();
        $target_status = $row['ttr_status'];
        $was_completed = !empty($row['ttr_end_date']) && $row['ttr_end_date'] != '0000-00-00';

        $updateData = array(
          'tt_assigned' => $row['ttr_assigned_num'],
          'tt_assigned_date' => $row['ttr_assigned_date'],
          'tt_assigned_group' => $row['ttr_assigned_group'],
          'tt_start_date' => $row['ttr_start_date'],
          'tt_end_date' => $row['ttr_end_date'],
          'm_id' => $row['m_id'],
          'tt_status' => $row['ttr_status'],
        );
        $updateId = $territory->update($pid, $updateData);

        $sql = "DELETE FROM " . TERRITORY_RECORD_TABLE . " WHERE ttr_id = {$row['ttr_id']}";
        $mysqli->query($sql);
      } else {

        $updateData = array(
          'tt_assigned' => '',
          'tt_assigned_date' => '0000-00-00',
          'tt_assigned_group' => '',
          'tt_start_date' => '0000-00-00',
          'tt_end_date' => '0000-00-00',
          'm_id' => 0,
        );
        $updateId = $territory->update($pid, $updateData);

      }
      if (isset($was_completed)) {
        $old_is_absence = !empty($target_status) && strpos($target_status, 'absence') !== false;
        $new_is_absence = !empty($current_status) && strpos($current_status, 'absence') !== false;
        if ((!$old_is_absence && $new_is_absence) || ($old_is_absence && $new_is_absence && $was_completed)) {
          territory_house_update($pid, 'restore');
        }
      }
    } elseif ($table == 'display') {
      $explode_id = explode('_', $pid);
      $sql = "DELETE FROM " . DISPLAY_TABLE . " WHERE dp_id = {$explode_id[0]} AND dp_num = {$explode_id[1]} AND m_id = {$m_id}";
      $mysqli->query($sql);
    } elseif ($table == 'telephone') {
      $sql = "SELECT tp_status FROM " . TELEPHONE_TABLE . " WHERE tp_id = {$pid}";
      $current_res = $mysqli->query($sql);
      $current_status = $current_res->num_rows > 0 ? $current_res->fetch_assoc()['tp_status'] : '';

      // 배정취소시 구역기록도 복구되도록
      $sql = "SELECT tpr_id, tpr_assigned_num, tpr_assigned_date, tpr_assigned_group, tpr_start_date, tpr_end_date, m_id, tpr_status FROM " . TELEPHONE_RECORD_TABLE . " WHERE tp_id = {$pid} AND record_m_id = {$m_id} ORDER BY tpr_id DESC LIMIT 1";
      $result = $mysqli->query($sql);
      if ($result->num_rows > 0) { // 오늘 배정한 모임중 지난 배정기록이 있으면
        $row = $result->fetch_assoc();
        $target_status = $row['tpr_status'];
        $was_completed = !empty($row['tpr_end_date']) && $row['tpr_end_date'] != '0000-00-00';

        $updateData = array(
          'tp_assigned' => $row['tpr_assigned_num'],
          'tp_assigned_date' => $row['tpr_assigned_date'],
          'tp_assigned_group' => $row['tpr_assigned_group'],
          'tp_start_date' => $row['tpr_start_date'],
          'tp_end_date' => $row['tpr_end_date'],
          'm_id' => $row['m_id'],
          'tp_status' => $row['tpr_status'],
        );
        $updateId = $telephone->update($pid, $updateData);

        $sql = "DELETE FROM " . TELEPHONE_RECORD_TABLE . " WHERE tpr_id = {$row['tpr_id']}";
        $mysqli->query($sql);
      } else {

        $updateData = array(
          'tp_assigned' => '',
          'tp_assigned_date' => '0000-00-00',
          'tp_assigned_group' => '',
          'tp_start_date' => '0000-00-00',
          'tp_end_date' => '0000-00-00',
          'm_id' => 0,
        );
        $updateId = $telephone->update($pid, $updateData);

      }
      if (isset($was_completed)) {
        $old_is_absence = !empty($target_status) && strpos($target_status, 'absence') !== false;
        $new_is_absence = !empty($current_status) && strpos($current_status, 'absence') !== false;
        if ((!$old_is_absence && $new_is_absence) || ($old_is_absence && $new_is_absence && $was_completed)) {
          telephone_house_update($pid, 'restore');
        }
      }
    }
  } elseif ($work == 'select_minister') { // 참석자 선택
    $sql = "SELECT mb_id FROM " . MEETING_TABLE . " WHERE m_id = {$m_id}";
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $mb_id_array = !empty($row['mb_id']) ? remove_moveout_mb_id(explode(',', $row['mb_id'])) : array();

      if ($action == 'add') {
        if (!in_array($current_mb_id, $mb_id_array)) {
          $mb_id_array[] = $current_mb_id;
          $mb_id = implode(',', $mb_id_array);
          $sql = "UPDATE " . MEETING_TABLE . " SET mb_id = '{$mb_id}' WHERE m_id = {$m_id}";
          if ($mysqli->query($sql))
            echo json_encode(array('attend' => '1'));
        }
      } elseif ($action == 'delete') {
        if (in_array($current_mb_id, $mb_id_array)) {
          $key = array_search($current_mb_id, $mb_id_array, true);
          unset($mb_id_array[$key]);
          $mb_id = implode(',', $mb_id_array);
          $sql = "UPDATE " . MEETING_TABLE . " SET mb_id = '{$mb_id}' WHERE m_id = {$m_id}";
          if ($mysqli->query($sql))
            echo json_encode(array('attend' => '0'));
        }
      }

    }
  } elseif ($work == 'update_meeting_contents') { // 모임내용 기록
    $sql = "UPDATE " . MEETING_TABLE . " SET m_contents = '{$m_contents}' WHERE m_id = {$m_id}";
    $mysqli->query($sql);
  }
}

/**
 * 구역 배정 완료 시 시스템 메시지 + Push 알림 발송
 */
function _send_assign_notification($ids, $type, $assigned_member_csv, $assigned_group) {
  global $mysqli;
  if (empty($ids)) return;

  $member_ids = array_filter(array_map('intval', explode(',', $assigned_member_csv)));
  if (empty($member_ids)) return;

  // 멤버 이름 조회 (그룹 구분 포함)
  $group_name = get_assigned_group_name($assigned_member_csv, $assigned_group);
  if (is_array($group_name)) {
    $member_text = implode(' | ', $group_name);
  } else {
    $member_text = $group_name;
  }

  foreach ($ids as $tt_id) {
    $safe_type = $mysqli->real_escape_string($type);

    // 기존 쪽지 및 읽음 기록 초기화 (새 배정이므로 이전 대화 삭제)
    $mysqli->query("DELETE FROM " . TERRITORY_MSG_TABLE . " WHERE tt_id = {$tt_id} AND tm_type = '{$safe_type}'");
    $mysqli->query("DELETE FROM " . TERRITORY_MSG_READ_TABLE . " WHERE tt_id = {$tt_id} AND tm_type = '{$safe_type}'");

    // 구역 이름 조회
    if ($type === 'D') {
      $sql = "SELECT dp_name as name FROM " . DISPLAY_TABLE . " WHERE d_id = {$tt_id} LIMIT 1";
    } else {
      $sql = "SELECT CONCAT('[', tt_num, '] ', tt_name) as name FROM " . TERRITORY_TABLE . " WHERE tt_id = {$tt_id} LIMIT 1";
    }
    $res = $mysqli->query($sql);
    if (!$res || !$res->num_rows) continue;
    $territory_name = $res->fetch_assoc()['name'];

    $label = ($type === 'D') ? '전시대' : '구역';
    $message = "{$territory_name} {$label}에 배정이 완료되었습니다.\n{$member_text}";
    $escaped_msg = $mysqli->real_escape_string($message);

    // 시스템 메시지 삽입 (mb_id=0, mb_name='오늘의봉사')
    $sql = "INSERT INTO " . TERRITORY_MSG_TABLE . " (tt_id, tm_type, mb_id, mb_name, tm_message)
            VALUES ({$tt_id}, '{$safe_type}', 0, '오늘의봉사', '{$escaped_msg}')";
    $mysqli->query($sql);

    // Push 알림 발송
    if (function_exists('send_push_to_territory_members')) {
      send_push_to_territory_members($tt_id, $type, 0, '오늘의봉사', $message);
    } else {
      // territory_msg_api.php의 함수를 직접 사용할 수 없으므로 인라인 발송
      _send_push_for_assign($tt_id, $type, $member_ids, $message);
    }
  }
}

/**
 * 배정 알림용 Push 발송
 */
function _send_push_for_assign($tt_id, $type, $member_ids, $message) {
  global $mysqli;

  $vapid_public = get_site_option('vapid_public_key');
  $vapid_private = get_site_option('vapid_private_key');
  if (!$vapid_public || !$vapid_private) return;

  $autoload = __DIR__ . '/../vendor/autoload.php';
  if (!file_exists($autoload)) return;
  require_once $autoload;

  $ids_str = implode(',', $member_ids);
  $sql = "SELECT ps_endpoint, ps_auth, ps_p256dh FROM " . PUSH_SUBSCRIPTION_TABLE . "
          WHERE mb_id IN ({$ids_str})";
  $result = $mysqli->query($sql);
  if (!$result || !$result->num_rows) return;

  $auth = [
    'VAPID' => [
      'subject' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
      'publicKey' => $vapid_public,
      'privateKey' => $vapid_private,
    ],
  ];

  $webPush = new \Minishlink\WebPush\WebPush($auth);

  $payload = json_encode([
    'title' => '구역 배정 알림',
    'body' => mb_substr($message, 0, 100),
    'url' => '/'
  ]);

  while ($sub = $result->fetch_assoc()) {
    $subscription = \Minishlink\WebPush\Subscription::create([
      'endpoint' => $sub['ps_endpoint'],
      'publicKey' => $sub['ps_p256dh'],
      'authToken' => $sub['ps_auth'],
    ]);
    $webPush->queueNotification($subscription, $payload);
  }

  foreach ($webPush->flush() as $report) {
    if ($report->isSubscriptionExpired()) {
      $expired_endpoint = $mysqli->real_escape_string($report->getEndpoint());
      $mysqli->query("DELETE FROM " . PUSH_SUBSCRIPTION_TABLE . " WHERE ps_endpoint = '{$expired_endpoint}'");
    }
  }
}

?>