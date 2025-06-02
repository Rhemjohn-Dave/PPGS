<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'tup_ppgs_tasks');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if(!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if(mysqli_query($conn, $sql)) {
    // Close the initial connection
    mysqli_close($conn);
    
    // Connect to the specific database
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check the second connection
    if(!$conn) {
        die("Connection to database failed: " . mysqli_connect_error());
    }
    
    // Set charset to utf8mb4
    if(!mysqli_set_charset($conn, "utf8mb4")) {
        die("Error setting charset: " . mysqli_error($conn));
    }
} else {
    die("Error creating database: " . mysqli_error($conn));
}
?> 