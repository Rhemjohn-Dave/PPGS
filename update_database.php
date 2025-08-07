<?php
require_once 'database/connection.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Read the schema file
$schema = file_get_contents('database/schema.sql');

// Split the schema into individual queries
$queries = array_filter(array_map('trim', explode(';', $schema)));

// Execute each query
foreach ($queries as $query) {
    if (!empty($query)) {
        if (mysqli_query($conn, $query)) {
            echo "Query executed successfully: " . substr($query, 0, 50) . "...<br>";
        } else {
            echo "Error executing query: " . mysqli_error($conn) . "<br>";
            echo "Query: " . $query . "<br>";
        }
    }
}

echo "Database update completed.<br>";

// Close connection
mysqli_close($conn);

// Redirect back to settings page
header("Location: settings.php?message=" . urlencode("Database updated successfully."));
exit;
?> 