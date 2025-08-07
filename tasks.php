<?php
session_start();
require_once 'database/connection.php';
require_once 'controllers/TaskController.php';
require_once 'controllers/DepartmentController.php';
require_once 'controllers/UserController.php';

// Initialize controllers
$userController = new UserController($conn);
$taskController = new TaskController($conn);
$departmentController = new DepartmentController($conn);

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Initialize user variable
$user = null;

// Get user details if user_id is set
if (isset($_SESSION["user_id"])) {
    $user = $userController->getUserById($_SESSION["user_id"]);
} else if (isset($_SESSION["id"])) {
    // For backward compatibility with existing sessions
    $user = $userController->getUserById($_SESSION["id"]);
    // Set user_id for future consistency
    $_SESSION["user_id"] = $_SESSION["id"];
} else {
    // Redirect if neither user_id nor id is set
    $_SESSION['error_message'] = "User session is invalid. Please login again.";
    header("location: login.php");
    exit;
}

// Initialize tasks array
$tasks = [];

// Get tasks based on user role
if ($user && ($user['role'] === 'admin' || $user['role'] === 'adaa')) {
    // Admin and ADAA see all tasks
    $query = "SELECT t.*, d.name as department_name, u.username as assigned_to_name, 
                   u.full_name as assigned_to_full_name, tr.title as request_title,
                   tr.equipment_name, tr.department_id, t.priority, tr.category,
                   requester.username as requester_username, requester.full_name as requester_full_name
           FROM tasks t
           JOIN task_requests tr ON t.request_id = tr.id
           LEFT JOIN departments d ON tr.department_id = d.id
           LEFT JOIN users u ON t.assigned_to = u.id
           LEFT JOIN users requester ON tr.requester_id = requester.id
           ORDER BY t.created_at DESC";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        $_SESSION['error_message'] = "Error retrieving tasks: " . mysqli_error($conn);
    }
} else if ($user && ($user['role'] === 'program_head' || $user['role'] === 'program head')) {
    // Program head sees tasks for their department that they approved
    // First get the program head's department from the database
    $dept_sql = "SELECT department_id FROM users WHERE id = ?";
    $program_head_department_id = null;
    if ($dept_stmt = mysqli_prepare($conn, $dept_sql)) {
        mysqli_stmt_bind_param($dept_stmt, "i", $user['id']);
        mysqli_stmt_execute($dept_stmt);
        $dept_result = mysqli_stmt_get_result($dept_stmt);
        if ($dept_row = mysqli_fetch_assoc($dept_result)) {
            $program_head_department_id = $dept_row['department_id'];
        }
        mysqli_stmt_close($dept_stmt);
    }

    if ($program_head_department_id) {
        $query = "SELECT t.*, d.name as department_name, u.username as assigned_to_name,
                       u.full_name as assigned_to_full_name, tr.title as request_title,
                       tr.equipment_name, tr.department_id, tr.priority as request_priority, tr.category,
                       requester.username as requester_username, requester.full_name as requester_full_name
               FROM tasks t
               JOIN task_requests tr ON t.request_id = tr.id
               LEFT JOIN departments d ON tr.department_id = d.id
               LEFT JOIN users u ON t.assigned_to = u.id
               LEFT JOIN users requester ON tr.requester_id = requester.id
               WHERE tr.department_id = ? AND tr.program_head_approval = 'approved'
               ORDER BY t.created_at DESC";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $program_head_department_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            $_SESSION['error_message'] = "Error retrieving department tasks: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error_message'] = "Program head department not found.";
        $tasks = [];
    }
} else if ($user) {
    // Regular user sees their assigned tasks and tasks they requested
    $query = "SELECT t.*, d.name as department_name, 
                   u.username as assigned_to_name, u.full_name as assigned_to_full_name,
                   tr.title as request_title, tr.description as request_description,
                   tr.equipment_name, tr.department_id, tr.requester_id, tr.status as request_status,
                   tr.created_at as request_created_at, tr.updated_at as request_updated_at,
                   tr.category,
                   requester.username as requester_name, requester.full_name as requester_full_name
            FROM tasks t
            JOIN task_requests tr ON t.request_id = tr.id
            LEFT JOIN departments d ON tr.department_id = d.id
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN users requester ON tr.requester_id = requester.id
            WHERE t.assigned_to = ? OR tr.requester_id = ?
            ORDER BY t.created_at DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $_SESSION["user_id"], $_SESSION["user_id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        $_SESSION['error_message'] = "Error retrieving your tasks: " . mysqli_error($conn);
    }
}

// Get all departments for admin
$departments = [];
if ($user && $user['role'] === 'admin') {
    $departments = $departmentController->getAll();
}

// Get all users for admin
$users = [];
if ($user && $user['role'] === 'admin') {
    $users = $userController->getAllUsers();
}

// Set page title for header
$page_title = "Tasks";

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
                <h1 class="h3 mb-0 text-gray-800">Tasks Management</h1>
                <?php if (isset($user) && is_array($user) && $user['role'] === 'admin'): ?>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createTaskModal">
                        <i class="fas fa-plus fa-sm text-white-50"></i> Create New Task
                    </button>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
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

            <?php if (isset($_SESSION['error_message'])): ?>
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

            <!-- Tasks Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">All Tasks</h6>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary task-status-filter"
                            data-status="all">
                            All Tasks
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success task-status-filter"
                            data-status="completed">
                            Completed
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning task-status-filter"
                            data-status="in progress">
                            In Progress
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info task-status-filter"
                            data-status="pending">
                            Pending
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary task-status-filter"
                            data-status="pending confirmation">
                            Pending Confirmation
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary task-status-filter"
                            data-status="postponed">
                            Postponed
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger task-status-filter"
                            data-status="rejected">
                            Rejected
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Department</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Due Date</th>
                                    <th>Repair Frequency</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(isset($task['request_title']) ? $task['request_title'] : 'Untitled Task'); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(isset($task['department_name']) ? $task['department_name'] : 'No Department'); ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Try various possible field names for assigned staff
                                            $assignedToName = 'Unassigned';
                                            if (!empty($task['assigned_to_full_name'])) {
                                                $assignedToName = $task['assigned_to_full_name'];
                                            } elseif (!empty($task['assigned_to_name'])) {
                                                $assignedToName = $task['assigned_to_name'];
                                            } elseif (!empty($task['assigned_to_username'])) {
                                                $assignedToName = $task['assigned_to_username'];
                                            }
                                            echo htmlspecialchars($assignedToName);
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php
                                            echo ($task['status'] === 'completed' ? 'success' :
                                                ($task['status'] === 'in_progress' ? 'warning' :
                                                    ($task['status'] === 'pending_confirmation' ? 'secondary' :
                                                        ($task['status'] === 'postponed' ? 'secondary' :
                                                            ($task['status'] === 'rejected' ? 'danger' : 'info')))));
                                            ?>">
                                                <?php
                                                $status_display = str_replace('_', ' ', ucfirst($task['status']));
                                                if ($task['status'] === 'postponed' && !empty($task['postponement_reasons'])) {
                                                    $status_display .= ' - ' . ucfirst(str_replace('_', ' ', $task['postponement_reasons']));
                                                }
                                                echo $status_display;
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php
                                            // Get priority value from task table only
                                            $priority = $task['priority'] ?? 'medium';

                                            // Determine badge class based on priority
                                            echo $priority === 'high' ? 'danger' :
                                                ($priority === 'medium' ? 'warning' : 'info');
                                            ?>">
                                                <?php echo ucfirst($priority); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($task['due_date'])); ?></td>
                                        <td>
                                            <?php
                                            // Get repair frequency for this equipment in this department
                                            $equipment_name = isset($task['equipment_name']) ? $task['equipment_name'] : '';
                                            $department_id = isset($task['department_id']) ? $task['department_id'] : null;

                                            if ($department_id && !empty($equipment_name)) {
                                                // Debug information
                                                // echo "<!-- Debug: Equipment: " . htmlspecialchars($equipment_name) . ", Dept ID: " . $department_id . " -->";
                                        
                                                $repair_query = "SELECT COUNT(*) as repair_count 
                                                           FROM tasks t 
                                                           JOIN task_requests tr ON t.request_id = tr.id 
                                                           WHERE tr.department_id = ? 
                                                           AND LOWER(tr.equipment_name) LIKE LOWER(?) 
                                                           AND t.status IN ('completed', 'in_progress')";
                                                $stmt = mysqli_prepare($conn, $repair_query);
                                                $search_term = "%" . trim($equipment_name) . "%";
                                                mysqli_stmt_bind_param($stmt, "is", $department_id, $search_term);
                                                mysqli_stmt_execute($stmt);
                                                $repair_result = mysqli_stmt_get_result($stmt);
                                                $repair_data = mysqli_fetch_assoc($repair_result);
                                                $repair_count = $repair_data['repair_count'];

                                                // For debugging, let's also get the actual matches
                                                $debug_query = "SELECT tr.equipment_name, t.status, tr.department_id
                                                          FROM tasks t 
                                                          JOIN task_requests tr ON t.request_id = tr.id 
                                                          WHERE tr.department_id = ? 
                                                          AND LOWER(tr.equipment_name) LIKE LOWER(?)";
                                                $debug_stmt = mysqli_prepare($conn, $debug_query);
                                                mysqli_stmt_bind_param($debug_stmt, "is", $department_id, $search_term);
                                                mysqli_stmt_execute($debug_stmt);
                                                $debug_result = mysqli_stmt_get_result($debug_stmt);
                                                $all_matches = mysqli_fetch_all($debug_result, MYSQLI_ASSOC);

                                                // Display repair frequency with appropriate badge color
                                                $badge_class = $repair_count > 3 ? 'danger' : ($repair_count > 1 ? 'warning' : 'info');
                                                echo '<span class="badge badge-' . $badge_class . '" data-toggle="tooltip" title="';
                                                if (!empty($all_matches)) {
                                                    echo "Matching equipment: ";
                                                    foreach ($all_matches as $match) {
                                                        echo htmlspecialchars($match['equipment_name']) . " (" . $match['status'] . "), ";
                                                    }
                                                } else {
                                                    echo "No matching equipment found";
                                                }
                                                echo '">' . $repair_count . ' times</span>';
                                            } else {
                                                echo '<span class="badge badge-secondary">N/A</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    onclick="viewTask(<?php echo isset($task['id']) ? $task['id'] : $task['task_id']; ?>)"
                                                    data-toggle="tooltip" title="View Task Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (isset($user) && is_array($user) && ($user['role'] === 'admin' || (isset($task['assigned_to']) && $task['assigned_to'] == $_SESSION['user_id']))): ?>
                                                    <?php if ($user['role'] === 'admin'): ?>
                                                        <button type="button" class="btn btn-sm btn-warning"
                                                            onclick="editTask(<?php echo isset($task['id']) ? $task['id'] : $task['task_id']; ?>, 
                                                               '<?php echo addslashes(htmlspecialchars(isset($task['title']) ? $task['title'] : 'Untitled Task')); ?>', 
                                                               '<?php echo addslashes(htmlspecialchars(isset($task['department_name']) ? $task['department_name'] : 'No Department')); ?>', 
                                                               '<?php echo addslashes(htmlspecialchars($task['status'])); ?>')" data-toggle="tooltip" title="Edit Task">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <?php if ($task['status'] === 'pending'): ?>
                                                            <button type="button" class="btn btn-sm btn-warning" disabled
                                                                data-toggle="tooltip" title="Start the job first">
                                                                <i class="fas fa-sync-alt"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-warning"
                                                                onclick="updateTask(<?php echo isset($task['id']) ? $task['id'] : $task['task_id']; ?>)"
                                                                data-toggle="tooltip" title="Update Task">
                                                                <i class="fas fa-sync-alt"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if (isset($user) && is_array($user) && $user['role'] === 'admin'): ?>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="deleteTask(<?php echo isset($task['id']) ? $task['id'] : $task['task_id']; ?>, 
                                                   '<?php echo addslashes(htmlspecialchars(isset($task['title']) ? $task['title'] : 'Untitled Task')); ?>')"
                                                        data-toggle="tooltip" title="Delete Task">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php // Add for requestor: ?>
                                                <?php if (isset($task['requester_id']) && $task['requester_id'] == $_SESSION['user_id'] && $task['status'] === 'pending_confirmation'): ?>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                        onclick="showRequestorActionModal(<?php echo isset($task['id']) ? $task['id'] : $task['task_id']; ?>)"
                                                        data-toggle="tooltip" title="Confirm or Reject Completion">
                                                        <i class="fas fa-check-double"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (isset($user) && is_array($user) && isset($task['requester_id']) && $task['requester_id'] == $_SESSION['user_id'] && $task['status'] === 'postponed'): ?>
                                                    <button type="button" class="btn btn-sm btn-info"
                                                        onclick="showFollowUpModal(<?php echo isset($task['id']) ? $task['id'] : $task['task_id']; ?>)"
                                                        data-toggle="tooltip" title="Send Follow Up">
                                                        <i class="fas fa-comment-dots"></i> Follow Up
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1" role="dialog" aria-labelledby="editTaskModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editTaskForm" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_task_id">

                    <div class="form-group">
                        <label for="edit_title">Task Title</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_department_id">Department</label>
                            <select class="form-control" id="edit_department_id" name="department_id" required>
                                <?php if (!empty($departments)): ?>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>">
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_assigned_to">Assigned To</label>
                            <select class="form-control" id="edit_assigned_to" name="assigned_to">
                                <option value="">Select User</option>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $usr): ?>
                                        <option value="<?php echo $usr['id']; ?>">
                                            <?php echo htmlspecialchars($usr['username'] . ' (' . $usr['full_name'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="edit_status">Status</label>
                            <select class="form-control" id="edit_status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="pending_confirmation">Pending Confirmation</option>
                                <option value="postponed">Postponed</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="edit_priority">Priority</label>
                            <select class="form-control" id="edit_priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="edit_due_date">Due Date</label>
                            <input type="date" class="form-control" id="edit_due_date" name="due_date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Task Modal -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1" role="dialog" aria-labelledby="deleteTaskModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTaskModalLabel">Delete Task</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="deleteTaskForm" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_task_id">
                    <p>Are you sure you want to delete the task "<span id="delete_task_title"></span>"?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Task Modal -->
<div class="modal fade" id="updateTaskModal" tabindex="-1" role="dialog" aria-labelledby="updateTaskModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateTaskModalLabel">Update Task</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="updateTaskForm" method="post" action="ajax/update_task.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="update_task_id">

                    <div class="form-group">
                        <label for="update_status">Status</label>
                        <select class="form-control" id="update_status" name="status" required
                            onchange="togglePostponementReason()">
                            <option value="in_progress">In Progress</option>
                            <option value="pending_confirmation">Mark as Finished</option>
                            <option value="postponed">Postpone Task</option>
                        </select>
                    </div>

                    <div class="form-group" id="postponement_reason_group" style="display: none;">
                        <label for="postponement_reason">Reason for Postponement</label>
                        <select class="form-control" id="postponement_reason" name="postponement_reason">
                            <option value="">Select a reason</option>
                            <option value="waiting_for_resources">Waiting for Resources</option>
                            <option value="technical_issues">Technical Issues</option>
                            <option value="waiting_for_approval">Waiting for Approval</option>
                            <option value="other_priority">Other Priority Task</option>
                            <option value="personal_emergency">Personal Emergency</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group" id="other_reason_group" style="display: none;">
                        <label for="other_reason">Specify Other Reason</label>
                        <textarea class="form-control" id="other_reason" name="other_reason" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Requestor Action Modal -->
<div class="modal fade" id="requestorActionModal" tabindex="-1" role="dialog"
    aria-labelledby="requestorActionModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestorActionModalLabel">Confirm or Reject Task Completion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Do you want to confirm this task as completed, or reject it and send it back to staff?</p>
                <input type="hidden" id="requestor_action_task_id" value="">
                <div id="rejectionNotesGroup" style="display:none;">
                    <label for="rejectionNotes">Rejection Notes <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejectionNotes" rows="3"
                        placeholder="Please provide a reason for rejection..."></textarea>
                </div>
                <div id="completionNotesGroup" style="display:none;">
                    <label for="completionNotes">Completion Comment <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="completionNotes" rows="3"
                        placeholder="Please provide a comment for completion..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="rejectTaskBtn">Reject</button>
                <button type="button" class="btn btn-success" id="confirmTaskBtn">Confirm Completion</button>
            </div>
        </div>
    </div>
</div>

<!-- Follow Up Modal -->
<div class="modal fade" id="followUpModal" tabindex="-1" role="dialog" aria-labelledby="followUpModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="followUpModalLabel">Send Follow Up</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="followup_task_id" value="">
                <div class="form-group">
                    <label for="followup_note">Message <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="followup_note" rows="3"
                        placeholder="Enter your follow-up message..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" id="sendFollowUpBtn">Send Follow Up</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/components/footer_scripts.php'; ?>

<script>
    // Function to view task details in modal
    function viewTask(taskId) {
        // Show loading indicator
        $('#viewTaskModal .modal-body').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-3">Loading task details...</p></div>');
        $('#viewTaskModal').modal('show');

        // Fetch task details
        $.ajax({
            url: 'ajax/get_task.php',
            type: 'GET',
            data: { id: taskId },
            success: function (response) {
                try {
                    $('#viewTaskModal .modal-body').html(response);
                } catch (e) {
                    console.error('Error processing response:', e);
                    $('#viewTaskModal .modal-body').html('<div class="alert alert-danger">Error loading task details: ' + e.message + '</div>');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#viewTaskModal .modal-body').html('<div class="alert alert-danger">Error: ' + error + '</div>');
            }
        });
    }

    // Function to open edit task modal
    function editTask(taskId, title, departmentName) {
        // Fetch task details to fill form
        $.ajax({
            url: 'ajax/get_task_data.php',
            type: 'GET',
            data: { id: taskId },
            dataType: 'json',
            success: function (response) {
                if (response && response.success) {
                    const task = response.task;

                    // Fill the form
                    $('#edit_task_id').val(taskId);
                    $('#edit_title').val(task.title);
                    $('#edit_description').val(task.description);
                    $('#edit_department_id').val(task.department_id);
                    $('#edit_assigned_to').val(task.assigned_to);
                    $('#edit_status').val(task.status);
                    $('#edit_priority').val(task.priority);
                    $('#edit_due_date').val(task.due_date);

                    // Show the modal
                    $('#editTaskModal').modal('show');
                } else {
                    toastr.error('Failed to load task details. Please try again.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                toastr.error('Error loading task details: ' + error);
            }
        });
    }

    // Function to open delete task modal
    function deleteTask(taskId, title) {
        // Fill the form
        $('#delete_task_id').val(taskId);
        $('#delete_task_title').text(title);

        // Show the modal
        $('#deleteTaskModal').modal('show');
    }

    function updateTask(taskId) {
        // Set the task ID in the form
        $('#update_task_id').val(taskId);

        // Reset form fields
        $('#update_status').val('in_progress');
        $('#postponement_reason').val('');
        $('#other_reason').val('');
        $('#postponement_reason_group').hide();
        $('#other_reason_group').hide();

        // Show the modal
        $('#updateTaskModal').modal('show');
    }

    function togglePostponementReason() {
        const status = $('#update_status').val();
        if (status === 'postponed') {
            $('#postponement_reason_group').show();
        } else {
            $('#postponement_reason_group').hide();
            $('#other_reason_group').hide();
        }
    }

    $('#postponement_reason').change(function () {
        if ($(this).val() === 'other') {
            $('#other_reason_group').show();
        } else {
            $('#other_reason_group').hide();
        }
    });

    // Form submission handling
    $('#updateTaskForm').submit(function (e) {
        e.preventDefault();

        const status = $('#update_status').val();
        const postponementReason = $('#postponement_reason').val();
        const otherReason = $('#other_reason').val();

        // Validate form
        if (status === 'postponed' && !postponementReason) {
            alert('Please select a reason for postponement');
            return;
        }

        if (postponementReason === 'other' && !otherReason.trim()) {
            alert('Please specify the other reason for postponement');
            return;
        }

        // Submit the form
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    toastr.success('Task updated successfully');
                    $('#updateTaskModal').modal('hide');
                    // Reload the page to show updated status
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to update task');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                toastr.error('Error updating task: ' + error);
            }
        });
    });

    $(document).ready(function () {
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Handle task status filtering
        $('.task-status-filter').click(function () {
            var status = $(this).data('status');
            var table = $('#dataTable').DataTable();

            if (status === 'all') {
                table.column(3).search('').draw();
            } else {
                // Handle special status cases
                if (status === 'pending confirmation') {
                    table.column(3).search('pending_confirmation').draw();
                } else if (status === 'in progress') {
                    table.column(3).search('in_progress').draw();
                } else if (status === 'postponed') {
                    table.column(3).search('postponed').draw();
                } else {
                    table.column(3).search(status).draw();
                }
            }
        });

        // Handle edit form submission
        $('#editTaskForm').submit(function (e) {
            e.preventDefault();

            $.ajax({
                url: 'ajax/update_task.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response && response.success) {
                        $('#editTaskModal').modal('hide');
                        toastr.success('Task updated successfully!');
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    } else {
                        toastr.error('Failed to update task: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    toastr.error('Error updating task: ' + error);
                }
            });
        });

        // Handle delete form submission
        $('#deleteTaskForm').submit(function (e) {
            e.preventDefault();

            $.ajax({
                url: 'ajax/delete_task.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response && response.success) {
                        $('#deleteTaskModal').modal('hide');
                        toastr.success('Task deleted successfully!');
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    } else {
                        toastr.error('Failed to delete task: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    toastr.error('Error deleting task: ' + error);
                }
            });
        });

        // Load Toastr library if not already loaded
        if (typeof toastr === 'undefined') {
            // Load toastr JS
            $.getScript('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js', function () {
                // Configure toastr options
                toastr.options = {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-top-right",
                    timeOut: 5000
                };
            });

            // Add CSS if not already added
            if ($('link[href*="toastr.min.css"]').length === 0) {
                $('head').append('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />');
            }
        } else {
            // Configure toastr options if already loaded
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: 5000
            };
        }

        // Handle task confirmation
        $(document).on('click', '.confirm-task', function () {
            const taskId = $(this).data('task-id');
            const status = $(this).data('status');
            const button = $(this);

            $.ajax({
                url: 'ajax/update_task_status.php',
                type: 'POST',
                data: {
                    task_id: taskId,
                    status: status
                },
                success: function (response) {
                    if (response.success) {
                        // Show success message
                        showAlert('success', response.message);
                        // Reload the page to update the task list
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function () {
                    showAlert('danger', 'An error occurred while updating the task status.');
                }
            });
        });

        // Function to show alerts
        function showAlert(type, message) {
            const alertDiv = $('<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                message +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>' +
                '</div>');

            $('#alert-container').html(alertDiv);

            // Auto-dismiss after 5 seconds
            setTimeout(function () {
                alertDiv.alert('close');
            }, 5000);
        }
    });

    function showRequestorActionModal(taskId) {
        $('#requestor_action_task_id').val(taskId);
        $('#rejectionNotes').val('');
        $('#completionNotes').val('');
        $('#rejectionNotesGroup').hide();
        $('#completionNotesGroup').hide();
        $('#requestorActionModal').modal('show');
    }

    $('#rejectTaskBtn').off('click').on('click', function () {
        if ($('#rejectionNotesGroup').is(':hidden')) {
            $('#rejectionNotesGroup').show();
            $('#completionNotesGroup').hide();
            $('#rejectionNotes').focus();
            return;
        }
        var taskId = $('#requestor_action_task_id').val();
        var notes = $('#rejectionNotes').val().trim();
        if (!notes) {
            toastr.error('Please provide a reason for rejection.');
            $('#rejectionNotes').focus();
            return;
        }
        $.ajax({
            url: 'ajax/update_task_status.php',
            type: 'POST',
            data: {
                task_id: taskId,
                status: 'pending',
                notes: notes
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    toastr.success('Task sent back to staff for further work.');
                    $('#requestorActionModal').modal('hide');
                    setTimeout(function () { location.reload(); }, 1000);
                } else {
                    toastr.error(response.message || 'Failed to update task.');
                }
            },
            error: function (xhr, status, error) {
                toastr.error('Error updating task: ' + error);
            }
        });
    });

    $('#confirmTaskBtn').off('click').on('click', function () {
        if ($('#completionNotesGroup').is(':hidden')) {
            $('#completionNotesGroup').show();
            $('#rejectionNotesGroup').hide();
            $('#completionNotes').focus();
            return;
        }
        var taskId = $('#requestor_action_task_id').val();
        var notes = $('#completionNotes').val().trim();
        if (!notes) {
            toastr.error('Please provide a comment for completion.');
            $('#completionNotes').focus();
            return;
        }
        $.ajax({
            url: 'ajax/update_task_status.php',
            type: 'POST',
            data: {
                task_id: taskId,
                status: 'completed',
                notes: notes
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    toastr.success('Task marked as completed.');
                    $('#requestorActionModal').modal('hide');
                    setTimeout(function () { location.reload(); }, 1000);
                } else {
                    toastr.error(response.message || 'Failed to update task.');
                }
            },
            error: function (xhr, status, error) {
                toastr.error('Error updating task: ' + error);
            }
        });
    });

    function showFollowUpModal(taskId) {
        $('#followup_task_id').val(taskId);
        $('#followup_note').val('');
        $('#followUpModal').modal('show');
    }

    $('#sendFollowUpBtn').off('click').on('click', function () {
        var taskId = $('#followup_task_id').val();
        var note = $('#followup_note').val().trim();
        if (!note) {
            toastr.error('Please enter your follow-up message.');
            $('#followup_note').focus();
            return;
        }
        $.ajax({
            url: 'ajax/followup_task.php',
            type: 'POST',
            data: { task_id: taskId, note: note },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    toastr.success('Follow-up sent successfully!');
                    $('#followUpModal').modal('hide');
                    setTimeout(function () { location.reload(); }, 1000);
                } else {
                    toastr.error(response.message || 'Failed to send follow-up.');
                }
            },
            error: function (xhr, status, error) {
                toastr.error('Error sending follow-up: ' + error);
            }
        });
    });
</script>