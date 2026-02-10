    <?php
    session_start();
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        header("location: login.php");
        exit;
    }

    // Get the current page's filename to set the active state
    $current_page = basename($_SERVER['PHP_SELF']);

    // Database connection details
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'payroll_db');

    // Initialize variables
$success_message = "";
$error_message = "";
$payrollMonth = date('Y-m'); 
$payrollNumber = "";
// NEW: Initialize new fields
$employee_id = "";
$appointment_number = "";
$lastName = "";
$firstName = "";
$middleName = "";
$suffix = "";
$department = "";
$sss = "";
$amount = "";

    // Check if the form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Establish a new connection for this transaction
        $link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($link === false) {
            die("ERROR: Could not connect. " . mysqli_connect_error());
        }

        // Sanitize inputs
        $payrollMonth = trim($_POST["payrollMonth"]);
        $payrollNumber = trim($_POST["payrollNumber"]);
        $employee_id = trim($_POST["employee_id"]);
        $appointment_number = trim($_POST["appointment_number"]);
        $lastName = trim($_POST["lastName"]);
        $firstName = trim($_POST["firstName"]);
        $middleName = trim($_POST["middleName"]);
        $suffix = trim($_POST["suffix"]);
        $department = trim($_POST["department"]);
        $sss = floatval(trim($_POST["sss"]));
        $amount = floatval(trim($_POST["amount"]));
        $creator_username = $_SESSION['username'];

        // --- START: NAME FIELD VALIDATION ---
        // This regex allows letters, spaces, hyphens (-), and apostrophes (')
    if (!preg_match("/^[a-zA-Z\s'-]+$/", $lastName)) {
        $error_message = "Error: Last name can only contain letters and spaces.";
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $firstName)) {
        $error_message = "Error: First name can only contain letters and spaces.";
    } elseif (!empty($middleName) && !preg_match("/^[a-zA-Z\s'-]+$/", $middleName)) {
        $error_message = "Error: Middle name can only contain letters and spaces.";
    } elseif (!empty($suffix) && !preg_match("/^[a-zA-Z\s.-]+$/", $suffix)) {
        // This regex for suffix allows only letters, spaces, periods, and hyphens.
        $error_message = "Error: Suffix can only contain letters and periods (e.g., Jr.).";
    }

        if (empty($error_message)) {
        $check_sql = "SELECT id FROM payroll_entries WHERE employee_id = ? AND payroll_month = ?";
        if ($check_stmt = mysqli_prepare($link, $check_sql)) {
            mysqli_stmt_bind_param($check_stmt, "ss", $employee_id, $payrollMonth);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $error_message = "Error: A payroll entry for Employee ID '" . htmlspecialchars($employee_id) . "' already exists for this month.";
            }
            mysqli_stmt_close($check_stmt);
        }
    }
        // --- END: NAME FIELD VALIDATION ---

    // Proceed if initial validation passes
    if (empty($error_message)) {
        // --- START: DUPLICATE CHECK (MODIFIED) ---
        // 1. UPDATED SQL: Added middle_name to the check
        $check_sql = "SELECT id FROM payroll_entries WHERE first_name = ? AND last_name = ? AND middle_name = ? AND payroll_month = ?";
        
        if ($check_stmt = mysqli_prepare($link, $check_sql)) {
            // 2. UPDATED BIND: Added middleName and changed type string from "sss" to "ssss"
            mysqli_stmt_bind_param($check_stmt, "ssss", $firstName, $lastName, $middleName, $payrollMonth);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                // 3. UPDATED MESSAGE: Made error message more specific
                $error_message = "Error: A payroll entry for " . htmlspecialchars($firstName) . " " . htmlspecialchars($middleName) . " " . htmlspecialchars($lastName) . " already exists for this month.";
            }
            mysqli_stmt_close($check_stmt);
        }
        // --- END: DUPLICATE CHECK ---
    }

        // Proceed with insertion only if there are still no errors
if (empty($error_message)) {
    // UPDATED: Added employee_id and appointment_number to the query
    $sql = "INSERT INTO payroll_entries (payroll_month, payroll_number, employee_id, appointment_number, last_name, first_name, middle_name, suffix, department, sss, amount, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = mysqli_prepare($link, $sql)) {
        // UPDATED: Added the new fields and changed the type string to "sssssssssdds"
        mysqli_stmt_bind_param($stmt, "sssssssssdds", 
            $payrollMonth, $payrollNumber, $employee_id, $appointment_number,
            $lastName, $firstName, $middleName, $suffix, $department, 
            $sss, $amount, $creator_username
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Payroll entry added successfully!";

                      // --- ADD THIS LOGGING CODE ---
                    $log_action = "Created Payroll Entry: " . $payrollNumber;
                    $log_sql = "INSERT INTO activity_logs (user_id, username, action) VALUES (?, ?, ?)";
                     if ($log_stmt = mysqli_prepare($link, $log_sql)) {
                       mysqli_stmt_bind_param($log_stmt, "iss", $_SESSION['id'], $_SESSION['username'], $log_action);
                         mysqli_stmt_execute($log_stmt);
                            mysqli_stmt_close($log_stmt);
                     }
                } else {
                    $error_message = "Something went wrong. Please try again later. " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        mysqli_close($link);
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard - Payroll System</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f4f6f9; }
            a { text-decoration: none; }
            .top-header { width: 100%; height: 60px; background-color: #111827; color: #d1d5db; padding: 0 25px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #374151; position: fixed; top: 0; left: 0; z-index: 1000; }
            .header-title { font-size: 20px; font-weight: 600; flex-shrink: 0; }
            .sidebar { width: 240px; background-color: #111827; color: #d1d5db; display: flex; flex-direction: column; position: fixed; left: 0; top: 60px; height: calc(100vh - 60px); }
            .sidebar-profile { text-align: center; padding: 20px 15px; border-bottom: 1px solid #374151; }
            .sidebar-profile img { width: 60px; height: 60px; border-radius: 50%; border: 2px solid #3498db; object-fit: cover; }
            .sidebar-profile h3 { margin: 10px 0 5px; font-size: 16px; color: #ffffff; }
            .sidebar-nav-title { padding: 25px 20px 10px; font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
            .sidebar-nav { flex-grow: 1; overflow-y: auto; }
            .sidebar-nav ul { list-style-type: none; }
            .sidebar-nav ul li a { display: flex; align-items: center; color: #d1d5db; padding: 12px 20px; font-size: 14px; transition: background-color 0.2s ease, color 0.2s ease; }
            .sidebar-nav ul li a .icon { margin-right: 15px; width: 20px; text-align: center; }
            .sidebar-nav ul li a:hover { background-color: #374151; color: #ffffff; }
            .sidebar-nav ul li.active a { background-color: #3b82f6; color: #ffffff; font-weight: bold; }
            .sidebar-footer { padding: 20px; border-top: 1px solid #374151; }
            .signout-btn-sidebar { display: flex; justify-content: center; align-items: center; gap: 10px; color: #ffffff; background-color: #ef4444; padding: 10px; border-radius: 6px; font-weight: 600; font-size: 14px; transition: background-color 0.2s ease; }
            .signout-btn-sidebar:hover { background-color: #dc2626; }
            .main-content { margin-left: 240px; padding-top: 60px; padding: 25px; }
            .form-container { background-color: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 100%; max-width: 1200px; margin: 115px auto; }
            .form-container h2 { text-align: center; font-size: 24px; color: #333; margin-bottom: 30px; }
            .form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; margin-bottom: 30px; }
            .grid-col-span-2 { grid-column: span 2; }
            .grid-col-span-4 { grid-column: span 4; }
            .form-group label { display: block; font-weight: 600; color: #444; margin-bottom: 8px; font-size: 14px; }
            .form-group input { width: 100%; padding: 12px 15px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
            .submit-btn { width: 100%; padding: 15px; font-size: 16px; font-weight: bold; color: #ffffff; background-color: #007bff; border: none; border-radius: 8px; cursor: pointer; transition: background-color 0.2s ease; }
            .submit-btn:hover { background-color: #0056b3; }
            .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; font-size: 15px; text-align: center; }
            .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        </style>
    </head>
    <body>
        
        <header class="top-header">
            <div class="header-title">Payroll System</div>
        </header>

        <aside class="sidebar">
            <div class="sidebar-profile">
                <img src="<?php echo htmlspecialchars($_SESSION['profile_picture'] ?? 'default-avatar.png'); ?>" alt="User Avatar">
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'admin'); ?></h3>
            </div>
            <div class="sidebar-nav-title">NAVIGATION</div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                        <a href="index.php"><i class="fas fa-edit icon"></i>Data Entry</a>
                    </li>
                    <li class="<?php echo ($current_page == 'view-records.php') ? 'active' : ''; ?>">
                        <a href="view-records.php"><i class="fas fa-table-list icon"></i>View Records</a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="signout-btn-sidebar">
                    <i class="fas fa-right-from-bracket"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </aside>

<main class="main-content">
    <div class="form-container">
        <h2>Add New Payroll Entry</h2>
        
        <?php 
            if(!empty($success_message)) { echo '<div class="alert alert-success">' . $success_message . '</div>'; }
            if(!empty($error_message)) { echo '<div class="alert alert-danger">' . $error_message . '</div>'; }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-grid">
                <div class="form-group grid-col-span-4">
                    <label for="payrollMonth">Payroll Month:</label>
                    <input type="month" id="payrollMonth" name="payrollMonth" value="<?php echo htmlspecialchars($payrollMonth); ?>" required>
                </div>
                <div class="form-group grid-col-span-2">
                    <label for="payrollNumber">Payroll Number:</label>
                    <input type="text" id="payrollNumber" name="payrollNumber" placeholder="e.g., PN-10234" value="<?php echo htmlspecialchars($payrollNumber); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="employee_id">Employee ID Number:</label>
                    <input type="text" id="employee_id" name="employee_id" placeholder="e.g., 2023-001" value="<?php echo htmlspecialchars($employee_id); ?>" required>
                </div>
                <div class="form-group">
                    <label for="appointment_number">Appointment Number:</label>
                    <input type="text" id="appointment_number" name="appointment_number" placeholder="e.g., AP-0525" value="<?php echo htmlspecialchars($appointment_number); ?>" required>
                </div>

                <div class="form-group">
                    <label for="lastName">Last Name:</label>
                    <input type="text" id="lastName" name="lastName" placeholder="e.g., Dela Cruz" value="<?php echo htmlspecialchars($lastName); ?>" required>
                </div>
                <div class="form-group">
                    <label for="firstName">First Name:</label>
                    <input type="text" id="firstName" name="firstName" placeholder="e.g., Juan" value="<?php echo htmlspecialchars($firstName); ?>" required>
                </div>
                <div class="form-group">
                    <label for="middleName">Middle Name:</label>
                    <input type="text" id="middleName" name="middleName" placeholder="Optional" value="<?php echo htmlspecialchars($middleName); ?>">
                </div>
                <div class="form-group">
                    <label for="suffix">Suffix:</label>
                    <input type="text" id="suffix" name="suffix" placeholder="e.g., Jr." value="<?php echo htmlspecialchars($suffix); ?>">
                </div>

                <div class="form-group grid-col-span-4">
                    <label for="department">Department:</label>
                    <input type="text" id="department" name="department" placeholder="e.g., HR" value="<?php echo htmlspecialchars($department); ?>" required>
                </div>
                <div class="form-group grid-col-span-2">
                    <label for="sss">SSS:</label>
                    <input type="number" id="sss" name="sss" step="0.01" placeholder="e.g., 585.00" value="<?php echo htmlspecialchars($sss); ?>" required>
                </div>
                <div class="form-group grid-col-span-2">
                    <label for="amount">Amount:</label>
                    <input type="number" id="amount" name="amount" step="0.01" placeholder="e.g., 50000.00" value="<?php echo htmlspecialchars($amount); ?>" required>
                </div>
            </div>
            <button type="submit" class="submit-btn">Add Payroll Entry</button>
        </form>
    </div>
</main>

    </body>
    </html>
