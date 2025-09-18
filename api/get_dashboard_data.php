<?php
// FILE: api/get_dashboard_data.php

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// --- HANDLE SINGLE RECORD FETCH for PDF preview ---
if (isset($_GET['record_id'])) {
    $record_id = (int)$_GET['record_id'];
    $stmt = $conn->prepare("SELECT report_path FROM qc_records WHERE id = ?");
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result) {
        echo json_encode(['success' => true, 'report_path' => $result['report_path']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found.']);
    }
    exit;
}


// --- HANDLE MAIN DASHBOARD DATA FETCH ---
$today_start = date('Y-m-d') . ' 00:00:00';

// KPIs
$kpi_stmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN evaluation_status = 'Pass' THEN 1 ELSE 0 END) as passed
    FROM qc_records WHERE timestamp >= ?");
$kpi_stmt->bind_param("s", $today_start);
$kpi_stmt->execute();
$kpi_result = $kpi_stmt->get_result()->fetch_assoc();

$total = (int)$kpi_result['total'];
$passed = (int)$kpi_result['passed'];
$failed = $total - $passed;

$pass_rate = ($total > 0) ? round(($passed / $total) * 100) : 0;
$fail_rate = ($total > 0) ? round(($failed / $total) * 100) : 0;

$kpis = [
    'total_inspections' => $total,
    'pass_rate' => $pass_rate,
    'fail_rate' => $fail_rate
];

// Recent Inspections (Last 20)
$inspections_stmt = $conn->prepare("SELECT r.id, r.component_name, r.batch_number, r.evaluation_status, r.timestamp, r.report_path, e.full_name 
    FROM qc_records r
    JOIN employees e ON r.employee_id = e.id
    ORDER BY r.timestamp DESC
    LIMIT 20");
$inspections_stmt->execute();
$recent_inspections = $inspections_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'kpis' => $kpis,
    'recent_inspections' => $recent_inspections
]);

?>