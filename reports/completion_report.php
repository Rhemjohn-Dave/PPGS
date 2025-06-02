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

// Query to get completion time statistics
$sql = "SELECT 
            t.id,
            tr.title,
            tr.priority,
            tr.category,
            d.name as department_name,
            u.username as assigned_to_name,
            t.created_at,
            t.completed_at,
            TIMESTAMPDIFF(HOUR, t.created_at, t.completed_at) as completion_time_hours
        FROM tasks t
        JOIN task_requests tr ON t.request_id = tr.id
        JOIN departments d ON tr.department_id = d.id
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.status = 'completed' 
        AND DATE_FORMAT(t.completed_at, '%Y-%m') = ?
        ORDER BY completion_time_hours DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "s", $month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Additional query to get average completion times by category
    $category_sql = "SELECT 
                        tr.category,
                        COUNT(t.id) as total_tasks,
                        AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.completed_at)) as avg_time
                    FROM tasks t
                    JOIN task_requests tr ON t.request_id = tr.id
                    WHERE t.status = 'completed' 
                    AND DATE_FORMAT(t.completed_at, '%Y-%m') = ?
                    GROUP BY tr.category
                    ORDER BY avg_time DESC";
    
    $cat_stmt = mysqli_prepare($conn, $category_sql);
    mysqli_stmt_bind_param($cat_stmt, "s", $month);
    mysqli_stmt_execute($cat_stmt);
    $category_result = mysqli_stmt_get_result($cat_stmt);
    
    // Additional query to get average completion times by priority
    $priority_sql = "SELECT 
                        tr.priority,
                        COUNT(t.id) as total_tasks,
                        AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.completed_at)) as avg_time
                     FROM tasks t
                     JOIN task_requests tr ON t.request_id = tr.id
                     WHERE t.status = 'completed' 
                     AND DATE_FORMAT(t.completed_at, '%Y-%m') = ?
                     GROUP BY tr.priority
                     ORDER BY FIELD(tr.priority, 'high', 'medium', 'low')";
    
    $priority_stmt = mysqli_prepare($conn, $priority_sql);
    mysqli_stmt_bind_param($priority_stmt, "s", $month);
    mysqli_stmt_execute($priority_stmt);
    $priority_result = mysqli_stmt_get_result($priority_stmt);
    
    // Get overall statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total_completed,
                    MIN(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as min_time,
                    MAX(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as max_time,
                    AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_time,
                    STD(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as std_time
                FROM tasks
                WHERE status = 'completed'
                AND DATE_FORMAT(completed_at, '%Y-%m') = ?";
    
    $stats_stmt = mysqli_prepare($conn, $stats_sql);
    mysqli_stmt_bind_param($stats_stmt, "s", $month);
    mysqli_stmt_execute($stats_stmt);
    $stats_result = mysqli_stmt_get_result($stats_stmt);
    $stats = mysqli_fetch_assoc($stats_result);
    
    // Start HTML output
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Task Completion Time Report - <?php echo date('F Y', strtotime($month)); ?></title>
        
        <!-- Include the same CSS as used in the main application -->
        <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
        <link href="../css/sb-admin-2.min.css" rel="stylesheet">
        <link href="../css/custom.css" rel="stylesheet">
        <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
        
        <style>
            @media print {
                .no-print {
                    display: none;
                }
            }
            .report-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            .completion-badge {
                display: inline-block;
                padding: 5px 10px;
                border-radius: 4px;
                color: white;
                font-weight: 600;
            }
            .completion-fast {
                background-color: #1cc88a;
            }
            .completion-normal {
                background-color: #36b9cc;
            }
            .completion-slow {
                background-color: #f6c23e;
                color: #5a5c69;
            }
            .completion-very-slow {
                background-color: #e74a3b;
            }
        </style>
    </head>
    <body class="bg-gradient-light">
        <div class="report-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary">Task Completion Time Report</h2>
                <div class="no-print">
                    <button onclick="window.print()" class="btn btn-primary shadow-sm">
                        <i class="fas fa-download fa-sm text-white-50"></i> Print Report
                    </button>
                    <a href="../index.php" class="btn btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            
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

            <!-- Completion Time Summary Cards -->
            <div class="row mb-4">
                <?php
                // Completion time ranges in hours
                $time_ranges = [
                    ['name' => 'Fast', 'min' => 0, 'max' => 24, 'icon' => 'fa-bolt', 'color' => 'success'],
                    ['name' => 'Normal', 'min' => 24, 'max' => 72, 'icon' => 'fa-clock', 'color' => 'info'],
                    ['name' => 'Slow', 'min' => 72, 'max' => 168, 'icon' => 'fa-hourglass-half', 'color' => 'warning'],
                    ['name' => 'Very Slow', 'min' => 168, 'max' => null, 'icon' => 'fa-hourglass-end', 'color' => 'danger']
                ];
                
                foreach($time_ranges as $range) {
                    // Count tasks in this range
                    mysqli_data_seek($result, 0);
                    $count = 0;
                    $total_hours = 0;
                    
                    while($row = mysqli_fetch_assoc($result)) {
                        if($row['completion_time_hours'] >= $range['min'] && 
                           ($range['max'] === null || $row['completion_time_hours'] < $range['max'])) {
                            $count++;
                            $total_hours += $row['completion_time_hours'];
                        }
                    }
                    
                    $avg_time = $count > 0 ? round($total_hours / $count, 1) : 0;
                    $percentage = mysqli_num_rows($result) > 0 ? round(($count / mysqli_num_rows($result)) * 100, 1) : 0;
                    ?>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-<?php echo $range['color']; ?> shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-<?php echo $range['color']; ?> text-uppercase mb-1">
                                            <?php echo $range['name']; ?> Completion
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $count; ?> Tasks (<?php echo $percentage; ?>%)
                                        </div>
                                        <div class="text-xs text-gray-600 mt-2">
                                            <?php if($range['max'] === null): ?>
                                                Over <?php echo round($range['min']); ?> hours
                                            <?php else: ?>
                                                <?php echo round($range['min']); ?>-<?php echo round($range['max']); ?> hours
                                            <?php endif; ?>
                                            <div class="small mt-1">Avg: <?php echo $avg_time; ?> hours</div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas <?php echo $range['icon']; ?> fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Task Completion Times</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Task ID</th>
                                    <th>Title</th>
                                    <th>Priority</th>
                                    <th>Created</th>
                                    <th>Completed</th>
                                    <th>Duration</th>
                                    <th>Completion Speed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($result, 0);
                                $total_hours = 0;
                                $task_count = 0;
                                
                                while($row = mysqli_fetch_assoc($result)){
                                    $task_count++;
                                    $total_hours += $row['completion_time_hours'];
                                    
                                    // Determine completion speed category
                                    $speed_class = 'completion-normal';
                                    $speed_label = 'Normal';
                                    
                                    if($row['completion_time_hours'] < 24) {
                                        $speed_class = 'completion-fast';
                                        $speed_label = 'Fast';
                                    } else if($row['completion_time_hours'] < 72) {
                                        $speed_class = 'completion-normal';
                                        $speed_label = 'Normal';
                                    } else if($row['completion_time_hours'] < 168) {
                                        $speed_class = 'completion-slow';
                                        $speed_label = 'Slow';
                                    } else {
                                        $speed_class = 'completion-very-slow';
                                        $speed_label = 'Very Slow';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['title'] ?? 'Task #' . $row['id']); ?></td>
                                        <td>
                                            <?php if($row['priority']): ?>
                                                <span class="badge badge-<?php echo strtolower($row['priority']) === 'high' ? 'danger' : (strtolower($row['priority']) === 'medium' ? 'warning' : 'success'); ?>">
                                                    <?php echo ucfirst($row['priority']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-info">Normal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($row['completed_at'])); ?></td>
                                        <td><?php echo round($row['completion_time_hours'], 2); ?> hours</td>
                                        <td>
                                            <span class="completion-badge <?php echo $speed_class; ?>">
                                                <?php echo $speed_label; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                
                                $avg_completion_time = $task_count > 0 ? round($total_hours / $task_count, 2) : 0;
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5">Average Completion Time:</th>
                                    <th colspan="2"><?php echo $avg_completion_time; ?> hours</th>
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
                        <li>This report shows completion times for tasks that were completed in <?php echo date('F Y', strtotime($month)); ?>.</li>
                        <li>Duration is measured in hours from task creation to task completion.</li>
                        <li>Tasks are categorized as:
                            <ul>
                                <li><strong>Fast:</strong> Less than 24 hours (1 day)</li>
                                <li><strong>Normal:</strong> 24-72 hours (1-3 days)</li>
                                <li><strong>Slow:</strong> 72-168 hours (3-7 days)</li>
                                <li><strong>Very Slow:</strong> More than 168 hours (7+ days)</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

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

        <!-- Initialize DataTables -->
        <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": [[5, "desc"]], // Sort by duration column by default
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
        });
        </script>
    </body>
    </html>
    <?php
    mysqli_stmt_close($stmt);
    mysqli_stmt_close($cat_stmt);
    mysqli_stmt_close($priority_stmt);
    mysqli_stmt_close($stats_stmt);
} else {
    echo "Error preparing statement: " . mysqli_error($conn);
}

// Connection is already closed by shutdown function
?> 