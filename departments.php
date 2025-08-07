<?php
session_start();
require_once 'database/connection.php';
require_once 'controllers/DepartmentController.php';
require_once 'controllers/UserController.php';

// Initialize controllers
$departmentController = new DepartmentController($conn);
$userController = new UserController($conn);

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin'){
    header("location: index.php");
    exit;
}

// Handle form submissions
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST['action'])){
        $response = ['success' => false, 'message' => 'An error occurred'];
        
        switch($_POST['action']){
            case 'create':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $head_id = !empty($_POST['head_id']) ? trim($_POST['head_id']) : null;
                
                $result = $departmentController->create($name, $description, $head_id);
                $response = $result;
                break;
                
            case 'update':
                $id = trim($_POST['id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $head_id = !empty($_POST['head_id']) ? trim($_POST['head_id']) : null;
                
                $result = $departmentController->update($id, $name, $description, $head_id);
                $response = $result;
                break;
                
            case 'delete':
                $id = trim($_POST['id']);
                $result = $departmentController->delete($id);
                $response = $result;
                break;
        }
        
        // Check if it's an AJAX request
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // Return JSON response for AJAX requests
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        } else {
            // Set session message for non-AJAX requests
            if($response['success']){
                $_SESSION['success_message'] = $response['message'];
            } else {
                $_SESSION['error_message'] = $response['message'];
            }
            header("location: departments.php");
            exit;
        }
    }
}

// Get all departments with additional information
$query = "SELECT 
    d.id,
    d.name,
    d.description,
    d.head_id,
    ph.username as head_username,
    ph.full_name as head_name,
    COUNT(DISTINCT u.id) as member_count,
    COUNT(DISTINCT tr.id) as task_count
FROM departments d
LEFT JOIN users u ON d.id = u.department_id
LEFT JOIN task_requests tr ON d.id = tr.department_id
LEFT JOIN users ph ON d.head_id = ph.id
GROUP BY d.id, d.name, d.description, d.head_id, ph.username, ph.full_name
ORDER BY d.name";

$result = mysqli_query($conn, $query);

if (!$result) {
    $_SESSION['error_message'] = "Error fetching departments: " . mysqli_error($conn);
    $departments = [];
} else {
    $departments = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get all program heads for dropdown
$programHeads = $userController->getUsersByRole('program head');

// Set page title
$page_title = "Departments";

// Include header
include 'includes/components/header.php';
// Include sidebar
include 'includes/components/sidebar.php';
// Include modals
include 'includes/components/modals.php';
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
                <h1 class="h3 mb-0 text-gray-800">Departments</h1>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createDepartmentModal">
                    <i class="fas fa-plus"></i> Add Department
                </button>
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

            <!-- Departments Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Department List</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Program Head</th>
                                    <th>Total Users</th>
                                    <th>Total Tasks</th>
                                    <th>Task Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($departments)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No departments found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($departments as $department): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($department['name']); ?></td>
                                        <td><?php echo htmlspecialchars($department['description']); ?></td>
                                        <td><?php echo $department['head_name'] ? htmlspecialchars($department['head_name']) : 'Not Assigned'; ?></td>
                                        <td class="text-center"><?php echo $department['member_count']; ?></td>
                                        <td class="text-center"><?php echo $department['task_count']; ?></td>
                                        <td>
                                            <?php if($department['task_count'] > 0): ?>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?php echo ($department['member_count'] / $department['task_count'] * 100); ?>%"
                                                     aria-valuenow="<?php echo $department['member_count']; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="<?php echo $department['task_count']; ?>">
                                                    <?php echo $department['member_count']; ?> Members
                                                </div>
                                            </div>
                                            <?php else: ?>
                                                No members
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="editDepartment(<?php echo $department['id']; ?>, '<?php echo addslashes(htmlspecialchars($department['name'])); ?>', '<?php echo addslashes(htmlspecialchars($department['description'])); ?>', '<?php echo $department['head_id']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteDepartment(<?php echo $department['id']; ?>, '<?php echo addslashes(htmlspecialchars($department['name'])); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->

    <?php include 'includes/components/footer.php'; ?>
</div>
<!-- End of Content Wrapper -->

<!-- Create Department Modal -->
<div class="modal fade" id="createDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="createDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createDepartmentModalLabel">Create New Department</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="name">Department Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="head_id">Program Head</label>
                        <select class="form-control" id="head_id" name="head_id">
                            <option value="">Select Program Head</option>
                            <?php if(!empty($programHeads)): ?>
                                <?php foreach($programHeads as $head): ?>
                                    <option value="<?php echo $head['id']; ?>">
                                        <?php echo htmlspecialchars($head['username'] . ' (' . $head['full_name'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No program heads available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="editDepartmentModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDepartmentModalLabel">Edit Department</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editDepartmentForm" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="form-group">
                        <label for="edit_name">Department Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_head_id">Program Head</label>
                        <select class="form-control" id="edit_head_id" name="head_id">
                            <option value="">Select Program Head</option>
                            <?php if(!empty($programHeads)): ?>
                                <?php foreach($programHeads as $head): ?>
                                    <option value="<?php echo $head['id']; ?>">
                                        <?php echo htmlspecialchars($head['username'] . ' (' . $head['full_name'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Department Modal -->
<div class="modal fade" id="deleteDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="deleteDepartmentModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteDepartmentModalLabel">Delete Department</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="deleteDepartmentForm" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <p>Are you sure you want to delete the department "<span id="delete_name"></span>"?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/components/footer_scripts.php'; ?>

<script>
// Simple direct functions for edit and delete
function editDepartment(id, name, description, headId) {
    // Fill the form
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_head_id').value = headId || '';
    
    // Show the modal
    $('#editDepartmentModal').modal('show');
}

function deleteDepartment(id, name) {
    // Fill the form
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').innerText = name;
    
    // Show the modal
    $('#deleteDepartmentModal').modal('show');
}

$(document).ready(function() {
    // Initialize DataTable
    $('#dataTable').DataTable();
    
    // Handle edit form submission
    $('#editDepartmentForm').submit(function(e) {
        e.preventDefault();
        document.getElementById('editDepartmentForm').submit();
    });
    
    // Handle delete form submission
    $('#deleteDepartmentForm').submit(function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this department? This action cannot be undone.')) {
            document.getElementById('deleteDepartmentForm').submit();
        }
    });
});
</script> 