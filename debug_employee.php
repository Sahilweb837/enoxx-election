<?php
require_once 'employee/config.php';

echo "Database: himachal_panchayat_elections\n";

try {
    $stmt = $pdo->query("SELECT id, username, password, is_active FROM employees");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "No employees found in the table. Attempting to insert default employee...\n";
    }
    
    // Check if emp1 exists
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE username = ?");
    $stmt->execute(['emp1']);
    if (!$stmt->fetch()) {
        echo "Employee 'emp1' not found. Inserting now...\n";
        $password = password_hash('employee123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO employees (employee_id, username, password, full_name, email, phone, district_id, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['EMP001', 'emp1', $password, 'Rajesh Kumar', 'rajesh@enoxxnews.in', '9876543210', 1, 'data_entry']);
        echo "Default employee 'emp1' inserted with password 'employee123'.\n";
    } else {
        echo "Employee 'emp1' already exists.\n";
    }
    
    $stmt = $pdo->query("SELECT id, username, password, is_active FROM employees");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
       