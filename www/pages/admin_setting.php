<?php
include_once('../header.php');

check_accessible('super');

$c_territory_type = unserialize(TERRITORY_TYPE);
$c_territory_type_use = unserialize(TERRITORY_TYPE_USE);
$c_meeting_schedule_type = unserialize(MEETING_SCHEDULE_TYPE);
$c_meeting_schedule_type_use = unserialize(MEETING_SCHEDULE_TYPE_USE);
$c_house_condition = unserialize(HOUSE_CONDITION);
$c_house_condition_use = unserialize(HOUSE_CONDITION_USE);
$c_meeting_schedule_type_attend_limit = unserialize(MEETING_SCHEDULE_TYPE_ATTEND_LIMIT);
?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">관리자 <span class="d-xl-none">설정</span></h1>
  <?php echo header_menu('admin','설정'); ?>
</header>

<?php echo footer_menu('관리자');?>

<div id="container" class="container-fluid">

  <ul class="nav nav-tabs mb-4" id="v-pills-tab" role="tablist">
    <li class="nav-item" role="presentation">
      <a class="nav-link active" id="v-pills-general-tab" data-toggle="tab"  href="#v-pills-general" role="tab" aria-controls="v-pills-general" aria-selected="true">일반</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link" id="v-pills-home-tab" data-toggle="tab"  href="#v-pills-home" role="tab" aria-controls="v-pills-home" aria-selected="true">홈</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link" id="v-pills-minister-tab" data-toggle="tab" href="#v-pills-minister" role="tab" aria-controls="v-pills-minister" aria-selected="false">봉사자</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link" id="v-pills-meeting-tab" data-toggle="tab" href="#v-pills-meeting" role="tab" aria-controls="v-pills-meeting" aria-selected="false">전시대</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link" id="v-pills-board-tab" data-toggle="tab" href="#v-pills-board" role="tab" aria-controls="v-pills-board" aria-selected="false">공지사항</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link" id="v-pills-guide-tab" data-toggle="tab"  href="#v-pills-guide" role="tab" aria-controls="v-pills-guide" aria-selected="false">인도자</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link" id="v-pills-territory-tab" data-toggle="tab"  href="#v-pills-territory" role="tab" aria-controls="v-pills-territory" aria-selected="false">구역</a>
    </li>
  </ul>

  <form id="site_option">
    <input type="hidden" name="work" value="site_option_update">
    <input type="hidden" name="territory_boundary" id="map_sub_polygon" value="<?=htmlspecialchars(TERRITORY_BOUNDARY)?>">

    <div class="tab-content" id="v-pills-tabContent">
      <!-- 일반 -->
      <div class="tab-pane fade show active" id="v-pills-general" role="tabpanel" aria-labelledby="v-pills-general-tab">

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">회중이름</label>
          <div class="col-8 col-md-10">
            <input class="form-control" type="text" name="site_name" value="<?=SITE_NAME?>">
            <small class="text-muted">
              로그인화면에 노출되는 회중이름입니다.
            </small>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">기본주소</label>
          <div class="col-8 col-md-10">
            <input class="form-control" type="text" name="default_address" value="<?=DEFAULT_ADDRESS?>">
            <small class="text-muted">
              다른 지역과 도로명 주소가 중복되는 것을 막기 위해 큰 범위의 기본 주소를 설정합니다.<br>예) 경기도 화성시
            </small>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">부재자 방문</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
                <input type="radio" name="absence_use" value="use" <?=ABSENCE_USE == 'use'?'checked="checked"':'';?>>
                사용
              </label>
              <label>
                <input type="radio" name="absence_use" value="" <?=empty(ABSENCE_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              완료된 구역에 대해서 부재자 방문을 할 수 있습니다.
            </small>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">구역 완료 기준</label>
          <div class="col-8 col-md-10">
            <div>
              <input type="number" class="form-control d-inline-block p-1 text-right" min="1" max="100" name="territory_complete_percent" value="<?=TERRITORY_COMPLETE_PERCENT?TERRITORY_COMPLETE_PERCENT:'90'?>" style="width: 60px;">%
            </div>
            <small class="text-muted">
              만남/부재를 포함하여 <?=TERRITORY_COMPLETE_PERCENT?TERRITORY_COMPLETE_PERCENT:'90'?>% 이상 세대 봉사를 완료한 구역은 완료로 자동 기록됩니다.
            </small>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">모임 참여자 노출</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
                <input type="radio" name="show_attend_use" value="use" <?=(SHOW_ATTEND_USE == 'use' || SHOW_ATTEND_USE == '')?'checked="checked"':'';?>>
                사용
              </label>
              <label>
                <input type="radio" name="show_attend_use" value="disuse" <?=SHOW_ATTEND_USE == 'disuse'?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              모든 사람이 모임의 참여자를 볼 수 있습니다.
            </small>
          </div>
        </div>

        <h5 class="border-bottom mt-4 mb-3 pb-2">모임 형태</h5>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" readonly placeholder="호별">
              </label>
            </div>
            <div class="form-group row">
              <label class="col-4 col-md-2 col-form-label">지원자 수 제한</label>
              <div class="col-8 col-md-10">
                <div>
                  <input type="number" class="form-control d-inline-block p-1 text-right" name="meeting_schedule_type_attend_limit[1]" value="<?=$c_meeting_schedule_type_attend_limit['1']?>" min="1" style="width: 50px;"> 명
                </div>
                <small class="text-muted">
                  이 모임 형태 전체에 적용할 지원자 수 제한입니다.<br>
                  '모임 계획 관리'에서 모임 계획별로 별도 설정도 가능합니다.<br>
                  * 제한 없음은 빈칸으로 두세요.
                </small>
              </div>
            </div>
          </div>
        </div>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" readonly placeholder="전시대">
              </label>
            </div>
            <div class="form-group row">
              <label class="col-4 col-md-2 col-form-label">지원자 수 제한</label>
              <div class="col-8 col-md-10">
                <div>
                  <input type="number" class="form-control d-inline-block p-1 text-right" name="meeting_schedule_type_attend_limit[2]" value="<?=$c_meeting_schedule_type_attend_limit['2']?>" min="1" style="width: 50px;"> 명
                </div>
                <small class="text-muted">
                  이 모임 형태 전체에 적용할 지원자 수 제한입니다.<br>
                  '모임 계획 관리'에서 모임 계획별로 별도 설정도 가능합니다.<br>
                  * 제한 없음은 빈칸으로 두세요.
                </small>
              </div>
            </div>
          </div>
        </div>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label"><a href=""></a>
                <input class="form-control" type="text" placeholder="추가1" name="meeting_schedule_type[3]" value="<?=!empty($c_meeting_schedule_type['3'])?$c_meeting_schedule_type['3']:''?>">
              </label>
              <div class="col-6 col-md-2">
                <div style="line-height:54px;">
                  <label class="mb-0">
                    <input type="radio" name="meeting_schedule_type_use[3]" value="use" <?=(!isset($c_meeting_schedule_type_use['3']) || $c_meeting_schedule_type_use['3'] === 'use')?'checked="checked"':'';?>>
                    사용
                  </label>
                  <label class="mb-0">
                    <input type="radio" name="meeting_schedule_type_use[3]" value="" <?=(isset($c_meeting_schedule_type_use['3']) && $c_meeting_schedule_type_use['3'] === '')?'checked="checked"':'';?>>
                    미사용
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-4 col-md-2 col-form-label">지원자 수 제한</label>
              <div class="col-8 col-md-10">
                <div>
                  <input type="number" class="form-control d-inline-block p-1 text-right" name="meeting_schedule_type_attend_limit[3]" value="<?=$c_meeting_schedule_type_attend_limit['3']?>" min="1" style="width: 50px;"> 명
                </div>
                <small class="text-muted">
                  이 모임 형태 전체에 적용할 지원자 수 제한입니다.<br>
                  '모임 계획 관리'에서 모임 계획별로 별도 설정도 가능합니다.<br>
                  * 제한 없음은 빈칸으로 두세요.
                </small>
              </div>
            </div>
          </div>
        </div>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" placeholder="추가2" name="meeting_schedule_type[4]" value="<?=!empty($c_meeting_schedule_type['4'])?$c_meeting_schedule_type['4']:''?>">
              </label>
              <div class="col-6 col-md-2">
                <div style="line-height:54px;">
                  <label class="mb-0">
                    <input type="radio" name="meeting_schedule_type_use[4]" value="use" <?=(!isset($c_meeting_schedule_type_use['4']) || $c_meeting_schedule_type_use['4'] === 'use')?'checked="checked"':'';?>>
                    사용
                  </label>
                  <label class="mb-0">
                    <input type="radio" name="meeting_schedule_type_use[4]" value="" <?=(isset($c_meeting_schedule_type_use['4']) && $c_meeting_schedule_type_use['4'] === '')?'checked="checked"':'';?>>
                    미사용
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-4 col-md-2 col-form-label">지원자 수 제한</label>
              <div class="col-8 col-md-10">
                <div>
                  <input type="number" class="form-control d-inline-block p-1 text-right" name="meeting_schedule_type_attend_limit[4]" value="<?=$c_meeting_schedule_type_attend_limit['4']?>" min="1" style="width: 50px;"> 명
                </div>
                <small class="text-muted">
                  이 모임 형태 전체에 적용할 지원자 수 제한입니다.<br>
                  '모임 계획 관리'에서 모임 계획별로 별도 설정도 가능합니다.<br>
                  * 제한 없음은 빈칸으로 두세요.
                </small>
              </div>
            </div>
          </div>
        </div>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" placeholder="추가3" name="meeting_schedule_type[5]" value="<?=!empty($c_meeting_schedule_type['5'])?$c_meeting_schedule_type['5']:''?>">
              </label>
              <div class="col-6 col-md-2">
                <div style="line-height:54px;">
                  <label class="mb-0">
                    <input type="radio" name="meeting_schedule_type_use[5]" value="use" <?=(!isset($c_meeting_schedule_type_use['5']) || $c_meeting_schedule_type_use['5'] === 'use')?'checked="checked"':'';?>>
                    사용
                  </label>
                  <label class="mb-0">
                    <input type="radio" name="meeting_schedule_type_use[5]" value="" <?=(isset($c_meeting_schedule_type_use['5']) && $c_meeting_schedule_type_use['5'] === '')?'checked="checked"':'';?>>
                    미사용
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-4 col-md-2 col-form-label">지원자 수 제한</label>
              <div class="col-8 col-md-10">
                <div>
                  <input type="number" class="form-control d-inline-block p-1 text-right" name="meeting_schedule_type_attend_limit[5]" value="<?=$c_meeting_schedule_type_attend_limit['5']?>" min="1" style="width: 50px;"> 명
                </div>
                <small class="text-muted">
                  이 모임 형태 전체에 적용할 지원자 수 제한입니다.<br>
                  '모임 계획 관리'에서 모임 계획별로 별도 설정도 가능합니다.<br>
                  * 제한 없음은 빈칸으로 두세요.
                </small>
              </div>
            </div>
          </div>
        </div>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" placeholder="추가4" name="meeting_schedule_type[6]" value="<?=!empty($c_meeting_schedule_type['6'])?$c_meeting_schedule_type['6']:''?>">
              </label>
              <div class="col-6 col-md-2">
                <div style="line-height:54px;">
                  <label class="mb-0">
                    <input type="radio" name="meeting_schedule_type_use[6]" value="use" <?=(!isset($c_meeting_schedule_type_use['6']) || $c_meeting_schedule_type_use['6'] === 'use')?'checked="checked"':'';?>>
                    사용
                  </label>
                  <label class="mb-0">
                    <input type="radio" name="meeting_schedule_type_use[6]" value="" <?=(isset($c_meeting_schedule_type_use['6']) && $c_meeting_schedule_type_use['6'] === '')?'checked="checked"':'';?>>
                    미사용
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-4 col-md-2 col-form-label">지원자 수 제한</label>
              <div class="col-8 col-md-10">
                <div>
                  <input type="number" class="form-control d-inline-block p-1 text-right" name="meeting_schedule_type_attend_limit[6]" value="<?=$c_meeting_schedule_type_attend_limit['6']?>" min="1" style="width: 50px;"> 명
                </div>
                <small class="text-muted">
                  이 모임 형태 전체에 적용할 지원자 수 제한입니다.<br>
                  '모임 계획 관리'에서 모임 계획별로 별도 설정도 가능합니다.<br>
                  * 제한 없음은 빈칸으로 두세요.
                </small>
              </div>
            </div>
          </div>
        </div>

        <h5 class="border-bottom mt-4 mb-3 pb-2">참석/지원 버튼</h5>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">호별 및 추가 참석/지원 가능 시간</label>
          <div class="col-8 col-md-10">
            <div>
              모임 시간</br>
              <div class="mt-1"><input type="number" class="form-control d-inline-block p-1 text-right" name="attend_before" value="<?=ATTEND_BEFORE?>" style="width: 100px;" placeholder="45"> 분 전부터</div>
              <div class="mt-1"><input type="number" class="form-control d-inline-block p-1 text-right" name="attend_after" value="<?=ATTEND_AFTER?>" style="width: 100px;" placeholder="15"> 분 후까지</div>
            </div>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">전시대 참석/지원 가능 시간</label>
          <div class="col-8 col-md-10">
            <div>
              모임 시간</br>
              <div class="mt-1"><input type="number" class="form-control d-inline-block p-1 text-right" name="attend_display_before" value="<?=ATTEND_DISPLAY_BEFORE?>" style="width: 100px;" placeholder="45"> 분 전부터</div>
              <div class="mt-1"><input type="number" class="form-control d-inline-block p-1 text-right" name="attend_display_after" value="<?=ATTEND_DISPLAY_AFTER?>" style="width: 100px;" placeholder="15"> 분 후까지</div>
            </div>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">같은 시간 모임 중복 참석/지원 제한</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
              <input type="radio" name="duplicate_attend_limit" value="use" <?=DUPLICATE_ATTEND_LIMIT == 'use'?'checked="checked"':'';?>>
              사용
              </label>
              <label>
                <input type="radio" name="duplicate_attend_limit" value="" <?=empty(DUPLICATE_ATTEND_LIMIT)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              같은 시간 모임에 중복 참석/지원을 제한합니다.
            </small>
          </div>
        </div>

        <h5 class="border-bottom mt-4 mb-3 pb-2">지도</h5>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">지도 API KEY</label>
          <div class="col-8 col-md-10">
            <input class="form-control" type="text" name="map_api_key" value="<?=MAP_API_KEY?>">
            <small class="text-muted">
              발급받은 JavaScript 키를 입력합니다.
            </small>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">기본 좌표</label>
          <div class="col-8 col-md-10">
            <input class="form-control" type="text" name="default_location" value="<?=DEFAULT_LOCATION?>">
            <small class="text-muted">
              지도의 초기좌표가 되는 주소를 설정합니다.<br>설정시 지도세팅시 유용합니다.<br>예) 경기 화성시 봉담읍 왕림리 79-43
            </small>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-12 col-md-12 col-form-label">회중 구역 경계</label>
          <div class="col-12 col-md-12">
            <div class="pt-2 pr-3 pl-3">
              <div id="map" style="height:600px;display:none;"></div>
            </div>
            <small class="text-muted">
              그려진 회중 구역 경계는 관리자 > 일반 구역 관리 > 구역 전체 지도 에 표시됩니다.
            </small>
          </div>
          <div class="col-12 col-md-12 pt-4">
            <button type="button" class="btn btn-outline-danger" name="territory_map_reset">구역경계 리셋</button>
            <div>
              <small class="text-muted">
                회중 구역 경계 지도가 노출되지 않을 때, 이 버튼을 눌러 초기화 해주세요.
              </small>
            </div>
          </div>
        </div>

      </div>

      <!-- 홈 -->
      <div class="tab-pane fade" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">

        <h5 class="border-bottom mt-4 mb-3 pb-2">모임 참석/지원</h5>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">호별 및 추가</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
              <input type="radio" name="attend_use" value="use" <?=ATTEND_USE == 'use'?'checked="checked"':'';?>>
              사용
              </label>
              <label>
                <input type="radio" name="attend_use" value="" <?=empty(ATTEND_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              홈 에서 호별 및 추가 모임 형태의 참석/지원 기능을 사용합니다.
            </small>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">전시대</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
              <input type="radio" name="attend_display_use" value="use" <?=ATTEND_DISPLAY_USE == 'use'?'checked="checked"':'';?>>
              사용
              </label>
              <label>
                <input type="radio" name="attend_display_use" value="" <?=empty(ATTEND_DISPLAY_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              홈 에서 전시대 모임 형태의 참석/지원 기능을 사용합니다.
            </small>
          </div>
        </div>

      </div>

      <!-- 봉사자 -->
      <div class="tab-pane fade" id="v-pills-minister" role="tabpanel" aria-labelledby="v-pills-minister-tab">

      <h5 class="border-bottom mt-4 mb-3 pb-2">일정</h5>

      <div class="form-group row">
        <label class="col-4 col-md-2 col-form-label">사용</label>
        <div class="col-8 col-md-10">
          <div>
            <label>
              <input type="radio" name="minister_schedule_event_use" value="use" <?=MINISTER_SCHEDULE_EVENT_USE == 'use'?'checked="checked"':'';?>>
              사용
            </label>
            <label>
              <input type="radio" name="minister_schedule_event_use" value="" <?=empty(MINISTER_SCHEDULE_EVENT_USE)?'checked="checked"':'';?>>
              미사용
            </label>
          </div>
          <small class="text-muted">
            나의 봉사 에서 일정 기능을 사용합니다.
          </small>
        </div>
      </div>
        <h5 class="border-bottom mt-4 mb-3 pb-2">모임 참석/지원</h5>
        
        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">호별 및 추가</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
                <input type="radio" name="minister_attend_use" value="use" <?=MINISTER_ATTEND_USE == 'use'?'checked="checked"':'';?>>
                사용
              </label>
              <label>
                <input type="radio" name="minister_attend_use" value="" <?=empty(MINISTER_ATTEND_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              나의 봉사 에서 호별 및 추가 모임 형태의 참석/지원 기능을 사용합니다.
            </small>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">전시대</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
                <input type="radio" name="minister_display_attend_use" value="use" <?=MINISTER_DISPLAY_ATTEND_USE == 'use'?'checked="checked"':'';?>>
                사용
              </label>
              <label>
                <input type="radio" name="minister_display_attend_use" value="" <?=empty(MINISTER_DISPLAY_ATTEND_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              나의 봉사 에서 전시대 모임 형태의 참석/지원 기능을 사용합니다.
            </small>
          </div>
        </div>

        <h5 class="border-bottom mt-4 mb-3 pb-2">기록</h5>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">사용</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
                <input type="radio" name="minister_schedule_report_use" value="use" <?=MINISTER_SCHEDULE_REPORT_USE == 'use'?'checked="checked"':'';?>>
                사용
              </label>
              <label>
                <input type="radio" name="minister_schedule_report_use" value="" <?=empty(MINISTER_SCHEDULE_REPORT_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              나의 봉사 에서 기록 저장/합계 기능을 사용합니다.
            </small>
          </div>
        </div>

        <h5 class="border-bottom mt-4 mb-3 pb-2">재방문</h5>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">재방문</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
                <input type="radio" name="returnvisit_use" value="use" <?=RETURNVISIT_USE == 'use'?'checked="checked"':'';?>>
                사용
              </label>
              <label>
                <input type="radio" name="returnvisit_use" value="" <?=empty(RETURNVISIT_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              전도인들이 재방문 관리를 할 수 있습니다.
            </small>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">재방문 자동중단 기간</label>
          <div class="col-8 col-md-10">
            <div>
              <input type="number" class="form-control d-inline-block p-1 text-right" min="0" name="return_visit_expiration" value="<?=RETURN_VISIT_EXPIRATION?RETURN_VISIT_EXPIRATION:'3'?>" style="width: 50px;"> 개월
            </div>
            <small class="text-muted">
              최종 방문 일자로부터 <?=RETURN_VISIT_EXPIRATION?RETURN_VISIT_EXPIRATION:'3'?>개월이 지난 경우 재방문은 자동으로 중단됩니다.<br>
              개월 수가 0인 경우 재방문은 중단되지 않습니다.
            </small>
          </div>
        </div>

        <h5 class="border-bottom mt-4 mb-3 pb-2">배정 기간</h5>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">일반 구역 배정 기간</label>
          <div class="col-8 col-md-10">
            <div>
              <input type="number" class="form-control d-inline-block p-1 text-right" min="1" name="minister_assign_expiration" value="<?=MINISTER_ASSIGN_EXPIRATION?MINISTER_ASSIGN_EXPIRATION:'7'?>" style="width: 50px;"> 일
            </div>
            <small class="text-muted">
              전도인들은 <?=MINISTER_ASSIGN_EXPIRATION?MINISTER_ASSIGN_EXPIRATION:'7'?>일 동안 배정받은 구역을 봉사할 수 있습니다.
            </small>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">전화 구역 배정 기간</label>
          <div class="col-8 col-md-10">
            <div>
              <input type="number" class="form-control d-inline-block p-1 text-right" min="1" name="minister_telephone_assign_expiration" value="<?=MINISTER_TELEPHONE_ASSIGN_EXPIRATION?MINISTER_TELEPHONE_ASSIGN_EXPIRATION:'7'?>" style="width: 50px;"> 일
            </div>
            <small class="text-muted">
              전도인들은 <?=MINISTER_TELEPHONE_ASSIGN_EXPIRATION?MINISTER_TELEPHONE_ASSIGN_EXPIRATION:'7'?>일 동안 배정받은 구역을 봉사할 수 있습니다.
            </small>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">편지 구역 배정 기간</label>
          <div class="col-8 col-md-10">
            <div>
              <input type="number" class="form-control d-inline-block p-1 text-right" min="1" name="minister_letter_assign_expiration" value="<?=MINISTER_LETTER_ASSIGN_EXPIRATION?MINISTER_LETTER_ASSIGN_EXPIRATION:'7'?>" style="width: 50px;"> 일
            </div>
            <small class="text-muted">
              전도인들은 <?=MINISTER_LETTER_ASSIGN_EXPIRATION?MINISTER_LETTER_ASSIGN_EXPIRATION:'7'?>일 동안 배정받은 구역을 봉사할 수 있습니다.
            </small>
          </div>
        </div>

        <h5 class="border-bottom mt-4 mb-3 pb-2">나의 통계</h5>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">사용</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
                <input type="radio" name="minister_statistics_use" value="use" <?=MINISTER_STATISTICS_USE == 'use'?'checked="checked"':'';?>>
                사용
              </label>
              <label>
                <input type="radio" name="minister_statistics_use" value="" <?=empty(MINISTER_STATISTICS_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              나의 통계 기능을 사용합니다.
            </small>
          </div>
        </div>

      </div>

      <!-- 전시대 -->
      <div class="tab-pane fade" id="v-pills-meeting" role="tabpanel" aria-labelledby="v-pills-meeting-tab">

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">사용</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
                <input type="radio" name="display_use" value="use" <?=DISPLAY_USE == 'use'?'checked="checked"':'';?>>
                사용
              </label>
              <label>
                <input type="radio" name="display_use" value="" <?=empty(DISPLAY_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              전시대 모임 지원 기능을 사용합니다.
            </small>
          </div>
        </div>

      </div>

      <!-- 공지사항 -->
      <div class="tab-pane fade" id="v-pills-board" role="tabpanel" aria-labelledby="v-pills-board-tab">

        <div class="form-group row">
          <label for="board_item_per_page" class="col-4 col-md-2 col-form-label">페이지 당 글 개수</label>
          <div class="col-8 col-md-10">
            <input type="number" class="form-control"  name="board_item_per_page" value="<?=BOARD_ITEM_PER_PAGE?BOARD_ITEM_PER_PAGE:'20'?>">
          </div>
        </div>

      </div>

      <!-- 인도자 -->
      <div class="tab-pane fade" id="v-pills-guide" role="tabpanel" aria-labelledby="v-pills-guide-tab">

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">당일 인도자 지정 기능</label>
          <div class="col-8 col-md-10">
            <div style="line-height:40px;">
              <label>
                <input type="radio" name="guide_appoint_use" value="use" <?=GUIDE_APPOINT_USE == 'use'?'checked="checked"':'';?>>
                사용
              </label>
              <label>
                <input type="radio" name="guide_appoint_use" value="" <?=empty(GUIDE_APPOINT_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              각 모임별로 당일 인도자를 지정할 수 있습니다.<br/>
              지정된 인도자는 모임 정보에 버튼에 노출됩니다.
            </small>
          </div>
        </div>

        <h5 class="border-bottom mt-4 mb-3 pb-2">인도</h5>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">구역 정렬</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
                <input type="radio" name="guide_card_order" value="0" <?=empty(GUIDE_CARD_ORDER)?'checked="checked"':'';?>>
                추천 순
              </label>
              <label>
                <input type="radio" name="guide_card_order" value="1" <?=GUIDE_CARD_ORDER == '1'?'checked="checked"':'';?>>
                구역 번호 순
              </label>
            </div>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">짝 기능</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
                <input type="radio" name="guide_assigned_group_use" value="use" <?=GUIDE_ASSIGNED_GROUP_USE == 'use'?'checked="checked"':'';?>>
                사용
              </label>
              <label>
                <input type="radio" name="guide_assigned_group_use" value="" <?=empty(GUIDE_ASSIGNED_GROUP_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">모임 내용</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
                <input type="radio" name="guide_meeting_contents_use" value="use" <?=GUIDE_MEETING_CONTENTS_USE == 'use'?'checked="checked"':'';?>>
                사용
              </label>
              <label>
                <input type="radio" name="guide_meeting_contents_use" value="" <?=empty(GUIDE_MEETING_CONTENTS_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">모임 내용 기본 양식</label>
          <div class="col-8 col-md-10">
            <textarea name="guide_meeting_contents" id="" rows="10" class="form-control"><?=GUIDE_MEETING_CONTENTS?></textarea>
          </div>
        </div>

        <h5 class="border-bottom mt-4 mb-3 pb-2">통계</h5>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">통계</label>
          <div class="col-8 col-md-10">
            <div>
              <label>
                <input type="radio" name="guide_statistics_use" value="use" <?=GUIDE_STATISTICS_USE == 'use'?'checked="checked"':'';?>>
                사용
              </label>
              <label>
                <input type="radio" name="guide_statistics_use" value="" <?=empty(GUIDE_STATISTICS_USE)?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
            <small class="text-muted">
              통계 기능을 사용합니다.
            </small>
          </div>
        </div>

      </div>

      <!-- 구역 -->
      <div class="tab-pane fade" id="v-pills-territory" role="tabpanel" aria-labelledby="v-pills-territory-tab">

        <h5 class="border-bottom mt-4 mb-3 pb-2">구역</h5>

        <div class="form-group row">
          <label for="territory_item_per_page" class="col-4 col-md-2 col-form-label">페이지 당 구역 개수</label>
          <div class="col-8 col-md-10">
            <input type="number" class="form-control" name="territory_item_per_page" value="<?=TERRITORY_ITEM_PER_PAGE?TERRITORY_ITEM_PER_PAGE:'50'?>" min="1" max="5000">
          </div>
        </div>

        <div class="form-group row">
          <label class="col-4 col-md-2 col-form-label">구역 정렬</label>
          <div class="col-8 col-md-10">
            <div style="line-height:40px;">
              <label>
                <input type="radio" name="admin_territory_sort" value="0" <?=empty(ADMIN_TERRITORY_SORT)?'checked="checked"':'';?>>
                문자로 정렬
              </label>
              <label>
                <input type="radio" name="admin_territory_sort" value="1" <?=ADMIN_TERRITORY_SORT == '1'?'checked="checked"':'';?>>
                숫자로 정렬
              </label>
            </div>
          </div>
        </div>

        <h5 class="border-bottom mt-4 mb-3 pb-2">구역 형태</h5>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" name="territory_type[type_1][0]" placeholder="일반" value="<?=!empty($c_territory_type['type_1'][0])?$c_territory_type['type_1'][0]:''?>">
              </label>
              <div class="col-6 col-md-2">
                <div style="line-height:54px;">
                  <label class="mb-0">
                    <input type="radio" name="territory_type_use[type_1]" value="use" <?=(!isset($c_territory_type_use['type_1']) || $c_territory_type_use['type_1'] === 'use')?'checked="checked"':'';?>>
                    사용
                  </label>
                  <label class="mb-0">
                    <input type="radio" name="territory_type_use[type_1]" value="" <?=(isset($c_territory_type_use['type_1']) && $c_territory_type_use['type_1'] === '')?'checked="checked"':'';?>>
                    미사용
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group row mb-0">
              <div class="col-12 col-md-12">
                <div class="table-responsive">
                  <table class="table mb-0">
                    <tbody>
                      <tr>
                        <td class="border-top-0">
                          <input class="form-control" type="text"  placeholder="길이름" style="min-width:120px"  readonly>
                        </td>
                        <td class="border-top-0">
                          <input class="form-control" type="text"  placeholder="건물번호" style="min-width:120px"  readonly>
                        </td>
                        <td class="border-top-0">
                          <input class="form-control" type="text"  name="territory_type[type_1][3]" placeholder="상세주소" style="min-width:120px" value="<?=!empty($c_territory_type['type_1'][3])?$c_territory_type['type_1'][3]:''?>">
                        </td>
                        <td class="border-top-0">
                          <input class="form-control" type="text" name="territory_type[type_1][4]" placeholder="층" style="min-width:120px" value="<?=!empty($c_territory_type['type_1'][4])?$c_territory_type['type_1'][4]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text" name="territory_type[type_1][5]" placeholder="이름/호" style="min-width:120px" value="<?=!empty($c_territory_type['type_1'][5])?$c_territory_type['type_1'][5]:''?>">
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" name="territory_type[type_2][0]" placeholder="아파트" value="<?=!empty($c_territory_type['type_2'][0])?$c_territory_type['type_2'][0]:''?>">
              </label>
              <div class="col-6 col-md-2">
                <div style="line-height:54px;">
                  <label class="mb-0">
                    <input type="radio" name="territory_type_use[type_2]" value="use" <?=(!isset($c_territory_type_use['type_2']) || $c_territory_type_use['type_2'] === 'use')?'checked="checked"':'';?>>
                    사용
                  </label>
                  <label class="mb-0">
                    <input type="radio" name="territory_type_use[type_2]" value="" <?=(isset($c_territory_type_use['type_2']) && $c_territory_type_use['type_2'] === '')?'checked="checked"':'';?>>
                    미사용
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group row mb-0">
              <div class="col-12 col-md-12">
                <div class="table-responsive">
                  <table class="table mb-0">
                    <tbody>
                      <tr>
                        <td class="border-top-0">
                          <input class="form-control" type="text" name="territory_type[type_2][1]" placeholder="아파트명" style="min-width:120px" value="<?=!empty($c_territory_type['type_2'][1])?$c_territory_type['type_2'][1]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text"  name="territory_type[type_2][2]" placeholder="동" style="min-width:120px" value="<?=!empty($c_territory_type['type_2'][2])?$c_territory_type['type_2'][2]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text"  name="territory_type[type_2][3]" placeholder="호" style="min-width:120px" value="<?=!empty($c_territory_type['type_2'][3])?$c_territory_type['type_2'][3]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text"  readonly style="min-width:120px" >
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text"  readonly style="min-width:120px" >
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" name="territory_type[type_3][0]" placeholder="빌라" value="<?=!empty($c_territory_type['type_3'][0])?$c_territory_type['type_3'][0]:''?>">
              </label>
              <div class="col-6 col-md-2">
                <div style="line-height:54px;">
                  <label class="mb-0">
                    <input type="radio" name="territory_type_use[type_3]" value="use" <?=(!isset($c_territory_type_use['type_3']) || $c_territory_type_use['type_3'] === 'use')?'checked="checked"':'';?>>
                    사용
                  </label>
                  <label class="mb-0">
                    <input type="radio" name="territory_type_use[type_3]" value="" <?=(isset($c_territory_type_use['type_3']) && $c_territory_type_use['type_3'] === '')?'checked="checked"':'';?>>
                    미사용
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group row mb-0">
              <div class="col-12 col-md-12">
                <div class="table-responsive">
                  <table class="table mb-0">
                    <tbody>
                      <tr>
                        <td class="border-top-0">
                          <input class="form-control" type="text" name="territory_type[type_3][1]"  placeholder="빌라명" style="min-width:120px" value="<?=!empty($c_territory_type['type_3'][1])?$c_territory_type['type_3'][1]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text" name="territory_type[type_3][2]"   placeholder="동" style="min-width:120px" value="<?=!empty($c_territory_type['type_3'][2])?$c_territory_type['type_3'][2]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text"  name="territory_type[type_3][3]"  placeholder="호" style="min-width:120px" value="<?=!empty($c_territory_type['type_3'][3])?$c_territory_type['type_3'][3]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text"  readonly style="min-width:120px" >
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text"  readonly style="min-width:120px" >
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" name="territory_type[type_4][0]" placeholder="격지" value="<?=!empty($c_territory_type['type_4'][0])?$c_territory_type['type_4'][0]:''?>">
              </label>
              <div class="col-6 col-md-2">
                <div style="line-height:54px;">
                  <label class="mb-0">
                    <input type="radio" name="territory_type_use[type_4]" value="use" <?=(!isset($c_territory_type_use['type_4']) || $c_territory_type_use['type_4'] === 'use')?'checked="checked"':'';?>>
                    사용
                  </label>
                  <label class="mb-0">
                    <input type="radio" name="territory_type_use[type_4]" value="" <?=(isset($c_territory_type_use['type_4']) && $c_territory_type_use['type_4'] === '')?'checked="checked"':'';?>>
                    미사용
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group row mb-0">
              <div class="col-12 col-md-12">
                <div class="table-responsive">
                  <table class="table mb-0">
                    <tbody>
                      <tr>
                        <td class="border-top-0">
                          <input class="form-control" type="text" placeholder="길이름" style="min-width:120px" readonly>
                        </td>
                        <td class="border-top-0">
                          <input class="form-control" type="text" placeholder="건물번호" style="min-width:120px" readonly>
                        </td>
                        <td class="border-top-0">
                          <input class="form-control" type="text" name="territory_type[type_4][3]" placeholder="상세주소" style="min-width:120px" value="<?=!empty($c_territory_type['type_4'][3])?$c_territory_type['type_4'][3]:''?>">
                        </td>
                        <td class="border-top-0">
                          <input class="form-control" type="text" name="territory_type[type_4][4]" placeholder="층" style="min-width:120px" value="<?=!empty($c_territory_type['type_4'][4])?$c_territory_type['type_4'][4]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text" name="territory_type[type_4][5]" placeholder="이름/호" style="min-width:120px" value="<?=!empty($c_territory_type['type_4'][5])?$c_territory_type['type_4'][5]:''?>">
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" placeholder="편지" readonly>
              </label>
              <div class="col-6 col-md-2">
              </div>
            </div>
            <div class="form-group row mb-0">
              <div class="col-12 col-md-12">
                <div class="table-responsive">
                  <table class="table mb-0">
                    <tbody>
                      <tr>
                        <td class="border-top-0">
                          <input class="form-control" type="text" placeholder="길이름" style="min-width:120px" readonly>
                        </td>
                        <td class="border-top-0">
                          <input class="form-control" type="text"  placeholder="건물번호" style="min-width:120px" readonly>
                        </td>
                        <td class="border-top-0">
                          <input class="form-control" type="text"  name="territory_type[type_5][3]" placeholder="상세주소" style="min-width:120px" value="<?=!empty($c_territory_type['type_5'][3])?$c_territory_type['type_5'][3]:''?>">
                        </td>
                        <td class="border-top-0">
                          <input class="form-control" type="text" name="territory_type[type_5][4]" placeholder="우편번호" style="min-width:120px" value="<?=!empty($c_territory_type['type_5'][4])?$c_territory_type['type_5'][4]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text" name="territory_type[type_5][5]" placeholder="이름" style="min-width:120px" value="<?=!empty($c_territory_type['type_5'][5])?$c_territory_type['type_5'][5]:''?>">
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" placeholder="전화" readonly>
              </label>
              <div class="col-6 col-md-2">

              </div>
            </div>
            <div class="form-group row mb-0">
              <div class="col-12 col-md-12">
                <div class="table-responsive">
                  <table class="table mb-0">
                    <tbody>
                      <tr>
                        <td class="border-top-0">
                          <input class="form-control" type="text" placeholder="전화번호" style="min-width:120px" readonly>
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text" name="territory_type[type_6][2]"  placeholder="업종" style="min-width:120px" value="<?=!empty($c_territory_type['type_6'][2])?$c_territory_type['type_6'][2]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text"  name="territory_type[type_6][3]" placeholder="상호" style="min-width:120px" value="<?=!empty($c_territory_type['type_6'][3])?$c_territory_type['type_6'][3]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text" placeholder="주소" style="min-width:120px" readonly>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="list-group mb-3">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" name="territory_type[type_7][0]" placeholder="추가1" value="<?=!empty($c_territory_type['type_7'][0])?$c_territory_type['type_7'][0]:''?>">
              </label>
              <div class="col-6 col-md-2">
                <div style="line-height:54px;">
                  <label class="mb-0">
                    <input type="radio" name="territory_type_use[type_7]" value="use" <?=(!isset($c_territory_type_use['type_7']) || $c_territory_type_use['type_7'] === 'use')?'checked="checked"':'';?>>
                    사용
                  </label>
                  <label class="mb-0">
                    <input type="radio" name="territory_type_use[type_7]" value="" <?=(isset($c_territory_type_use['type_7']) && $c_territory_type_use['type_7'] === '')?'checked="checked"':'';?>>
                    미사용
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-12 col-md-12">
                <div class="table-responsive">
                  <table class="table mb-0">
                    <tbody>
                      <tr>
                        <td class="border-top-0">
                          <input class="form-control" type="text" placeholder="길이름" style="min-width:120px" readonly>
                        </td>
                        <td class="border-top-0">
                          <input class="form-control" type="text"  placeholder="건물번호" style="min-width:120px" readonly>
                        </td>
                        <td class="border-top-0">
                          <input class="form-control" type="text"  name="territory_type[type_7][3]" style="min-width:120px" value="<?=!empty($c_territory_type['type_7'][3])?$c_territory_type['type_7'][3]:''?>">
                        </td>
                        <td class="border-top-0">
                          <input class="form-control" type="text"  name="territory_type[type_7][4]" style="min-width:120px" value="<?=!empty($c_territory_type['type_7'][4])?$c_territory_type['type_7'][4]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text" name="territory_type[type_7][5]" style="min-width:120px" value="<?=!empty($c_territory_type['type_7'][5])?$c_territory_type['type_7'][5]:''?>">
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="list-group mb-4">
          <div class="list-group-item p-2">
            <div class="form-group row mb-0">
              <label class="col-6 col-md-2 col-form-label">
                <input class="form-control" type="text" name="territory_type[type_8][0]" placeholder="추가2" value="<?=!empty($c_territory_type['type_8'][0])?$c_territory_type['type_8'][0]:''?>">
              </label>
              <div class="col-6 col-md-2">
                <div style="line-height:54px;">
                  <label class="mb-0">
                    <input type="radio" name="territory_type_use[type_8]" value="use" <?=(!isset($c_territory_type_use['type_8']) || $c_territory_type_use['type_8'] === 'use')?'checked="checked"':'';?>>
                    사용
                  </label>
                  <label class="mb-0">
                    <input type="radio" name="territory_type_use[type_8]" value="" <?=(isset($c_territory_type_use['type_8']) && $c_territory_type_use['type_8'] === '')?'checked="checked"':'';?>>
                    미사용
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group row mb-0">
              <div class="col-12 col-md-12">
                <div class="table-responsive">
                  <table class="table mb-0">
                    <tbody>
                      <tr>
                        <td class="border-top-0">
                          <input class="form-control" type="text" name="territory_type[type_8][1]" style="min-width:120px" value="<?=!empty($c_territory_type['type_8'][1])?$c_territory_type['type_8'][1]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text" name="territory_type[type_8][2]" style="min-width:120px" value="<?=!empty($c_territory_type['type_8'][2])?$c_territory_type['type_8'][2]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text" name="territory_type[type_8][3]" style="min-width:120px" value="<?=!empty($c_territory_type['type_8'][3])?$c_territory_type['type_8'][3]:''?>">
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text" readonly style="min-width:120px" >
                        </td>
                        <td class="border-top-0">
                        <input class="form-control" type="text" readonly style="min-width:120px">
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <h5 class="border-bottom mt-4 mb-3 pb-2">특이사항</h5>

        <div class="form-group row">
          <div class="col-6 col-md-2">
            <input class="form-control my-2" type="text"  placeholder="재방"  readonly>
          </div>
          <div class="col-6 col-md-2">
            <input class="form-control my-2" type="text"  placeholder="연구"  readonly>
          </div>
          <div class="col-6 col-md-2">
            <input class="form-control my-2" type="text"  placeholder="JW" name="house_condition[3]" value="<?=!empty($c_house_condition[3])?$c_house_condition[3]:''?>">
          </div>
          <div  class="col-6 col-md-2">
            <div style="line-height:54px;">
              <label class="mb-0">
                <input type="radio" name="house_condition_use[3]" value="use" <?=($c_house_condition_use[3] && $c_house_condition_use[3] == 'use')?'checked="checked"':'';?>>
                사용
              </label>
              <label class="mb-0">
                <input type="radio" name="house_condition_use[3]" value="" <?=empty($c_house_condition_use[3])?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <input class="form-control my-2" type="text"  placeholder="없는집"  name="house_condition[4]" value="<?=!empty($c_house_condition[4])?$c_house_condition[4]:''?>">
          </div>
          <div  class="col-6 col-md-2">
            <div style="line-height:54px;">
              <label class="mb-0">
                <input type="radio" name="house_condition_use[4]" value="use" <?=($c_house_condition_use[4] && $c_house_condition_use[4] == 'use')?'checked="checked"':'';?>>
                사용
              </label>
              <label class="mb-0">
                <input type="radio" name="house_condition_use[4]" value="" <?=empty($c_house_condition_use[4])?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <input class="form-control my-2" type="text"  placeholder="수정요청"  name="house_condition[5]" value="<?=!empty($c_house_condition[5])?$c_house_condition[5]:''?>">
          </div>
          <div  class="col-6 col-md-2">
            <div style="line-height:54px;">
              <label class="mb-0">
                <input type="radio" name="house_condition_use[5]" value="use" <?=($c_house_condition_use[5] && $c_house_condition_use[5] == 'use')?'checked="checked"':'';?>>
                사용
              </label>
              <label class="mb-0">
                <input type="radio" name="house_condition_use[5]" value="" <?=empty($c_house_condition_use[5])?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <input class="form-control my-2" type="text"  placeholder="심한반대"  name="house_condition[6]" value="<?=!empty($c_house_condition[6])?$c_house_condition[6]:''?>">
          </div>
          <div  class="col-6 col-md-2">
            <div style="line-height:54px;">
              <label class="mb-0">
                <input type="radio" name="house_condition_use[6]" value="use" <?=($c_house_condition_use[6] && $c_house_condition_use[6] == 'use')?'checked="checked"':'';?>>
                사용
              </label>
              <label class="mb-0">
                <input type="radio" name="house_condition_use[6]" value="" <?=empty($c_house_condition_use[6])?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <input class="form-control my-2" type="text"  placeholder="외국인"  name="house_condition[7]" value="<?=!empty($c_house_condition[7])?$c_house_condition[7]:''?>">
          </div>
          <div  class="col-6 col-md-2">
            <div style="line-height:54px;">
              <label class="mb-0">
                <input type="radio" name="house_condition_use[7]" value="use" <?=($c_house_condition_use[7] && $c_house_condition_use[7] == 'use')?'checked="checked"':'';?>>
                사용
              </label>
              <label class="mb-0">
                <input type="radio" name="house_condition_use[7]" value="" <?=empty($c_house_condition_use[7])?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <input class="form-control my-2" type="text"  placeholder="기타"  name="house_condition[8]" value="<?=!empty($c_house_condition[8])?$c_house_condition[8]:''?>">
          </div>
          <div  class="col-6 col-md-2">
            <div style="line-height:54px;">
              <label class="mb-0">
                <input type="radio" name="house_condition_use[8]" value="use" <?=($c_house_condition_use[8] && $c_house_condition_use[8] == 'use')?'checked="checked"':'';?>>
                사용
              </label>
              <label class="mb-0">
                <input type="radio" name="house_condition_use[8]" value="" <?=empty($c_house_condition_use[8])?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <input class="form-control my-2" type="text"  placeholder="별도구역"  name="house_condition[9]" value="<?=!empty($c_house_condition[9])?$c_house_condition[9]:''?>">
          </div>
          <div  class="col-6 col-md-2">
            <div style="line-height:54px;">
              <label class="mb-0">
                <input type="radio" name="house_condition_use[9]" value="use" <?=($c_house_condition_use[9] && $c_house_condition_use[9] == 'use')?'checked="checked"':'';?>>
                사용
              </label>
              <label class="mb-0">
                <input type="radio" name="house_condition_use[9]" value="" <?=empty($c_house_condition_use[9])?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
          </div>
          <div class="col-6 col-md-2">
            <input class="form-control my-2" type="text"  name="house_condition[10]" placeholder="추가" value="<?=!empty($c_house_condition[10])?$c_house_condition[10]:''?>">
          </div>
          <div  class="col-6 col-md-2">
            <div style="line-height:54px;">
              <label class="mb-0">
                <input type="radio" name="house_condition_use[10]" value="use" <?=($c_house_condition_use[10] && $c_house_condition_use[10] == 'use')?'checked="checked"':'';?>>
                사용
              </label>
              <label class="mb-0">
                <input type="radio" name="house_condition_use[10]" value="" <?=empty($c_house_condition_use[10])?'checked="checked"':'';?>>
                미사용
              </label>
            </div>
          </div>
        </div>

      </div>

    </div>

    <div class="clearfix mt-4">
      <button type="submit" class="btn btn-outline-primary float-right"><i class="bi bi-save"></i> 저장</button>
    </div>

  </form>

  <hr>

  <div id="v_admin_setting_reset">
    <div class="form-group row">
      <div class="col-12 col-md-12">
        <select class="form-control" v-model="type" style="width: 100px;display: inline-block;">
          <option value="일반"><?=!empty($c_territory_type['type_1'][0])?$c_territory_type['type_1'][0]:'일반'?></option>
          <option value="아파트"><?=!empty($c_territory_type['type_2'][0])?$c_territory_type['type_2'][0]:'아파트'?></option>
          <option value="빌라"><?=!empty($c_territory_type['type_3'][0])?$c_territory_type['type_3'][0]:'빌라'?></option>
          <option value="격지"><?=!empty($c_territory_type['type_4'][0])?$c_territory_type['type_4'][0]:'격지'?></option>
          <option value="편지"><?=!empty($c_territory_type['type_5'][0])?$c_territory_type['type_5'][0]:'편지'?></option>
          <option value="전화"><?=!empty($c_territory_type['type_6'][0])?$c_territory_type['type_6'][0]:'전화'?></option>
          <option value="추가1"><?=!empty($c_territory_type['type_7'][0])?$c_territory_type['type_7'][0]:'추가1'?></option>
          <option value="추가2"><?=!empty($c_territory_type['type_8'][0])?$c_territory_type['type_8'][0]:'추가2'?></option>
        </select>

        <button type="button" class="btn btn-outline-danger" v-on:click="resetAll()">
          <template v-if="reset_spinner">
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              <span class="sr-only">Loading...</span>
          </template>
          <template v-else>
            구역 리셋
          </template>
        </button>

        <div>
          <small class="text-muted">
            개인 구역을 제외한 모든 구역의 배정, 봉사 정보가 리셋되고 기록으로 남게됩니다. <br>한번 리셋을 하면 되돌릴 수 없습니다. 신중하게 진행해주세요.
          </small>
        </div>
      </div>
    </div>
  </div>

  <hr>

  <div id="v_admin_remove_image">
    <div class="form-group row">
      <div class="col-12 col-md-12">
        <button type="button" class="btn btn-outline-danger" v-on:click="removeImage()">
          <template v-if="reset_spinner">
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              <span class="sr-only">Loading...</span>
          </template>
          <template v-else>
            이미지 파일 정리
          </template>
        </button>
        <div>
          <small class="text-muted">
            서버 upload 폴더에 올려진 이미지 파일들 중 사용하지 않는 모든 파일을 삭제합니다. <br>
            매달 한 번씩 정리할 것을 권장해 드립니다.
          </small>
        </div>
      </div>
    </div>
  </div>

  <div class="text-secondary text-right mt-3"><small>앱 버전 <?=APP_VERSION?></small></div>

</div>

<script  language="javascript" type="text/javascript">
  var v_admin_setting_reset = new Vue({
    el: '#v_admin_setting_reset',
    data: {
      type: '일반',
      reset_spinner:false
    },
    methods: {
      resetAll(){
        var type_text = $('#v_admin_setting_reset option[value="'+v_admin_setting_reset.type+'"]').text();
        $('#confirm-modal').confirm(type_text+' 구역을 리셋하시겠습니까? 모든 구역내의 배정, 봉사데이터는 삭제되며 기록으로 남습니다.').on({
          confirm: function () {
            v_admin_setting_reset.reset_spinner = true;
            $.post(BASE_PATH+'/v_data/reset_work.php', { type:v_admin_setting_reset.type }, function(data) {
              alert(data);
              $('#toast').toastMessage('리셋 완료');
              v_admin_setting_reset.reset_spinner = false;
            });
          }
        });
      },
    }
  });
  var v_admin_remove_image = new Vue({
    el: '#v_admin_remove_image',
    data: {
      reset_spinner:false
    },
    methods: {
      removeImage(){
        $('#confirm-modal').confirm('사용하지 않는 이미지 파일을 모두 삭제하시겠습니까?').on({
          confirm: function () {
            v_admin_remove_image.reset_spinner = true;
            $.post(BASE_PATH+'/v_data/remove_image.php', function(data) {
              $('#toast').toastMessage('정리 완료');
              v_admin_remove_image.reset_spinner = false;
            });
          }
        });
      },
    }
  });

var mapContainer = document.getElementById('map'), // 지도를 표시할 div
  mapOption = {
      center: new daum.maps.LatLng(37.1978051, 126.943861), // 지도의 중심좌표
      level: 6 // 지도의 확대 레벨
  };

// 지도를 표시할 div와  지도 옵션으로  지도를 생성합니다
var map = new daum.maps.Map(mapContainer, mapOption);

// 도형 스타일을 변수로 설정합니다
var strokeColor = '#616161',
	fillColor = '#00ffe6',
	fillOpacity = 0.2,
	hintStrokeStyle = 'shortdashdot',
  strokeWeight= 2;

var line_strokeColor = 'rgb(255, 51, 119)',
  line_strokeStyle = 'shortdash',
	line_hintStrokeStyle = 'dash';

var options = { // Drawing Manager를 생성할 때 사용할 옵션입니다
    map: map, // Drawing Manager로 그리기 요소를 그릴 map 객체입니다
    drawingMode: [
        daum.maps.Drawing.OverlayType.POLYGON
    ],
    // 사용자에게 제공할 그리기 가이드 툴팁입니다
    // 사용자에게 도형을 그릴때, 드래그할때, 수정할때 가이드 툴팁을 표시하도록 설정합니다
    guideTooltip: ['draw', 'drag', 'edit'],
    markerOptions: {
        draggable: true,
        removable: true,
        markerImages: [
          null,
          {
            src: BASE_PATH+'/img/marker.png',
            width:25,
            offsetX : 12, // 지도에 고정시킬 이미지 내 위치 좌표
            offsetY : 12, // 지도에 고정시킬 이미지 내 위치 좌표
          }
        ]
    },
    arrowOptions: {
        draggable: true,
        removable: true,
        strokeColor: line_strokeColor,
        strokeStyle: line_strokeStyle,
        hintStrokeStyle: hintStrokeStyle
    },
    polylineOptions: {
        draggable: true,
        removable: true,
        strokeColor: strokeColor,
        strokeStyle: line_strokeStyle,
        hintStrokeStyle: hintStrokeStyle
    },
    rectangleOptions: {
        draggable: true,
        removable: true,
        strokeColor: strokeColor,
        fillColor: fillColor,
        fillOpacity: fillOpacity
    },
    circleOptions: {
        draggable: true,
        removable: true,
        strokeColor: strokeColor,
        fillColor: fillColor,
        fillOpacity: fillOpacity
    },
    polygonOptions: {
        draggable: true,
        removable: true,
        strokeColor: strokeColor,
        fillColor: fillColor,
        fillOpacity: fillOpacity,
        strokeStyle:'shortdashdot',
        strokeWeight:strokeWeight
    }
};

// 위에 작성한 옵션으로 Drawing Manager를 생성합니다
var manager = new daum.maps.Drawing.DrawingManager(options);

// Toolbox를 생성합니다.
// Toolbox 생성 시 위에서 생성한 DrawingManager 객체를 설정합니다.
// DrawingManager 객체를 꼭 설정해야만 그리기 모드와 매니저의 상태를 툴박스에 설정할 수 있습니다.
var toolbox = new daum.maps.Drawing.Toolbox({drawingManager: manager});

// 지도 위에 Toolbox를 표시합니다
// daum.maps.ControlPosition은 컨트롤이 표시될 위치를 정의하는데 TOP은 위 가운데를 의미합니다.
map.addControl(toolbox.getElement(), daum.maps.ControlPosition.TOP);

<?php if(!empty(TERRITORY_BOUNDARY)): ?>
  <?php if(json_last_error() == JSON_ERROR_NONE): ?>
    var data = JSON.parse('<?=TERRITORY_BOUNDARY?>');
    if(typeof data =='object' && typeof data['polygon'][0] !== 'undefined'){

      drawPolygon(data[daum.maps.drawing.OverlayType.POLYGON]);

      // Drawing Manager에서 가져온 데이터 중 다각형을 아래 지도에 표시하는 함수입니다
      function drawPolygon(polygons) {
          var len = polygons.length, i = 0;

          for (; i < len; i++) {
              var path = pointsToPath(polygons[i].points);
              manager.put(daum.maps.drawing.OverlayType.POLYGON, path);
          }
      }

      setTimeout(function(){

        $('#map').show();
        map.relayout();

        // 주어진 영역이 화면 안에 전부 나타날 수 있도록 지도의 중심 좌표와 확대 수준을 설정한다.
        var bounds = new daum.maps.LatLngBounds();

        for (i = 0; i < data[daum.maps.drawing.OverlayType.POLYGON].length; i++) {
            var path = pointsToPath(data[daum.maps.drawing.OverlayType.POLYGON][i].points);
            for (a = 0; a < path.length; a++) {
              bounds.extend(path[a]);
            }
        }

        map.setBounds(bounds);

      }, 1);

    }else{
      $('#map').show();
      map.relayout();

      <?php if(!empty(DEFAULT_LOCATION)): ?>
          // 주소-좌표 변환 객체를 생성합니다
          var geocoder = new daum.maps.services.Geocoder();

          // 주소로 좌표를 검색합니다
          geocoder.addressSearch('<?=DEFAULT_LOCATION?>', function(result, status) {

            // 정상적으로 검색이 완료됐으면
            if (status === daum.maps.services.Status.OK) {
              var coords = new daum.maps.LatLng(result[0].y, result[0].x); // 지도의 중심좌표
              map.setCenter(coords);
            }
          });
      <?php endif; ?>
    }
  <?php endif; ?>
<?php else: ?>
  $('#map').show();
  map.relayout();

  <?php if(!empty(DEFAULT_LOCATION)): ?>
    // 주소-좌표 변환 객체를 생성합니다
    var geocoder = new daum.maps.services.Geocoder();

    // 주소로 좌표를 검색합니다
    geocoder.addressSearch('<?=DEFAULT_LOCATION?>', function(result, status) {

      // 정상적으로 검색이 완료됐으면
      if (status === daum.maps.services.Status.OK) {
        var coords = new daum.maps.LatLng(result[0].y, result[0].x); // 지도의 중심좌표
        map.setCenter(coords);
      }
    });
  <?php endif; ?>
<?php endif; ?>

// Drawing Manager에서 가져온 데이터 중
// 선과 다각형의 꼭지점 정보를 daum.maps.LatLng객체로 생성하고 배열로 반환하는 함수입니다
function pointsToPath(points) {
    var len = points.length,
        path = [],
        i = 0;

    for (; i < len; i++) {
        var latlng = new daum.maps.LatLng(points[i].y, points[i].x);
        path.push(latlng);
    }

    return path;
}

manager.addListener('state_changed', function() {
  var data = manager.getData();
  var json = JSON.stringify(data);
  $("#map_sub_polygon").val(json);
});
</script>

<?php include_once('../footer.php'); ?>
