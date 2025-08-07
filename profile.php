<?php
session_start();
require_once 'config/database.php';
require_once 'controllers/UserController.php';
require_once 'controllers/DepartmentController.php';

// Initialize controllers
$userController = new UserController($conn);
$departmentController = new DepartmentController($conn);

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Get user details
$user = $userController->getUserById($_SESSION["user_id"]);

// Get user's department
$department = $departmentController->getById($user['department_id']);

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $userData = [
        'id' => $_SESSION["user_id"],
        'username' => trim($_POST["username"]),
        'current_password' => trim($_POST["current_password"]),
        'new_password' => trim($_POST["new_password"]),
        'confirm_password' => trim($_POST["confirm_password"])
    ];

    if($userController->updateProfile($userData)){
        $_SESSION["username"] = $userData['username'];
        header("location: profile.php?success=1");
        exit;
    }
}

// Include header
include 'includes/components/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Profile</h1>
            </div>

            <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">
                Profile updated successfully!
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">User Information</h6>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <div class="form-group mb-3">
                                    <label>Username</label>
                                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label>Role</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                                </div>
                                <div class="form-group mb-3">
                                    <label>Department</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($department['name']); ?>" readonly>
                                </div>
                                <div class="form-group mb-3">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" class="form-control">
                                    <small class="form-text text-muted">Leave blank if you don't want to change password</small>
                                </div>
                                <div class="form-group mb-3">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Account Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card border-left-primary shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        Total Tasks</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $user['total_tasks']; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card border-left-success shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                        Completed Tasks</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $user['completed_tasks']; ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php 
// Include footer scripts
include 'includes/components/scripts.php';
?> 