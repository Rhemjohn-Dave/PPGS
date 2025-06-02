<?php
session_start();
require_once "config/database.php";

// Check if user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: index.php");
    exit;
}

// Set page title for header
$page_title = "Generate Reports";

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
                <h1 class="h3 mb-0 text-gray-800">Generate Reports</h1>
            </div>

            <!-- Content Row -->
            <div class="row">
                <!-- Department-wise Task Report -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Department-wise Tasks</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Monthly Report</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-building fa-2x text-gray-300"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <form action="reports/department_report.php" method="post" target="_blank">
                                    <div class="form-group">
                                        <label for="dept_month">Select Month</label>
                                        <input type="month" class="form-control" id="dept_month" name="month" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Generate Report</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category-wise Task Report -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Category-wise Tasks</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Monthly Report</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-tags fa-2x text-gray-300"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <form action="reports/category_wise_task_report.php" method="post" target="_blank">
                                    <div class="form-group">
                                        <label for="category_month">Select Month</label>
                                        <input type="month" class="form-control" id="category_month" name="month" required>
                                    </div>
                                    <button type="submit" class="btn btn-success">Generate Report</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status-wise Task Report -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Task Status Report</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Monthly Report</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <form action="reports/status_report.php" method="post" target="_blank">
                                    <div class="form-group">
                                        <label for="status_month">Select Month</label>
                                        <input type="month" class="form-control" id="status_month" name="month" required>
                                    </div>
                                    <button type="submit" class="btn btn-info">Generate Report</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User-wise Task Report -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        User-wise Tasks</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Monthly Report</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <form action="reports/user_report.php" method="post" target="_blank">
                                    <div class="form-group">
                                        <label for="user_month">Select Month</label>
                                        <input type="month" class="form-control" id="user_month" name="month" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning">Generate Report</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Priority-wise Task Report -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Priority-wise Tasks</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Monthly Report</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <form action="reports/priority_report.php" method="post" target="_blank">
                                    <div class="form-group">
                                        <label for="priority_month">Select Month</label>
                                        <input type="month" class="form-control" id="priority_month" name="month" required>
                                    </div>
                                    <button type="submit" class="btn btn-danger">Generate Report</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completion Time Report -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-secondary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                        Task Completion Time</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Monthly Report</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <form action="reports/completion_report.php" method="post" target="_blank">
                                    <div class="form-group">
                                        <label for="completion_month">Select Month</label>
                                        <input type="month" class="form-control" id="completion_month" name="month" required>
                                    </div>
                                    <button type="submit" class="btn btn-secondary">Generate Report</button>
                                </form>
                            </div>
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

</body>
</html>

<?php
// Connection is already closed by shutdown function
?> 