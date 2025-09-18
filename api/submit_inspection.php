<?php
// FILE: api/submit_inspection.php

session_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/database.php';
require_once '../FPDF/fpdf.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// --- VALIDATION ---
$component_name = trim($input['component_name'] ?? '');
$batch_number = trim($input['batch_number'] ?? '');
$temperature = filter_var($input['temperature'] ?? '', FILTER_VALIDATE_FLOAT);
$pressure = filter_var($input['pressure'] ?? '', FILTER_VALIDATE_FLOAT);

if (empty($component_name) || empty($batch_number) || $temperature === false || $pressure === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill all fields with valid numbers.']);
    exit;
}

// --- EVALUATION LOGIC ---
$rules_stmt = $conn->prepare("SELECT parameter_name, min_value, moderate_value, max_value FROM evaluation_rules WHERE is_active = 1");
$rules_stmt->execute();
$rules_result = $rules_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rules = array_column($rules_result, null, 'parameter_name');

$results = [];
$overall_status = 'Pass';
$params_to_check = ['Temperature' => $temperature, 'Pressure' => $pressure];
foreach ($params_to_check as $name => $value) {
    $rule = $rules[$name];
    $status = 'danger';
    if ($value >= $rule['min_value'] && $value <= $rule['max_value']) {
        $status = ($value >= $rule['moderate_value'] && $value <= $rule['max_value']) ? 'good' : 'moderate';
    }
    if ($status === 'danger') $overall_status = 'Fail';
    $results[$name] = ['value' => $value, 'status' => $status, 'rule' => $rule];
}

// --- AI SUGGESTION LOGIC ---
$ai_suggestion = 'AI suggestion could not be generated at this time.';
try {
    $prompt = "You are a quality control expert in an automotive factory. A component test resulted in a status of '{$overall_status}'. Parameters measured: Temperature was {$temperature}°C (optimal range {$rules['Temperature']['min_value']}-{$rules['Temperature']['max_value']}), and Pressure was {$pressure} bar (optimal range {$rules['Pressure']['min_value']}-{$rules['Pressure']['max_value']}). Provide one brief, actionable suggestion for the operator to improve process stability or identify a root cause.";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://openrouter.ai/api/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'deepseek/deepseek-r1:free',
        'messages' => [['role' => 'user', 'content' => $prompt]]
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer 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',
        'Content-Type: application/json',
        'Http-Referer: ' . APP_URL,
        'X-Title: ' . APP_NAME
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch) === 0) {
        $response_data = json_decode($response, true);
        if (isset($response_data['choices'][0]['message']['content'])) {
            $ai_suggestion = trim($response_data['choices'][0]['message']['content']);
        } else if (isset($response_data['error']['message'])) {
            $ai_suggestion = 'AI Error: ' . $response_data['error']['message'];
        }
    } else {
        $ai_suggestion = 'AI connection error: ' . curl_error($ch);
    }
    curl_close($ch);
} catch (Exception $e) {
    // Fallback message remains
}


// --- DATABASE & PDF WORK ---
$conn->begin_transaction();
try {
    $stmt_record = $conn->prepare("INSERT INTO qc_records (component_name, batch_number, employee_id, evaluation_status) VALUES (?, ?, ?, ?)");
    $stmt_record->bind_param("ssis", $component_name, $batch_number, $_SESSION['user_id'], $overall_status);
    $stmt_record->execute();
    $record_id = $conn->insert_id;

    $stmt_data = $conn->prepare("INSERT INTO qc_data_points (record_id, parameter_name, parameter_value, unit) VALUES (?, ?, ?, ?)");
    $name_temp = 'Temperature'; $unit_temp = '°C';
    $stmt_data->bind_param("isds", $record_id, $name_temp, $temperature, $unit_temp);
    $stmt_data->execute();
    
    $name_pressure = 'Pressure'; $unit_pressure = 'bar';
    $stmt_data->bind_param("isds", $record_id, $name_pressure, $pressure, $unit_pressure);
    $stmt_data->execute();

    $reports_dir = __DIR__ . '/../reports';
    if (!is_dir($reports_dir)) {
        mkdir($reports_dir, 0777, true);
    }
    
    $file_path = $reports_dir . '/report_' . $record_id . '.pdf';

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'SAKTHI AUTO COMPONENT - QC REPORT', 0, 1, 'C');
    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(40, 10, 'Component:', 0, 0); $pdf->Cell(0, 10, $component_name, 0, 1);
    $pdf->Cell(40, 10, 'Batch #:', 0, 0); $pdf->Cell(0, 10, $batch_number, 0, 1);
    $pdf->Cell(40, 10, 'Operator:', 0, 0); $pdf->Cell(0, 10, $_SESSION['user_name'], 0, 1);
    $pdf->Cell(40, 10, 'Date:', 0, 0); $pdf->Cell(0, 10, date('Y-m-d H:i:s'), 0, 1);
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(60, 10, 'Parameter', 1); $pdf->Cell(60, 10, 'Value', 1); $pdf->Cell(60, 10, 'Status', 1, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    foreach($results as $name => $res) {
        $pdf->Cell(60, 10, $name, 1); $pdf->Cell(60, 10, $res['value'], 1);
        if($res['status'] === 'good') $pdf->SetTextColor(0,128,0);
        if($res['status'] === 'moderate') $pdf->SetTextColor(255,165,0);
        if($res['status'] === 'danger') $pdf->SetTextColor(255,0,0);
        $pdf->Cell(60, 10, strtoupper($res['status']), 1, 1, 'C');
        $pdf->SetTextColor(0,0,0);
    }
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, "OVERALL STATUS: " . strtoupper($overall_status), 0, 1, 'C');
    $pdf->Ln(5);

    // ** NEW: ADD AI SUGGESTION TO PDF **
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'AI Process Advisor Suggestion:', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $ai_suggestion)); // `iconv` helps handle special characters
    
    $pdf->Output('F', $file_path);
    
    $db_path = 'reports/report_' . $record_id . '.pdf';
    $stmt_update = $conn->prepare("UPDATE qc_records SET report_path = ? WHERE id = ?");
    $stmt_update->bind_param("si", $db_path, $record_id);
    $stmt_update->execute();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// --- PREPARE HTML RESPONSE ---
$evaluation_html = '<table><thead><tr><th>Parameter</th><th>Value</th><th>Status</th></tr></thead><tbody>';
foreach ($results as $name => $res) {
    $evaluation_html .= `<tr><td>{$name}</td><td>{$res['value']}</td><td><span class="status-badge {$res['status']}">{$res['status']}</span></td></tr>`;
}
$evaluation_html .= '</tbody></table><div class="overall-status-box status-'.$overall_status.'">Overall: <strong>'.$overall_status.'</strong></div>';
$evaluation_html .= '<div class="ai-suggestion-box"><i class="fas fa-lightbulb"></i><strong>AI Suggestion:</strong><p>' . htmlspecialchars($ai_suggestion) . '</p></div>';

echo json_encode([
    'success' => true, 
    'record_id' => $record_id,
    'evaluation_html' => $evaluation_html
]);

?>