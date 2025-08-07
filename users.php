<?php
session_start();
require_once "database/connection.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: login.php");
    exit;
}

// Initialize variables and error handling
$result = false;
$users = [];

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['create_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
        
        // Check if username or email already exists
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        if($check_stmt = mysqli_prepare($conn, $check_sql)){
            mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
            if(mysqli_stmt_execute($check_stmt)){
                $result = mysqli_stmt_get_result($check_stmt);
                if(mysqli_num_rows($result) > 0){
                    $_SESSION['error_message'] = "Username or email already exists.";
                } else {
                    // Create new user
                    $sql = "INSERT INTO users (username, email, password, role, department_id) VALUES (?, ?, ?, ?, ?)";
                    if($insert_stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($insert_stmt, "ssssi", $username, $email, $password, $role, $department_id);
                        if(mysqli_stmt_execute($insert_stmt)){
                            $_SESSION['success_message'] = "User created successfully.";
                        } else {
                            $_SESSION['error_message'] = "Something went wrong. Please try again later. " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($insert_stmt);
                    }
                }
            }
            mysqli_stmt_close($check_stmt);
        }
    }

    if (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
        
        // Check if username or email already exists for other users
        $check_sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        if($check_stmt = mysqli_prepare($conn, $check_sql)){
            mysqli_stmt_bind_param($check_stmt, "ssi", $username, $email, $user_id);
            if(mysqli_stmt_execute($check_stmt)){
                $result = mysqli_stmt_get_result($check_stmt);
                if(mysqli_num_rows($result) > 0){
                    $_SESSION['error_message'] = "Username or email already exists.";
                } else {
                    // Update user
                    $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, department_id = ? WHERE id = ?";
                    if($update_stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($update_stmt, "ssssii", $username, $full_name, $email, $role, $department_id, $user_id);
                        if(mysqli_stmt_execute($update_stmt)){
                            $_SESSION['success_message'] = "User updated successfully.";
                        } else {
                            $_SESSION['error_message'] = "Something went wrong. Please try again later. " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($update_stmt);
                    }
                }
            }
            mysqli_stmt_close($check_stmt);
        }
    }
    
    // Handle delete user
    if (isset($_POST['delete']) && isset($_POST['id'])) {
        $user_id = $_POST['id'];
        
        // Delete the user
        $sql = "DELETE FROM users WHERE id = ?";
        if($delete_stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
            if(mysqli_stmt_execute($delete_stmt)){
                $_SESSION['success_message'] = "User deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Something went wrong. Please try again later. " . mysqli_error($conn);
            }
            mysqli_stmt_close($delete_stmt);
        }
    }
}

// Get all users with their department names - Simple approach first
$sql = "SELECT u.*, d.name as department_name 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.id 
        ORDER BY u.username";
$result = mysqli_query($conn, $sql);

// Debug output
if (!$result) {
    $_SESSION['error_message'] = "Error querying database: " . mysqli_error($conn);
} else {
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}

// Set page title for header
$page_title = "Users";

// Include header
include 'includes/components/header.php';
// Include sidebar
include 'includes/components/sidebar.php';
// Include modals
include 'includes/components/modals.php';
?>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <!-- Main Content -->
    <div id="content">
        <?php include 'includes/components/navbar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Users</h1>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createUserModal">
                    <i class="fas fa-plus"></i> Create User
                </button>
            </div>

            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Users Table -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Department</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                            <td><?php echo isset($user['department_name']) ? htmlspecialchars($user['department_name']) : 'Not Assigned'; ?></td>
                                            <td><?php echo isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?></td>
                                            <td>
                                                <?php if (isset($user['id'])): ?>
                                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#editUserModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No users found in the database.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->

    <?php include 'includes/components/footer.php'; ?>
</div>
<!-- End of Content Wrapper -->

<?php include 'includes/components/footer_scripts.php'; ?>

<!-- Initialize Modals -->
<?php
createUserModal();

// Check if we have users before trying to display modals
if (count($users) > 0) {
    // Add edit user modals and delete confirmation modals for each user
    foreach($users as $user) {
        if (isset($user['id'])) {
            editUserModal($user);
            deleteConfirmationModal($user['id'], 'user');
        }
    }
}
?> 