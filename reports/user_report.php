<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../index.php");
    exit;
}

// Get the selected month
$month = isset($_POST['month']) ? $_POST['month'] : date('Y-m');

// Query to get user-wise task statistics
$sql = "SELECT 
            u.id as user_id,
            u.username,
            u.role,
            COUNT(t.id) as total_tasks,
            SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
            SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
            AVG(TIMESTAMPDIFF(HOUR, t.created_at, 
                CASE WHEN t.status = 'completed' THEN t.completed_at ELSE NOW() END)) as avg_time
        FROM users u
        LEFT JOIN tasks t ON u.id = t.assigned_to AND DATE_FORMAT(t.created_at, '%Y-%m') = ?
        GROUP BY u.id, u.username, u.role
        HAVING total_tasks > 0
        ORDER BY completed_tasks DESC, total_tasks DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Set page title for header
    $page_title = "User-wise Task Report";
    
    // Replace component includes with standalone HTML
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
    
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
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
                        <h1 class="h3 mb-0 text-gray-800">User-wise Task Report</h1>
                        <div class="no-print">
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
                                <div class="col-md-6 align-self-end mb-3">
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
                                    <p><strong>Generated By:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php
                    // Calculate totals first
                    $total_tasks = 0;
                    $total_completed = 0;
                    $total_pending = 0;
                    $total_in_progress = 0;
                    $total_completion_time = 0;
                    $users_count = 0;
                    
                    // First pass to calculate totals
                    while($row = mysqli_fetch_assoc($result)){
                        $total_tasks += $row['total_tasks'];
                        $total_completed += $row['completed_tasks'];
                        $total_pending += $row['pending_tasks'];
                        $total_in_progress += $row['in_progress_tasks'];
                        
                        if($row['avg_time'] !== null){
                            $total_completion_time += $row['avg_time'];
                            $users_count++;
                        }
                    }
                    
                    // Reset result pointer
                    mysqli_data_seek($result, 0);
                    
                    $overall_completion_rate = $total_tasks > 0 ? 
                        round(($total_completed / $total_tasks) * 100, 2) : 0;
                    $avg_completion_time = $users_count > 0 ? 
                        round($total_completion_time / $users_count, 2) : 0;
                    ?>
    
                    <!-- Report Summary -->
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
    
                        <!-- Users With Tasks Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Active Users</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $users_count; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
    
                    <!-- User Task Distribution Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">User Task Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Role</th>
                                            <th>Total Tasks</th>
                                            <th>Completed</th>
                                            <th>Pending</th>
                                            <th>In Progress</th>
                                            <th>Completion Rate</th>
                                            <th>Avg. Time (hours)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        while($row = mysqli_fetch_assoc($result)){
                                            $completion_rate = $row['total_tasks'] > 0 ? 
                                                round(($row['completed_tasks'] / $row['total_tasks']) * 100, 2) : 0;
                                            
                                            // Determine badge class
                                            $badge_class = 'badge-secondary';
                                            if($row['role'] == 'admin') {
                                                $badge_class = 'badge-danger';
                                            } else if($row['role'] == 'program head') {
                                                $badge_class = 'badge-success';
                                            } else if($row['role'] == 'staff') {
                                                $badge_class = 'badge-primary';
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo htmlspecialchars($row['role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $row['total_tasks']; ?></td>
                                                <td><?php echo $row['completed_tasks']; ?></td>
                                                <td><?php echo $row['pending_tasks']; ?></td>
                                                <td><?php echo $row['in_progress_tasks']; ?></td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-success" role="progressbar" 
                                                             style="width: <?php echo $completion_rate; ?>%" 
                                                             aria-valuenow="<?php echo $completion_rate; ?>" 
                                                             aria-valuemin="0" aria-valuemax="100">
                                                            <?php echo $completion_rate; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $row['avg_time'] ? round($row['avg_time'], 2) : '-'; ?></td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="2">Average/Total</th>
                                            <th><?php echo $total_tasks; ?></th>
                                            <th><?php echo $total_completed; ?></th>
                                            <th><?php echo $total_pending; ?></th>
                                            <th><?php echo $total_in_progress; ?></th>
                                            <th>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?php echo $overall_completion_rate; ?>%" 
                                                         aria-valuenow="<?php echo $overall_completion_rate; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $overall_completion_rate; ?>%
                                                    </div>
                                                </div>
                                            </th>
                                            <th><?php echo $avg_completion_time; ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
    
                    <!-- Notes Section -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Notes</h6>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>This report shows task distribution across users for <?php echo date('F Y', strtotime($month)); ?>.</li>
                                <li>Completion rate is calculated as (completed tasks / total tasks) × 100%.</li>
                                <li>Average time is measured in hours from task creation to completion.</li>
                                <li>Users with no assigned tasks are not included in this report.</li>
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

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

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
            "order": [[2, "desc"]], // Sort by total tasks column by default
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
?> 