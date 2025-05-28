<?php
include '../../services/database.php';

unset($_SESSION['allocation_result']);
echo json_encode(['status' => 'success', 'message' => 'Data CMO direset.']);
?>