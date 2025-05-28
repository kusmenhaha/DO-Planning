<?php
include '../../services/database.php';

unset($_SESSION['cmo_temp_data']);
unset($_SESSION['cmo_temp_data_update']);
echo json_encode(['status' => 'success', 'message' => 'Data CMO direset.']);
?>