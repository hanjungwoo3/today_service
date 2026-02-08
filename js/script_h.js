$(document).ready(function () {

  //공통

  // 취소 버튼 클릭시
  $('#container').on('click', '#form-cancel', function () {
    let url = $(this).attr('page') + '.php';
    let variation = $(this).attr('data-var');
    let val = $(this).attr('data-val');
    if (variation && val) url = url + '?' + variation + '=' + val;
    $('#confirm-modal').confirm('저장하지 않고 나가시겠습니까?').on({
      confirm: function () {
        location.href = BASE_PATH + url;
      }
    });
    return false;
  });

  //달력 이전달 다음달 클릭시
  $('#container').on('click', '.calendar .calendar_month', function () {
    const calendar = $('.calendar').attr('calendar');
    const list = $('.calendar').attr('list');
    const toYear = $(this).attr('toYear');
    const toMonth = $(this).attr('toMonth');

    // 전환되는 달에 맞춰 s_date를 전달:
    // - 목표 달이 로컬 오늘과 같으면 오늘 날짜
    // - 아니면 해당 달 1일을 선택 상태로 전달 (달력에 빨간 선택 표시 유지)
    const now = new Date();
    const nowYear = now.getFullYear();
    const nowMonth = now.getMonth() + 1;
    const targetYear = parseInt(toYear, 10);
    const targetMonth = parseInt(toMonth, 10);
    const localYmd = [
      nowYear,
      String(nowMonth).padStart(2, '0'),
      String(now.getDate()).padStart(2, '0')
    ].join('-');

    const sDateForTarget =
      (targetYear === nowYear && targetMonth === nowMonth)
        ? localYmd
        : `${targetYear}-${String(targetMonth).padStart(2, '0')}-01`;

    const commonQuery = `?toYear=${toYear}&toMonth=${toMonth}&s_date=${sDateForTarget}`;

    pageload_custom(BASE_PATH + '/pages/' + calendar + '.php' + commonQuery, '#' + calendar);
    pageload_custom(BASE_PATH + '/pages/' + list + '.php' + commonQuery, '#' + list);

    return false;
  });

  //클릭한 달력 날짜 select 효과 주기
  $('#container').on('click', '.calendar td:not(.disabled)', function () {
    $('.badge-danger').removeClass('badge-danger');
    $(this).find('div:first-child').addClass('badge-danger');
  });

  // 엑셀업로드 팝업창 파일 선택시 파일명 노출
  $('.modal').on('change', '.custom-file-input', function () {
    let file_name = $(this).val().replace(/C:\\fakepath\\/i, '');
    $(this).next('.custom-file-label').text(file_name);
  });


  //봉사자 minister

  //봉사자 개인일정 저장시
  $('body').on('submit', '#minister_event_form', function () {
    let formData = new FormData($(this)[0]);
    let s_date, s_date2;
    if (($(this).find('input[name="timeswitch"]').is(':checked'))) {
      s_date = $(this).find('input[name="date"]').val();
      s_date2 = $(this).find('input[name="date2"]').val();
      if (!s_date || !s_date2) {
        alert('시작일과 종료일을 입력해주세요.');
        return false;
      }
      if (s_date > s_date2) {
        alert('종료일이 시작일보다 빠를 수 없습니다.');
        return false;
      }
    } else {
      s_date = $(this).find('input[name="datetime"]').val();
      s_date2 = $(this).find('input[name="datetime2"]').val();
      if (!s_date || !s_date2) {
        alert('시작시간과 종료시간을 입력해주세요.');
        return false;
      }
      if (s_date > s_date2) {
        alert('종료시간이 시작시간보다 빠를 수 없습니다.');
        return false;
      }
    }
    let ym_array = s_date.split('-');
    let toYear = ym_array[0];
    let toMonth = ym_array[1].length >= 2 && ym_array[1].substr(0, 1) == 0 ? ym_array[1].substr(1, ym_array[1].length - 1) : ym_array[1];
    $.ajax({
      url: BASE_PATH + '/pages/minister_schedule_work.php',
      data: formData,
      processData: false,
      type: 'POST',
      async: false,
      contentType: false,
      dataType: 'html',
      success: function (result) {
        pageload_custom(BASE_PATH + '/pages/minister_calendar_schedule.php?s_date=' + s_date, '#minister_calendar_schedule');
        pageload_custom(BASE_PATH + '/pages/minister_calendar.php?s_date=' + s_date + '&toYear=' + toYear + '&toMonth=' + toMonth, '#minister_calendar');
      },
      complete: function (xhr, textStatus) {
        $('#popup-modal .modal-body').html('');
        $('#popup-modal').modal('hide');

        $('#toast').toastMessage('저장 완료');
      }
    });
    return false;
  });

  //봉사자 개인봉사보고 저장시
  $('#container').on('submit', '#minister_report form', function () {
    let formData = new FormData($(this)[0]);
    let s_date = $(this).find('input[name="date"]').val();
    let ym_array = s_date.split('-');
    let toYear = ym_array[0];
    let toMonth = ym_array[1].length >= 2 && ym_array[1].substr(0, 1) == 0 ? ym_array[1].substr(1, ym_array[1].length - 1) : ym_array[1];
    $.ajax({
      url: BASE_PATH + '/pages/minister_schedule_work.php',
      data: formData,
      processData: false,
      type: 'POST',
      async: false,
      dataType: 'html',
      contentType: false,
      processData: false,
      success: function (result) {
        $('#toast').toastMessage('저장 완료');
      },
      complete: function (xhr, textStatus) {
        pageload_custom(BASE_PATH + '/pages/minister_calendar_schedule.php?s_date=' + s_date, '#minister_calendar_schedule');
        pageload_custom(BASE_PATH + '/pages/minister_calendar.php?s_date=' + s_date + '&toYear=' + toYear + '&toMonth=' + toMonth, '#minister_calendar');
      }
    });
    return false;
  });

  //봉사자 봉사모임 계획표 width
  var first_td = $('.minister_meeting_schedule .table-responsive table tbody tr:first td').length;
  var second_td = $('.minister_meeting_schedule .table-responsive table tbody tr:nth-child(5) td').length;
  var td = Math.max(first_td, second_td);
  var min_width = 40 + 85 + 110 * td;
  $('.minister_meeting_schedule .table-responsive table').css('min-width', min_width + 'px');

  //봉사자 나의통계 봉사년도 선택 시
  $('#container').on('change', '#mininster_stat_search', function () {
    let formData = new FormData($(this)[0]);
    $.ajax({
      url: BASE_PATH + '/pages/minister_statistics_sub.php',
      data: formData,
      processData: false,
      type: 'POST',
      async: false,
      dataType: 'html',
      contentType: false,
      processData: false,
      success: function (result) {
        $('#minister_stat').html(result);
      }
    });
    return false;
  });

  //봉사자 개인정보관리에서 저장 버튼 클릭시
  $('#container').on('submit', '#minister_info', function () {
    let formData = new FormData($(this)[0]);
    $.ajax({
      url: BASE_PATH + '/pages/minister_work.php',
      data: formData,
      processData: false,
      type: 'POST',
      async: false,
      dataType: 'html',
      contentType: false,
      processData: false,
      success: function (result) {
      },
      complete: function (xhr, textStatus) {
        $('#toast').toastMessage('저장 완료');
      }
    });
    return false;
  });


  //공지 board

  // 공지 작성후 저장시
  $('#container').on('submit', '#board_frame form', function () {
    let select = $(this).find('select[name="b_guide[]"] option:selected').text();
    let work = $(this).find('input[name="work"]').val();
    let auth = $(this).find('input[name="auth"]').val();
    if (select == '') {
      alert("게시판을 선택해주세요");
      return false;
    } else {
      $.ajax({
        url: BASE_PATH + '/pages/board_work.php',
        data: {
          'work': work,
          'data': $(this).serialize()
        },
        type: 'POST',
        async: false,
        dataType: 'html',
        success: function (result) {
          $('#toast').toastMessage('저장 완료');
        },
        complete: function (xhr, textStatus) {
          $.ajax({
            url: BASE_PATH + '/pages/board_list.php',
            data: { 'auth': auth },
            type: 'POST',
            async: false,
            dataType: 'html',
            beforeSend: function () {
              $('#board_frame').hide();
            },
            success: function (result) {
              $('#board_frame').html(result);
            },
            complete: function (xhr, textStatu) {
              $('#board_frame').show();
            }
          });
        }
      });
    }
    return false;
  });


  //인도자

  //인도자 봉사통계 요일 클릭시
  $('#container').on('change', 'select[name="guide_ms_id"]', function () {
    let ms_id = $(this).val();
    $.ajax({
      url: BASE_PATH + '/pages/guide_statistics_sub.php',
      data: {
        'ms_id': ms_id
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      beforeSend: function (xhr) {
        $('#guide_statistics').html('');
      },
      success: function (result) {
        $('#guide_statistics').html(result);
      },
      complete: function (xhr, textStatus) {
      }
    });
    return false;
  });


  //관리자

  //관리자 전도인 이름 중복 방지
  $('#container').on('keyup', '#member-form #mb_name', function () {
    let mb_id = $('#member-form input[name="mb_id"]').val();
    let mb_name = $(this).val();
    let work = 'search';
    $.ajax({
      url: BASE_PATH + '/pages/admin_member_work.php',
      data: {
        'work': work,
        'mb_id': mb_id,
        'mb_name': mb_name
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        if (!!parseInt(result)) {
          $('.mb_name_alert').html('<div class="text-danger mt-1" role="alert">해당 이름은 이미 사용중입니다.</div>');
          $('#member-form button[type="submit"]').attr('disabled', true);
        } else {
          $('.mb_name_alert').html('');
          $('#member-form button[type="submit"]').attr('disabled', false);
        }
      }
    });
    return false;
  });

  //관리자 전도인 관리 form 저장 시
  $('#container').on('submit', '#member-form', function () {
    $.ajax({
      url: BASE_PATH + '/pages/admin_member_work.php',
      data: { 'data': $(this).serialize() },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        location.href = BASE_PATH + '/pages/admin_member.php';
      },
      complete: function (xhr, textStatus) {
        $('#toast').toastMessage('저장 완료');
      }
    });
    return false;
  });

  //관리자 전도인 관리 form 삭제 시
  $('#container').on('click', '#member-form #member-form-del', function () {
    let mb_id = $(this).attr('mb_id');
    let work = $(this).attr('work');
    if (work == 'del') {
      $('#confirm-modal').confirm('전도인 정보를 삭제하시겠습니까?').on({
        confirm: function () {
          $.ajax({
            url: BASE_PATH + '/pages/admin_member_work.php',
            data: {
              'work': work,
              'mb_id': mb_id
            },
            type: 'POST',
            async: false,
            dataType: 'html',
            success: function (result) {
              location.href = BASE_PATH + '/pages/admin_member.php';
            },
            complete: function (xhr, textStatus) {
              $('#toast').toastMessage('삭제 완료');
            }
          });
        }
      });
    }
    return false;
  });

  //관리자 전도인관리 엑셀업로드 팝업에서 submit
  $('body').on('submit', '#mb-excelupload-modal form', function (e) {
    e.preventDefault();
    let formData = new FormData($(this)[0]);
    $.ajax({
      url: BASE_PATH + '/include/member_excel_upload.php',
      data: formData,
      processData: false,
      type: 'POST',
      async: false,
      contentType: false,
      dataType: 'html',
      success: function (json) {
      },
      complete: function (xhr, textStatus) {
        $('#toast').toastMessage('엑셀업로드 완료');
        location.href = BASE_PATH + "/pages/admin_member.php";
      }
    });
    return false;
  });


  // 관리자 봉사모임관리 봉사모임계획 저장시
  $('body').on('submit', '#admin_meeting_form', function () {
    let formData = new FormData($(this)[0]);
    let ma_id = $(this).find('input[name="ma_id"]').val();
    let ms_id = $(this).find('input[name="ms_id"]').val();
    let type_select = $(this).find('select[name="type"] option:selected').val();
    let mp_select = $(this).find('select[name="place"] option:selected').val();
    if (type_select == '0') {
      $(this).find('select[name="type"]').focus();
    } else if (mp_select == '0') {
      $(this).find('select[name="place"]').focus();
    } else {
      let proceed = true;
      if (ms_id) { // 수정할 때만 확인 메시지 표시
        proceed = confirm('현재 시간 이후의 모든 모임 정보가 업데이트됩니다. 계속하시겠습니까?');
      }
      if (proceed) {
        $.ajax({
          url: BASE_PATH + '/pages/admin_meeting_work.php',
          data: formData,
          processData: false,
          type: 'POST',
          async: false,
          contentType: false,
          dataType: 'html',
          success: function (result) {
            pageload_custom(BASE_PATH + '/pages/admin_meeting_list.php?ma_id=' + ma_id, '#admin_meeting_list');
          },
          complete: function (xhr, textStatus) {
            $('#meeting-modal .modal-body').html('');
            $('#meeting-modal').modal('hide');
            $('#toast').toastMessage('저장 완료');
          }
        });
      }
    }
    return false;
  });

  //관리자 봉사모임장소관리, 전시대 관리, 봉사집단관리 empty input 삭제시
  $('#container').on('click', '.delte_blank_place', function () {
    $(this).parent().parent().remove();
  });

  //관리자 봉사모임장소관리, 전시대 관리, 봉사집단관리에서 input 추가 시
  $('#container').on('click', '#data_add', function () {
    let url = $('.multiple_add_section').data('url');
    let length = $('.multiple_add_section tbody tr').length;
    let content;
    if (url == 'meeting_place') {
      content = '<tr><th><input type="text" class="form-control" name="meeting_place[n][' + (length + 1) + '][name]" required></th><td><input type="text" class="form-control" name="meeting_place[n][' + (length + 1) + '][address]"></td><td><button type="button" class="btn btn-outline-danger delte_blank_place"><i class="bi bi-trash"></i> 삭제</button></td></tr>';
    } else if (url == 'display_place') {
      content = '<tr><td class="text-center align-middle"></td><td><input type="text" class="form-control" name="display_place[n][' + (length + 1) + '][name]" required></td><td><input type="text" class="form-control" name="display_place[n][' + (length + 1) + '][address]"></td><td><input type="number" class="form-control" name="display_place[n][' + (length + 1) + '][count]" min=1 required></td><td class="text-center align-middle">-</td><td><button type="button" class="btn btn-outline-danger delte_blank_place"><i class="bi bi-trash"></i> 삭제</button></td></tr>';
    } else if (url == 'group') {
      content = '<tr><td></td><td><input type="text" class="form-control" name="group[n][' + (length + 1) + '][name]" required></td><td><button type="button" class="btn btn-outline-danger delte_blank_place"><i class="bi bi-trash"></i> 삭제</button></td></tr>';
    }
    $('.multiple_add_section tbody').append(content);
  });

  //관리자 봉사모임장소관리, 전시대관리, 봉사집단관리, 회중일정관리 데이터 저장 시
  $('#container').on('submit', '.multiple_add_section', function () {
    let formData = new FormData($(this)[0]);
    let url = $('.multiple_add_section').data('url');
    let work_url = BASE_PATH + '/pages/admin_' + url + '_work.php';
    let redirect_url = BASE_PATH + '/pages/admin_' + url + '.php';
    $.ajax({
      url: work_url,
      data: formData,
      processData: false,
      type: 'POST',
      async: false,
      contentType: false,
      dataType: 'html',
      success: function (result) {
        location.href = redirect_url;
      },
      complete: function (xhr, textStatus) {
        $('#toast').toastMessage('저장 완료');
      }
    });
    return false;
  });

  //관리자 봉사모임장소관리, 전시대관리, 봉사집단관리, 회중일정관리 데이터 삭제 시
  $('#container').on('click', '#data_delete', function () {
    let del_id = $(this).attr('del_id');
    let url = $('.multiple_add_section').data('url');
    let work_url = BASE_PATH + '/pages/admin_' + url + '_work.php';
    let redirect_url = BASE_PATH + '/pages/admin_' + url + '.php';
    $('#confirm-modal').confirm('해당 데이터를 정말 삭제하시겠습니까?').on({
      confirm: function () {
        $.ajax({
          url: work_url,
          data: {
            'work': 'del',
            'del_id': del_id
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (result) {
            location.href = redirect_url;
          },
          complete: function (xhr, textStatus) {
            $('#toast').toastMessage('삭제 완료');
          }
        });
      }
    });
  });

  //관리자 회중일정관리 회중일정 저장 시
  $('body').on('submit', '#admin_addschedule_form', function () {
    let formData = new FormData($(this)[0]);
    let work = $(this).find('input[name="work"]').val();

    // 자동설정이 체크되어있다면 주일과 요일 필수 선택
    if ($(this).find('input[name="autoswitch"]').is(':checked')) {
      if ($(this).find('select[name="week"]').val() == '0' || $(this).find('select[name="weekday"]').val() == '0') {
        alert('주일과 요일을 선택해야 합니다.');
        return false;  // 폼 제출 중지
      }
    } else {
      // 자동설정이 아닐 경우 날짜/시간 필수 입력 확인
      if ($(this).find('input[name="timeswitch"]').is(':checked')) {
        // 하루 종일 (날짜)
        if (!$(this).find('input[name="date"]').val() || !$(this).find('input[name="date2"]').val()) {
          alert('시작일과 종료일을 입력해주세요.');
          return false;
        }
        if ($(this).find('input[name="date"]').val() > $(this).find('input[name="date2"]').val()) {
          alert('종료일이 시작일보다 빠를 수 없습니다.');
          return false;
        }
      } else {
        // 시간 포함
        if (!$(this).find('input[name="datetime"]').val() || !$(this).find('input[name="datetime2"]').val()) {
          alert('시작시간과 종료시간을 입력해주세요.');
          return false;
        }
        if ($(this).find('input[name="datetime"]').val() > $(this).find('input[name="datetime2"]').val()) {
          alert('종료시간이 시작시간보다 빠를 수 없습니다.');
          return false;
        }
      }
    }

    $.ajax({
      url: BASE_PATH + '/pages/admin_addschedule_work.php',
      data: formData,
      processData: false,
      type: 'POST',
      async: false,
      contentType: false,
      dataType: 'html',
      success: function (result) {
        pageload_custom(BASE_PATH + '/pages/admin_addschedule_list.php', '#admin_addschedule_list');
      },
      complete: function (xhr, textStatus) {
        $('#popup-modal .modal-body').html('');
        $('#popup-modal').modal('hide');
        if (work == 'add') {
          $('#toast').toastMessage('추가 완료');
        } else if (work == 'edit') {
          $('#toast').toastMessage('수정 완료');
        }
      }
    });
    return false;
  });

  //관리자 통계확인 Tab 클릭시
  $('#container').on('click', '#admin_statistics_tab a.nav-link', function () {
    $('ul.nav-tabs a.nav-link').removeClass("active");
    $(this).addClass("active");
    let url = $(this).attr('url');
    $.get('/pages/' + url + '.php', function (data) {
      $('#admin_statistics_view').html(data);
    });
    return false;
  });

  //관리자 통계확인 참석자 통계 봉사년도 선택 시
  $('#container').on('change', '#stat_meeting_year_search', function () {
    let formData = new FormData($(this)[0]);
    $.ajax({
      url: BASE_PATH + '/pages/admin_statistics_meeting_sub.php',
      data: formData,
      processData: false,
      type: 'POST',
      async: false,
      dataType: 'html',
      contentType: false,
      processData: false,
      success: function (result) {
        $('#statistics_meeting').html(result);
      }
    });
    return false;
  });

  //관리자 통계확인 form submit 시
  $('#container').on('submit', '#admin_statistics_view form', function () {
    let formData = new FormData($(this)[0]);
    let url = $(this).attr('url');
    $.ajax({
      url: BASE_PATH + '/pages/admin_' + url + '.php',
      data: formData,
      type: 'POST',
      async: false,
      dataType: 'html',
      contentType: false,
      processData: false,
      beforeSend: function (xhr) {
        $('#' + url).html('');
      },
      success: function (result) {
        $('#' + url).html(result);
      },
      complete: function (xhr, textStatus) {
      }
    });
    return false;
  });


});


//봉사자 나의 봉사 일정 추가 하루종일 토글 클릭 시
function timetoggle() {
  $('input[name="date"]').toggleClass('d-none');
  $('input[name="date2"]').toggleClass('d-none');
  $('input[name="datetime"]').toggleClass('d-none');
  $('input[name="datetime2"]').toggleClass('d-none');
}

//관리자 회중일정 추가 자동설정 토글 클릭 시
function autotoggle() {
  $('#ma_auto').toggleClass('d-none');
  $('#ma_timeswitch').toggleClass('d-none');
}

//일정 날짜 설정 시 마치는 날짜가 시작날짜보다 작은 경우
function datemin() {
  // 시작일 변경 시 종료일 자동 변경 로직 제거 (사용자 불편 방지)
}

//일정 날짜 설정 시 마치는 날짜가 시작날짜보다 작은 경우
function datemax() {
  // 종료일 변경 시 시작일 자동 변경 로직 제거 (사용자 불편 방지)
}

//봉사시간 설정 시 시작 시간이 종료 시간보다 큰 경우
function timemin() {
  let time = $('input[name="st_time"]').val();
  let time2 = $('input[name="fi_time"]').val();
  if (time > time2) $('input[name="fi_time"]').val(time);
}

//봉사시간 설정 시 종료 시간이 시작 시간보다 작은 경우
function timemax() {
  let time = $('input[name="st_time"]').val();
  let time2 = $('input[name="fi_time"]').val();
  if (time > time2) $('input[name="st_time"]').val(time2);
}

//달력 날짜 클릭 시 스케줄 출력
function schedule_reload(s_date, list) {
  let url = '/pages/' + list + '.php';
  let id = '#' + list;
  $.ajax({
    url: url,
    data: { 's_date': s_date },
    type: 'POST',
    async: false,
    dataType: 'html',
    beforeSend: function () {
      $(id).hide();
    },
    success: function (result) {
      $(id).html(result);
    },
    complete: function (xhr, textStatu) {
      $(id).show();
    }
  });
  return false;
}

// 하루 동안의 봉사모임 정보 보기
function open_meeting_view(s_date) {
  $.ajax({
    url: BASE_PATH + '/include/meeting_view.php',
    data: {
      's_date': s_date
    },
    type: 'POST',
    async: false,
    dataType: 'html',
    success: function (result) {
      $('#popup-modal .modal-body').html(result);
      $('#popup-modal').modal();
    }
  });
  return false;
}

// 봉사모임 정보 보기
function open_meeting_info(s_date, ms_id, page) {
  $.ajax({
    url: BASE_PATH + '/include/meeting_info.php',
    data: {
      's_date': s_date,
      'ms_id': ms_id,
      'page': page
    },
    type: 'POST',
    async: false,
    dataType: 'html',
    success: function (result) {
      $('#popup-modal .modal-body').html(result);
      $('#popup-modal').modal();
    }
  });
  return false;
}

// 봉사자 개인일정 폼 열기
function minister_schedule_work(work, me_id, s_date) {
  if (work == 'add') {
    $.ajax({
      url: BASE_PATH + '/include/minister_schedule_form.php',
      data: {
        'me_id': '',
        's_date': s_date
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
      url: BASE_PATH + '/include/minister_schedule_form.php',
      data: {
        'me_id': me_id,
        's_date': s_date
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#popup-modal .modal-body').html(result);
        $('#popup-modal').modal();
      }
    });
  } else if (work == 'del') {
    let ym_array = s_date.split('-');
    let toYear = ym_array[0];
    let toMonth = ym_array[1].length >= 2 && ym_array[1].substr(0, 1) == 0 ? ym_array[1].substr(1, ym_array[1].length - 1) : ym_array[1];
    $('#confirm-modal').confirm('일정을 삭제하시겠습니까?').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/pages/minister_schedule_work.php',
          data: {
            'work': 'del',
            'me_id': me_id
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (result) {
            $('#toast').toastMessage('삭제 완료');
          },
          complete: function (xhr, textStatus) {
            pageload_custom(BASE_PATH + '/pages/minister_calendar_schedule.php?s_date=' + s_date, '#minister_calendar_schedule');
            pageload_custom(BASE_PATH + '/pages/minister_calendar.php?s_date=' + s_date + '&toYear=' + toYear + '&toMonth=' + toMonth, '#minister_calendar');
          }
        });
      }
    });
  }
  return false;
}

// 봉사자
function minister_work(work, ms_id, s_date, el) {
  let ym_array = s_date.split('-');
  let toYear = ym_array[0];
  let toMonth = ym_array[1].length >= 2 && ym_array[1].substr(0, 1) == 0 ? ym_array[1].substr(1, ym_array[1].length - 1) : ym_array[1];
  if (work == 'support' || work == 'del') {

    if ($(el).text() == '참석') {
      message = '참석';
    } else if ($(el).text() == '지원') {
      message = '지원';
    } else {
      message = '취소';
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
          async: false,
          dataType: 'html',
          success: function (xhr, textStatus) {
            if (xhr == 'disabled') {
              alert(message + " 가능한 인원수를 초과하였습니다.");
            } else if (xhr == 'duplicated') {
              alert("같은 시간 모임에는 중복 " + message + "이 불가능합니다.");
            } else {
              $('#toast').toastMessage(message + ' 완료');
            }

            schedule_reload(s_date, 'minister_calendar_schedule');

            pageload_custom(BASE_PATH + '/pages/minister_calendar.php?s_date=' + s_date + '&toYear=' + toYear + '&toMonth=' + toMonth, '#minister_calendar');

            // pageload_custom(BASE_PATH+'/pages/minister_schedule.php?s_date='+s_date,'#meeting_calendar_schedule');
            // pageload_custom(BASE_PATH+'/pages/meeting_calendar.php?s_date='+s_date+'&toYear='+toYear+'&toMonth='+toMonth,'#meeting_calendar');
          }
        });
      }
    });
  }
}

//전시대 봉사
function display_work(work, ms_id, s_date, bool) {
  let ym_array = s_date.split('-');
  let toYear = ym_array[0];
  let toMonth = ym_array[1].length >= 2 && ym_array[1].substr(0, 1) == 0 ? ym_array[1].substr(1, ym_array[1].length - 1) : ym_array[1];
  if (work == 'support') {
    $('#confirm-modal').confirm('전시대에 지원하시겠습니까?').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/pages/meeting_work.php',
          data: {
            'work': 'support',
            'ms_id': ms_id,
            's_date': s_date
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (xhr, textStatus) {
            if (xhr == 'disabled') {
              alert("지원 가능한 인원수를 초과하였습니다.");
            } else if (xhr == 'duplicated') {
              alert("같은 시간 모임에는 중복 지원이 불가능합니다.");
            } else {
              $('#toast').toastMessage('지원 완료');
            }
            if (bool) {
              pageGoPost({
                url: BASE_PATH + '/pages/meeting_view.php',
                vals: [
                  ['ms_id', ms_id],
                  ['s_date', s_date]
                ]
              });
            } else {
              pageload_custom(BASE_PATH + '/pages/meeting_calendar_schedule.php?s_date=' + s_date, '#meeting_calendar_schedule');
              pageload_custom(BASE_PATH + '/pages/meeting_calendar.php?s_date=' + s_date + '&toYear=' + toYear + '&toMonth=' + toMonth, '#meeting_calendar');
            }
          }
        });
      }
    });
  } else if (work == 'cancle') {
    $('#confirm-modal').confirm('지원을 취소하시겠습니까?').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/pages/meeting_work.php',
          data: {
            'work': 'del',
            'ms_id': ms_id,
            's_date': s_date
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (xhr, textStatus) {
            if (bool) {
              pageGoPost({
                url: BASE_PATH + '/pages/meeting_view.php',
                vals: [
                  ['ms_id', ms_id],
                  ['s_date', s_date]
                ]
              });
            } else {
              pageload_custom(BASE_PATH + '/pages/meeting_calendar_schedule.php?s_date=' + s_date, '#meeting_calendar_schedule');
              pageload_custom(BASE_PATH + '/pages/meeting_calendar.php?s_date=' + s_date + '&toYear=' + toYear + '&toMonth=' + toMonth, '#meeting_calendar');
            }
          },
          complete: function (xhr, textStatu) {
            $('#toast').toastMessage('취소 완료');
          }
        });
      }
    });
  }
  return false;
}

// 공지사항
function board_work(work, b_id, auth, page) {
  if (work == 'view') {
    $.ajax({
      url: BASE_PATH + '/pages/board_work.php',
      data: {
        'b_id': b_id,
        'work': 'view'
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $.ajax({
          url: BASE_PATH + '/pages/board_view.php',
          data: {
            'b_id': b_id,
            'auth': auth,
            'page': page
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          beforeSend: function () {
            $('#board_frame').hide();
          },
          success: function (result) {
            $('#board_frame').html(result);
          },
          complete: function (xhr, textStatu) {
            $('#board_frame').show();
          }
        });
      },
      complete: function (xhr, textStatus) {
        $('.board-content').ready(function () {
          var iscontnet = $('.board-content').length > 0;
          if (iscontnet) backDisable(iscontnet);
        });
      }
    });
  } else if (work == 'all_view') {
    $('#confirm-modal').confirm('해당 게시판의 모든 공지를 읽음 처리하시겠습니까?').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/pages/board_work.php',
          data: {
            'work': 'all_view',
            'auth': auth,
            'page': page
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (result) {
            $('#toast').toastMessage('읽음 처리 완료');
          },
          complete: function (xhr, textStatus) {
            $.ajax({
              url: BASE_PATH + '/pages/board_list.php',
              data: {
                'auth': auth,
                'page': page
              },
              type: 'POST',
              async: false,
              dataType: 'html',
              beforeSend: function () {
                $('#board_frame').hide();
              },
              success: function (result) {
                $('#board_frame').html(result);
              },
              complete: function (xhr, textStatu) {
                $('#board_frame').show();
              }
            });
          }
        });
      }
    });
  } else if (work == 'write') {
    $.ajax({
      url: BASE_PATH + '/pages/board_form.php',
      data: {
        'b_id': b_id,
        'auth': auth
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      beforeSend: function () {
        $('#board_frame').hide();
      },
      success: function (result) {
        $('#board_frame').html(result);
      },
      complete: function (xhr, textStatu) {
        $('#board_frame').show();
        $('form').ready(function () {
          var iscontnet = $('form').length > 0;
          if (iscontnet) backDisable(iscontnet);
        });
      }
    });
  } else if (work == 'del') {
    $('#confirm-modal').confirm('공지를 삭제하시겠습니까?').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/pages/board_work.php',
          data: {
            'work': 'del',
            'b_id': b_id
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (result) {
            $('#toast').toastMessage('삭제 완료');
          },
          complete: function (xhr, textStatus) {
            $.ajax({
              url: BASE_PATH + '/pages/board_list.php',
              data: {
                'auth': auth,
                'page': page
              },
              type: 'POST',
              async: false,
              dataType: 'html',
              beforeSend: function () {
                $('#board_frame').hide();
              },
              success: function (result) {
                $('#board_frame').html(result);
              },
              complete: function (xhr, textStatu) {
                $('#board_frame').show();
              }
            });
          }
        });
      }
    });
  }
  return false;
}

// 공지사항 뒤로가기 방지
function backDisable(iscontnet) {
  if (iscontnet) {
    if (window.history && window.history.pushState) {
      window.history.pushState(null, null, '');
      window.onpopstate = function () {
        $('#container a.navbar-brand').get(0).click();
      }
    }
  }
}

//인도자 봉사모임관리
function guide_meeting_work(work, ms_id, s_date, page) {
  let guide;
  let type;
  let confirm;
  let cancle;
  let auth = 1;
  let reason;

  if (work == 'appoint') {
    guide = $('select[name="guide"]').val();
    if (guide == '') {
      confirm = '당일 인도자를 삭제하시겠습니까?';
    } else {
      confirm = s_date + ' 모임 인도자로 지정하시겠습니까?';
    }
    cancle = '0';
    type = '지정';
  } else {
    if (work == '0') {
      type = '복원';
    } else {
      work = $('select[name="cancle_type"]').val();
      reason = $('input[name="cancle_reason"]').val();
      type = '취소';
    }
    cancle = work;
    confirm = '해당 모임을 ' + type + '하시겠습니까?';
  }

  $('#confirm-modal').confirm(confirm).on({
    confirm: function () {
      $.ajax({
        url: BASE_PATH + '/pages/guide_meeting_work.php',
        data: {
          'guide': guide,
          'work': work,
          'ms_id': ms_id,
          's_date': s_date,
          'reason': reason
        },
        type: 'POST',
        async: false,
        dataType: 'html',
        success: function (xhr, textStatus) {

          if (page == 'home') {
            // 로컬 날짜 기준으로 홈 봉사 목록 갱신
            const now = new Date();
            const today = [
              now.getFullYear(),
              String(now.getMonth() + 1).padStart(2, '0'),
              String(now.getDate()).padStart(2, '0')
            ].join('-');
            pageload_custom(BASE_PATH + '/pages/today_service_list.php?s_date=' + today, '#today-service-list');
            pageload_custom(BASE_PATH + '/pages/minister_calendar.php?s_date=' + s_date + '&toYear=' + toYear + '&toMonth=' + toMonth, '#minister_calendar');
          } else if (page == 'minister') {
            pageload_custom(BASE_PATH + '/pages/minister_calendar_schedule.php?s_date=' + s_date, '#minister_calendar_schedule');
          } else if (page == 'display') {
            // 로컬 날짜 기준으로 전시대 달력/목록 갱신
            const now = new Date();
            const today = [
              now.getFullYear(),
              String(now.getMonth() + 1).padStart(2, '0'),
              String(now.getDate()).padStart(2, '0')
            ].join('-');
            pageload_custom(BASE_PATH + '/pages/meeting_calendar.php?s_date=' + today + '&toYear=' + today.split('-')[0] + '&toMonth=' + String(parseInt(today.split('-')[1], 10)), '#meeting_calendar');
            pageload_custom(BASE_PATH + '/pages/meeting_calendar_schedule.php?s_date=' + today, '#meeting_calendar_schedule');
          } else if (page == 'guide') {
            pageload_custom(BASE_PATH + '/pages/guide_history_list.php?s_date=' + s_date, '#guide_history_list');
          } else if (page == 'guide_assign') {
            pageGoPost({ url: BASE_PATH + '/pages/guide_assign_step.php', vals: [['s_date', s_date], ['ms_id', ms_id]] });
          }

        },
        complete: function (xhr, textStatus) {
          $('#toast').toastMessage(type + ' 완료');
          $('#popup-modal').modal('hide');
        }
      });
    }
  });
  return false;
}

// 관리자 봉사모임관리 봉사모임계획 수정
function meeting_work(work, ms_id, ma_id) {
  if (work == 'add') {
    $.ajax({
      url: BASE_PATH + '/include/meeting_form.php',
      data: {
        'ms_id': '',
        'ma_id': ma_id
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#meeting-modal .modal-body').html(result);
        $('#meeting-modal').modal();
      }
    });
  } else if (work == 'edit') {
    $.ajax({
      url: BASE_PATH + '/include/meeting_form.php',
      data: {
        'ms_id': ms_id,
        'ma_id': ma_id
      },
      type: 'POST',
      async: false,
      dataType: 'html',
      success: function (result) {
        $('#meeting-modal .modal-body').html(result);
        $('#meeting-modal').modal();
      }
    });
  } else if (work == 'del') {
    $('#confirm-modal').confirm('이 모임 계획을 삭제하면 관련된 이후의 모든 모임 정보가 삭제 됩니다. <br> 모임 계획을 삭제하시겠습니까?').on({
      confirm: function () {
        $.ajax({
          url: BASE_PATH + '/pages/admin_meeting_work.php',
          data: {
            'work': 'del',
            'del_id': ms_id
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function (result) {
            pageload_custom(BASE_PATH + '/pages/admin_meeting_list.php?ma_id=' + ma_id, '#admin_meeting_list');
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

//post 페이지 이동
function pageGoPost(arr) {
  let f = document.createElement('form');

  for (var i = 0; i < arr.vals.length; i++) {
    let obj;
    obj = document.createElement('input');
    obj.setAttribute('type', 'hidden');
    obj.setAttribute('name', arr.vals[i][0]);
    obj.setAttribute('value', arr.vals[i][1]);
    f.appendChild(obj);
  }

  f.setAttribute('method', 'post');
  f.setAttribute('action', arr.url);
  document.body.appendChild(f);

  f.submit();
}
