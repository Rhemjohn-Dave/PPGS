<?php
session_start();
require_once "config/database.php";
require_once 'controllers/NotificationController.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Get user details - support both id formats for backward compatibility
$user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : $_SESSION["id"];

// Initialize notification controller
$notificationController = new NotificationController($conn);

// Get notifications with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // 20 notifications per page
$read_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get the paginated notifications using the controller
$notificationData = $notificationController->getPaginatedNotifications($user_id, $page, $limit, $read_filter);

// Extract data from the result
$notifications = $notificationData['notifications'];
$total_notifications = $notificationData['total'];
$total_pages = $notificationData['pages'];

// Set page title for header
$page_title = "All Notifications";

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
                <h1 class="h3 mb-0 text-gray-800">Notifications</h1>
                <?php if (!empty($notifications)): ?>
                <button type="button" class="btn btn-sm btn-primary" id="mark-all-read">
                    <i class="fas fa-check-double fa-sm text-white-50"></i> Mark All as Read
                </button>
                <?php endif; ?>
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

            <!-- Notification Filters -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Notification Filters</h6>
                </div>
                <div class="card-body">
                    <div class="btn-group mb-3">
                        <a href="notifications.php?filter=all" class="btn btn-<?php echo $read_filter === 'all' ? 'primary' : 'outline-primary'; ?>">
                            All Notifications
                        </a>
                        <a href="notifications.php?filter=unread" class="btn btn-<?php echo $read_filter === 'unread' ? 'primary' : 'outline-primary'; ?>">
                            Unread
                        </a>
                        <a href="notifications.php?filter=read" class="btn btn-<?php echo $read_filter === 'read' ? 'primary' : 'outline-primary'; ?>">
                            Read
                        </a>
                    </div>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Notifications</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-bell-slash fa-4x text-gray-300 mb-3"></i>
                            <p class="lead">No notifications found</p>
                            <p class="text-muted">You don't have any notifications<?php echo $read_filter !== 'all' ? ' matching the selected filter' : ''; ?>.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group notification-list">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item notification-item <?php echo $notification['is_read'] ? 'bg-light' : ''; ?>" 
                                     data-notification-id="<?php echo $notification['id']; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1 <?php echo $notification['is_read'] ? '' : 'font-weight-bold'; ?>">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </h5>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="mt-2">
                                        <?php if (!empty($notification['link'])): ?>
                                            <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="btn btn-sm btn-primary view-notification" 
                                               data-notification-id="<?php echo $notification['id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!$notification['is_read']): ?>
                                            <button type="button" class="btn btn-sm btn-secondary mark-read-btn" 
                                                    data-notification-id="<?php echo $notification['id']; ?>">
                                                <i class="fas fa-check"></i> Mark as Read
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-center mt-4">
                                <nav aria-label="Notification pagination">
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="notifications.php?page=<?php echo $page - 1; ?>&filter=<?php echo $read_filter; ?>">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="notifications.php?page=<?php echo $i; ?>&filter=<?php echo $read_filter; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="notifications.php?page=<?php echo $page + 1; ?>&filter=<?php echo $read_filter; ?>">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
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
    // Mark individual notification as read
    $('.mark-read-btn').on('click', function() {
        const notificationId = $(this).data('notification-id');
        const button = $(this);
        const notificationItem = button.closest('.notification-item');
        
        $.ajax({
            url: 'ajax/mark_notification_read.php',
            type: 'POST',
            data: { notification_id: notificationId },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Update UI
                        notificationItem.addClass('bg-light');
                        notificationItem.find('h5').removeClass('font-weight-bold');
                        button.remove();
                        
                        // Show success message
                        toastr.success('Notification marked as read');
                    } else {
                        toastr.error(data.message || 'Failed to mark notification as read');
                    }
                } catch (e) {
                    console.error('Error parsing JSON response', e);
                    toastr.error('Failed to process response');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                toastr.error('Server error: ' + error);
            }
        });
    });
    
    // Mark all notifications as read
    $('#mark-all-read').on('click', function() {
        $.ajax({
            url: 'ajax/mark_all_notifications_read.php',
            type: 'POST',
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Update UI
                        $('.notification-item').addClass('bg-light');
                        $('.notification-item h5').removeClass('font-weight-bold');
                        $('.mark-read-btn').remove();
                        
                        // Show success message
                        toastr.success('All notifications marked as read');
                    } else {
                        toastr.error(data.message || 'Failed to mark all notifications as read');
                    }
                } catch (e) {
                    console.error('Error parsing JSON response', e);
                    toastr.error('Failed to process response');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                toastr.error('Server error: ' + error);
            }
        });
    });
    
    // Handle notification link clicks
    $('.view-notification').on('click', function(e) {
        const notificationId = $(this).data('notification-id');
        
        // Mark as read via AJAX
        $.ajax({
            url: 'ajax/mark_notification_read.php',
            type: 'POST',
            data: { notification_id: notificationId }
        });
        
        // Continue with the default action (following the link)
    });
});
</script> 