<?php
checkRoleAccess('admin');
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Admin Dashboard</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Statistics Cards -->
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center">Total Tasks</h3>
                                    <h1 class="text-center">150</h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center">Pending Tasks</h3>
                                    <h1 class="text-center">25</h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center">Active Users</h3>
                                    <h1 class="text-center">45</h1>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="text-center">Departments</h3>
                                    <h1 class="text-center">12</h1>
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
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Task ID</th>
                                                    <th>Title</th>
                                                    <th>Category</th>
                                                    <th>Department</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Task rows will be populated from database -->
                                                <tr>
                                                    <td>1</td>
                                                    <td>Sample Task</td>
                                                    <td>Printing</td>
                                                    <td>IT Department</td>
                                                    <td><span class="label label-success">Completed</span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info">View</button>
                                                        <button class="btn btn-sm btn-primary">Assign</button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
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
                                        <div class="col-md-3">
                                            <a href="index.php?page=user_management" class="btn btn-primary btn-block">
                                                <i class="zmdi zmdi-accounts"></i> Manage Users
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="index.php?page=department_management" class="btn btn-primary btn-block">
                                                <i class="zmdi zmdi-city"></i> Manage Departments
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="index.php?page=reports" class="btn btn-primary btn-block">
                                                <i class="zmdi zmdi-chart"></i> Generate Reports
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="index.php?page=task_request" class="btn btn-primary btn-block">
                                                <i class="zmdi zmdi-plus-circle"></i> Create Task
                                            </a>
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
</div> 