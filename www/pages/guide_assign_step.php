<?php include_once('../header.php');?>
<?php check_accessible('guide');?>

<?php
$mb_id = mb_id();
$date = isset($s_date)?$s_date:date('Y-m-d');
$m_id = get_meeting_id($date, $ms_id);
$meeting_data = get_meeting_data($m_id);
$ms_ids = explode(',',get_ms_id_by_guide($mb_id));
$auth = (is_admin($mb_id) || in_array($ms_id, $ms_ids))?true:false;
$date_parts = explode("-", $date);
?>

<div id="v_guide_assign_step" style="margin-top:-60px;" m_id="<?=$m_id?>">

  <header class="navbar navbar-expand-xl fixed-top header d-none" >
    <h1 class="text-white mb-0 navbar-brand">인도자 <span class="d-xl-none">봉사인도</span></h1>
    <?php echo header_menu('guide','봉사인도'); ?>
  </header>

  <?php echo footer_menu('인도자'); ?>

  <div id="container" class="container-fluid">

    <nav class="navbar navbar-light bg-light mb-4">
      <a class="navbar-brand" href="<?=BASE_PATH?>/pages/guide_history.php?s_date=<?=$date?>&toYear=<?=$date_parts[0]?>&toMonth=<?=$date_parts[1]?>"><i class="bi bi-arrow-left"></i></a>
      <div class="w-75 float-right text-right mb-0 clearfix" onclick="open_meeting_info('<?=$date?>','<?=$ms_id?>','guide_assign')">
        <div>
          <small class="badge badge-pill badge-light align-middle"><?=get_meeting_schedule_type_text($meeting_data['ms_type'])?></small>
          <?php
          // 취소 뱃지
          if($meeting_data['m_cancle'] != 0){
            echo '<span class="badge badge-pill badge-light text-danger align-middle"><i class="bi bi-x-circle-fill"></i> 취소됨';
            if($meeting_data['m_cancle'] == 2){
              echo '⋮알림비노출';
            }else{
              echo '⋮알림노출';
            }
            echo '</span>';
          }
          ?>
        </div>  
        <div class="mb-0">
          <?=get_datetime_text($meeting_data['m_date'].' '.$meeting_data['ms_time'])?> <i class="bi bi-info-circle align-top text-secondary"></i>
        </div>
        <div style="max-width: 100%;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;">
          <?=$meeting_data['mp_name']?>
        </div>
      </div>
    </nav>

    <div class="alert alert-info" role="alert" v-if="!auth">
      <small>이 모임의 인도/보조자가 아닙니다. 보기만 가능합니다.</small>
    </div>

    <div class="sticky-top bg-white container-fluid border-bottom mb-4">
      <div class="row py-2">
        <div class="col-12">
          <ul class="nav nav-pills nav-fill" style="flex-wrap: nowrap; font-size: 0.85rem;">
            <li class="nav-item" style="flex: 1; min-width: 0;">
              <a class="nav-link" v-on:click="changeMode('attend')" :class="mode=='attend'?'active':''" style="white-space: nowrap; padding: 0.5rem 0.25rem;">참여자 선택</a>
            </li>
            <li class="nav-item" style="flex: 1; min-width: 0;">
              <a class="nav-link" v-on:click="changeMode('assign')" :class="mode=='assign'?'active':''" style="white-space: nowrap; padding: 0.5rem 0.25rem;">구역 배정</a>
            </li>
            <?php if(GUIDE_MEETING_CONTENTS_USE == 'use'): ?>
            <li class="nav-item" v-if="auth" style="flex: 1; min-width: 0;">
              <a class="nav-link" v-on:click="changeMode('contents')" :class="mode=='contents'?'active':''" style="white-space: nowrap; padding: 0.5rem 0.25rem;">모임 내용 기록</a>
            </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>

    <div class="mb-3 p-3 bg-light bg-opacity-10 " v-if="meeting_data.ms_type == 2 && mode != 'contents'">
      <div class="d-flex align-items-center">
        <i class="bi bi-light-circle text-info me-2"></i>
        <small class="text-muted mb-0">
          이 모임은 전시대 선정된 전도인만 참여가 가능합니다.
        </small>
      </div>
    </div>

    <div class="fixed-bottom container-fluid bg-white py-2 border-top" style="bottom:80px;" v-if="mode=='assign' && auth">

        <div class="row py-2" >
          <div class="col-12">
            <div v-html="selectedMembers()" v-if="selected_members.length>0"></div>
            <div v-else class="p-2 bg-light text-center text-secondary">참여자를 선택해 주세요</div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
          <template v-if="selected_territories.length==0&&selected_telephones.length==0&&selected_displays.length==0">
            <div class="p-2 bg-light text-center text-secondary">구역을 선택해 주세요</div>
          </template>
          <template v-else>
            <span class="badge badge-info badge-territory mr-2 my-1 bedge-big " :class="selected_territories.includes(value.id)?'':'d-none'" v-for="(value, index) in territories" v-on:click="selectTerritory(value.id,index)" v-if="value.og_type!='편지'">{{value.num}} · 일반</span>
            <span class="badge badge-info badge-territory mr-2 my-1 bedge-big" :class="selected_telephones.includes(value.id)?'':'d-none'" v-for="(value, index) in telephones" v-on:click="selectTelephone(value.id,index)">{{value.num}} · 전화</span>
            <span class="badge badge-info badge-territory mr-2 my-1 bedge-big" :class="selected_territories.includes(value.id)?'':'d-none'" v-for="(value, index) in territories" v-on:click="selectTerritory(value.id,index)" v-if="value.og_type=='편지'">{{value.num}} · 편지</span>
            <span class="badge badge-info badge-territory mr-2 my-1 bedge-big" :class="selected_displays.includes(value.id)?'':'d-none'" v-for="(value, index) in displays" v-on:click="selectDisplay(value.id,index)">{{value.name+' '+value.num+'팀'}} · 전시대</span>

          </template>
          </div>
        </div>

        <div class="row py-2" >
          <div class="col-12">
            <template v-if="guide_assigned_group_use=='use'">
              <select class="form-control float-left d-inline-block mr-2" v-on:change="changeAssignedGroup($event)" style="width:110px;" id="assign_group">
                <option value="">짝 없음</option>
                <option value="2" :selected="guide_assigned_group_use=='use'">2명씩</option>
                <option value="3">3명씩</option>
                <option value="4">4명씩</option>
                <option value="5">5명씩</option>
                <option value="custom">임의지정</option>
              </select>
              <input type="text" class="form-control float-left" :class="assigned_group=='custom'?'':'d-none'" style="width:140px;" id="custom_assign_group" placeholder="예) 2.3.2" v-on:input="customAssignGroup($event)">
            </template>
            <button type="button" class="btn btn-outline-primary float-right" v-on:click="assignMember()"><i class="bi bi-save"></i> 배정</button>
          </div>
        </div>

    </div>

    <template v-if="mode=='attend'">
      <div class="row">
        <div class="col-12">
          <h5 class="border-bottom mt-4 mb-3 pb-2">참여자<small class="float-right">{{getCountAttendMember()}}명</small></h5>
          <template v-for="member in filteredMembers()">
            <button class="mb btn mb-2 mr-1 btn-member active" v-if="member.attend==1" :uid="member.mb_id" :class="[member.mb_sex=='W'? 'btn-outline-danger' : 'btn-outline-primary']" v-on:click="attendMember(member.mb_id)">{{member.mb_name}}</button>
          </template>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <h5 class="border-bottom mt-4 mb-3 pb-2">전도인<small class="float-right">{{getCountMember()}}명</small></h5>
          <template v-for="(member, index) in filteredMembers()">
            <button class="mb btn mb-2 mr-1 btn-member" v-if="member.mb_id != 0" :uid="member.mb_id" :class="[member.attend==1? 'd-none' : '',member.mb_sex=='W'? 'btn-outline-danger' : 'btn-outline-primary']" v-on:click="attendMember(member.mb_id,index)">{{member.mb_name}}</button>
            <h6 class="border-bottom mt-2 pb-1" v-else>{{member.mb_name}}</h6>
          </template>
        </div>
      </div>
    </template>

    <template v-if="mode=='assign'">
      <div class="row">
        <div class="col-12">
          <h5 class="border-bottom mt-4 mb-3 pb-2">참여자<small class="float-right">{{getCountAttendMember()}}명 중 {{assigned_members.length}}명 배정됨</small></h5>
          <template v-for="(member, index) in filteredMembers()">
            <button class="mb btn mb-2 mr-1 btn-member position-relative" v-if="member.attend==1" :uid="member.mb_id" :disabled="member.attend==1? false : true" :class="[member.attend==1&&member.mb_sex=='W'? 'btn-outline-danger' : '',member.attend==1&&member.mb_sex=='M'? 'btn-outline-primary' : '',selected_members.includes(member.mb_id)?'active':'',assigned_members.includes(member.mb_id)?'btn-member-assigned':'']" v-on:click="selectMember(member.mb_id)">{{member.mb_name}}<i class="bi bi-bookmark-check-fill text-warning" v-if="assigned_members.includes(member.mb_id)" style="position: absolute;top: -6px;left: -3px;"></i></button>
          </template>
        </div>
      </div>
      <div class="row" style="padding-bottom:170px;">
        <div class="col-12">
          <h5 class="border-bottom mt-4 mb-3 pb-2">구역</h5>
          <ul class="nav nav-tabs mb-4" id="v-pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
              <a class="nav-link text-center active" id="v-pills-territory-tab" data-toggle="tab"  href="#v-pills-territory" role="tab" aria-controls="v-pills-territory" aria-selected="true" v-on:click="onTabClick">일반<br><small>({{getCountAssignableTerritory()}})</small></a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link text-center" id="v-pills-telephone-tab" data-toggle="tab" href="#v-pills-telephone" role="tab" aria-controls="v-pills-telephone" aria-selected="false" v-on:click="onTabClick">전화<br><small>({{getCountAssignableTelephone()}})</small></a>
            </li>
            <li class="nav-item" role="presentation" >
              <a class="nav-link text-center" id="v-pills-letter-tab" data-toggle="tab" href="#v-pills-letter" role="tab" aria-controls="v-pills-letter" aria-selected="false" v-on:click="onTabClick">편지<br><small>({{getCountAssignableLetter()}})</small></a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link text-center" id="v-pills-display-tab" data-toggle="tab" href="#v-pills-display" role="tab" aria-controls="v-pills-display" aria-selected="false" v-on:click="onTabClick">전시대<br><small>({{getCountAssignableDisplay()}})</small></a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link text-center" id="v-pills-assign-tab" data-toggle="tab"  href="#v-pills-assign" role="tab" aria-controls="v-pills-assign" aria-selected="true" v-on:click="onTabClick">배정됨<br><small>({{getCountAssignedTotal()}})</small></a>
            </li>
          </ul>
          
          <div class="tab-content" id="v-pills-tabContent">
            <div class="tab-pane fade show active" id="v-pills-territory" role="tabpanel" aria-labelledby="v-pills-territory-tab">
              <!-- 구역 형태 필터 -->
              <div class="mb-3 p-3 bg-light rounded border">
                <div class="d-flex flex-column">
                  <small class="text-muted mb-2 fw-bold">
                    구역 형태 필터
                  </small>
                  <div class="d-flex flex-wrap">
                    <div class="form-check form-check-inline mb-1">
                      <input type="radio" class="form-check-input" name="territoryTypeFilter" id="filterAllTypes" value="all" v-model="territoryTypeFilter">
                      <label class="form-check-label" for="filterAllTypes">
                        전체 ({{getTerritoryCountByType('all')}})
                      </label>
                    </div>
                    <div class="form-check form-check-inline mb-1" v-for="type in territoryTypes">
                      <input type="radio" class="form-check-input" :name="'territoryTypeFilter'" :id="'filterType'+type" :value="type" v-model="territoryTypeFilter">
                      <label class="form-check-label" :for="'filterType'+type">
                        {{type}} ({{getTerritoryCountByType(type)}})
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              <ul class="list-group">
                <template v-for="(value, index) in territories" v-if="isTerritoryAssignable(value)&&(territoryTypeFilter == 'all' || value.type == territoryTypeFilter)">
                  <li class="list-group-item clearfix border-bottom-0 p-2" v-on:click="selectTerritory(value.id,index)" :class="selected_territories.includes(value.id)?'active':''">
                    <p class="mb-1">
                      <span class="badge badge-pill badge-success badge-outline px-1 align-middle">{{value.num}} · {{value.type}}</span>

                      <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle" >
                        <template v-if="value.status == 'absence' || value.status == 'absence_reassign'">
                          <i class="bi bi-person-fill-slash"></i> 부재
                        </template>
                        <template v-else>
                          <i class="bi bi-people-fill"></i> 전체
                        </template>

                        <template v-if="value.all_past_records && value.all_past_records.length > 0">
                          <template v-if="value.all_past_records[0].progress == 'completed'">
                            <span class="text-success">완료</span>
                          </template>
                          <template v-else-if="value.all_past_records[0].progress == 'in_progress'">
                            <span class="text-warning">진행 중</span>
                          </template>             
                        </template>
                      </span>

                    </p>
                    <p class="mb-0">{{value.name}}</p>
                    <div class="mt-n1">
                      <div class="progress d-inline-flex align-middle w-100" style="height: 5px;">
                        <div class="progress-bar" :class="(value.progress || 0) == 100 ? 'bg-success' : 'bg-warning'" role="progressbar" :style="'width:'+(value.progress || 0)+'%'" :aria-valuenow="value.progress || 0" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                    </div>
                    <div class="mt-n2">
                        <small class="text-secondary d-inline-block">
                          전체 {{value.total}} · 만남 {{value.visit}} · 부재 {{value.absence}} · 남은 집 {{value.total - value.visit - value.absence}}
                        </small>
                    </div>

                    <div class="mt-2 px-2 py-1 bg-light rounded" v-if="hasVisitItems(value.all_past_records, 'territory')">
                      <div v-html="visitsHtml(value.all_past_records, 'territory', value.id)"></div>
                    </div>
                  </li>
                  <div class="list-group-item clearfix border-top-0 p-2 mt-0" :class="selected_territories.includes(value.id)?'active':''">
                    <button class="btn btn-sm btn-outline-secondary ml-1 float-right" type="button" v-on:click="ViewTerritory(value.id)">보기</button>
                    <button class="btn btn-sm btn-outline-secondary float-right" type="button" v-on:click="map_view('territory', value.id)">지도</button>
                  </div>
                </template>
              </ul>
            </div>
            <div class="tab-pane fade" id="v-pills-telephone" role="tabpanel" aria-labelledby="v-pills-telephone-tab">
              <ul class="list-group">
                <template v-for="(value, index) in telephones" v-if="isTelephoneAssignable(value)">
                  <li class="list-group-item clearfix border-bottom-0 p-2" v-on:click="selectTelephone(value.id,index)" :class="selected_telephones.includes(value.id)?'active':''">
                    <p class="mb-1">
                      <span class="badge badge-pill badge-warning badge-outline px-1 align-middle">{{value.num}} · 전화</span>

                      <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle" >
                        <template v-if="value.status == 'absence' || value.status == 'absence_reassign'">
                          <i class="bi bi-person-fill-slash"></i> 부재
                        </template>
                        <template v-else>
                          <i class="bi bi-people-fill"></i> 전체
                        </template>

                        <template v-if="value.all_past_records && value.all_past_records.length > 0">
                          <template v-if="value.all_past_records[0].progress == 'completed'">
                            <span class="text-success">완료</span>
                          </template>
                          <template v-else-if="value.all_past_records[0].progress == 'in_progress'">
                            <span class="text-warning">진행 중</span>
                          </template>
                        </template>
                      </span>
                    </p>
                    <p class="mb-0">{{value.name}}</p>
                    <div class="mt-n1">
                      <div class="progress d-inline-flex align-middle w-100" style="height: 5px;">
                        <div class="progress-bar" :class="(value.progress || 0) == 100 ? 'bg-success' : 'bg-warning'" role="progressbar" :style="'width:'+(value.progress || 0)+'%'" :aria-valuenow="value.progress || 0" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                    </div>
                    <div class="mt-n2">
                      <small class="text-secondary d-inline-block">
                         전체 {{value.total}} · 만남 {{value.visit}} · 부재 {{value.absence}} · 남은 집 {{value.total - value.visit - value.absence}}</small>
                    </div>
                    <div class="mt-2 px-2 py-1 bg-light rounded" v-if="hasVisitItems(value.all_past_records, 'telephone')">
                      <div v-html="visitsHtml(value.all_past_records, 'telephone', value.id)"></div>
                    </div>
                  </li>
                  <div class="list-group-item clearfix border-top-0 p-2 mt-0" :class="selected_telephones.includes(value.id)?'active':''">
                    <button class="btn btn-sm btn-outline-secondary float-right" type="button" v-on:click="ViewTelephone(value.id)">보기</button>
                  </div>
                </template>
              </ul>
            </div>
            <div class="tab-pane fade" id="v-pills-letter" role="tabpanel" aria-labelledby="v-pills-letter-tab" >
              <ul class="list-group">
                <template v-for="(value, index) in territories" v-if="isLetterAssignable(value)">
                  <li class="list-group-item clearfix border-bottom-0 p-2" v-on:click="selectTerritory(value.id,index)" :class="selected_territories.includes(value.id)?'active':''">
                    <p class="mb-1">
                      <span class="badge badge-pill badge-info badge-outline px-1 align-middle">{{value.num}} · {{value.type}}</span>

                      <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle" >
                        <template v-if="value.status == 'absence' || value.status == 'absence_reassign'">
                          <i class="bi bi-person-fill-slash"></i> 부재
                        </template>
                        <template v-else>
                          <i class="bi bi-people-fill"></i> 전체
                        </template>

                        <template v-if="value.all_past_records && value.all_past_records.length > 0">
                          <template v-if="value.all_past_records[0].progress == 'completed'">
                            <span class="text-success">완료</span>
                          </template>
                          <template v-else-if="value.all_past_records[0].progress == 'in_progress'">
                            <span class="text-warning">진행 중</span>
                          </template>              
                        </template>
                      </span>

                    </p>
                    <p class="mb-0">{{value.name}}</p>
                    <div class="mt-n1">
                      <div class="progress d-inline-flex align-middle w-100" style="height: 5px;">
                        <div class="progress-bar" :class="(value.progress || 0) == 100 ? 'bg-success' : 'bg-warning'" role="progressbar" :style="'width:'+(value.progress || 0)+'%'" :aria-valuenow="value.progress || 0" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                    </div>
                    <div class="mt-n2">
                      <small class="text-secondary d-inline-block">
                         전체 {{value.total}} · 발송 {{value.visit}} · 남은 집 {{value.total - value.visit - value.absence}}</small>
                    </div>
                    <div class="mt-2 px-2 py-1 bg-light rounded" v-if="hasVisitItems(value.all_past_records, 'territory')">
                      <div v-html="visitsHtml(value.all_past_records, 'territory', value.id)"></div>
                    </div>
                    <div class="assigned_group_name mt-1 ">
                      {{value.assigned_group_name}}
                    </div>
                  </li>
                  <div class="list-group-item clearfix border-top-0 p-2 mt-0" :class="selected_territories.includes(value.id)?'active':''">
                    <button class="btn btn-sm btn-outline-danger float-left" type="button" v-on:click="cancelAssign(value.id, 'territory')" v-if="auth">배정취소</button>
                    <button class="btn btn-sm btn-outline-secondary float-left" type="button" v-on:click="setSelectedMembers(value.id, 'territory', value.assigned_ids,value.assigned_group)" v-if="value.assigned_ids != '' && auth">배정 불러오기</button>
                    <button class="btn btn-sm btn-outline-secondary ml-1 float-right" type="button" v-on:click="ViewTerritory(value.id)">보기</button>
                    <button class="btn btn-sm btn-outline-secondary float-right" type="button" v-on:click="map_view('territory', value.id)">지도</button>
                  </div>
                </template>
              </ul>
            </div>
            <div class="tab-pane fade" id="v-pills-display" role="tabpanel" aria-labelledby="v-pills-display-tab">
              <ul class="list-group">
                <template v-for="(value, index) in displays" v-if="value.m_id != m_id">
                  <li class="list-group-item clearfix border-bottom-0 p-2" v-on:click="selectDisplay(value.id,index)" :class="selected_displays.includes(value.id)?'active':''">
                    <!-- <p class="mb-0"><span class="badge badge-pill badge-info badge-outline px-1 align-middle mr-1">{{index+1}}</span></p> -->
                    <p class="mb-0">{{value.name}} {{value.num}}팀</p>
                    <div class="mt-n1" v-if="value.assigned_date!=''">
                      <small>{{value.assigned_date}} 배정</small>
                    </div>
                  </li>
                  <div class="list-group-item clearfix border-top-0 p-2 mt-0" :class="selected_displays.includes(value.id)?'active':''">
                    <template v-if="(value.address != '') || (value.address != '')">
                      <button type="button" class="btn btn-outline-secondary btn-sm float-right" v-on:click="ViewMap(value.address)" v-if="value.address != ''">
                        지도
                      </button>
                      <button type="button" class="btn btn-outline-secondary btn-sm mr-1 float-right" v-on:click="KakaoNavi(value.address)" v-if="value.address != ''">
                        길찾기
                      </button>
                      <button type="button" class="btn btn-outline-secondary btn-sm mr-1 float-right" v-on:click="ViewRoadview(value.address)" v-if="value.address != ''" >
                        로드뷰
                      </button>
                    </template>
                  </div>
                </template>
              </ul>
            </div>
            <div class="tab-pane fade" id="v-pills-assign" role="tabpanel" aria-labelledby="v-pills-assign-tab">
              <h6 class="border-bottom mt-2 pb-1">일반</h6>
              <ul class="list-group mb-2">
                <template v-for="(value, index) in territories" v-if="value.m_id == m_id&&value.og_type!='편지'">
                  <li class="list-group-item clearfix border-bottom-0 p-2" :class="selected_territories.includes(value.id)?'active':''">
                    <p class="mb-1">
                      <span class="badge badge-pill badge-success badge-outline px-1 align-middle">{{value.num}} · {{value.type}}</span>
                      <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle" >
                        <template v-if="value.status == 'absence' || value.status == 'absence_reassign'">
                          <i class="bi bi-person-fill-slash"></i> 부재
                        </template>
                        <template v-else>
                          <i class="bi bi-people-fill"></i> 전체
                        </template>

                        <template v-if="value.all_past_records && value.all_past_records.length > 0">
                          <template v-if="value.all_past_records[0].progress == 'completed'">
                            <span class="text-success">완료</span>
                          </template>
                          <template v-else-if="value.all_past_records[0].progress == 'in_progress'">
                            <span class="text-warning">진행 중</span>
                          </template>          
                        </template>
                      </span>
                    </p>
                    <p class="mb-0">{{value.name}}</p>
                    <div class="mt-n1">
                      <div class="progress d-inline-flex align-middle w-100" style="height: 5px;">
                        <div class="progress-bar" :class="value.progress == 100 ? 'bg-success' : 'bg-warning'" role="progressbar" :style="'width:'+value.progress+'%'" :aria-valuenow="value.progress" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                    </div>
                    <div class="mt-n2">
                      <small class="text-secondary d-inline-block">
                         전체 {{value.total}} · 만남 {{value.visit}} · 부재 {{value.absence}} · 남은 집 {{value.total - value.visit - value.absence}}</small>
                    </div>
                    <div class="mt-2 px-2 py-1 bg-light rounded" v-if="hasVisitItems(value.all_past_records, 'territory')">
                      <div v-html="visitsHtml(value.all_past_records, 'territory', value.id)"></div>
                    </div>
                    <div class="assigned_group_name mt-1">
                      {{value.assigned_group_name}}
                    </div>
                  </li>
                  <div class="list-group-item clearfix border-top-0 p-2 mt-0" :class="selected_territories.includes(value.id)?'active':''">
                    <button class="btn btn-sm btn-outline-danger mr-1 float-left" type="button" v-on:click="cancelAssign(value.id, 'territory')" v-if="auth">배정취소</button>
                    <button class="btn btn-sm btn-outline-secondary float-left" type="button" v-on:click="setSelectedMembers(value.id, 'territory', value.assigned_ids,value.assigned_group)" v-if="value.assigned_ids != '' && auth">배정 불러오기</button>
                    <button class="btn btn-sm btn-outline-secondary ml-1 float-right" type="button" v-on:click="ViewTerritory(value.id)">보기</button>
                    <button class="btn btn-sm btn-outline-secondary float-right" type="button" v-on:click="map_view('territory', value.id)">지도</button>
                  </div>
                </template>
              </ul>
              <ul class="list-group mb-2">
                <li class="list-group-item clearfix disabled p-2" v-for="(value, index) in territories_record" v-if="value.og_type!='편지'">
                  <p class="mb-1">
                    <span class="badge badge-pill badge-success badge-outline px-1 align-middle">{{value.num}} · {{value.type}}</span>
                    <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle" >
                      <template v-if="value.status == 'absence' || value.status == 'absence_reassign'">
                        <i class="bi bi-person-fill-slash"></i> 부재
                      </template>
                      <template v-else>
                        <i class="bi bi-people-fill"></i> 전체
                      </template>

                      <template v-if="value.all_past_records && value.all_past_records.length > 0">
                        <template v-if="value.all_past_records[0].progress == 'completed'">
                          <span class="text-success">완료</span>
                        </template>
                        <template v-else-if="value.all_past_records[0].progress == 'in_progress'">
                          <span class="text-warning">진행 중</span>
                        </template>          
                      </template>
                    </span>
                  </p>               
                  <p class="mb-1">{{value.name}}</p>
                  <div class="mt-n1">
                    <div class="progress d-inline-flex align-middle w-100" style="height: 5px;">
                      <div class="progress-bar" :class="value.progress == 100 ? 'bg-success' : 'bg-warning'" role="progressbar" :style="'width:'+value.progress+'%'" :aria-valuenow="value.progress" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                  </div>
                  <div class="mt-n2">
                    <small class="text-secondary d-inline-block">
                        전체 {{value.total}} · 만남 {{value.visit}} · 부재 {{value.absence}} · 남은 집 {{value.total - value.visit - value.absence}}</small>
                  </div>
                  <div class="mt-2 px-2 py-1 bg-light rounded" v-if="hasVisitItems(value.all_past_records, 'territory')">
                    <div v-html="visitsHtml(value.all_past_records, 'territory', value.id)"></div>
                  </div>
                  <div class="assigned_group_name mt-1">
                    {{value.assigned_group_name}}
                  </div>
                  <div v-if="value.assigned_date!=''" class="mt-1">
                    <small class="text-secondary">{{value.assigned_date}} 배정</small>
                  </div>
                </li>
              </ul>

              <h6 class="border-bottom mt-2 pb-1">전화</h6>
              <ul class="list-group mb-2">
                <template v-for="(value, index) in telephones" v-if="value.m_id == m_id">
                  <li class="list-group-item clearfix border-bottom-0 p-2" :class="selected_telephones.includes(value.id)?'active':''">
                    <p class="mb-1">
                      <span class="badge badge-pill badge-warning badge-outline px-1 align-middle">{{value.num}} · 전화</span>
                      <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle" >
                        <template v-if="value.status == 'absence' || value.status == 'absence_reassign'">
                          <i class="bi bi-person-fill-slash"></i> 부재
                        </template>
                        <template v-else>
                          <i class="bi bi-people-fill"></i> 전체
                        </template>

                        <template v-if="value.all_past_records && value.all_past_records.length > 0">
                          <template v-if="value.all_past_records[0].progress == 'completed'">
                            <span class="text-success">완료</span>
                          </template>
                          <template v-else-if="value.all_past_records[0].progress == 'in_progress'">
                            <span class="text-warning">진행 중</span>
                          </template>              
                        </template>
                      </span>
                    </p>
                    <p class="mb-0">{{value.name}}</p>
                    <div class="mt-n1">
                      <div class="progress d-inline-flex align-middle w-100" style="height: 5px;">
                        <div class="progress-bar" :class="value.progress == 100 ? 'bg-success' : 'bg-warning'" role="progressbar" :style="'width:'+value.progress+'%'" :aria-valuenow="value.progress" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                    </div>
                    <div class="mt-n2">
                      <small class="text-secondary d-inline-block">
                         전체 {{value.total}} · 만남 {{value.visit}} · 부재 {{value.absence}} · 남은 집 {{value.total - value.visit - value.absence}}</small>
                    </div>
                    <div class="mt-2 px-2 py-1 bg-light rounded" v-if="hasVisitItems(value.all_past_records, 'telephone')">
                      <div v-html="visitsHtml(value.all_past_records, 'telephone', value.id)"></div>
                    </div>
                    <div class="assigned_group_name mt-1">
                      {{value.assigned_group_name}}
                    </div>
                  </li>
                  <div class="list-group-item clearfix border-top-0 p-2 mt-0" :class="selected_telephones.includes(value.id)?'active':''">
                    <button class="btn btn-sm btn-outline-danger float-left" type="button" v-on:click="cancelAssign(value.id, 'telephone')" v-if="auth">배정취소</button>
                    <button class="btn btn-sm btn-outline-secondary float-right" type="button" v-on:click="ViewTelephone(value.id)">보기</button>
                    <button class="btn btn-sm btn-outline-secondary mr-2 float-right" type="button" v-on:click="setSelectedMembers(value.id, 'telephone', value.assigned_ids,value.assigned_group)" v-if="value.assigned_ids != '' && auth">배정 불러오기</button>
                  </div>
                </template>
              </ul>
              <ul class="list-group mb-2">
                <li class="list-group-item clearfix disabled p-2" v-for="(value, index) in telephones_record">
                  <p class="mb-1">
                    <span class="badge badge-pill badge-warning badge-outline px-1 align-middle">{{value.num}} · 전화</span>
                    <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle" >
                      <template v-if="value.status == 'absence' || value.status == 'absence_reassign'">
                        <i class="bi bi-person-fill-slash"></i> 부재
                      </template>
                      <template v-else>
                        <i class="bi bi-people-fill"></i> 전체
                      </template>

                      <template v-if="value.all_past_records && value.all_past_records.length > 0">
                        <template v-if="value.all_past_records[0].progress == 'completed'">
                          <span class="text-success">완료</span>
                        </template>
                        <template v-else-if="value.all_past_records[0].progress == 'in_progress'">
                          <span class="text-warning">진행 중</span>
                        </template>              
                      </template>
                    </span>
                  </p>                  
                  <p class="mb-1">{{value.name}}</p>
                  <div class="mt-n1">
                    <div class="progress d-inline-flex align-middle w-100" style="height: 5px;">
                      <div class="progress-bar" :class="value.progress == 100 ? 'bg-success' : 'bg-warning'" role="progressbar" :style="'width:'+value.progress+'%'" :aria-valuenow="value.progress" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                  </div>
                  <div class="mt-n2">
                    <small class="text-secondary d-inline-block">
                        전체 {{value.total}} · 만남 {{value.visit}} · 부재 {{value.absence}} · 남은 집 {{value.total - value.visit - value.absence}}</small>
                  </div>
                  <div class="mt-2 px-2 py-1 bg-light rounded" v-if="hasVisitItems(value.all_past_records, 'telephone')">
                    <div v-html="visitsHtml(value.all_past_records, 'telephone', value.id)"></div>
                  </div>
                  <div class="assigned_group_name mt-1">
                    {{value.assigned_group_name}}
                  </div>
                  <div v-if="value.assigned_date!=''" class="mt-1">
                    <small class="text-secondary">{{value.assigned_date}} 배정</small>
                  </div>
                </li>
              </ul>

              <h6 class="border-bottom mt-2 pb-1">편지</h6>
              <ul class="list-group mb-2">
                <template v-for="(value, index) in territories" v-if="value.m_id == m_id&&value.og_type=='편지'">
                  <li class="list-group-item clearfix border-bottom-0 p-2" :class="selected_territories.includes(value.id)?'active':''">
                    <p class="mb-1">
                      <span class="badge badge-pill badge-info badge-outline px-1 align-middle">{{value.num}} · 편지</span>
                      <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle" >
                        <template v-if="value.status == 'absence' || value.status == 'absence_reassign'">
                          <i class="bi bi-person-fill-slash"></i> 부재
                        </template>
                        <template v-else>
                          <i class="bi bi-people-fill"></i> 전체
                        </template>

                        <template v-if="value.all_past_records && value.all_past_records.length > 0">
                          <template v-if="value.all_past_records[0].progress == 'completed'">
                            <span class="text-success">완료</span>
                          </template>
                          <template v-else-if="value.all_past_records[0].progress == 'in_progress'">
                            <span class="text-warning">진행 중</span>
                          </template>              
                        </template>
                      </span>
                    </p>
                    <p class="mb-0">{{value.name}}</p>
                    <div class="mt-n1">
                      <div class="progress d-inline-flex align-middle w-100" style="height: 5px;">
                        <div class="progress-bar" :class="value.progress == 100 ? 'bg-success' : 'bg-warning'" role="progressbar" :style="'width:'+value.progress+'%'" :aria-valuenow="value.progress" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                    </div>
                    <div class="mt-n2">
                      <small class="text-secondary d-inline-block">
                         전체 {{value.total}} · 발송 {{value.visit}} · 남은 집 {{value.total - value.visit - value.absence}}</small>
                    </div>
                    <div class="mt-2 px-2 py-1 bg-light rounded" v-if="hasVisitItems(value.all_past_records, 'territory')">
                      <div v-html="visitsHtml(value.all_past_records, 'territory', value.id)"></div>
                    </div>
                    <div class="assigned_group_name mt-1">
                      {{value.assigned_group_name}}
                    </div>
                  </li>
                  <div class="list-group-item clearfix border-top-0 p-2 mt-0" :class="selected_territories.includes(value.id)?'active':''">
                    <button class="btn btn-sm btn-outline-danger float-left" type="button" v-on:click="cancelAssign(value.id, 'territory')" v-if="auth">배정취소</button>
                    <button class="btn btn-sm btn-outline-secondary float-right" type="button" v-on:click="ViewTerritory(value.id)">보기</button>
                    <button class="btn btn-sm btn-outline-secondary mr-2 float-right" type="button" v-on:click="setSelectedMembers(value.id, 'territory', value.assigned_ids,value.assigned_group)" v-if="value.assigned_ids != '' && auth">배정 불러오기</button>
                  </div>
                </template>
              </ul>
              <ul class="list-group mb-2">
                <li class="list-group-item clearfix disabled p-2" v-for="(value, index) in territories_record" v-if="value.og_type=='편지'">
                  <p class="mb-1">
                    <span class="badge badge-pill badge-info badge-outline px-1 align-middle">{{value.num}} · 편지</span>
                    <span class="badge badge-pill badge-secondary badge-outline px-1 align-middle" >
                      <template v-if="value.status == 'absence' || value.status == 'absence_reassign'">
                        <i class="bi bi-person-fill-slash"></i> 부재
                      </template>
                      <template v-else>
                        <i class="bi bi-people-fill"></i> 전체
                      </template>

                      <template v-if="value.all_past_records && value.all_past_records.length > 0">
                        <template v-if="value.all_past_records[0].progress == 'completed'">
                          <span class="text-success">완료</span>
                        </template>
                        <template v-else-if="value.all_past_records[0].progress == 'in_progress'">
                          <span class="text-warning">진행 중</span>
                        </template>              
                      </template>
                    </span>
                  </p>
                  <p class="mb-1">{{value.name}}</p>
                  <div class="mt-n1">
                    <div class="progress d-inline-flex align-middle w-100" style="height: 5px;">
                      <div class="progress-bar" :class="value.progress == 100 ? 'bg-success' : 'bg-warning'" role="progressbar" :style="'width:'+value.progress+'%'" :aria-valuenow="value.progress" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                  </div>
                  <div class="mt-n2">
                    <small class="text-secondary d-inline-block">
                        전체 {{value.total}} · 발송 {{value.visit}} · 남은 집 {{value.total - value.visit - value.absence}}</small>
                  </div>
                  <div class="mt-2 px-2 py-1 bg-light rounded" v-if="hasVisitItems(value.all_past_records, 'territory')">
                    <div v-html="visitsHtml(value.all_past_records, 'territory', value.id)"></div>
                  </div>
                  <div class="assigned_group_name mt-1">
                    {{value.assigned_group_name}}
                  </div>
                  <div v-if="value.assigned_date!=''" class="mt-1">
                    <small class="text-secondary">{{value.assigned_date}} 배정</small>
                  </div>
                </li>
              </ul>

              <h6 class="border-bottom mt-2 pb-1">전시대</h6>
              <ul class="list-group">
                <template v-for="(value, index) in displays" v-if="value.m_id == m_id">
                  <li class="list-group-item clearfix border-bottom-0 p-2" :class="selected_displays.includes(value.id)?'active':''">
                    <p>{{value.name}} {{value.num}}팀</p>
                    <div class="assigned_group_name mt-1">
                      {{value.assigned_group_name}}
                    </div>
                    <div v-if="value.assigned_date!=''" class="mt-1">
                      <small class="text-secondary">{{value.assigned_date}} 배정</small>
                    </div>
                  </li>
                  <div class="list-group-item clearfix border-top-0 p-2 mt-0" :class="selected_displays.includes(value.id)?'active':''">
                    <button class="btn btn-sm btn-outline-danger float-left" type="button" v-on:click="cancelAssign(value.id, 'display')" v-if="auth">배정취소</button>
                    <template v-if="(value.address != '') || (value.address != '')">
                      <button type="button" class="btn btn-outline-info btn-sm float-right" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" >
                        <i class="bi bi-geo-alt"></i>
                      </button>
                      <div class="dropdown-menu">
                        <button class="dropdown-item" type="button" v-on:click="ViewRoadview(value.address)" v-if="value.address != ''">로드뷰</button>
                        <button class="dropdown-item" type="button" v-on:click="KakaoNavi(value.address)" v-if="value.address != ''">길찾기</button>
                        <button class="dropdown-item" type="button" v-on:click="ViewMap(value.address)" v-if="value.address != ''">지도보기</button>
                      </div>
                    </template>
                    <button class="btn btn-sm btn-outline-secondary mr-2 float-right" type="button" v-on:click="setSelectedMembers(value.id, 'display', value.assigned_ids,value.assigned_group)" v-if="value.assigned_ids != '' && auth">배정 불러오기</button>
                  </div>
                </template>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="row invisible">
        <div class="col-12">

            <span class="badge badge-info badge-territory mr-2 my-1 bedge-big" :class="selected_territories.includes(value.id)?'':'d-none'" v-for="(value, index) in territories" v-if="value.og_type!='편지'">{{value.num}} · 일반</span>
            <span class="badge badge-info badge-territory mr-2 my-1 bedge-big" :class="selected_telephones.includes(value.id)?'':'d-none'" v-for="(value, index) in telephones" >{{value.num}} · 전화</span>
            <span class="badge badge-info badge-territory mr-2 my-1 bedge-big" :class="selected_territories.includes(value.id)?'':'d-none'" v-for="(value, index) in territories" v-if="value.og_type=='편지'">{{value.num}} · 편지</span>
            <span class="badge badge-info badge-territory mr-2 my-1 bedge-big" :class="selected_displays.includes(value.id)?'':'d-none'" v-for="(value, index) in displays" >{{index+1}} · 전시대</span>

        </div>
      </div>

      <div class="row invisible">
        <div class="col-12">

          <div v-html="selectedMembers()"></div>

        </div>
      </div>

    </template>

    <?php if(GUIDE_MEETING_CONTENTS_USE == 'use'): ?>
    <template v-if="auth&&mode=='contents'">
      <form id="guide_record_form" >
        <input type="hidden" name="work" value="update_meeting_contents">
        <input type="hidden" name="m_id" value="<?=$m_id?>">

        <div class="form-group row">
          <div class="col-12 col-md-12">
            <h5 class="border-bottom mt-4 mb-3 pb-2">모임 내용</h5>
            <textarea class="form-control" name="m_contents" rows="12" ><?=$meeting_data['m_contents']?$meeting_data['m_contents']:GUIDE_MEETING_CONTENTS?></textarea>
          </div>
        </div>

        <div class="mt-2 text-right">
          <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save"></i> 저장</button>
        </div>
      </form>
    </template>
    <?php endif; ?>

  </div>

</div>

<script type="text/javascript">
$(document).ready(function(){
  var height = $('.container-fluid .fixed-top').outerHeight();
  $('#v_guide_assign_step').css('margin-top', (height - 57)+'px');

  $( window ).bind("resize", function(){
    var height = $('.container-fluid .fixed-top').outerHeight();
    $('#v_guide_assign_step').css('margin-top', (height - 57)+'px');
  });
});
</script>

<script language="javascript" type="text/javascript">
  function cho_hangul(str) {
    cho = ["ㄱ","ㄲ","ㄴ","ㄷ","ㄸ","ㄹ","ㅁ","ㅂ","ㅃ","ㅅ","ㅆ","ㅇ","ㅈ","ㅉ","ㅊ","ㅋ","ㅌ","ㅍ","ㅎ"];
    result = "";
    code = str.charCodeAt(0)-44032;
    if(code>-1 && code<11172) result += cho[Math.floor(code/588)];
    else result = str.charAt(0);
    return result;
  }

  var v_guide_assign_step = new Vue({
    el: '#v_guide_assign_step',
    data: {
      m_id: <?=$m_id?>,
      ms_id: <?=$ms_id?>,
      meeting_data: <?=json_encode($meeting_data)?>,
      auth: <?= $auth ? 'true' : 'false' ?>,
      guide_assigned_group_use: '<?=GUIDE_ASSIGNED_GROUP_USE?>',
      absence_use: '<?=ABSENCE_USE?>',
      members: [],
      assigned_members: [],
      assigned_group:'<?=GUIDE_ASSIGNED_GROUP_USE?'2':''?>',
      custom_assigned_group:'',
      mode: 'attend',
      toggle:'collapse',
      selected_members: [],
      selected_territories:[],
      selected_telephones:[],
      selected_displays:[],
      territories:[],
      telephones:[],
      displays:[],
      territories_record:[],
      telephones_record:[],

      territoryTypeFilter: 'all',
      territoryTypes: [],
      activeTabId: '',
      expandedVisits: {}
    },
    methods: {
      formatDate(dateString) {
        if (!dateString || dateString === '0000-00-00' || dateString === '') {
          return '';
        }
        try {
          const date = new Date(dateString);
          if (isNaN(date.getTime())) {
            return '';
          }
          const year = date.getFullYear();
          const month = date.getMonth() + 1;
          const day = date.getDate();
          if (isNaN(year) || isNaN(month) || isNaN(day)) {
            return '';
          }
          return year + '.' + month + '.' + day;
        } catch (e) {
          return '';
        }
      },
      isExpanded(key){
        return !!this.expandedVisits[key];
      },
      toggleExpanded(key, e){
        if(e){ try{ e.preventDefault(); e.stopPropagation(); }catch(_){} }
        this.$set(this.expandedVisits, key, !this.expandedVisits[key]);
      },
      buildVisitItems(visits, tableType){
        if (!Array.isArray(visits)) return [];
        const items = [];
        for (let i = 0; i < visits.length; i++) {
          const v = visits[i];
          if (!v || !Array.isArray(v.records) || v.records.length === 0) continue;
          const first = v.records[0];
          const last = v.records[v.records.length - 1];
          const start = last && last.start_date ? String(last.start_date) : '';
          const end = first && first.end_date ? String(first.end_date) : '';
          const hasValidStart = !!(start && start !== '0000-00-00');
          const hasValidEnd = !!(end && end !== '0000-00-00');
          if (!(hasValidStart || hasValidEnd)) continue; // 날짜가 전혀 없으면 제외
          const isMatch = first.table === tableType;
          items.push({
            className: isMatch ? '' : 'text-secondary',
            iconHtml: v.visit === '부재' ? "<i class='bi bi-person-fill-slash'></i> 부재" : "<i class='bi bi-people-fill'></i> 전체",
            startDate: hasValidStart ? start : '',
            endDate: hasValidEnd ? end : ''
          });
        }
        return items;
      },
      hasVisitItems(visits, tableType){
        return this.buildVisitItems(visits, tableType).length > 0;
      },
      visitsHtml(visits, tableType, pid){
        const items = this.buildVisitItems(visits, tableType);
        if (items.length === 0) return '';
        const expanded = this.isExpanded(pid);
        const visible = expanded ? items : items.slice(0,1);
        let html = '';
        visible.forEach(it => {
          html += '<small class="'+(it.className?it.className+' ':'')+'d-block">'+it.iconHtml+' ';
          html += (it.startDate?this.formatDate(it.startDate):'')+' ~ ';
          if(it.endDate){ html += this.formatDate(it.endDate); }
          html += '</small>';
        });
        // 토글 버튼 (미묘하게, 덜 눈에 띄게)
        if(items.length > 1){
          const expanded = this.isExpanded(pid);
          const btnText = expanded ? '접기' : '더보기';
          const icon = expanded ? '<i class="bi bi-chevron-up"></i>' : '<i class="bi bi-chevron-down"></i>';
          html += '<button type="button" class="btn btn-link btn-sm text-secondary p-0 mt-1" onclick="v_guide_assign_step.toggleExpanded(\''+pid+'\', event)">'+icon+' <small>'+btnText+'</small></button>';
        }
        return html;
      },
      init(){
        this.listMembers();
        this.countAssignMember();
      },
      changeMode(mode){
        this.mode = mode;
        if(this.mode == 'assign'){
          this.listTerritory();
          this.listTelephone();
          this.listDisplay();
          this.listTerritoryRecord();
          this.listTelephoneRecord();
          
          // 탭이 로드된 후 첫 번째 활성 탭 설정
          this.$nextTick(() => {
            this.setActiveTab();
          });
        }
      },
      setActiveTab(){
        // 첫 번째 표시되는 탭을 활성화하되, 사용자가 선택한 탭이 있으면 그 탭을 우선 유지
        const tabs = document.querySelectorAll('#v-pills-tab .nav-link');
        let candidateTab = null;

        // 1) 사용자가 선택한 탭이 있고 여전히 표시 가능하면 그 탭 유지
        if (this.activeTabId) {
          tabs.forEach(tab => {
            if (!candidateTab && tab.getAttribute('href') === this.activeTabId && tab.parentElement.style.display !== 'none' && tab.parentElement.offsetParent !== null) {
              candidateTab = tab;
            }
          });
        }

        // 2) 없으면 첫 번째 표시되는 탭 선택
        if(!candidateTab){
          for(let tab of tabs) {
            if(tab.parentElement.style.display !== 'none' && tab.parentElement.offsetParent !== null) {
              candidateTab = tab;
              break;
            }
          }
        }
        
        if(candidateTab) {
          // 모든 탭 비활성화
          tabs.forEach(tab => {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
          });
          
          // 해당 탭 활성화
          candidateTab.classList.add('active');
          candidateTab.setAttribute('aria-selected', 'true');
          this.activeTabId = candidateTab.getAttribute('href');
          
          // 탭 콘텐츠 활성화
          const tabContents = document.querySelectorAll('.tab-pane');
          tabContents.forEach(content => {
            content.classList.remove('show', 'active');
          });
          const targetContent = document.querySelector(this.activeTabId);
          if(targetContent) {
            targetContent.classList.add('show', 'active');
          }
        }
      },
      selectedMembers(){

        var html = '';

        if(this.assigned_group == ''){  // 짝 없음

          this.selected_members.forEach(function(mb_id){
            var sex = v_guide_assign_step.getMemberSex(mb_id);
            var sex_class = sex=='M'?'badge-primary':'badge-danger';
            html += '<span class="badge '+sex_class+' mr-2 my-1 badge-member bedge-big position-relative" onclick="v_guide_assign_step.selectMember(\''+mb_id+'\')">'+v_guide_assign_step.getMemberName(mb_id);
            if(v_guide_assign_step.assigned_members.includes(mb_id)){
              html += '<i class="bi bi-bookmark-check-fill text-warning" style="position: absolute;top: -1px;left: -1px;font-size:12px;"></i>';
            }
            html +=  '</span>';
          })

        }else if(this.assigned_group == 'custom'){ // 임의지정

          var custom_assigned_group = this.custom_assigned_group.split( '.' );
          custom_assigned_group = custom_assigned_group.filter(Number);

          if(custom_assigned_group.length > 1){

            var c_group = 1;
            this.selected_members.forEach(function(mb_id, index){

              var sex = v_guide_assign_step.getMemberSex(mb_id);
              var sex_class = sex=='M'?'badge-primary':'badge-danger';

              var i = c_group-2;
              var con_index = 0;
              if(i >= 0){
                while(custom_assigned_group[i]){
                  con_index = con_index + custom_assigned_group[i]*1;
                  i = i-1;
                }
              }

              if(index == con_index){
                if(custom_assigned_group[c_group-1] < 6){
                  html += '<span class="bedge-wrap">';
                }
              }

              var mr = '';
              if((index == con_index + (custom_assigned_group[c_group-1]-1) ) || !custom_assigned_group[c_group-1] ){
                mr = 'mr-2';
              }

              html += '<span class="badge '+sex_class+' my-1 badge-member bedge-big position-relative '+mr+'" onclick="v_guide_assign_step.selectMember(\''+mb_id+'\')">'+v_guide_assign_step.getMemberName(mb_id);
              if(v_guide_assign_step.assigned_members.includes(mb_id)){
                html += '<i class="bi bi-bookmark-check-fill text-warning" style="position: absolute;top: -1px;left: -1px;font-size:12px;"></i>';
              }
              html +=  '</span>';

              if(index == con_index + (custom_assigned_group[c_group-1]-1)){
                if(custom_assigned_group[c_group-1] < 6){
                  html += '</span>';
                }
                c_group++;
              }

            });

          }else{

            var c_group = 1;
            this.selected_members.forEach(function(mb_id, index){
              var sex = v_guide_assign_step.getMemberSex(mb_id);
              var sex_class = sex=='M'?'badge-primary':'badge-danger';

              if(custom_assigned_group.length == 1){
                var assigned_group = custom_assigned_group[0];
              }else{
                var assigned_group = 1;
              }

              if(index == (c_group-1) * assigned_group){
                if(assigned_group < 6){
                  html += '<span class="bedge-wrap">';
                }
              }

              var mr = '';
              if(index == ((c_group-1) * assigned_group) + (assigned_group-1)){
                mr = 'mr-2';
              }

              html += '<span class="badge '+sex_class+' my-1 badge-member bedge-big position-relative '+mr+'" onclick="v_guide_assign_step.selectMember(\''+mb_id+'\')">'+v_guide_assign_step.getMemberName(mb_id);
              if(v_guide_assign_step.assigned_members.includes(mb_id)){
                html += '<i class="bi bi-bookmark-check-fill text-warning" style="position: absolute;top: -1px;left: -1px;font-size:12px;"></i>';
              }
              html +=  '</span>';

              if(index == ((c_group-1) * assigned_group) + (assigned_group-1)){
                if(assigned_group < 6){
                  html += '</span>';
                }
                c_group++;
              }
            });

          }

        }else{ // 숫자

          var c_group = 1;

          this.selected_members.forEach(function(mb_id, index){

            var sex = v_guide_assign_step.getMemberSex(mb_id);
            var sex_class = sex=='M'?'badge-primary':'badge-danger';

            if( index == (c_group-1) * v_guide_assign_step.assigned_group){
              if(v_guide_assign_step.assigned_group < 6){
                html += '<span class="bedge-wrap">';
              }
            }

            var mr = '';
            if(index == ((c_group-1) * v_guide_assign_step.assigned_group) + (v_guide_assign_step.assigned_group-1)){
              mr = 'mr-2';
            }

            html += '<span class="badge '+sex_class+' my-1 badge-member bedge-big position-relative '+mr+'" onclick="v_guide_assign_step.selectMember(\''+mb_id+'\')">'+v_guide_assign_step.getMemberName(mb_id);
            if(v_guide_assign_step.assigned_members.includes(mb_id)){
              html += '<i class="bi bi-bookmark-check-fill text-warning" style="position: absolute;top: -1px;left: -1px;font-size:12px;"></i>';
            }
            html +=  '</span>';

            if(index == ((c_group-1) * v_guide_assign_step.assigned_group) + (v_guide_assign_step.assigned_group-1)){
              if(v_guide_assign_step.assigned_group < 6){
                html += '</span>';
              }
              c_group++;
            }

          })

        }

        return html;

      },
      setSelectedMembers(territory_id, territory_type, assigned_ids, assigned_group){

        this.selected_members = [];
        this.selected_territories = [];
        this.selected_telephones = [];
        this.selected_displays = [];

        if(assigned_ids){
          assigned_ids = assigned_ids.split( ',' );

          this.selected_members = assigned_ids;

          if(this.guide_assigned_group_use == 'use'){

            assigned_group = assigned_group.split( ',' );
            assigned_group = assigned_group.filter((element, i) => element != null);

            if(assigned_group != '' && assigned_group.length == 1){
              var array = [2, 3, 4, 5];
              if(assigned_group[0] == 2 || assigned_group[0] == 3 || assigned_group[0] == 4 || assigned_group[0] == 5){
                this.assigned_group = assigned_group[0];
                this.custom_assigned_group = '';
                $('#custom_assign_group').val('');
                $("#assign_group option[value=\""+assigned_group[0]+"\"]").prop("selected", true);
              }else{
                this.assigned_group = 'custom';
                this.custom_assigned_group = assigned_group[0];
                $('#custom_assign_group').val(assigned_group[0]);
                $("#assign_group option:last").prop("selected", true);
              }
            }else if(assigned_group.length > 1){
              this.assigned_group = 'custom';
              this.custom_assigned_group = assigned_group.join( '.' );
              $('#custom_assign_group').val(assigned_group.join( '.' ));
              $("#assign_group option:last").prop("selected", true);
            }else{
              this.assigned_group = '';
              this.custom_assigned_group = '';
              $('#custom_assign_group').val('');
              $("#assign_group option:eq(0)").prop("selected", true);
            }

          }
        }

        if(territory_type == 'territory'){
          this.selected_territories.push(territory_id);
        }else if(territory_type == 'telephone'){
          this.selected_telephones.push(territory_id);
        }else if(territory_type == 'display'){
          this.selected_displays.push(territory_id);
        }

        $('#toast').toastMessage('불러오기 완료');
      },
      getMemberName(mb_id){
        var result = '';
        for(i=0;i < this.members.length;i++){
          if(this.members[i].mb_id == mb_id){
            result = this.members[i].mb_name;
            break;

          }
        }
        return result;
      },
      getMemberSex(mb_id){
        var result = '';
        for(i=0;i < this.members.length;i++){
          if(this.members[i].mb_id == mb_id){
            result = this.members[i].mb_sex;
            break;

          }
        }
        return result;
      },
      changeAssignedGroup(event){

        this.assigned_group = event.target.value;

      },
      customAssignGroup(event){

        var str = event.target.value;
        str = str.replace(/[^.\d|\.]/g, '').replace(/\.+/g, '.');

        $('#custom_assign_group').val(str);

        this.custom_assigned_group = str;

      },
      countAssignMember(){

        $.post(BASE_PATH+'/v_data/guide_assign_step_countassignmember.php', { m_id: v_guide_assign_step.m_id }, function(data) {
          var members = JSON.parse(data);
          members = Object.values(members);
          v_guide_assign_step.assigned_members = members;
        });

      },
      getCountAttendMember(){
        var attend_members = this.members.filter((element) => element.attend == 1);
        return attend_members.length;
      },
      getCountMember(){
        var members = this.members.filter((element) => element.mb_id != 0);
        return members.length;
      },
      getCountAssignableTerritory(){
        var territories = this.territories.filter((element) => this.isTerritoryAssignable(element));
        return territories.length;
      },
      getCountAssignableTelephone(){
        var telephones = this.telephones.filter((element) => this.isTelephoneAssignable(element));
        return telephones.length;
      },
      getCountAssignableDisplay(){
        var displays = this.displays.filter((element) => element.m_id != this.m_id);
        return displays.length;
      },
      getCountAssignableLetter(){
        var letters = this.territories.filter((element) => this.isLetterAssignable(element));
        return letters.length;
      },
      getCountAssignedTotal(){
        var territories = this.territories.filter((element) => element.m_id == this.m_id);
        var telephones = this.telephones.filter((element) => element.m_id == this.m_id);
        var displays = this.displays.filter((element) => element.m_id == this.m_id);

        var territories_record = this.territories_record;
        var telephones_record = this.telephones_record;

        var total_length = territories.length + telephones.length + displays.length + territories_record.length + telephones_record.length;
        
        return total_length;
      },
      attendMember(mb_id){
        const index = this.members.findIndex(m => m.mb_id == mb_id);
        if(index === -1) return; // 없는 경우 처리

        if(!this.auth) return false;
        if(this.m_id == '') return false;

        if(v_guide_assign_step.members[index].attend == 1){
          var action = 'delete';
        }else{
          var action = 'add';
        }

        $.ajax({
                          url: BASE_PATH+'/pages/guide_work.php',
          data: {
            work: 'select_minister',
            action: action,
            m_id: v_guide_assign_step.m_id,
            current_mb_id: mb_id
          },
          type: 'POST',
          async: false,
          dataType: 'html',
          success: function(result){
            if(!result) {
              alert('서버 응답이 비어있습니다.');
              return;
            }
            try {
              result = JSON.parse(result);
            } catch(e) {
              alert('서버 응답이 올바른 JSON이 아닙니다.');
              return;
            }
            if(result.attend == '1'){
              v_guide_assign_step.members[index].attend = 1;
            }else if(result.attend == '0'){
              v_guide_assign_step.members[index].attend = 0;
            }
          },
        });

      },
      assignMember(){
        if(!this.auth) return false;
        if(this.m_id == '') return false;
        if(this.mode!='assign') return false;
        if(this.selected_members.length == 0 ){ $('#toast').toastMessage('참여자를 선택해 주세요'); return false; }
        if(this.selected_territories.length == 0 && this.selected_telephones.length == 0 && this.selected_displays.length == 0){ $('#toast').toastMessage('구역을 선택해 주세요'); return false; }

        // 전시대 배정일 때만 체크
        if(this.selected_displays.length > 0) {
          // 선택된 전도인 중 mb_display == 1(전시대 불가능)인 사람 있는지 확인
          const impossible = this.selected_members.some(mb_id => {
            const member = this.members.find(m => m.mb_id == mb_id);
            return member && member.mb_display == 1;
          });
          if(impossible) {
            if(!confirm('전시대 참여가 불가능한 전도인이 포함되어 있습니다.\n계속 배정하시겠습니까?')) {
              return false; // 취소 시 진행 중단
            }
          }
        }

        var territories = this.selected_territories;
        var telephones = this.selected_telephones;
        var displays = this.selected_displays;
        var selected_members = this.selected_members;
        var m_id = this.m_id;
        var assigned_group = this.assigned_group=='custom'?this.custom_assigned_group.replace(/\./g,','):this.assigned_group;

        $.post(BASE_PATH+'/pages/guide_work.php', { work: 'assign', territories: territories, telephones: telephones, displays: displays, member: selected_members, m_id: m_id, assigned_group:assigned_group }, function(data) {
          v_guide_assign_step.listTerritory();
          v_guide_assign_step.listTelephone();
          v_guide_assign_step.listDisplay();
          v_guide_assign_step.countAssignMember();

          v_guide_assign_step.selected_members = [];
          v_guide_assign_step.selected_territories = [];
          v_guide_assign_step.selected_telephones = [];
          v_guide_assign_step.selected_displays = [];

          $('#toast').toastMessage('배정 완료');
        });
      },
      cancelAssign(id,table){
        if(!this.auth) return false;
        if(this.m_id == '') return false;

        $('#confirm-modal').confirm('구역 배정을 취소하시겠습니까?<br>배정된 후의 봉사 기록은 삭제됩니다.').on({
          confirm: function () {
            $.post(BASE_PATH+'/pages/guide_work.php', { work: 'assign_cancel', pid: id, table: table, m_id: v_guide_assign_step.m_id }, function(data) {
              v_guide_assign_step.listTerritory();
              v_guide_assign_step.listTelephone();
              v_guide_assign_step.listDisplay();
              v_guide_assign_step.countAssignMember();
            });

            $('#toast').toastMessage('취소 완료');
          }
        });
      },
      listMembers(){
        $.post(BASE_PATH+'/v_data/guide_assign_step_member.php', { m_id: v_guide_assign_step.m_id }, function(data) {
          var members = JSON.parse(data);
            var current_cho = '';
            for(i=0;i < members.length;i++){
              var cho = cho_hangul(members[i]['mb_name']);
              if(current_cho != cho){
                var arr = {
                  "mb_id" : 0,
                  "mb_name" : cho
                };
                members.splice(i, 0, arr);
                current_cho = cho;
              }

            }

            v_guide_assign_step.members = members;
        });
      }, 
      listTerritory(){
        $.post(BASE_PATH+'/v_data/guide_assign_step_territory.php', { m_id:v_guide_assign_step.m_id, ms_id: v_guide_assign_step.ms_id }, function(data) {
          var territories = JSON.parse(data);
          v_guide_assign_step.territories = territories;
          
          // 구역 형태 수집
          var types = [];
          territories.forEach(function(territory) {
            if (territory.type && !types.includes(territory.type)) {
              types.push(territory.type);
            }
          });
          v_guide_assign_step.territoryTypes = types;
          
          // 탭 재설정
          v_guide_assign_step.$nextTick(() => {
            v_guide_assign_step.setActiveTab();
          });
        });
      },
      listTelephone(){
        $.post(BASE_PATH+'/v_data/guide_assign_step_telephone.php', { m_id:v_guide_assign_step.m_id, ms_id: v_guide_assign_step.ms_id }, function(data) {
          var telephones = JSON.parse(data);
          v_guide_assign_step.telephones = telephones;
          
          // 탭 재설정
          v_guide_assign_step.$nextTick(() => {
            v_guide_assign_step.setActiveTab();
          });
        });
      },
      listDisplay(){
        $.post(BASE_PATH+'/v_data/guide_assign_step_display.php', { m_id:v_guide_assign_step.m_id }, function(data) {
          var displays = JSON.parse(data);
          v_guide_assign_step.displays = displays;
          
          // 탭 재설정
          v_guide_assign_step.$nextTick(() => {
            v_guide_assign_step.setActiveTab();
          });
        });
      },
      listTerritoryRecord(){
        $.post(BASE_PATH+'/v_data/guide_assign_step_territory_record.php', { m_id:v_guide_assign_step.m_id }, function(data) {
          var territories = JSON.parse(data);
          v_guide_assign_step.territories_record = territories;
        });
      },
      listTelephoneRecord(){
        $.post(BASE_PATH+'/v_data/guide_assign_step_telephone_record.php', { m_id:v_guide_assign_step.m_id }, function(data) {
          var telephones = JSON.parse(data);
          v_guide_assign_step.telephones_record = telephones;
        });
      },
      ViewTerritory(id){
        open_territory_view(id,'view');
      },
      ViewTelephone(id){
        open_telephone_view(id);
      },
      ViewRoadview(address){
        daum_roadview(address);
      },
      KakaoNavi(address){
        kakao_navi(address,address);
      },
      ViewMap(address){
        map_view('house',address);
      },
      selectMember(mb_id){
        if(!this.auth) return false;

        if(this.selected_members.includes(mb_id)){
          var selected_members = this.selected_members.filter((element) => element !== mb_id);
          this.selected_members = selected_members;
        }else{
          this.selected_members.push(mb_id);
        }
      },
      selectTerritory(id,index){
        if(!this.auth) return false;

        if(this.selected_territories.includes(id)){
          var selected_territories = this.selected_territories.filter((element) => element !== id);
          this.selected_territories = selected_territories;
        }else{
          this.selected_territories.push(id);
        }
      },
      selectTelephone(id,index){
        if(!this.auth) return false;

        if(this.selected_telephones.includes(id)){
          var selected_telephones = this.selected_telephones.filter((element) => element !== id);
          this.selected_telephones = selected_telephones;
        }else{
          this.selected_telephones.push(id);
        }
      },
      selectDisplay(id,index){
        if(!this.auth) return false;

        if(this.selected_displays.includes(id)){
          var selected_displays = this.selected_displays.filter((element) => element !== id);
          this.selected_displays = selected_displays;
        }else{
          this.selected_displays.push(id);
        }
      },

      filteredMembers() {
        // 모임 형태가 전시대(ms_type == 2)이면 전시대 선정된 사람들만 표시
        // 다른 모임 형태이면 전체 표시
        if (this.meeting_data.ms_type == 2) {
          return this.members.filter(m =>
            m.mb_id == 0 || // 구분자(초성)는 항상 표시
            m.mb_display == 0 // 전시대 선정된 사람들만
          );
        } else {
          return this.members; // 전체 표시
        }
      },
      getTerritoryCountByType(type) {
        if (type === 'all') {
          return this.territories.filter(territory => 
            this.isTerritoryAssignable(territory)
          ).length;
        } else {
          return this.territories.filter(territory => 
            this.isTerritoryAssignable(territory) && 
            territory.type === type
          ).length;
        }
      },

      onTabClick(event){
        const href = event.currentTarget && event.currentTarget.getAttribute('href');
        if (href) {
          this.activeTabId = href;
        }
      },
      isTerritoryAssignable(territory) {
        // 이미 배정된 구역은 제외
        if (territory.m_id == this.m_id) return false;
        // 편지 구역은 제외 (편지 탭에서 처리)
        if (territory.og_type == '편지') return false;
        
        // 부재자 방문 사용 여부에 따른 필터링
        if (this.absence_use == 'use') {
          // 부재자 방문 사용 시: 부재 완료인 구역은 배정 불가
          if (territory.current_status == '1' && territory.progress_status == 'completed') return false;
        } else {
          // 부재자 방문 미사용 시: 전체 완료인 구역, 부재 구역은 배정 불가
          if (territory.progress_status == 'completed' || territory.current_status == '1') return false;
        }
        
        return true;
      },
      isTelephoneAssignable(telephone) {
        // 이미 배정된 전화 구역은 제외
        if (telephone.m_id == this.m_id) return false;
        
        // 부재자 방문 사용 여부에 따른 필터링
        if (this.absence_use == 'use') {
          // 부재자 방문 사용 시: 부재 완료인 구역은 배정 불가
          if (telephone.current_status == '1' && telephone.progress_status == 'completed') return false;
        } else {
          // 부재자 방문 미사용 시: 전체 완료인 구역, 부재 구역은 배정 불가
          if (telephone.progress_status == 'completed' || telephone.current_status == '1') return false;
        }
        
        return true;
      },
      isLetterAssignable(territory) {
        // 이미 배정된 구역은 제외
        if (territory.m_id == this.m_id) return false;
        // 편지 구역만 포함
        if (territory.og_type != '편지') return false;
        
        // 부재자 방문 사용 여부에 따른 필터링
        if (this.absence_use == 'use') {
          // 부재자 방문 사용 시: 부재 완료인 구역은 배정 불가
          if (territory.current_status == '1' && territory.progress_status == 'completed') return false;
        } else {
          // 부재자 방문 미사용 시: 전체 완료인 구역, 부재 구역은 배정 불가
          if (territory.progress_status == 'completed' || territory.current_status == '1') return false;
        }
        
        return true;
      }
    }
  });

  v_guide_assign_step.init();

  setInterval(() => v_guide_assign_step.listMembers(), 10000);
  setInterval(() => v_guide_assign_step.listTerritory(), 10000);
  setInterval(() => v_guide_assign_step.listTelephone(), 10000);
  setInterval(() => v_guide_assign_step.listDisplay(), 10000);
  setInterval(() => v_guide_assign_step.countAssignMember(), 10000);

</script>

<?php include_once('../footer.php');?>
