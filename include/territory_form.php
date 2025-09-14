<?php include_once('../config.php');?>
<?php check_accessible('admin');?>

<?php
$work = 'add';
if($tt_id){
  $work = 'edit';
  $sql = "SELECT * FROM ".TERRITORY_TABLE." WHERE tt_id = {$tt_id}";
  $result = $mysqli->query($sql);
  $row = $result->fetch_assoc(); 
}
?>

<style media="screen">
  .modes {position: absolute;top: 10px;left: 10px;z-index: 1;}
  .getdata{position: absolute;top: 370px;left: 10px;z-index: 1;}
</style>

<div class="container-fluid">
  <form id="admin_territory_form" method="POST">
    <input type="hidden" name="work" value="<?=$work?>">
    <input type="hidden" name="tt_id" value="<?=$tt_id?>"> 

    <?php if($type == 'letter'): ?>
      <input type="hidden" name="tt_type" value="편지">
    <?php else: ?>
      <div class="form-group row">
        <label for="tt_type" class="col-4 col-md-2 col-form-label">구역 형태</label>
        <div class="col-8 col-md-10">
          <select class="form-control" id="tt_type" name="tt_type">
            <?php echo get_territory_type_options('edit', $row['tt_type']);?>
          </select>
        </div>
      </div>
    <?php endif; ?>

    <div class="form-group row">
      <label for="tt_num" class="col-4 col-md-2 col-form-label">구역 번호</label>
      <div class="col-8 col-md-10">
        <input type="text" class="form-control" id="tt_num" name="tt_num" value="<?=isset($row['tt_num'])?$row['tt_num']:''?>" required>
      </div>
    </div>

    <div class="form-group row">
      <label for="tt_name" class="col-4 col-md-2 col-form-label">구역 이름</label>
      <div class="col-8 col-md-10">
        <input type="text" class="form-control" id="tt_name" name="tt_name" value="<?=isset($row['tt_num'])?$row['tt_name']:''?>" required>
      </div>
    </div>

    <div class="form-group row">
      <label for="tt_address" class="col-4 col-md-2 col-form-label">대표 주소</label>
      <div class="col-8 col-md-10">
        <input type="text" class="form-control" id="tt_address" name="tt_address" value="<?=isset($row['tt_num'])?$row['tt_address']:''?>">
      </div>
    </div>

    <div class="form-group row">
      <label for="tt_address2" class="col-4 col-md-2 col-form-label">대표 주소 2</label>
      <div class="col-8 col-md-10">
        <input type="text" class="form-control" id="tt_address2" name="tt_address2" value="<?=isset($row['tt_num'])?$row['tt_address2']:''?>">
      </div>
    </div>

    <?php if($type == 'letter'): ?>
      <input type="hidden" name="tt_polygon" value="">
    <?php else: ?>
      <div class="form-group row">
      <label for="tt_polygon" class="col-12 col-md-12 col-form-label">구역 지도</label>
      <div class="col-12 col-md-12">
        <div id="set-map" style="height:400px;display:none;"></div>
        <input type="hidden" name="tt_polygon" id="map_sub_polygon" value="<?=isset($row['tt_polygon'])?htmlspecialchars(stripslashes($row['tt_polygon'])):''?>">
      </div>
    </div>
    <?php endif; ?>

    <div class="form-group row">
    <label for="mb_id" class="col-4 col-md-2 col-form-label">개인 구역</label>
      <div class="col-8 col-md-10">
        <select class="form-control" id="mb_id" name="mb_id">
          <option value="0">선택 안 함</option>
          <?php echo get_member_option($row['mb_id']);?>
        </select>
      </div>
    </div>

    <div class="form-group row">
      <label for="tt_mb_date" class="col-4 col-md-2 col-form-label">개인 구역<br>배정 날짜</label>
      <div class="col-8 col-md-10">
        <input type="date" class="form-control" id="tt_mb_date" name="tt_mb_date" value="<?=isset($row['tt_mb_date'])?$row['tt_mb_date']:'0000-00-00'?>">
      </div>
    </div>

    <div class="mt-2 text-right">
      <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save"></i> 저장</button>
    </div>
  </form>
</div>

<script type="text/javascript">
var mapContainer = document.getElementById('set-map'), // 지도를 표시할 div
  mapOption = {
      center: new daum.maps.LatLng(37.1978051, 126.943861), // 지도의 중심좌표
      level: 4 // 지도의 확대 레벨
  };

// 지도를 표시할 div와  지도 옵션으로  지도를 생성합니다
var map = new daum.maps.Map(mapContainer, mapOption);

// 도형 스타일을 변수로 설정합니다
var strokeColor = '#39f',
  	fillColor = '#cce6ff',
  	fillOpacity = 0.5,
  	hintStrokeStyle = 'dash';

var line_strokeColor = 'rgb(255, 51, 119)',
    line_strokeStyle = 'shortdash',
  	line_hintStrokeStyle = 'dash';

var options = { // Drawing Manager를 생성할 때 사용할 옵션입니다
    map: map, // Drawing Manager로 그리기 요소를 그릴 map 객체입니다
    drawingMode: [
        daum.maps.Drawing.OverlayType.POLYGON,
        daum.maps.Drawing.OverlayType.MARKER,
        daum.maps.Drawing.OverlayType.ARROW,
        daum.maps.Drawing.OverlayType.POLYLINE,
        daum.maps.Drawing.OverlayType.RECTANGLE,
        daum.maps.Drawing.OverlayType.CIRCLE
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
            src: '<?=BASE_PATH?>/img/marker.png',
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
        fillOpacity: fillOpacity
    }
};

// 위에 작성한 옵션으로 Drawing Manager를 생성합니다
var manager = new daum.maps.Drawing.DrawingManager(options);

// 일반 지도와 스카이뷰로 지도 타입을 전환할 수 있는 지도타입 컨트롤을 생성합니다
var mapTypeControl = new daum.maps.MapTypeControl();

// 지도에 컨트롤을 추가해야 지도위에 표시됩니다
// daum.maps.ControlPosition은 컨트롤이 표시될 위치를 정의하는데 TOPRIGHT는 오른쪽 위를 의미합니다
map.addControl(mapTypeControl, daum.maps.ControlPosition.TOPLEFT);

// Toolbox를 생성합니다.
// Toolbox 생성 시 위에서 생성한 DrawingManager 객체를 설정합니다.
// DrawingManager 객체를 꼭 설정해야만 그리기 모드와 매니저의 상태를 툴박스에 설정할 수 있습니다.
var toolbox = new daum.maps.Drawing.Toolbox({drawingManager: manager});

// 지도 위에 Toolbox를 표시합니다
// daum.maps.ControlPosition은 컨트롤이 표시될 위치를 정의하는데 TOP은 위 가운데를 의미합니다.
map.addControl(toolbox.getElement(), daum.maps.ControlPosition.TOP);

<?php if(!empty($row['tt_polygon'])): ?>

  <?php //$result = json_decode($row[tt_polygon]);
  if(json_last_error() == JSON_ERROR_NONE): ?>
    var data = JSON.parse('<?=$row['tt_polygon']?>');
    if(typeof data =='object' && (typeof data['marker'][0] !== 'undefined' || typeof data['arrow'][0] !== 'undefined' || typeof data['polyline'][0] !== 'undefined' || typeof data['rectangle'][0] !== 'undefined' || typeof data['circle'][0] !== 'undefined' || typeof data['polygon'][0] !== 'undefined')){

      drawPolygon(data[daum.maps.drawing.OverlayType.POLYGON]);
      drawRectangle(data[daum.maps.drawing.OverlayType.RECTANGLE]);
      drawCircle(data[daum.maps.drawing.OverlayType.CIRCLE]);
      drawPolyline(data[daum.maps.drawing.OverlayType.POLYLINE]);
      drawArrow(data[daum.maps.drawing.OverlayType.ARROW]);
      drawMarker(data[daum.maps.drawing.OverlayType.MARKER]);

      // Drawing Manager에서 가져온 데이터 중 마커를 아래 지도에 표시하는 함수입니다
      function drawMarker(markers) {
          var len = markers.length, i = 0;

          for (; i < len; i++) {
              var position = new daum.maps.LatLng(markers[i].y, markers[i].x);
              var index = markers[i].zIndex;
              manager.put(daum.maps.drawing.OverlayType.MARKER, position, index);
          }
      }

      // Drawing Manager에서 가져온 데이터 중 화살표를 아래 지도에 표시하는 함수입니다
      function drawArrow(lines) {
          var len = lines.length, i = 0;

          for (; i < len; i++) {
              var path = pointsToPath(lines[i].points);
              manager.put(daum.maps.drawing.OverlayType.ARROW, path);
          }
      }

      // Drawing Manager에서 가져온 데이터 중 선을 아래 지도에 표시하는 함수입니다
      function drawPolyline(lines) {
          var len = lines.length, i = 0;

          for (; i < len; i++) {
              var path = pointsToPath(lines[i].points);
              manager.put(daum.maps.drawing.OverlayType.POLYLINE, path);
          }
      }

      // Drawing Manager에서 가져온 데이터 중 사각형을 아래 지도에 표시하는 함수입니다
      function drawRectangle(rects) {
          var len = rects.length, i = 0;

          for (; i < len; i++) {
              var bounds = new daum.maps.LatLngBounds(
                  new daum.maps.LatLng(rects[i].sPoint.y, rects[i].sPoint.x),
                  new daum.maps.LatLng(rects[i].ePoint.y, rects[i].ePoint.x)
              );
              manager.put(daum.maps.drawing.OverlayType.RECTANGLE, bounds);
          }
      }

      // Drawing Manager에서 가져온 데이터 중 원을 아래 지도에 표시하는 함수입니다
      function drawCircle(circles) {
          var len = circles.length, i = 0;

          for (; i < len; i++) {
              var center = new daum.maps.LatLng(circles[i].center.y, circles[i].center.x);
              var radius = circles[i].radius;
              manager.put(daum.maps.drawing.OverlayType.CIRCLE, center, radius);
          }
      }

      // Drawing Manager에서 가져온 데이터 중 다각형을 아래 지도에 표시하는 함수입니다
      function drawPolygon(polygons) {
          var len = polygons.length, i = 0;

          for (; i < len; i++) {
              var path = pointsToPath(polygons[i].points);
              manager.put(daum.maps.drawing.OverlayType.POLYGON, path);
          }
      }

      setTimeout(function(){

        $('#set-map').show();
        map.relayout();

        // 주어진 영역이 화면 안에 전부 나타날 수 있도록 지도의 중심 좌표와 확대 수준을 설정한다.
        var bounds = new daum.maps.LatLngBounds();

        for (i = 0; i < data[daum.maps.drawing.OverlayType.MARKER].length; i++) {
            var position = new daum.maps.LatLng(data[daum.maps.drawing.OverlayType.MARKER][i].y, data[daum.maps.drawing.OverlayType.MARKER][i].x);
            bounds.extend(position);
        }

        for (i = 0; i < data[daum.maps.drawing.OverlayType.ARROW].length; i++) {
            var path = pointsToPath(data[daum.maps.drawing.OverlayType.ARROW][i].points);
            for (a = 0; a < path.length; a++) {
              bounds.extend(path[a]);
            }
        }

        for (i = 0; i < data[daum.maps.drawing.OverlayType.POLYLINE].length; i++) {
            var path = pointsToPath(data[daum.maps.drawing.OverlayType.POLYLINE][i].points);
            for (a = 0; a < path.length; a++) {
              bounds.extend(path[a]);
            }
        }

        for (i = 0; i < data[daum.maps.drawing.OverlayType.RECTANGLE].length; i++) {
            var spoint = new daum.maps.LatLng(data[daum.maps.drawing.OverlayType.RECTANGLE][i].sPoint.y, data[daum.maps.drawing.OverlayType.RECTANGLE][i].sPoint.x);
            var epoint = new daum.maps.LatLng(data[daum.maps.drawing.OverlayType.RECTANGLE][i].ePoint.y, data[daum.maps.drawing.OverlayType.RECTANGLE][i].ePoint.x);
            bounds.extend(spoint);
            bounds.extend(epoint);
        }

        for (i = 0; i < data[daum.maps.drawing.OverlayType.CIRCLE].length; i++) {
            var center = new daum.maps.LatLng(data[daum.maps.drawing.OverlayType.CIRCLE][i].center.y, data[daum.maps.drawing.OverlayType.CIRCLE][i].center.x);
            bounds.extend(center);
        }

        for (i = 0; i < data[daum.maps.drawing.OverlayType.POLYGON].length; i++) {
            var path = pointsToPath(data[daum.maps.drawing.OverlayType.POLYGON][i].points);
            for (a = 0; a < path.length; a++) {
              bounds.extend(path[a]);
            }
        }

        map.setBounds(bounds);

        // 현재 지도의 레벨을 얻어옵니다
        // var level = map.getLevel();
        //
        // // 지도를 1레벨 올립니다 (지도가 축소됩니다)
        // map.setLevel(level + 1);

      }, 1);

    }else{
      setTimeout(function(){
        $('#set-map').show();
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
      }, 1);
    }

  <?php endif; ?>
<?php else: ?>
  setTimeout(function(){
    $('#set-map').show();
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
  }, 1);
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
