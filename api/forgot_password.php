// FILE: api/forgot_password.php

<?php
require_once '../config/database.php';
require_once '../includes/mailer.php'; // Include the new mailer function

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, full_name FROM employees WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Create a secure token
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expires_at = date("Y-m-d H:i:s", time() + 1800); // Token valid for 30 minutes

    $update_stmt = $conn->prepare("UPDATE employees SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?");
    $update_stmt->bind_param("ssi", $token_hash, $expires_at, $user['id']);
    $update_stmt->execute();

    // Prepare and send email
    $reset_link = APP_URL . "/reset_password.php?token=" . $token;
    $subject = 'Password Reset Request - ' . APP_NAME;
    $message = "Dear {$user['full_name']},<br><br>You requested a password reset. Click the link below to set a new password. This link is valid for 30 minutes.<br><br><a href='{$reset_link}'>Reset Password</a><br><br>If you did not request this, please ignore this email.";
    
    if (send_email($email, $user['full_name'], $subject, $message)) {
        echo json_encode(['success' => true, 'message' => 'Password reset link sent to your email.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send reset email. Please try again later.']);
    }

} else {
    // To prevent user enumeration, show a generic success message even if the email doesn't exist.
    echo json_encode(['success' => true, 'message' => 'If an account exists for this email, a reset link has been sent.']);
}

$stmt->close();
$conn->close();
?>