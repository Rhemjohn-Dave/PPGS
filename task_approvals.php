<?php
session_start();
require_once "config/database.php";
require_once "controllers/NotificationController.php";
require_once "includes/helpers/notification_helper.php";

// Check if user is logged in and has the correct role (program head or adaa)
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION["role"], ['program head', 'adaa'])) {
    // Redirect to index or show an error message if not authorized
    header("location: index.php");
    exit;
}

$current_user_id = $_SESSION['id'];
$current_user_role = $_SESSION['role'];

// Initialize notification controller
$notificationController = new NotificationController($conn);

// Include debugging information at the top of the file
error_log("Task Approvals Page - User ID: " . $current_user_id . ", Role: " . $current_user_role);

// Get user department info for program heads
$user_department_id = null;
if ($current_user_role == 'program head') {
    $dept_sql = "SELECT department_id FROM users WHERE id = ?";
    if ($dept_stmt = mysqli_prepare($conn, $dept_sql)) {
        mysqli_stmt_bind_param($dept_stmt, "i", $current_user_id);
        if (mysqli_stmt_execute($dept_stmt)) {
            $dept_result = mysqli_stmt_get_result($dept_stmt);
            if ($dept_row = mysqli_fetch_assoc($dept_result)) {
                $user_department_id = $dept_row['department_id'];
                error_log("Program Head Department ID: " . $user_department_id);
            }
        }
        mysqli_stmt_close($dept_stmt);
    }
}

// Handle Approval/Rejection Form Submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve_request']) || isset($_POST['reject_request'])) {
        $request_id = $_POST['request_id'];
        $action = isset($_POST['approve_request']) ? 'approved' : 'rejected';

        // Get request details for notifications
        $request_sql = "SELECT tr.*, 
                       u.username as requester_username,
                       u.full_name as requester_full_name,
                       u.id as requester_id, 
                       d.name as department_name 
                       FROM task_requests tr 
                       JOIN users u ON tr.requester_id = u.id 
                       JOIN departments d ON u.department_id = d.id 
                       WHERE tr.id = ?";
        $request_details = null;
        if ($stmt = mysqli_prepare($conn, $request_sql)) {
            mysqli_stmt_bind_param($stmt, "i", $request_id);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $request_details = mysqli_fetch_assoc($result);
            }
            mysqli_stmt_close($stmt);
        }

        if ($request_details) {
            $update_sql = "";
            if ($current_user_role == 'program head') {
                $update_sql = "UPDATE task_requests SET program_head_approval = ? WHERE id = ?";
            } elseif ($current_user_role == 'adaa') {
                // ADAA can only approve if Program Head has already approved
                $update_sql = "UPDATE task_requests SET adaa_approval = ? WHERE id = ? AND program_head_approval = 'approved'";
            }

            if (!empty($update_sql)) {
                if ($stmt = mysqli_prepare($conn, $update_sql)) {
                    mysqli_stmt_bind_param($stmt, "si", $action, $request_id);
                    if (mysqli_stmt_execute($stmt)) {
                        // Send notification to requester
                        $requester_message = "Your task request has been " . strtolower($action) . " by " . $_SESSION['username'];
                        $requester_link = "task_requests.php";
                        $notificationController->sendNotification([$request_details['requester_id']], $requester_message, $requester_link);

                        // Get requester name from request details with fallback options
                        $requesterName = 'Unknown';
                        if (!empty($request_details['requester_full_name'])) {
                            $requesterName = $request_details['requester_full_name'];
                        } elseif (!empty($request_details['requester_username'])) {
                            $requesterName = $request_details['requester_username'];
                        } elseif (!empty($request_details['requester_name'])) {
                            $requesterName = $request_details['requester_name'];
                        }

                        // If program head approved, notify ADAAs
                        if ($action == 'approved' && $current_user_role == 'program head') {
                            // Get all users with ADAA role
                            $adaa_sql = "SELECT id FROM users WHERE role = 'adaa'";
                            $adaa_ids = [];
                            if ($adaa_stmt = mysqli_prepare($conn, $adaa_sql)) {
                                mysqli_stmt_execute($adaa_stmt);
                                $adaa_result = mysqli_stmt_get_result($adaa_stmt);
                                while ($row = mysqli_fetch_assoc($adaa_result)) {
                                    $adaa_ids[] = $row['id'];
                                }
                                mysqli_stmt_close($adaa_stmt);
                            }

                            if (!empty($adaa_ids)) {
                                $adaa_message = "Task request from " . $requesterName . " has been " . strtolower($action) . " by " . $_SESSION['username'];
                                $adaa_link = "task_approvals.php";
                                $notificationController->sendNotification($adaa_ids, $adaa_message, $adaa_link);
                            }
                        }
                        // If ADAA approved, notify admins
                        elseif ($action == 'approved' && $current_user_role == 'adaa') {
                            // Get all users with admin role
                            $admin_sql = "SELECT id FROM users WHERE role = 'admin'";
                            $admin_ids = [];
                            if ($admin_stmt = mysqli_prepare($conn, $admin_sql)) {
                                mysqli_stmt_execute($admin_stmt);
                                $admin_result = mysqli_stmt_get_result($admin_stmt);
                                while ($row = mysqli_fetch_assoc($admin_result)) {
                                    $admin_ids[] = $row['id'];
                                }
                                mysqli_stmt_close($admin_stmt);
                            }

                            if (!empty($admin_ids)) {
                                $admin_message = "Task request from " . $requesterName . " has been " . strtolower($action) . " and may need resource allocation";
                                $admin_link = "task_approvals.php";
                                $notificationController->sendNotification($admin_ids, $admin_message, $admin_link);
                            }
                        }

                        // Check if both approved (for potential notification later)
                        if ($action == 'approved' && $current_user_role == 'adaa') {
                            // Check final status
                            $check_sql = "SELECT program_head_approval, adaa_approval FROM task_requests WHERE id = ?";
                            if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
                                mysqli_stmt_bind_param($check_stmt, "i", $request_id);
                                mysqli_stmt_execute($check_stmt);
                                $check_result = mysqli_stmt_get_result($check_stmt);
                                $final_status = mysqli_fetch_assoc($check_result);
                                if ($final_status && $final_status['program_head_approval'] == 'approved' && $final_status['adaa_approval'] == 'approved') {
                                    // Update overall status to approved
                                    $final_update_sql = "UPDATE task_requests SET status = 'approved' WHERE id = ?";
                                    if ($final_stmt = mysqli_prepare($conn, $final_update_sql)) {
                                        mysqli_stmt_bind_param($final_stmt, "i", $request_id);
                                        mysqli_stmt_execute($final_stmt);
                                        mysqli_stmt_close($final_stmt);
                                    }
                                }
                                mysqli_stmt_close($check_stmt);
                            }
                        } elseif ($action == 'rejected') {
                            // If either rejects, set overall status to rejected
                            $final_update_sql = "UPDATE task_requests SET status = 'rejected' WHERE id = ?";
                            if ($final_stmt = mysqli_prepare($conn, $final_update_sql)) {
                                mysqli_stmt_bind_param($final_stmt, "i", $request_id);
                                mysqli_stmt_execute($final_stmt);
                                mysqli_stmt_close($final_stmt);
                            }
                        }
                        $_SESSION['success_message'] = "Request " . ucfirst($action) . " successfully.";
                    } else {
                        $_SESSION['error_message'] = "Error updating request status.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $_SESSION['error_message'] = "Database error preparing update statement.";
                }
            } else {
                $_SESSION['error_message'] = "Invalid action or role for approval.";
            }
        } else {
            $_SESSION['error_message'] = "Error retrieving request details.";
        }
        // Redirect to prevent form resubmission
        header("Location: task_approvals.php");
        exit;
    }
}

// Fetch Pending Requests
$requests = [];
$fetch_sql = "";

if ($current_user_role == 'program head' && $user_department_id) {
    // Program head sees requests from their department where their approval is pending
    $fetch_sql = "SELECT tr.*, 
                  u.username as requester_username,
                  u.full_name as requester_full_name,
                  d.name as department_name 
                  FROM task_requests tr 
                  JOIN users u ON tr.requester_id = u.id 
                  JOIN departments d ON tr.department_id = d.id 
                  WHERE tr.department_id = ? 
                  AND tr.program_head_approval = 'pending' 
                  ORDER BY tr.created_at DESC";

    if ($stmt = mysqli_prepare($conn, $fetch_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_department_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $requests[] = $row;
            }
            error_log("Program Head - Found " . count($requests) . " pending requests for department " . $user_department_id);
        } else {
            error_log("Error executing program head query: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing program head query: " . mysqli_error($conn));
    }
} elseif ($current_user_role == 'adaa') {
    // ADAA sees requests where Program Head has approved and ADAA approval is pending
    $fetch_sql = "SELECT tr.*, 
                  u.username as requester_username,
                  u.full_name as requester_full_name,
                  d.name as department_name 
                  FROM task_requests tr 
                  JOIN users u ON tr.requester_id = u.id 
                  JOIN departments d ON tr.department_id = d.id 
                  WHERE tr.program_head_approval = 'approved' 
                  AND tr.adaa_approval = 'pending' 
                  ORDER BY tr.created_at DESC";

    if ($stmt = mysqli_prepare($conn, $fetch_sql)) {
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $requests[] = $row;
            }
            error_log("ADAA - Found " . count($requests) . " pending requests");
        } else {
            error_log("Error executing ADAA query: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing ADAA query: " . mysqli_error($conn));
    }
}

// Add additional diagnostics
error_log("Task Approvals - Final query: " . $fetch_sql);
error_log("Task Approvals - Total requests found: " . count($requests));

// Set page title for header
$page_title = "Task Approvals";

// Include header, sidebar, etc.
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
            <h1 class="h3 mb-4 text-gray-800">Pending Task Approvals</h1>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
            <?php endif; ?>

            <!-- Approvals Table -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <?php if (!empty($requests)): ?>
                            <table class="table table-bordered" id="approvalsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Requester</th>
                                        <th>Title</th>
                                        <th>Reason</th>
                                        <th>Requested At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td>
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
                                                echo htmlspecialchars($requesterName);
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($request['title']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($request['reason'])); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></td>
                                            <td>
                                                <form action="task_approvals.php" method="post" style="display: inline-block;">
                                                    <input type="hidden" name="request_id"
                                                        value="<?php echo $request['id']; ?>">
                                                    <button type="submit" name="approve_request" class="btn btn-success btn-sm"
                                                        title="Approve">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form action="task_approvals.php" method="post" style="display: inline-block;">
                                                    <input type="hidden" name="request_id"
                                                        value="<?php echo $request['id']; ?>">
                                                    <button type="submit" name="reject_request" class="btn btn-danger btn-sm"
                                                        title="Reject">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-info btn-sm view-task-btn"
                                                    data-request-id="<?php echo $request['id']; ?>">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No pending requests found.</p>
                        <?php endif; ?>
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

<!-- Modal for viewing task details -->
<div class="modal fade" id="viewTaskModal" tabindex="-1" role="dialog" aria-labelledby="viewTaskModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTaskModalLabel">Task/Request Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="task-details-content">
                <div class="text-center"><span class="spinner-border"></span> Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/components/footer_scripts.php'; ?>

<script>
    $(document).ready(function () {
        $('#approvalsTable').DataTable();
        $('.view-task-btn').on('click', function () {
            var requestId = $(this).data('request-id');
            $('#task-details-content').html('<div class="text-center"><span class="spinner-border"></span> Loading...</div>');
            $('#viewTaskModal').modal('show');
            // AJAX to fetch request details
            $.ajax({
                url: 'ajax/get_request_data.php',
                method: 'GET',
                data: { id: requestId },
                dataType: 'json',
                success: function (response) {
                    if (response.success && response.request) {
                        var r = response.request;
                        // Helper for colored badges
                        function badge(val) {
                            var map = {
                                'approved': 'success',
                                'pending': 'warning',
                                'rejected': 'danger'
                            };
                            var color = map[val] || 'secondary';
                            return '<span class="badge badge-' + color + ' text-capitalize">' + val + '</span>';
                        }
                        var html = '';
                        html += '<h5 class="mb-3"><i class="fas fa-info-circle"></i> General Information</h5>';
                        html += '<table class="table table-bordered table-striped">';
                        html += '<tr><th style="width:30%">Title</th><td>' + (r.title || '-') + '</td></tr>';
                        html += '<tr><th>Description</th><td>' + (r.description || '-') + '</td></tr>';
                        html += '<tr><th>Reason</th><td>' + (r.reason || '-') + '</td></tr>';
                        html += '<tr><th>Category</th><td><span class="badge badge-info">' + (r.category || '-') + '</span></td></tr>';
                        html += '<tr><th>Requester</th><td>' + (r.requester_full_name || r.requester_username || '-') + '</td></tr>';
                        html += '<tr><th>Department</th><td>' + (r.department_name || '-') + '</td></tr>';
                        html += '<tr><th>Status</th><td>' + badge(r.status) + '</td></tr>';
                        html += '<tr><th>Program Head Approval</th><td>' + badge(r.program_head_approval) + '</td></tr>';
                        html += '<tr><th>ADAA Approval</th><td>' + badge(r.adaa_approval) + '</td></tr>';
                        html += '<tr><th>Requested At</th><td>' + (r.created_at || '-') + '</td></tr>';
                        html += '</table>';
                        $('#task-details-content').html(html);
                    } else {
                        $('#task-details-content').html('<div class="alert alert-danger">Request details not found.</div>');
                    }
                },
                error: function () {
                    $('#task-details-content').html('<div class="alert alert-danger">Error loading request details.</div>');
                }
            });
        });
    });
</script>

</body>

</html>