<?php include_once('../config.php');?>

<div class="table-responsive">
  <table class="table" style="min-width: 1450px;">
    <colgroup>
      <col style="width:15px;">
      <col style="width:85px;">
      <col style="width:75px;">
      <col style="width:155px;">
      <col style="width:130px;">
      <col style="width:110px;">
      <col style="width:100px;">
      <col style="width:105px;">
      <col style="width:120px;">
      <col>
      <col style="width:115px;">
      <col style="width:115px;">
    </colgroup>
    <thead class="thead-light">
      <tr class="text-center">
        <th scope="col">No</th>
        <th scope="col" class="fixed">이름
          <a type="button" :class="sortedClass('mb_name')" @click="sortMember('mb_name')">
            <i class="bi bi-arrow-down-up"></i>
          </a>
        </th>
        <th scope="col">성별
          <a type="button" :class="sortedClass('mb_sex')" @click="sortMember('mb_sex')">
            <i class="bi bi-arrow-down-up"></i>
          </a>
        </th>
        <th scope="col">연락처 </th>
        <th scope="col">파이오니아
          <a type="button" :class="sortedClass('pioneer')" @click="sortMember('pioneer')">
            <i class="bi bi-arrow-down-up"></i>
          </a>
        </th>
        <th scope="col">봉사 집단
          <a type="button" :class="sortedClass('g_name')" @click="sortMember('g_name')">
            <i class="bi bi-arrow-down-up"></i>
          </a>
        </th>
        <th scope="col">직책
          <a type="button" :class="sortedClass('position')" @click="sortMember('position')">
            <i class="bi bi-arrow-down-up"></i>
          </a>
        </th>
        <th scope="col">권한
          <a type="button" :class="sortedClass('auth')" @click="sortMember('auth')">
            <i class="bi bi-arrow-down-up"></i>
          </a>
        </th>
        <th scope="col">전시대
          <a type="button" :class="sortedClass('mb_display')" @click="sortMember('mb_display')">
            <i class="bi bi-arrow-down-up"></i>
          </a>
        </th>
        <th scope="col">주소
          <a type="button" :class="sortedClass('mb_address')" @click="sortMember('mb_address')">
            <i class="bi bi-arrow-down-up"></i>
          </a>
        </th>
        <th scope="col">전입 날짜</th>
        <th scope="col">전출 날짜</th>
      </tr>
    </thead>
    <tbody class="text-center">
      <template v-if="members.length&&members.length>0">
        <tr v-for="(value, index) in members">
          <td class="align-middle text-center">{{index+1}}</td>
          <td class="align-middle text-center fixed">
            <template v-if="member_auth == 1">
              <a :href="`<?=BASE_PATH?>/pages/admin_member_form.php?mb_id=${value.id}`" class="sub_move">{{value.mb_name}}</a>
            </template>
            <template v-else>
              {{value.mb_name}}
            </template>
          </td>
          <td class="align-middle text-center">{{value.mb_sex}}</td>
          <td class="align-middle text-center"><a :href="`tel:${value.mb_hp}`">{{value.mb_hp}}</a></td>
          <td class="align-middle text-center">{{value.mb_pioneer}}</td>
          <td class="align-middle text-center">{{value.g_name}}</td>
          <td class="align-middle text-center">{{value.mb_position}}</td>
          <td class="align-middle text-center">{{value.mb_auth}}</td>
          <td class="align-middle text-center">{{value.mb_display}}</td>
          <td class="align-middle text-center">{{value.mb_address}}</td>
          <td class="align-middle text-center">{{value.mb_movein_date}}</td>
          <td class="align-middle text-center">{{value.mb_moveout_date}}</td>
        </tr>
      </template>
      <template v-else>
        <tr>
          <td :colspan="12" class="text-center align-middle">생성된 전도인이 없습니다</td>
        </tr>
      </template>
    </tbody>
  </table>
</div>

<script  language="javascript" type="text/javascript">
  var v_admin_member = new Vue({
    el: '#v_admin_member',
    data: {
      name:'',
      moveout:[],
      member_auth: <?=get_member_auth(mb_id())?>,
      sortBy:'',
      sortedbyASC: true,
      p_id:'',
      h_id:'',
      members:[]
    },
    methods: {
      searchMember(){
          $.post(BASE_PATH+'/v_data/admin_member.php', { name: this.name, moveout: this.moveout }, function(data) {
            try { 
              var members = JSON.parse(data);
              if(members){
                v_admin_member.members = members;
              }else{
                v_admin_member.members = [];
              }
            } catch (e) {}
          });
      },
      sortedClass (key) {
        return this.sortBy === key ? 'text-primary':'text-secondary';
      },
      sortMember(sortBy) {
        if (this.sortedbyASC) {
          this.members.sort((x, y) => (x['mb_name'] < y['mb_name'] ? -1 : 1));
          this.members.sort((x, y) => (x[sortBy] > y[sortBy] ? -1 : 1));
          this.sortedbyASC = false;
        } else {
          this.members.sort((x, y) => (x['mb_name'] < y['mb_name'] ? -1 : 1));
          this.members.sort((x, y) => (x[sortBy] < y[sortBy] ? -1 : 1));
          this.sortedbyASC = true;
        }
        this.sortBy = sortBy;
      }
    }
});

v_admin_member.searchMember();
</script>
