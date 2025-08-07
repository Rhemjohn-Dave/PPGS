$(document).ready(function() {
    // Toggle Sidebar
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
        $('#content').toggleClass('active');
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize popovers
    $('[data-toggle="popover"]').popover();

    // Close sidebar when clicking outside on mobile
    $(document).on('click', function(e) {
        if ($(window).width() <= 768) {
            if (!$(e.target).closest('#sidebar, #sidebarCollapse').length) {
                $('#sidebar').removeClass('active');
                $('#content').removeClass('active');
            }
        }
    });

    // Handle dropdown menus
    $('.dropdown-toggle').dropdown();

    // Initialize Material Design inputs
    $('.form-control').each(function() {
        if ($(this).val() !== '') {
            $(this).addClass('has-value');
        }
    });

    $('.form-control').on('focus blur', function() {
        $(this).toggleClass('has-value', $(this).val() !== '');
    });

    // Handle alerts
    $('.alert').each(function() {
        if ($(this).hasClass('alert-dismissible')) {
            $(this).find('.close').on('click', function() {
                $(this).closest('.alert').fadeOut();
            });
        }
    });

    // Handle modals
    $('.modal').on('show.bs.modal', function() {
        $('body').addClass('modal-open');
    });

    $('.modal').on('hidden.bs.modal', function() {
        $('body').removeClass('modal-open');
    });

    // Handle tabs
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        $(e.target).addClass('active');
        $(e.relatedTarget).removeClass('active');
    });

    // Handle form validation
    $('form').on('submit', function(e) {
        var $form = $(this);
        if ($form.find('.form-control:invalid').length) {
            e.preventDefault();
            $form.find('.form-control:invalid').first().focus();
        }
    });

    // Handle table row selection
    $('.table tbody tr').on('click', function() {
        $(this).toggleClass('selected');
    });

    // Handle card collapse
    $('.card-header').on('click', function() {
        $(this).find('.zmdi').toggleClass('zmdi-chevron-down zmdi-chevron-up');
    });

    // Initialize datepickers
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });

    // Initialize select2
    $('.select2').select2({
        theme: 'bootstrap'
    });

    // Handle file inputs
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    // Form Input Focus Effects
    $('.fg-line .form-control').focus(function() {
        $(this).closest('.fg-line').addClass('focused');
    });

    $('.fg-line .form-control').blur(function() {
        if ($(this).val() === '') {
            $(this).closest('.fg-line').removeClass('focused');
        }
    });

    // Task Category Form Fields
    $('#category').on('change', function() {
        var category = $(this).val();
        $('.category-fields').hide();
        if (category) {
            $('#' + category + 'Fields').show();
        }
    });

    // Task Status Updates
    $('.task-status').on('change', function() {
        var taskId = $(this).data('task-id');
        var newStatus = $(this).val();
        
        $.ajax({
            url: 'functions/update_task_status.php',
            method: 'POST',
            data: {
                task_id: taskId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    // Update UI accordingly
                    var statusLabel = $('.status-label[data-task-id="' + taskId + '"]');
                    statusLabel.removeClass('label-warning label-success label-danger')
                             .addClass('label-' + getStatusClass(newStatus))
                             .text(newStatus);
                } else {
                    alert('Failed to update task status');
                }
            }
        });
    });

    // Helper function for status classes
    function getStatusClass(status) {
        switch(status.toLowerCase()) {
            case 'pending':
                return 'warning';
            case 'completed':
                return 'success';
            case 'cancelled':
                return 'danger';
            default:
                return 'default';
        }
    }

    // Notifications
    function showNotification(message, type) {
        var notification = $('<div class="alert alert-' + type + ' alert-dismissible fade in" role="alert">' +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span></button>' + message + '</div>');

        $('.container').prepend(notification);
        setTimeout(function() {
            notification.alert('close');
        }, 5000);
    }

    // File Upload Preview
    $('input[type="file"]').on('change', function(e) {
        var fileName = e.target.files[0].name;
        $(this).next('.custom-file-label').html(fileName);
    });

    // DataTables Initialization
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search..."
            }
        });
    }
}); 