<?php
// FILE: api/login.php

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$email_or_id = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email_or_id) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email/ID and password are required.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, employee_id, full_name, email, password_hash, role FROM employees WHERE email = ? OR employee_id = ?");
$stmt->bind_param("ss", $email_or_id, $email_or_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        echo json_encode(['success' => true, 'message' => 'Login successful! Redirecting...', 'redirect' => 'dashboard.php']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
}

$stmt->close();
$conn->close();
?>