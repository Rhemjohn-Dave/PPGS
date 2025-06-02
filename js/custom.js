// Custom JavaScript for TUP PPGS Task Management System

// Initialize tooltips
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});

// Initialize popovers
$(function () {
    $('[data-toggle="popover"]').popover();
});

// Handle task status updates
function updateTaskStatus(taskId, newStatus) {
    $.ajax({
        url: 'update_task_status.php',
        method: 'POST',
        data: {
            task_id: taskId,
            status: newStatus
        },
        success: function(response) {
            if(response.success) {
                // Update status display
                $('#task-status').text(newStatus);
                // Show success message
                showAlert('success', 'Task status updated successfully');
            } else {
                showAlert('danger', 'Failed to update task status');
            }
        },
        error: function() {
            showAlert('danger', 'An error occurred while updating the status');
        }
    });
}

// Handle comment submission
function submitComment(taskId) {
    const comment = $('#comment-input').val();
    if(comment.trim() === '') {
        showAlert('warning', 'Please enter a comment');
        return;
    }

    $.ajax({
        url: 'add_comment.php',
        method: 'POST',
        data: {
            task_id: taskId,
            comment: comment
        },
        success: function(response) {
            if(response.success) {
                // Add new comment to the list
                addCommentToList(response.comment);
                // Clear input
                $('#comment-input').val('');
                showAlert('success', 'Comment added successfully');
            } else {
                showAlert('danger', 'Failed to add comment');
            }
        },
        error: function() {
            showAlert('danger', 'An error occurred while adding the comment');
        }
    });
}

// Add new comment to the list
function addCommentToList(comment) {
    const commentHtml = `
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h6 class="card-subtitle mb-2 text-muted">${comment.username}</h6>
                    <small class="text-muted">${comment.created_at}</small>
                </div>
                <p class="card-text">${comment.comment}</p>
            </div>
        </div>
    `;
    $('#comments-list').prepend(commentHtml);
}

// Show alert message
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    $('#alert-container').html(alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

// Handle task deletion confirmation
function confirmDelete(taskId) {
    if(confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
        window.location.href = `delete_task.php?id=${taskId}`;
    }
}

// Handle form validation
function validateTaskForm() {
    let isValid = true;
    const title = $('#task-title').val();
    const description = $('#task-description').val();
    const priority = $('#task-priority').val();
    const assignedTo = $('#task-assigned-to').val();

    if(!title.trim()) {
        showAlert('danger', 'Please enter a title');
        isValid = false;
    }

    if(!description.trim()) {
        showAlert('danger', 'Please enter a description');
        isValid = false;
    }

    if(!priority) {
        showAlert('danger', 'Please select a priority');
        isValid = false;
    }

    if(!assignedTo) {
        showAlert('danger', 'Please select someone to assign the task to');
        isValid = false;
    }

    return isValid;
}

// Initialize date pickers
$(function() {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });
});

// Handle task filtering
function filterTasks() {
    const status = $('#status-filter').val();
    const priority = $('#priority-filter').val();
    const search = $('#search-input').val().toLowerCase();

    $('.task-row').each(function() {
        const row = $(this);
        const rowStatus = row.data('status');
        const rowPriority = row.data('priority');
        const rowText = row.text().toLowerCase();

        const statusMatch = !status || rowStatus === status;
        const priorityMatch = !priority || rowPriority === priority;
        const searchMatch = !search || rowText.includes(search);

        if(statusMatch && priorityMatch && searchMatch) {
            row.show();
        } else {
            row.hide();
        }
    });
}

// Initialize task filtering
$(function() {
    $('#status-filter, #priority-filter').change(filterTasks);
    $('#search-input').on('keyup', filterTasks);
}); 