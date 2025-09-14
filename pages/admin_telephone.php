<?php include_once('../header.php');?>
<?php check_accessible('admin');?>

<?php $ms_options = get_meeting_option();?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">관리자 <span class="d-xl-none">전화 구역 관리</span></h1>
  <?php echo header_menu('admin','전화 구역 관리'); ?>
</header>

<?php echo footer_menu('관리자'); ?>

<div id="v_admin_telephone">

  <div id="container" class="container-fluid">

    <form id="admin-telephone-search-form" class="clearfix" action="" method="post">
      <?php include_once('../include/territory_search_filter.php');?>
    </form>

    <form id="admin_telephone_assign" action="" method="post">
      <input type="hidden" name="work" value="assign">
      <div style="overflow:hidden;" class="mb-4">
        <div class="row">
          <div class="col-12 mb-3">선택한 구역들을</div>
        </div>
        <div class="row">
          <div class="col-4 border-right">
            <div class="row">
              <div class="col-12 text-center p-3 border-bottom">
                <button type="submit" onclick="javascript: jQuery('#admin_telephone_assign input[name=work]').val('check_delete');" class="btn btn-outline-danger" ><i class="bi bi-trash"></i> 삭제</button>
              </div>
              <div class="col-12 text-center p-3">
                <button type="submit" onclick="javascript: jQuery('#admin_telephone_assign input[name=work]').val('check_reset');" class="btn btn-outline-secondary" ><i class="bi bi-recycle"></i> 리셋</button>
              </div>
            </div>
          </div>
          <div class="col-8 align-self-center">
            <select class="form-control" name="ms_id">
              <option value="0">분배되지 않음</option>
              <optgroup label="모임 형태">
                <option value="all_3">전체</option>
                <option value="all_1"><?=get_meeting_schedule_type_text(1)?></option>
                <option value="all_2"><?=get_meeting_schedule_type_text(2)?></option>
                <option value="all_4"><?=get_meeting_schedule_type_text(3)?></option>
                <option value="all_5"><?=get_meeting_schedule_type_text(4)?></option>
                <option value="all_6"><?=get_meeting_schedule_type_text(5)?></option>
                <option value="all_7"><?=get_meeting_schedule_type_text(6)?></option>
              </optgroup>
              <optgroup label="모임 계획">
                <?php echo $ms_options;?>
              </optgroup>
            </select>
            <div class="mt-2">모임 계획 으로 <button type="submit" onclick="javascript: jQuery('#admin_telephone_assign input[name=work]').val('assign');" class="btn btn-outline-secondary">분배</button></div>
          </div>
        </div>
      </div>
      <div id="admin-telephone-list">
          <div class="mb-2 clearfix">
              <span class="float-left" v-if="telephones.length>0">총합 {{Number(total).toLocaleString()}}개</span>
              <button type="button" class="btn btn-outline-primary float-right" v-on:click="telephoneWork('add','','','','');">
                  <i class="bi bi-plus-circle-dotted"></i> 추가
              </button>
          </div>

          <div class="table-responsive" style="min-height: 350px;">
              <table class="table mb-0" style="min-width: 1200px;">
                  <colgroup>
                      <col style="width:10px;">
                      <col style="width:30px;">
                      <col style="width:110px;">
                      <col style="width:80px;">
                      <col>
                      <col style="width:70px;">
                      <col style="width:70px;">
                      <col style="width:140px;">
                      <col style="width:100px;">
                      <col style="width:90px;">
                      <col style="width:180px;">
                      <col style="width:100px;">
                      <col style="width:90px;">
                      <col style="width:70px;">
                  </colgroup>
                  <thead class="thead-light">
                      <tr>
                          <th class="text-center align-middle"><input id="all_check" type="checkbox" onclick="if($(this).is(':checked')){ $('#admin-telephone-list input[type=checkbox]:not(#all_check)').prop('checked', true); $('#admin-telephone-list tbody tr').addClass('checked'); }else{ $('#admin-telephone-list input[type=checkbox]:not(#all_check)').prop('checked', false); $('#admin-telephone-list tbody tr').removeClass('checked'); }"></th>
                          <th class="text-center align-middle">No</th>
                          <th class="text-center align-middle fixed">구역 번호</th>
                          <th class="text-center align-middle">구역 ID</th>
                          <th class="align-middle">구역 이름</th>
                          <th class="text-center align-middle">세대수</th>
                          <th class="text-center align-middle">부재</th>
                          <th class="text-center align-middle">분배 상태<br/><small>요일 (모임 계획 ID)</small></th>
                          <th class="text-center align-middle">개인 구역<br/><small>배정 날짜</small></th>
                          <th class="text-center align-middle">봉사 기록</th>
                          <th class="text-center align-middle">진행 상태<br/><small>진행 기간</small></th>
                          <th class="text-center align-middle">배정 상태<br/><small>최근 배정일</small></th>
                          <th class="text-center align-middle">특이사항</th>
                          <th>&nbsp;</th>
                      </tr>
                  </thead>
                  <tbody>
                      <template v-if="telephones.length>0">
                          <tr v-for="(value, index) in telephones">
                              <td class="text-center align-middle">
                                  <input type="checkbox" name="tp_id[]" :value="value.id" onchange="if($(this).is(':checked')){ $(this).parent().parent().addClass('checked'); }else{$(this).parent().parent().removeClass('checked');}">
                              </td>
                              <td class="text-center align-middle">{{((page-1)*limit)+index+1}}</td>
                              <td class="text-center align-middle fixed">{{value.num}}</td>
                              <td class="text-center align-middle">{{value.id}}</td>
                              <td class="align-middle">{{value.name}}</td>
                              <td class="text-center align-middle">{{value.house_count}}</td>
                              <td class="text-center align-middle">{{value.absence}}</td>
                              <td class="text-center align-middle">{{value.ms_id_text}}</td>
                              <td class="text-center align-middle">
                                <template v-if="value.return_visit_member!=''">
                                  {{value.return_visit_member}}<br><small>{{value.return_visit_date}}</small>
                                </template>
                              </td>
                              <td class="text-center align-middle">
                                  <button class="btn btn-outline-info" type="button" v-on:click="telephoneWork('record',value.id,'','','');"><i class="bi bi-list-task"></i> ({{value.record_count}})</button>
                              </td>
                              <td class="text-center align-middle">
                                <small>
                                <i v-if="value.status_text === '부재'" class="bi bi-person-fill-slash"></i>
                                <i v-else-if="value.status_text === '전체'" class="bi bi-people-fill"></i>
                                {{value.status_text}} 
                                </small>
                                <small v-if="value.status_detail.includes('진행중')" class="text-warning">{{value.status_detail}}</small>
                                <small v-else-if="value.status_detail.includes('완료')" class="text-success">{{value.status_detail}}</small>
                                <small v-else>{{value.status_detail}}</small>
                                <small>({{value.progress}}%)</small><br/>

                                <small>{{value.progress_date}}</small>
                              </td>
                              <td class="text-center align-middle">{{value.status}}<br/><small>{{value.latest_assigned_date}}</small></td>
                              <td class="text-center align-middle">
                                  <button type="button" v-on:click="memoWork('telephone',value.id);" class="btn btn-outline-danger mb-0 align-middle" v-if="value.memo!=''">
                                      <i class="bi bi-exclamation-triangle"></i>
                                  </button>
                              </td>
                              <td class="text-center align-middle">
                                  <div class="dropdown">
                                  <button class="btn btn-outline-secondary" type="button" :id="'ex'+value.id" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                      <i class="bi bi-three-dots-vertical "></i>
                                  </button>
                                  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="'ex'+value.id" >
                                      <button class="dropdown-item" type="button" v-on:click="openTelephoneView(value.id);">구역 보기</button>
                                      <button class="dropdown-item" type="button" v-on:click="telephoneWork('edit',value.id,value.num,'','');">구역 수정</button>
                                      <button class="dropdown-item" type="button" v-on:click="telephoneWork('copy',value.id,value.num,'','','');">구역 복제</button>
                                      <button class="dropdown-item" type="button" v-on:click="telephoneWork('del',value.id,value.num,'','');">구역 삭제</button>
                                      <button class="dropdown-item" type="button" v-on:click="telephoneWork('house',value.id,'','','');">세대 편집</button>
                                      <button class="dropdown-item" type="button" v-on:click="telephoneWork('upload',value.id,value.num,'','');">엑셀업로드</button>
                                      <button class="dropdown-item" type="button" v-on:click="telephoneWork('download',value.id);">엑셀다운로드</button>
                                      <button class="dropdown-item" type="button" v-on:click="telephoneWork('reset',value.id,value.num,'','');">구역 리셋</button>
                                  </div>
                                  </div>
                              </td>
                          </tr>
                      </template>
                      <template v-else>
                          <tr>
                              <td colspan="13" class="text-center align-middle text-secondary">검색 결과가 존재하지 않습니다</td>
                          </tr>
                      </template>
                  </tbody>
              </table>
          </div>

          <template v-if="telephones.length>0">
            <div class="p-3"></div>
            <v-pagination v-model="page" :length="pagelength" :total-visible="7" prev-icon="bi bi-caret-left-fill" next-icon="bi bi-caret-right-fill" @input="onPageChange"></v-pagination>
          </template>

      </div>
      <div class="p-3"></div>
    </form>

  </div>
</div>

<script language="javascript" type="text/javascript">
  var v_admin_telephone = new Vue({
    vuetify: new Vuetify(),
    el: '#v_admin_telephone',
    data: {
        location: 'admin',
        search_type: 'letter',
        admin_telephone_sort: '<?=ADMIN_TERRITORY_SORT?>',
        s_assign:'선택안함',
        s_status:'선택안함',
        s_num:'',
        s_name:'',
        s_memo:'선택안함',
        total:0,
        page:1,
        pagelength:1,
        limit : <?=TERRITORY_ITEM_PER_PAGE?TERRITORY_ITEM_PER_PAGE:50?>,
        telephones:[],
        search_spinner:false
    },
    methods: {
        sortTelephone(){
          if(this.admin_telephone_sort == '1'){
              v_admin_telephone.telephones = _.sortBy(v_admin_telephone.telephones, [o => Number(o['num'])], ['asc']);
          }else{
              v_admin_telephone.telephones = _.sortBy(v_admin_telephone.telephones, [o => String(o['num'])], ['asc']);
          }
        },
        searchFilter(pageInput = this.page){
            this.search_spinner =  true;

            $.post(BASE_PATH+'/v_data/admin_telephone.php', { s_assign: this.s_assign, s_status: this.s_status, s_num: this.s_num, s_name: this.s_name, s_memo: this.s_memo, page: pageInput }, function(data) {
              try {  
                var telephones = JSON.parse(data);
                if(telephones){
                  v_admin_telephone.telephones = telephones;
                  v_admin_telephone.sortTelephone();
                  v_admin_telephone.page = pageInput;
                }else{
                  v_admin_telephone.telephones = [];
                }
                $.each(v_admin_telephone.telephones, function (index, value) {
                  v_admin_telephone.total = value.total
                  v_admin_telephone.pagelength = value.pagelength
                });
              } catch (e) {}
              v_admin_telephone.search_spinner = false;
            });
            // e.preventDefault();
        },
        onPageChange() {
            this.searchFilter();
        },
        insertTelephone(tp_id){
          $.post(BASE_PATH+'/v_data/admin_telephone_one.php', { id:tp_id }, function(data) {
                var telephone = JSON.parse(data);
                if(telephone){
                  v_admin_telephone.telephones.push(telephone);
                  v_admin_telephone.sortTelephone();
                }

            });
            this.total = parseInt(this.total) + 1;
        },
        updateTelephone(tp_id){
            $.post(BASE_PATH+'/v_data/admin_telephone_one.php', { id:tp_id }, function(data) {
                var telephone = JSON.parse(data);

                if(telephone){
                  $.each(v_admin_telephone.telephones, function (index, value) {
                    if(value.id == tp_id){
                      Vue.set(v_admin_telephone.telephones, index, telephone);
                      return false;
                    }
                  });
                }

            });
        },
        deleteTelephone(tp_id){
          $.each(v_admin_telephone.telephones, function (index, value) {
            if(value.id == tp_id){
              Vue.delete(v_admin_telephone.telephones, index);
              return false;
            }
          });
          this.total = parseInt(this.total) - 1;
        },
        memoWork(table,pid){
          memo_work(table,pid);
        },
        telephoneWork(work, tp_id, tt_num, update_page, update_wrap_id){
          telephone_work(work,tp_id,tt_num,update_page,update_wrap_id);
        },
        openTelephoneView(tp_id){
          open_telephone_view(tp_id,'view');
        }

    }
});
</script>

<?php include_once('../footer.php');?>
