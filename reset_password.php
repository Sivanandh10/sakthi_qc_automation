<?php
require_once 'config/database.php';
$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = "Invalid or missing reset token.";
} else {
    $token_hash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT id FROM employees WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error = "Token is invalid or has expired. Please request a new one.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if ($password !== $password_confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        // Fetch user ID again inside the POST block
        $stmt = $conn->prepare("SELECT id FROM employees WHERE reset_token_hash = ?");
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $user_id = $stmt->get_result()->fetch_assoc()['id'];
        
        $update_stmt = $conn->prepare("UPDATE employees SET password_hash = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $update_stmt->bind_param("si", $password_hash, $user_id);
        if($update_stmt->execute()){
            $success = "Your password has been reset successfully! You can now <a href='index.html'>log in</a>.";
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="background-container"></div>
    <div class="form-container">
        <div class="form-wrapper" style="opacity: 1;">
            <div class="form-header">
                <h2>Set New Password</h2>
            </div>
            
            <?php if (!empty($error)): ?>
                <div style="color: var(--error-color); background-color: #f8d7da; padding: 1rem; border-radius: 5px; text-align: center; margin-bottom: 1rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php elseif (!empty($success)): ?>
                 <div style="color: var(--success-color); background-color: #d4edda; padding: 1rem; border-radius: 5px; text-align: center; margin-bottom: 1rem;">
                    <?= $success ?>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-input" required>
                        <label for="password">New Password</label>
                    </div>
                    <div class="input-group">
                        <input type="password" id="password_confirm" name="password_confirm" class="form-input" required>
                        <label for="password_confirm">Confirm New Password</label>
                    </div>
                    <button type="submit" class="btn btn-primary full-width">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>