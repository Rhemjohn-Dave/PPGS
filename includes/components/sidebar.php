<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-text mx-3">TUP Visayas</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Task Management
    </div>

    <!-- Nav Item - Tasks -->
    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="tasks.php">
            <i class="fas fa-fw fa-list"></i>
            <span>Tasks</span>
        </a>
    </li>

    <!-- Nav Item - Request Task -->
    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'request_task.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="request_task.php">
            <i class="fas fa-fw fa-plus-circle"></i>
            <span>Request Task</span>
        </a>
    </li>

    <!-- Nav Item - Departments -->
    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'departments.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="departments.php">
            <i class="fas fa-fw fa-building"></i>
            <span>Departments</span>
        </a>
    </li>

    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == "admin"): ?>
    <!-- Nav Item - Task Requests -->
    <li class="nav-item">
        <a class="nav-link" href="task_requests.php">
            <i class="fas fa-clipboard-list"></i>
            <span>Task Requests</span>
        </a>
    </li>

    <!-- Nav Item - Users -->
    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="users.php">
            <i class="fas fa-fw fa-users"></i>
            <span>Users</span>
        </a>
    </li>

    <!-- Nav Item - Reports -->
    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'generate_report.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="generate_report.php">
            <i class="fas fa-fw fa-chart-bar"></i>
            <span>Reports</span>
        </a>
    </li>
    <?php endif; ?>

    <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'program head' || $_SESSION['role'] == 'adaa')): ?>
    <!-- Nav Item - Task Approvals -->
    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'task_approvals.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="task_approvals.php">
            <i class="fas fa-fw fa-check-double"></i>
            <span>Task Approvals</span>
        </a>
    </li>
    <?php endif; ?>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle">
            <i class="fas fa-angle-left"></i>
        </button>
    </div>
</ul>
<!-- End of Sidebar --> 