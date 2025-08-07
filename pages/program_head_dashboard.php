<?php
checkRoleAccess('program_head');
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Program Head Dashboard</h2>
                </div>
                <div class="card-body">
                    <!-- Quick Stats -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center">Pending Approval</h3>
                                    <h1 class="text-center">8</h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center">Approved Tasks</h3>
                                    <h1 class="text-center">15</h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center">Rejected Tasks</h3>
                                    <h1 class="text-center">3</h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center">Total Tasks</h3>
                                    <h1 class="text-center">26</h1>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row m-t-20">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h2>Quick Actions</h2>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <a href="index.php?page=task_request" class="btn btn-primary btn-block">
                                                <i class="zmdi zmdi-plus-circle"></i> Request Task
                                            </a>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="index.php?page=task_list" class="btn btn-primary btn-block">
                                                <i class="zmdi zmdi-format-list-bulleted"></i> View Tasks
                                            </a>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="index.php?page=pending_approvals" class="btn btn-primary btn-block">
                                                <i class="zmdi zmdi-check-all"></i> Pending Approvals
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Approvals -->
                    <div class="row m-t-20">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h2>Pending Approvals</h2>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped datatable">
                                            <thead>
                                                <tr>
                                                    <th>Task ID</th>
                                                    <th>Title</th>
                                                    <th>Requester</th>
                                                    <th>Category</th>
                                                    <th>Date Requested</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Task rows will be populated from database -->
                                                <tr>
                                                    <td>1</td>
                                                    <td>Printing Request</td>
                                                    <td>John Doe</td>
                                                    <td>Printing</td>
                                                    <td>2024-03-15</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success">Approve</button>
                                                        <button class="btn btn-sm btn-danger">Reject</button>
                                                        <button class="btn btn-sm btn-info">View</button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="row m-t-20">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h2>Recent Activity</h2>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Action</th>
                                                    <th>Task</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Activity rows will be populated from database -->
                                                <tr>
                                                    <td>2024-03-15</td>
                                                    <td>Approved</td>
                                                    <td>Printing Request</td>
                                                    <td><span class="label label-success">Completed</span></td>
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
    </div>
</div> 