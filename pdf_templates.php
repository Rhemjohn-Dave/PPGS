<?php
session_start();
require_once "config/database.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: index.php");
    exit;
}

// Fetch all PDF templates
$sql = "SELECT * FROM pdf_templates ORDER BY name";
$result = mysqli_query($conn, $sql);

// Set page title
$page_title = "PDF Templates";

// Include header and sidebar
include 'includes/components/header.php';
include 'includes/components/sidebar.php';
?>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <!-- Main Content -->
    <div id="content">
        <?php include 'includes/components/navbar.php'; ?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">PDF Templates</h1>
                <a href="create_template.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Add New Template
                </a>
            </div>

            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Content Row -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Available Templates</h6>
                        </div>
                        <div class="card-body">
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th>Created At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($template = mysqli_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($template["name"]); ?></td>
                                                    <td><?php echo htmlspecialchars($template["description"]); ?></td>
                                                    <td><?php echo date('M d, Y H:i', strtotime($template["created_at"])); ?></td>
                                                    <td>
                                                        <a href="edit_template.php?id=<?php echo $template["id"]; ?>" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        <a href="delete_template.php?id=<?php echo $template["id"]; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this template?');">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No PDF templates found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->

    <!-- Footer -->
    <?php include 'includes/components/footer.php'; ?>
    <!-- End of Footer -->
</div>
<!-- End of Content Wrapper -->

<?php include 'includes/components/footer_scripts.php'; ?> 