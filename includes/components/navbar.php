<!-- Topbar -->
<?php 
// Fetch Notifications - Moved here to ensure variables are defined before use
$notification_count = 0;
$notifications = [];

// Ensure database connection is available
if (!isset($conn)) {
    require_once __DIR__ . '/../../config/database.php';
}

// Include notification controller if not already included
if (!class_exists('NotificationController')) {
    require_once __DIR__ . '/../../controllers/NotificationController.php';
}

// Initialize notification controller
$notificationController = new NotificationController($conn);

// Check if user is logged in
if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
    
    // Get unread count
    $notification_count = $notificationController->getUnreadNotificationCount($current_user_id);
    
    // Get recent notifications (5 most recent)
    $notifications = $notificationController->getUserNotifications($current_user_id, 5);
} elseif (isset($_SESSION['id']) && is_numeric($_SESSION['id'])) {
    // For backwards compatibility
    $current_user_id = $_SESSION['id'];
    
    // Get unread count
    $notification_count = $notificationController->getUnreadNotificationCount($current_user_id);
    
    // Get recent notifications (5 most recent)
    $notifications = $notificationController->getUserNotifications($current_user_id, 5);
}

// Fetch user's full name
$full_name = '';
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $sql = "SELECT full_name FROM users WHERE username = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $full_name = $row['full_name'];
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Logo and Brand/Title -->
    <a class="navbar-brand d-none d-lg-flex align-items-center" href="index.php" style="text-decoration: none;">
        <img src="assets/images/tuplogo.png" alt="TUP Visayas Logo" 
             style="max-width: 40px; height: auto; margin-right: 10px;">
        <h5 class="text-gray-800 my-auto">TUP Visayas PPGS</h5> 
    </a>

    <!-- Mobile Brand/Title -->
    <a class="navbar-brand d-flex d-lg-none align-items-center mr-auto" href="index.php" style="text-decoration: none;">
        <img src="assets/images/tuplogo.png" alt="TUP Visayas Logo" 
             style="max-width: 40px; height: auto; margin-right: 10px;">
        <span class="text-gray-800 my-auto">TUP-V PPGS</span>
    </a>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">

        <!-- Nav Item - Alerts (Notifications) -->
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-bell fa-fw"></i>
                <!-- Counter - Alerts -->
                <?php if ($notification_count > 0): ?>
                <span class="badge badge-danger badge-counter"><?php echo $notification_count > 99 ? '99+' : $notification_count; ?></span>
                <?php endif; ?>
            </a>
            <!-- Dropdown - Alerts -->
            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="alertsDropdown">
                <h6 class="dropdown-header">
                    Notifications Center
                </h6>
                <?php if (empty($notifications)): ?>
                    <div class="dropdown-item text-center">No notifications</div>
                <?php else: ?>
                    <?php foreach($notifications as $notification): ?>
                        <a class="dropdown-item d-flex align-items-center notification-item <?php echo $notification['is_read'] ? 'bg-light' : ''; ?>" 
                           href="<?php echo !empty($notification['link']) ? htmlspecialchars($notification['link']) : '#'; ?>" 
                           data-notification-id="<?php echo $notification['id']; ?>">
                            <div class="mr-3">
                                <div class="icon-circle <?php echo $notification['is_read'] ? 'bg-secondary' : 'bg-primary'; ?>">
                                    <i class="fas <?php echo $notification['is_read'] ? 'fa-check' : 'fa-bell'; ?> text-white"></i>
                                </div>
                            </div>
                            <div>
                                <div class="small text-gray-500"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></div>
                                <span class="<?php echo $notification['is_read'] ? '' : 'font-weight-bold'; ?>"><?php echo htmlspecialchars($notification['message']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($notifications)): ?>
                    <a class="dropdown-item text-center small text-gray-500" href="#" id="mark-all-read-btn">
                        <i class="fas fa-check-double mr-1"></i> Mark All as Read
                    </a>
                <?php endif; ?>
                <a class="dropdown-item text-center small text-gray-500" href="notifications.php">
                    <i class="fas fa-list mr-1"></i> View All Notifications
                </a>
            </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= htmlspecialchars($full_name); ?></span>
                <img class="img-profile rounded-circle" src="assets/images/undraw_profile.png">
            </a>
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="userDropdown">
                <a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profile
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="logout.php">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>
    </ul>
</nav>
<!-- End of Topbar -->

<script>
$(document).ready(function() {
    // Mark notification as read when clicked
    $('.notification-item').on('click', function(e) {
        const notificationId = $(this).data('notification-id');
        if (!notificationId) return;
        
        // Mark as read via AJAX
        $.ajax({
            url: 'ajax/mark_notification_read.php',
            type: 'POST',
            data: { notification_id: notificationId },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Update notification counter in navbar
                        updateNotificationCounter();
                    }
                } catch (e) {
                    console.error('Error parsing JSON response', e);
                }
            }
        });
    });
    
    // Mark all notifications as read
    $('#mark-all-read-btn').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'ajax/mark_all_notifications_read.php',
            type: 'POST',
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Update UI
                        $('.notification-item').removeClass('bg-light');
                        $('.notification-item span').removeClass('font-weight-bold');
                        $('.notification-item .icon-circle').removeClass('bg-primary').addClass('bg-secondary');
                        $('.notification-item .icon-circle i').removeClass('fa-bell').addClass('fa-check');
                        $('#alertsDropdown .badge-counter').remove();
                        
                        // Show success message if toastr is available
                        if (typeof toastr !== 'undefined') {
                            toastr.success('All notifications marked as read');
                        }
                    }
                } catch (e) {
                    console.error('Error parsing JSON response', e);
                }
            }
        });
    });
    
    // Function to update notification counter
    function updateNotificationCounter() {
        $.ajax({
            url: 'ajax/get_notification_count.php',
            type: 'GET',
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        const count = data.count || 0;
                        
                        // Update the badge
                        const badge = $('#alertsDropdown .badge-counter');
                        if (count > 0) {
                            const badgeText = count > 99 ? '99+' : count;
                            if (badge.length > 0) {
                                badge.text(badgeText);
                            } else {
                                $('#alertsDropdown').append(`<span class="badge badge-danger badge-counter">${badgeText}</span>`);
                            }
                        } else {
                            badge.remove();
                        }
                    }
                } catch (e) {
                    console.error('Error parsing JSON response', e);
                }
            }
        });
    }
    
    // Refresh notification count periodically (every 30 seconds)
    setInterval(updateNotificationCounter, 30000);
});
</script> 