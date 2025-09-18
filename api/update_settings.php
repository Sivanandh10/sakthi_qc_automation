<?php
// FILE: api/update_settings.php

session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

// Security Check: Only allow supervisors and admins
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['supervisor', 'admin'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'You do not have permission to perform this action.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Extract and validate all values
$rules = [
    'Temperature' => [
        'min' => filter_var($input['temp_min_value'] ?? 0, FILTER_VALIDATE_FLOAT),
        'moderate' => filter_var($input['temp_moderate_value'] ?? 0, FILTER_VALIDATE_FLOAT),
        'max' => filter_var($input['temp_max_value'] ?? 0, FILTER_VALIDATE_FLOAT)
    ],
    'Pressure' => [
        'min' => filter_var($input['pressure_min_value'] ?? 0, FILTER_VALIDATE_FLOAT),
        'moderate' => filter_var($input['pressure_moderate_value'] ?? 0, FILTER_VALIDATE_FLOAT),
        'max' => filter_var($input['pressure_max_value'] ?? 0, FILTER_VALIDATE_FLOAT)
    ]
];

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE evaluation_rules SET min_value = ?, moderate_value = ?, max_value = ? WHERE parameter_name = ?");
    foreach($rules as $param_name => $values) {
        $stmt->bind_param("ddds", $values['min'], $values['moderate'], $values['max'], $param_name);
        $stmt->execute();
    }
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>