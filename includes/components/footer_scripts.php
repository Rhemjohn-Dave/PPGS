    <!-- End of Content Wrapper -->
</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- jQuery first -->
<script src="vendor/jquery/jquery.min.js"></script>

<!-- Bootstrap core JavaScript-->
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- jQuery UI -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- DataTables JavaScript -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="js/sb-admin-2.min.js"></script>
<script src="js/custom.js"></script>

<!-- Toastr.js for notifications -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- Initialize jQuery and Bootstrap -->
<script>
$(document).ready(function() {
    // Initialize all tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize all popovers
    $('[data-toggle="popover"]').popover();
    
    // Initialize Toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 5000
    };
    
    // Initialize DataTable if the element exists
    if ($.fn.DataTable.isDataTable('#dataTable')) {
        $('#dataTable').DataTable().destroy();
    }
    
    if ($('#dataTable').length) {
        $('#dataTable').DataTable({
            "order": [[ 5, "asc" ]], // Sort by due date
            "pageLength": 10,
            "responsive": true,
            "language": {
                "emptyTable": "No tasks found",
                "zeroRecords": "No matching tasks found"
            }
        });
    }
    
    // Handle modal triggers
    $('[data-toggle="modal"]').on('click', function(e) {
        e.preventDefault();
        var targetModal = $($(this).data('target'));
        if (targetModal.length) {
            targetModal.modal('show');
        }
    });
});
</script>

<!-- Custom Script for Notification Click Handling -->
<script>
$(document).ready(function() {
    // Handle notification clicks
    $('.notification-item').on('click', function(e) {
        var notificationId = $(this).data('notification-id');
        var notificationLink = $(this).attr('href');
        
        // Don't mark as read if there's no ID
        if (!notificationId) return;
        
        // Stop the default behavior
        e.preventDefault();
        
        // Mark notification as read via AJAX
        $.ajax({
            url: 'mark_notification_read.php',
            type: 'POST',
            data: {
                notification_id: notificationId
            },
            success: function(response) {
                console.log('Notification marked as read', response);
                
                // Navigate to the notification link
                if (notificationLink && notificationLink !== '#') {
                    window.location.href = notificationLink;
                }
                
                // Update unread count
                updateNotificationCount();
            },
            error: function(xhr, status, error) {
                console.error('Error marking notification as read:', error);
                
                // Still navigate to link even if there was an error
                if (notificationLink && notificationLink !== '#') {
                    window.location.href = notificationLink;
                }
            }
        });
    });
    
    // Function to update notification count
    function updateNotificationCount() {
        $.ajax({
            url: 'get_notification_count.php',
            type: 'GET',
            success: function(response) {
                try {
                    var data = JSON.parse(response);
                    var badgeContainer = $('#alertsDropdown .badge-counter');
                    
                    if (data.count > 0) {
                        if (badgeContainer.length) {
                            badgeContainer.text(data.count);
                        } else {
                            $('#alertsDropdown').append('<span class="badge badge-danger badge-counter">' + data.count + '</span>');
                        }
                    } else {
                        badgeContainer.remove();
                    }
                } catch (e) {
                    console.error('Error parsing notification count:', e);
                }
            }
        });
    }
});
</script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Initialize SweetAlert2 -->
<script>
    // Function to show success message
    function showSuccess(message) {
        Swal.fire({
            title: 'Success!',
            text: message,
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: '#1cc88a'
        });
    }
    
    // Function to show error message
    function showError(message) {
        Swal.fire({
            title: 'Error!',
            text: message,
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#e74a3b'
        });
    }
    
    // Check for session messages
    <?php if(isset($_SESSION['success_message'])): ?>
        showSuccess('<?php echo $_SESSION['success_message']; ?>');
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error_message'])): ?>
        showError('<?php echo $_SESSION['error_message']; ?>');
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
</script>

<!-- Notifications JS -->
<script src="assets/js/notifications.js"></script>

</body>
</html> 