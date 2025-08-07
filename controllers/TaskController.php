<?php
require_once __DIR__ . '/../includes/helpers/task_helper.php';
require_once __DIR__ . '/../includes/helpers/notification_helper.php';
require_once __DIR__ . '/../database/connection.php';

class TaskController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function create($data)
    {
        $task_id = createTask($data, $this->conn);
        if ($task_id) {
            // Send notification to assigned user
            $message = "You have been assigned a new task: " . $data['title'];
            $link = "tasks.php";
            sendNotification([$data['assigned_to']], $message, $this->conn, $link);
        }
        return $task_id;
    }

    public function update($task_id, $data)
    {
        return updateTask($task_id, $data, $this->conn);
    }

    public function delete($task_id)
    {
        return deleteTask($task_id, $this->conn);
    }

    public function getById($task_id)
    {
        return getTaskById($task_id, $this->conn);
    }

    public function getByDepartment($department_id)
    {
        return getTasksByDepartment($department_id, $this->conn);
    }

    public function getByAssignee($user_id)
    {
        return getTasksByAssignee($user_id, $this->conn);
    }

    public function updateStatus($task_id, $status)
    {
        return updateTaskStatus($task_id, $status, $this->conn);
    }

    public function getStats($department_id)
    {
        return getTaskStatsByDepartment($department_id, $this->conn);
    }

    public function getAllTaskCounts()
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'pending_confirmation' THEN 1 ELSE 0 END) as pending_confirmation,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM tasks";

        $result = mysqli_query($this->conn, $sql);
        if ($result) {
            $counts = mysqli_fetch_assoc($result);

            // Force all values to be integers and handle NULL values
            $counts['total'] = isset($counts['total']) ? (int) $counts['total'] : 0;
            $counts['completed'] = isset($counts['completed']) ? (int) $counts['completed'] : 0;
            $counts['pending'] = isset($counts['pending']) ? (int) $counts['pending'] : 0;
            $counts['in_progress'] = isset($counts['in_progress']) ? (int) $counts['in_progress'] : 0;
            $counts['pending_confirmation'] = isset($counts['pending_confirmation']) ? (int) $counts['pending_confirmation'] : 0;
            $counts['rejected'] = isset($counts['rejected']) ? (int) $counts['rejected'] : 0;

            return $counts;
        }

        return [
            'total' => 0,
            'completed' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'pending_confirmation' => 0,
            'rejected' => 0
        ];
    }

    public function getDepartmentTaskCounts($department_id)
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN t.status = 'pending_confirmation' THEN 1 ELSE 0 END) as pending_confirmation,
                    SUM(CASE WHEN t.status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM tasks t
                JOIN task_requests tr ON t.request_id = tr.id
                WHERE tr.department_id = ?";

        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $department_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                $counts = mysqli_fetch_assoc($result);

                // Force all values to be integers and handle NULL values
                $counts['total'] = isset($counts['total']) ? (int) $counts['total'] : 0;
                $counts['completed'] = isset($counts['completed']) ? (int) $counts['completed'] : 0;
                $counts['pending'] = isset($counts['pending']) ? (int) $counts['pending'] : 0;
                $counts['in_progress'] = isset($counts['in_progress']) ? (int) $counts['in_progress'] : 0;
                $counts['pending_confirmation'] = isset($counts['pending_confirmation']) ? (int) $counts['pending_confirmation'] : 0;
                $counts['rejected'] = isset($counts['rejected']) ? (int) $counts['rejected'] : 0;

                return $counts;
            }
        }
        return [
            'total' => 0,
            'completed' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'pending_confirmation' => 0,
            'rejected' => 0
        ];
    }

    public function getUserTaskCounts($user_id)
    {
        error_log('TaskController: getUserTaskCounts called with user_id = ' . $user_id);
        $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN t.status = 'pending_confirmation' THEN 1 ELSE 0 END) as pending_confirmation,
            SUM(CASE WHEN t.status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM tasks t
        JOIN task_requests tr ON t.request_id = tr.id
        WHERE t.assigned_to = ? OR tr.requester_id = ?";
        error_log('TaskController: SQL = ' . $sql);
        error_log("Getting task counts for user ID: $user_id");
        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
            $execute_result = mysqli_stmt_execute($stmt);
            if (!$execute_result) {
                error_log("Failed to execute task count query for user $user_id: " . mysqli_stmt_error($stmt));
                return [
                    'total' => 0,
                    'completed' => 0,
                    'pending' => 0,
                    'in_progress' => 0,
                    'pending_confirmation' => 0,
                    'rejected' => 0
                ];
            }
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                $counts = mysqli_fetch_assoc($result);
                // Force all values to be integers and handle NULL values
                $counts['total'] = isset($counts['total']) ? (int) $counts['total'] : 0;
                $counts['completed'] = isset($counts['completed']) ? (int) $counts['completed'] : 0;
                $counts['pending'] = isset($counts['pending']) ? (int) $counts['pending'] : 0;
                $counts['in_progress'] = isset($counts['in_progress']) ? (int) $counts['in_progress'] : 0;
                $counts['pending_confirmation'] = isset($counts['pending_confirmation']) ? (int) $counts['pending_confirmation'] : 0;
                $counts['rejected'] = isset($counts['rejected']) ? (int) $counts['rejected'] : 0;
                error_log('TaskController: counts for user_id ' . $user_id . ' = ' . print_r($counts, true));
                error_log("Task counts for user $user_id: " . json_encode($counts));
                return $counts;
            } else {
                error_log("Error fetching task counts for user $user_id: " . mysqli_error($this->conn));
            }
        } else {
            error_log("Error preparing task counts query for user $user_id: " . mysqli_error($this->conn));
        }
        return [
            'total' => 0,
            'completed' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'pending_confirmation' => 0,
            'rejected' => 0
        ];
    }

    /**
     * Get all tasks
     * @return array Array of tasks
     */
    public function getAllTasks()
    {
        $sql = "SELECT t.*, d.name as department_name, u.username as assigned_to_name,
                       u.full_name as assigned_to_full_name
                FROM tasks t
                LEFT JOIN departments d ON t.department_id = d.id
                LEFT JOIN users u ON t.assigned_to = u.id
                ORDER BY t.created_at DESC";

        $result = mysqli_query($this->conn, $sql);
        $tasks = [];

        if ($result) {
            $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }

        return $tasks;
    }

    /**
     * Get tasks by department
     * @param int $department_id Department ID
     * @return array Array of tasks
     */
    public function getTasksByDepartment($department_id)
    {
        $sql = "SELECT t.*, d.name as department_name, u.username as assigned_to_name,
                       u.full_name as assigned_to_full_name
                FROM tasks t
                LEFT JOIN departments d ON t.department_id = d.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.department_id = ?
                ORDER BY t.created_at DESC";

        $tasks = [];

        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $department_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result) {
                $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
            }
        }

        return $tasks;
    }

    /**
     * Get tasks assigned to a user
     * @param int $user_id User ID
     * @return array Array of tasks
     */
    public function getTasksByUser($user_id)
    {
        $sql = "SELECT t.*, d.name as department_name, u.username as assigned_to_name,
                       u.full_name as assigned_to_full_name
                FROM tasks t
                LEFT JOIN departments d ON t.department_id = d.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.assigned_to = ?
                ORDER BY t.created_at DESC";

        $tasks = [];

        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result) {
                $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
            }
        }

        return $tasks;
    }

    /**
     * Check if a user is the assignee of a task
     * @param int $user_id User ID
     * @param int $task_id Task ID
     * @return bool True if user is the task assignee, false otherwise
     */
    public function isTaskOwner($user_id, $task_id)
    {
        $sql = "SELECT assigned_to FROM tasks WHERE id = ?";

        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $task_id);

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    return $row['assigned_to'] == $user_id;
                }
            }
        }

        return false;
    }

    /**
     * Get the frequency of repair tasks for a specific department and equipment.
     * @param int $department_id Department ID
     * @param string $equipment_name Equipment Name
     * @return int The count of repair tasks.
     */
    public function getRepairFrequencyByEquipment($department_id, $equipment_name)
    {
        $count = 0;
        // Join with task_requests table to filter by category and department
        $sql = "SELECT COUNT(t.id) AS repair_count
                FROM tasks t
                JOIN task_requests tr ON t.request_id = tr.id
                WHERE tr.category = 'repairs'
                AND tr.department_id = ?
                AND tr.equipment_name = ?"; // Assuming equipment_name is stored in task_requests

        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "is", $department_id, $equipment_name);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && $row = mysqli_fetch_assoc($result)) {
                $count = $row['repair_count'];
            }
            mysqli_stmt_close($stmt);
        }

        return $count;
    }

    /**
     * Get recent tasks based on user role
     * @param int $user_id User ID
     * @param string $user_role User role
     * @param int $department_id Department ID (for program head)
     * @param int $limit Number of tasks to return (default 10)
     * @return array Array of recent tasks
     */
    public function getRecentTasksByRole($user_id, $user_role, $department_id = null, $limit = 10)
    {
        $tasks = [];

        switch ($user_role) {
            case 'admin':
            case 'adaa':
                // Admin and ADAA can see all tasks
                $sql = "SELECT t.*, d.name as department_name, u.username as assigned_to_name, 
                               u.full_name as assigned_to_full_name, tr.title as request_title,
                               tr.equipment_name, tr.department_id, t.priority, tr.category,
                               requester.username as requester_username, requester.full_name as requester_full_name
                        FROM tasks t
                        LEFT JOIN task_requests tr ON t.request_id = tr.id
                        LEFT JOIN departments d ON tr.department_id = d.id
                        LEFT JOIN users u ON t.assigned_to = u.id
                        LEFT JOIN users requester ON tr.requester_id = requester.id
                        ORDER BY t.created_at DESC
                        LIMIT ?";

                if ($stmt = mysqli_prepare($this->conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $limit);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    if ($result) {
                        $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
                    }
                    mysqli_stmt_close($stmt);
                }
                break;

            case 'program_head':
            case 'program head':
                // Program head can only see tasks from their department that they approved
                // First get the program head's department from the database
                $dept_sql = "SELECT department_id FROM users WHERE id = ?";
                $program_head_department_id = null;
                if ($dept_stmt = mysqli_prepare($this->conn, $dept_sql)) {
                    mysqli_stmt_bind_param($dept_stmt, "i", $user_id);
                    mysqli_stmt_execute($dept_stmt);
                    $dept_result = mysqli_stmt_get_result($dept_stmt);
                    if ($dept_row = mysqli_fetch_assoc($dept_result)) {
                        $program_head_department_id = $dept_row['department_id'];
                    }
                    mysqli_stmt_close($dept_stmt);
                }

                if ($program_head_department_id) {
                    $sql = "SELECT t.*, d.name as department_name, u.username as assigned_to_name, 
                                   u.full_name as assigned_to_full_name, tr.title as request_title,
                                   tr.equipment_name, tr.department_id, t.priority, tr.category,
                                   requester.username as requester_username, requester.full_name as requester_full_name
                            FROM tasks t
                            LEFT JOIN task_requests tr ON t.request_id = tr.id
                            LEFT JOIN departments d ON tr.department_id = d.id
                            LEFT JOIN users u ON t.assigned_to = u.id
                            LEFT JOIN users requester ON tr.requester_id = requester.id
                            WHERE tr.department_id = ? AND tr.program_head_approval = 'approved'
                            ORDER BY t.created_at DESC
                            LIMIT ?";

                    if ($stmt = mysqli_prepare($this->conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "ii", $program_head_department_id, $limit);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        if ($result) {
                            $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
                break;

            case 'user':
            case 'staff':
            default:
                // Regular users can only see tasks they created or are assigned to
                $sql = "SELECT t.*, d.name as department_name, u.username as assigned_to_name, 
                               u.full_name as assigned_to_full_name, tr.title as request_title,
                               tr.equipment_name, tr.department_id, t.priority, tr.category,
                               requester.username as requester_username, requester.full_name as requester_full_name
                        FROM tasks t
                        LEFT JOIN task_requests tr ON t.request_id = tr.id
                        LEFT JOIN departments d ON tr.department_id = d.id
                        LEFT JOIN users u ON t.assigned_to = u.id
                        LEFT JOIN users requester ON tr.requester_id = requester.id
                        WHERE t.assigned_to = ? OR tr.requester_id = ?
                        ORDER BY t.created_at DESC
                        LIMIT ?";

                if ($stmt = mysqli_prepare($this->conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $limit);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    if ($result) {
                        $tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
                    }
                    mysqli_stmt_close($stmt);
                }
                break;
        }

        return $tasks;
    }
}