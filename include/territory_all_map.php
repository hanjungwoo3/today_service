<?php include_once('../config.php'); ?>

<?php
$c_territory_type_use = unserialize(TERRITORY_TYPE_USE);
$type_mapping = array(
  'type_1' => '일반',
  'type_2' => '아파트',
  'type_3' => '빌라',
  'type_4' => '격지',
  'type_7' => '추가1',
  'type_8' => '추가2',
);
$unused_types = array();
$exclude_sql = "";
$source = isset($_GET['source']) ? $_GET['source'] : '';

if ($source !== 'admin') {
  foreach ($type_mapping as $key => $value) {
    if (isset($c_territory_type_use[$key]) && empty($c_territory_type_use[$key])) {
      $unused_types[] = "'" . $value . "'";
    }
  }
}

if (!empty($unused_types)) {
  $exclude_sql = " WHERE tt_type NOT IN (" . implode(',', $unused_types) . ") ";
}

$sql = "SELECT tt_id, tt_num, tt_name, tt_type, tt_polygon FROM " . TERRITORY_TABLE . $exclude_sql . " ORDER BY CASE tt_type WHEN '아파트' THEN 1 WHEN '격지' THEN 2 WHEN '일반' THEN 3 WHEN '빌라' THEN 4 ELSE 5 END";
$result = $mysqli->query($sql);
$container_id = (!empty($modal)) ? 'map-modal' : 'map-territory';
?>

<style media="screen">
  .area {
    position: absolute;
    background: #fff;
    border: 1px solid #888;
    border-radius: 3px;
    font-size: 12px;
    top: -5px;
    left: 15px;
    padding: 2px;
  }
</style>

<div id="<?= $container_id ?>" style="width:100%;height:100%;position:absolute;"></div>
<div class="alert alert-info mb-0 pt-1 pb-1 align-middle"
  style="position: absolute;bottom: 0;z-index: 9999;width: 100%;">
  <span
    style="display: inline-block;width: 20px;vertical-align: middle;height: 20px;margin: 7px;background-image: url(&quot;http://i1.daumcdn.net/localimg/localimages/07/mapjsapi/toolbox.png&quot;);background-position: 0px -90px;"></span>
  다각형으로 표시한 구역만 보여집니다.
</div>

<script type="text/javascript">
  var mapContainer = document.getElementById('<?= $container_id ?>'), // 지도를 표시할 div
    mapOption = {
      center: new daum.maps.LatLng(37.1978051, 126.943861), // 지도의 중심좌표
      level: 7 // 지도의 확대 레벨
    };

  // 지도를 표시할 div와  지도 옵션으로  지도를 생성합니다
  var map = new daum.maps.Map(mapContainer, mapOption);

  // 도형 스타일을 변수로 설정합니다
  var strokeColor = '#39f',
    fillColor = '#ff00c8',
    fillOpacity = 0.2,
    hintStrokeStyle = 'dash';

  var line_strokeColor = 'rgb(255, 51, 119)',
    line_strokeStyle = 'shortdash',
    line_hintStrokeStyle = 'dash';

  // 일반 지도와 스카이뷰로 지도 타입을 전환할 수 있는 지도타입 컨트롤을 생성합니다
  var mapTypeControl = new daum.maps.MapTypeControl();

  // 지도에 컨트롤을 추가해야 지도위에 표시됩니다
  // daum.maps.ControlPosition은 컨트롤이 표시될 위치를 정의하는데 TOPRIGHT는 오른쪽 위를 의미합니다
  map.addControl(mapTypeControl, daum.maps.ControlPosition.TOPLEFT);

  var zoomControl = new daum.maps.ZoomControl();
  map.addControl(zoomControl, daum.maps.ControlPosition.LEFT);

  // 주어진 영역이 화면 안에 전부 나타날 수 있도록 지도의 중심 좌표와 확대 수준을 설정한다.
  var bounds = new daum.maps.LatLngBounds();

  var customOverlay = new daum.maps.CustomOverlay({});

  var areas = [];

  var polygons = [];

  <?php if (!empty(TERRITORY_BOUNDARY)): ?>
    var data = JSON.parse('<?= TERRITORY_BOUNDARY ?>');
    if (typeof data == 'object' && typeof data['polygon'][0] !== 'undefined') {
      for (i = 0; i < data[daum.maps.drawing.OverlayType.POLYGON].length; i++) {
        var path = pointsToPath(data[daum.maps.drawing.OverlayType.POLYGON][i].points);

        // 다각형을 생성합니다
        var polygon = new daum.maps.Polygon({
          map: map, // 다각형을 표시할 지도 객체
          path: path,
          strokeWeight: 2,
          strokeColor: '#616161',
          strokeOpacity: 1,
          fillColor: '#00ffe6',
          fillOpacity: 0.2,
          strokeStyle: 'shortdashdot'
        });
        for (a = 0; a < path.length; a++) {
          bounds.extend(path[a]);
        }
      }

      map.setBounds(bounds);
    } else {
      <?php if (!empty(DEFAULT_LOCATION)): ?>
        // 주소-좌표 변환 객체를 생성합니다
        var geocoder = new daum.maps.services.Geocoder();

        // 주소로 좌표를 검색합니다
        geocoder.addressSearch('<?= DEFAULT_LOCATION ?>', function (result, status) {

          // 정상적으로 검색이 완료됐으면
          if (status === daum.maps.services.Status.OK) {
            var coords = new daum.maps.LatLng(result[0].y, result[0].x); // 지도의 중심좌표
            map.setCenter(coords);
          }
        });
      <?php endif; ?>
    }

  <?php else: ?>

    <?php if (!empty(DEFAULT_LOCATION)): ?>
      // 주소-좌표 변환 객체를 생성합니다
      var geocoder = new daum.maps.services.Geocoder();

      // 주소로 좌표를 검색합니다
      geocoder.addressSearch('<?= DEFAULT_LOCATION ?>', function (result, status) {

        // 정상적으로 검색이 완료됐으면
        if (status === daum.maps.services.Status.OK) {
          var coords = new daum.maps.LatLng(result[0].y, result[0].x); // 지도의 중심좌표
          map.setCenter(coords);
        }
      });
    <?php endif; ?>

  <?php endif; ?>

  <?php while ($row = $result->fetch_assoc()):
    if (empty($row['tt_polygon'])) {
      continue;
    }
    $tt_polygon = $row['tt_polygon']; ?>

    <?php //세대수
      $sql = "SELECT COUNT(h_id) as cnt FROM " . HOUSE_TABLE . " WHERE tt_id = {$row['tt_id']}";
      $h_result = $mysqli->query($sql);
      $h = $h_result->fetch_assoc();
      ?>

    <?php if (json_last_error() == JSON_ERROR_NONE): ?>
      var data = JSON.parse('<?= $tt_polygon ?>');
      if (typeof data == 'object' && (typeof data['marker'][0] !== 'undefined' || typeof data['arrow'][0] !== 'undefined' || typeof data['polyline'][0] !== 'undefined' || typeof data['rectangle'][0] !== 'undefined' || typeof data['circle'][0] !== 'undefined' || typeof data['polygon'][0] !== 'undefined')) {

        var area = {
          tt_id: '<?= $row['tt_id'] ?>',
          tt_num: '<?= $row['tt_num'] ?>',
          tt_name: '<?= $row['tt_name'] ?>',
          tt_type: '<?= $row['tt_type'] ?>',
          tt_type_text: '<?= get_type_text($row['tt_type']) ?>',
          count: '<?= $h['cnt'] ?>',
          path: []
        };
        for (i = 0; i < data[daum.maps.drawing.OverlayType.POLYGON].length; i++) {
          var path = pointsToPath(data[daum.maps.drawing.OverlayType.POLYGON][i].points);
          area.path.push(path);
        }

        areas.push(area);

      }

    <?php endif; ?>
  <?php endwhile; ?>

  var customOverlay = new daum.maps.CustomOverlay({}),
    infowindow = new daum.maps.InfoWindow({ removable: true });

  for (var i = 0, len = areas.length; i < len; i++) {
    displayArea(areas[i]);
  }

  // 현재 지도의 레벨을 얻어옵니다
  // var level = map.getLevel();
  // 지도를 1레벨 올립니다 (지도가 축소됩니다)
  // map.setLevel(level + 1);

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

  // 다각형을 생상하고 이벤트를 등록하는 함수입니다
  function displayArea(area) {

    if (area.tt_type == '아파트') {
      strokeColor = '#ff0000';
      // fillColor = '#ffbfbf';
    } else if (area.tt_type == '일반') {
      strokeColor = '#008cff';
      // fillColor = '#bedfef';
    } else if (area.tt_type == '빌라') {
      strokeColor = '#ed00ff';
      // fillColor = '#e9beef';
    } else if (area.tt_type == '격지') {
      strokeColor = '#ffdd00';
      // fillColor = '#edefbe';
    }

    // // 다각형을 생성합니다
    var polygon = new daum.maps.Polygon({
      map: map, // 다각형을 표시할 지도 객체
      path: area.path,
      strokeWeight: 2,
      strokeColor: strokeColor,
      strokeOpacity: 1,
      fillColor: fillColor,
      fillOpacity: fillOpacity
    });

    polygons.push(polygon);

    // 다각형에 click 이벤트를 등록하고 이벤트가 발생하면 다각형의 이름과 면적을 인포윈도우에 표시합니다
    daum.maps.event.addListener(polygon, 'click', function (mouseEvent) {

      for (var i = 0, len = polygons.length; i < len; i++) {
        // var p_fillColor = polygons[i].Cb[0].fillColor;
        // var p_strokeColor = polygons[i].Cb[0].strokeColor;
        // console.log(polygons[i]);
        polygons[i].setOptions({ fillColor: '#ff00c8' });
      }

      polygon.setOptions({ fillColor: '#ff8200' });

      customOverlay.setContent('<div class="area">구역 번호: ' + area.tt_num + '<br>구역 이름: ' + area.tt_name + '<br>구역 형태: ' + area.tt_type_text + '<br>세대수: ' + area.count + '</div>');

      customOverlay.setPosition(mouseEvent.latLng);
      customOverlay.setMap(map);
    });
  }
</script>