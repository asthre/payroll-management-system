<?php
session_start();

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('location: index.php');
    exit;
}

// --- Database Configuration ---
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

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if (empty($username) || empty($password)) {
        $login_error = 'Please enter both username and password.';
    } else {
        $sql = "SELECT id, username, password, status, profile_picture FROM users WHERE username = :username";
        
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":username", $username, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $hashed_password = $row["password"];
                        
                        if (password_verify($password, $hashed_password)) {
                            if ($row['status'] === 'active') {
                                session_regenerate_id(true);
                                
                                // Set session variables
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $row["id"];
                                $_SESSION["username"] = $row["username"];
                                $_SESSION["profile_picture"] = $row["profile_picture"];
                                
                                // --- ADDED: SIGN-IN LOGGING ---
                                try {
    $log_sql = "INSERT INTO activity_logs (user_id, username, action) VALUES (?, ?, ?)";
    $log_stmt = $pdo->prepare($log_sql);
    $log_stmt->execute([
        $row["id"], 
        $row["username"], 
        'Sign In'
    ]);
} catch (PDOException $e) {
    // Optional: Handle logging error
}
                                
                                header("location: index.php");
                                exit;
                            } else {
                                $login_error = 'Your account is inactive.';
                            }
                        } else {
                            $login_error = 'The password you entered was not valid.';
                        }
                    }
                } else {
                    $login_error = 'No account found with that username.';
                }
            } else {
                echo "Oops! Something went wrong.";
            }
            unset($stmt);
        }
    }
    unset($pdo);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .login-container { background-color: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; text-align: center; }
        .login-container h1 { font-size: 28px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        .login-container .subtitle { font-size: 16px; color: #6b7280; margin-bottom: 25px; }
        .error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: left; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 14px; }
        .input-wrapper { position: relative; }
        .input-wrapper .icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .input-wrapper .icon-toggle { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; cursor: pointer; }
        .form-group input { width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        .form-group input#password { padding-right: 40px; }
        .form-group input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        .signin-btn { width: 100%; padding: 14px; font-size: 16px; font-weight: bold; color: #ffffff; background-color: #2563eb; border: none; border-radius: 8px; cursor: pointer; transition: background-color 0.2s ease; margin-top: 10px; }
        .signin-btn:hover { background-color: #1d4ed8; }
    </style>
</head>
<body>

    <div class="login-container">
        <h1>Payroll System</h1>
        <p class="subtitle">Please enter your details.</p>

        <?php 
        if (!empty($login_error)) {
            echo '<div class="error-message">' . htmlspecialchars($login_error) . '</div>';
        }
        ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user icon"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock icon"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <i class="fas fa-eye icon-toggle" id="togglePassword"></i>
                </div>
            </div>

            <button type="submit" class="signin-btn">Sign in</button>
        </form>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>

</body>
</html>