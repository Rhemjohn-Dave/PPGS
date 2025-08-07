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

// Query to get category-wise task statistics
$sql = "SELECT 
            IFNULL(tr.category, 'Uncategorized') as category,
            COUNT(t.id) as total_tasks,
            SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
            SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
            SUM(CASE WHEN t.status = 'pending_confirmation' THEN 1 ELSE 0 END) as pending_confirmation_tasks,
            SUM(CASE WHEN t.status = 'rejected' THEN 1 ELSE 0 END) as rejected_tasks
        FROM tasks t
        JOIN task_requests tr ON t.request_id = tr.id
        WHERE DATE_FORMAT(t.created_at, '%Y-%m') = ?
        GROUP BY tr.category
        ORDER BY total_tasks DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Count total tasks
    $total_all_tasks = 0;
    $total_completed = 0;
    $total_pending = 0;
    $total_in_progress = 0;
    $total_pending_confirmation = 0;
    $total_rejected = 0;
    
    // First scan to get totals
    while($row = mysqli_fetch_assoc($result)) {
        $total_all_tasks += $row['total_tasks'];
        $total_completed += $row['completed_tasks'];
        $total_pending += $row['pending_tasks'];
        $total_in_progress += $row['in_progress_tasks'];
        $total_pending_confirmation += $row['pending_confirmation_tasks'];
        $total_rejected += $row['rejected_tasks'];
    }
    
    // Reset result pointer
    mysqli_data_seek($result, 0);
    
    // Calculate category percentages
    $categories = [];
    while($row = mysqli_fetch_assoc($result)) {
        $row['percentage'] = $total_all_tasks > 0 ? round(($row['total_tasks'] / $total_all_tasks) * 100, 2) : 0;
        $row['completion_rate'] = $row['total_tasks'] > 0 ? round(($row['completed_tasks'] / $row['total_tasks']) * 100, 2) : 0;
        
        // Convert category values to more readable form
        $category_name = ucfirst($row['category']);
        switch($row['category']) {
            case 'printing':
                $category_name = 'Printing/Risograph';
                break;
            case 'repairs':
                $category_name = 'Repairs';
                break;
            case 'maintenance':
                $category_name = 'Maintenance';
                break;
            case 'instructional':
                $category_name = 'Instructional Materials';
                break;
            case 'clerical':
                $category_name = 'Clerical/Typing';
                break;
            case 'inventory':
                $category_name = 'Inventory';
                break;
            case 'event':
                $category_name = 'Event Assistance';
                break;
        }
        $row['category_name'] = $category_name;
        $categories[] = $row;
    }
    
    // Calculate overall completion rate
    $overall_completion_rate = $total_all_tasks > 0 ? round(($total_completed / $total_all_tasks) * 100, 2) : 0;

    // Set page title for header
    $page_title = "Category-wise Task Report";

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
                        <h1 class="h3 mb-0 text-gray-800">Category-wise Task Report</h1>
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

                    <!-- Report Summary Section -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Report Summary</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Summary cards -->
                                    <div class="row">
                                        <div class="col-xl-3 col-md-6 mb-4">
                                            <div class="card border-left-primary shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Tasks</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_all_tasks; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-xl-3 col-md-6 mb-4">
                                            <div class="card border-left-success shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed Tasks</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_completed; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-check fa-2x text-gray-300"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-xl-3 col-md-6 mb-4">
                                            <div class="card border-left-warning shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Tasks</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_pending; ?></div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-xl-3 col-md-6 mb-4">
                                            <div class="card border-left-info shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Completion Rate</div>
                                                            <div class="row no-gutters align-items-center">
                                                                <div class="col-auto">
                                                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $overall_completion_rate; ?>%</div>
                                                                </div>
                                                                <div class="col">
                                                                    <div class="progress progress-sm mr-2">
                                                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $overall_completion_rate; ?>%" aria-valuenow="<?php echo $overall_completion_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
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
                    
                    <?php if (!empty($categories)): ?>
                    <!-- Category Task Distribution -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Category Task Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Total Tasks</th>
                                            <th>Completed</th>
                                            <th>Pending</th>
                                            <th>In Progress</th>
                                            <th>Pending Confirmation</th>
                                            <th>Rejected</th>
                                            <th>Completion Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                            <td><?php echo $category['total_tasks']; ?></td>
                                            <td><?php echo $category['completed_tasks']; ?></td>
                                            <td><?php echo $category['pending_tasks']; ?></td>
                                            <td><?php echo $category['in_progress_tasks']; ?></td>
                                            <td><?php echo $category['pending_confirmation_tasks']; ?></td>
                                            <td><?php echo $category['rejected_tasks']; ?></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $category['completion_rate']; ?>%" aria-valuenow="<?php echo $category['completion_rate']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $category['completion_rate']; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Notes</h6>
                        </div>
                        <div class="card-body">
                            <p>This report shows the distribution of tasks by category for the selected period: <strong><?php echo date('F Y', strtotime($month)); ?></strong></p>
                            <ul>
                                <li><strong>Total Tasks:</strong> Total number of tasks in each category</li>
                                <li><strong>Completed:</strong> Tasks that have been completed and confirmed</li>
                                <li><strong>Pending:</strong> Tasks that have not started yet</li>
                                <li><strong>In Progress:</strong> Tasks that are currently being worked on</li>
                                <li><strong>Pending Confirmation:</strong> Tasks that are completed and waiting for requester confirmation</li>
                                <li><strong>Rejected:</strong> Tasks that were rejected by the requester</li>
                                <li><strong>Completion Rate:</strong> Percentage of tasks completed out of total tasks</li>
                            </ul>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <strong>No data found!</strong> There are no tasks for the selected period.
                    </div>
                    <?php endif; ?>
                    
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; TUP Visayas PPGS Task Management <?php echo date('Y'); ?></span>
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

    <!-- DataTables JavaScript -->
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });
    </script>
</body>
</html>
<?php
}
?> 