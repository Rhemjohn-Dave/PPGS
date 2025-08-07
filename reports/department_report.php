<?php
session_start();
require_once "../config/database.php";
require_once "../controllers/DepartmentController.php";

// Check if user is logged in and has appropriate permissions
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || 
   !in_array($_SESSION["role"], ["admin", "program_head", "program head", "adaa"])){
    header("location: ../index.php");
    exit;
}

// Initialize variables
$departmentController = new DepartmentController($conn);
$month = isset($_POST['month']) ? $_POST['month'] : date('Y-m');
$departmentFilter = '';
$filterParams = [];
$filterTypes = '';
$departments = [];

// Check if a specific department is requested (for program heads)
$departmentId = null;
if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
    $departmentId = (int)$_GET['department_id'];
}

// Program heads can only view their own department
if ($_SESSION["role"] === "program_head" || $_SESSION["role"] === "program head") {
    $departmentId = $_SESSION["department_id"];
}

// Initialize SQL query
$sql = "SELECT 
            d.name as department_name,
            d.id as department_id,
            COUNT(t.id) as total_tasks,
            SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
            SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
            SUM(CASE WHEN t.status = 'pending_confirmation' THEN 1 ELSE 0 END) as pending_confirmation_tasks,
            SUM(CASE WHEN t.status = 'rejected' THEN 1 ELSE 0 END) as rejected_tasks
        FROM departments d
        LEFT JOIN task_requests tr ON d.id = tr.department_id
        LEFT JOIN tasks t ON tr.id = t.request_id";

// Add filters
$whereConditions = [];
$filterParams = [];
$filterTypes = "";

// Add month filter
if (!empty($month)) {
    $whereConditions[] = "DATE_FORMAT(t.created_at, '%Y-%m') = ?";
    $filterParams[] = $month;
    $filterTypes .= "s";
}

// Add department filter if applicable
if (!empty($departmentId)) {
    $whereConditions[] = "d.id = ?";
    $filterParams[] = $departmentId;
    $filterTypes .= "i";
}

// Compile WHERE clause
if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

// Complete the query
$sql .= " GROUP BY d.id, d.name ORDER BY d.name";

// Execute the query
if($stmt = mysqli_prepare($conn, $sql)){
    if (!empty($filterParams)) {
        mysqli_stmt_bind_param($stmt, $filterTypes, ...$filterParams);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Replace the component includes with direct HTML
    $page_title = "Department-wise Task Report";
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - TUP Visayas PPGS Task Management</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <!-- Toastr CSS for notifications -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Department-wise Task Report</h1>
                        <div>
                            <button onclick="window.print()" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                                <i class="fas fa-download fa-sm text-white-50"></i> Print Report
                            </button>
                            <a href="../index.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
    
                    <!-- Report Period Selection -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Report Period</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="month" class="form-label">Select Month</label>
                                    <input type="month" class="form-control" id="month" name="month" value="<?php echo $month; ?>" required>
                                </div>
                                <?php if (!isset($departmentId) && $_SESSION["role"] === "admin"): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="department_id" class="form-label">Select Department</label>
                                    <select class="form-control" id="department_id" name="department_id">
                                        <option value="">All Departments</option>
                                        <?php 
                                        $departments = $departmentController->getAll();
                                        foreach($departments as $dept) {
                                            $selected = ($departmentId == $dept['id']) ? 'selected' : '';
                                            echo '<option value="'.$dept['id'].'" '.$selected.'>'.htmlspecialchars($dept['name']).'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <div class="<?php echo (!isset($departmentId) && $_SESSION["role"] === "admin") ? 'col-md-12' : 'col-md-6 align-self-end'; ?> mb-3">
                                    <button type="submit" class="btn btn-primary">Generate Report</button>
                                </div>
                            </form>
                        </div>
                    </div>
    
                    <!-- Report Information -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Report Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>Report Period:</strong> <?php echo date('F Y', strtotime($month)); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Generated On:</strong> <?php echo date('F j, Y g:i A'); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <?php if (!empty($departmentId)): 
                                        $department = $departmentController->getById($departmentId);
                                    ?>
                                    <p><strong>Department:</strong> <?php echo htmlspecialchars($department['name']); ?></p>
                                    <?php else: ?>
                                    <p><strong>Department:</strong> All Departments</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
    
                    <!-- Report Summary -->
                    <?php
                    $total_tasks = 0;
                    $total_completed = 0;
                    $total_pending = 0;
                    $total_in_progress = 0;
                    $total_pending_confirmation = 0;
                    $total_rejected = 0;
                    
                    if (mysqli_num_rows($result) > 0) {
                        // First scan to get totals
                        while($row = mysqli_fetch_assoc($result)){
                            $total_tasks += $row['total_tasks'];
                            $total_completed += $row['completed_tasks'];
                            $total_pending += $row['pending_tasks'];
                            $total_in_progress += $row['in_progress_tasks'];
                            $total_pending_confirmation += $row['pending_confirmation_tasks'];
                            $total_rejected += $row['rejected_tasks'];
                        }
                        
                        // Reset result pointer
                        mysqli_data_seek($result, 0);
                        
                        // Calculate overall completion rate
                        $overall_completion_rate = $total_tasks > 0 ? 
                            round(($total_completed / $total_tasks) * 100, 2) : 0;
                    ?>
                    <div class="row">
                        <!-- Total Tasks Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Tasks</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_tasks; ?></div>
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
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_completed; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
    
                        <!-- Pending Tasks Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Pending Tasks</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_pending + $total_in_progress + $total_pending_confirmation; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-pause fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
    
                        <!-- Completion Rate Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Completion Rate
                                            </div>
                                            <div class="row no-gutters align-items-center">
                                                <div class="col-auto">
                                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $overall_completion_rate; ?>%</div>
                                                </div>
                                                <div class="col">
                                                    <div class="progress progress-sm mr-2">
                                                        <div class="progress-bar bg-info" role="progressbar"
                                                            style="width: <?php echo $overall_completion_rate; ?>%" aria-valuenow="<?php echo $overall_completion_rate; ?>" aria-valuemin="0"
                                                            aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
    
                    <!-- Department Task Distribution Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Department Task Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Department</th>
                                            <th>Total Tasks</th>
                                            <th>Completed</th>
                                            <th>In Progress</th>
                                            <th>Pending</th>
                                            <th>Pending Conf.</th>
                                            <th>Rejected</th>
                                            <th>Completion Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        while($row = mysqli_fetch_assoc($result)){
                                            $completion_rate = $row['total_tasks'] > 0 ? 
                                                round(($row['completed_tasks'] / $row['total_tasks']) * 100, 2) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                                                <td><?php echo $row['total_tasks']; ?></td>
                                                <td><?php echo $row['completed_tasks']; ?></td>
                                                <td><?php echo $row['in_progress_tasks']; ?></td>
                                                <td><?php echo $row['pending_tasks']; ?></td>
                                                <td><?php echo $row['pending_confirmation_tasks']; ?></td>
                                                <td><?php echo $row['rejected_tasks']; ?></td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-success" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $completion_rate; ?>%"
                                                             aria-valuenow="<?php echo $completion_rate; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                            <?php echo $completion_rate; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th><?php echo $total_tasks; ?></th>
                                            <th><?php echo $total_completed; ?></th>
                                            <th><?php echo $total_in_progress; ?></th>
                                            <th><?php echo $total_pending; ?></th>
                                            <th><?php echo $total_pending_confirmation; ?></th>
                                            <th><?php echo $total_rejected; ?></th>
                                            <th><?php echo $overall_completion_rate; ?>%</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php } else { ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">No Data Found</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                No task data found for the selected period.
                                <?php if(!empty($month)): ?>
                                <p>Try selecting a different month or department.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
    
                    <!-- Notes Section -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Notes</h6>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>This report shows task distribution across departments for <?php echo date('F Y', strtotime($month)); ?>.</li>
                                <li>Completion rate is calculated as (completed tasks / total tasks) × 100%.</li>
                                <li>Tasks in "Pending Confirmation" status are awaiting approval from the requester.</li>
                                <li>Tasks with no assigned department are not included in this report.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->
    
            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; TUP Visayas PPGS Task Management System <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Bootstrap core JavaScript-->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="../js/demo/datatables-demo.js"></script>
    
    <!-- Initialize DataTables -->
    <script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "order": [[1, "desc"]], // Sort by total tasks column by default
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
        });
    });
    </script>
</body>
</html>
    
    <?php
    mysqli_stmt_close($stmt);
} else {
    echo "Error preparing statement: " . mysqli_error($conn);
}

// Close connection has been removed as it's handled by the shutdown function
?> 