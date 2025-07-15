<?php
checkRoleAccess('user');
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>User Dashboard</h2>
                </div>
                <div class="card-body">
                    <!-- Quick Stats -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center">My Tasks</h3>
                                    <h1 class="text-center">12</h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center">Pending</h3>
                                    <h1 class="text-center">5</h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center">In Progress</h3>
                                    <h1 class="text-center">3</h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center">Completed</h3>
                                    <h1 class="text-center">4</h1>
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
                                        <div class="col-md-6">
                                            <a href="index.php?page=task_request" class="btn btn-primary btn-block">
                                                <i class="zmdi zmdi-plus-circle"></i> Request New Task
                                            </a>
                                        </div>
                                        <div class="col-md-6">
                                            <a href="index.php?page=task_list" class="btn btn-primary btn-block">
                                                <i class="zmdi zmdi-format-list-bulleted"></i> View My Tasks
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Tasks -->
                    <div class="row m-t-20">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h2>Recent Tasks</h2>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped datatable">
                                            <thead>
                                                <tr>
                                                    <th>Task ID</th>
                                                    <th>Title</th>
                                                    <th>Category</th>
                                                    <th>Status</th>
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
                                                    <td><span class="label label-success">Completed</span></td>
                                                    <td>2024-03-15</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info">View</button>
                                                        <button class="btn btn-sm btn-primary">Update</button>
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
    </div>
</div> 