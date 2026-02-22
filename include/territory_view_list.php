<?php include_once('../config.php'); ?>

<?php
$tt_id = isset($_POST['tt_id']) ? intval($_POST['tt_id']) : (isset($_GET['tt_id']) ? intval($_GET['tt_id']) : 0);
$tt_type = isset($_POST['tt_type']) ? $_POST['tt_type'] : (isset($_GET['tt_type']) ? $_GET['tt_type'] : '');

if ($tt_id):

  // tt_status 정보 가져오기
  $tt_status_sql = "SELECT tt_status FROM " . TERRITORY_TABLE . " WHERE tt_id = {$tt_id}";
  $tt_status_result = $mysqli->query($tt_status_sql);
  $tt_status_row = $tt_status_result->fetch_assoc();
  $is_absence_status = strpos($tt_status_row['tt_status'], 'absence') !== false;

  $compare_address = '';
  $new_compare_address = '';
  $sql = "SELECT * FROM " . HOUSE_TABLE . " WHERE tt_id = {$tt_id} order by h_order";
  $result = $mysqli->query($sql);
  while ($r = $result->fetch_assoc()):

    $condition = get_house_condition_text($r['h_condition']);
    if ($compare_address)
      $new_compare_address = ($tt_type == '아파트' || $tt_type == '빌라' || $tt_type == '추가2') ? $r['h_address1'] . $r['h_address2'] : $r['h_address1'] . $r['h_address2'] . $r['h_address3'];
    ?>

    <?php if ($compare_address != $new_compare_address): ?>
      <tr class="bg-light border-bottom">
        <td colspan="6">&nbsp;</td>
      </tr>
    <?php endif; ?>
    <tr class="gubun-odd">
      <?php if ($tt_type == '아파트' || $tt_type == '빌라' || $tt_type == '추가2'): ?>
        <td colspan="3" class="pb-0"
          style="word-wrap: break-word; word-break: break-word; overflow-wrap: break-word; max-width: 0;">
          <div style="padding-right: 5px;"><?= $r['h_address1'] ?></div>
        </td>
      <?php else: ?>
        <td colspan="3" class="pb-0"
          style="word-wrap: break-word; word-break: break-word; overflow-wrap: break-word; max-width: 0;">
          <div style="padding-right: 5px;">
            <?= $r['h_address1'] ?>       <?= $r['h_address2'] ?>       <?php if ($r['h_address3'])
                            echo '(' . $r['h_address3'] . ')'; ?>
          </div>
        </td>
      <?php endif; ?>
      <?php if ($condition): ?>
        <td colspan="<?= $tt_type == '편지' ? '1' : '2' ?>" rowspan="2"
          style="text-align:center; vertical-align: middle; min-width: 45px; width: <?= $tt_type == '편지' ? '90px' : '90px' ?>;">
          <div style="word-wrap: break-word; word-break: break-word; overflow-wrap: break-word; padding: 2px;">
            <span class="condition-chip<?= $r['h_condition'] ?>"><?= $condition ?></span>
          </div>
        </td>
      <?php else: ?>
        <td rowspan="2" class="text-center" style="vertical-align: middle; min-width: 45px; width: 45px; position: relative;">
          <label class="visit-check-label <?= ($is_absence_status && $r['h_visit_old'] == 'Y') ? 'disabled' : ''; ?>"
            <?= ($is_absence_status && $r['h_visit_old'] == 'Y') ? 'title="이미 방문 완료된 세대입니다"' : ''; ?>>
            <input type="checkbox" class="visit-check" name="h_visit" value="Y"
              <?= ($r['h_visit'] == 'Y') ? 'checked="checked"' : ''; ?>       <?= ($is_absence_status && $r['h_visit_old'] == 'Y') ? 'disabled="disabled"' : ''; ?>
              onclick="<?= ($is_absence_status && $r['h_visit_old'] == 'Y') ? 'return false;' : 'visit_check(\'territory\',' . $r['h_id'] . ',this);' ?>">
            <span
              class="visit-check-mark <?= $tt_type == '편지' ? 'letter' : '' ?> <?= ($is_absence_status && $r['h_visit_old'] == 'Y') ? 'disabled' : ''; ?>"></span>
          </label>
        </td>
        <?php if ($tt_type != '편지'): ?>
          <td rowspan="2" class="text-center" style="vertical-align: middle; min-width: 45px; width: 45px; position: relative;">
            <label class="visit-check-label <?= ($is_absence_status && $r['h_visit_old'] == 'Y') ? 'disabled' : ''; ?>"
              <?= ($is_absence_status && $r['h_visit_old'] == 'Y') ? 'title="이미 방문 완료된 세대입니다"' : ''; ?>>
              <input type="checkbox" class="visit-check" name="h_visit" value="N"
                <?= ($r['h_visit'] == 'N') ? 'checked="checked"' : ''; ?>         <?= ($is_absence_status && $r['h_visit_old'] == 'Y') ? 'disabled="disabled"' : ''; ?>
                onclick="<?= ($is_absence_status && $r['h_visit_old'] == 'Y') ? 'return false;' : 'visit_check(\'territory\',' . $r['h_id'] . ',this);' ?>">
              <span class="visit-check-mark <?= ($is_absence_status && $r['h_visit_old'] == 'Y') ? 'disabled' : ''; ?>"></span>
            </label>
          </td>
        <?php endif; ?>
      <?php endif; ?>
      <td style="text-align:center; vertical-align: middle; min-width: 60px; width: 60px; position: relative; padding-right: 15px;" rowspan="2">
        <?php if ($condition): ?>
          <button class="btn btn-outline-info btn-sm text-center condition-btn-margin"
            style="width: 34px; height: 34px; padding: 0; line-height: 32px;"
            onclick="condition_work('territory', 'view', <?= $r['h_id'] ?>, '<?= addslashes($r['h_address1'] . ' ' . $r['h_address2'] . ' ' . $r['h_address3']) ?>');"><i
              class="bi bi-bell" style="font-size: 1.1rem;"></i></button>
        <?php else: ?>
          <button class="btn btn-outline-secondary btn-sm text-center condition-btn-margin"
            style="width: 34px; height: 34px; padding: 0; line-height: 32px;"
            onclick="condition_work('territory', 'add', <?= $r['h_id'] ?>, '<?= addslashes($r['h_address1'] . ' ' . $r['h_address2'] . ' ' . $r['h_address3']) ?>');"><i
              class="bi bi-pencil" style="font-size: 1.1rem;"></i></button>
        <?php endif; ?>
      </td>
    </tr>
    <tr class="gubun-even">
      <?php if ($tt_type == '아파트' || $tt_type == '빌라' || $tt_type == '추가2'): ?>
        <td>&nbsp;</td>
        <td style="word-break: break-all; padding: 1px;">
          <div style="padding-right: 2px;"><?= $r['h_address2'] ?></div>
        </td>
        <td style="word-break: break-all; padding: 1px;">
          <div style="padding-right: 2px;"><?= $r['h_address3'] ?></div>
        </td>
      <?php else: ?>
        <td class="text-left">
          <?php if ($r['h_address1'])
            echo kakao_menu($r['h_address1'] . ' ' . $r['h_address2']); ?>
        </td>
        <td style="word-break: break-all; padding: 1px;">
          <div style="padding-right: 2px;"><?= $r['h_address4'] ?></div>
        </td>
        <td style="word-break: break-all; padding: 1px;">
          <div style="padding-right: 2px;"><?= $r['h_address5'] ?></div>
        </td>
      <?php endif; ?>
    </tr>

    <?php $compare_address = ($tt_type == '아파트' || $tt_type == '빌라' || $tt_type == '추가2') ? $r['h_address1'] . $r['h_address2'] : $r['h_address1'] . $r['h_address2'] . $r['h_address3']; ?>
  <?php endwhile; ?>
<?php endif; ?>