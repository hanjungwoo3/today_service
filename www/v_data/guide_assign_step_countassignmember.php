<?php include_once('../config.php');?>

<?php $assigned_member = get_assigned_member_of_meeting($m_id); ?>

<?php echo json_encode($assigned_member);?>
