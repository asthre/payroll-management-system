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

// --- BASIC VARIABLES ---
$current_page = basename($_SERVER['PHP_SELF']);

// Initialize variables for the view
$records = [];
$total_pages = 0;
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$search_category = isset($_GET['category']) ? $_GET['category'] : 'name';
$search_performed = isset($_GET['query']); 

// --- NEW: RESULTS PER PAGE LOGIC ---
$allowed_per_page = [10, 25, 50, 100];
$results_per_page = isset($_GET['per_page']) && in_array($_GET['per_page'], $allowed_per_page) ? (int)$_GET['per_page'] : 10;

// Sorting
$allowed_sort = ['payroll_month', 'payroll_number', 'last_name', 'first_name', 'amount'];
$sort_by = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) && strtoupper($_GET['order']) == 'ASC' ? 'ASC' : 'DESC';
$next_order = $sort_order == 'ASC' ? 'DESC' : 'ASC';

if ($search_performed) {
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit_start = ($page - 1) * $results_per_page;
    
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
            case 'appointment_no':
                $sql_where = " WHERE appointment_number LIKE :query";
                $params[':query'] = '%' . $search_query . '%';
                break;
        }
    }

    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM payroll_entries $sql_where");
    $stmt_count->execute($params);
    $total_results = $stmt_count->fetchColumn();
    $total_pages = ceil($total_results / $results_per_page);

    $sql = "SELECT * FROM payroll_entries $sql_where 
            ORDER BY $sort_by $sort_order 
            LIMIT :start, :limit";
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':start', (int)$limit_start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$results_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $entries_displayed = count($records);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Records - Payroll System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="records.css"> 
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
    <div class="content-container">
        <div class="content-header">
            <h2>Payroll Records</h2>
            <a href="export-csv.php?query=<?php echo urlencode($search_query); ?>&category=<?php echo $search_category; ?>" class="csv-btn">
                <i class="fas fa-file-csv"></i> Print CSV
            </a>
        </div>

        <form action="view-records.php" method="GET" class="filter-section">
            <div class="unified-search-container">
                <div class="search-category">
                    <select name="category" id="searchCategory">
                        <option value="name" <?php if ($search_category == 'name') echo 'selected'; ?>>Name</option>
                        <option value="appointment_no" <?php if ($search_category == 'appointment_no') echo 'selected'; ?>>Appointment No.</option>
                        <option value="payroll_no" <?php if ($search_category == 'payroll_no') echo 'selected'; ?>>Payroll No.</option>
                        <option value="month" <?php if ($search_category == 'month') echo 'selected'; ?>>Month</option>
                    </select>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </div>
                <input type="search" name="query" id="searchInput" placeholder="Search..." class="search-input" value="<?php echo htmlspecialchars($search_query); ?>" required>
                <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
            </div>
        </form>

<?php if ($search_performed): ?>
                <div class="page-controls">
                <form action="view-records.php" method="GET" class="per-page-selector">
                    <label for="per_page">Show</label>
                    <select name="per_page" id="per_page" onchange="this.form.submit()">
                        <?php foreach ($allowed_per_page as $option): ?>
                            <option value="<?php echo $option; ?>" <?php if ($results_per_page == $option) echo 'selected'; ?>><?php echo $option; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span>entries</span>
                    <input type="hidden" name="query" value="<?php echo htmlspecialchars($search_query); ?>">
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($search_category); ?>">
                </form>
    <div class="record-count">
    Total Results Found: <strong><?php echo $total_results; ?></strong>
</div>
<?php endif; ?>


        <?php if ($search_performed): ?>
            <div class="table-wrapper">
    <table>


                <thead>
                    <tr>
                        <?php
                        // ADDED 'suffix' to the headers array
                         $headers = [
                                'payroll_month' => 'Payroll Month',
                                'payroll_number' => 'Payroll Number',
                                'employee_id' => 'Employee ID',
                                'appointment_number' => 'Appointment No.',
                                'last_name' => 'Last Name',
                                'first_name' => 'First Name',
                                'middle_name' => 'Middle Name',
                                'suffix' => 'Suffix', 
                                'department' => 'Department',
                                'sss' => 'SSS',
                                'amount' => 'Amount' ];

                        foreach ($headers as $field => $label): 
                            if(in_array($field, $allowed_sort)) {
                                $active = ($sort_by == $field) ? 'active' : '';
                                $icon = ($sort_by == $field) ? ($sort_order=='ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>') : '<i class="fas fa-sort"></i>';
                                // UPDATED: Sorting links now include the per_page parameter
                                echo "<th><a href='?sort=$field&order=$next_order&query=".urlencode($search_query)."&category=$search_category&per_page=$results_per_page' class='sortable-header $active'>$label $icon</a></th>";
                            } else {
                                echo "<th>$label</th>";
                            }
                        endforeach;
                        ?>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($records) > 0): ?>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('F Y', strtotime($record['payroll_month']))); ?></td>
                                    <td><?php echo htmlspecialchars($record['payroll_number']); ?></td>
                                    <td><?php echo htmlspecialchars($record['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($record['appointment_number']); ?></td>
                                    <td><?php echo htmlspecialchars($record['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['middle_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['suffix']); ?></td>
                                    <td><?php echo htmlspecialchars($record['department']); ?></td>
                                    <td>₱<?php echo htmlspecialchars(number_format($record['sss'], 2)); ?></td>
                                    <td>₱<?php echo htmlspecialchars(number_format($record['amount'], 2)); ?></td>
                                <td class="action-buttons">
                                    <a href="edit-record.php?id=<?php echo $record['id']; ?>" class="edit-btn">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="no-records">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>


                </table>
        <div class="pagination">
                <?php
                if ($total_pages > 1) {
                    $base_url = "?query=".urlencode($search_query)."&category=$search_category&sort=$sort_by&order=$sort_order&per_page=$results_per_page";
                    $range = 2;
                    
                    echo "<a href='{$base_url}&page=1' class='" . ($page <= 1 ? 'disabled' : '') . "'>&laquo;</a>";
                    echo "<a href='{$base_url}&page=" . ($page - 1) . "' class='" . ($page <= 1 ? 'disabled' : '') . "'>&lsaquo;</a>";
                    
                    for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++) {
                        if ($i == $page) {
                            echo "<span class='active'>$i</span>";
                        } else {
                            echo "<a href='{$base_url}&page=$i'>$i</a>";
                        }
                    }

                    echo "<a href='{$base_url}&page=" . ($page + 1) . "' class='" . ($page >= $total_pages ? 'disabled' : '') . "'>&rsaquo;</a>";
                    echo "<a href='{$base_url}&page=$total_pages' class='" . ($page >= $total_pages ? 'disabled' : '') . "'>&raquo;</a>";
                }
                ?>
            </div>

        <?php else: ?>
            <div class="initial-message">
                <p>Please use the search bar to find payroll records.</p>
            </div>
        <?php endif; ?>
    </div>


</main>

<script>
    const categorySelect = document.getElementById('searchCategory');
    const searchInput = document.getElementById('searchInput');

    function updateSearchInput() {
        if (categorySelect.value === 'month') {
            searchInput.type = 'month';
            searchInput.placeholder = '';
        } else {
            searchInput.type = 'search';
            searchInput.placeholder = 'Search...';
        }
    }
    
    updateSearchInput();
    categorySelect.addEventListener('change', updateSearchInput);
</script>

</body>
</html>