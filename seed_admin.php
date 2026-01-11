<?php
// Include DB config to connect
require_once 'config/db.php';

// The admin user credentials
$fname = 'Anuj';
$lname = 'Kumar';
$email = 'anuj.kumar@wishluvbuildcon.com';
$raw_password = 'Anuj@2025';
$role = 'Admin';
$emp_code = 'ADMIN01'; // Default ID

// Hash the password securely
$hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

try {
    // Check if user already exists
    $check = $conn->prepare("SELECT id FROM employees WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        // Update password if user exists
        $stmt = $conn->prepare("UPDATE employees SET password = :pass, role = :role WHERE email = :email");
        $stmt->execute(['pass' => $hashed_password, 'role' => $role, 'email' => $email]);
        echo "User already key exists. Password updated successfully.<br>";
    } else {
        // Insert new admin user
        $stmt = $conn->prepare("INSERT INTO employees (employee_code, first_name, last_name, email, password, role, status) VALUES (:code, :fname, :lname, :email, :pass, :role, 'Active')");
        $stmt->execute([
            'code' => $emp_code,
            'fname' => $fname,
            'lname' => $lname,
            'email' => $email,
            'pass' => $hashed_password,
            'role' => $role
        ]);
        echo "Admin user created successfully.<br>";
    }

    echo "Email: $email<br>";
    echo "Password: $raw_password<br>";
    echo "<br><a href='login.php'>Go to Login</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>