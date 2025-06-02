<?php
session_start();
require_once "config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: login.php");
    exit;
}

// Get all staff members
$staff_sql = "SELECT id, username FROM users WHERE role = 'staff' ORDER BY username";
$staff_result = mysqli_query($conn, $staff_sql);

// Get task requests
$sql = "SELECT tr.*, u.username as requester_name, 
        t.assigned_to as staff_id, s.username as staff_name
        FROM task_requests tr
        LEFT JOIN users u ON tr.requester_id = u.id
        LEFT JOIN tasks t ON t.request_id = tr.id
        LEFT JOIN users s ON t.assigned_to = s.id
        ORDER BY tr.created_at DESC";

if($stmt = mysqli_prepare($conn, $sql)) {
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
    }
    mysqli_stmt_close($stmt);
}

// Set page title for header
$page_title = "Manage Requests";

// Include header
include 'includes/components/header.php';
// Include sidebar
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
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Manage Task Requests</h1>
            </div>

            <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>

            <!-- Content Row -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Task</th>
                                            <th>Requester</th>
                                            <th>Status</th>
                                            <th>Assigned To</th>
                                            <th>Requested At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if(isset($result) && mysqli_num_rows($result) > 0) {
                                            while($row = mysqli_fetch_assoc($result)) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($row["title"]) . "</td>";
                                                echo "<td>" . htmlspecialchars($row["requester_name"]) . "</td>";
                                                echo "<td>";
                                                $status_class = "";
                                                switch($row["status"]) {
                                                    case "pending":
                                                        $status_class = "badge-warning";
                                                        break;
                                                    case "approved":
                                                        $status_class = "badge-success";
                                                        break;
                                                    case "rejected":
                                                        $status_class = "badge-danger";
                                                        break;
                                                    default:
                                                        $status_class = "badge-secondary";
                                                }
                                                echo "<span class='badge " . $status_class . "'>" . htmlspecialchars($row["status"]) . "</span>";
                                                echo "</td>";
                                                echo "<td>";
                                                if($row["staff_id"]) {
                                                    echo htmlspecialchars($row["staff_name"] ?? "Unknown Staff");
                                                } else {
                                                    echo "Not Assigned";
                                                }
                                                echo "</td>";
                                                echo "<td>" . date('M d, Y', strtotime($row["created_at"])) . "</td>";
                                                echo "<td>";
                                                if($row["status"] == "pending") {
                                                    echo "<button type='button' class='btn btn-success btn-sm' data-toggle='modal' data-target='#assignStaffModal" . $row["id"] . "'>Approve</button> ";
                                                    echo "<a href='reject_request.php?id=" . $row["id"] . "' class='btn btn-danger btn-sm'>Reject</a> ";
                                                }
                                                echo "<a href='view_request.php?id=" . $row["id"] . "' class='btn btn-info btn-sm'>View</a>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center'>No task requests found.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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

<!-- Staff Assignment Modals -->
<?php
// Reset the result pointer
mysqli_data_seek($result, 0);
while($row = mysqli_fetch_assoc($result)) {
    if($row["status"] == "pending") {
?>
<!-- Assign Staff Modal for Request <?php echo $row["id"]; ?> -->
<div class="modal fade" id="assignStaffModal<?php echo $row["id"]; ?>" tabindex="-1" role="dialog" aria-labelledby="assignStaffModalLabel<?php echo $row["id"]; ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignStaffModalLabel<?php echo $row["id"]; ?>">Assign Staff Member</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="approve_request.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="request_id" value="<?php echo $row["id"]; ?>">
                    <div class="form-group">
                        <label for="staff_id<?php echo $row["id"]; ?>">Select Staff Member</label>
                        <select class="form-control" id="staff_id<?php echo $row["id"]; ?>" name="staff_id" required>
                            <option value="">Choose a staff member...</option>
                            <?php
                            // Reset staff result pointer
                            mysqli_data_seek($staff_result, 0);
                            while($staff = mysqli_fetch_assoc($staff_result)) {
                                echo "<option value='" . $staff['id'] . "'>" . htmlspecialchars($staff['username']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve & Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
    }
}
?>

<?php include 'includes/components/footer_scripts.php'; ?> 