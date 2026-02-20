<?php
include_once('config.php');

setcookie("jw_ministry", "", time() - 3600);
unset( $_SESSION['mb_id'] );
echo "<script>location.href = '".BASE_PATH."/login.php';</script>";
exit;
?>
