<?php
session_start();
require_once 'functions/auth.php';
require_once 'config/database.php';
require_once 'controllers/UserController.php';
require_once 'controllers/DepartmentController.php';

// Initialize controllers
$userController = new UserController($conn);
$departmentController = new DepartmentController($conn);

// Check if user is already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}

// Get all departments for the dropdown
$departments = $departmentController->getAll();

$username = $email = $password = $confirm_password = $full_name = $role = $department_id = "";
$username_err = $email_err = $password_err = $confirm_password_err = $full_name_err = $role_err = $department_err = $register_err = "";

$allowed_roles = ['user', 'program head', 'adaa', 'staff'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $username = trim($_POST["username"]);
        if ($userController->getUserByUsername($username)) {
            $username_err = "This username is already taken.";
        }
    }
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email address.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $email = trim($_POST["email"]);
        if ($userController->getUserByEmail($email)) {
            $email_err = "This email is already registered.";
        }
    }
    // Validate full name
    if (empty(trim($_POST["full_name"]))) {
        $full_name_err = "Please enter your full name.";
    } else {
        $full_name = trim($_POST["full_name"]);
    }
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    // Validate role
    if (empty(trim($_POST["role"]))) {
        $role_err = "Please select a role.";
    } else {
        $role = trim($_POST["role"]);
        if (!in_array($role, $allowed_roles)) {
            $role_err = "Invalid role selected.";
        }
    }
    // Validate department (optional)
    if ($role === 'adaa') {
        $department_id = '';
    } elseif (!empty(trim($_POST["department"]))) {
        $department_id = trim($_POST["department"]);
    }
    // Check input errors before inserting in database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($full_name_err) && empty($role_err)) {
        $userData = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'full_name' => $full_name,
            'role' => $role,
            'department_id' => $department_id
        ];
        if ($userController->createUser($userData)) {
            header("location: login.php");
            exit;
        } else {
            $register_err = "Something went wrong. Please try again later.";
        }
    }
}

$page_title = 'Register';
include 'includes/components/header.php';
?>
<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-lg my-5">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0">Create Account</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($register_err)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $register_err; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php foreach (['username_err', 'email_err', 'full_name_err', 'password_err', 'confirm_password_err', 'role_err'] as $err): ?>
                        <?php if (!empty($$err)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $$err; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"
                        autocomplete="off">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                value="<?php echo htmlspecialchars($full_name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-control" id="role" name="role" required
                                onchange="toggleDepartmentField()">
                                <option value="">Select Role</option>
                                <?php foreach ($allowed_roles as $r): ?>
                                    <option value="<?php echo $r; ?>" <?php echo ($role == $r) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($r); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="department" class="form-label">Department (optional)</label>
                            <select class="form-control" id="department" name="department">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo $department['id']; ?>" <?php echo ($department_id == $department['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($department['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted" id="department-note" style="display:none;">ADAA does not
                                belong to any department.</small>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-block">Register</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <a href="login.php">Already have an account? Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/components/footer_scripts.php'; ?>
<script>
    function toggleDepartmentField() {
        var role = document.getElementById('role').value;
        var department = document.getElementById('department');
        var note = document.getElementById('department-note');
        if (role === 'adaa') {
            department.value = '';
            department.disabled = true;
            note.style.display = 'block';
        } else {
            department.disabled = false;
            note.style.display = 'none';
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        toggleDepartmentField();
    });
</script>