<?php include_once('../config.php');?>

<?php
$total_img = array();

$sql = "SELECT * FROM ".BOARD_TABLE;
$result = $mysqli->query($sql);
if($result->num_rows>0){
  while($row = $result->fetch_assoc()){
    preg_match_all("#<img(.*?)\\/?>#", stripslashes($row['b_content']), $board_img);
    foreach ($board_img[1] as $val) {
      preg_match_all('/(src)=("[^"]*")/i', $val, $board_src);
      foreach ($board_src[2] as $val2) {
        $val2 = preg_replace("/[\"\']/i", "", $val2);
        array_push($total_img, $val2);
      }
    }
  }
}

function listFolderFiles($dir, $total_img){
  $ffs = scandir($dir);

  unset($ffs[array_search('.', $ffs, true)]);
  unset($ffs[array_search('..', $ffs, true)]);

  // 디렉토리가 비어있는지 확인합니다.
  if(count($ffs) < 1) return;

  foreach($ffs as $ff){
    if(is_dir($dir.'/'.$ff)){
      listFolderFiles($dir.'/'.$ff, $total_img);
    }else{
      $cdir = str_replace('.','',$dir);
      $url = $cdir.'/'.$ff;
      if(!in_array($url, $total_img)) unlink($dir.'/'.$ff);
    }
  }
}

listFolderFiles('../upload', $total_img);

exit;
?>
