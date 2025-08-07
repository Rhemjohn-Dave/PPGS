<?php
// Create User Modal
function createUserModal()
{
    global $conn;
    // Get all departments for dropdown
    $departments_sql = "SELECT id, name FROM departments ORDER BY name";
    $departments_result = mysqli_query($conn, $departments_sql);
    ?>
    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" role="dialog" aria-labelledby="createUserModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Create User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="users.php" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="program head">Program Head</option>
                                <option value="adaa">ADAA</option>
                                <option value="staff">Staff</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select class="form-control" id="department" name="department_id">
                                <option value="">Select Department</option>
                                <?php while ($department = mysqli_fetch_assoc($departments_result)): ?>
                                    <option value="<?php echo $department['id']; ?>">
                                        <?php echo htmlspecialchars($department['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

// Create Task Modal
function createTaskModal()
{
    global $conn;
    // Get all departments for dropdown
    $departments_sql = "SELECT id, name FROM departments ORDER BY name";
    $departments_result = mysqli_query($conn, $departments_sql);

    // Get all staff users for assignment
    $staff_sql = "SELECT id, username FROM users WHERE role = 'staff' ORDER BY username";
    $staff_result = mysqli_query($conn, $staff_sql);
    ?>
    <!-- Create Task Modal -->
    <div class="modal fade" id="createTaskModal" tabindex="-1" role="dialog" aria-labelledby="createTaskModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTaskModalLabel">Create Task</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="tasks.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="request_id" name="request_id" value="">
                        <div class="form-group">
                            <label for="title">Task Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select class="form-control" id="department" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php while ($department = mysqli_fetch_assoc($departments_result)): ?>
                                    <option value="<?php echo $department['id']; ?>">
                                        <?php echo htmlspecialchars($department['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="assigned_to">Assign To</label>
                            <select class="form-control" id="assigned_to" name="assigned_to">
                                <option value="">Select Staff Member</option>
                                <?php while ($staff = mysqli_fetch_assoc($staff_result)): ?>
                                    <option value="<?php echo $staff['id']; ?>">
                                        <?php echo htmlspecialchars($staff['username']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select class="form-control" id="priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="due_date">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="create_task" class="btn btn-primary">Create Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

// Edit User Modal
function editUserModal($user)
{
    global $conn;
    // Get all departments for dropdown
    $departments_sql = "SELECT id, name FROM departments ORDER BY name";
    $departments_result = mysqli_query($conn, $departments_sql);
    ?>
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" role="dialog"
        aria-labelledby="editUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel<?php echo $user['id']; ?>">Edit User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="users.php" method="post">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="program head" <?php echo $user['role'] == 'program head' ? 'selected' : ''; ?>>
                                    Program Head</option>
                                <option value="adaa" <?php echo $user['role'] == 'adaa' ? 'selected' : ''; ?>>ADAA</option>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="staff" <?php echo $user['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="department_id">Department</label>
                            <select class="form-control" id="department_id" name="department_id">
                                <option value="">Select Department</option>
                                <?php
                                $sql = "SELECT * FROM departments ORDER BY name";
                                $result = mysqli_query($conn, $sql);
                                while ($dept = mysqli_fetch_assoc($result)) {
                                    $selected = ($user['department_id'] == $dept['id']) ? 'selected' : '';
                                    echo "<option value='{$dept['id']}' {$selected}>{$dept['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Delete Confirmation Modal
function deleteConfirmationModal($id, $type)
{
    ?>
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal<?php echo $id; ?>" tabindex="-1" role="dialog"
        aria-labelledby="deleteModalLabel<?php echo $id; ?>" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel<?php echo $id; ?>">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this <?php echo $type; ?>? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form action="<?php echo $type; ?>s.php" method="POST" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}



// Add Comment Modal
function addCommentModal($task_id)
{
    ?>
    <!-- Add Comment Modal -->
    <div class="modal fade" id="addCommentModal" tabindex="-1" role="dialog" aria-labelledby="addCommentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCommentModalLabel">Add Comment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="view_task.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                        <div class="form-group">
                            <label for="comment">Comment</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_comment" class="btn btn-primary">Add Comment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

// Upload Attachment Modal
function uploadAttachmentModal($task_id)
{
    ?>
    <!-- Upload Attachment Modal -->
    <div class="modal fade" id="uploadAttachmentModal" tabindex="-1" role="dialog"
        aria-labelledby="uploadAttachmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadAttachmentModalLabel">Upload Attachment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="view_task.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                        <div class="form-group">
                            <label for="attachment">File</label>
                            <input type="file" class="form-control-file" id="attachment" name="attachment" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="upload_attachment" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

function createDepartmentModal()
{
    ?>
    <!-- Create Department Modal -->
    <div class="modal fade" id="createDepartmentModal" tabindex="-1" role="dialog"
        aria-labelledby="createDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createDepartmentModalLabel">Create Department</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="departments.php" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Department Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="create_department" class="btn btn-primary">Create Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

function editDepartmentModal($department)
{
    ?>
    <!-- Edit Department Modal -->
    <div class="modal fade" id="editDepartmentModal<?php echo $department['id']; ?>" tabindex="-1" role="dialog"
        aria-labelledby="editDepartmentModalLabel<?php echo $department['id']; ?>" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDepartmentModalLabel<?php echo $department['id']; ?>">Edit Department
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="departments.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="department_id" value="<?php echo $department['id']; ?>">
                        <div class="form-group">
                            <label for="name<?php echo $department['id']; ?>">Department Name</label>
                            <input type="text" class="form-control" id="name<?php echo $department['id']; ?>" name="name"
                                value="<?php echo htmlspecialchars($department['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="description<?php echo $department['id']; ?>">Description</label>
                            <textarea class="form-control" id="description<?php echo $department['id']; ?>"
                                name="description"
                                rows="3"><?php echo htmlspecialchars($department['description']); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="update_department" class="btn btn-primary">Update Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

function editTaskModal($task)
{
    global $conn;
    $current_role = $_SESSION['role']; // Get current user's role
    $is_staff_editing = ($current_role == 'staff');

    // Get all departments for dropdown (only needed if not staff)
    $departments_result = null;
    if (!$is_staff_editing) {
        $departments_sql = "SELECT id, name FROM departments ORDER BY name";
        $departments_result = mysqli_query($conn, $departments_sql);
    }

    // Get all staff users for assignment (only needed if not staff)
    $staff_result = null;
    if (!$is_staff_editing) {
        $staff_sql = "SELECT id, username FROM users WHERE role = 'staff' ORDER BY username";
        $staff_result = mysqli_query($conn, $staff_sql);
    }

    // Define allowed status transitions
    $allowedTransitions = [
        'pending' => ['in_progress'],
        'in_progress' => ['pending_confirmation'],
        'pending_confirmation' => ['completed', 'in_progress'],
        'completed' => ['in_progress'],
        'rejected' => ['pending']
    ];

    // Get current status
    $currentStatus = $task['status'] ?? 'pending';
    $allowedNextStatuses = $allowedTransitions[$currentStatus] ?? [];
    ?>
    <!-- Edit Task Modal -->
    <div class="modal fade" id="editTaskModal<?php echo $task['id']; ?>" tabindex="-1" role="dialog"
        aria-labelledby="editTaskModalLabel<?php echo $task['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaskModalLabel<?php echo $task['id']; ?>">Edit Task</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="ajax/update_task.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="title<?php echo $task['id']; ?>">Title</label>
                            <input type="text" class="form-control" id="title<?php echo $task['id']; ?>" name="title"
                                value="<?php echo htmlspecialchars($task['title'] ?? ''); ?>" required <?php echo $is_staff_editing ? 'readonly' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label for="description<?php echo $task['id']; ?>">Description</label>
                            <textarea class="form-control" id="description<?php echo $task['id']; ?>" name="description"
                                rows="3" <?php echo $is_staff_editing ? 'readonly' : ''; ?>><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
                        </div>
                        <?php if (!$is_staff_editing): ?>
                            <div class="form-group">
                                <label for="department_id<?php echo $task['id']; ?>">Department</label>
                                <select class="form-control" id="department_id<?php echo $task['id']; ?>" name="department_id"
                                    required>
                                    <?php while ($dept = mysqli_fetch_assoc($departments_result)): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo $task['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="assigned_to<?php echo $task['id']; ?>">Assign To</label>
                                <select class="form-control" id="assigned_to<?php echo $task['id']; ?>" name="assigned_to">
                                    <option value="">Unassigned</option>
                                    <?php while ($staff = mysqli_fetch_assoc($staff_result)): ?>
                                        <option value="<?php echo $staff['id']; ?>" <?php echo $task['assigned_to'] == $staff['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($staff['username']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="status<?php echo $task['id']; ?>">Status</label>
                            <select class="form-control" id="status<?php echo $task['id']; ?>" name="status" required>
                                <option value="<?php echo $currentStatus; ?>" selected>
                                    <?php echo str_replace('_', ' ', ucfirst($currentStatus)); ?> (Current)
                                </option>
                                <?php foreach ($allowedNextStatuses as $nextStatus): ?>
                                    <option value="<?php echo $nextStatus; ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($nextStatus)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="priority<?php echo $task['id']; ?>">Priority</label>
                            <select class="form-control" id="priority<?php echo $task['id']; ?>" name="priority" required
                                <?php echo $is_staff_editing ? 'disabled' : ''; ?>>
                                <?php if ($is_staff_editing): ?>
                                    <option value="<?php echo $task['priority']; ?>" selected>
                                        <?php echo ucfirst($task['priority']); ?></option>
                                <?php else: ?>
                                    <option value="low" <?php echo $task['priority'] == 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo $task['priority'] == 'medium' ? 'selected' : ''; ?>>Medium
                                    </option>
                                    <option value="high" <?php echo $task['priority'] == 'high' ? 'selected' : ''; ?>>High
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="due_date<?php echo $task['id']; ?>">Due Date</label>
                            <input type="date" class="form-control" id="due_date<?php echo $task['id']; ?>" name="due_date"
                                value="<?php echo $task['due_date']; ?>" required <?php echo $is_staff_editing ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="update_task" class="btn btn-primary">Update Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

function viewTaskModal($task)
{
    global $conn;
    // Add debug logging to help identify issues
    error_log("viewTaskModal received data: " . print_r($task, true));

    // Get task request details if not already included
    if (!isset($task['request_id'])) {
        error_log("No request_id found in task data");
        return;
    }

    $taskRequest = null;
    $query = "SELECT tr.*, 
              tr.category,
              tr.num_copies, 
              tr.paper_size, 
              tr.paper_type, 
              tr.equipment_name, 
              tr.problem_description,
              tr.reason
              FROM task_requests tr
              WHERE tr.id = ?";

    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, "i", $task['request_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $taskRequest = mysqli_fetch_assoc($result);
            // Merge task request data with task data
            $task = array_merge($task, $taskRequest);
            error_log("Task request data merged: " . print_r($task, true));
        }
        mysqli_stmt_close($stmt);
    }
    ?>
    <!-- View Task Modal -->
    <div class="modal fade" id="viewTaskModal<?php echo $task['id']; ?>" tabindex="-1" role="dialog"
        aria-labelledby="viewTaskModalLabel<?php echo $task['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewTaskModalLabel<?php echo $task['id']; ?>">Task Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Task Information</h6>
                            <p><strong>Title:</strong>
                                <?php echo htmlspecialchars($task['request_title'] ?? $task['title'] ?? 'Untitled Task'); ?>
                            </p>
                            <p><strong>Description:</strong>
                                <?php echo nl2br(htmlspecialchars($task['description'] ?? '')); ?></p>
                            <p><strong>Department:</strong>
                                <?php echo htmlspecialchars($task['department_name'] ?? 'No Department'); ?></p>

                            <?php if (isset($task['category'])): ?>
                                <p><strong>Category:</strong> <?php echo htmlspecialchars(ucfirst($task['category'])); ?></p>
                            <?php endif; ?>

                            <p><strong>Status:</strong> <span class="badge badge-<?php
                            echo $task['status'] == 'completed' ? 'success' :
                                ($task['status'] == 'in_progress' ? 'primary' :
                                    ($task['status'] == 'pending_confirmation' ? 'info' : 'warning'));
                            ?>"><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?></span></p>

                            <?php
                            // Get priority value, checking multiple possible keys
                            $priority = 'normal';
                            if (isset($task['priority'])) {
                                $priority = $task['priority'];
                            } elseif (isset($task['request_priority'])) {
                                $priority = $task['request_priority'];
                            }

                            // Determine badge class based on priority
                            $priorityClass = 'info';
                            if ($priority == 'high') {
                                $priorityClass = 'danger';
                            } elseif ($priority == 'medium') {
                                $priorityClass = 'warning';
                            }
                            ?>

                            <p><strong>Priority:</strong> <span
                                    class="badge badge-<?php echo $priorityClass; ?>"><?php echo ucfirst($priority); ?></span>
                            </p>
                            <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($task['due_date'] ?? '')); ?>
                            </p>

                            <?php if (isset($task['category'])): ?>
                                <?php if ($task['category'] === 'printing'): ?>
                                    <h6>Printing Details</h6>
                                    <p><strong>Number of Copies:</strong>
                                        <?php echo htmlspecialchars($task['num_copies'] ?? 'N/A'); ?></p>
                                    <p><strong>Paper Size:</strong> <?php echo htmlspecialchars($task['paper_size'] ?? 'N/A'); ?>
                                    </p>
                                    <p><strong>Paper Type:</strong> <?php echo htmlspecialchars($task['paper_type'] ?? 'N/A'); ?>
                                    </p>
                                <?php elseif ($task['category'] === 'repairs'): ?>
                                    <h6>Repair Details</h6>
                                    <p><strong>Equipment Name:</strong>
                                        <?php echo htmlspecialchars($task['equipment_name'] ?? 'N/A'); ?></p>
                                    <p><strong>Problem Description:</strong>
                                        <?php echo nl2br(htmlspecialchars($task['problem_description'] ?? 'N/A')); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>

                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Assignment Details</h6>
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

                            // Try various possible field names for assigned staff
                            $assignedToName = 'Unassigned';
                            if (!empty($task['assigned_to_full_name'])) {
                                $assignedToName = $task['assigned_to_full_name'];
                            } elseif (!empty($task['assigned_to_username'])) {
                                $assignedToName = $task['assigned_to_username'];
                            } elseif (!empty($task['assigned_to_name'])) {
                                $assignedToName = $task['assigned_to_name'];
                            }

                            ?>
                            <p><strong>Requested By:</strong> <?php echo htmlspecialchars($requesterName); ?></p>
                            <p><strong>Assigned To:</strong> <?php echo htmlspecialchars($assignedToName); ?></p>


                            <?php
                            // Handle program head approval
                            $ph_approval = isset($task['program_head_approval']) ? $task['program_head_approval'] : 'pending';
                            $ph_class = $ph_approval == 'approved' ? 'success' : ($ph_approval == 'pending' ? 'warning' : 'danger');
                            ?>
                            <p><strong>Program Head Approval:</strong> <span
                                    class="badge badge-<?php echo $ph_class; ?>"><?php echo ucfirst($ph_approval); ?></span>
                            </p>

                            <?php
                            // Handle ADAA approval
                            $adaa_approval = isset($task['adaa_approval']) ? $task['adaa_approval'] : 'pending';
                            $adaa_class = $adaa_approval == 'approved' ? 'success' : ($adaa_approval == 'pending' ? 'warning' : 'danger');
                            ?>
                            <p><strong>ADAA Approval:</strong> <span
                                    class="badge badge-<?php echo $adaa_class; ?>"><?php echo ucfirst($adaa_approval); ?></span>
                            </p>

                            <p><strong>Created:</strong>
                                <?php echo isset($task['created_at']) ? date('M d, Y H:i', strtotime($task['created_at'])) : 'Unknown'; ?>
                            </p>
                            <p><strong>Last Updated:</strong>
                                <?php echo isset($task['updated_at']) ? date('M d, Y H:i', strtotime($task['updated_at'])) : 'Unknown'; ?>
                            </p>
                            <?php if (isset($task['completed_at']) && $task['completed_at']): ?>
                                <p><strong>Completed On:</strong>
                                    <?php echo date('M d, Y H:i', strtotime($task['completed_at'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if ($task['status'] == 'pending_confirmation' && isset($task['requester_id']) && ($task['requester_id'] == $_SESSION['user_id'] || $_SESSION['role'] === 'admin')): ?>
                        <button type="button" class="btn btn-success confirm-task" data-task-id="<?php echo $task['id']; ?>"
                            data-status="completed">
                            <i class="fas fa-check"></i> Confirm Completion
                        </button>
                        <button type="button" class="btn btn-danger confirm-task" data-task-id="<?php echo $task['id']; ?>"
                            data-status="rejected">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Request Task Modal
function requestTaskModal()
{
    global $conn;
    // Get all departments for dropdown
    $departments_sql = "SELECT id, name FROM departments ORDER BY name";
    $departments_result = mysqli_query($conn, $departments_sql);
    ?>
    <!-- Request Task Modal -->
    <div class="modal fade" id="requestTaskModal" tabindex="-1" role="dialog" aria-labelledby="requestTaskModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="requestTaskModalLabel">Request New Task</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="tasks.php" method="post" enctype="multipart/form-data" id="requestTaskForm">
                    <div class="modal-body">
                        <!-- Basic Information -->
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Basic Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="title">Task Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label for="category">Task Category</label>
                                    <select class="form-control" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="printing">Risograph/Printing</option>
                                        <option value="repairs">Repairs</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="instructional">Instructional Materials</option>
                                        <option value="clerical">Clerical/Typing</option>
                                        <option value="inventory">Inventory</option>
                                        <option value="event">Event Assistance</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"
                                        required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="reason">Reason for Request</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Department and Priority -->
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">Department & Priority</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="department">Department</label>
                                    <select class="form-control" id="department" name="department_id" required>
                                        <option value="">Select Department</option>
                                        <?php while ($department = mysqli_fetch_assoc($departments_result)): ?>
                                            <option value="<?php echo $department['id']; ?>">
                                                <?php echo htmlspecialchars($department['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="priority">Priority</label>
                                    <select class="form-control" id="priority" name="priority" required>
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Category-Specific Fields -->
                        <div id="categoryFields">
                            <!-- Printing/Risograph Fields -->
                            <div class="category-section" id="printingFields" style="display: none;">
                                <div class="card mb-3">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0">Printing Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="num_copies">Number of Copies</label>
                                            <input type="number" class="form-control" id="num_copies" name="num_copies"
                                                min="1">
                                        </div>
                                        <div class="form-group">
                                            <label for="paper_size">Paper Size</label>
                                            <select class="form-control" id="paper_size" name="paper_size">
                                                <option value="a4">A4</option>
                                                <option value="letter">Letter</option>
                                                <option value="legal">Legal</option>
                                                <option value="a3">A3</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="paper_type">Paper Type</label>
                                            <select class="form-control" id="paper_type" name="paper_type">
                                                <option value="plain">Plain</option>
                                                <option value="glossy">Glossy</option>
                                                <option value="cardstock">Card Stock</option>
                                                <option value="recycled">Recycled</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Repairs Fields -->
                            <div class="category-section" id="repairsFields" style="display: none;">
                                <div class="card mb-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Repairs Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="equipment_name">Equipment Name</label>
                                            <input type="text" class="form-control" id="equipment_name"
                                                name="equipment_name">
                                        </div>
                                        <div class="form-group">
                                            <label for="problem_description">Problem Description</label>
                                            <textarea class="form-control" id="problem_description"
                                                name="problem_description" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Maintenance Fields -->
                            <div class="category-section" id="maintenanceFields" style="display: none;">
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Maintenance Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="maintenance_type">Type of Maintenance</label>
                                            <select class="form-control" id="maintenance_type" name="maintenance_type">
                                                <option value="cleaning">Cleaning</option>
                                                <option value="inspection">Inspection</option>
                                                <option value="preventive">Preventive</option>
                                                <option value="corrective">Corrective</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="area">Area</label>
                                            <input type="text" class="form-control" id="area" name="area">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Instructional Materials Fields -->
                            <div class="category-section" id="instructionalFields" style="display: none;">
                                <div class="card mb-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Instructional Materials Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="subject">Subject/Department</label>
                                            <input type="text" class="form-control" id="subject" name="subject">
                                        </div>
                                        <div class="form-group">
                                            <label for="grade_level">Grade Level</label>
                                            <select class="form-control" id="grade_level" name="grade_level">
                                                <option value="elementary">Elementary</option>
                                                <option value="junior_high">Junior High</option>
                                                <option value="senior_high">Senior High</option>
                                                <option value="college">College</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="template">Upload Template</label>
                                            <input type="file" class="form-control-file" id="template" name="template"
                                                accept=".doc,.docx,.pdf,.ppt,.pptx">
                                            <small class="form-text text-muted">Accepted formats: DOC, DOCX, PDF, PPT,
                                                PPTX</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attachments -->
                        <div class="card mb-3">
                            <div class="card-header bg-dark text-white">
                                <h6 class="mb-0">Attachments</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="attachments">Supporting Documents</label>
                                    <input type="file" class="form-control-file" id="attachments" name="attachments[]"
                                        multiple>
                                    <small class="form-text text-muted">You can upload multiple files. Maximum size per
                                        file: 5MB</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="submit_request" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            // Initialize form fields when modal is shown
            $('#requestTaskModal').on('show.bs.modal', function () {
                console.log('Modal shown - initializing form');
                // Clear any previous error messages
                $('.error-message').remove();

                // Initialize form fields
                $('#title').val('').removeAttr('disabled');
                $('#description').val('').removeAttr('disabled');
                $('#reason').val('').removeAttr('disabled');
                $('#department').val('').removeAttr('disabled');
                $('#category').val('').removeAttr('disabled');
            });

            // Form validation
            $('#requestTaskForm').on('submit', function (e) {
                e.preventDefault(); // Prevent form submission for debugging

                // Get form values with proper trimming
                var title = $('#title').val().trim();
                var description = $('#description').val().trim();
                var reason = $('#reason').val().trim();
                var department = $('#department').val();
                var category = $('#category').val();

                // Debug logging
                console.log('Form values:', {
                    title: title,
                    description: description,
                    reason: reason,
                    department: department,
                    category: category
                });

                var errorMessages = [];

                // Basic validation
                if (!title) {
                    console.log('Title is empty');
                    errorMessages.push('Please enter a task title');
                    $('#title').after('<div class="error-message text-danger">Please enter a task title</div>');
                }

                if (!description) {
                    console.log('Description is empty');
                    errorMessages.push('Please enter a description');
                    $('#description').after('<div class="error-message text-danger">Please enter a description</div>');
                }

                if (!reason) {
                    console.log('Reason is empty');
                    errorMessages.push('Please enter a reason for the request');
                    $('#reason').after('<div class="error-message text-danger">Please enter a reason for the request</div>');
                }

                if (!department) {
                    console.log('Department not selected');
                    errorMessages.push('Please select a department');
                    $('#department').after('<div class="error-message text-danger">Please select a department</div>');
                }

                if (!category) {
                    console.log('Category not selected');
                    errorMessages.push('Please select a task category');
                    $('#category').after('<div class="error-message text-danger">Please select a task category</div>');
                }

                // Show all error messages at once
                if (errorMessages.length > 0) {
                    console.log('Validation errors:', errorMessages);
                    return false;
                }

                console.log('Form validation passed, submitting...');
                // If no errors, submit the form
                this.submit();
            });

            // File size validation
            $('#attachments, #template').change(function () {
                var maxSize = 5 * 1024 * 1024; // 5MB
                var files = this.files;

                for (var i = 0; i < files.length; i++) {
                    if (files[i].size > maxSize) {
                        alert('File size exceeds 5MB limit: ' + files[i].name);
                        $(this).val('');
                        return false;
                    }
                }
            });
        });
    </script>
    <?php
}

function viewTaskRequestModal($request)
{
    $requestId = $request['id'];

    // Check if a task is associated with this request and fetch its priority
    $taskPriority = 'normal'; // Default priority if no task found or priority not set
    $query = "SELECT priority FROM tasks WHERE request_id = ? LIMIT 1";
    if ($stmt = mysqli_prepare($GLOBALS['conn'], $query)) {
        mysqli_stmt_bind_param($stmt, "i", $requestId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $taskData = mysqli_fetch_assoc($result);
            if (isset($taskData['priority'])) {
                $taskPriority = $taskData['priority'];
            }
        }
        mysqli_stmt_close($stmt);
    }

    ?>
    <div class="modal fade" id="viewTaskRequestModal<?php echo $requestId; ?>" tabindex="-1" role="dialog"
        aria-labelledby="viewTaskRequestModalLabel<?php echo $requestId; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewTaskRequestModalLabel<?php echo $requestId; ?>">Task Request Details
                        <?php echo '<span class="badge badge-info">' . ($request['effective_status'] ?? ucfirst($request['status'])) . '</span>'; ?>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3">Request Information</h6>
                            <p><strong>Title:</strong>
                                <?php echo htmlspecialchars($request['request_title'] ?? $request['title'] ?? 'Untitled Request'); ?>
                            </p>
                            <p><strong>Description:</strong>
                                <?php echo nl2br(htmlspecialchars($request['description'] ?? '')); ?></p>
                            <p><strong>Department:</strong>
                                <?php echo htmlspecialchars($request['department_name'] ?? 'No Department'); ?></p>
                            <?php if (!empty($request['category'])): ?>
                                <p><strong>Category:</strong> <?php echo htmlspecialchars(ucfirst($request['category'])); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($request['due_date'])): ?>
                                <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($request['due_date'])); ?></p>
                            <?php endif; ?>
                            <p><strong>Status:</strong>
                                <span class="badge badge-<?php
                                echo ($request['status'] == 'pending') ? 'warning' :
                                    (($request['status'] == 'approved') ? 'success' :
                                        (($request['status'] == 'rejected') ? 'danger' :
                                            (($request['status'] == 'assigned') ? 'info' : 'primary')));
                                ?>">
                                    <?php
                                    // Check for effective_status first (which might be used in task_requests.php)
                                    if (isset($request['effective_status'])) {
                                        echo ucfirst($request['effective_status']);
                                    } else {
                                        echo ucfirst($request['status']);
                                    }
                                    ?>
                                </span>
                            </p>
                            <?php
                            // Determine badge class based on priority
                            $priorityClass = 'info';
                            if ($taskPriority == 'high') {
                                $priorityClass = 'danger';
                            } elseif ($taskPriority == 'medium') {
                                $priorityClass = 'warning';
                            }
                            ?>
                            <p><strong>Priority:</strong>
                                <span class="badge badge-<?php echo $priorityClass; ?>">
                                    <?php echo ucfirst($taskPriority); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold mb-3">Approval Details</h6>
                            <?php
                            // Try various possible field names for requester
                            $requesterName = 'Unknown';
                            if (!empty($request['requester_full_name'])) {
                                $requesterName = $request['requester_full_name'];
                            } elseif (!empty($request['requester_username'])) {
                                $requesterName = $request['requester_username'];
                            } elseif (!empty($request['requester_name'])) {
                                $requesterName = $request['requester_name'];
                            }

                            // Try various possible field names for created by
                            $createdByName = 'Unknown';
                            if (!empty($request['created_by_full_name'])) {
                                $createdByName = $request['created_by_full_name'];
                            } elseif (!empty($request['created_by_username'])) {
                                $createdByName = $request['created_by_username'];
                            } elseif (!empty($request['created_by_name'])) {
                                $createdByName = $request['created_by_name'];
                            } elseif (!empty($request['created_by'])) {
                                $createdByName = $request['created_by'];
                            }
                            ?>
                            <p><strong>Requested By:</strong> <?php echo htmlspecialchars($requesterName); ?></p>
                            <?php if (!empty($createdByName) && $createdByName !== 'Unknown' && $createdByName !== $requesterName): ?>
                                <p><strong>Created By:</strong> <?php echo htmlspecialchars($createdByName); ?></p>
                            <?php endif; ?>
                            <p><strong>Program Head Approval:</strong>
                                <span class="badge badge-<?php
                                echo ($request['program_head_approval'] == 'pending') ? 'warning' :
                                    (($request['program_head_approval'] == 'approved') ? 'success' : 'danger');
                                ?>">
                                    <?php echo ucfirst($request['program_head_approval']); ?>
                                </span>
                            </p>
                            <p><strong>ADAA Approval:</strong>
                                <span class="badge badge-<?php
                                echo ($request['adaa_approval'] == 'pending') ? 'warning' :
                                    (($request['adaa_approval'] == 'approved') ? 'success' : 'danger');
                                ?>">
                                    <?php echo ucfirst($request['adaa_approval']); ?>
                                </span>
                            </p>
                            <p><strong>Request Date:</strong>
                                <?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?></p>
                            <p><strong>Last Updated:</strong>
                                <?php echo date('M d, Y h:i A', strtotime($request['updated_at'])); ?></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <?php if (
                        ($_SESSION['role'] == 'admin') &&
                        ($request['status'] == 'approved' ||
                            ($request['program_head_approval'] == 'approved' && $request['adaa_approval'] == 'approved'))
                    ): ?>
                        <button type="button" class="btn btn-primary"
                            onclick="$('#viewTaskRequestModal<?php echo $requestId; ?>').modal('hide'); setTimeout(function() { $('#assignTaskModal<?php echo $requestId; ?>').modal('show'); }, 500);">
                            <i class="fas fa-user-plus"></i> Assign Task
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (
        ($_SESSION['role'] == 'admin') &&
        ($request['status'] == 'approved' ||
            ($request['program_head_approval'] == 'approved' && $request['adaa_approval'] == 'approved'))
    ): ?>
        <!-- Separate Assign Task Modal -->
        <div class="modal fade" id="assignTaskModal<?php echo $requestId; ?>" tabindex="-1" role="dialog"
            aria-labelledby="assignTaskModalLabel<?php echo $requestId; ?>" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignTaskModalLabel<?php echo $requestId; ?>">Assign Task</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="assignTaskForm<?php echo $requestId; ?>" method="post" action="task_requests.php">
                            <input type="hidden" name="assign_task" value="1">
                            <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                            <div class="form-group">
                                <label>Task Title</label>
                                <input type="text" class="form-control"
                                    value="<?php echo htmlspecialchars($request['title']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Requester</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($requesterName); ?>"
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="staff_id<?php echo $requestId; ?>">Assign To</label>
                                <select class="form-control" id="staff_id<?php echo $requestId; ?>" name="staff_id" required>
                                    <option value="">Select Staff Member</option>
                                    <?php
                                    // Get staff members
                                    $staff_sql = "SELECT id, username, full_name FROM users WHERE role = 'staff' ORDER BY full_name, username";
                                    $staff_result = mysqli_query($GLOBALS['conn'], $staff_sql);
                                    if ($staff_result) {
                                        while ($staff = mysqli_fetch_assoc($staff_result)):
                                            $task_count = 0;
                                            $sql = "SELECT COUNT(*) as task_count FROM tasks WHERE assigned_to = ? AND status IN ('pending', 'in_progress')";
                                            if ($stmt = mysqli_prepare($GLOBALS['conn'], $sql)) {
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
                                        <?php
                                        endwhile;
                                    }
                                    ?>
                                </select>
                                <small class="form-text text-muted">Staff members with 5 or more pending tasks are not
                                    available.</small>
                            </div>
                            <div class="form-group">
                                <label for="priority<?php echo $requestId; ?>">Priority</label>
                                <select class="form-control" id="priority<?php echo $requestId; ?>" name="priority" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="due_date<?php echo $requestId; ?>">Due Date</label>
                                <input type="date" class="form-control" id="due_date<?php echo $requestId; ?>" name="due_date"
                                    value="<?php echo htmlspecialchars($request['due_date'] ?? ''); ?>" readonly>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" form="assignTaskForm<?php echo $requestId; ?>"
                            class="btn btn-primary">Assign</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php
}
?>