<?php include_once('../config.php');?>

<?php
$mb_id = mb_id();
$auth = $auth?$auth:1;

if(!in_array($auth, get_member_board_auth($mb_id)) || empty($b_id) || empty($auth)){
  echo '잘못된 접근입니다.';
  exit;
}

$sql="SELECT * FROM ".BOARD_TABLE." WHERE b_id = {$b_id}";
$result = $mysqli->query($sql);
$row = $result->fetch_assoc();
?>

<nav class="navbar navbar-light bg-light mb-4">
  <a class="navbar-brand" href="board.php?auth=<?=$auth?>&page=<?=$page?>"><i class="bi bi-arrow-left"></i></a>
  <div class="w-75 float-right text-right mb-0 clearfix">
    <div><?=$row['b_title']?></div>
    <small class="text-muted float-right"><?=get_datetime_text($row['create_datetime'])?></small>
</div>
</nav>

<div class="board-content p-3"><?=$row['b_content']?></div>

<!-- Viewer.js: 고급 이미지 뷰어 (확대/축소, 드래그) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/viewerjs@1.11.6/dist/viewer.min.css">
<script src="https://cdn.jsdelivr.net/npm/viewerjs@1.11.6/dist/viewer.min.js"></script>
<script>
  (function(){
    function onReady(fn){
      if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', fn);
      }else{ fn(); }
    }

    function enhanceImages(){
      var container = document.querySelector('.board-content');
      if(!container) return;
      var imgs = Array.prototype.slice.call(container.querySelectorAll('img'));
      imgs.forEach(function(img){
        // 이미 버튼이 붙은 경우 중복 처리 방지
        if(img.closest('.board-image-wrap')) return;

        // 링크로 감싸져 있으면 링크 노드를, 아니면 이미지 노드를 감싸도록 함
        var target = img.closest('a') || img;
        var wrapper = document.createElement('span');
        wrapper.className = 'board-image-wrap';
        target.parentNode.insertBefore(wrapper, target);
        wrapper.appendChild(target);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'board-image-zoom-btn';
        btn.innerHTML = '<i class="bi bi-zoom-in"></i>';
        wrapper.appendChild(btn);

        btn.addEventListener('click', function(e){
          e.preventDefault();
          e.stopPropagation();
          try{
            var viewer = new Viewer(img, {
              navbar: false,
              title: false,
              toolbar: {
                zoomIn: 1,
                zoomOut: 1,
                oneToOne: 0,
                reset: 1,
                rotateLeft: 1,
                rotateRight: 1
              },
              movable: true,
              zoomable: true,
              scalable: true,
              transition: true
            });
            viewer.show();
            viewer.one('hidden', function(){ try{ viewer.destroy(); }catch(_){} });
          }catch(err){ /* noop */ }
        });
      });
    }

    onReady(enhanceImages);
  })();
</script>

<div class="clearfix my-4">
  <?php if(is_admin($mb_id)):?>
  <button type="button" class="btn btn-outline-danger" onclick="board_work('del', '<?=$row['b_id']?>', '<?=$auth?>', '<?=$page?>')"><i class="bi bi-trash"></i> 삭제</button>
  <button type="button" class="btn btn-outline-primary float-right" onclick="board_work('write', '<?=$row['b_id']?>', '<?=$auth?>', '<?=$page?>')"><i class="bi bi-pencil-square"></i> 수정</button>
  <?php endif;?>
</div>
