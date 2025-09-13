<?php include_once('../header.php'); ?>
<?php check_accessible('admin'); ?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0  navbar-brand">관리자 <span class="d-xl-none">통계</span></h1>
  <?php echo header_menu('admin','통계'); ?>
</header>

<?php echo footer_menu('관리자'); ?>

<div id="v_admin_statistics" class="container-fluid py-4">
  <div v-if="loading">로딩 중...</div>
  <div v-else>
    <!-- 검색 조건 설정 -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">검색 조건 설정</h5>
        <div>
          <button class="btn btn-sm btn-outline-primary me-2" @click="addSearchCondition">
            <i class="fas fa-plus"></i> 조건 추가
          </button>
          <button class="btn btn-sm btn-outline-secondary" @click="resetSearch">
            <i class="fas fa-undo"></i> 초기화
          </button>
        </div>
      </div>
      <div class="card-body">
        <!-- 동적 검색 조건 -->
        <div v-for="(condition, index) in search.conditions" :key="index" class="row mb-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">데이터 테이블</label>
            <select class="form-select" v-model="condition.table" @change="updateFields(condition)">
              <option value="">선택하세요</option>
              <option v-for="table in availableTables" :key="table.value" :value="table.value">
                {{ table.label }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">검색 필드</label>
            <select class="form-select" v-model="condition.field">
              <option value="">선택하세요</option>
              <option v-for="field in getAvailableFields(condition.table)" :key="field.value" :value="field.value">
                {{ field.label }}
              </option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">검색 조건</label>
            <select class="form-select" v-model="condition.operator">
              <option value="equals">일치</option>
              <option value="contains">포함</option>
              <option value="startsWith">시작</option>
              <option value="endsWith">끝남</option>
              <option value="greaterThan">초과</option>
              <option value="lessThan">미만</option>
              <option value="between">사이</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">검색어</label>
            <div class="input-group">
              <input type="text" class="form-control" v-model="condition.value" 
                     :placeholder="condition.operator === 'between' ? '값1,값2' : '검색어'">
            </div>
          </div>
          <div class="col-md-1">
            <button class="btn btn-outline-danger w-100" @click="removeSearchCondition(index)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>

        <!-- 컬럼 선택 -->
        <div class="row mt-4">
          <div class="col-12">
            <label class="form-label">표시할 컬럼</label>
            <div class="d-flex flex-wrap gap-2">
              <div class="form-check" v-for="col in availableColumns" :key="col.value">
                <input class="form-check-input" type="checkbox" :value="col.value" v-model="selectedColumns" :id="'col-' + col.value">
                <label class="form-check-label" :for="'col-' + col.value">
                  {{ col.label }}
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- 검색 버튼 -->
        <div class="row mt-4">
          <div class="col-12">
            <button class="btn btn-primary" @click="searchData">검색</button>
            <button class="btn btn-success ms-2" @click="downloadExcel" :disabled="!filteredData.length">엑셀 다운로드</button>
          </div>
        </div>
      </div>
    </div>

    <!-- 전도인별 봉사횟수 -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">전도인별 봉사횟수</h5>
        <button class="btn btn-success btn-sm" @click="downloadMemberCountExcel" :disabled="!memberCounts.length">엑셀 다운로드</button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>이름</th>
                <th>닉네임</th>
                <th>연락처</th>
                <th>봉사횟수</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in memberCounts" :key="row.mb_id">
                <td>{{ row.mb_name }}</td>
                <td>{{ row.mb_nick }}</td>
                <td>{{ row.mb_hp }}</td>
                <td>{{ row.count }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 결과 테이블 -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">검색 결과</h5>
        <span class="text-muted">총 {{ filteredData.length }}건</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th v-for="col in selectedColumns" :key="col">
                  {{ getColumnLabel(col) }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in filteredData" :key="index">
                <td v-for="col in selectedColumns" :key="col">
                  {{ formatValue(item[col]) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
var v_admin_statistics = new Vue({
  el: '#v_admin_statistics',
  data: {
    loading: true,
    allData: {},
    search: {
      conditions: []
    },
    availableTables: [
      { value: 'members', label: '전도인(회원)' },
      { value: 'meetings', label: '봉사 모임' },
      { value: 'schedules', label: '모임 스케줄' },
      { value: 'territories', label: '구역' },
      { value: 'telephones', label: '전화 구역' },
      { value: 'displays', label: '전시대' },
      { value: 'territory_records', label: '호별구역' },
      { value: 'telephone_records', label: '전화구역' },
      { value: 'houses', label: '세대' },
      { value: 'house_memos', label: '세대 메모' },
      { value: 'return_visits', label: '재방문 기록' },
      { value: 'telephone_houses', label: '전화구역 세대' },
      { value: 'telephone_house_memos', label: '전화구역 세대 메모' },
      { value: 'telephone_return_visits', label: '전화구역 재방문 기록' }
    ],
    tableFields: {
      members: [
        { value: 'mb_id', label: '회원ID' },
        { value: 'mb_name', label: '이름' },
        { value: 'mb_nick', label: '닉네임' },
        { value: 'mb_email', label: '이메일' },
        { value: 'mb_hp', label: '휴대폰' },
        { value: 'mb_level', label: '레벨' }
      ],
      meetings: [
        { value: 'm_id', label: '모임ID' },
        { value: 'ms_id', label: '스케줄ID' },
        { value: 'm_date', label: '날짜' },
        { value: 'mb_id', label: '참석자' },
        { value: 'attend_count', label: '참석자수' }
      ],
      schedules: [
        { value: 'ms_id', label: '스케줄ID' },
        { value: 'ms_title', label: '제목' },
        { value: 'ms_time', label: '시간' },
        { value: 'ms_week', label: '요일' },
        { value: 'ms_type', label: '모임형태' },
        { value: 'ms_place', label: '장소' },
        { value: 'ms_memo', label: '메모' }
      ],
      territories: [
        { value: 't_id', label: '구역ID' },
        { value: 't_name', label: '구역명' },
        { value: 't_memo', label: '메모' }
      ],
      telephones: [
        { value: 'tel_id', label: '전화구역ID' },
        { value: 'tel_name', label: '전화구역명' },
        { value: 'tel_memo', label: '메모' }
      ],
      displays: [
        { value: 'd_id', label: '공개증거구역ID' },
        { value: 'd_name', label: '공개증거구역명' },
        { value: 'd_memo', label: '메모' }
      ],
      territory_records: [
        { value: 'tr_id', label: '기록ID' },
        { value: 't_id', label: '구역ID' },
        { value: 'h_id', label: '세대ID' },
        { value: 'tr_date', label: '날짜' },
        { value: 'tr_memo', label: '메모' }
      ],
      telephone_records: [
        { value: 'telr_id', label: '기록ID' },
        { value: 'tel_id', label: '전화구역ID' },
        { value: 'telh_id', label: '전화세대ID' },
        { value: 'telr_date', label: '날짜' },
        { value: 'telr_memo', label: '메모' }
      ],
      houses: [
        { value: 'h_id', label: '세대ID' },
        { value: 'h_name', label: '세대명' },
        { value: 'h_address', label: '주소' },
        { value: 'h_phone', label: '전화번호' },
        { value: 'h_memo', label: '메모' }
      ],
      house_memos: [
        { value: 'hm_id', label: '메모ID' },
        { value: 'h_id', label: '세대ID' },
        { value: 'hm_date', label: '날짜' },
        { value: 'hm_memo', label: '메모' }
      ],
      return_visits: [
        { value: 'rv_id', label: '재방문ID' },
        { value: 'h_id', label: '세대ID' },
        { value: 'rv_date', label: '날짜' },
        { value: 'rv_memo', label: '메모' }
      ],
      telephone_houses: [
        { value: 'telh_id', label: '전화세대ID' },
        { value: 'telh_name', label: '세대명' },
        { value: 'telh_phone', label: '전화번호' },
        { value: 'telh_memo', label: '메모' }
      ],
      telephone_house_memos: [
        { value: 'telhm_id', label: '메모ID' },
        { value: 'telh_id', label: '전화세대ID' },
        { value: 'telhm_date', label: '날짜' },
        { value: 'telhm_memo', label: '메모' }
      ],
      telephone_return_visits: [
        { value: 'telrv_id', label: '재방문ID' },
        { value: 'telh_id', label: '전화세대ID' },
        { value: 'telrv_date', label: '날짜' },
        { value: 'telrv_memo', label: '메모' }
      ]
    },
    selectedColumns: [],
    filteredData: [],
    memberCounts: []
  },
  computed: {
    availableColumns() {
      const columns = [];
      this.search.conditions.forEach(condition => {
        if (condition.table) {
          const fields = this.getAvailableFields(condition.table);
          fields.forEach(field => {
            if (!columns.find(c => c.value === field.value)) {
              columns.push(field);
            }
          });
        }
      });
      return columns;
    },
    memberCounts() {
      if (!this.allData.members || !this.allData.meetings) return [];
      // mb_id가 meetings.mb_id(쉼표구분) 안에 포함된 횟수 카운트
      return this.allData.members.map(m => {
        let count = 0;
        this.allData.meetings.forEach(meeting => {
          if (meeting.mb_id && meeting.mb_id.split(',').includes(m.mb_id)) count++;
        });
        return {
          mb_id: m.mb_id,
          mb_name: m.mb_name,
          mb_nick: m.mb_nick,
          mb_hp: m.mb_hp,
          count
        };
      }).sort((a, b) => b.count - a.count); // 봉사횟수 내림차순
    }
  },
  created() {
    this.fetchAllData();
  },
  methods: {
    fetchAllData() {
      fetch(BASE_PATH+'/v_data/admin_statistics.php')
        .then(res => res.json())
        .then(data => {
          this.allData = data;
          this.loading = false;
        })
        .catch(() => {
          alert('데이터를 불러오지 못했습니다.');
          this.loading = false;
        });
    },
    addSearchCondition() {
      this.search.conditions.push({
        table: '',
        field: '',
        operator: 'equals',
        value: ''
      });
    },
    removeSearchCondition(index) {
      this.search.conditions.splice(index, 1);
    },
    resetSearch() {
      this.search.conditions = [];
      this.selectedColumns = [];
      this.filteredData = [];
    },
    updateFields(condition) {
      condition.field = '';
      this.selectedColumns = [];
    },
    getAvailableFields(table) {
      return this.tableFields[table] || [];
    },
    searchData() {
      if (!this.allData) return;
      
      let results = [];
      const conditions = this.search.conditions.filter(c => c.table && c.field && c.value);
      
      if (conditions.length === 0) {
        this.filteredData = [];
        return;
      }

      // 첫 번째 조건으로 시작
      const firstCondition = conditions[0];
      const firstTable = this.allData[firstCondition.table] || [];
      
      results = firstTable.filter(item => this.matchesCondition(item, firstCondition));
      
      // 나머지 조건들 적용
      for (let i = 1; i < conditions.length; i++) {
        const condition = conditions[i];
        const table = this.allData[condition.table] || [];
        
        results = results.filter(item => {
          const relatedItems = table.filter(t => this.matchesCondition(t, condition));
          return relatedItems.length > 0;
        });
      }

      this.filteredData = results;
    },
    matchesCondition(item, condition) {
      const value = item[condition.field];
      if (value === undefined) return false;
      
      const searchValue = condition.value.toString().toLowerCase();
      const itemValue = value.toString().toLowerCase();
      
      switch (condition.operator) {
        case 'equals':
          return itemValue === searchValue;
        case 'contains':
          return itemValue.includes(searchValue);
        case 'startsWith':
          return itemValue.startsWith(searchValue);
        case 'endsWith':
          return itemValue.endsWith(searchValue);
        case 'greaterThan':
          return Number(itemValue) > Number(searchValue);
        case 'lessThan':
          return Number(itemValue) < Number(searchValue);
        case 'between':
          const [min, max] = searchValue.split(',').map(Number);
          return Number(itemValue) >= min && Number(itemValue) <= max;
        default:
          return true;
      }
    },
    formatValue(value) {
      if (value === null || value === undefined) return '';
      if (typeof value === 'object') return JSON.stringify(value);
      return value;
    },
    getColumnLabel(value) {
      for (const table of Object.values(this.tableFields)) {
        const field = table.find(f => f.value === value);
        if (field) return field.label;
      }
      return value;
    },
    downloadExcel() {
      const data = this.filteredData.map(item => {
        const row = {};
        this.selectedColumns.forEach(col => {
          row[this.getColumnLabel(col)] = this.formatValue(item[col]);
        });
        return row;
      });

      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.json_to_sheet(data);

      const colWidths = this.selectedColumns.map(col => ({
        wch: Math.max(this.getColumnLabel(col).length, 10)
      }));
      ws['!cols'] = colWidths;

      XLSX.utils.book_append_sheet(wb, ws, "검색 결과");
      XLSX.writeFile(wb, "검색결과_" + new Date().toISOString().split('T')[0] + ".xlsx");
    },
    downloadMemberCountExcel() {
      const data = this.memberCounts.map(row => ({
        '이름': row.mb_name,
        '닉네임': row.mb_nick,
        '연락처': row.mb_hp,
        '봉사횟수': row.count
      }));
      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.json_to_sheet(data);
      ws['!cols'] = [
        { wch: 10 }, { wch: 10 }, { wch: 15 }, { wch: 10 }
      ];
      XLSX.utils.book_append_sheet(wb, ws, '전도인별 봉사횟수');
      XLSX.writeFile(wb, '전도인별_봉사횟수_' + new Date().toISOString().split('T')[0] + '.xlsx');
    }
  }
});
</script>

<?php include_once('../footer.php');?>