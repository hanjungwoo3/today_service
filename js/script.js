// bootstrap modal 로 confirm 함수 대체
$.fn.confirm = function (message) {

  return this.each(function () {
    let element = this;

    $('.modal-body', this).html(message);

    // 이전에 등록된 이벤트 리스너를 제거하여 중복 방지
    $(this).off('click', '.confirm');

    $(this).on('click', '.confirm', function (event) {
      $(element).trigger('confirm', event);
      $(element).modal('hide');
    });

    $(this).on('hide.bs.modal', function (event) {
      $(this).off('confirm dismiss');
    });

    $(this).modal('show');
  });
};

$.fn.toastMessage = function (message) {

  return this.each(function () {
    let element = this;

    $('.toast-body', this).html(message);

    $(this).toast('show');
  });
};

// $.ajax를 호출 함수 (24.12.29)
function ajaxRequest(url, data, processData, contentType, dataType, successCallback) {
  $.ajax({
    url: url,
    data: data,
    processData: processData,
    contentType: contentType,
    type: 'POST',
    dataType: dataType,
    success: function (response) {
      // successCallback을 통해 호출자에게 성공한 데이터를 전달
      if (typeof successCallback === 'function') {
        successCallback(response);
      }
    },
    error: function (xhr, status, error) {
      console.error('AJAX Error:', status, error);
      alert('서버 에러가 발생했습니다. 관리자에게 문의하세요.');
    }
  });
}

// 카카오 네비 길찾기 실행
function kakao_navi(address, name) {

  // 주소-좌표 변환 객체를 생성합니다
  let geocoder = new daum.maps.services.Geocoder();

  // 주소로 좌표를 검색합니다
  geocoder.addressSearch(address, function (result, status) {
    // 정상적으로 검색이 완료됐으면
    if (status === daum.maps.services.Status.OK) {
      let x = Number(result[0].x);
      let y = Number(result[0].y);
      Kakao.Navi.start({
        name: name,
        x: x,
        y: y,
        routeInfo: true,
        coordType: 'wgs84'
      });
    }
  });

}

// 로드뷰 실행
function daum_roadview(address) {

  $('#daum-roadview-modal #daum-roadview').html('');
  $('#daum-roadview-modal').modal();

  // 주소-좌표 변환 객체를 생성합니다
  let geocoder = new daum.maps.services.Geocoder();

  // 주소로 좌표를 검색합니다
  geocoder.addressSearch(address, function (result, status) {
    // 정상적으로 검색이 완료됐으면
    if (status === daum.maps.services.Status.OK) {
      let x = Number(result[0].x);
      let y = Number(result[0].y);
      let roadviewContainer = document.getElementById('daum-roadview'); //로드뷰를 표시할 div
      let roadview = new daum.maps.Roadview(roadviewContainer); //로드뷰 객체
      let roadviewClient = new daum.maps.RoadviewClient(); //좌표로부터 로드뷰 파노ID를 가져올 로드뷰 helper객체

      let position = new daum.maps.LatLng(y, x);

      // 특정 위치의 좌표와 가까운 로드뷰의 panoId를 추출하여 로드뷰를 띄운다.
      roadviewClient.getNearestPanoId(position, 50, function (panoId) {
        roadview.setPanoId(panoId, position); //panoId와 중심좌표를 통해 로드뷰 실행
      });


      daum.maps.event.addListener(roadview, 'init', function () {

        // 로드뷰에 올릴 마커를 생성합니다.
        let rMarker = new daum.maps.Marker({
          position: position,
          map: roadview //map 대신 rv(로드뷰 객체)로 설정하면 로드뷰에 올라갑니다.
        });

        // // 로드뷰에 올릴 장소명 인포윈도우를 생성합니다.
        // var rLabel = new daum.maps.InfoWindow({
        //   position: position,
        //   content: '<div style="padding:5px;">'+address+'</div>'
        // });
        // rLabel.open(roadview, rMarker);

        // 로드뷰 마커가 중앙에 오도록 로드뷰의 viewpoint 조정 합니다.
        let projection = roadview.getProjection(); // viewpoint(화면좌표)값을 추출할 수 있는 projection 객체를 가져옵니다.

        // 마커의 position과 altitude값을 통해 viewpoint값(화면좌표)를 추출합니다.
        let viewpoint = projection.viewpointFromCoords(rMarker.getPosition(), rMarker.getAltitude());
        roadview.setViewpoint(viewpoint); //로드뷰에 뷰포인트를 설정합니다.
      });


    } else {
      $('#daum-roadview').html('<p>로드뷰를 표시할 수 없습니다.</p>');
    }
  });

}


// 비동기 페이지 변경 함수 (커스텀)
function pageload_custom(url, wrap_id) {
  $.ajax({
    url: url,
    type: 'POST',
    dataType: 'html',
    success: function (result) {
      $(wrap_id).html(result);
      $(wrap_id).show();
    }
  });
}

function returnvisit(table, work, pid) {
  if (work == 'stop') { // 중단
    $('#confirm-modal').confirm('재방문을 중단하시겠습니까?<br>이 집에 대한 재방문 기록이 모두 삭제됩니다.').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/pages/minister_work.php',
          data: {
            'work': 'stop',
            'pid': pid,
            'table': table
          },
          type: 'POST',
          async: true,
          dataType: 'html',
          success: function (result) {
            if (table == 'territory') {
              $('#minister-returnvisit div[rv_index=h_' + pid + ']').hide();
            } else if (table == 'telephone') {
              $('#minister-returnvisit div[rv_index=tph_' + pid + ']').hide();
            }
          },
          complete: function (xhr, textStatus) {
            $('#toast').toastMessage('업데이트 완료');
          }
        });
      }
    });
  } else if (work == 'transfer') { // 양도
    $.ajax({
      url: BASE_PATH + '/include/minister_returnvisit_transfer.php',
      data: {
        'pid': pid,
        'table': table
      },
      type: 'POST',
      async: true,
      dataType: 'html',
      success: function (result) {
        $('#popup-min-modal .modal-body').html(result);
        $('#popup-min-modal').modal();
      },
      complete: function (xhr, textStatus) {
        // $('#toast').toastMessage('양도 완료');
      }
    });
  }

}

// 재방문 기록 삭제
function delete_returnvisit(table, pid, rv_id) {
  $('#confirm-modal').confirm('재방문 기록을 삭제하시겠습니까?').on({
    confirm: function () {
      $.ajax({
        url: BASE_PATH + '/pages/minister_work.php',
        data: {
          'work': 'delete_return_visit',
          'rv_id': rv_id,
          'table': table
        },
        type: 'POST',
        async: true,
        dataType: 'html',
        success: function (result) {
          $('#returnvisitmemo_update .returnvisit_list[rv_index=' + rv_id + ']').hide();
        },
        complete: function (xhr, textStatus) {
          $('#toast').toastMessage('삭제 완료');
        }
      });
    }
  });
}

// 구역 상태 삭제
function condition_delete(table, pid) {
  $('#confirm-modal').confirm('특이사항을 삭제하시겠습니까?').on({
    confirm: function () {
      $.ajax({
        url: BASE_PATH + '/include/condition_work.php',
        data: {
          'table': table,
          'work': 'delete',
          'pid': pid
        },
        type: 'POST',
        async: true,
        dataType: 'html',
        success: function (result) {
        },
        complete: function (xhr, textStatus) {
          $('#condition-modal').modal('hide');
          if (table == 'territory') {
            territory_view_update(); // territory_view_list.php 업데이트
          } else if (table == 'telephone') {
            telephone_view_update(); // telephone_view_list.php 업데이트
          }
          $('#toast').toastMessage('삭제 완료');
        }
      });
    }
  });
}

// 재방문, 연구로 상태 변경
function returnvisit_change_study(table, condition, pid) {
  $.ajax({
    url: BASE_PATH + '/pages/minister_work.php',
    data: {
      'work': 'returnvisit_change_study',
      'table': table,
      'condition': condition,
      'pid': pid
    },
    type: 'POST',
    async: false,
    dataType: 'html',
    success: function (result) {

    },
    complete: function (xhr, textStatus) {
      $('#toast').toastMessage('업데이트 완료');
    }
  });
}

// 구역 전체 지도 보기
function statistics_map_view(source = '') {
  pageload_custom(BASE_PATH + '/include/territory_all_map.php?modal=true&source=' + source, '#territory-statistics-map-modal .modal-body');
  $('#territory-statistics-map-modal').modal();
}

// 구역지도 보기
function map_view(type, value) {
  $.ajax({
    url: BASE_PATH + '/include/territory_view_map.php',
    data: {
      type: type,
      value: value
    },
    type: 'POST',
    async: false,
    dataType: 'html',
    success: function (result) {
      $('#territory-map-modal .modal-body').html(result);
      $('#territory-map-modal').modal();
    },
    complete: function (xhr, textStatus) {
    }
  });
}

// 호별 구역카드 보기 클릭시
function open_territory_view(tt_id, mode = 'view') {
  if (mode === 'start') {
    $.ajax({
      url: BASE_PATH + '/include/ajax_work.php',
      data: { 'work': 'territory_start', 'table': 'territory', 'id': tt_id },
      type: 'POST',
      async: true,
      dataType: 'text', // 빈 응답/경고에도 파싱 에러가 나지 않도록 텍스트 처리
      success: function (response) {
        console.log('Start service logged:', response);
      },
      error: function (xhr, status, error) {
        console.error('Error starting service:', error);
      }
    });
  }
  $.ajax({
    url: BASE_PATH + '/include/territory_view.php',
    data: {
      'tt_id': tt_id,
    },
    type: 'POST',
    async: true,
    dataType: 'html',
    success: function (result) {
      $('#territory-view-modal .modal-body').html(result);
      $('#territory-view-modal').modal();
      let viewportHeight = $('#territory-view-modal').outerHeight();
      $('#territory-view-modal .modal-dialog .modal-content').css({ height: viewportHeight });
      // 모달 내 쪽지 뱃지 갱신
      if (typeof TerritoryMsg !== 'undefined') TerritoryMsg.refreshBadges();
    }
  });
}

// 호별 구역카드 나가기, 저장하고나가기 클릭시
function close_territory_view(tt_id) {
  $('#territory-view-modal').modal('hide');

  if (typeof v_admin_territory !== 'undefined') {
    v_admin_territory.updateTerritory(tt_id);
  }
  // 인도자 배정 화면에서 구역 상세를 닫을 때, 해당 구역 정보만이라도 최신으로 반영되도록 전체 구역 리스트를 1회 갱신
  if (typeof v_guide_assign_step !== 'undefined' && typeof v_guide_assign_step.listTerritory === 'function') {
    v_guide_assign_step.listTerritory();
  }
  // 홈(오늘의 봉사) 화면이면 오늘 리스트를 1회 갱신
  if ($('#today-service-list').length) {
    const now = new Date();
    const today = [
      now.getFullYear(),
      String(now.getMonth() + 1).padStart(2, '0'),
      String(now.getDate()).padStart(2, '0')
    ].join('-');
    pageload_custom(BASE_PATH + '/pages/today_service_list.php?s_date=' + today, '#today-service-list');
  }

  $('#territory-view-modal .modal-body').html('');

  clearTimeout(timeout); // 업데이트 클리어
}

// 전화 구역카드 보기 클릭시
function open_telephone_view(tp_id, mode = 'view') {
  if (mode === 'start') {
    $.ajax({
      url: BASE_PATH + '/include/ajax_work.php',
      data: { 'work': 'territory_start', 'table': 'telephone', 'id': tp_id },
      type: 'POST',
      async: true,
      dataType: 'text', // 빈 응답/경고에도 파싱 에러가 나지 않도록 텍스트 처리
      success: function (response) {
        console.log('Start service logged:', response);
      },
      error: function (xhr, status, error) {
        console.error('Error starting service:', error);
      }
    });
  }
  $.ajax({
    url: BASE_PATH + '/include/telephone_view.php',
    data: {
      'tp_id': tp_id,
    },
    type: 'POST',
    async: true,
    dataType: 'html',
    success: function (result) {
      $('#telephone-view-modal .modal-body').html(result);
      $('#telephone-view-modal').modal();
      let viewportHeight = $('#telephone-view-modal').outerHeight();
      $('#telephone-view-modal .modal-dialog .modal-content').css({ height: viewportHeight });
    }
  });
}

// 전화 구역카드 나가기, 저장하고나가기 클릭시
function close_telephone_view(tp_id) {
  $('#telephone-view-modal').modal('hide');

  if (typeof v_admin_telephone !== 'undefined') {
    v_admin_telephone.updateTelephone(tp_id);
  }
  // 홈(오늘의 봉사) 화면이면 오늘 리스트를 1회 갱신
  if ($('#today-service-list').length) {
    const now = new Date();
    const today = [
      now.getFullYear(),
      String(now.getMonth() + 1).padStart(2, '0'),
      String(now.getDate()).padStart(2, '0')
    ].join('-');
    pageload_custom(BASE_PATH + '/pages/today_service_list.php?s_date=' + today, '#today-service-list');
  }
  $('#telephone-view-modal .modal-body').html('');

  clearTimeout(timeout); // 업데이트 클리어
}

// 호별 구역 관련 작업
function territory_work(work, tt_id, tt_num, update_page, update_wrap_id, type = 'territory') {
  if (work == 'upload') {
    $('#excelupload-modal input[name="table"]').val('territory');
    $('#excelupload-modal input[name="pid"]').val(tt_id);
    // $('#excelupload-modal input[name="update_page"]').val(update_page);
    // $('#excelupload-modal input[name="update_wrap_id"]').val(update_wrap_id);
    $('#excelupload-modal .custom-file-input').val('');
    $('#excelupload-modal .custom-file-label').text('파일선택');
    $('#excelupload-modal').modal();
  } else if (work == 'download') {
    location.href = BASE_PATH + '/include/territory_excel_download.php?tt_id=' + tt_id;
  } else if (work == 'copy') {
    $.ajax({
      url: BASE_PATH + '/include/admin_territory_copy.php',
      data: {
        'pid': tt_id,
        'table': type
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-min-modal .modal-body').html(result);
        $('#popup-min-modal').modal();
      }
    });
  } else if (work == 'add') {
    $.ajax({
      url: BASE_PATH + '/include/territory_form.php',
      data: {
        'tt_id': '',
        'type': type
        // 'update_page':update_page,
        // 'update_wrap_id':update_wrap_id
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-modal .modal-body').html(result);
        $('#popup-modal').modal();
      }
    });
  } else if (work == 'edit') {
    $.ajax({
      url: BASE_PATH + '/include/territory_form.php',
      data: {
        'tt_id': tt_id,
        'type': type
        // 'update_page':update_page,
        // 'update_wrap_id':update_wrap_id
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-modal .modal-body').html(result);
        $('#popup-modal').modal();
        // pageload_custom(update_page,'#admin-territory-list');
      }
    });
  } else if (work == 'del') { // 삭제
    $('#confirm-modal').confirm(tt_num + ' 구역을 삭제하시겠습니까?').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/pages/admin_territory_work.php',
          data: {
            'work': 'del',
            'tt_id': tt_id
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (result) {
            // pageload_custom(update_page,update_wrap_id);
            if (typeof v_admin_territory !== 'undefined') {
              v_admin_territory.deleteTerritory(tt_id);
            }
          },
          complete: function (xhr, textStatus) {
            $('#toast').toastMessage(tt_num + '구역 삭제 완료');
          }
        });
      }
    });
  } else if (work == 'reset') { // 리셋
    $('#confirm-modal').confirm(tt_num + ' 구역을 리셋하시겠습니까? 구역 내의 배정, 봉사 데이터는 삭제되며 기록으로 남습니다.').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/include/ajax_work.php',
          data: {
            'work': 'territory_reset',
            'tt_id': tt_id
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (result) {
            // pageload_custom(update_page,update_wrap_id);
            if (typeof v_admin_territory !== 'undefined') {
              v_admin_territory.updateTerritory(tt_id);
            }
          },
          complete: function (xhr, textStatus) {
            $('#toast').toastMessage(tt_num + '구역 리셋 완료');
          }
        });
      }
    });
  } else if (work == 'house') { // 세대 편집
    $.ajax({
      url: BASE_PATH + '/include/territory_house_form.php',
      data: {
        'tt_id': tt_id,
        // 'update_page':update_page,
        // 'update_wrap_id':update_wrap_id
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-modal .modal-body').html(result);
        $('#popup-modal').modal();
      }
    });
  } else if (work == 'record') {
    $.ajax({
      url: BASE_PATH + '/include/territory_record_form.php',
      data: {
        'tt_id': tt_id,
        // 'update_page':update_page,
        // 'update_wrap_id':update_wrap_id
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-modal .modal-body').html(result);
        $('#popup-modal').modal();
      }
    });
  }
  return false;
}

// 전화 구역 관련 작업
function telephone_work(work, tp_id, tp_num, update_page, update_wrap_id) {
  if (work == 'upload') {
    $('#excelupload-modal input[name="table"]').val('telephone');
    $('#excelupload-modal input[name="pid"]').val(tp_id);
    // $('#excelupload-modal input[name="update_page"]').val(update_page);
    // $('#excelupload-modal input[name="update_wrap_id"]').val(update_wrap_id);
    $('#excelupload-modal .custom-file-input').val('');
    $('#excelupload-modal .custom-file-label').text('파일선택');
    $('#excelupload-modal').modal();
  } else if (work == 'download') {
    location.href = BASE_PATH + '/include/telephone_excel_download.php?tp_id=' + tp_id;
  } else if (work == 'copy') {
    $.ajax({
      url: BASE_PATH + '/include/admin_territory_copy.php',
      data: {
        'pid': tp_id,
        'table': 'telephone'
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-min-modal .modal-body').html(result);
        $('#popup-min-modal').modal();
      }
    });
  } else if (work == 'add') {
    $.ajax({
      url: BASE_PATH + '/include/telephone_form.php',
      data: {
        'tp_id': '',
        // 'update_page':update_page,
        // 'update_wrap_id':update_wrap_id
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-modal .modal-body').html(result);
        $('#popup-modal').modal();
      }
    });
  } else if (work == 'edit') {
    $.ajax({
      url: BASE_PATH + '/include/telephone_form.php',
      data: {
        'tp_id': tp_id,
        // 'update_page':update_page,
        // 'update_wrap_id':update_wrap_id
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-modal .modal-body').html(result);
        $('#popup-modal').modal();
        // pageload_custom(update_page,'#admin-territory-list');
      }
    });
  } else if (work == 'del') { // 삭제
    $('#confirm-modal').confirm(tp_num + ' 구역을 삭제하시겠습니까?').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/pages/admin_telephone_work.php',
          data: {
            'work': 'del',
            'tp_id': tp_id
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (result) {
            // pageload_custom(update_page,update_wrap_id);
            if (typeof v_admin_telephone !== 'undefined') {
              v_admin_telephone.deleteTelephone(tp_id);
            }
          },
          complete: function (xhr, textStatus) {
            $('#toast').toastMessage(tp_num + '구역 삭제 완료');
          }
        });
      }
    });
  } else if (work == 'reset') { // 리셋
    $('#confirm-modal').confirm(tp_num + ' 구역을 리셋하시겠습니까? 모든 구역 내의 배정, 봉사 데이터는 삭제되며 기록으로 남습니다.').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/include/ajax_work.php',
          data: {
            'work': 'telephone_reset',
            'tp_id': tp_id
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (result) {
            // pageload_custom(update_page,update_wrap_id);
            if (typeof v_admin_telephone !== 'undefined') {
              v_admin_telephone.updateTelephone(tp_id);
            }
          },
          complete: function (xhr, textStatus) {
            $('#toast').toastMessage(tp_num + '구역 리셋 완료');
          }
        });
      }
    });
  } else if (work == 'house') { // 세대 편집
    $.ajax({
      url: BASE_PATH + '/include/telephone_house_form.php',
      data: {
        'tp_id': tp_id,
        // 'update_page':update_page,
        // 'update_wrap_id':update_wrap_id
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-modal .modal-body').html(result);
        $('#popup-modal').modal();
      }
    });
  } else if (work == 'record') { // 봉사기록 편집
    $.ajax({
      url: BASE_PATH + '/include/telephone_record_form.php',
      data: {
        'tp_id': tp_id,
        // 'update_page':update_page,
        // 'update_wrap_id':update_wrap_id
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-modal .modal-body').html(result);
        $('#popup-modal').modal();
      }
    });
  }
  return false;
}

// 회중일정 관련 작업
function addschedule_work(work, ma_id) {
  if (work == 'add') {
    $.ajax({
      url: BASE_PATH + '/include/admin_addschedule_form.php',
      data: {
        'ma_id': ''
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-modal .modal-body').html(result);
        $('#popup-modal').modal();
      }
    });
  } else if (work == 'edit') {
    $.ajax({
      url: BASE_PATH + '/include/admin_addschedule_form.php',
      data: {
        'ma_id': ma_id
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-modal .modal-body').html(result);
        $('#popup-modal').modal();
        // pageload_custom(update_page,'#admin-territory-list');
      }
    });
  } else if (work == 'del') { // 삭제
    $('#confirm-modal').confirm('회중 일정을 삭제하시겠습니까?').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/pages/admin_addschedule_work.php',
          data: {
            'work': 'del',
            'del_id': ma_id
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (result) {
            pageload_custom(BASE_PATH + '/pages/admin_addschedule_list.php', '#admin_addschedule_list');
          },
          complete: function (xhr, textStatus) {
            $('#toast').toastMessage('삭제 완료');
          }
        });
      }
    });
  }
  return false;
}

// 호별 구역보기에서 상태 추가/확인 버튼
function condition_work(table, work, pid, name) {
  // 중복 호출 방지
  if ($('#condition-modal').hasClass('loading')) {
    return false;
  }

  // 파라미터 검증
  if (!table || !work || !pid) {
    console.error('condition_work: 필수 파라미터가 누락되었습니다.');
    alert('필수 정보가 누락되었습니다.');
    return false;
  }

  $('#condition-modal').addClass('loading');

  $.ajax({
    url: BASE_PATH + '/include/condition_form.php',
    data: {
      table: table,
      pid: pid,
      work: work,
      name: name || ''
    },
    type: 'POST',
    dataType: 'html',
    timeout: 10000, // 10초 타임아웃
    success: function (result) {
      if (result && result.trim() !== '') {
        $('#condition-modal .modal-body').html(result);
        $('#condition-modal').modal('show');
      } else {
        console.error('condition_work: 서버에서 빈 응답을 받았습니다.');
        alert('특이사항 정보를 불러올 수 없습니다.');
      }
    },
    error: function (xhr, status, error) {
      console.error('condition_work error:', error, 'status:', status);
      if (status === 'timeout') {
        alert('요청 시간이 초과되었습니다. 다시 시도해주세요.');
      } else {
        alert('특이사항을 불러오는 중 오류가 발생했습니다.');
      }
    },
    complete: function (xhr, textStatus) {
      $('#condition-modal').removeClass('loading');
    }
  });
}

// 구역카드 비고
function memo_work(table, pid) {
  $.ajax({
    url: BASE_PATH + '/include/memo_form.php',
    data: {
      table: table,
      pid: pid
    },
    type: 'POST',
    async: false,
    dataType: 'html',
    success: function (result) {

      $('#popup-modal .modal-body').html(result);
      $('#popup-modal').modal();

    },
    complete: function (xhr, textStatus) {
    }
  });
}

// 부재 / 방문 클릭할떄마다 즉시 업데이트
function visit_check(table, pid, e) {
  // 중복/겹침 방지 플래그
  if (window.territory_visit_busy) return false;
  window.territory_visit_busy = true;

  // 같은 세대 다른 체크 해제 후 현재만 유지
  $(e).closest('td').closest('tr').find('.visit-check').not($(e)).prop("checked", false);

  let visit = $(e).val();
  if (!$(e).is(":checked")) {
    visit = '';
  }

  // 클릭 중 재로딩 방지: 기존 타이머 클리어
  if (typeof timeout !== 'undefined') {
    clearTimeout(timeout);
  }

  // UI 잠금
  const $rowChecks = $(e).closest('tr').find('.visit-check');
  $rowChecks.prop('disabled', true);

  $.ajax({
    url: BASE_PATH + '/include/ajax_work.php',
    data: {
      'work': 'house_visit_update',
      'table': table,
      'pid': pid,
      'visit': visit
    },
    type: 'POST',
    async: true,
    dataType: 'html',
    complete: function (xhr, textStatus) {
      // 타이머만 다시 설정 (즉시 로딩 없이 조용히 병합될 예정)
      if (typeof timeout !== 'undefined') {
        timeout = setTimeout(function () { if (typeof territory_view_update === 'function') { territory_view_update(true); } }, 20000);
      }
      // UI 잠금 해제
      $rowChecks.prop('disabled', false);
      window.territory_visit_busy = false;
    }
  });

  return false;
}

// 전화구역 타입 구역보기 화면에서 더 많은 정보 클릭
function show_tel_info(tph_id) {
  $.ajax({
    url: BASE_PATH + '/include/ajax_work.php',
    data: {
      'work': 'show_tel_info',
      'tph_id': tph_id
    },
    type: 'POST',
    async: false,
    dataType: 'html',
    success: function (result) {
      $('#popup-modal .modal-body').html(result);
      $('#popup-modal').modal();
    },
    complete: function (xhr, textStatus) {
    }
  });
}

function logout() {
  $('#confirm-modal').confirm('정말 로그아웃하시겠습니까?').on({
    confirm: function () {
      location.href = BASE_PATH + '/logout.php';
    }
  });
  return false;
}

function attend_ministry(s_date, ms_id, el) {
  let work;
  if ($(el).text() == '참석') {
    message = '참석';
    work = 'today_support';
  } else if ($(el).text() == '지원') {
    message = '지원';
    work = 'today_support';
  } else {
    message = '취소';
    work = 'del';
  }

  $('#confirm-modal').confirm(message + '하시겠습니까?').on({
    confirm: function () {
      $.ajax({
        url: BASE_PATH + '/pages/meeting_work.php',
        data: {
          'work': work,
          'ms_id': ms_id,
          's_date': s_date
        },
        type: 'POST',
        async: true,
        dataType: 'html',
        success: function (xhr, textStatus) {
          if (xhr == 'disabled') {
            alert(message + " 가능한 인원수를 초과하였습니다.");
          } else if (xhr == 'duplicated') {
            alert("같은 시간 모임에는 중복 " + message + "이 불가능합니다.");
          } else {
            $('#toast').toastMessage(message + ' 완료');
          }
          // 로컬 날짜 기준으로 오늘 봉사 목록 갱신
          const now = new Date();
          const today = [
            now.getFullYear(),
            String(now.getMonth() + 1).padStart(2, '0'),
            String(now.getDate()).padStart(2, '0')
          ].join('-');
          pageload_custom(BASE_PATH + '/pages/today_service_list.php?s_date=' + today, '#today-service-list');
          // 전시대 달력/목록도 로컬 날짜로 갱신
          pageload_custom(BASE_PATH + '/pages/meeting_calendar.php?s_date=' + today + '&toYear=' + today.split('-')[0] + '&toMonth=' + String(parseInt(today.split('-')[1], 10)), '#meeting_calendar');
          pageload_custom(BASE_PATH + '/pages/meeting_calendar_schedule.php?s_date=' + today, '#meeting_calendar_schedule');
        }
      });
    }
  });

  return false;
}

// 호별 구역관리 > 세대편집 에서 세대추가
function territory_house_add() {
  let length = $("#territory_house_form table tbody tr").length;
  let new_tr = '<tr>';
  new_tr += '<td class="text-center dragHandle align-middle"><i class="bi bi-grip-vertical"></i></td>';
  new_tr += '<td class="text-center align-middle"><input type="hidden" name="territory_house[add_' + length + '][add]" value="add"></td>';
  new_tr += '<td class="text-center align-middle">';
  new_tr += '<button type="button" class="btn btn-outline-secondary btn-sm align-middle border-0 p-0" onclick="$(this).parent().parent().prev().before($(this).parent().parent());"><i class="bi bi-caret-up-fill h4"></i></button>';
  new_tr += '<button type="button" class="btn btn-outline-secondary btn-sm align-middle border-0 p-0" onclick="$(this).parent().parent().next().after($(this).parent().parent());"><i class="bi bi-caret-down-fill h4"></i></button>';
  new_tr += '</td>';
  new_tr += '<td><input type="text" value="" class="form-control" name="territory_house[add_' + length + '][h_address1]"></td>';
  new_tr += '<td><input type="text" value="" class="form-control" name="territory_house[add_' + length + '][h_address2]"></td>';
  new_tr += '<td><input type="text" value="" class="form-control" name="territory_house[add_' + length + '][h_address3]"></td>';
  new_tr += '<td><input type="text" value="" class="form-control" name="territory_house[add_' + length + '][h_address4]"></td>';
  new_tr += '<td><input type="text" value="" class="form-control" name="territory_house[add_' + length + '][h_address5]"></td>';
  new_tr += '<td class="text-center align-middle"></td>';
  new_tr += '<td class="text-center align-middle"><input type="checkbox" name="territory_house[add_' + length + '][delete]" value="delete"></td>';
  new_tr += '<td class="text-center dragHandle align-middle"><i class="bi bi-grip-vertical"></i></td>';
  new_tr += '</tr>';

  $('#territory_house_form table tbody').append(new_tr);

  $("#territory_house_form table").tableDnD({
    onDragClass: "myDrag",
    dragHandle: ".dragHandle"
  });

  $("#territory_house_form tr").hover(function () {
    $(this.cells[0]).addClass('showDragHandle');
    $(this.cells[10]).addClass('showDragHandle');
  }, function () {
    $(this.cells[0]).removeClass('showDragHandle');
    $(this.cells[10]).removeClass('showDragHandle');
  });

}

// 전화 구역관리 > 세대편집 에서 세대추가
function telephone_house_add() {
  let length = $("#telephone_house_form table tbody tr").length;
  let new_tr = '<tr>';
  new_tr += '<td class="text-center dragHandle align-middle"><i class="bi bi-grip-vertical"></i></td>';
  new_tr += '<td class="text-center align-middle"><input type="hidden" name="telephone_house[add_' + length + '][add]" value="add"></td>';
  new_tr += '<td class="text-center align-middle">';
  new_tr += '<button type="button" class="btn btn-outline-secondary btn-sm align-middle border-0 p-0" onclick="$(this).parent().parent().prev().before($(this).parent().parent();"><i class="bi bi-caret-up-fill h4"></i></button>';
  new_tr += '<button type="button" class="btn btn-outline-secondary btn-sm align-middle border-0 p-0" onclick="$(this).parent().parent().next().after($(this).parent().parent();"><i class="bi bi-caret-down-fill h4"></i></button>';
  new_tr += '</td>';
  new_tr += '<td><input type="text" value="" class="form-control" name="telephone_house[add_' + length + '][tph_number]"></td>';
  new_tr += '<td><input type="text" value="" class="form-control" name="telephone_house[add_' + length + '][tph_type]"></td>';
  new_tr += '<td><input type="text" value="" class="form-control" name="telephone_house[add_' + length + '][tph_name]"></td>';
  new_tr += '<td><input type="text" value="" class="form-control" name="telephone_house[add_' + length + '][tph_address]"></td>';
  new_tr += '<td class="text-center align-middle"></td>';
  new_tr += '<td class="text-center align-middle"><input type="checkbox" name="telephone_house[add_' + length + '][delete]" value="delete"></td>';
  new_tr += '<td class="text-center dragHandle align-middle"><i class="bi bi-grip-vertical"></i></td>';
  new_tr += '</tr>';

  $('#telephone_house_form table tbody').append(new_tr);

  $("#telephone_house_form table").tableDnD({
    onDragClass: "myDrag",
    dragHandle: ".dragHandle"
  });

  $("#telephone_house_form tr").hover(function () {
    $(this.cells[0]).addClass('showDragHandle');
    $(this.cells[9]).addClass('showDragHandle');
  }, function () {
    $(this.cells[0]).removeClass('showDragHandle');
    $(this.cells[9]).removeClass('showDragHandle');
  });
}

//관리자 전도인관리 엑셀 업로드
function member_excelupload_modal() {
  $('#mb-excelupload-modal .custom-file-input').val('');
  $('#mb-excelupload-modal .custom-file-label').text('파일선택');
  $('#mb-excelupload-modal').modal();
}

$(document).ready(function () {

  // 재방문 양도
  $('body').on('submit', '#returnvisit-transfer-form', function () {
    let formData = new FormData($(this)[0]);
    let table = $(this).find('input[name=table]').val();
    let pid = $(this).find('input[name=pid]').val();
    $.ajax({
      url: BASE_PATH + '/pages/minister_work.php',
      data: formData,
      type: 'POST',
      async: false,
      processData: false,
      contentType: false,
      success: function (result) {
        if (table == 'territory') {
          $('#minister-returnvisit div[rv_index=h_' + pid + ']').hide();
        } else if (table == 'telephone') {
          $('#minister-returnvisit div[rv_index=tph_' + pid + ']').hide();
        }
        if (v_admin_house) {
          v_admin_house.updateHouse(pid);
        }
      },
      complete: function (xhr, textStatus) {
        $('#popup-modal .modal-body').html('');
        $('#popup-modal').modal('hide');
        $('#toast').toastMessage('양도 완료');
      }
    });
  });

  // 메뉴 스크롤 고정
  // var obj = $(".menu.menu-sub ul.nav-tabs li a.nav-link.active").offset();
  // if(obj){$('.menu.menu-sub ul.nav-tabs').scrollLeft(obj.left);}

  if (("standalone" in window.navigator) && window.navigator.standalone) {
    // For iOS Apps
    $("a").on("click", function (e) {

      let new_location = $(this).attr("href");
      if (new_location != undefined && new_location.substr(0, 1) != "#" && new_location != '' && $(this).attr("data-method") == undefined) {
        e.preventDefault();
        window.location = new_location;
      }
    });
  }

  // 선택한 구역들을 모임으로 분배 / 삭제
  $('#container').on('submit', '#admin_territory_assign', function () {

    let formData = new FormData($(this)[0]);

    // 체크박스 중 하나라도 체크된 것이 있는지 확인
    let isChecked = false;
    $('input[type="checkbox"][name^="tt_id"]').each(function () {
      if ($(this).is(':checked')) {
        isChecked = true;
        return false; // 하나라도 체크된 것이 있으면 반복 종료
      }
    });

    if (isChecked) {
      let message = '선택한 구역들을 선택한 모임 계획으로 분배하시겠습니까?';
      let work = formData.get('work');
      if (work == 'check_delete') {
        message = '선택한 구역들을 정말 삭제하시겠습니까?<br>구역에 포함된 세대 정보도 삭제됩니다.';
      } else if (work == 'check_reset') {
        message = '선택한 구역들을 정말 리셋하시겠습니까?<br>선택된 구역 내의 배정, 봉사 데이터는 삭제되며 기록으로 남습니다.';
      }
      $('#confirm-modal').confirm(message).on({
        confirm: function () {
          $.ajax({
            url: BASE_PATH + '/pages/admin_territory_work.php',
            data: formData,
            processData: false,
            type: 'POST',
            async: false,
            contentType: false,
            dataType: 'html',
            success: function (result) {
              // pageload_custom(BASE_PATH+'/pages/admin_territory_list.php?s_type='+s_type+'&s_assign='+s_assign+'&s_status='+s_status+'&num='+num+'&name='+name,'#admin-territory-list');
              if (typeof v_admin_territory !== 'undefined') {
                v_admin_territory.searchFilter();
                $('#admin-territory-list input[type=checkbox]').prop('checked', false);
                $('#admin-territory-list tbody tr').removeClass('checked');
              }
            }
          });
          if (work == 'assign') {
            $('#toast').toastMessage('분배 완료');
          } else if (work == 'check_delete') {
            $('#toast').toastMessage('삭제 완료');
          } else if (work == 'check_reset') {
            $('#toast').toastMessage('리셋 완료');
          }
        }
      });
    } else {
      alert('구역을 선택해 주세요.');
    }

    return false;
  });

  // 선택한 전시대 장소들을 모임으로 분배
  $('#container').on('submit', '#admin_display_assign', function () {

    let formData = new FormData($(this)[0]);

    // 체크박스 중 하나라도 체크된 것이 있는지 확인
    let isChecked = false;
    let checkedBoxes = $('input[type="checkbox"][name="dp_id[]"]:checked');

    if (checkedBoxes.length > 0) {
      isChecked = true;

      // 선택된 체크박스들을 분배 폼에 추가
      checkedBoxes.each(function () {
        formData.append('dp_id[]', $(this).val());
      });
    }

    if (isChecked) {
      let message = '선택한 전시대 장소들을 선택한 모임 계획으로 분배하시겠습니까?';
      let work = formData.get('work');
      $('#confirm-modal').confirm(message).on({
        confirm: function () {

          $.ajax({
            url: BASE_PATH + '/pages/admin_display_place_assign.php',
            data: formData,
            processData: false,
            type: 'POST',
            async: false,
            contentType: false,
            dataType: 'json',
            success: function (result) {
              console.log('Success response:', result);
              if (result.success) {
                // 페이지 새로고침
                window.location.reload();
              } else {
                alert(result.message);
              }
            },
            error: function (xhr, status, error) {
              console.log('Error response:', xhr.responseText);
              console.log('Status:', status);
              console.log('Error:', error);
              alert('처리 중 오류가 발생했습니다.');
            }
          });
          if (work == 'assign') {
            $('#toast').toastMessage('분배 완료');
          } else if (work == 'check_delete') {
            $('#toast').toastMessage('삭제 완료');
          } else if (work == 'check_reset') {
            $('#toast').toastMessage('리셋 완료');
          }
        }
      });
    } else {
      alert('전시대 장소를 선택해 주세요.');
    }

    return false;
  });

  // 선택한 구역들을 모임으로 분배 / 삭제
  $('#container').on('submit', '#admin_telephone_assign', function () {

    let formData = new FormData($(this)[0]);

    // 체크박스 중 하나라도 체크된 것이 있는지 확인
    let isChecked = false;
    $('input[type="checkbox"][name^="tp_id"]').each(function () {
      if ($(this).is(':checked')) {
        isChecked = true;
        return false; // 하나라도 체크된 것이 있으면 반복 종료
      }
    });

    if (isChecked) {
      let message = '선택한 구역들을 선택한 모임 계획으로 분배 하시겠습니까?';
      let work = formData.get('work');
      if (work == 'check_delete') {
        message = '선택한 구역들을 정말 삭제하시겠습니까?<br>구역에 포함된 세대 정보도 삭제됩니다.';
      } else if (work == 'check_reset') {
        message = '선택한 구역들을 정말 리셋하시겠습니까?<br>선택된 구역 내의 배정, 봉사 데이터는 삭제되며 기록으로 남습니다.';
      }
      $('#confirm-modal').confirm(message).on({
        confirm: function () {

          $.ajax({
            url: BASE_PATH + '/pages/admin_telephone_work.php',
            data: formData,
            processData: false,
            type: 'POST',
            async: false,
            contentType: false,
            dataType: 'html',
            success: function (result) {
              // pageload_custom(BASE_PATH+'/pages/admin_telephone_list.php?s_type='+s_type+'&s_assign='+s_assign+'&s_status='+s_status+'&num='+num+'&name='+name,'#admin-telephone-list');
              if (typeof v_admin_telephone !== 'undefined') {
                v_admin_telephone.searchFilter();
                $('#admin-telephone-list input[type=checkbox]').prop('checked', false);
                $('#admin-telephone-list tbody tr').removeClass('checked');
              }
            }
          });
          if (work == 'assign') {
            $('#toast').toastMessage('분배 완료');
          } else if (work == 'check_delete') {
            $('#toast').toastMessage('삭제 완료');
          } else if (work == 'check_reset') {
            $('#toast').toastMessage('리셋 완료');
          }

        }
      });
    } else {
      alert('구역을 선택해 주세요.');
    }

    return false;
  });

  // 선택한 세대들을 구역으로 배정 / 삭제
  $('#container').on('submit', '#admin_house_assign', function () {

    let formData = new FormData($(this)[0]);

    // 체크박스 중 하나라도 체크된 것이 있는지 확인
    let isChecked = false;
    $('input[type="checkbox"][name^="h_id"]').each(function () {
      if ($(this).is(':checked')) {
        isChecked = true;
        return false; // 하나라도 체크된 것이 있으면 반복 종료
      }
    });

    if (isChecked) {
      let message = '선택한 세대들을 선택한 구역으로 이동하시겠습니까?';
      let work = formData.get('work');
      if (work == 'check_delete') {
        message = '선택한 세대들을 정말 삭제하시겠습니까? 관련 기록도 모두 삭제됩니다.';
      }
      $('#confirm-modal').confirm(message).on({
        confirm: function () {

          $.ajax({
            url: BASE_PATH + '/pages/admin_house_work.php',
            data: formData,
            processData: false,
            type: 'POST',
            async: false,
            contentType: false,
            dataType: 'html',
            success: function (result) {
              if (result == 'invalid_id') {
                $('#toast').toastMessage('업데이트 실패: 존재하지 않는 구역 ID입니다');
              } else {
                if (v_admin_house) {
                  v_admin_house.searchHouse();
                  $('#admin_house_list input[type=checkbox]').prop('checked', false);
                  $('#admin_house_list tbody tr').removeClass('checked');
                }
                // pageload_custom(BASE_PATH+'/pages/admin_house_list.php?type='+type+'&h_assign='+h_assign+'&h_address1='+h_address1+'&h_address2='+h_address2+'&h_address3='+h_address3+'&h_address4='+h_address4+'&h_address5='+h_address5+'&tph_number='+tph_number+'&tph_type='+tph_type+'&tph_name='+tph_name+'&tph_address='+tph_address+'&h_id='+h_id,'#admin_house_list');
              }
            }
          });
          if (work == 'assign') {
            $('#toast').toastMessage('이동 완료');
          } else if (work == 'check_delete') {
            $('#toast').toastMessage('삭제 완료');
          }

        }
      });
    } else {
      alert('세대를 선택해 주세요.');
    }

    return false;
  });

  $('body').on('click', '#assign-form .chip label', function () {
    if ($(this).hasClass('no_display')) {
      $('#no_display_text').show(0).delay(4000).hide(0);
    }
  });

  //인도자 모임 내용 작성후 저장 시
  $('#container').on('submit', '#guide_record_form', function () {
    let formData = new FormData($(this)[0]);
    $.ajax({
      url: BASE_PATH + '/pages/guide_work.php',
      data: formData,
      type: 'POST',
      async: false,
      processData: false,
      contentType: false,
      success: function (result) {
      },
      complete: function (xhr, textStatus) {
        $('#toast').toastMessage('저장 완료');
      }
    });
    return false;
  });

  // 인도자 > 봉사모임 에서 '오늘 날짜로' 버튼 클릭시 (로컬 시간 기준)
  $('#container').on('click', '#guide_history_date button', function () {
    const now = new Date();
    const today = [
      now.getFullYear(),
      String(now.getMonth() + 1).padStart(2, '0'),
      String(now.getDate()).padStart(2, '0')
    ].join('-'); // 로컬 타임존 기준 YYYY-MM-DD

    $('#guide_history_date input').val(today);  // input 필드에 값 설정
    pageload_custom(BASE_PATH + '/pages/guide_history_list.php?s_date=' + today, '#guide_history_list');
  });

  // 인도자 > 봉사모임 에서 date input 선택시
  $('#container').on('change', '#guide_history_date input[type="date"]', function () {
    var selected = $(this).val();
    pageload_custom(BASE_PATH + '/pages/guide_history_list.php?s_date=' + selected, '#guide_history_list');
  });

  // 일반 구역카드 폼 입력하고 저장 버튼 클릭시 (24.12.29 with ChatGPT)
  $('body').on('submit', '#admin_territory_form', function (e) {
    e.preventDefault(); // 폼 기본 동작 방지

    const formData = new FormData(this); // 폼 데이터를 가져오기
    const work = $(this).find('input[name="work"]').val();

    ajaxRequest(BASE_PATH + '/pages/admin_territory_work.php', formData, false, false, 'html', function (successId) {

      if (work === 'add' && typeof v_admin_territory !== 'undefined') {
        v_admin_territory.insertTerritory(successId);
      } else if (work === 'edit' && typeof v_admin_territory !== 'undefined') {
        v_admin_territory.updateTerritory(successId);
      }

      // 성공 메시지 및 UI 업데이트
      $('#popup-modal .modal-body').html('');
      $('#popup-modal').modal('hide');
      $('#toast').toastMessage(work === 'add' ? '추가 완료' : '수정 완료');
    });
  });

  // 전화 구역카드 폼 입력하고 저장 버튼 클릭시 (25.01.15 with ChatGPT)
  $('body').on('submit', '#admin_telephone_form', function (e) {
    e.preventDefault(); // 폼 기본 동작 방지

    const formData = new FormData(this); // 폼 데이터를 가져오기
    const work = $(this).find('input[name="work"]').val();

    ajaxRequest(BASE_PATH + '/pages/admin_telephone_work.php', formData, false, false, 'html', function (successId) {

      if (work === 'add' && typeof v_admin_telephone !== 'undefined') {
        v_admin_telephone.insertTelephone(successId);
      } else if (work === 'edit' && typeof v_admin_telephone !== 'undefined') {
        v_admin_telephone.insertTelephone(successId);
      }

      // 성공 메시지 및 UI 업데이트
      $('#popup-modal .modal-body').html('');
      $('#popup-modal').modal('hide');
      $('#toast').toastMessage(work === 'add' ? '추가 완료' : '수정 완료');
    });
  });

  // 구역복제 팝업에서 submit
  $('body').on('submit', 'form#copy-territory', function (e) {
    e.preventDefault();
    let formData = new FormData($(this)[0]);
    let table = $(this).find('input[name="table"]').val();
    let url;
    if (table == 'territory' || table == 'letter') {
      url = BASE_PATH + '/pages/admin_territory_work.php';
    } else if (table == 'telephone') {
      url = BASE_PATH + '/pages/admin_telephone_work.php';
    } else {
      return false;
    }
    $.ajax({
      url: url,
      data: formData,
      processData: false,
      type: 'POST',
      async: false,
      contentType: false,
      dataType: 'html',
      success: function (result) {
        var arr = result.split(",");

        $.each(arr, function (index, el) {
          if (table == 'territory' || table == 'letter') {
            if (typeof v_admin_territory !== 'undefined') {
              v_admin_territory.insertTerritory(el);
            }
          } else if (table == 'telephone') {
            if (typeof v_admin_telephone !== 'undefined') {
              v_admin_telephone.insertTelephone(el);
            }
          }
        });

      },
      complete: function (xhr, textStatus) {
        $('#toast').toastMessage('복제 완료');
        $('#popup-min-modal').modal('hide');
      }
    });
    return false;
  });

  // 엑셀업로드 팝업에서 submit
  $('body').on('submit', '#excelupload-modal form', function (e) {
    e.preventDefault();
    let formData = new FormData($(this)[0]);
    let table = $(this).find('input[name="table"]').val();
    // var update_page = $(this).find('input[name="update_page"]').val();
    // var update_wrap_id = $(this).find('input[name="update_wrap_id"]').val();
    let pid = $(this).find('input[name="pid"]').val();
    let url;
    if (table == 'territory') {
      url = BASE_PATH + '/include/territory_excel_upload.php';
    } else if (table == 'telephone') {
      url = BASE_PATH + '/include/telephone_excel_upload.php';
    } else {
      return false;
    }
    $.ajax({
      url: url,
      data: formData,
      processData: false,
      type: 'POST',
      async: false,
      contentType: false,
      dataType: 'html',
      success: function (result) {
        if (table == 'territory') {
          if (typeof v_admin_territory !== 'undefined') {
            v_admin_territory.updateTerritory(pid);
          }
        } else if (table == 'telephone') {
          if (typeof v_admin_telephone !== 'undefined') {
            v_admin_telephone.updateTelephone(pid);
          }
        }
      },
      complete: function (xhr, textStatus) {
        $('#toast').toastMessage('업로드 완료');
        $('#excelupload-modal').modal('hide');
        $('#excelupload-modal form input[name="pid"]').val('');
        $('#excelupload-modal form input[name="excel"]').val('');
        // $('#excelupload-modal form input[name="update_page"]').val('');
        // $('#excelupload-modal form input[name="update_wrap_id"]').val('');
        // pageload_custom(update_page,update_wrap_id);
      }
    });
    return false;
  });

  // 구역보기에서 상태 변경 submit
  $('body').on('submit', '#condition-modal form', function () {
    let formData = new FormData($(this)[0]);
    let table = $(this).find('input[name="table"]').val();
    $.ajax({
      url: BASE_PATH + '/include/condition_work.php',
      data: formData,
      type: 'POST',
      async: false,
      dataType: 'html',
      processData: false,
      contentType: false,
      success: function (result) {
      },
      complete: function (xhr, textStatus) {
        $('#condition-modal').modal('hide');
        if (table == 'territory') {
          territory_view_update(); // territory_view_list.php 업데이트
        } else if (table == 'telephone') {
          telephone_view_update(); // telephone_view_list.php 업데이트
        }
      }
    });

    return false;
  });

  // 구역보기에서 구역추가요청 submit
  $('body').on('submit', '#memo-form', function () {
    let table = $(this).find('input[name="table"]').val();
    let pid = $(this).find('input[name="pid"]').val();
    let formData = new FormData($(this)[0]);
    $.ajax({
      url: BASE_PATH + '/include/ajax_work.php',
      data: formData,
      type: 'POST',
      async: false,
      dataType: 'html',
      processData: false,
      contentType: false,
      success: function (result) {
        if (table == 'territory') {
          if (typeof v_admin_territory !== 'undefined') {
            v_admin_territory.updateTerritory(pid);
          }
        } else if (table == 'telephone') {
          if (typeof v_admin_telephone !== 'undefined') {
            v_admin_telephone.updateTelephone(pid);
          }
        }
      },
      complete: function (xhr, textStatus) {
        $('#popup-modal .modal-body').html('');
        $('#popup-modal').modal('hide');

        $('#toast').toastMessage('저장 완료');
      }
    });

    return false;
  });

  // 재방문 기록 추가
  $('body').on('submit', '#returnvisitmemo_form', function () {
    let formData = new FormData($(this)[0]);
    let pid = $(this).find('input[name="pid"]').val();
    let table = $(this).find('input[name="table"]').val();
    $.ajax({
      url: BASE_PATH + '/pages/minister_work.php',
      data: formData,
      type: 'POST',
      async: false,
      processData: false,
      contentType: false,
      success: function (result) {
        if (table == 'territory') {
          location.href = BASE_PATH + "/pages/minister_returnvisit_management.php?h_id=" + pid;
        } else if (table == 'telephone') {
          location.href = BASE_PATH + "/pages/minister_returnvisit_management_telephone.php?tph_id=" + pid;
        }
      },
      complete: function (xhr, textStatus) {
        $('#toast').toastMessage('추가 완료');
      }
    });
    return false;
  });

  // 재방문 기록 수정
  $('body').on('submit', '#returnvisitmemo_update form', function () {
    let formData = new FormData($(this)[0]);
    let h_id = $(this).find('input[name="h_id"]').val();
    $.ajax({
      url: BASE_PATH + '/pages/minister_work.php',
      data: formData,
      type: 'POST',
      async: false,
      processData: false,
      contentType: false,
      success: function (result) {
        $('#toast').toastMessage('업데이트 완료');
      }
    });
    return false;
  });

  // 구역 상태 변경 모달에서 상태 선택시 재방, 연구이면 방문시간 나오게
  $('body').on('change', '#condition-modal form #condition', function () {
    if ($(this).val() == '1' || $(this).val() == '2') {
      $('#condition-modal #datetime').parent().parent().show();
    } else {
      $('#condition-modal #datetime').parent().parent().hide();
    }
  });

  $('.territory-view').on('click', '#territory-view-modal button', function () {
    clearTimeout(timeout);
  });

  $('#container').on('submit', '#site_option', function () {
    let formData = new FormData($(this)[0]);
    $.ajax({
      url: BASE_PATH + '/include/ajax_work.php',
      data: formData,
      processData: false,
      type: 'POST',
      async: false,
      contentType: false,
      dataType: 'html',
      success: function (result) {
      },
      complete: function (xhr, textStatus) {
        $('#toast').toastMessage('저장 완료');
      }
    });
    return false;
  });

  // 호별구역관리 > 세대편집 저장
  $('body').on('submit', '#territory_house_form', function () {
    let tt_id = $(this).find('input[name="tt_id"]').val();
    let data = {};
    let i = 0;
    $(this).find('table tbody tr').each(function () {
      let rowId = $(this).find('input[name*="[h_address1]"]').attr('name'); // `territory_house[12760][h_address1]` 형식
      if (rowId) {
        // 행 ID 추출 (예: 12760)
        let match = rowId.match(/territory_house\[(add_\d+|\d+)\]/);
        if (match) {
          let h_id = match[1]; // ID만 추출

          // 데이터를 저장
          data[i] = {
            h_id: h_id,
            tt_id: tt_id,
            add: $(this).find('input[name*="[add]"]').val() || "",
            h_address1: $(this).find('input[name*="[h_address1]"]').val() || "",
            h_address2: $(this).find('input[name*="[h_address2]"]').val() || "",
            h_address3: $(this).find('input[name*="[h_address3]"]').val() || "",
            h_address4: $(this).find('input[name*="[h_address4]"]').val() || "",
            h_address5: $(this).find('input[name*="[h_address5]"]').val() || "",
            delete: $(this).find('input[name*="[delete]"]:checked').val() || null // 체크박스 데이터
          };
        }
      }
      i++;
    });

    ajaxRequest(BASE_PATH + '/pages/admin_territory_work.php', JSON.stringify({ work: 'territory_house', data: data }), true, 'application/json', 'html', function (result) {

      if (typeof v_admin_territory !== 'undefined') {
        v_admin_territory.updateTerritory(tt_id);
      }
      if (typeof v_admin_house !== 'undefined') {
        v_admin_house.onPageChange();
      }

      // 성공 메시지 및 UI 업데이트
      $('#popup-modal .modal-body').html('');
      $('#popup-modal').modal('hide');
      $('#toast').toastMessage('저장 완료');
    });

    return false;
  });

  // 전화구역관리 > 세대편집 저장
  $('body').on('submit', '#telephone_house_form', function () {
    let tp_id = $(this).find('input[name="tp_id"]').val();
    let data = {};
    let i = 0;
    $(this).find('table tbody tr').each(function () {
      let rowId = $(this).find('input[name*="[tph_number]"]').attr('name'); // `territory_house[12760][h_address1]` 형식
      if (rowId) {
        // 행 ID 추출 (예: 12760)
        let match = rowId.match(/telephone_house\[(add_\d+|\d+)\]/);
        if (match) {
          let tph_id = match[1]; // ID만 추출

          // 데이터를 저장
          data[i] = {
            tph_id: tph_id,
            tp_id: tp_id,
            add: $(this).find('input[name*="[add]"]').val() || "",
            tph_number: $(this).find('input[name*="[tph_number]"]').val() || "",
            tph_type: $(this).find('input[name*="[tph_type]"]').val() || "",
            tph_name: $(this).find('input[name*="[tph_name]"]').val() || "",
            tph_address: $(this).find('input[name*="[tph_address]"]').val() || "",
            delete: $(this).find('input[name*="[delete]"]:checked').val() || null // 체크박스 데이터
          };
        }
      }
      i++;
    });

    ajaxRequest(BASE_PATH + '/pages/admin_telephone_work.php', JSON.stringify({ work: 'telephone_house', data: data }), true, 'application/json', 'html', function (result) {

      if (typeof v_admin_telephone !== 'undefined') {
        v_admin_telephone.updateTelephone(tp_id);
      }
      if (typeof v_admin_house !== 'undefined') {
        v_admin_house.onPageChange();
      }

      // 성공 메시지 및 UI 업데이트
      $('#popup-modal .modal-body').html('');
      $('#popup-modal').modal('hide');
      $('#toast').toastMessage('저장 완료');
    });

    return false;
  });

  // 호별구역관리 > 봉사기록 저장
  $('body').on('submit', '#territory_record_form', function () {
    // var update_page = $(this).find('input[name="update_page"]').val();
    // var update_wrap_id = $(this).find('input[name="update_wrap_id"]').val();
    let tt_id = $(this).find('input[name="tt_id"]').val();
    let formData = new FormData($(this)[0]);

    // 체크박스 중 하나라도 체크된 것이 있는지 확인
    let isChecked = false;
    $('input[type="checkbox"][name^="territory_record"]').each(function () {
      if ($(this).is(':checked')) {
        isChecked = true;
        return false; // 하나라도 체크된 것이 있으면 반복 종료
      }
    });

    if (isChecked) {
      $('#confirm-modal').confirm('봉사기록을 삭제하시겠습니까?<br>삭제하면 다시 되돌릴 수 없습니다.').on({
        confirm: function () {
          $.ajax({
            url: BASE_PATH + '/pages/admin_territory_work.php',
            data: formData,
            processData: false,
            type: 'POST',
            async: false,
            contentType: false,
            dataType: 'html',
            success: function (result) {
              // pageload_custom(update_page,update_wrap_id);
              if (typeof v_admin_territory !== 'undefined') {
                v_admin_territory.updateTerritory(tt_id);
              }
            },
            complete: function (xhr, textStatus) {
              $('#popup-modal .modal-body').html('');
              $('#popup-modal').modal('hide');

              $('#toast').toastMessage('삭제 완료');
            }
          });
        }
      });
    } else {
      alert('기록을 선택해 주세요.');
    }

    return false;
  });

  // 전화구역관리 > 봉사기록 저장
  $('body').on('submit', '#telephone_record_form', function () {
    // var update_page = $(this).find('input[name="update_page"]').val();
    // var update_wrap_id = $(this).find('input[name="update_wrap_id"]').val();
    let tp_id = $(this).find('input[name="tp_id"]').val();
    let formData = new FormData($(this)[0]);

    // 체크박스 중 하나라도 체크된 것이 있는지 확인
    let isChecked = false;
    $('input[type="checkbox"][name^="territory_record"]').each(function () {
      if ($(this).is(':checked')) {
        isChecked = true;
        return false; // 하나라도 체크된 것이 있으면 반복 종료
      }
    });

    if (isChecked) {
      $('#confirm-modal').confirm('봉사기록을 삭제하시겠습니까?<br>삭제하면 다시 되돌릴 수 없습니다.').on({
        confirm: function () {
          $.ajax({
            url: BASE_PATH + '/pages/admin_telephone_work.php',
            data: formData,
            processData: false,
            type: 'POST',
            async: false,
            contentType: false,
            dataType: 'html',
            success: function (result) {
              // pageload_custom(update_page,update_wrap_id);
              if (typeof v_admin_telephone !== 'undefined') {
                v_admin_telephone.updateTelephone(tp_id);
              }
            },
            complete: function (xhr, textStatus) {
              $('#popup-modal .modal-body').html('');
              $('#popup-modal').modal('hide');

              $('#toast').toastMessage('삭제 완료');
            }
          });
        }
      });
    } else {
      alert('기록을 선택해 주세요.');
    }

    return false;
  });

  // 관리자 새대관리에서 검색 구역타입 변경
  $('#admin-house-search-form').on('change', '#admin_house_search_type', function () {
    if (typeof v_admin_house !== 'undefined') {
      v_admin_house.onPageChange();
    }
    let type = $('#admin_house_search_type option:selected').val();
    if (type == '1') {
      $('.search-telephone').hide();
      $('.search-letter').hide();
      $('#admin-house-search-form .search-telephone input').val('');
      $('#admin-house-search-form .search-letter input').val('');
      $('#admin-house-search-form .search-territory').css('display', 'flex');
    } else if (type == '2') {
      $('.search-territory').hide();
      $('.search-letter').hide();
      $('#admin-house-search-form .search-territory input').val('');
      $('#admin-house-search-form .search-letter input').val('');
      $('#admin-house-search-form .search-telephone').css('display', 'flex');
    } else if (type == '3') {
      $('.search-territory').hide();
      $('.search-telephone').hide();
      $('#admin-house-search-form .search-territory input').val('');
      $('#admin-house-search-form .search-telephone input').val('');
      $('#admin-house-search-form .search-letter').css('display', 'flex');
    }
  });

  // 관리자 세대관리에서 검색
  $('#container').on('submit', '#admin-house-search-form', function () {
    let formData = new FormData($(this)[0]);
    $.ajax({
      url: BASE_PATH + '/pages/admin_house_list.php',
      data: formData,
      processData: false,
      type: 'POST',
      async: false,
      contentType: false,
      dataType: 'html',
      success: function (result) {
        $('#admin_house_list').html(result);
      }
    });
    return false;
  });

  // 회중일정관리 폼 > 봉사모임 팝업 연 후 닫을 때 오류 없도록
  $('#meeting-modal').on('hidden.bs.modal', function (event) {
    if ($('#popup-modal').attr('aria-modal') == 'true') {
      $('body').addClass('modal-open').css('padding-right', '16px');
    }
  });

  // 구역보기 > 특이사항 팝업 연 후 닫을 때 오류 없도록
  $('#condition-modal').on('hidden.bs.modal', function (event) {
    if ($('#territory-view-modal').attr('aria-modal') == 'true' || $('#telephone-view-modal').attr('aria-modal') == 'true') {
      $('body').addClass('modal-open').css('padding-right', '16px');
    }
  });

  // 사이트관리 구역경계지도 리셋
  $('#container').on('click', 'button[name="territory_map_reset"]', function () {
    $('#confirm-modal').confirm('구역 경계를 리셋하면 다시 되돌릴 수 없습니다.<br/>오류가 있을 때만 사용해 주세요.').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/include/ajax_work.php',
          data: {
            'work': 'territory_map_reset'
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (result) {
            $('#toast').toastMessage('리셋 완료');
          }
        });
      }
    });
  });


});
