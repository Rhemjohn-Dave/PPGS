<?php

class UserController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getUserById($id)
    {
        $sql = "SELECT u.*, d.name as department_name 
                FROM users u 
                LEFT JOIN departments d ON u.department_id = d.id 
                WHERE u.id = ?";

        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                return mysqli_fetch_assoc($result);
            }
        }
        return false;
    }

    public function getUserByUsername($username)
    {
        $sql = "SELECT * FROM users WHERE username = ?";

        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                return mysqli_fetch_assoc($result);
            }
        }
        return false;
    }

    public function getUserByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                return mysqli_fetch_assoc($result);
            }
        }
        return false;
    }

    public function getAllUsers()
    {
        $sql = "SELECT u.*, d.name as department_name 
                FROM users u 
                LEFT JOIN departments d ON u.department_id = d.id 
                ORDER BY u.username";

        $result = mysqli_query($this->conn, $sql);
        $users = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }

        return $users;
    }

    public function getUsersByDepartment($department_id)
    {
        $sql = "SELECT u.*, d.name as department_name 
                FROM users u 
                LEFT JOIN departments d ON u.department_id = d.id 
                WHERE u.department_id = ?";

        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $department_id);

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $users = [];

                while ($row = mysqli_fetch_assoc($result)) {
                    $users[] = $row;
                }

                return $users;
            }
        }
        return [];
    }

    public function getUsersByRole($role)
    {
        $sql = "SELECT u.*, d.name as department_name 
                FROM users u 
                LEFT JOIN departments d ON u.department_id = d.id 
                WHERE u.role = ?";

        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $role);

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $users = [];

                while ($row = mysqli_fetch_assoc($result)) {
                    $users[] = $row;
                }

                return $users;
            }
        }
        return [];
    }

    public function createUser($data)
    {
        $sql = "INSERT INTO users (username, password, email, full_name, role, department_id) 
                VALUES (?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

            mysqli_stmt_bind_param(
                $stmt,
                "sssssi",
                $data['username'],
                $hashed_password,
                $data['email'],
                $data['full_name'],
                $data['role'],
                $data['department_id']
            );

            if (mysqli_stmt_execute($stmt)) {
                return mysqli_insert_id($this->conn);
            }
        }
        return false;
    }

    public function updateUser($id, $data)
    {
        $sql = "UPDATE users SET 
                username = ?, 
                email = ?, 
                full_name = ?, 
                role = ?, 
                department_id = ? 
                WHERE id = ?";

        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param(
                $stmt,
                "ssssii",
                $data['username'],
                $data['email'],
                $data['full_name'],
                $data['role'],
                $data['department_id'],
                $id
            );

            return mysqli_stmt_execute($stmt);
        }
        return false;
    }

    public function deleteUser($id)
    {
        $sql = "DELETE FROM users WHERE id = ?";

        if ($stmt = mysqli_prepare($this->conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            return mysqli_stmt_execute($stmt);
        }
        return false;
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    public function hasRole($user_id, $role)
    {
        $user = $this->getUserById($user_id);
        return $user && $user['role'] === $role;
    }
}