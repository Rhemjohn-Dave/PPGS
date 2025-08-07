<?php
session_start();
require_once "config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: login.php");
    exit;
}

// Fix any inconsistencies in task requests database
// This ensures any task that has an entry in the tasks table is marked as assigned
try {
    // Find task requests that have tasks but are not marked as assigned
    $cleanup_sql = "UPDATE task_requests tr 
                    SET tr.status = 'assigned' 
                    WHERE EXISTS (SELECT 1 FROM tasks t WHERE t.request_id = tr.id) 
                    AND tr.status != 'assigned'";
    
    if(mysqli_query($conn, $cleanup_sql)) {
        $affected = mysqli_affected_rows($conn);
        if($affected > 0) {
            $_SESSION['info_message'] = "Fixed $affected task requests with inconsistent status.";
        }
    }
} catch (Exception $e) {
    // Just log the error, don't interrupt user workflow
    error_log("Error cleaning up task requests: " . $e->getMessage());
}

// Handle task assignment
if(isset($_POST['assign_task']) && isset($_POST['request_id']) && isset($_POST['staff_id'])) {
    $request_id = $_POST['request_id'];
    $staff_id = $_POST['staff_id'];
    $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : null;
    
    // Validate inputs
    if(empty($request_id) || empty($staff_id)) {
        $_SESSION['error_message'] = "Please select a staff member.";
        header("location: task_requests.php");
        exit;
    }
    
    // Check if request exists and is approved
    $sql = "SELECT title, requester_id, status, program_head_approval, adaa_approval 
            FROM task_requests WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $request_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $request = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if(!$request) {
            $_SESSION['error_message'] = "Request not found.";
            header("location: task_requests.php");
            exit;
        }
        
        if($request['status'] == 'assigned') {
            $_SESSION['error_message'] = "This request has already been assigned.";
            header("location: task_requests.php");
            exit;
        }
        
        // Check if a task already exists for this request
        $task_check_sql = "SELECT id FROM tasks WHERE request_id = ?";
        if($task_check_stmt = mysqli_prepare($conn, $task_check_sql)) {
            mysqli_stmt_bind_param($task_check_stmt, "i", $request_id);
            mysqli_stmt_execute($task_check_stmt);
            $task_check_result = mysqli_stmt_get_result($task_check_stmt);
            
            if(mysqli_num_rows($task_check_result) > 0) {
                mysqli_stmt_close($task_check_stmt);
                $_SESSION['error_message'] = "This request already has a task assigned to it.";
                header("location: task_requests.php");
                exit;
            }
            mysqli_stmt_close($task_check_stmt);
        }
        
        if($request['program_head_approval'] != 'approved' || $request['adaa_approval'] != 'approved') {
            $_SESSION['error_message'] = "This request has not been fully approved.";
            header("location: task_requests.php");
            exit;
        }
    }
    
    // Check staff availability
    $sql = "SELECT COUNT(*) as task_count FROM tasks 
            WHERE assigned_to = ? AND status IN ('pending', 'in_progress')";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $staff_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $staff_tasks = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if($staff_tasks['task_count'] >= 5) {
            $_SESSION['error_message'] = "Selected staff member has too many pending tasks.";
            header("location: task_requests.php");
            exit;
        }
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update task request status
        $sql = "UPDATE task_requests SET status = 'assigned' WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $request_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        // Create task
        $sql = "INSERT INTO tasks (request_id, assigned_to, status, priority, due_date) VALUES (?, ?, 'pending', ?, ?)";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iiss", $request_id, $staff_id, $_POST['priority'], $due_date);
            mysqli_stmt_execute($stmt);
            $task_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
        }
        
        // Send notifications
        require_once "includes/helpers/notification_helper.php";
        
        // Notify staff
        $staff_message = "You have been assigned a new task: " . htmlspecialchars($request['title']);
        $staff_link = "tasks.php?view_task=" . $task_id;
        sendNotification([$staff_id], $staff_message, $conn, $staff_link);
        
        // Notify requester
        $requester_message = "Your task '" . htmlspecialchars($request['title']) . "' has been assigned to a staff member.";
        $requester_link = "tasks.php?view_request=" . $request_id;
        sendNotification([$request['requester_id']], $requester_message, $conn, $requester_link);
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success_message'] = "Task assigned successfully.";
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Error assigning task: " . $e->getMessage();
    }
    
    header("location: task_requests.php");
    exit;
}

// Get all task requests with requester and department information
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'approved';

// Make sure we're ALWAYS filtering out assigned tasks unless specifically requested
$status_condition = "";
if ($status_filter === 'pending') {
    $status_condition = "WHERE (tr.program_head_approval = 'pending' OR tr.adaa_approval = 'pending') AND tr.status != 'assigned'";
} elseif ($status_filter === 'approved') {
    $status_condition = "WHERE tr.program_head_approval = 'approved' AND tr.adaa_approval = 'approved' AND tr.status != 'assigned'";
} elseif ($status_filter === 'assigned') {
    $status_condition = "WHERE tr.status = 'assigned'";
} elseif ($status_filter === 'rejected') {
    $status_condition = "WHERE (tr.program_head_approval = 'rejected' OR tr.adaa_approval = 'rejected') AND tr.status != 'assigned'";
} else {
    // Default "all" view should still exclude assigned tasks
    $status_condition = "WHERE tr.status != 'assigned'";
}

// Always ensure we're filtering out the correct tasks
$sql = "SELECT tr.*, u.username as requester_name, u.full_name as requester_full_name, u.email as requester_email,
        d.name as department_name,
        CASE 
            WHEN EXISTS (SELECT 1 FROM tasks t WHERE t.request_id = tr.id) THEN 'assigned'
            ELSE tr.status 
        END as effective_status
        FROM task_requests tr
        JOIN users u ON tr.requester_id = u.id
        JOIN departments d ON tr.department_id = d.id
        $status_condition
        ORDER BY tr.created_at DESC";

$requests_result = mysqli_query($conn, $sql);
$total_requests = mysqli_num_rows($requests_result);

// Get all staff members for assignment
$sql = "SELECT id, username, full_name FROM users WHERE role = 'staff' ORDER BY full_name, username";
$staff_result = mysqli_query($conn, $sql);

// Set page title
$page_title = "Task Requests";

// Include header and sidebar
include 'includes/components/header.php';
include 'includes/components/sidebar.php';
include 'includes/components/modals.php';
?>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <!-- Main Content -->
    <div id="content">
        <?php include 'includes/components/navbar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['info_message'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['info_message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['info_message']); ?>
            <?php endif; ?>

            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    Task Requests 
                    <?php if($status_filter !== 'all'): ?>
                        <span class="badge badge-primary"><?php echo ucfirst($status_filter); ?></span>
                    <?php endif; ?>
                    <span class="badge badge-secondary"><?php echo $total_requests; ?> requests</span>
                </h1>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Filter by Status
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item <?php echo $status_filter === 'all' ? 'active' : ''; ?>" href="?status=all">All Requests</a>
                        <a class="dropdown-item <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" href="?status=pending">Pending Approval</a>
                        <a class="dropdown-item <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" href="?status=approved">Approved (Ready to Assign)</a>
                        <a class="dropdown-item <?php echo $status_filter === 'assigned' ? 'active' : ''; ?>" href="?status=assigned">Assigned</a>
                        <a class="dropdown-item <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" href="?status=rejected">Rejected</a>
                    </div>
                </div>
            </div>

            <!-- Alert for explanation -->
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                <strong>Note:</strong> When a task is assigned to a staff member, it is moved from the request list to the tasks list. 
                Assigned requests can be viewed by selecting "Assigned" in the filter dropdown.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Content Row -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <?php if($total_requests == 0): ?>
                                <div class="alert alert-info">
                                    No task requests found for the selected filter. 
                                    <?php if($status_filter !== 'all'): ?>
                                        <a href="?status=all" class="alert-link">View all requests</a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Requester</th>
                                                <th>Department</th>
                                                <th>Status</th>
                                                <th>Program Head</th>
                                                <th>ADAA</th>
                                                <th>Requested At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($request = mysqli_fetch_assoc($requests_result)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($request['title']); ?></td>
                                                    <td>
                                                    <?php echo htmlspecialchars($request['requester_full_name']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($request['department_name']); ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = "";
                                                        switch($request['effective_status']) {
                                                            case "pending":
                                                                $status_class = "badge-warning";
                                                                break;
                                                            case "dean_adaa_approved":
                                                                $status_class = "badge-success";
                                                                break;
                                                            case "assigned":
                                                                $status_class = "badge-info";
                                                                break;
                                                            case "rejected":
                                                                $status_class = "badge-danger";
                                                                break;
                                                            default:
                                                                $status_class = "badge-secondary";
                                                        }
                                                        echo "<span class='badge " . $status_class . "'>" . htmlspecialchars($request['effective_status']) . "</span>";
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $approval_class = "";
                                                        switch($request['program_head_approval']) {
                                                            case "approved":
                                                                $approval_class = "badge-success";
                                                                break;
                                                            case "pending":
                                                                $approval_class = "badge-warning";
                                                                break;
                                                            case "rejected":
                                                                $approval_class = "badge-danger";
                                                                break;
                                                            default:
                                                                $approval_class = "badge-secondary";
                                                        }
                                                        echo "<span class='badge " . $approval_class . "'>" . htmlspecialchars($request['program_head_approval']) . "</span>";
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $approval_class = "";
                                                        switch($request['adaa_approval']) {
                                                            case "approved":
                                                                $approval_class = "badge-success";
                                                                break;
                                                            case "pending":
                                                                $approval_class = "badge-warning";
                                                                break;
                                                            case "rejected":
                                                                $approval_class = "badge-danger";
                                                                break;
                                                            default:
                                                                $approval_class = "badge-secondary";
                                                        }
                                                        echo "<span class='badge " . $approval_class . "'>" . htmlspecialchars($request['adaa_approval']) . "</span>";
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewTaskRequestModal<?php echo $request['id']; ?>">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                        <?php viewTaskRequestModal($request); ?>
                                                        
                                                        <?php if($request['effective_status'] != 'assigned' && $request['program_head_approval'] == 'approved' && $request['adaa_approval'] == 'approved'): ?>
                                                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#assignTaskModal<?php echo $request['id']; ?>">
                                                                <i class="fas fa-user-plus"></i> Assign
                                                            </button>
                                                            
                                                            <!-- Assign Task Modal -->
                                                            <div class="modal fade" id="assignTaskModal<?php echo $request['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="assignTaskModalLabel<?php echo $request['id']; ?>" aria-hidden="true">
                                                                <div class="modal-dialog" role="document">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="assignTaskModalLabel<?php echo $request['id']; ?>">Assign Task</h5>
                                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                                <span aria-hidden="true">&times;</span>
                                                                            </button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <form id="assignTaskForm<?php echo $request['id']; ?>" method="post" action="task_requests.php">
                                                                                <input type="hidden" name="assign_task" value="1">
                                                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                                                <div class="form-group">
                                                                                    <label>Task Title</label>
                                                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($request['title']); ?>" readonly>
                                                                                </div>
                                                                                <div class="form-group">
                                                                                    <label>Requester</label>
                                                                                    <input type="text" class="form-control" value="<?php 
                                                                                    // Try various possible field names for requester
                                                                                    $requesterName = 'Unknown';
                                                                                    if (!empty($request['requester_full_name'])) {
                                                                                        $requesterName = $request['requester_full_name'];
                                                                                    } elseif (!empty($request['requester_name'])) {
                                                                                        $requesterName = $request['requester_name'];
                                                                                    } elseif (!empty($request['username'])) {
                                                                                        $requesterName = $request['username'];
                                                                                    }
                                                                                    echo htmlspecialchars($requesterName);
                                                                                    ?>" readonly>
                                                                                </div>
                                                                                <div class="form-group">
                                                                                    <label for="staff_id<?php echo $request['id']; ?>">Assign To</label>
                                                                                        <select class="form-control" id="staff_id<?php echo $request['id']; ?>" name="staff_id" required>
                                                                                            <option value="">Select Staff Member</option>
                                                                                            <?php 
                                                                                                mysqli_data_seek($staff_result, 0);
                                                                                                while($staff = mysqli_fetch_assoc($staff_result)): 
                                                                                                    $task_count = 0;
                                                                                                    $sql = "SELECT COUNT(*) as task_count FROM tasks WHERE assigned_to = ? AND status IN ('pending', 'in_progress')";
                                                                                                    if($stmt = mysqli_prepare($conn, $sql)) {
                                                                                                        mysqli_stmt_bind_param($stmt, "i", $staff['id']);
                                                                                                        mysqli_stmt_execute($stmt);
                                                                                                        $result = mysqli_stmt_get_result($stmt);
                                                                                                        $task_count = mysqli_fetch_assoc($result)['task_count'];
                                                                                                        mysqli_stmt_close($stmt);
                                                                                                    }
                                                                                            ?>
                                                                                                <option value="<?php echo $staff['id']; ?>" <?php echo $task_count >= 5 ? 'disabled' : ''; ?>>
                                                                                                    <?php echo htmlspecialchars($staff['full_name']); ?>
                                                                                                    <?php echo $task_count >= 5 ? ' (Too many tasks)' : ''; ?>
                                                                                                </option>
                                                                                            <?php endwhile; ?>
                                                                                        </select>
                                                                                        <small class="form-text text-muted">Staff members with 5 or more pending tasks are not available.</small>
                                                                                </div>
                                                                                
                                                                                <div class="form-group">
                                                                                    <label for="priority<?php echo $request['id']; ?>">Priority</label>
                                                                                    <select class="form-control" id="priority<?php echo $request['id']; ?>" name="priority" required>
                                                                                        <option value="">Select Priority</option>
                                                                                        <option value="low">Low</option>
                                                                                        <option value="medium">Medium</option>
                                                                                        <option value="high">High</option>
                                                                                    </select>
                                                                                </div>
                                                                                
                                                                                <div class="form-group">
                                                                                    <label for="due_date<?php echo $request['id']; ?>">Due Date</label>
                                                                                        <input type="date" class="form-control" id="due_date<?php echo $request['id']; ?>" name="due_date" min="<?php echo date('Y-m-d'); ?>" required>
                                                                                </div>
                                                                            </form>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                            <button type="submit" form="assignTaskForm<?php echo $request['id']; ?>" class="btn btn-primary">Assign</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
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

<script>
$(document).ready(function() {
    console.log('Document ready - task_requests.php');
    
    // Initialize DataTable
    $('#dataTable').DataTable({
        "order": [[7, "desc"]] // Sort by request date (Requested At column)
    });
});
</script>

<?php include 'includes/components/footer_scripts.php'; ?> 