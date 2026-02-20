<?php include_once('../config.php');?>

<?php
$mb_id = mb_id();
$auth = isset($auth)?$auth:'1';
$page = isset($page)?$page:1;

if(!in_array($auth, get_member_board_auth($mb_id))){
  echo '잘못된 접근입니다.';
  exit;
}

$total = $mysqli->query("SELECT count(*) FROM ".BOARD_TABLE." WHERE b_guide LIKE '%{$auth}%'")->fetch_row()[0];
$limit = BOARD_ITEM_PER_PAGE?BOARD_ITEM_PER_PAGE:20;
$pages = ceil($total / $limit);
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM ".BOARD_TABLE." WHERE b_guide LIKE '%{$auth}%' ORDER BY b_notice DESC, b_id DESC LIMIT {$offset}, {$limit}";
$result = $mysqli->query($sql);
?>

<div class="border-bottom pb-3">
  <button class="btn btn-outline-warning" onclick="board_work('all_view', '', '<?=$auth?>', '<?=$page?>')">전체읽음</button>
  <?php if(is_admin($mb_id)): ?>
  <button class="btn btn-outline-primary float-right" onclick="board_work('write', '', '<?=$auth?>', '<?=$page?>')">
    <i class="bi bi-plus-circle-dotted"></i> 추가
  </button>
  <?php endif; ?>
</div>

<?php if($result->num_rows > 0):?>
<div class="list-group list-group-flush">
  <?php if(file_exists(__DIR__.'/../include/custom_board_top.php')) include __DIR__.'/../include/custom_board_top.php'; ?>
  <?php while($row = $result->fetch_assoc()):?>
    <?php
    $bold = ($row['b_notice'])?'font-weight-bold':'';
    $datetime = explode(' ', $row['create_datetime']);
    $date = $datetime[0];
    $time = $datetime[1];
    $data = ($date == Date('Y-m-d'))?$time:Date('Y.m.d', strtotime($date));
    $read = $row['read_mb']?explode(" ", $row['read_mb']):array();
    ?>
    <div class="list-group-item px-0" onclick="board_work('view', '<?=$row['b_id']?>', '<?=$auth?>', '<?=$page?>')">
      <div class="d-flex w-100 justify-content-between">
        <div>
          <div>
            <?php
            // if($row['b_notice']){
            //   echo '<span class="badge badge-pill badge-light text-info align-middle"><i class="bi bi-bookmark-star-fill"></i></span>';
            // }
            ?>
            <?php if(!(in_array($mb_id, $read))) echo '<span class="badge badge-pill badge-light text-warning align-middle">읽지 않음</span>';?>
          </div>
          <div class="<?=$bold?>">
            <?=$row['b_title'];?>
          </div>
          <small class="text-muted"><?=$data?></small>
        </div>
        <i class="bi bi-chevron-right align-self-center"></i>
      </div>
    </div>
  <?php endwhile;?>
</div>

<nav aria-label="Page navigation" class="mt-4">
  <ul class="pagination justify-content-center">
    <?php for($i=1;$i<=$pages;$i++): ?>
      <li class="page-item <?=$i==$page?'active':''?>"><a class="page-link" href="<?=BASE_PATH?>/pages/board.php?auth=<?=$auth?>&page=<?=$i?>"><?=$i?></a></li>
    <?php endfor; ?>
  </ul>
</nav>
<?php else:?>
<div class="text-center align-middle p-5 text-secondary">공지사항이 없습니다</div>
<?php endif; ?>
