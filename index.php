<?php
session_start();
require_once "config/database.php";
require_once 'controllers/UserController.php';
require_once 'controllers/TaskController.php';
require_once 'controllers/DepartmentController.php';

// Initialize controllers
$userController = new UserController($conn);
$taskController = new TaskController($conn);
$departmentController = new DepartmentController($conn);

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Initialize default values
$user = null;
$taskCounts = [
    'total' => 0,
    'completed' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'pending_confirmation' => 0,
    'rejected' => 0
];
$recentTasks = [];
$departments = [];

// Get user details if user_id is set
if(isset($_SESSION["user_id"])) {
    $user = $userController->getUserById($_SESSION["user_id"]);
    
    if($user) {
        // Get task counts based on user role
        if($user['role'] === 'admin'){
            // Get all task counts for admin
            $taskCounts = $taskController->getAllTaskCounts();
        } else if($user['role'] === 'program_head' || $user['role'] === 'program head'){
            // Get task counts for the department this program head manages
            $taskCounts = $taskController->getDepartmentTaskCounts($user['department_id']);
        } else if($user['role'] === 'adaa') {
            // ADAA can see all task counts (similar to admin)
            $taskCounts = $taskController->getAllTaskCounts();
        } else {
            // Get task counts assigned to this regular user - use prepared statement directly for more reliable results
            $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'pending_confirmation' THEN 1 ELSE 0 END) as pending_confirmation,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM tasks 
                WHERE assigned_to = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if($result && $row = mysqli_fetch_assoc($result)) {
                    // Force all values to be integers and handle NULL values
                    $taskCounts['total'] = isset($row['total']) ? (int)$row['total'] : 0;
                    $taskCounts['completed'] = isset($row['completed']) ? (int)$row['completed'] : 0;
                    $taskCounts['pending'] = isset($row['pending']) ? (int)$row['pending'] : 0;
                    $taskCounts['in_progress'] = isset($row['in_progress']) ? (int)$row['in_progress'] : 0;
                    $taskCounts['pending_confirmation'] = isset($row['pending_confirmation']) ? (int)$row['pending_confirmation'] : 0;
                    $taskCounts['rejected'] = isset($row['rejected']) ? (int)$row['rejected'] : 0;
                } else {
                    $_SESSION['error_message'] = "Error retrieving task counts: " . mysqli_error($conn);
                }
            }
        }

        // Get recent tasks based on user role
        if($user['role'] === 'admin' || $user['role'] === 'adaa'){
            // Admin and ADAA see all recent tasks (limited to 10)
            $query = "SELECT 
                        t.*, 
                        d.name as department_name, 
                        tr.title as request_title,
                        tr.requester_id,
                        tr.program_head_approval,
                        tr.adaa_approval,
                        tr.category,
                        requester.username as requester_username,
                        requester.full_name as requester_full_name,
                        assignee.username as assigned_to_username,
                        assignee.full_name as assigned_to_full_name
                     FROM tasks t
                     JOIN task_requests tr ON t.request_id = tr.id
                     JOIN departments d ON tr.department_id = d.id
                     LEFT JOIN users requester ON tr.requester_id = requester.id
                     LEFT JOIN users assignee ON t.assigned_to = assignee.id
                     ORDER BY t.created_at DESC LIMIT 10";
            
            error_log("Admin/ADAA SQL Query: " . $query);
            $result = mysqli_query($conn, $query);
            if($result) {
                $recentTasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
                // Debug logging
                error_log("Recent tasks data for admin/adaa: " . print_r($recentTasks, true));
                // Log specific fields for debugging
                foreach ($recentTasks as $task) {
                    error_log("Task ID: " . $task['id'] . 
                             ", Requester ID: " . $task['requester_id'] . 
                             ", Requester Name: " . $task['requester_full_name'] . 
                             ", Assigned To ID: " . $task['assigned_to'] . 
                             ", Assigned To Name: " . $task['assigned_to_full_name']);
                }
            } else {
                $_SESSION['error_message'] = "Error retrieving tasks: " . mysqli_error($conn);
                error_log("SQL Error: " . mysqli_error($conn));
            }
        } else if($user['role'] === 'program_head' || $user['role'] === 'program head'){
            // Program head sees tasks for their department
            $query = "SELECT 
                        t.*, 
                        d.name as department_name, 
                        tr.title as request_title,
                        tr.requester_id,
                        tr.program_head_approval,
                        tr.adaa_approval,
                        tr.category,
                        requester.username as requester_username,
                        requester.full_name as requester_full_name,
                        assignee.username as assigned_to_username,
                        assignee.full_name as assigned_to_full_name
                     FROM tasks t
                     JOIN task_requests tr ON t.request_id = tr.id
                     JOIN departments d ON tr.department_id = d.id
                     LEFT JOIN users requester ON tr.requester_id = requester.id
                     LEFT JOIN users assignee ON t.assigned_to = assignee.id
                     WHERE d.id = ?
                     ORDER BY t.created_at DESC LIMIT 10";
            error_log("Program Head SQL Query: " . $query);
            $stmt = mysqli_prepare($conn, $query);
            if ($stmt === false) {
                error_log("Failed to prepare statement: " . mysqli_error($conn));
                $_SESSION['error_message'] = "Error preparing statement: " . mysqli_error($conn);
                $recentTasks = [];
            } else {
                if (!mysqli_stmt_bind_param($stmt, "i", $user['department_id'])) {
                    error_log("Failed to bind parameters: " . mysqli_stmt_error($stmt));
                    $_SESSION['error_message'] = "Error binding parameters: " . mysqli_stmt_error($stmt);
                    mysqli_stmt_close($stmt);
                    $recentTasks = [];
                } else {
                    if (!mysqli_stmt_execute($stmt)) {
                        error_log("Failed to execute statement: " . mysqli_stmt_error($stmt));
                        $_SESSION['error_message'] = "Error executing statement: " . mysqli_stmt_error($stmt);
                        mysqli_stmt_close($stmt);
                        $recentTasks = [];
                    } else {
                        $result = mysqli_stmt_get_result($stmt);
                        if($result) {
                            $recentTasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
                            // Debug logging
                            error_log("Recent tasks data for program head: " . print_r($recentTasks, true));
                            // Log specific fields for debugging
                            foreach ($recentTasks as $task) {
                                error_log("Task ID: " . $task['id'] . 
                                         ", Requester ID: " . $task['requester_id'] . 
                                         ", Requester Name: " . $task['requester_full_name'] . 
                                         ", Assigned To ID: " . $task['assigned_to'] . 
                                         ", Assigned To Name: " . $task['assigned_to_full_name']);
                            }
                        } else {
                            $_SESSION['error_message'] = "Error retrieving department tasks: " . mysqli_error($conn);
                            error_log("SQL Error for program head: " . mysqli_error($conn));
                            $recentTasks = [];
                        }
                    }
                }
            }
        } else {
            // Regular user sees their assigned tasks and tasks they requested
            $query = "SELECT 
                        t.*, 
                        d.name as department_name, 
                        tr.title as request_title,
                        tr.requester_id,
                        tr.program_head_approval,
                        tr.adaa_approval,
                        tr.category,
                        requester.username as requester_username,
                        requester.full_name as requester_full_name,
                        assignee.username as assigned_to_username,
                        assignee.full_name as assigned_to_full_name
                     FROM tasks t
                     JOIN task_requests tr ON t.request_id = tr.id
                     LEFT JOIN departments d ON tr.department_id = d.id
                     LEFT JOIN users requester ON tr.requester_id = requester.id
                     LEFT JOIN users assignee ON t.assigned_to = assignee.id
                     WHERE t.assigned_to = ? OR tr.requester_id = ?
                     ORDER BY t.created_at DESC LIMIT 10";
            error_log("Regular User SQL Query: " . $query);
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $_SESSION["user_id"], $_SESSION["user_id"]);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if($result) {
                $recentTasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
                // Debug logging
                error_log("Recent tasks data for regular user: " . print_r($recentTasks, true));
                // Log specific fields for debugging
                foreach ($recentTasks as $task) {
                    error_log("Task ID: " . $task['id'] . 
                             ", Requester ID: " . $task['requester_id'] . 
                             ", Requester Name: " . $task['requester_full_name'] . 
                             ", Assigned To ID: " . $task['assigned_to'] . 
                             ", Assigned To Name: " . $task['assigned_to_full_name']);
                }
            } else {
                $_SESSION['error_message'] = "Error retrieving your tasks: " . mysqli_error($conn);
                error_log("SQL Error for regular user: " . mysqli_error($conn));
            }
        }

        // Get all departments for admin, program head and ADAA
        if($user['role'] === 'admin' || $user['role'] === 'adaa'){
            $departments = $departmentController->getAll();
        } else if($user['role'] === 'program_head' || $user['role'] === 'program head') {
            $departments = [$departmentController->getById($user['department_id'])];
        }
    }
}

// Set page title for header
$page_title = "Dashboard";

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
                <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                <?php if(isset($user) && is_array($user) && ($user['role'] === 'admin' || $user['role'] === 'program_head' || $user['role'] === 'program head' || $user['role'] === 'adaa')): ?>
                <a href="reports/department_report.php<?php echo ($user['role'] === 'program_head' || $user['role'] === 'program head') ? '?department_id='.$user['department_id'] : ''; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
                </a>
                <?php endif; ?>
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

            <!-- Task Count Cards -->
            <div class="row">
                <!-- Total Tasks Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Tasks</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo isset($taskCounts['total']) ? intval($taskCounts['total']) : 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completed Tasks Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Completed Tasks</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo isset($taskCounts['completed']) ? intval($taskCounts['completed']) : 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- In Progress Tasks Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        In Progress Tasks</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo isset($taskCounts['in_progress']) ? intval($taskCounts['in_progress']) : 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-spinner fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Tasks Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Pending Tasks</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo isset($taskCounts['pending']) ? intval($taskCounts['pending']) : 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Task Status Cards -->
            <div class="row">
                <!-- Pending Confirmation Tasks Card -->
                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card border-left-secondary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                        Pending Confirmation</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo isset($taskCounts['pending_confirmation']) ? intval($taskCounts['pending_confirmation']) : 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rejected Tasks Card -->
                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Rejected Tasks</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo isset($taskCounts['rejected']) ? intval($taskCounts['rejected']) : 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completion Progress Card -->
            <?php if(isset($taskCounts['total']) && $taskCounts['total'] > 0): ?>
            <div class="row">
                <div class="col-xl-12 col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Task Completion Rate</h6>
                        </div>
                        <div class="card-body">
                            <div class="progress progress-lg mb-2">
                                <?php 
                                // Safely calculate percentages, avoiding division by zero
                                $total = max(1, (int)($taskCounts['total'])); // Ensure we don't divide by zero
                                
                                // Make sure we have integer values for all counts
                                $completedCount = isset($taskCounts['completed']) ? (int)($taskCounts['completed']) : 0;
                                $inProgressCount = isset($taskCounts['in_progress']) ? (int)($taskCounts['in_progress']) : 0;
                                $pendingCount = isset($taskCounts['pending']) ? (int)($taskCounts['pending']) : 0;
                                $pendingConfirmationCount = isset($taskCounts['pending_confirmation']) ? (int)($taskCounts['pending_confirmation']) : 0;
                                $rejectedCount = isset($taskCounts['rejected']) ? (int)($taskCounts['rejected']) : 0;
                                
                                // Verify that the sum of all statuses equals the total (for debugging)
                                $sumOfStatuses = $completedCount + $inProgressCount + $pendingCount + $pendingConfirmationCount + $rejectedCount;
                                if ($sumOfStatuses != $total && $total > 0) {
                                    error_log("Sum of statuses ($sumOfStatuses) doesn't match total ($total) for user {$_SESSION['user_id']}");
                                }
                                
                                $completionRate = ($completedCount / $total) * 100;
                                $inProgressRate = ($inProgressCount / $total) * 100;
                                $pendingRate = ($pendingCount / $total) * 100;
                                $pendingConfirmationRate = ($pendingConfirmationCount / $total) * 100;
                                $rejectedRate = ($rejectedCount / $total) * 100;
                                ?>
                                <?php if($completedCount > 0): ?>
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completionRate; ?>%" 
                                    aria-valuenow="<?php echo $completionRate; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo round($completionRate); ?>% Completed
                                </div>
                                <?php endif; ?>
                                
                                <?php if($inProgressCount > 0): ?>
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $inProgressRate; ?>%" 
                                    aria-valuenow="<?php echo $inProgressRate; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo round($inProgressRate); ?>% In Progress
                                </div>
                                <?php endif; ?>
                                
                                <?php if($pendingCount > 0): ?>
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $pendingRate; ?>%" 
                                    aria-valuenow="<?php echo $pendingRate; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo round($pendingRate); ?>% Pending
                                </div>
                                <?php endif; ?>
                                
                                <?php if($pendingConfirmationCount > 0): ?>
                                <div class="progress-bar bg-secondary" role="progressbar" style="width: <?php echo $pendingConfirmationRate; ?>%" 
                                    aria-valuenow="<?php echo $pendingConfirmationRate; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo round($pendingConfirmationRate); ?>% Pending Confirmation
                                </div>
                                <?php endif; ?>
                                
                                <?php if($rejectedCount > 0): ?>
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $rejectedRate; ?>%" 
                                    aria-valuenow="<?php echo $rejectedRate; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo round($rejectedRate); ?>% Rejected
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="row text-center mt-3">
                                <div class="col">
                                    <span class="badge badge-success">Completed: <?php echo $completedCount; ?></span>
                                </div>
                                <div class="col">
                                    <span class="badge badge-warning">In Progress: <?php echo $inProgressCount; ?></span>
                                </div>
                                <div class="col">
                                    <span class="badge badge-info">Pending: <?php echo $pendingCount; ?></span>
                                </div>
                                <?php if($pendingConfirmationCount > 0): ?>
                                <div class="col">
                                    <span class="badge badge-secondary">Pending Confirmation: <?php echo $pendingConfirmationCount; ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if($rejectedCount > 0): ?>
                                <div class="col">
                                    <span class="badge badge-danger">Rejected: <?php echo $rejectedCount; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Tasks -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Tasks</h6>
                </div>
                <div class="card-body">
                    <?php if(empty($recentTasks)): ?>
                        <div class="text-center">
                            <p>No tasks found.</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Department</th>
                                    <th>Requested By</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentTasks as $task): ?>
                                <?php 
                                // Debug each task to see its structure
                                error_log("Task ID: " . ($task['id'] ?? 'unknown') . 
                                         ", Request ID: " . ($task['request_id'] ?? 'unknown') . 
                                         ", Assigned To ID: " . ($task['assigned_to'] ?? 'unknown') . 
                                         ", Is empty assigned_to_id: " . (empty($task['assigned_to_id']) ? 'true' : 'false') . 
                                         ", Is empty assigned_to: " . (empty($task['assigned_to']) ? 'true' : 'false'));
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($task['request_title'] ?? 'Untitled Task'); ?></td>
                                    <td><?php echo htmlspecialchars($task['department_name'] ?? 'N/A'); ?></td>
                                    <td>
                                    <?php
                                    // Try various possible field names for requester
                                    $requesterName = 'Unknown';
                                    if (!empty($task['requester_full_name'])) {
                                        $requesterName = $task['requester_full_name'];
                                    } elseif (!empty($task['requester_username'])) {
                                        $requesterName = $task['requester_username'];
                                    } elseif (!empty($task['requester_name'])) {
                                        $requesterName = $task['requester_name'];
                                    }
                                    echo htmlspecialchars($requesterName);
                                    ?>
                                    </td>
                                    <td>
                                    <?php
                                    // Try various possible field names for assigned staff
                                    $assignedToName = 'Unassigned';
                                    if (!empty($task['assigned_to_full_name'])) {
                                        $assignedToName = $task['assigned_to_full_name'];
                                    } elseif (!empty($task['assigned_to_username'])) {
                                        $assignedToName = $task['assigned_to_username'];
                                    } elseif (!empty($task['assigned_to_name'])) {
                                        $assignedToName = $task['assigned_to_name'];
                                    }
                                    echo htmlspecialchars($assignedToName);
                                    ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch($task['status']) {
                                            case 'completed':
                                                $statusClass = 'badge-success';
                                                break;
                                            case 'in_progress':
                                                $statusClass = 'badge-warning';
                                                break;
                                            case 'pending':
                                                $statusClass = 'badge-secondary';
                                                break;
                                            case 'pending_confirmation':
                                                $statusClass = 'badge-info';
                                                break;
                                            case 'rejected':
                                                $statusClass = 'badge-danger';
                                                break;
                                            default:
                                                $statusClass = 'badge-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                    <?php
                                    // Get priority value, checking multiple possible keys
                                    $priority = 'Normal';
                                    if (!empty($task['request_priority'])) {
                                        $priority = $task['request_priority'];
                                    } elseif (!empty($task['priority'])) {
                                        $priority = $task['priority'];
                                    }
                                    
                                    // Determine badge class based on priority
                                    $priorityClass = 'badge-info';
                                    if ($priority == 'high') {
                                        $priorityClass = 'badge-danger';
                                    } elseif ($priority == 'medium') {
                                        $priorityClass = 'badge-warning';
                                    }
                                    ?>
                                    <span class="badge <?php echo $priorityClass; ?>">
                                        <?php echo ucfirst($priority); ?>
                                    </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($task['due_date'])); ?></td>
                                    <td>
                                        <?php if (empty($task['assigned_to']) && !empty($task['request_id'])): ?>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                   onclick="viewTaskRequest(<?php echo $task['request_id']; ?>)"
                                                   data-toggle="tooltip" title="View Request Details">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                   onclick="viewTask(<?php echo $task['id']; ?>)"
                                                   data-toggle="tooltip" title="View Task Details">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if($task['status'] === 'pending_confirmation' && ($task['requester_id'] == $_SESSION['user_id'] || $_SESSION['role'] === 'admin')): ?>
                                        <button type="button" class="btn btn-success btn-sm confirm-task" 
                                                data-task-id="<?php echo $task['id']; ?>"
                                                data-status="completed"
                                                data-toggle="modal" data-target="#confirmTaskModal">
                                            <i class="fas fa-check"></i> Confirm
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
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

<!-- View Task Modal -->
<div class="modal fade" id="viewTaskModal" tabindex="-1" role="dialog" aria-labelledby="viewTaskModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTaskModalLabel">Task Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Task details will be loaded here via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- View Task Request Modal -->
<div class="modal fade" id="viewTaskRequestModal" tabindex="-1" role="dialog" aria-labelledby="viewTaskRequestModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTaskRequestModalLabel">Request Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Request details will be loaded here via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewTask(taskId) {
    // Show loading indicator
    $('#viewTaskModal .modal-body').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-3">Loading task details...</p></div>');
    $('#viewTaskModal').modal('show');
    
    // Fetch task details
    $.ajax({
        url: 'ajax/get_task.php',
        type: 'GET',
        data: { id: taskId },
        success: function(response) {
            try {
                $('#viewTaskModal .modal-body').html(response);
            } catch (e) {
                console.error('Error processing response:', e);
                $('#viewTaskModal .modal-body').html('<div class="alert alert-danger">Error loading task details: ' + e.message + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#viewTaskModal .modal-body').html('<div class="alert alert-danger">Error: ' + error + '</div>');
        }
    });
}

function viewTaskRequest(requestId) {
    // Show loading indicator
    $('#viewTaskRequestModal .modal-body').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-3">Loading request details...</p></div>');
    $('#viewTaskRequestModal').modal('show');
    
    // Fetch request details
    $.ajax({
        url: 'ajax/get_task_request.php',
        type: 'GET',
        data: { id: requestId },
        success: function(response) {
            try {
                $('#viewTaskRequestModal .modal-body').html(response);
            } catch (e) {
                console.error('Error processing response:', e);
                $('#viewTaskRequestModal .modal-body').html('<div class="alert alert-danger">Error loading request details: ' + e.message + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#viewTaskRequestModal .modal-body').html('<div class="alert alert-danger">Error: ' + error + '</div>');
        }
    });
}

$(document).ready(function() {
    // Handle task confirmation button click - for buttons that open the confirmation modal
    $(document).on('click', '.confirm-task[data-toggle="modal"]', function(e) {
        e.preventDefault();
        var taskId = $(this).data('task-id');
        var status = $(this).data('status') || 'completed';
        
        console.log('Setting up confirmation modal for task ID:', taskId, 'with status:', status);
        
        // Store the task ID and status in the confirmation button's data attributes
        $('#confirmTaskBtn').data('task-id', taskId);
        $('#confirmTaskBtn').data('status', status);
    });
    
    // Handle direct task confirmation - for buttons in task modals that don't open another modal
    $(document).on('click', '.confirm-task:not([data-toggle="modal"])', function(e) {
        e.preventDefault();
        var taskId = $(this).data('task-id');
        var status = $(this).data('status');
        
        console.log('Direct confirmation for task ID:', taskId, 'with status:', status);
        
        if (!taskId || !status) {
            console.error('Missing required data attributes:', { taskId, status });
            alert('Error: Missing required task information');
            return;
        }
        
        $.ajax({
            url: 'ajax/update_task_status.php',
            method: 'POST',
            dataType: 'json',
            data: {
                task_id: taskId,
                status: status
            },
            success: function(response) {
                console.log('Response:', response);
                if(response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('Error updating task status: ' + error);
            }
        });
    });

    // Handle final confirmation from the confirmation modal
    $('#confirmTaskBtn').click(function() {
        var taskId = $(this).data('task-id');
        var status = $(this).data('status') || 'completed';
        var notes = $('#completionNotes').val();
        
        console.log('Final confirmation for task ID:', taskId, 'with status:', status, 'and notes:', notes);
        
        if (!taskId) {
            console.error('Missing task ID in confirm button');
            alert('Error: Missing task ID');
            return;
        }
        
        $.ajax({
            url: 'ajax/update_task_status.php',
            method: 'POST',
            dataType: 'json',
            data: {
                task_id: taskId,
                status: status,
                notes: notes
            },
            success: function(response) {
                console.log('Response:', response);
                if(response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('Error updating task status: ' + error);
            }
        });
    });
});
</script>

<!-- Task Confirmation Modal -->
<div class="modal fade" id="confirmTaskModal" tabindex="-1" role="dialog" aria-labelledby="confirmTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmTaskModalLabel">Confirm Task Completion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to confirm this task as completed?</p>
                <div class="form-group">
                    <label for="completionNotes">Completion Notes (Optional)</label>
                    <textarea class="form-control" id="completionNotes" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmTaskBtn">Confirm Completion</button>
            </div>
        </div>
    </div>
</div>

