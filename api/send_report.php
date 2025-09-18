<?php
// FILE: api/send_report.php

session_start();
require_once '../config/database.php';
require_once '../includes/mailer.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$record_id = (int)($input['record_id'] ?? 0);

if ($record_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Record ID.']);
    exit;
}

// Get record and user details
$stmt = $conn->prepare("SELECT r.report_path, r.batch_number, r.evaluation_status, e.full_name, e.email 
    FROM qc_records r 
    JOIN employees e ON r.employee_id = e.id 
    WHERE r.id = ?");
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result || empty($result['report_path'])) {
    echo json_encode(['success' => false, 'message' => 'Could not find the report file.']);
    exit;
}

// Send email using the centralized mailer
$user_email = $result['email'];
$user_name = $result['full_name'];
$admin_email = 'sivanandhcc@esec.ac.in';

$subject = "QC Report Submitted: Batch #{$result['batch_number']} - {$result['evaluation_status']}";
$body = "Dear Team,<br><br>A new QC report has been submitted by <strong>{$user_name}</strong>. Please find the details attached.<br><br>
    <strong>Batch:</strong> {$result['batch_number']}<br>
    <strong>Final Status:</strong> {$result['evaluation_status']}<br><br>
    Thank you.";
$attachment_path = '../' . $result['report_path'];

// Use a new PHPMailer instance for multi-send
$mail = new PHPMailer\PHPMailer\PHPMailer(true);
$mail->isSMTP();
$mail->Host = SMTP_HOST;
$mail->SMTPAuth = true;
$mail->Username = SMTP_USER;
$mail->Password = SMTP_PASS;
$mail->SMTPSecure = SMTP_SECURE;
$mail->Port = SMTP_PORT;
$mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
$mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

$mail->addAddress($user_email, $user_name);
$mail->addAddress($admin_email); // Add second recipient

$mail->isHTML(true);
$mail->Subject = $subject;
$mail->Body = $body;
$mail->addAttachment($attachment_path);

try {
    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Report sent successfully to you and the QC head.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Email could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
}
?>