<?php
// FILE: config/database.php

// --- DATABASE CONFIGURATION ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sakthi_qc');

// --- EMAIL (SMTP) CONFIGURATION ---
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'hackfest@esec.ac.in');
define('SMTP_PASS', 'kkepwfszvkvkjmrb');
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl');

// --- APPLICATION SETTINGS ---
define('APP_URL', 'http://localhost/sakthi_qc_automation');
define('APP_NAME', 'SAKTHI QC Automation');
define('SMTP_FROM_EMAIL', 'hackfest@esec.ac.in');
define('SMTP_FROM_NAME', 'SAKTHI QC System');

// --- GLOBAL DATABASE CONNECTION ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
?>