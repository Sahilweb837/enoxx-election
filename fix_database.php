<?php
require_once 'employee/config.php';

function addColumnIfMissing($pdo, $table, $column, $definition) {
    if ($table === 'employees' && $column === 'role') {
        // Special case: check if 'status' exists and rename it to 'role'
        $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'status'");
        if ($stmt->rowCount() > 0) {
            echo "Renaming column 'status' to 'role' in table 'employees'...\n";
            $pdo->exec("ALTER TABLE employees CHANGE COLUMN status role ENUM('data_entry', 'supervisor', 'manager') DEFAULT 'data_entry'");
            return true;
        }
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($stmt->rowCount() == 0) {
        echo "Adding column '$column' to table '$table'...\n";
        $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        return true;
    }
    return false;
}

try {
    echo "--- FINAL Database Fix Script ---\n";
    
    // Fix employees table
    addColumnIfMissing($pdo, 'employees', 'role', "ENUM('data_entry', 'supervisor', 'manager') DEFAULT 'data_entry'");
    addColumnIfMissing($pdo, 'employees', 'is_active', "TINYINT(1) DEFAULT 1");
    addColumnIfMissing($pdo, 'employees', 'district_id', "INT NULL");
    
    // Force reset emp1
    echo "Resetting user 'emp1'...\n";
    $pdo->prepare("DELETE FROM employees WHERE username = 'emp1'")->execute();
    
    $password = password_hash('employee123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO employees (employee_id, username, password, full_name, email, phone, district_id, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['EMP001', 'emp1', $password, 'Rajesh Kumar', 'rajesh@enoxxnews.in', '9876543210', 1, 'data_entry', 1]);
    
    echo "--- Done! User 'emp1' with password 'employee123' is now ready. ---\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
