<?php
// FILE: api/get_report_data.php

session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Append time to dates to cover the entire day
$start_datetime = $start_date . ' 00:00:00';
$end_datetime = $end_date . ' 23:59:59';

// --- Chart Data: Daily Volume ---
$chart_daily_stmt = $conn->prepare("
    SELECT
        DATE(timestamp) as inspection_date,
        SUM(CASE WHEN evaluation_status = 'Pass' THEN 1 ELSE 0 END) as pass_count,
        SUM(CASE WHEN evaluation_status = 'Fail' THEN 1 ELSE 0 END) as fail_count
    FROM qc_records
    WHERE timestamp BETWEEN ? AND ?
    GROUP BY inspection_date
    ORDER BY inspection_date ASC
");
$chart_daily_stmt->bind_param("ss", $start_datetime, $end_datetime);
$chart_daily_stmt->execute();
$daily_results = $chart_daily_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$daily_chart_data = ['labels' => [], 'pass' => [], 'fail' => []];
foreach ($daily_results as $row) {
    $daily_chart_data['labels'][] = $row['inspection_date'];
    $daily_chart_data['pass'][] = (int)$row['pass_count'];
    $daily_chart_data['fail'][] = (int)$row['fail_count'];
}

// --- Chart Data: Status Ratio ---
$ratio_stmt = $conn->prepare("
    SELECT evaluation_status, COUNT(*) as count
    FROM qc_records
    WHERE timestamp BETWEEN ? AND ?
    GROUP BY evaluation_status
");
$ratio_stmt->bind_param("ss", $start_datetime, $end_datetime);
$ratio_stmt->execute();
$ratio_results = $ratio_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$ratio_chart_data = ['pass' => 0, 'fail' => 0];
foreach ($ratio_results as $row) {
    if ($row['evaluation_status'] == 'Pass') {
        $ratio_chart_data['pass'] = (int)$row['count'];
    } else {
        $ratio_chart_data['fail'] = (int)$row['count'];
    }
}

// --- Table Data ---
$table_stmt = $conn->prepare("
    SELECT
        r.component_name, r.batch_number, r.evaluation_status, r.timestamp, e.full_name,
        MAX(CASE WHEN dp.parameter_name = 'Temperature' THEN dp.parameter_value ELSE NULL END) as temperature,
        MAX(CASE WHEN dp.parameter_name = 'Pressure' THEN dp.parameter_value ELSE NULL END) as pressure
    FROM qc_records r
    JOIN employees e ON r.employee_id = e.id
    LEFT JOIN qc_data_points dp ON r.id = dp.record_id
    WHERE r.timestamp BETWEEN ? AND ?
    GROUP BY r.id
    ORDER BY r.timestamp DESC
");
$table_stmt->bind_param("ss", $start_datetime, $end_datetime);
$table_stmt->execute();
$table_data = $table_stmt->get_result()->fetch_all(MYSQLI_ASSOC);


echo json_encode([
    'success' => true,
    'charts' => [
        'daily' => $daily_chart_data,
        'ratio' => $ratio_chart_data
    ],
    'table_data' => $table_data
]);

?>