<?php
// FILE: dashboard.php

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}
$userName = htmlspecialchars($_SESSION['user_name']);
$userRole = ucfirst(htmlspecialchars($_SESSION['user_role']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QC Dashboard - SAKTHI Automation</title>
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
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="nav-link">
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
                <h1>Dashboard Overview</h1>
                <button id="start-inspection-btn" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Start New Inspection
                </button>
            </header>

            <section class="kpi-grid">
                 <div class="kpi-card">
                    <div class="kpi-icon blue"><i class="fas fa-microscope"></i></div>
                    <div class="kpi-info">
                        <span class="kpi-label">Total Inspections (Today)</span>
                        <p id="kpi-total" class="kpi-value">0</p>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="kpi-info">
                        <span class="kpi-label">Pass Rate (Today)</span>
                        <p id="kpi-pass-rate" class="kpi-value">0%</p>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon red"><i class="fas fa-times-circle"></i></div>
                    <div class="kpi-info">
                        <span class="kpi-label">Fail Rate (Today)</span>
                        <p id="kpi-fail-rate" class="kpi-value">0%</p>
                    </div>
                </div>
            </section>

            <section class="table-container-wrapper">
                <h2>Recent Inspections</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Component</th>
                                <th>Batch #</th>
                                <th>Status</th>
                                <th>Timestamp</th>
                                <th>Operator</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recent-inspections-tbody">
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <div id="inspection-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <div id="step-1" class="modal-step active">
                <h2>New QC Inspection</h2>
                <form id="inspection-form">
                    <div class="input-group"><input type="text" id="component-name" name="component_name" class="form-input" required placeholder=" "><label for="component-name">Component Name (e.g., Piston Head)</label></div>
                    <div class="input-group"><input type="text" id="batch-number" name="batch_number" class="form-input" required placeholder=" "><label for="batch-number">Batch Number (e.g., B789-C)</label></div>
                    <div class="input-group"><input type="number" step="0.01" id="temperature" name="temperature" class="form-input" required placeholder=" "><label for="temperature">Temperature (°C)</label></div>
                    <div class="input-group"><input type="number" step="0.01" id="pressure" name="pressure" class="form-input" required placeholder=" "><label for="pressure">Pressure (bar)</label></div>
                    <div class="modal-actions"><button type="button" class="btn btn-secondary" id="close-modal-btn-1">Cancel</button><button type="submit" class="btn btn-primary">Evaluate Results <i class="fas fa-arrow-right"></i></button></div>
                </form>
            </div>
            <div id="step-2" class="modal-step">
                <h2>Evaluation Result</h2>
                <div id="evaluation-results-container"></div>
                <div class="modal-actions"><button type="button" class="btn btn-secondary" id="back-to-step-1-btn"><i class="fas fa-arrow-left"></i> Back</button><button type="button" class="btn btn-primary" id="generate-pdf-btn">Generate Report <i class="fas fa-file-pdf"></i></button></div>
            </div>
            <div id="step-3" class="modal-step">
                <h2>Report Preview</h2>
                <div id="pdf-preview-container"><iframe id="pdf-iframe" src="" frameborder="0"></iframe></div>
                <div class="modal-actions"><button type="button" class="btn btn-secondary" id="close-modal-btn-3">Discard</button><button type="button" class="btn btn-success" id="send-email-btn">Confirm & Send Email <i class="fas fa-paper-plane"></i></button></div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>