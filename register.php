<?php
session_start();
require_once 'config/database.php';
require_once 'controllers/UserController.php';
require_once 'controllers/DepartmentController.php';

// Initialize controllers
$userController = new UserController($conn);
$departmentController = new DepartmentController($conn);

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

// Get all departments for the dropdown
$departments = $departmentController->getAll();

$username = $password = $confirm_password = $role = $department_id = "";
$username_err = $password_err = $confirm_password_err = $role_err = $department_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        $username = trim($_POST["username"]);
        if($userController->getUserByUsername($username)){
            $username_err = "This username is already taken.";
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }

    // Validate role
    if(empty(trim($_POST["role"]))){
        $role_err = "Please select a role.";
    } else{
        $role = trim($_POST["role"]);
    }

    // Validate department
    if(empty(trim($_POST["department"]))){
        $department_err = "Please select a department.";
    } else{
        $department_id = trim($_POST["department"]);
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($role_err) && empty($department_err)){
        $userData = [
            'username' => $username,
            'password' => $password,
            'role' => $role,
            'department_id' => $department_id
        ];
        
        if($userController->createUser($userData)){
            header("location: login.php");
            exit;
        } else{
            echo "Something went wrong. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - NATZ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Register</h3>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group mb-3">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>    
                            <div class="form-group mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="form-group mb-3">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                            </div>
                            <div class="form-group mb-3">
                                <label>Role</label>
                                <select name="role" class="form-control <?php echo (!empty($role_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select Role</option>
                                    <option value="user" <?php echo ($role == 'user') ? 'selected' : ''; ?>>User</option>
                                    <option value="program_head" <?php echo ($role == 'program_head') ? 'selected' : ''; ?>>Program Head</option>
                                    <option value="adaa" <?php echo ($role == 'adaa') ? 'selected' : ''; ?>>ADAA</option>
                                </select>
                                <span class="invalid-feedback"><?php echo $role_err; ?></span>
                            </div>
                            <div class="form-group mb-3">
                                <label>Department</label>
                                <select name="department" class="form-control <?php echo (!empty($department_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select Department</option>
                                    <?php foreach($departments as $department): ?>
                                        <option value="<?php echo $department['id']; ?>" <?php echo ($department_id == $department['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($department['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="invalid-feedback"><?php echo $department_err; ?></span>
                            </div>
                            <div class="form-group mb-3">
                                <input type="submit" class="btn btn-primary w-100" value="Register">
                            </div>
                            <p class="text-center">Already have an account? <a href="login.php">Login here</a></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 