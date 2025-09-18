<?php
// FILE: api/validate_user.php

require_once '../config/database.php';

header('Content-Type: application/json');

$field = $_GET['field'] ?? '';
$value = trim($_GET['value'] ?? '');

if (empty($field) || empty($value)) {
    echo json_encode(['exists' => false, 'message' => 'Field or value not provided.']);
    exit;
}

$allowed_fields = ['email', 'employee_id'];
if (!in_array($field, $allowed_fields)) {
    echo json_encode(['exists' => false, 'message' => 'Invalid field specified.']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM employees WHERE `$field` = ?");
$stmt->bind_param("s", $value);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['exists' => true]);
} else {
    echo json_encode(['exists' => false]);
}

$stmt->close();
$conn->close();
?>