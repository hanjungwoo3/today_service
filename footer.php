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

    <div class="preloader">
      <img src="<?=BASE_PATH?>/img/preloader2.gif">
    </div>
    <div class="preloader_bg"></div>

  </body>
</html>
