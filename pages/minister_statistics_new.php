<?php include_once('../header.php');?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">봉사자 <span class="d-xl-none">나의 통계</span></h1>
  <?php echo header_menu('minister','나의 통계'); ?>
</header>

<?php echo footer_menu('봉사자'); ?>

<style>
/* 커스텀 스타일 */
.year-select {
  border: none;
  background: transparent;
  color: #6390d8;
  font-weight: bold;
  font-size: 1.2rem;
  padding: 0.5rem;
  cursor: pointer;
}
.year-select:focus {
  outline: none;
}
.summary-card {
  background: #6390d8;
  color: white;
}
.month-card {
  transition: all 0.2s;
}
.month-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.month-card.has-hours {
  border-color: #6390d8;
  background-color: #f8f9fa;
}
.month-hours {
  font-size: 1.5rem;
  font-weight: bold;
  color: #6390d8;
}
.month-label {
  font-size: 0.9rem;
  color: #6c757d;
}
.period-info {
  font-size: 0.9rem;
  color: #6c757d;
  margin-bottom: 1rem;
}
.attendance-rate {
  font-size: 1.2rem;
  font-weight: bold;
  color: #28a745;
}
.visit-period {
  font-size: 1.2rem;
  font-weight: bold;
  color: #dc3545;
}

@media (max-width: 768px) {
  .month-hours {
    font-size: 1.2rem;
  }
  .month-label {
    font-size: 0.8rem;
  }
  .container-fluid {
    padding: 0.5rem;
  }
}
</style>

<div id="app" class="container-fluid py-3">
  <!-- 1. 봉사년도 선택 및 검색 기간 표시 -->
  <div class="row mb-3">
    <div class="col-12 d-flex align-items-center">
      <select class="year-select mr-2" v-model="selectedYear" @change="updateStatistics">
        <option v-for="year in years" :value="year">{{year}}년 봉사 통계</option>
      </select>
      <span class="ml-2 text-muted">검색 기간: {{selectedYear}}년 9월 ~ {{parseInt(selectedYear) + 1}}년 8월</span>
    </div>
  </div>

  <!-- 2. 월별 봉사 시간 -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">월별 봉사 시간</h5>
          <div class="row g-2">
            <div v-for="(hours, index) in monthlyHours" :key="index" class="col-4 col-md-2">
              <div class="card month-card h-100" :class="{'has-hours': hours > 0}">
                <div class="card-body text-center p-2">
                  <div class="month-hours">{{hours}}</div>
                  <div class="month-label">{{months[index]}}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. 월별 모임 참석률 (ms_type별 선택) -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <h5 class="card-title mb-0 mr-3">월별 모임 참석률</h5>
            <select class="year-select" v-model="selectedType" @change="updateStatistics">
              <option value="all">전체 모임</option>
              <option v-for="type in msTypeOptions" :value="type.value">{{type.label}}</option>
            </select>
          </div>
          <div class="table-responsive">
            <table class="table table-sm text-center">
              <thead>
                <tr>
                  <th>월</th>
                  <th>전체 모임 수</th>
                  <th>참석 횟수</th>
                  <th>참석률(%)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(month, idx) in months" :key="month">
                  <td>{{month}}</td>
                  <td>{{monthlyMeetingStats[idx].total}}</td>
                  <td>{{monthlyMeetingStats[idx].attended}}</td>
                  <td>{{monthlyMeetingStats[idx].total > 0 ? Math.round(monthlyMeetingStats[idx].attended / monthlyMeetingStats[idx].total * 100) : 0}}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. 월별 재방문 세대 수 -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">월별 재방문 세대 수</h5>
          <div class="table-responsive">
            <table class="table table-sm text-center">
              <thead>
                <tr>
                  <th v-for="month in months" :key="month">{{month}}</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td v-for="(count, idx) in monthlyReturnVisitHouseholds" :key="idx">{{count}}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 5. 월별 세대당 재방문 평균 간격 -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">월별 세대당 재방문 평균 간격(일)</h5>
          <div class="table-responsive">
            <table class="table table-sm text-center">
              <thead>
                <tr>
                  <th v-for="month in months" :key="month">{{month}}</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td v-for="(gap, idx) in monthlyReturnVisitAvgGap" :key="idx">{{gap}}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
new Vue({
  el: '#app',
  data: {
    selectedYear: new Date().getFullYear(),
    selectedType: 'all',
    msTypeOptions: [
      { value: '1', label: '호별' },
      { value: '2', label: '전시대' },
      { value: '3', label: '추가1' },
      { value: '4', label: '추가2' },
      { value: '5', label: '추가3' },
      { value: '6', label: '추가4' }
    ],
    years: [],
    months: ['9월', '10월', '11월', '12월', '1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월'],
    monthlyHours: Array(12).fill(0),
    attendanceRates: {},
    monthlyAttendanceRates: {},
    monthlyMeetingStats: [],
    dailyMeetingStats: [],
    activeVisits: 0,
    averageVisitPeriod: 0,
    loading: true,
    allData: null,
    monthlyReturnVisitHouseholds: Array(12).fill(0),
    monthlyReturnVisitAvgGap: Array(12).fill(0)
  },
  mounted() {
    this.initializeYears();
    this.loadAllData();
  },
  methods: {
    initializeYears() {
      const currentYear = new Date().getFullYear();
      for (let year = currentYear; year >= 2018; year--) {
        this.years.push(year);
      }
    },
    async loadAllData() {
      this.loading = true;
      try {
        const response = await axios.get(BASE_PATH+'/v_data/minister_statistics.php');
        console.log('API Response:', response.data);
        this.allData = response.data;
        this.updateStatistics();
      } catch (error) {
        console.error('데이터 로딩 중 오류 발생:', error);
      } finally {
        this.loading = false;
      }
    },
    updateStatistics() {
      if (!this.allData) return;
      const startDate = `${this.selectedYear}-09-01`;
      const endDate = `${parseInt(this.selectedYear) + 1}-08-31`;
      this.calculateMonthlyHours(startDate, endDate);
      this.calculateMeetingStats(startDate, endDate, this.selectedType);
      this.calculateReturnVisitStats(startDate, endDate);
    },
    calculateMonthlyHours(startDate, endDate) {
      this.monthlyHours = Array(12).fill(0);
      if (this.allData.reports && this.allData.reports.length > 0) {
        this.allData.reports.forEach(report => {
          const reportDate = new Date(report.mr_date);
          const year = reportDate.getFullYear();
          const month = reportDate.getMonth();
          if (year === parseInt(this.selectedYear) || (year === parseInt(this.selectedYear) + 1 && month < 8)) {
            const adjustedMonth = (month + 3) % 12;
            const hours = parseInt(report.mr_hour) || 0;
            this.monthlyHours[adjustedMonth] += hours;
          }
        });
      }
    },
    calculateMeetingStats(startDate, endDate, msType) {
      // 월별/요일별 전체 모임 수와 참석 횟수 (ms_type 필터 적용)
      const months = this.months;
      const days = ['일', '월', '화', '수', '목', '금', '토'];
      this.monthlyMeetingStats = Array(12).fill().map(() => ({ total: 0, attended: 0 }));
      this.dailyMeetingStats = days.map(() => ({ total: 0, attended: 0 }));
      if (this.allData.meetings && this.allData.meetings.length > 0) {
        this.allData.meetings.forEach(meeting => {
          const meetingDate = new Date(meeting.m_date);
          const year = meetingDate.getFullYear();
          const month = meetingDate.getMonth();
          const dayIdx = meetingDate.getDay();
          // ms_type 필터 (전체면 통과, 아니면 해당 타입만)
          if (msType !== 'all' && meeting.ms_type != msType) return;
          if (year === parseInt(this.selectedYear) || (year === parseInt(this.selectedYear) + 1 && month < 8)) {
            const adjustedMonth = (month + 3) % 12;
            this.monthlyMeetingStats[adjustedMonth].total++;
            this.dailyMeetingStats[dayIdx].total++;
            if (meeting.m_attended === '1') {
              this.monthlyMeetingStats[adjustedMonth].attended++;
              this.dailyMeetingStats[dayIdx].attended++;
            }
          }
        });
      }
    },
    calculateReturnVisitStats(startDate, endDate) {
      this.activeVisits = 0;
      const currentDate = new Date();
      this.monthlyReturnVisitHouseholds = Array(12).fill(0);
      this.monthlyReturnVisitAvgGap = Array(12).fill(0);
      if (this.allData.return_visits && this.allData.return_visits.length > 0) {
        let totalPeriod = 0;
        let activeCount = 0;
        let monthHouseSet = Array(12).fill().map(() => new Set());
        let monthHouseVisits = Array(12).fill().map(() => ({})); // {houseKey: [date1, date2, ...]}
        this.allData.return_visits.forEach(visit => {
          const startDateObj = new Date(visit.rv_date);
          const year = startDateObj.getFullYear();
          const month = startDateObj.getMonth();
          if (year === parseInt(this.selectedYear) || (year === parseInt(this.selectedYear) + 1 && month < 8)) {
            const adjustedMonth = (month + 3) % 12;
            const houseKey = visit.house_id || visit.rv_house_id || visit.rv_id;
            monthHouseSet[adjustedMonth].add(houseKey);
            // 세대별 방문 날짜 저장
            if (!monthHouseVisits[adjustedMonth][houseKey]) {
              monthHouseVisits[adjustedMonth][houseKey] = [];
            }
            monthHouseVisits[adjustedMonth][houseKey].push(startDateObj);
          }
          // 현재 재방문 중인지 확인
          const endDateObj = visit.rv_end_date ? new Date(visit.rv_end_date) : currentDate;
          if (!visit.rv_end_date || endDateObj >= currentDate) {
            this.activeVisits++;
          }
          // 평균 기간 계산
          const period = Math.ceil((endDateObj - startDateObj) / (1000 * 60 * 60 * 24));
          totalPeriod += period;
          activeCount++;
        });
        // 월별 세대 수 집계
        this.monthlyReturnVisitHouseholds = monthHouseSet.map(set => set.size);
        // 월별 세대당 평균 간격 계산
        this.monthlyReturnVisitAvgGap = monthHouseVisits.map(houseVisits => {
          let allGaps = [];
          Object.values(houseVisits).forEach(dateArr => {
            if (dateArr.length < 2) return;
            // 날짜 정렬
            dateArr.sort((a, b) => a - b);
            for (let i = 1; i < dateArr.length; i++) {
              allGaps.push((dateArr[i] - dateArr[i - 1]) / (1000 * 60 * 60 * 24));
            }
          });
          if (allGaps.length === 0) return '-';
          const avg = allGaps.reduce((a, b) => a + b, 0) / allGaps.length;
          return Math.round(avg * 10) / 10;
        });
      }
    }
  }
});
</script>

<?php include_once('../footer.php');?>
