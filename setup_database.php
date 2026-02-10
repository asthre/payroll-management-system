<?php
// --- Database Configuration ---
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'payroll_db'); // Your database name

// Use plain text with newlines for terminal output
echo "--- Database Setup (Secure BCrypt) ---\n";

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    echo "SUCCESS: Database '" . DB_NAME . "' created or already exists.\n";
    
    // Select the database
    $pdo->exec("USE " . DB_NAME);

    // SQL to create the users table
    $sql_create_table = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        profile_picture VARCHAR(255) DEFAULT 'default_avatar.jpg' 
    );"; 
    
    $pdo->exec($sql_create_table);
    echo "SUCCESS: Table 'users' created or already exists.\n";

    // Delete any existing 'admin' user to ensure a clean insert
    // $pdo->exec("DELETE FROM users WHERE username = 'admin'");

    // Password to be hashed
    $plain_password = 'Cl4$$iC';
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

    // Insert the admin user with the secure hashed password
    $sql_insert_user = "INSERT INTO users (username, password) VALUES ('kevin', ?)";
    $stmt = $pdo->prepare($sql_insert_user);
    $stmt->execute([$hashed_password]);
    echo "SUCCESS: User with a secure BCrypt password has been created.\n";

    echo "\nSetup Complete! You can now log in.\n";

} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage() . "\n");
}
?>