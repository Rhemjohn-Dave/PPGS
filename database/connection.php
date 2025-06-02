<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'tup_ppgs_tasks';

// Create connection
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to safely escape strings
function escape($str) {
    global $conn;
    return mysqli_real_escape_string($conn, $str);
}

// Function to execute query and return result
function query($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("Query failed: " . mysqli_error($conn) . "\nSQL: " . $sql);
        return false;
    }
    return $result;
}

// Function to fetch single row
function fetch($result) {
    return mysqli_fetch_assoc($result);
}

// Function to fetch all rows
function fetchAll($result) {
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// Function to get last inserted ID
function lastInsertId() {
    global $conn;
    return mysqli_insert_id($conn);
}

// Function to get number of affected rows
function affectedRows() {
    global $conn;
    return mysqli_affected_rows($conn);
}

// Function to begin transaction
function beginTransaction() {
    global $conn;
    mysqli_begin_transaction($conn);
}

// Function to commit transaction
function commit() {
    global $conn;
    mysqli_commit($conn);
}

// Function to rollback transaction
function rollback() {
    global $conn;
    mysqli_rollback($conn);
}

// Function to close connection
function closeConnection() {
    global $conn;
    // Check if connection exists and is open before closing
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_errno) {
        mysqli_close($conn);
    }
}

// Register shutdown function to close connection
register_shutdown_function('closeConnection');
?> 