<?php
checkRoleAccess(getUserRole());
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Task List</h2>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row m-b-20">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3>Filters</h3>
                                </div>
                                <div class="card-body">
                                    <form id="filterForm" class="form-inline">
                                        <div class="form-group m-r-10">
                                            <label for="category" class="m-r-5">Category:</label>
                                            <select class="form-control" id="category" name="category">
                                                <option value="">All Categories</option>
                                                <option value="printing">Risograph / Printing</option>
                                                <option value="repairs">Repairs</option>
                                                <option value="maintenance">Maintenance</option>
                                                <option value="instructional">Instructional Materials</option>
                                            </select>
                                        </div>
                                        <div class="form-group m-r-10">
                                            <label for="status" class="m-r-5">Status:</label>
                                            <select class="form-control" id="status" name="status">
                                                <option value="">All Status</option>
                                                <option value="pending">Pending</option>
                                                <option value="approved">Approved</option>
                                                <option value="in_progress">In Progress</option>
                                                <option value="completed">Completed</option>
                                                <option value="rejected">Rejected</option>
                                            </select>
                                        </div>
                                        <div class="form-group m-r-10">
                                            <label for="priority" class="m-r-5">Priority:</label>
                                            <select class="form-control" id="priority" name="priority">
                                                <option value="">All Priorities</option>
                                                <option value="low">Low</option>
                                                <option value="medium">Medium</option>
                                                <option value="high">High</option>
                                            </select>
                                        </div>
                                        <div class="form-group m-r-10">
                                            <label for="date_range" class="m-r-5">Date Range:</label>
                                            <input type="text" class="form-control" id="date_range" name="date_range" placeholder="Select Date Range">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                        <button type="reset" class="btn btn-default">Clear Filters</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Task List -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-striped datatable">
                                    <thead>
                                        <tr>
                                            <th>Task ID</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Requested By</th>
                                            <th>Date Requested</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Task rows will be populated from database -->
                                        <tr>
                                            <td>1</td>
                                            <td>Printing Request</td>
                                            <td>Printing</td>
                                            <td><span class="label label-warning">Medium</span></td>
                                            <td><span class="label label-success">Completed</span></td>
                                            <td>John Doe</td>
                                            <td>2024-03-15</td>
                                            <td>
                                                <button class="btn btn-sm btn-info">View</button>
                                                <?php if (getUserRole() === 'admin'): ?>
                                                    <button class="btn btn-sm btn-primary">Assign</button>
                                                <?php endif; ?>
                                                <?php if (getUserRole() === 'program_head' || getUserRole() === 'adaa'): ?>
                                                    <button class="btn btn-sm btn-success">Approve</button>
                                                    <button class="btn btn-sm btn-danger">Reject</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize date range picker
    $('#date_range').daterangepicker({
        opens: 'left',
        locale: {
            format: 'YYYY-MM-DD'
        }
    });

    // Handle filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        // AJAX call to fetch filtered tasks
        // Update table with filtered results
    });

    // Handle task actions
    $('.btn-info').on('click', function() {
        // Show task details modal
    });

    $('.btn-primary').on('click', function() {
        // Show assign task modal
    });

    $('.btn-success').on('click', function() {
        // Handle task approval
    });

    $('.btn-danger').on('click', function() {
        // Handle task rejection
    });
});
</script> 