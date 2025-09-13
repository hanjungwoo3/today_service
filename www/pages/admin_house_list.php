<?php include_once('../config.php');?>

<div class="mb-2 clearfix">
  <span class="float-left" v-if="houses.length&&houses.length>0">총 {{Number(total).toLocaleString()}}개</span>
</div>
<div class="table-responsive" style="min-height: 350px;">
  <table class="table mb-0" style="min-width: 1100px;">
    <colgroup>
      <col style="width:10px;">
      <col style="width:30px;">
      <col style="width:100px;" v-if="type==1">
      <col style="width:80px;">
      <col style="width:90px;" v-if="type==2">
      <col style="width:120px;" v-if="type==2">
      <col>
      <col style="width:100px;">
      <col style="width:120px;">
      <col style="width:120px;">
      <col style="width:70px;">
    </colgroup>
    <thead class="thead-light text-center">
      <tr>
        <th class="text-center">
          <input id="all_check" type="checkbox" onclick="if($(this).is(':checked')){ $('#admin_house_list input[type=checkbox]:not(#all_check)').prop('checked', true); $('#admin_house_list tbody tr').addClass('checked'); }else{ $('#admin_house_list input[type=checkbox]:not(#all_check)').prop('checked', false); $('#admin_house_list tbody tr').removeClass('checked'); }">
        </th>
        <th class="text-center">No</th>
        <th v-if="type==1">구역 형태</th>
        <th>세대 ID</th>
        <th v-if="type==2">업종</th>
        <th v-if="type==2">상호</th>
        <th>주소</th>
        <th>특이사항</th>
        <th>기록 시간</th>
        <th>기록 전도인</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <template v-if="houses.length&&houses.length>0">
        <tr v-for="(value, index) in houses">
          <td class="text-center align-middle">
            <input type="checkbox" name="h_id[]" :value="value.id" onchange="if($(this).is(':checked')){ $(this).parent().parent().addClass('checked'); }else{$(this).parent().parent().removeClass('checked');}">
          </td>
          <td class="align-middle text-center">{{((page-1)*limit)+index+1}}</td>
          <td class="align-middle text-center" v-if="type==1">{{value.tt_type}}</td>
          <td class="align-middle text-center">{{value.id}}</td>
          <td class="align-middle" v-if="type==2">{{value.tph_type}}</td>
          <td class="align-middle" v-if="type==2">{{value.tph_name}}</td>
          <td class="align-middle">{{value.address}}</td>
          <td class="text-center align-middle text-sm">{{value.condition_text}}</td>
          <td class="text-center align-middle text-secondary text-sm font-weight-light">{{value.condition_text?value.cdate:''}}</td>
          <td class="text-center align-middle">{{value.condition_text?value.mb_name:''}}</td>
          <td class="text-center align-middle">
            <template v-if="value.type == 1 || value.type == 3">
              <div class="dropdown">
                <button class="btn btn-outline-secondary" type="button" :id="'ex'+value.id+'_tt'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="bi bi-three-dots-vertical "></i>
                </button>
                <div class="dropdown-menu dropdown-menu-left" :aria-labelledby="'ex'+value.id+'_tt'" >
                  <button class="dropdown-item" type="button" v-on:click="openTerritoryView(value.pid);">구역 보기</button>
                  <button class="dropdown-item" type="button" v-on:click="territoryWork('house',value.pid,'','','');">세대 편집</button>
                  <button class="dropdown-item" type="button" v-on:click="returnvisit('territory','transfer',value.id)" v-if="value.condition==1||value.condition==2">{{value.condition==1?'재방문':'연구'}} 양도</button>
                </div>
              </div>
            </template>
            <template v-else>
              <div class="dropdown">
                <button class="btn btn-outline-secondary" type="button" :id="'ex'+value.id+'_tp'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="bi bi-three-dots-vertical "></i>
                </button>
                <div class="dropdown-menu dropdown-menu-left" :aria-labelledby="'ex'+value.id+'_tp'" >
                  <button class="dropdown-item" type="button" v-on:click="openTelephoneView(value.pid);">구역 보기</button>
                  <button class="dropdown-item" type="button" v-on:click="telephoneWork('house',value.pid,'','','');">세대 편집</button>
                  <button class="dropdown-item" type="button" v-on:click="returnvisit('telephone','transfer',value.id)" v-if="value.condition==1||value.condition==2">{{value.condition==1?'재방문':'연구'}} 양도</button>
                </div>
              </div>
            </template>
          </td>
        </tr>
      </template>
      <template v-else>
        <tr>
          <td :colspan="type==1?9:8" class="text-center align-middle text-secondary">검색 결과가 존재하지 않습니다</td>
        </tr>
      </template>
    </tbody>
  </table>
</div>

<template v-if="houses.length>0">
  <div class="p-3"></div>
  <v-pagination v-model="page" :length="pagelength" :total-visible="7" prev-icon="bi bi-caret-left-fill" next-icon="bi bi-caret-right-fill" @input="onPageChange"></v-pagination>
</template>

<script  language="javascript" type="text/javascript">
  var v_admin_house = new Vue({
    vuetify: new Vuetify(),
    el: '#v_admin_house',
    data: {
      type:'1',
      s_type:'전체',
      h_assign:'전체',
      h_address1:'',
      h_address2:'', 
      h_address3:'',
      h_address4:'',
      h_address5:'',
      tph_number:'',
      tph_type:'',
      tph_name:'',
      tph_address:'',

      p_id:'',
      h_id:'',
      total:0,
      page:1,
      pagelength:1,
      limit : <?=defined('TERRITORY_ITEM_PER_PAGE') && TERRITORY_ITEM_PER_PAGE ? TERRITORY_ITEM_PER_PAGE : 50?>,
      houses:[],
      search_spinner:false
    },
    methods: {
        searchHouse(pageInput = this.page){
            this.search_spinner =  true;

            $.post(BASE_PATH+'/v_data/admin_house.php', { type: this.type, s_type: this.s_type, h_assign: this.h_assign, h_address1: this.h_address1, h_address2: this.h_address2, h_address3: this.h_address3, h_address4: this.h_address4, h_address5: this.h_address5, tph_number: this.tph_number, tph_type: this.tph_type, tph_name: this.tph_name, tph_address: this.tph_address, h_id: this.h_id, p_id: this.p_id, page: pageInput }, function(data) {
              try {  
                var houses = JSON.parse(data);
                if(houses){
                  v_admin_house.houses = houses;
                  // v_admin_house.sortTerritory();
                  v_admin_house.page = pageInput;
                }else{
                  v_admin_house.houses = [];
                }
                $.each(v_admin_house.houses, function (index, value) {
                  v_admin_house.total = value.total
                  v_admin_house.pagelength = value.pagelength
                  });
              } catch (e) {}
              v_admin_house.search_spinner = false;
            });
        },
        onPageChange() {
            this.searchHouse();
        },
        updateHouse(h_id){
            $.post(BASE_PATH+'/v_data/admin_house_one.php', { type: this.type, id:h_id }, function(data) {
                var house = JSON.parse(data);

                if(house){
                  $.each(v_admin_house.houses, function (index, value) {
                    if(value.id == h_id){
                      Vue.set(v_admin_house.houses, index, house);
                      return false;
                    }
                  });
                }

            });
        },
        territoryWork(work, tt_id, tt_num, update_page, update_wrap_id){
          territory_work(work,tt_id,tt_num,update_page,update_wrap_id);
        },
        openTerritoryView(tt_id){
          open_territory_view(tt_id,'view');
        },
        telephoneWork(work, tp_id, tp_num, update_page, update_wrap_id){
          telephone_work(work,tp_id,tp_num,update_page,update_wrap_id);
        },
        openTelephoneView(tp_id){
          open_telephone_view(tp_id,'view');
        },
        returnvisit(table,work,pid){
          returnvisit(table,work,pid);
        }

    }
});
</script>
