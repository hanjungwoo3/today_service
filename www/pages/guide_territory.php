<?php include_once('../header.php');?>
<?php check_accessible('guide');?>

<?php
$today = date('Y-m-d');
$mb_id = mb_id();
$ms_options = '';

$sql = "SELECT ms_id, ma_id, ms_week, ms_time, mp_name, g_name
        FROM ".MEETING_SCHEDULE_TABLE." ms LEFT JOIN ".MEETING_PLACE_TABLE." mp ON ms.mp_id = mp.mp_id LEFT JOIN ".GROUP_TABLE." g ON ms.g_id = g.g_id
        ORDER BY ms_week, ms_time, g_name, mp_name, ms_id ASC";
$result = $mysqli->query($sql);
while($ms = $result->fetch_assoc()){
  $ms_id = $ms['ms_id'];
  $ms_ids = explode(',',get_ms_id_by_guide($mb_id));
  if(in_array($ms_id, $ms_ids)){
    $ms_options .= '<option value="'.$ms['ms_id'].'">('.$ms['ms_id'].') '.get_week_text($ms['ms_week']).' '.get_meeting_data_text($ms['ms_time'], $ms['g_name'], $ms['mp_name']);
    if($ms['ma_id']) $ms_options .= ' [회중일정]';
    $ms_options .= '</option>';
  }
}
?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">인도자 <span class="d-xl-none">구역</span></h1>
  <?php echo header_menu('guide','구역'); ?>
</header>

<?php echo footer_menu('인도자'); ?>
<div id="v_guide_territory">
  <div id="container" class="container-fluid">

    <button type="button" class="btn btn-outline-success mb-4" onclick="statistics_map_view();"><i class="bi bi-geo-alt"></i><span class="align-middle"> 구역 전체 지도</span></button>

    <form id="guide-management-territory-search-form" action="" method="post" class="clearfix mb-4">
      <?php include_once('../include/territory_search_filter.php');?>
    </form>

    <div id="guide-territory-list">

      <div class="mb-2 clearfix">
          <span class="float-left" v-if="territories.length>0">총합 {{Number(total).toLocaleString()}}개</span>
      </div>

      <div class="table-responsive">
        <table class="table mb-0" style="min-width: 900px;">
          <colgroup>
            <col style="width:30px;">
            <col style="width:110px;">
            <template v-if="search_type === 'territory'">
              <col style="width:90px;">
            </template>
            <col>
            <col style="width:110px;">
            <col style="width:70px;">
            <col style="width:100px;">
            <col style="width:70px;">
          </colgroup>
          <thead class="thead-light">
            <tr>
              <th class="text-center">No</th>
              <th class="text-center fixed">구역 번호</th>
              <template v-if="search_type === 'territory'">
                <th class="text-center">구역 형태</th>
              </template>
              <th class="">구역 이름</th>
              <th class="text-center">배정 상태</th>
              <th class="text-center">진행률</th>
              <th class="text-center">봉사 기록</th>
              <th>&nbsp;</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="territories.length>0">
                <tr v-for="(value, index) in territories">
                  <td class="text-center align-middle">{{((page-1)*limit)+index+1}}</td>
                  <td class="text-center align-middle fixed">{{value.num}}</td>
                  <template v-if="search_type === 'territory'">
                    <td class="text-center align-middle">{{value.type}}</td>
                  </template>
                  <td class="align-middle">{{value.name}}</td>
                  <td class="text-center align-middle">{{value.status}}</td>
                  <td class="text-center align-middle">{{value.progress}}%</td>
                  <td class="text-center align-middle">
                    <template v-if="search_type === 'telephone'">
                      <button class="btn btn-outline-info" type="button" @click="telephoneWork('record',value.id,'','','');"><i class="bi bi-list-task"></i> ({{value.record_count}})</button>
                    </template>
                    <template v-else>
                      <button class="btn btn-outline-info" type="button" @click="territoryWork('record',value.id,'','','');"><i class="bi bi-list-task"></i> ({{value.record_count}})</button>
                    </template>
                  </td>
                  <td class="text-center align-middle">
                    <div class="dropdown">
                      <button class="btn btn-outline-secondary" type="button" :id="'ex'+value.id" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="bi bi-three-dots-vertical "></i>
                      </button>
                      <div class="dropdown-menu dropdown-menu-right" :aria-labelledby="'ex'+value.id" >
                        <template v-if="search_type === 'telephone'">
                          <button class="dropdown-item" type="button" @click="openTelephoneView(value.id);">구역 보기</button>
                        </template>
                        <template v-else>
                          <button class="dropdown-item" type="button" @click="openTerritoryView(value.id);">구역 보기</button>
                        </template>
                      </div>
                    </div>
                  </td>
                </tr>
            </template>
            <template v-else>
                <tr>
                  <td colspan="15" class="text-center align-middle">검색결과가 존재하지 않습니다</td>
                </tr>
            </template>
          </tbody>
        </table>
      </div>
      <template v-if="territories.length>0">
            <div class="p-3"></div>
            <v-pagination v-model="page" :length="pagelength" :total-visible="7" prev-icon="bi bi-caret-left-fill" next-icon="bi bi-caret-right-fill" @input="onPageChange"></v-pagination>
          </template>
    </div>
  </div>
</div>

<script  language="javascript" type="text/javascript">
  var v_guide_territory = new Vue({
    vuetify: new Vuetify(),
    el: '#v_guide_territory',
    data: {
        location: 'guide',
        search_type: 'territory',
        admin_territory_sort: '<?=ADMIN_TERRITORY_SORT?>',
        s_type:'전체',
        s_assign:'선택안함',
        s_status:'선택안함',
        s_num:'',
        s_name:'',
        total:0,
        page:1,
        pagelength:1,
        limit : <?=TERRITORY_ITEM_PER_PAGE?TERRITORY_ITEM_PER_PAGE:50?>,
        territories:[],
        search_spinner:false
    },
    methods: {
        sortTerritory(){
          if(this.admin_territory_sort == '1'){
            v_guide_territory.territories = _.sortBy(v_guide_territory.territories, [o => Number(o['num'])], ['asc']);
          }else{
            v_guide_territory.territories = _.sortBy(v_guide_territory.territories, [o => String(o['num'])], ['asc']);
          }
        },
        searchFilter(pageInput = this.page){
            this.search_spinner = true;

            if(this.search_type === 'letter') this.s_type = '편지';

            $.post(BASE_PATH+'/v_data/guide_territory.php', { search_type: this.search_type, s_type: this.s_type, s_assign: this.s_assign, s_status: this.s_status, s_num: this.s_num, s_name: this.s_name, page: pageInput }, function(data) {
              try {
                var territories = JSON.parse(data);
                if(territories){
                  v_guide_territory.territories = territories;
                  v_guide_territory.sortTerritory();
                  v_guide_territory.page = pageInput;
                }else{
                  v_guide_territory.territories = [];
                }
                $.each(v_guide_territory.territories, function (index, value) {
                  v_guide_territory.total = value.total
                  v_guide_territory.pagelength = value.pagelength
                });
              } catch (e) {}
              v_guide_territory.search_spinner = false;
            });
        },
        onPageChange() {
            this.searchFilter();
        },
        territoryWork(work, tt_id, tt_num, update_page, update_wrap_id, type = ''){
          territory_work(work,tt_id,tt_num,update_page,update_wrap_id, type);
        },
        openTerritoryView(tt_id){
          open_territory_view(tt_id,'view');
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

<?php include_once('../footer.php'); ?>
