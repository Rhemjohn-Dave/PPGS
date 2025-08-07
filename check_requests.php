<?php
session_start();
require_once "config/database.php";

// Security check - only allow admins to access this page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: login.php");
    exit;
}

// Get all task requests from the database
$sql = "SELECT tr.*, 
        u.full_name as requester_name, 
        d.name as department_name 
        FROM task_requests tr 
        JOIN users u ON tr.requester_id = u.id 
        JOIN departments d ON tr.department_id = d.id 
        ORDER BY tr.created_at DESC";

$requests = [];
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_assoc($result)){
        $requests[] = $row;
    }
    mysqli_free_result($result);
} else {
    error_log("Error fetching requests: " . mysqli_error($conn));
}

// Get all program heads
$program_heads_sql = "SELECT u.id, u.full_name, u.department_id, d.name as department_name 
                      FROM users u 
                      JOIN departments d ON u.department_id = d.id 
                      WHERE u.role = 'program head'";
$program_heads = [];
if($result = mysqli_query($conn, $program_heads_sql)){
    while($row = mysqli_fetch_assoc($result)){
        $program_heads[] = $row;
    }
    mysqli_free_result($result);
}

// Get all ADAA
$adaa_sql = "SELECT id, full_name FROM users WHERE role = 'adaa'";
$adaas = [];
if($result = mysqli_query($conn, $adaa_sql)){
    while($row = mysqli_fetch_assoc($result)){
        $adaas[] = $row;
    }
    mysqli_free_result($result);
}

// Get all departments
$dept_sql = "SELECT * FROM departments";
$departments = [];
if($result = mysqli_query($conn, $dept_sql)){
    while($row = mysqli_fetch_assoc($result)){
        $departments[] = $row;
    }
    mysqli_free_result($result);
}

// Set page title for header
$page_title = "Task Request Diagnostic";

// Include header and sidebar
include 'includes/components/header.php';
include 'includes/components/sidebar.php';
?>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <!-- Main Content -->
    <div id="content">
        <?php include 'includes/components/navbar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <h1 class="h3 mb-4 text-gray-800">Task Request Diagnostic</h1>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">System Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card border-left-primary shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        Task Requests</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($requests); ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-left-success shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                        Program Heads</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($program_heads); ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-left-info shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                        ADAAs</div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($adaas); ?></div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
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

            <!-- Program Heads Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Program Heads</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="programHeadsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($program_heads as $head): ?>
                                <tr>
                                    <td><?php echo $head['id']; ?></td>
                                    <td><?php echo htmlspecialchars($head['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($head['department_name'] ?? 'None'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ADAA Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">ADAAs</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="adaaTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($adaas as $adaa): ?>
                                <tr>
                                    <td><?php echo $adaa['id']; ?></td>
                                    <td><?php echo htmlspecialchars($adaa['full_name']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Requests Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Task Requests</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="requestsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Requester</th>
                                    <th>Title</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Program Head</th>
                                    <th>ADAA</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($requests as $request): ?>
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
                                    <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['title']); ?></td>
                                    <td><?php echo htmlspecialchars($request['department_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $request['status'] == 'approved' ? 'success' : 
                                                ($request['status'] == 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $request['program_head_approval'] == 'approved' ? 'success' : 
                                                ($request['program_head_approval'] == 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($request['program_head_approval']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $request['adaa_approval'] == 'approved' ? 'success' : 
                                                ($request['adaa_approval'] == 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($request['adaa_approval']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
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

<?php include 'includes/components/footer_scripts.php'; ?>

<script>
$(document).ready(function() {
    $('#requestsTable').DataTable();
    $('#programHeadsTable').DataTable();
    $('#adaaTable').DataTable();
});
</script>

</body>
</html> 