<?php
include_once('../header.php');
?>

<header class="navbar navbar-expand-xl fixed-top header">
  <h1 class="text-white mb-0 navbar-brand">건물 정보</h1>
  <?php echo header_menu('admin','건물정보'); ?>
</header>

<?php echo footer_menu('관리자');?>

<div id="container" class="container-fluid">
  <div class="row">
    <!-- 지도 영역 -->
    <div class="col-12 col-md-8">
      <div id="map" style="height:600px;"></div>
    </div>
    
    <!-- 건물 정보 리스트 영역 -->
    <div class="col-12 col-md-4">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">건물 정보</h5>
        </div>
        <div class="card-body">
          <div id="building-list" style="height:600px; overflow-y:auto;">
            <div class="text-center text-muted">
              지도에서 영역을 선택해주세요
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript" src="//sgisapi.kostat.go.kr/openapi/3.0/app/js/require.js"></script>
<script>
require.config({
  paths: {
    'jquery': '//code.jquery.com/jquery-3.6.0.min',
    'sgis': '//sgisapi.kostat.go.kr/openapi/3.0/app/js/sgis'
  }
});

require(['jquery', 'sgis'], function($, sgis) {
  // SGIS API 초기화
  var map = new sgis.Map({
    container: 'map',
    center: [126.978, 37.5665], // 서울시청
    zoom: 15
  });

  // 영역 선택 도구 추가
  var drawingTool = new sgis.DrawingTool({
    map: map,
    drawingMode: 'polygon',
    style: {
      fillColor: '#3388ff',
      fillOpacity: 0.2,
      strokeColor: '#3388ff',
      strokeWidth: 2
    }
  });

  // 영역 선택 완료 시 이벤트
  drawingTool.on('drawend', function(e) {
    var polygon = e.feature;
    
    // 선택된 영역의 경계 좌표 가져오기
    var coordinates = polygon.getGeometry().getCoordinates()[0];
    
    // 건물 정보 요청
    $.ajax({
      url: '//sgisapi.kostat.go.kr/openapi/3.0/app/rest/GetBuildingInfo',
      method: 'GET',
      data: {
        accessToken: 'YOUR_ACCESS_TOKEN', // 실제 토큰으로 교체 필요
        coordinates: JSON.stringify(coordinates)
      },
      success: function(response) {
        displayBuildingList(response);
      },
      error: function(error) {
        console.error('건물 정보 요청 실패:', error);
        $('#building-list').html('<div class="alert alert-danger">건물 정보를 가져오는데 실패했습니다.</div>');
      }
    });
  });

  // 건물 정보 표시 함수
  function displayBuildingList(buildings) {
    var html = '';
    if (buildings && buildings.length > 0) {
      buildings.forEach(function(building) {
        html += `
          <div class="card mb-2">
            <div class="card-body">
              <h6 class="card-title">${building.buildingName || '이름 없음'}</h6>
              <p class="card-text">
                <small class="text-muted">
                  주소: ${building.address}<br>
                  건물용도: ${building.buildingUse}<br>
                  층수: ${building.floorCount}층
                </small>
              </p>
            </div>
          </div>
        `;
      });
    } else {
      html = '<div class="text-center text-muted">선택한 영역에 건물이 없습니다.</div>';
    }
    $('#building-list').html(html);
  }

  // 도구 활성화
  drawingTool.activate();
});
</script>

<?php include_once('../footer.php'); ?> 