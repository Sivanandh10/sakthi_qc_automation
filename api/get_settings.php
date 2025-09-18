<?php
// FILE: api/get_settings.php

session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stmt = $conn->prepare("SELECT parameter_name, min_value, moderate_value, max_value FROM evaluation_rules");
$stmt->execute();
$rules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'rules' => $rules]);
?>