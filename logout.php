<?php
session_start();

// --- DATABASE CONNECTION ---
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'payroll_db');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Optional: handle connection error
}

// --- LOG THE SIGN-OUT ACTION ---
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $log_sql = "INSERT INTO activity_logs (user_id, username, action) VALUES (?, ?, ?)";
    $log_stmt = $pdo->prepare($log_sql);
    $log_stmt->execute([
        $_SESSION["id"], 
        $_SESSION["username"], 
        'Sign Out'
    ]);
}

// --- DESTROY THE SESSION ---
$_SESSION = array();
session_destroy();
 
// Redirect to login page
header("location: login.php");
exit;
?>