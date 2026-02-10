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

// Get the current page's filename for the active sidebar link
$current_page = basename($_SERVER['PHP_SELF']);

// Initialize variables
$record = null;
$id = null;

// --- HANDLE FORM SUBMISSION (POST REQUEST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get hidden ID from the form
    $id = $_POST['id'];

    // Collect updated data from form
    $payroll_month = $_POST['payroll_month'];
    $payroll_number = $_POST['payroll_number'];
    $employee_id = $_POST['employee_id'];
    $appointment_number = $_POST['appointment_number'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $suffix = $_POST['suffix'];
    $department = $_POST['department'];
    $sss = $_POST['sss'];
    $amount = $_POST['amount'];
    $updater_username = $_SESSION['username'];

    // Prepare an update statement
     $sql = "UPDATE payroll_entries SET 
                payroll_month = ?, payroll_number = ?, employee_id = ?, appointment_number = ?,
                last_name = ?, first_name = ?, middle_name = ?, suffix = ?, 
                department = ?, sss = ?, amount = ?, updated_by = ? 
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
     $stmt->execute([
        $payroll_month, $payroll_number, $employee_id, $appointment_number,
        $last_name, $first_name, $middle_name, $suffix,
        $department, $sss, $amount, $updater_username, $id
    ]);

    // --- ADDED: LOG UPDATE ACTION ---
    if ($stmt->rowCount()) {
        $log_action = "Updated Payroll Entry ID: " . $id;
        $log_sql = "INSERT INTO activity_logs (user_id, username, action) VALUES (?, ?, ?)";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute([$_SESSION['id'], $_SESSION['username'], $log_action]);
    }
    // --- END LOGGING CODE ---

    // Redirect back to the view records page
    header("location: view-records.php");
    exit();
} else {
    // --- FETCH EXISTING RECORD (GET REQUEST) ---
    if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
        $id = trim($_GET["id"]);

        $sql = "SELECT * FROM payroll_entries WHERE id = ?";
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(1, $id);
            if ($stmt->execute() && $stmt->rowCount() == 1) {
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                header("location: view-records.php");
                exit();
            }
        }
    } else {
        header("location: view-records.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Payroll Record</title>
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
        table { width: 100%; border-collapse: collapse; table-layout: auto; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 14px; white-space: nowrap; }
        th { background-color: #f9fafb; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .action-buttons { text-align: center; }
        .action-buttons a.edit-btn { background-color: #f59e0b; color: #fff; display: inline-block; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 13px; } 
        .action-buttons a.edit-btn:hover { background-color: #d97706; }
        .no-records { text-align: center; padding: 40px; color: #6b7280; }
        .form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; }
        .form-group { margin-bottom: 10px; }
        .grid-col-span-2 { grid-column: span 2; } .grid-col-span-4 { grid-column: span 4; }
        .form-group label { display: block; font-weight: 600; color: #444; margin-bottom: 8px; font-size: 14px; }
        .form-group input { width: 100%; padding: 12px 15px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        .form-buttons { display: flex; gap: 15px; grid-column: span 4; margin-top: 20px; }
        .submit-btn { flex-grow: 1; padding: 15px; font-size: 16px; font-weight: bold; color: #ffffff; background-color: #2563eb; border: none; border-radius: 8px; cursor: pointer; transition: background-color 0.2s ease; }
        .submit-btn:hover { background-color: #1d4ed8; }
        .cancel-btn { flex-grow: 1; text-align: center; padding: 15px; font-size: 16px; font-weight: bold; color: #333; background-color: #e5e7eb; border: none; border-radius: 8px; cursor: pointer; transition: background-color 0.2s ease; }
        .cancel-btn:hover { background-color: #d1d5db; }
        .form-container { background-color: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 100%; max-width: 1200px; margin: 115px auto;  }
    </style>
</head>
<body>
    
    <header class="top-header">
        <div class="header-title">Payroll System</div>
    </header>

    <aside class="sidebar">
        <div class="sidebar-profile">
            <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="User Avatar">
            <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
        </div>
        <div class="sidebar-nav-title">NAVIGATION</div>
        <nav class="sidebar-nav">
            <ul>
                <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <a href="index.php"><i class="fas fa-edit icon"></i>Data Entry</a>
                </li>
                <li class="active"> <a href="view-records.php"><i class="fas fa-table-list icon"></i>View Records</a>
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
        <h2>Edit Payroll Record</h2>
        <form action="edit-record.php" method="POST">
            <div class="form-grid">
                <div class="form-group grid-col-span-4">
                    <label for="payrollMonth">Payroll Month:</label>
                    <input type="month" id="payrollMonth" name="payroll_month" value="<?php echo date('Y-m', strtotime($record['payroll_month'])); ?>" required>
                </div>

                <div class="form-group grid-col-span-2">
                    <label for="payrollNumber">Payroll Number:</label>
                    <input type="text" id="payrollNumber" name="payroll_number" placeholder="e.g., PN-10234" value="<?php echo htmlspecialchars($record['payroll_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="employee_id">Employee ID Number:</label>
                    <input type="text" id="employee_id" name="employee_id" placeholder="e.g., 2023-001" value="<?php echo htmlspecialchars($record['employee_id']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="appointment_number">Appointment Number:</label>
                    <input type="text" id="appointment_number" name="appointment_number" placeholder="e.g., AP-0525" value="<?php echo htmlspecialchars($record['appointment_number']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="lastName">Last Name:</label>
                    <input type="text" id="lastName" name="last_name" placeholder="e.g., Dela Cruz" value="<?php echo htmlspecialchars($record['last_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="firstName">First Name:</label>
                    <input type="text" id="firstName" name="first_name" placeholder="e.g., Juan" value="<?php echo htmlspecialchars($record['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="middleName">Middle Name:</label>
                    <input type="text" id="middleName" name="middle_name" placeholder="Optional" value="<?php echo htmlspecialchars($record['middle_name']); ?>">
                </div>
                <div class="form-group">
                    <label for="suffix">Suffix:</label>
                    <input type="text" id="suffix" name="suffix" placeholder="e.g., Jr." value="<?php echo htmlspecialchars($record['suffix']); ?>">
                </div>

                <div class="form-group grid-col-span-4">
                    <label for="department">Department:</label>
                    <input type="text" id="department" name="department" placeholder="e.g., HR" value="<?php echo htmlspecialchars($record['department']); ?>" required>
                </div>
                <div class="form-group grid-col-span-2">
                    <label for="sss">SSS:</label>
                    <input type="number" id="sss" name="sss" step="0.01" placeholder="e.g., 585.00" value="<?php echo htmlspecialchars($record['sss']); ?>" required>
                </div>
                <div class="form-group grid-col-span-2">
                    <label for="amount">Amount:</label>
                    <input type="number" id="amount" name="amount" step="0.01" placeholder="e.g., 50000.00" value="<?php echo htmlspecialchars($record['amount']); ?>" required>
                </div>
                
                <input type="hidden" name="id" value="<?php echo $record['id']; ?>"/>
                
                <div class="form-buttons">
                    <button type="submit" name="update_record" class="submit-btn">Save Changes</button>
                    <a href="view-records.php" class="cancel-btn">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</main> 

</body>
</html>