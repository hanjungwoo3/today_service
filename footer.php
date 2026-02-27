<?php include_once('config.php'); ?>
    <!-- 호별구역 보기 팝업 -->
    <div class="modal" id="territory-view-modal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered m-0" role="document">
        <div class="modal-content">
          <div class="modal-body">
            ...
          </div>
        </div>
      </div>
    </div>
    <!-- 호별구역 보기 팝업 끝 -->
    <!-- 전화구역 보기 팝업 -->
    <div class="modal" id="telephone-view-modal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered m-0" role="document">
        <div class="modal-content">
          <div class="modal-body">
            ...
          </div>
        </div>
      </div>
    </div>
    <!-- 전화구역 보기 팝업 끝 -->
    <!-- 구역 엑셀 업로드 팝업 -->
    <div class="modal" id="excelupload-modal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="table">
              <input type="hidden" name="pid">
              <div class="custom-file mb-4">
                <input type="file" name="excel" class="custom-file-input" id="customFile">
                <label class="custom-file-label" for="customFile">파일선택</label>
              </div>
              <button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-upload"></i> 업로드</button>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">닫기</button>
          </div>
        </div>
      </div>
    </div>
    <!-- 구역 엑셀 업로드 팝업 팝업 끝 -->
    <!-- 전도인 엑셀 업로드 팝업 -->
    <div class="modal" id="mb-excelupload-modal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body">
            <form method="post" enctype="multipart/form-data">
              <div class="custom-file mb-4">
                <input type="file" name="excel" class="custom-file-input" id="customFile">
                <label class="custom-file-label" for="customFile">파일선택</label>
              </div>
              <button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-upload"></i> 업로드</button>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">닫기</button>
          </div>
        </div>
      </div>
    </div>
    <!-- 전도인 엑셀 업로드 팝업 끝 -->
    <!-- 지도 팝업 -->
    <div class="modal" id="territory-map-modal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body" style=" height: 100vh;width: 100vw;">
          </div>
          <button type="button" class="btn border-none p-0" data-dismiss="modal">
            <i class="bi bi-x h1 text-primary"></i>
          </button>
        </div>
      </div>
    </div>
    <!-- 지도 팝업 끝 -->
    <div class="modal" id="territory-statistics-map-modal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body" style=" height: 100vh;width: 100vw;">
          </div>
          <button type="button" class="btn border-none p-0" data-dismiss="modal">
            <i class="bi bi-x h1 text-primary"></i>
          </button>
        </div>
      </div>
    </div>
    <!-- 로드뷰 팝업 -->
    <div class="modal" id="daum-roadview-modal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body" style=" height: 100vh;width: 100vw;">
            <div id="daum-roadview" style="height: 100%;width: 100%">

            </div>
          </div>
          <button type="button" class="btn border-none p-0" data-dismiss="modal">
            <i class="bi bi-x h1 text-primary"></i>
          </button>
        </div>
      </div>
    </div>
    <!-- 로드뷰 팝업 끝 -->
    <!-- 공통 팝업 -->
    <div class="modal" id="popup-modal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">닫기</button>
          </div>
        </div>
      </div>
    </div>
    <!-- 공통 팝업 끝 -->
    <!-- 공통 팝업 -->
    <div class="modal" id="popup-min-modal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body">
            ...
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">닫기</button>
          </div>
        </div>
      </div>
    </div>
    <!-- 공통 팝업 끝 -->
    <!-- 봉사모임 폼 팝업 -->
    <div class="modal" id="meeting-modal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" >
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">닫기</button>
          </div>
        </div>
      </div>
    </div>
    <!-- 봉사모임 폼 팝업 끝 -->
    <!-- 세대상태 변경 팝업 -->
    <div class="modal" id="condition-modal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">닫기</button>
          </div>
        </div>
      </div>
    </div>
    <!-- 세대상태 변경 팝업 끝 -->
    <!-- 확인 창 팝업 -->
    <div class="modal" id="confirm-modal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-body">
            ...
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary mr-3 dismiss" data-dismiss="modal">취소</button>
            <button type="button" class="btn btn-outline-primary confirm">확인</button>
          </div>
        </div>
      </div>
    </div>
    <!-- 확인 창 팝업 끝 -->

    <div id="toast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-delay="1500">
      <div class="toast-body">
      </div>
    </div>

    <!-- 구역 쪽지 팝업 -->
    <?php if(!is_moveout(mb_id())): ?>
    <div id="tmsg-backdrop"></div>
    <div id="tmsg-popup" style="display:none;"></div>
    <script src="<?=BASE_PATH?>/js/territory_msg.js"></script>
    <script>
      var _tmsgMyMbId = <?= intval(mb_id()) ?>;
      $(document).on('click', '.territory-msg-btn', function(e) {
        e.stopPropagation();
        // 모달 안에서 쪽지 열 때 모달의 focusin 가로채기 해제
        $(document).off('focusin.bs.modal');
        var ttId = parseInt($(this).data('tt-id'));
        var ttNum = $(this).data('tt-num') + '';
        var msgType = $(this).data('msg-type') || 'T';
        TerritoryMsg.openPanel(ttId, ttNum, _tmsgMyMbId, msgType);
      });
    </script>
    <style>
    #tmsg-backdrop { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 9999; display: none; }
    #tmsg-popup { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; width: 340px; }
    .tmsg-popup-inner { border-radius: 12px; background: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.18); overflow: hidden; display: flex; flex-direction: column; max-height: 440px; }
    .tmsg-header { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #f8f9fa; border-bottom: 1px solid #eee; }
    .tmsg-title { font-size: 15px; font-weight: 600; }
    .tmsg-header-btns { display: flex; align-items: center; gap: 4px; }
    .tmsg-refresh { border: none; background: none; font-size: 16px; color: #666; padding: 0 4px; cursor: pointer; }
    .tmsg-refresh:hover { color: #333; }
    .tmsg-close { border: none; background: none; font-size: 22px; color: #999; padding: 0 4px; cursor: pointer; line-height: 1; }
    .tmsg-close:hover { color: #333; }
    .tmsg-body { flex: 1; overflow-y: auto; padding: 10px 14px; min-height: 200px; max-height: 300px; }
    .tmsg-footer { display: flex; padding: 8px 10px; border-top: 1px solid #eee; gap: 6px; }
    .tmsg-footer input { flex: 1; border: 1px solid #ddd; border-radius: 20px; padding: 8px 14px; font-size: 14px; outline: none; }
    .tmsg-footer input:focus { border-color: #80bdff; }
    #tmsg-send { border: none; background: #5c7cfa; color: #fff; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    #tmsg-send:hover { background: #4c6ef5; }
    .tmsg-item { margin-bottom: 10px; font-size: 15px; }
    .tmsg-item.mine { text-align: right; }
    .tmsg-name { font-size: 12px; color: #888; margin-bottom: 2px; }
    .tmsg-text { display: inline-block; padding: 8px 12px; border-radius: 16px; background: #f0f0f0; max-width: 80%; word-break: break-word; text-align: left; }
    .tmsg-item.mine .tmsg-text { background: #d4edff; }
    .tmsg-time { font-size: 11px; color: #aaa; margin-top: 2px; }
    .tmsg-empty, .tmsg-loading { text-align: center; color: #999; padding: 20px 0; font-size: 13px; }
    @media (max-width: 576px) {
      #tmsg-popup { top: auto; bottom: 0; left: 0; right: 0; width: 100%; transform: none; }
      .tmsg-popup-inner { border-radius: 16px 16px 0 0; max-height: 55vh; }
      .tmsg-body { max-height: 40vh; }
    }
    </style>
    <?php endif; ?>
    <!-- 구역 쪽지 팝업 끝 -->

    <!-- 푸시 알림 초기화 -->
    <?php
    $vapid_public_key = get_site_option('vapid_public_key');
    if (mb_id() > 0 && $vapid_public_key):
    ?>
    <script>
    PushNotify.init('<?= htmlspecialchars($vapid_public_key) ?>', BASE_PATH);
    </script>
    <?php endif; ?>
    <!-- 푸시 알림 끝 -->

    <div class="preloader">
      <img src="<?=BASE_PATH?>/img/preloader2.gif">
    </div>
    <div class="preloader_bg"></div>

  </body>
</html>
