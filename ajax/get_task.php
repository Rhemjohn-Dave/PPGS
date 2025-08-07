<?php
session_start();
require_once '../database/connection.php';
require_once '../controllers/TaskController.php';
require_once '../controllers/UserController.php';
require_once '../controllers/DepartmentController.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo '<div class="alert alert-danger">You must be logged in to view task details.</div>';
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">Task ID is required.</div>';
    exit;
}

$taskId = $_GET['id'];
$taskController = new TaskController($conn);
$userController = new UserController($conn);
$departmentController = new DepartmentController($conn);

// Get task details
$task = $taskController->getById($taskId);

if (!$task) {
    echo '<div class="alert alert-danger">Task not found.</div>';
    exit;
}

// Check if user has permission to view this task
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$hasPermission = false;

// Get task request details to check permissions
$requestQuery = "SELECT tr.requester_id, tr.department_id, tr.program_head_approval, d.head_id 
                 FROM task_requests tr 
                 LEFT JOIN departments d ON tr.department_id = d.id 
                 WHERE tr.id = ?";
if ($stmt = mysqli_prepare($conn, $requestQuery)) {
    mysqli_stmt_bind_param($stmt, "i", $task['request_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $requester_id = $row['requester_id'];
        $department_id = $row['department_id'];
        $program_head_approval = $row['program_head_approval'];
        $head_id = $row['head_id'];

        // Check permissions based on role
        if ($user_role === 'admin' || $user_role === 'adaa') {
            // Admin and ADAA can see all tasks
            $hasPermission = true;
        } elseif ($user_role === 'program_head' || $user_role === 'program head') {
            // Program head can see tasks from their department that they approved
            // Get program head's department from database
            $program_head_dept_query = "SELECT department_id FROM users WHERE id = ?";
            if ($dept_stmt = mysqli_prepare($conn, $program_head_dept_query)) {
                mysqli_stmt_bind_param($dept_stmt, "i", $user_id);
                mysqli_stmt_execute($dept_stmt);
                $dept_result = mysqli_stmt_get_result($dept_stmt);
                if ($dept_row = mysqli_fetch_assoc($dept_result)) {
                    $program_head_department_id = $dept_row['department_id'];
                    $hasPermission = ($department_id == $program_head_department_id && $program_head_approval === 'approved');
                }
                mysqli_stmt_close($dept_stmt);
            }
        } else {
            // Regular users can see tasks they created or are assigned to
            $hasPermission = ($requester_id == $user_id || $task['assigned_to'] == $user_id);
        }
    }
    mysqli_stmt_close($stmt);
}

if (!$hasPermission) {
    echo '<div class="alert alert-danger">You do not have permission to view this task.</div>';
    exit;
}

// Get additional task details with requester information and category-specific fields
$query = "SELECT t.*, 
          u.username as assigned_to_username, 
          u.full_name as assigned_to_full_name,
          req.username as requester_username,
          req.full_name as requester_full_name,
          d.name as department_name,
          d.id as department_id,
          tr.category,
          tr.num_copies,
          tr.paper_size,
          tr.paper_type,
          tr.equipment_name,
          tr.problem_description,
          tr.reason
          FROM tasks t
          LEFT JOIN users u ON t.assigned_to = u.id
          LEFT JOIN task_requests tr ON t.request_id = tr.id
          LEFT JOIN users req ON tr.requester_id = req.id
          LEFT JOIN departments d ON tr.department_id = d.id
          WHERE t.id = ?";

if ($stmt = mysqli_prepare($conn, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $taskId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $taskWithUsers = mysqli_fetch_assoc($result);
        // Merge with the original task data
        $task = array_merge($task, $taskWithUsers);

        // Debug logging for repair category fields
        error_log("Task Category: " . ($task['category'] ?? 'not set'));
        error_log("Equipment Name: " . ($task['equipment_name'] ?? 'not set'));
        error_log("Problem Description: " . ($task['problem_description'] ?? 'not set'));
        error_log("Reason: " . ($task['reason'] ?? 'not set'));
        error_log("Full task data: " . print_r($task, true));
    }
}

// Debug log the task data to check if department info is present
error_log("Task data after merging: " . print_r($task, true));

// Get task request details if this task has an associated request
$taskRequest = null;
$query = "SELECT tr.*, 
          tr.category,
          tr.num_copies, 
          tr.paper_size, 
          tr.paper_type, 
          tr.equipment_name, 
          tr.problem_description,
          tr.reason,
          u.username as requester_username, 
          u.full_name as requester_name,
          d.name as department_name,
          ph.username as program_head_username,
          ph.full_name as program_head_name,
          a.username as adaa_username,
          a.full_name as adaa_name
          FROM task_requests tr
          LEFT JOIN users u ON tr.requester_id = u.id
          LEFT JOIN departments d ON tr.department_id = d.id
          LEFT JOIN users ph ON d.head_id = ph.id
          LEFT JOIN users a ON a.role = 'adaa'
          WHERE tr.id = ?";

if ($stmt = mysqli_prepare($conn, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $task['request_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $taskRequest = mysqli_fetch_assoc($result);
        // Debug logging for repair category fields in task request
        error_log("Task Request Data for ID " . $task['request_id'] . ":");
        error_log("Category: " . ($taskRequest['category'] ?? 'not set'));
        error_log("Equipment Name: " . ($taskRequest['equipment_name'] ?? 'not set'));
        error_log("Problem Description: " . ($taskRequest['problem_description'] ?? 'not set'));
        error_log("Reason: " . ($taskRequest['reason'] ?? 'not set'));
        error_log("Full Task Request Data: " . print_r($taskRequest, true));
    }
}

// Debug log the task request data to check department info
if ($taskRequest) {
    error_log("Task request data: " . print_r($taskRequest, true));
}

// Get comments
$comments = [];
$query = "SELECT tc.*, u.username, u.full_name 
          FROM task_comments tc 
          LEFT JOIN users u ON tc.user_id = u.id 
          WHERE tc.task_id = ? 
          ORDER BY tc.created_at DESC";

if ($stmt = mysqli_prepare($conn, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $taskId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $comments[] = $row;
        }
    }
}

// Get attachments
$attachments = [];
$query = "SELECT ta.*, u.username, u.full_name 
          FROM task_attachments ta 
          LEFT JOIN users u ON ta.uploaded_by = u.id 
          WHERE ta.task_id = ? 
          ORDER BY ta.created_at DESC";

if ($stmt = mysqli_prepare($conn, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $taskId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $attachments[] = $row;
        }
    }
}

// Get assigned user details
$assignedUser = null;
if (isset($task['assigned_to']) && !empty($task['assigned_to'])) {
    $assignedUser = $userController->getUserById($task['assigned_to']);
}

// Get created by user details
$createdByUser = null;
if (isset($task['created_by']) && !empty($task['created_by'])) {
    $createdByUser = $userController->getUserById($task['created_by']);
}

// Get department details
$department = null;
if (isset($task['department_id']) && !empty($task['department_id'])) {
    $department = $departmentController->getById($task['department_id']);
}

// Get rejection notes timeline
$rejection_notes = [];
$query = "SELECT tcn.*, u.full_name 
          FROM task_completion_notes tcn
          JOIN users u ON tcn.user_id = u.id
          WHERE tcn.task_id = ?
            AND tcn.note_type = 'rejection'
          ORDER BY tcn.created_at ASC";
if ($stmt = mysqli_prepare($conn, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $taskId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rejection_notes[] = $row;
        }
    }
}

// Get postponement notes timeline
$postponement_notes = [];
$query = "SELECT tcn.*, u.full_name 
          FROM task_completion_notes tcn
          JOIN users u ON tcn.user_id = u.id
          WHERE tcn.task_id = ?
            AND tcn.note_type = 'postponement'
          ORDER BY tcn.created_at ASC";
if ($stmt = mysqli_prepare($conn, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $taskId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $postponement_notes[] = $row;
        }
    }
}

// Get followup notes timeline
$followup_notes = [];
$query = "SELECT tcn.*, u.full_name 
          FROM task_completion_notes tcn
          JOIN users u ON tcn.user_id = u.id
          WHERE tcn.task_id = ?
            AND tcn.note_type = 'followup'
          ORDER BY tcn.created_at ASC";
if ($stmt = mysqli_prepare($conn, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $taskId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $followup_notes[] = $row;
        }
    }
}

// Get completion notes timeline
$completion_notes = [];
$query = "SELECT tcn.*, u.full_name 
          FROM task_completion_notes tcn
          JOIN users u ON tcn.user_id = u.id
          WHERE tcn.task_id = ?
            AND tcn.note_type = 'completion'
          ORDER BY tcn.created_at ASC";
if ($stmt = mysqli_prepare($conn, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $taskId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $completion_notes[] = $row;
        }
    }
}

// Format status for display
function formatStatus($status)
{
    return str_replace('_', ' ', ucwords($status));
}

// Status badge color
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'completed':
            return 'badge-success';
        case 'in_progress':
            return 'badge-warning';
        case 'pending':
            return 'badge-info';
        case 'pending_confirmation':
            return 'badge-secondary';
        case 'rejected':
            return 'badge-danger';
        case 'approved':
            return 'badge-success';
        default:
            return 'badge-primary';
    }
}

// Priority badge color
function getPriorityBadgeClass($priority)
{
    switch ($priority) {
        case 'high':
            return 'badge-danger';
        case 'medium':
            return 'badge-warning';
        case 'low':
            return 'badge-info';
        default:
            return 'badge-secondary';
    }
}
?>

<div class="task-details">
    <div class="row">
        <div class="col-md-8">
            <?php if ($taskRequest && $taskRequest['category'] === 'repairs'): ?>
                <!-- Repair Details Section -->
                <div class="card mb-4">
                    <div class="card-header bg-danger">
                        <h6 class="mb-0 text-white">Repair Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Equipment Name:</strong><br>
                                    <?php echo htmlspecialchars($taskRequest['equipment_name'] ?? 'Not specified'); ?></p>

                                <p><strong>Problem Description:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($taskRequest['problem_description'] ?? 'Not specified')); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Reason:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($taskRequest['reason'] ?? 'Not specified')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <h4><?php echo htmlspecialchars($task['title'] ?? 'Untitled Task'); ?></h4>

            <div class="mb-3">
                <span class="badge <?php echo getStatusBadgeClass($task['status']); ?>">
                    <?php echo formatStatus($task['status']); ?>
                </span>
                <span class="badge <?php
                $priority = $task['priority'] ?? 'medium';
                echo getPriorityBadgeClass($priority);
                ?>">
                    <?php echo ucfirst($priority); ?> Priority
                </span>
                <?php if (isset($task['category']) && !empty($task['category'])): ?>
                    <span class="badge badge-secondary">
                        <?php echo htmlspecialchars($task['category']); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($taskRequest): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary">
                        <h6 class="mb-0 text-white">Request Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Requester:</strong><br>
                                    <?php
                                    $requesterName = 'Unknown';
                                    if (!empty($taskRequest['requester_full_name'])) {
                                        $requesterName = $taskRequest['requester_full_name'];
                                    } elseif (!empty($taskRequest['requester_username'])) {
                                        $requesterName = $taskRequest['requester_username'];
                                    }
                                    echo htmlspecialchars($requesterName);
                                    ?>
                                </p>

                                <p><strong>Department:</strong><br>
                                    <?php echo htmlspecialchars($taskRequest['department_name'] ?? 'No Department'); ?></p>

                                <p><strong>Category:</strong><br>
                                    <?php echo htmlspecialchars(ucfirst($taskRequest['category'] ?? 'Not specified')); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Program Head Approval:</strong><br>
                                    <span
                                        class="badge <?php echo getStatusBadgeClass($taskRequest['program_head_approval'] ?? 'pending'); ?>">
                                        <?php echo formatStatus($taskRequest['program_head_approval'] ?? 'pending'); ?>
                                    </span>
                                </p>

                                <p><strong>ADAA Approval:</strong><br>
                                    <span
                                        class="badge <?php echo getStatusBadgeClass($taskRequest['adaa_approval'] ?? 'pending'); ?>">
                                        <?php echo formatStatus($taskRequest['adaa_approval'] ?? 'pending'); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($task['description'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">Description</h6>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($comments)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">Comments (<?php echo count($comments); ?>)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($comments as $comment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($comment['full_name'] ?? $comment['username']); ?>
                                        </h6>
                                        <small><?php echo date('M d, Y g:i A', strtotime($comment['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($attachments)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0">Attachments (<?php echo count($attachments); ?>)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($attachments as $attachment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($attachment['file_name']); ?></h6>
                                        <small><?php echo date('M d, Y', strtotime($attachment['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        Uploaded by:
                                        <?php echo htmlspecialchars($attachment['full_name'] ?? $attachment['username']); ?>
                                        <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>"
                                            class="btn btn-sm btn-primary ml-2" target="_blank">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($rejection_notes)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">Returned to Staff (<?php echo count($rejection_notes); ?> times)</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($rejection_notes as $note): ?>
                                <li class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <strong><?php echo htmlspecialchars($note['full_name']); ?></strong>
                                        <small><?php echo date('M d, Y g:i A', strtotime($note['created_at'])); ?></small>
                                    </div>
                                    <div>
                                        <span class="badge badge-danger">Returned</span>
                                    </div>
                                    <p class="mb-1 mt-2"><?php echo nl2br(htmlspecialchars($note['notes'])); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($postponement_notes)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">Postponed by Staff (<?php echo count($postponement_notes); ?> times)</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($postponement_notes as $note): ?>
                                <li class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <strong><?php echo htmlspecialchars($note['full_name']); ?></strong>
                                        <small><?php echo date('M d, Y g:i A', strtotime($note['created_at'])); ?></small>
                                    </div>
                                    <div>
                                        <span class="badge badge-secondary">Postponed</span>
                                    </div>
                                    <p class="mb-1 mt-2"><?php echo nl2br(htmlspecialchars($note['notes'])); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($followup_notes)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">User Follow-Ups (<?php echo count($followup_notes); ?>)</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($followup_notes as $note): ?>
                                <li class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <strong><?php echo htmlspecialchars($note['full_name']); ?></strong>
                                        <small><?php echo date('M d, Y g:i A', strtotime($note['created_at'])); ?></small>
                                    </div>
                                    <div>
                                        <span class="badge badge-info">Follow Up</span>
                                    </div>
                                    <p class="mb-1 mt-2"><?php echo nl2br(htmlspecialchars($note['notes'])); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($completion_notes)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">Completion Comments (<?php echo count($completion_notes); ?>)</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($completion_notes as $note): ?>
                                <li class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <strong><?php echo htmlspecialchars($note['full_name']); ?></strong>
                                        <small><?php echo date('M d, Y g:i A', strtotime($note['created_at'])); ?></small>
                                    </div>
                                    <div>
                                        <span class="badge badge-success">Completion</span>
                                    </div>
                                    <p class="mb-1 mt-2"><?php echo nl2br(htmlspecialchars($note['notes'])); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary">
                    <h6 class="mb-0 text-white">Task Info</h6>
                </div>
                <div class="card-body">
                    <p><strong>Due Date:</strong><br> <?php echo date('M d, Y', strtotime($task['due_date'])); ?></p>

                    <p><strong>Assigned To:</strong><br>
                        <?php
                        $assignedToName = 'Unassigned';
                        if (!empty($assignedUser['full_name'])) {
                            $assignedToName = $assignedUser['full_name'];
                        } elseif (!empty($assignedUser['username'])) {
                            $assignedToName = $assignedUser['username'];
                        } elseif (!empty($task['assigned_to_full_name'])) {
                            $assignedToName = $task['assigned_to_full_name'];
                        } elseif (!empty($task['assigned_to_name'])) {
                            $assignedToName = $task['assigned_to_name'];
                        } elseif (!empty($task['assigned_to_username'])) {
                            $assignedToName = $task['assigned_to_username'];
                        }
                        echo htmlspecialchars($assignedToName);
                        ?>
                    </p>

                    <?php
                    // Debug department info
                    error_log("Department variable: " . (isset($department) ? json_encode($department) : "Not set"));
                    error_log("Task department_name: " . ($task['department_name'] ?? "Not set"));
                    error_log("Task department_id: " . ($task['department_id'] ?? "Not set"));
                    error_log("TaskRequest department_id: " . ($taskRequest['department_id'] ?? "Not set"));
                    error_log("TaskRequest department_name: " . ($taskRequest['department_name'] ?? "Not set"));

                    // First try department from $department variable
                    if (isset($department) && $department && !empty($department['name'])):
                        ?>
                        <p><strong>Department:</strong><br> <?php echo htmlspecialchars($department['name']); ?></p>

                        <?php
                        // Then try department directly from task
                    elseif (!empty($task['department_name'])):
                        ?>
                        <p><strong>Department:</strong><br> <?php echo htmlspecialchars($task['department_name']); ?></p>

                        <?php
                        // Then try department from task request
                    elseif ($taskRequest && !empty($taskRequest['department_name'])):
                        ?>
                        <p><strong>Department:</strong><br> <?php echo htmlspecialchars($taskRequest['department_name']); ?>
                        </p>

                        <?php
                        // If none available, show No Department
                    else:
                        ?>
                        <p><strong>Department:</strong><br> No Department</p>
                    <?php endif; ?>

                    <p><strong>Created By:</strong><br>
                        <?php
                        $createdByName = 'Unknown';
                        if (!empty($task['requester_full_name'])) {
                            $createdByName = $task['requester_full_name'];
                        } elseif (!empty($task['requester_username'])) {
                            $createdByName = $task['requester_username'];
                        } elseif (!empty($createdByUser['full_name'])) {
                            $createdByName = $createdByUser['full_name'];
                        } elseif (!empty($createdByUser['username'])) {
                            $createdByName = $createdByUser['username'];
                        }
                        echo htmlspecialchars($createdByName);
                        ?>
                    </p>

                    <p><strong>Created:</strong><br> <?php echo date('M d, Y g:i A', strtotime($task['created_at'])); ?>
                    </p>

                    <p><strong>Last Updated:</strong><br>
                        <?php echo isset($task['updated_at']) ? date('M d, Y g:i A', strtotime($task['updated_at'])) : 'Never'; ?>
                    </p>

                    <?php if ($_SESSION['role'] === 'staff' && $task['assigned_to'] == $_SESSION['user_id']): ?>
                        <?php if ($task['status'] === 'pending'): ?>
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary btn-block"
                                    onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'in_progress')">
                                    <i class="fas fa-play"></i> Start Task
                                </button>
                            </div>
                        <?php elseif ($task['status'] === 'in_progress'): ?>
                            <div class="mt-3">
                                <button type="button" class="btn btn-success btn-block"
                                    onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'pending_confirmation')">
                                    <i class="fas fa-check"></i> Mark as Finished
                                </button>
                            </div>
                        <?php elseif ($task['status'] === 'pending_confirmation'): ?>
                            <div class="mt-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-clock"></i> Waiting for requester confirmation
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($_SESSION['role'] === 'admin' || (isset($taskRequest['requester_id']) && $taskRequest['requester_id'] == $_SESSION['user_id'])): ?>
                <?php if ($task['status'] === 'pending_confirmation'): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <button type="button" class="btn btn-success btn-block"
                                onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'completed')">
                                <i class="fas fa-check-circle"></i> Confirm Completion
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // JavaScript functions to handle task actions
    function setTaskIdForComment(taskId) {
        document.getElementById('comment_task_id').value = taskId;
    }

    function updateTaskStatus(taskId, status) {
        $.ajax({
            url: 'ajax/update_task_status.php',
            type: 'POST',
            data: {
                task_id: taskId,
                status: status
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    // Show success message
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message);
                    } else {
                        alert(response.message);
                    }

                    // Close modal and reload page after a short delay
                    $('#viewTaskModal').modal('hide');
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                } else {
                    // Show error message
                    if (typeof toastr !== 'undefined') {
                        toastr.error(response.message);
                    } else {
                        alert(response.message);
                    }
                }
            },
            error: function (xhr, status, error) {
                // Show error message
                if (typeof toastr !== 'undefined') {
                    toastr.error('Error: ' + error);
                } else {
                    alert('Error: ' + error);
                }
            }
        });
    }
</script>