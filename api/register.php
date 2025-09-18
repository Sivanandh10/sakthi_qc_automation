<?php
// FILE: api/register.php

require_once '../config/database.php';
require_once '../includes/mailer.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$full_name = trim($input['full_name'] ?? '');
$employee_id = trim($input['employee_id'] ?? '');
$email = trim($input['email'] ?? '');
$role = $input['role'] ?? '';
$password = $input['password'] ?? '';
$password_confirm = $input['password_confirm'] ?? '';

$errors = [];
if (empty($full_name) || empty($employee_id) || empty($email) || empty($role) || empty($password)) {
    $errors[] = 'All fields are required.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}
if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}
if ($password !== $password_confirm) {
    $errors[] = 'Passwords do not match.';
}
$allowed_roles = ['operator', 'supervisor'];
if (!in_array($role, $allowed_roles)) {
    $errors[] = 'Invalid role selected.';
}
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM employees WHERE email = ? OR employee_id = ?");
$stmt->bind_param("ss", $email, $employee_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email or Employee ID already exists.']);
    $stmt->close();
    exit;
}
$stmt->close();

$password_hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare("INSERT INTO employees (full_name, employee_id, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $full_name, $employee_id, $email, $password_hash, $role);

if ($stmt->execute()) {
    $subject = "Welcome to " . APP_NAME;
    $message = "<h3>Welcome aboard, {$full_name}!</h3>
               <p>Your account for the SAKTHI AUTO COMPONENT LIMITED QC Portal has been successfully created with the role of '{$role}'.</p>
               <p>You can now log in using your Employee ID or email address.</p>
               <br>
               <p>Thank you,</p>
               <p>The SAKTHI QC Team</p>";

    send_email($email, $full_name, $subject, $message);
    echo json_encode(['success' => true, 'message' => 'Registration successful! A welcome email has been sent.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed due to a server error. Please try again.']);
}

$stmt->close();
$conn->close();
?>