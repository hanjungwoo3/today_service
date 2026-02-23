<?php include_once('header.php');?>
<script>
  // 초기 페인팅 시 서버 타임존 날짜가 잠깐 보이는 것을 막기 위해 숨김
  document.documentElement.style.visibility = 'hidden';
</script>

<?php
$mb_id = mb_id();
$s_date_param = isset($_GET['s_date']) ? $_GET['s_date'] : '';
$today_dt = DateTime::createFromFormat('Y-m-d', $s_date_param);
$today = $today_dt ? $today_dt->format('Y-m-d') : date('Y-m-d'); // 기본은 서버 날짜, 가능하면 클라이언트 전달값 사용
$ma_id = get_addschedule_id($today);

$sql = "SELECT * FROM ".MEETING_ADD_TABLE." WHERE ma_id IN({$ma_id}) ORDER BY ma_auto DESC, ma_date DESC, ma_date2, ma_title";
$result = $mysqli->query($sql);

$sql = "SELECT m_id FROM ".MEETING_TABLE." WHERE m_date = '{$today}' AND m_cancle = '1'";
$cancle = $mysqli->query($sql);

?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">오늘의 봉사 <span id="today-date-display"></span></h1>
  <p class="text-white today-info mb-0 ml-md-auto">
    <?php if(!is_moveout($mb_id)): ?>
    <a onclick="open_meeting_view('<?=$today?>')" class="btn btn-outline-light btn-sm"><i class="bi bi-info-circle"></i> 모임정보</a>
    <?php endif; ?>
  </p>
</header>

<?php echo footer_menu('오늘의 봉사');?>

<!-- 지정된 기간 동안의 추가일정, 알림사항 -->
<?php if(!is_moveout($mb_id)): ?>
<div class="list-group list-group-flush" style="margin-top: -12px">
  <?php if($result->num_rows > 0):?>
    <?php while($mar = $result->fetch_assoc()):?>
      <div class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-light">
        <span>
          <span class="badge badge-light text-info align-middle">일정</span>
          <span class="align-middle"><?=$mar['ma_title']?></span>
        </span>
        <a href="<?=BASE_PATH?>/pages/minister_schedule.php#minister_event" class="btn btn-outline-secondary btn-sm badge">상세 보기</a>
      </div>
    <?php endwhile;?>
  <?php endif;?>

  <?php if($cancle->num_rows > 0):?>
  <div class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-light">
    <span>
      <span class="badge badge-light text-danger align-middle">알림</span>
      <span class="align-middle">취소된 봉사모임이 있습니다.</span>
    </span>
    <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm badge" onclick="open_meeting_view('<?=$today?>')">상세보기</a>
  </div>
  <?php endif;?>

  <?php if(board_new($mb_id)):?>
    <div class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-light">
      <span>
        <span class="badge badge-light text-warning align-middle">공지</span>
        <span class="align-middle">새로운 공지사항이 있습니다.</span>
      </span>
      <a href="<?=BASE_PATH?>/pages/board.php?auth=<?=board_new($mb_id)?>" class="btn btn-outline-secondary btn-sm badge">상세보기</a>
    </div>
  <?php endif;?>

</div>

<div id="today-service-list"></div>
<div id="territory-msg-container" style="display:none;"></div>

<?php if(file_exists('include/custom_board_top.php')) include 'include/custom_board_top.php'; ?>
<?php if(file_exists('include/custom_home_assignments.php')) include 'include/custom_home_assignments.php'; ?>

<script type="text/javascript">
  function getLocalDateYMD(){
    const now = new Date();
    return [
      now.getFullYear(),
      String(now.getMonth() + 1).padStart(2, '0'),
      String(now.getDate()).padStart(2, '0')
    ].join('-');
  }

  function setHeaderDate(localYmd){
    const d = new Date(localYmd);
    const week = ['','월','화','수','목','금','토','일'];
    const md = String(d.getMonth()+1).padStart(2,'0') + '.' + String(d.getDate()).padStart(2,'0');
    document.getElementById('today-date-display').textContent = `${md} ${week[d.getDay()]}`;
  }

  document.addEventListener('DOMContentLoaded', () => {
    const rendered = '<?=$today?>';
    const localYmd = getLocalDateYMD();

    // 헤더 날짜를 로컬 기준으로 즉시 반영
    setHeaderDate(localYmd);

    if (rendered !== localYmd) {
      // 서버 렌더 날짜와 다르면 로컬 날짜로 강제 교체
      window.location.replace(`${BASE_PATH}/index.php?s_date=${localYmd}`);
      return;
    }

    // 최초 로딩 시 로컬 날짜 기준으로 목록 불러오기
    pageload_custom(BASE_PATH+'/pages/today_service_list.php?s_date='+localYmd,'#today-service-list');

    // 10초마다 오늘의 봉사 업데이트 (항상 로컬 날짜 기준, 비가시 상태면 스킵)
    setInterval(function(){
      if (document.hidden) return;
      const current = getLocalDateYMD();
      pageload_custom(BASE_PATH+'/pages/today_service_list.php?s_date='+current,'#today-service-list'); 
    },10000);

    // 화면 표시
    document.documentElement.style.visibility = 'visible';
  });
</script>

<!-- 구역 쪽지 -->
<script src="<?=BASE_PATH?>/js/territory_msg.js"></script>
<script>
  var _tmsgMyMbId = <?= intval($mb_id) ?>;
  $(document).on('click', '.territory-msg-btn', function(e) {
    e.stopPropagation();
    var ttId = parseInt($(this).data('tt-id'));
    var ttNum = $(this).data('tt-num') + '';
    var msgType = $(this).data('msg-type') || 'T';
    TerritoryMsg.openPanel(ttId, ttNum, _tmsgMyMbId, msgType);
  });
</script>
<style>
.tmsg-panel { margin: 0 0 8px; border: 1px solid #ddd; border-radius: 8px; background: #fff; overflow: hidden; }
.tmsg-header { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f8f9fa; border-bottom: 1px solid #eee; }
.tmsg-title { font-size: 14px; font-weight: 600; }
.tmsg-header-btns { display: flex; align-items: center; gap: 4px; }
.tmsg-refresh { border: none; background: none; font-size: 16px; color: #666; padding: 0 4px; cursor: pointer; }
.tmsg-refresh:hover { color: #333; }
.tmsg-close { border: none; background: none; font-size: 20px; color: #999; padding: 0 4px; cursor: pointer; line-height: 1; }
.tmsg-close:hover { color: #333; }
.tmsg-body { max-height: 250px; overflow-y: auto; padding: 8px 12px; }
.tmsg-footer { display: flex; padding: 8px; border-top: 1px solid #eee; gap: 6px; }
.tmsg-footer input { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 6px 10px; font-size: 14px; outline: none; }
.tmsg-footer input:focus { border-color: #80bdff; }
#tmsg-send { border: none; background: #5c7cfa; color: #fff; border-radius: 4px; padding: 6px 12px; cursor: pointer; }
#tmsg-send:hover { background: #4c6ef5; }
.tmsg-item { margin-bottom: 8px; font-size: 13px; }
.tmsg-item.mine { text-align: right; }
.tmsg-name { font-size: 11px; color: #888; margin-bottom: 2px; }
.tmsg-text { display: inline-block; padding: 6px 10px; border-radius: 12px; background: #f0f0f0; max-width: 80%; word-break: break-word; text-align: left; }
.tmsg-item.mine .tmsg-text { background: #d4edff; }
.tmsg-time { font-size: 10px; color: #aaa; margin-top: 2px; }
.tmsg-empty, .tmsg-loading { text-align: center; color: #999; padding: 20px 0; font-size: 13px; }
</style>
<?php endif; ?>

<?php include_once('footer.php');?>