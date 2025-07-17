<?php
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function getUserRole()
{
    return $_SESSION['user_role'] ?? null;
}

function redirectBasedOnRole()
{
    if (!isLoggedIn()) {
        header('Location: index.php?page=login');
        exit();
    }

    $role = getUserRole();
    switch ($role) {
        case 'admin':
            header('Location: index.php?page=admin_dashboard');
            break;
        case 'user':
            header('Location: index.php?page=user_dashboard');
            break;
        case 'program_head':
            header('Location: index.php?page=program_head_dashboard');
            break;
        case 'adaa':
            header('Location: index.php?page=adaa_dashboard');
            break;
        default:
            header('Location: index.php?page=login');
    }
    exit();
}

function checkRoleAccess($requiredRole)
{
    if (!isLoggedIn() || getUserRole() !== $requiredRole) {
        header('Location: index.php?page=unauthorized');
        exit();
    }
}
?>