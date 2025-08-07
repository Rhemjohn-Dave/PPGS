<?php
session_start();
require_once 'database/connection.php';
require_once 'controllers/TaskController.php';
require_once 'controllers/DepartmentController.php';
require_once 'controllers/UserController.php';
require_once 'controllers/NotificationController.php';

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize controllers
$taskController = new TaskController($conn);
$departmentController = new DepartmentController($conn);
$notificationController = new NotificationController($conn);

// Get user role and department
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['role'] ?? '';
$department_id = $_SESSION['department_id'] ?? 0;

// Debug information
error_log("User ID: " . $user_id);
error_log("User Role: " . $user_role);
error_log("Department ID: " . $department_id);

// Get all departments for the dropdown
$departments = $departmentController->getAll();

// Check if user is logged in and has appropriate role
if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true ||
    !in_array($_SESSION["role"], ["user", "program head", "adaa"])
) {
    header("location: index.php");
    exit;
}

$title = $category = $reason = "";
$title_err = $category_err = $reason_err = $attachment_err = "";
$category_fields = [];

// Get user's department
$user_department = null;
$dept_sql = "SELECT d.id, d.name FROM departments d 
             INNER JOIN users u ON u.department_id = d.id 
             WHERE u.id = ?";
if ($dept_stmt = mysqli_prepare($conn, $dept_sql)) {
    mysqli_stmt_bind_param($dept_stmt, "i", $_SESSION["user_id"]);
    mysqli_stmt_execute($dept_stmt);
    $dept_result = mysqli_stmt_get_result($dept_stmt);
    if ($dept_row = mysqli_fetch_assoc($dept_result)) {
        $user_department = $dept_row['id'];
    }
    mysqli_stmt_close($dept_stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate title
    if (empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title.";
    } else {
        $title = trim($_POST["title"]);
    }

    // Validate category
    if (empty(trim($_POST["category"]))) {
        $category_err = "Please select a category.";
    } else {
        $category = trim($_POST["category"]);
    }

    // Validate reason
    if (empty(trim($_POST["reason"]))) {
        $reason_err = "Please enter a reason.";
    } else {
        $reason = trim($_POST["reason"]);
    }

    // Validate attachment for printing category
    if ($category === 'printing' && (!isset($_FILES['attachment']) || $_FILES['attachment']['size'] === 0)) {
        $attachment_err = "Please attach a file for printing request.";
    }

    // Check input errors before inserting in database
    if (empty($title_err) && empty($category_err) && empty($reason_err) && empty($attachment_err)) {
        try {
            // Validate session variables
            if (!isset($_SESSION["user_id"])) {
                throw new Exception("User session not found. Please login again.");
            }

            // Validate department exists
            if (!$user_department) {
                throw new Exception("User department not found. Please contact administrator.");
            }

            // Prepare an insert statement
            $sql = "INSERT INTO task_requests (requester_id, department_id, title, reason, category, num_copies, paper_size, paper_type, equipment_name, problem_description, due_date, status, program_head_approval, adaa_approval, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', 'pending', NOW())";

            if ($stmt = mysqli_prepare($conn, $sql)) {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param(
                    $stmt,
                    "iisssssssss",
                    $param_requester_id,
                    $param_department_id,
                    $param_title,
                    $param_reason,
                    $param_category,
                    $param_num_copies,
                    $param_paper_size,
                    $param_paper_type,
                    $param_equipment_name,
                    $param_problem_description,
                    $param_due_date
                );

                // Set parameters
                $param_requester_id = $_SESSION["user_id"];
                $param_department_id = $user_department;
                $param_title = $title;
                $param_reason = $reason;
                $param_category = $category;

                // Set category-specific parameters, default to NULL if not provided
                $param_num_copies = $_POST['num_copies'] ?? null;
                $param_paper_size = $_POST['paper_size'] ?? null;
                $param_paper_type = $_POST['paper_type'] ?? null;
                $param_equipment_name = $_POST['equipment_name'] ?? null;
                $param_problem_description = $_POST['problem_description'] ?? null;

                $param_due_date = $_POST['due_date'];

                // Execute the statement
                if (mysqli_stmt_execute($stmt)) {
                    // Get the ID of the inserted request
                    $request_id = mysqli_insert_id($conn);

                    // Log successful submission
                    error_log("Task request submitted successfully. Request ID: " . $request_id);

                    // Send notifications to program heads for the department
                    $dept_head_sql = "SELECT id FROM users WHERE role = 'program head' AND department_id = ?";
                    if ($dept_head_stmt = mysqli_prepare($conn, $dept_head_sql)) {
                        mysqli_stmt_bind_param($dept_head_stmt, "i", $user_department);
                        mysqli_stmt_execute($dept_head_stmt);
                        $dept_head_result = mysqli_stmt_get_result($dept_head_stmt);

                        $program_heads = [];
                        while ($head_row = mysqli_fetch_assoc($dept_head_result)) {
                            $program_heads[] = $head_row['id'];
                        }

                        if (!empty($program_heads)) {
                            // Include user name in the notification
                            $user_name = $_SESSION["username"] ?? "A user";
                            $message = "New task request: \"" . $title . "\" submitted by " . $user_name . ".";
                            $link = "task_approvals.php";
                            $notificationController->sendNotification($program_heads, $message, $link);

                            // Log notification attempt
                            error_log("Notification sent to program heads: " . implode(',', $program_heads));
                        } else {
                            // Try with exact role name 'program_head' (with underscore)
                            mysqli_stmt_close($dept_head_stmt);
                            $dept_head_sql = "SELECT id FROM users WHERE role = 'program_head' AND department_id = ?";
                            if ($dept_head_stmt = mysqli_prepare($conn, $dept_head_sql)) {
                                mysqli_stmt_bind_param($dept_head_stmt, "i", $user_department);
                                mysqli_stmt_execute($dept_head_stmt);
                                $dept_head_result = mysqli_stmt_get_result($dept_head_stmt);

                                $program_heads = [];
                                while ($head_row = mysqli_fetch_assoc($dept_head_result)) {
                                    $program_heads[] = $head_row['id'];
                                }

                                if (!empty($program_heads)) {
                                    // Include user name in the notification
                                    $user_name = $_SESSION["username"] ?? "A user";
                                    $message = "New task request: \"" . $title . "\" submitted by " . $user_name . ".";
                                    $link = "task_approvals.php";
                                    $notificationController->sendNotification($program_heads, $message, $link);

                                    // Log notification attempt
                                    error_log("Notification sent to program_head (with underscore): " . implode(',', $program_heads));
                                } else {
                                    error_log("No program heads found for department ID: " . $user_department);
                                }
                                mysqli_stmt_close($dept_head_stmt);
                            }
                        }
                    }

                    // Set success message
                    $_SESSION['success_message'] = "Task request submitted successfully.";

                    // Redirect to tasks page
                    header("location: tasks.php");
                    exit();
                } else {
                    throw new Exception("Error executing statement: " . mysqli_error($conn));
                }

                // Close statement
                mysqli_stmt_close($stmt);
            } else {
                throw new Exception("Error preparing statement: " . mysqli_error($conn));
            }
        } catch (Exception $e) {
            error_log("Error in task request submission: " . $e->getMessage());
            $_SESSION['error_message'] = "Failed to submit task request: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Please fill in all required fields correctly.";
    }
}

// Set page title for header
$page_title = "Request Task";

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
                <h1 class="h3 mb-0 text-gray-800">Request New Task</h1>
            </div>

            <!-- Content Row -->
            <div class="row">
                <div class="col-xl-12 col-lg-12">
                    <div class="card shadow mb-4">
                        <!-- Card Header -->
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Task Details</h6>
                        </div>
                        <!-- Card Body -->
                        <div class="card-body">
                            <form id="taskRequestForm" method="POST"
                                action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>"
                                enctype="multipart/form-data">
                                <input type="hidden" name="action" value="create">
                                <input type="hidden" name="created_by" value="<?php echo $user_id; ?>">

                                <?php if (isset($_SESSION['error_message'])) { ?>
                                    <div class="alert alert-danger">
                                        <?php echo $_SESSION['error_message'];
                                        unset($_SESSION['error_message']); ?>
                                    </div>
                                <?php } ?>

                                <?php if (isset($_SESSION['success_message'])) { ?>
                                    <div class="alert alert-success">
                                        <?php echo $_SESSION['success_message'];
                                        unset($_SESSION['success_message']); ?>
                                    </div>
                                <?php } ?>

                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" name="title"
                                        class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $title; ?>">
                                    <span class="invalid-feedback"><?php echo $title_err; ?></span>
                                </div>

                                <div class="form-group">
                                    <label for="department">Department</label>
                                    <input type="text" class="form-control" value="<?php
                                    $dept_name_sql = "SELECT name FROM departments WHERE id = ?";
                                    if ($dept_name_stmt = mysqli_prepare($conn, $dept_name_sql)) {
                                        mysqli_stmt_bind_param($dept_name_stmt, "i", $user_department);
                                        mysqli_stmt_execute($dept_name_stmt);
                                        $dept_name_result = mysqli_stmt_get_result($dept_name_stmt);
                                        if ($dept_name_row = mysqli_fetch_assoc($dept_name_result)) {
                                            echo htmlspecialchars($dept_name_row['name']);
                                        }
                                        mysqli_stmt_close($dept_name_stmt);
                                    }
                                    ?>" readonly>
                                </div>

                                <div class="form-group">
                                    <label for="category">Category</label>
                                    <select name="category"
                                        class="form-control <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>"
                                        id="category">
                                        <option value="">Select Category</option>
                                        <option value="printing" <?php echo ($category == 'printing') ? 'selected' : ''; ?>>Printing/Risograph</option>
                                        <option value="repairs" <?php echo ($category == 'repairs') ? 'selected' : ''; ?>>
                                            Repairs</option>
                                        <option value="maintenance" <?php echo ($category == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                        <option value="instructional" <?php echo ($category == 'instructional') ? 'selected' : ''; ?>>Instructional Materials</option>
                                        <option value="clerical" <?php echo ($category == 'clerical') ? 'selected' : ''; ?>>Clerical</option>
                                        <option value="inventory" <?php echo ($category == 'inventory') ? 'selected' : ''; ?>>Inventory</option>
                                        <option value="event" <?php echo ($category == 'event') ? 'selected' : ''; ?>>
                                            Event Assistance</option>
                                    </select>
                                    <span class="invalid-feedback"><?php echo $category_err; ?></span>
                                </div>

                                <div class="form-group">
                                    <label for="reason">Reason</label>
                                    <textarea name="reason"
                                        class="form-control <?php echo (!empty($reason_err)) ? 'is-invalid' : ''; ?>"><?php echo $reason; ?></textarea>
                                    <span class="invalid-feedback"><?php echo $reason_err; ?></span>
                                </div>

                                <div class="form-group">
                                    <label for="due_date">Due Date</label>
                                    <input type="date" name="due_date" class="form-control"
                                        min="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <!-- Category-specific fields will be dynamically added here -->
                                <div id="categoryFields"></div>

                                <div class="form-group">
                                    <label for="attachment" class="form-label">Attachment <span id="attachmentRequired"
                                            style="display: none; color: red;">*</span></label>
                                    <input type="file" class="form-control" id="attachment" name="attachment">
                                    <small class="text-muted">Maximum file size: 5MB</small>
                                    <span class="invalid-feedback"><?php echo $attachment_err; ?></span>
                                </div>

                                <div class="form-group">
                                    <input type="submit" class="btn btn-primary" value="Submit">
                                    <a href="tasks.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->

    <!-- Footer -->
    <?php include 'includes/components/footer.php'; ?>
</div>
<!-- End of Content Wrapper -->

<!-- Include Scripts -->
<?php include 'includes/components/footer_scripts.php'; ?>

<!-- Custom scripts for this page -->
<script>
    $(document).ready(function () {
        // Handle category change
        $('#category').change(function () {
            var category = $(this).val();
            var fieldsHtml = '';

            // Show/hide required indicator for attachment
            if (category === 'printing') {
                $('#attachmentRequired').show();
                $('#attachment').prop('required', true);
            } else {
                $('#attachmentRequired').hide();
                $('#attachment').prop('required', false);
            }

            switch (category) {
                case 'printing':
                    fieldsHtml = `
                        <div class="form-group">
                            <label for="num_copies">Number of Copies</label>
                            <input type="number" name="num_copies" class="form-control" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="paper_size">Paper Size</label>
                            <select name="paper_size" class="form-control" required>
                                <option value="letter">Letter</option>
                                <option value="legal">Legal</option>
                                <option value="a4">A4</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="paper_type">Paper Type</label>
                            <select name="paper_type" class="form-control" required>
                                <option value="bond">Bond Paper</option>
                                <option value="colored">Colored Paper</option>
                                <option value="cardboard">Cardboard</option>
                            </select>
                        </div>
                    `;
                    break;
                case 'repairs':
                    fieldsHtml = `
                        <div class="form-group">
                            <label for="equipment_name">Equipment Name</label>
                            <input type="text" name="equipment_name" class="form-control">
                        </div>
                    `;
                    break;
                case 'maintenance':
                    fieldsHtml = `
                        <div class="form-group">
                            <label for="area">Area</label>
                            <input type="text" name="area" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="maintenance_type">Type of Maintenance</label>
                            <select name="maintenance_type" class="form-control">
                                <option value="preventive">Preventive</option>
                                <option value="corrective">Corrective</option>
                                <option value="predictive">Predictive</option>
                            </select>
                        </div>
                    `;
                    break;
                case 'instructional':
                    fieldsHtml = `
                        <div class="form-group">
                            <label for="subject">Subject/Department</label>
                            <input type="text" name="subject" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="grade_level">Grade Level</label>
                            <input type="text" name="grade_level" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="template">Template File</label>
                            <input type="file" name="template" class="form-control-file" accept=".doc,.docx,.pdf">
                        </div>
                    `;
                    break;
                case 'clerical':
                    fieldsHtml = `
                        <div class="form-group">
                            <label for="document_type">Document Type</label>
                            <input type="text" name="document_type" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="num_pages">Number of Pages</label>
                            <input type="number" name="num_pages" class="form-control" min="1">
                        </div>
                    `;
                    break;
                case 'inventory':
                    fieldsHtml = `
                        <div class="form-group">
                            <label for="inventory_type">Inventory Type</label>
                            <select name="inventory_type" class="form-control">
                                <option value="supplies">Supplies</option>
                                <option value="equipment">Equipment</option>
                                <option value="furniture">Furniture</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                    `;
                    break;
                case 'event':
                    fieldsHtml = `
                        <div class="form-group">
                            <label for="event_name">Event Name</label>
                            <input type="text" name="event_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="event_date">Event Date</label>
                            <input type="date" name="event_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="assistance_type">Type of Assistance</label>
                            <select name="assistance_type" class="form-control">
                                <option value="logistics">Logistics</option>
                                <option value="technical">Technical</option>
                                <option value="administrative">Administrative</option>
                            </select>
    </div>
                    `;
                    break;
            }

            $('#categoryFields').html(fieldsHtml);
        });

        // Trigger change event on page load if category is already selected
        if ($('#category').val()) {
            $('#category').trigger('change');
        }
    });
</script>

</body>

</html>

<?php
// Connection is already closed by shutdown function
?>