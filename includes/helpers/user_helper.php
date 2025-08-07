<?php
require_once __DIR__ . '/../../database/connection.php';

/**
 * Get user details by ID
 * @param int $user_id User ID
 * @param mysqli $conn Database connection
 * @return array|null User details or null if not found
 */
function getUserById($user_id, $conn) {
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $query = "SELECT u.*, d.department_name 
              FROM users u
              LEFT JOIN departments d ON u.department_id = d.department_id
              WHERE u.user_id = '$user_id'";
    
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

/**
 * Get user details by username
 * @param string $username Username
 * @param mysqli $conn Database connection
 * @return array|null User details or null if not found
 */
function getUserByUsername($username, $conn) {
    $username = mysqli_real_escape_string($conn, $username);
    $query = "SELECT u.*, d.department_name 
              FROM users u
              LEFT JOIN departments d ON u.department_id = d.department_id
              WHERE u.username = '$username'";
    
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

/**
 * Get users by department
 * @param int $department_id Department ID
 * @param mysqli $conn Database connection
 * @return array Array of users
 */
function getUsersByDepartment($department_id, $conn) {
    $department_id = mysqli_real_escape_string($conn, $department_id);
    $query = "SELECT u.*, d.department_name 
              FROM users u
              LEFT JOIN departments d ON u.department_id = d.department_id
              WHERE u.department_id = '$department_id'
              ORDER BY u.username";
    
    $users = [];
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
    return $users;
}

/**
 * Get users by role
 * @param string $role User role
 * @param mysqli $conn Database connection
 * @return array Array of users
 */
function getUsersByRole($role, $conn) {
    $role = mysqli_real_escape_string($conn, $role);
    $query = "SELECT u.*, d.department_name 
              FROM users u
              LEFT JOIN departments d ON u.department_id = d.department_id
              WHERE u.role = '$role'
              ORDER BY u.username";
    
    $users = [];
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
    return $users;
}

/**
 * Create a new user
 * @param array $user_data User data
 * @param mysqli $conn Database connection
 * @return int|false User ID if successful, false otherwise
 */
function createUser($user_data, $conn) {
    $username = mysqli_real_escape_string($conn, $user_data['username']);
    $password = password_hash($user_data['password'], PASSWORD_DEFAULT);
    $email = mysqli_real_escape_string($conn, $user_data['email']);
    $role = mysqli_real_escape_string($conn, $user_data['role']);
    $department_id = mysqli_real_escape_string($conn, $user_data['department_id']);
    
    $query = "INSERT INTO users (username, password, email, role, department_id, created_at)
              VALUES ('$username', '$password', '$email', '$role', '$department_id', NOW())";
    
    if (mysqli_query($conn, $query)) {
        return mysqli_insert_id($conn);
    }
    return false;
}

/**
 * Update user profile
 * @param int $user_id User ID
 * @param array $user_data User data to update
 * @param mysqli $conn Database connection
 * @return bool True if successful, false otherwise
 */
function updateUser($user_id, $user_data, $conn) {
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $updates = [];
    
    if (isset($user_data['email'])) {
        $email = mysqli_real_escape_string($conn, $user_data['email']);
        $updates[] = "email = '$email'";
    }
    
    if (isset($user_data['password'])) {
        $password = password_hash($user_data['password'], PASSWORD_DEFAULT);
        $updates[] = "password = '$password'";
    }
    
    if (isset($user_data['role'])) {
        $role = mysqli_real_escape_string($conn, $user_data['role']);
        $updates[] = "role = '$role'";
    }
    
    if (isset($user_data['department_id'])) {
        $department_id = mysqli_real_escape_string($conn, $user_data['department_id']);
        $updates[] = "department_id = '$department_id'";
    }
    
    if (!empty($updates)) {
        $updates[] = "updated_at = NOW()";
        $update_str = implode(", ", $updates);
        $query = "UPDATE users SET $update_str WHERE user_id = '$user_id'";
        return mysqli_query($conn, $query);
    }
    return false;
}

/**
 * Delete user
 * @param int $user_id User ID
 * @param mysqli $conn Database connection
 * @return bool True if successful, false otherwise
 */
function deleteUser($user_id, $conn) {
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $query = "DELETE FROM users WHERE user_id = '$user_id'";
    return mysqli_query($conn, $query);
}

/**
 * Verify user password
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches, false otherwise
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user has specific role
 * @param int $user_id User ID
 * @param string $role Role to check
 * @param mysqli $conn Database connection
 * @return bool True if user has role, false otherwise
 */
function hasRole($user_id, $role, $conn) {
    $user = getUserById($user_id, $conn);
    return $user && $user['role'] === $role;
} 