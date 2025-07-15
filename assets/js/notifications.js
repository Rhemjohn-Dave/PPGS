/**
 * Notification Manager
 * Handles real-time notification updates and UI interactions
 */
const NotificationManager = {
    /**
     * Initialize notification components
     */
    init: function() {
        console.log('Initializing Notification Manager...');
        
        // Set up event handlers
        this.setupEventHandlers();
        
        // Start polling for updates if user is logged in
        this.startPolling();
        
        console.log('Notification Manager initialized successfully');
    },
    
    /**
     * Set up event handlers for notification interactions
     */
    setupEventHandlers: function() {
        console.log('Setting up notification event handlers');
        
        // Handle notification clicks
        $(document).on('click', '.notification-item', function(e) {
            const notificationId = $(this).data('notification-id');
            const notificationLink = $(this).attr('href');
            
            // Don't proceed if there's no notification ID
            if (!notificationId) {
                console.warn('No notification ID found for clicked item');
                return;
            }
            
            // Prevent default behavior to handle the click ourselves
            e.preventDefault();
            
            console.log('Processing notification click:', { notificationId, notificationLink });
            
            // Mark as read via AJAX
            $.ajax({
                url: 'ajax/mark_notification_read.php',
                type: 'POST',
                data: { notification_id: notificationId },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        console.log('Mark notification response:', data);
                        
                        if (data.success) {
                            // Update notification counter
                            NotificationManager.updateCounter();
                            
                            // Update UI for this notification
                            const notificationItem = $(`[data-notification-id="${notificationId}"]`);
                            notificationItem.addClass('bg-light');
                            notificationItem.find('span, h5').removeClass('font-weight-bold');
                            notificationItem.find('.icon-circle').removeClass('bg-primary').addClass('bg-secondary');
                            notificationItem.find('.icon-circle i').removeClass('fa-bell').addClass('fa-check');
                            
                            // Navigate to the notification link if it exists and is valid
                            if (notificationLink && notificationLink !== '#') {
                                console.log('Redirecting to:', notificationLink);
                                window.location.href = notificationLink;
                            } else {
                                console.log('No valid link to redirect to');
                            }
                        } else {
                            console.error('Failed to mark notification as read:', data.message);
                            toastr.error(data.message || 'Failed to mark notification as read');
                        }
                    } catch (e) {
                        console.error('Error parsing JSON response:', e);
                        toastr.error('Error processing notification');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error marking notification as read:', error);
                    toastr.error('Server error while processing notification');
                    
                    // Still try to navigate if there's a valid link
                    if (notificationLink && notificationLink !== '#') {
                        window.location.href = notificationLink;
                    }
                }
            });
        });
        
        // Mark all notifications as read
        $(document).on('click', '#mark-all-read-btn, #mark-all-read', function(e) {
            e.preventDefault();
            console.log('Marking all notifications as read');
            
            $.ajax({
                url: 'ajax/mark_all_notifications_read.php',
                type: 'POST',
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        console.log('Mark all notifications response:', data);
                        
                        if (data.success) {
                            // Update UI
                            $('.notification-item').addClass('bg-light');
                            $('.notification-item span, .notification-item h5').removeClass('font-weight-bold');
                            $('.notification-item .icon-circle').removeClass('bg-primary').addClass('bg-secondary');
                            $('.notification-item .icon-circle i').removeClass('fa-bell').addClass('fa-check');
                            $('#alertsDropdown .badge-counter').remove();
                            
                            // Show success message
                            toastr.success('All notifications marked as read');
                            
                            // Update notification counter
                            NotificationManager.updateCounter();
                        } else {
                            console.error('Failed to mark all notifications as read:', data.message);
                            toastr.error(data.message || 'Failed to mark all notifications as read');
                        }
                    } catch (e) {
                        console.error('Error parsing JSON response:', e);
                        toastr.error('Error processing request');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error marking all notifications as read:', error);
                    toastr.error('Server error while marking all notifications as read');
                }
            });
        });
    },
    
    /**
     * Start polling for notification updates
     */
    startPolling: function() {
        console.log('Starting notification polling');
        
        // Initial check
        this.checkForNewNotifications();
        
        // Set up interval to check for new notifications (every 30 seconds)
        setInterval(() => {
            console.log('Polling for new notifications');
            this.checkForNewNotifications();
        }, 30000);
    },
    
    /**
     * Check for new notifications via the API
     */
    checkForNewNotifications: function() {
        console.log('Checking for new notifications via API');
        $.ajax({
            url: 'api/get_notifications.php',
            type: 'GET',
            data: { limit: 5 },
            success: function(response) {
                try {
                    console.log('Notification check response:', response);
                    if (response.success) {
                        // Update notification count
                        NotificationManager.updateCounterWithData(response.count);
                        console.log('Updated notification count to:', response.count);
                        
                        // Update notification list if needed (for more advanced implementations)
                        // NotificationManager.updateNotificationList(response.notifications);
                    } else {
                        console.warn('Notification check returned unsuccessful response:', response.message);
                    }
                } catch (e) {
                    console.error('Error processing notification response', e, response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error checking for notifications:', error, xhr.responseText);
            }
        });
    },
    
    /**
     * Update notification counter in navbar
     */
    updateCounter: function() {
        $.ajax({
            url: 'ajax/get_notification_count.php',
            type: 'GET',
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        const badgeContainer = $('#alertsDropdown .badge-counter');
                        
                        if (data.count > 0) {
                            if (badgeContainer.length) {
                                badgeContainer.text(data.count);
                            } else {
                                $('#alertsDropdown').append('<span class="badge badge-danger badge-counter">' + data.count + '</span>');
                            }
                        } else {
                            badgeContainer.remove();
                        }
                    }
                } catch (e) {
                    console.error('Error updating notification counter:', e);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching notification count:', error);
            }
        });
    },
    
    /**
     * Update notification counter with provided count
     * @param {number} count - Number of unread notifications
     */
    updateCounterWithData: function(count) {
        console.log('Updating notification counter with count:', count);
        const badge = $('#alertsDropdown .badge-counter');
        if (count > 0) {
            const badgeText = count > 99 ? '99+' : count;
            if (badge.length > 0) {
                badge.text(badgeText);
                console.log('Updated existing badge text to:', badgeText);
            } else {
                $('#alertsDropdown').append(`<span class="badge badge-danger badge-counter">${badgeText}</span>`);
                console.log('Added new badge with text:', badgeText);
            }
        } else {
            badge.remove();
            console.log('Removed notification badge (count is zero)');
        }
    },
    
    /**
     * Update the notification dropdown list with new notifications
     * @param {Array} notifications - Array of notification objects
     */
    updateNotificationList: function(notifications) {
        // This is a more advanced feature that would replace the notifications
        // in the dropdown with new ones without page reload
        
        // Implementation would depend on the exact HTML structure of notifications
        // and would require more comprehensive code to maintain state
        console.log('Notification list update requested with', notifications.length, 'notifications');
    }
};

// Initialize notification manager on document ready
$(document).ready(function() {
    console.log('Document ready, initializing NotificationManager');
    NotificationManager.init();
}); 