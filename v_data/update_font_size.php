<?php
include_once('../config_custom.php');
include_once('../config_table.php');

if(empty($_POST['font_size'])) {
    echo 'FAIL';
    exit;
}

$mysqli = new mysqli($host, $user, $password, $dbname);
if ($mysqli->connect_errno) {
    echo 'FAIL';
    exit;
}

$mb_id = mb_id();
$font_size = $_POST['font_size'];

$mysqli->query("UPDATE ".MEMBER_TABLE." SET `font_size` = '".$font_size."' WHERE `mb_id` = '".$mb_id."'");

if($mysqli->affected_rows > 0) {
    echo 'OK';
} else {
    echo 'FAIL';
}

$mysqli->close();
?> 