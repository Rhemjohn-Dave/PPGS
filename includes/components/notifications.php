<?php
/**
 * Renders the notification dropdown for the navbar
 * @param array $notifications Array of notifications to display
 * @param int $unreadCount Count of unread notifications
 * @return void
 */
function renderNotificationDropdown($notifications, $unreadCount) {
    ?>
    <li class="nav-item dropdown no-arrow mx-1">
        <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-bell fa-fw"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="badge badge-danger badge-counter"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
            <?php endif; ?>
        </a>
        <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
            aria-labelledby="alertsDropdown">
            <h6 class="dropdown-header">
                Notifications Center
            </h6>
            
            <?php if (empty($notifications)): ?>
                <div class="dropdown-item d-flex align-items-center">
                    <div class="mr-3">
                        <div class="icon-circle bg-light">
                            <i class="fas fa-bell-slash text-muted"></i>
                        </div>
                    </div>
                    <div>
                        <span class="font-weight-bold">No notifications</span>
                        <div class="small text-gray-500">You don't have any notifications yet</div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <a class="dropdown-item d-flex align-items-center notification-item <?= $notification['is_read'] ? 'bg-light' : '' ?>" 
                       href="<?= !empty($notification['link']) ? htmlspecialchars($notification['link']) : '#' ?>"
                       data-notification-id="<?= (int)$notification['id'] ?>">
                        <div class="mr-3">
                            <div class="icon-circle <?= $notification['is_read'] ? 'bg-light' : 'bg-primary' ?>">
                                <i class="fas <?= $notification['is_read'] ? 'fa-check text-gray-500' : 'fa-file-alt text-white' ?>"></i>
                            </div>
                        </div>
                        <div>
                            <div class="small text-gray-500"><?= date('M d, Y', strtotime($notification['created_at'])) ?></div>
                            <span class="<?= $notification['is_read'] ? '' : 'font-weight-bold' ?>"><?= htmlspecialchars($notification['message']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($notifications)): ?>
                <a class="dropdown-item text-center small text-gray-500" href="#" id="mark-all-read">Mark All as Read</a>
                <a class="dropdown-item text-center small text-gray-500" href="notifications.php">View All Notifications</a>
            <?php endif; ?>
        </div>
    </li>
    <?php
}
?>

<script>
$(document).ready(function() {
    // Mark individual notification as read when clicked
    $(document).on('click', '.notification-item', function(e) {
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
                        // Update UI if needed
                        updateNotificationCounter();
                    }
                } catch (e) {
                    console.error('Error parsing JSON response', e);
                }
            }
        });
    });
    
    // Mark all notifications as read
    $('#mark-all-read').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'ajax/mark_all_notifications_read.php',
            type: 'POST',
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Update UI
                        $('.notification-item').removeClass('font-weight-bold').addClass('bg-light');
                        $('.notification-item .icon-circle').removeClass('bg-primary').addClass('bg-light');
                        $('.notification-item .icon-circle i').removeClass('fa-file-alt text-white').addClass('fa-check text-gray-500');
                        $('.badge-counter').remove();
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
                    const count = data.count || 0;
                    
                    // Update the badge
                    const badge = $('#alertsDropdown .badge-counter');
                    if (count > 0) {
                        if (badge.length > 0) {
                            badge.text(count > 9 ? '9+' : count);
                        } else {
                            $('#alertsDropdown').append(`<span class="badge badge-danger badge-counter">${count > 9 ? '9+' : count}</span>`);
                        }
                    } else {
                        badge.remove();
                    }
                } catch (e) {
                    console.error('Error parsing JSON response', e);
                }
            }
        });
    }
});
</script> 