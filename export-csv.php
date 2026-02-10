<?php
// Initialize the session and check if the user is logged in
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// --- DATABASE CONNECTION ---
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'payroll_db');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// --- SEARCH LOGIC (to filter the export) ---
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$search_category = isset($_GET['category']) ? $_GET['category'] : 'name';
$sql_where = "";
$params = [];

if (!empty($search_query)) {
    switch ($search_category) {
        case 'name':
            $sql_where = " WHERE CONCAT_WS(' ', first_name, last_name) LIKE :query";
            $params[':query'] = '%' . $search_query . '%';
            break;
        case 'payroll_no':
            $sql_where = " WHERE payroll_number LIKE :query";
            $params[':query'] = '%' . $search_query . '%';
            break;
        case 'month':
            $sql_where = " WHERE payroll_month LIKE :query";
            $params[':query'] = $search_query . '%';
            break;
    }
}

// --- DATA FETCHING (without pagination limit) ---
$sql = "SELECT * FROM payroll_entries $sql_where ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- CSV GENERATION ---
$filename = "payroll_records_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add the CSV header row
fputcsv($output, [
    'ID', 'Payroll Month', 'Payroll Number', 'Last Name', 'First Name', 'Middle Name', 
    'Suffix', 'Department', 'SSS', 'Amount', 'Created At', 'Created By', 'Updated At', 'Updated By'
]);

// Loop through the filtered records and add them to the CSV
if (count($records) > 0) {
    foreach ($records as $row) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
?><?php
// Initialize the session and check if the user is logged in
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// --- DATABASE CONNECTION ---
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'payroll_db');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// --- SEARCH LOGIC (to filter the export) ---
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$search_category = isset($_GET['category']) ? $_GET['category'] : 'name';
$sql_where = "";
$params = [];

if (!empty($search_query)) {
    switch ($search_category) {
        case 'name':
            $sql_where = " WHERE CONCAT_WS(' ', first_name, last_name) LIKE :query";
            $params[':query'] = '%' . $search_query . '%';
            break;
        case 'payroll_no':
            $sql_where = " WHERE payroll_number LIKE :query";
            $params[':query'] = '%' . $search_query . '%';
            break;
        case 'month':
            $sql_where = " WHERE payroll_month LIKE :query";
            $params[':query'] = $search_query . '%';
            break;
    }
}

// --- DATA FETCHING (without pagination limit) ---
$sql = "SELECT * FROM payroll_entries $sql_where ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- CSV GENERATION ---
$filename = "payroll_records_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add the CSV header row
fputcsv($output, [
    'ID', 'Payroll Month', 'Payroll Number', 'Last Name', 'First Name', 'Middle Name', 
    'Suffix', 'Department', 'SSS', 'Amount', 'Created At', 'Created By', 'Updated At', 'Updated By'
]);

// Loop through the filtered records and add them to the CSV
if (count($records) > 0) {
    foreach ($records as $row) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
?>