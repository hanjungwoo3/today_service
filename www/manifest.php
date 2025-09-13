<?php
include_once('config.php');

header('Content-Type: application/json');

$manifest = array(
  "short_name" => "오늘의 봉사",
  "name" => "오늘의 봉사",
  "icons" => array(
    array(
      "src" => BASE_PATH."/icons/icon-jw-n.png",
      "type" => "image/png",
      "sizes" => "48x48"
    ),
    array(
      "src" => BASE_PATH."/icons/icon-jw-n.png",
      "type" => "image/png",
      "sizes" => "96x96"
    ),
    array(
      "src" => BASE_PATH."/icons/icon-jw-n.png",
      "type" => "image/png",
      "sizes" => "144x144"
    ),
    array(
      "src" => BASE_PATH."/icons/icon-jw-n.png",
      "type" => "image/png",
      "sizes" => "192x192"
    ),
    array(
      "src" => BASE_PATH."/icons/icon-jw-n.png",
      "type" => "image/png",
      "sizes" => "1024x1024"
    )
  ),
  "start_url" => BASE_PATH."/",
  "background_color" => "#ffffff",
  "theme_color" => "#4a6da7",
  "display" => "standalone",
  "orientation" => "portrait"
);

echo json_encode($manifest, JSON_UNESCAPED_SLASHES);
?> 