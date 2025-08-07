<?php
require_once __DIR__ . '/../includes/helpers/department_helper.php';
require_once __DIR__ . '/../database/connection.php';

class DepartmentController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function create($name, $description, $head_id = null) {
        $data = [
            'name' => $name,
            'description' => $description,
            'head_id' => $head_id
        ];
        return createDepartment($data, $this->conn);
    }
    
    public function update($id, $name, $description, $head_id = null) {
        $data = [
            'name' => $name,
            'description' => $description,
            'head_id' => $head_id
        ];
        return updateDepartment($id, $data, $this->conn);
    }
    
    public function delete($id) {
        return deleteDepartment($id, $this->conn);
    }
    
    public function getById($id) {
        return getDepartmentById($id, $this->conn);
    }
    
    public function getAll() {
        return getAllDepartments($this->conn);
    }
    
    public function getStats($id) {
        return getDepartmentStats($id, $this->conn);
    }
} 