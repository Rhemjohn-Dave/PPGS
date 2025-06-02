<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPGS Task Management System</title>
    
    <!-- Material Admin Theme CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/material-design-iconic-font.min.css" rel="stylesheet">
    <link href="assets/css/materialadmin.css" rel="stylesheet">
    <link href="assets/css/demo.css" rel="stylesheet">
    
    <!-- Additional CSS -->
    <link href="assets/css/select2.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-datepicker.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside id="sidebar">
            <div class="sidebar-header">
                <h2>PPGS TMS</h2>
            </div>
            <ul class="list-unstyled">
                <?php if (isLoggedIn()): ?>
                    <li><a href="index.php?page=dashboard"><i class="zmdi zmdi-home"></i> Dashboard</a></li>
                    <?php if (getUserRole() === 'admin'): ?>
                        <li><a href="index.php?page=user_management"><i class="zmdi zmdi-accounts"></i> User Management</a></li>
                        <li><a href="index.php?page=department_management"><i class="zmdi zmdi-city"></i> Department Management</a></li>
                        <li><a href="index.php?page=reports"><i class="zmdi zmdi-chart"></i> Reports</a></li>
                    <?php endif; ?>
                    <li><a href="index.php?page=task_request"><i class="zmdi zmdi-plus-circle"></i> Request Task</a></li>
                    <li><a href="index.php?page=task_list"><i class="zmdi zmdi-format-list-bulleted"></i> Task List</a></li>
                    <li><a href="functions/logout.php"><i class="zmdi zmdi-power"></i> Logout</a></li>
                <?php endif; ?>
            </ul>
        </aside>

        <!-- Main Content -->
        <section id="content">
            <nav class="navbar navbar-default">
                <div class="container-fluid">
                    <div class="navbar-header">
                        <button type="button" id="sidebarCollapse" class="btn btn-info navbar-btn">
                            <i class="zmdi zmdi-menu"></i>
                        </button>
                    </div>
                    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                        <ul class="nav navbar-nav navbar-right">
                            <?php if (isLoggedIn()): ?>
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                        <i class="zmdi zmdi-account"></i> <?php echo $_SESSION['username']; ?> <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a href="index.php?page=profile"><i class="zmdi zmdi-account-box"></i> Profile</a></li>
                                        <li><a href="index.php?page=settings"><i class="zmdi zmdi-settings"></i> Settings</a></li>
                                        <li role="separator" class="divider"></li>
                                        <li><a href="functions/logout.php"><i class="zmdi zmdi-power"></i> Logout</a></li>
                                    </ul>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </nav>
            <div class="container-fluid">
                <!-- Content will be loaded here -->
            </div>
        </section>
    </div>
</body>
</html> 