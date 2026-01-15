<?php
// Mock input
$m_id = 9; // Use a likely valid meeting ID (from screenshot logic or guessing)
// Actually I don't know a valid ID. I'll pick 1 or 9.
// But wait, if m_id is invalid, it just returns [].
$_POST['m_id'] = $m_id;

// Include the target file
include 'guide_assign_step_territory.php';
?>