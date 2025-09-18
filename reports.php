<?php
// FILE: reports.php

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
    <title>Reports - SAKTHI QC Automation</title>
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
                <a href="reports.php" class="nav-link active">
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
                <h1>Historical Reports & Analytics</h1>
            </header>
            
            <section class="card">
                <div class="filters-bar">
                    <div class="filter-group">
                        <label for="start-date">From:</label>
                        <input type="date" id="start-date" class="form-input">
                    </div>
                    <div class="filter-group">
                        <label for="end-date">To:</label>
                        <input type="date" id="end-date" class="form-input">
                    </div>
                    <button id="generate-report-btn" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Generate Report</button>
                </div>
            </section>

            <div id="report-content" class="hidden">
                <section class="chart-grid">
                    <div class="card chart-container">
                        <h3>Daily Inspection Volume</h3>
                        <canvas id="dailyVolumeChart"></canvas>
                    </div>
                    <div class="card chart-container">
                        <h3>Overall Status Ratio</h3>
                        <canvas id="statusRatioChart"></canvas>
                    </div>
                </section>

                <section class="table-container-wrapper">
                    <div class="table-header">
                        <h2>Detailed Records</h2>
                        <button id="export-csv-btn" class="btn btn-secondary"><i class="fas fa-file-csv"></i> Export CSV</button>
                    </div>
                    <div class="table-container">
                        <table id="reports-table">
                            <thead>
                                <tr>
                                    <th>Component</th>
                                    <th>Batch #</th>
                                    <th>Status</th>
                                    <th>Timestamp</th>
                                    <th>Operator</th>
                                    <th>Temp (°C)</th>
                                    <th>Pressure (bar)</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>
                </section>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/reports.js"></script>
</body>
</html>