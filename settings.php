<?php
// FILE: settings.php

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}
$userName = htmlspecialchars($_SESSION['user_name']);
$userRole = ucfirst(htmlspecialchars($_SESSION['user_role']));
// Only Supervisors and Admins can manage rules
$canManageRules = (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['supervisor', 'admin']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SAKTHI QC Automation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-industry"></i>
                <h2>SAKTHI QC</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="nav-link active">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
                    <div class="user-details">
                        <span class="user-name"><?php echo $userName; ?></span>
                        <span class="user-role"><?php echo $userRole; ?></span>
                    </div>
                </div>
                <a href="logout.php" class="nav-link logout-link" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h1>System Settings</h1>
            </header>
            
            <?php if ($canManageRules): ?>
            <section class="card">
                <h2>Evaluation Rules Management</h2>
                <p class="card-subtitle">Adjust the thresholds for Pass/Fail evaluation. Changes take effect immediately.</p>
                <form id="rules-form">
                    <div class="rules-container">
                        <fieldset class="rule-group">
                            <legend>Temperature (°C)</legend>
                            <div class="input-group">
                                <label for="temp-min">Minimum (Pass)</label>
                                <input type="number" step="0.01" id="temp-min" name="temp_min_value" class="form-input">
                            </div>
                            <div class="input-group">
                                <label for="temp-moderate">Moderate Threshold</label>
                                <input type="number" step="0.01" id="temp-moderate" name="temp_moderate_value" class="form-input">
                            </div>
                             <div class="input-group">
                                <label for="temp-max">Maximum (Pass)</label>
                                <input type="number" step="0.01" id="temp-max" name="temp_max_value" class="form-input">
                            </div>
                        </fieldset>
                        <fieldset class="rule-group">
                             <legend>Pressure (bar)</legend>
                            <div class="input-group">
                                <label for="pressure-min">Minimum (Pass)</label>
                                <input type="number" step="0.01" id="pressure-min" name="pressure_min_value" class="form-input">
                            </div>
                            <div class="input-group">
                                <label for="pressure-moderate">Moderate Threshold</label>
                                <input type="number" step="0.01" id="pressure-moderate" name="pressure_moderate_value" class="form-input">
                            </div>
                             <div class="input-group">
                                <label for="pressure-max">Maximum (Pass)</label>
                                <input type="number" step="0.01" id="pressure-max" name="pressure_max_value" class="form-input">
                            </div>
                        </fieldset>
                    </div>
                    <div class="form-actions-right">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>
            </section>
            <?php else: ?>
            <section class="card">
                <p>You do not have permission to manage system settings.</p>
            </section>
            <?php endif; ?>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/settings.js"></script>
</body>
</html>