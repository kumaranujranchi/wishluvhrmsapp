<?php
require_once 'config/db.php';

$email = 'anuj.kumar@wishluvbuildcon.com';
$password = 'Anuj@2025';

echo "<h2>Debug Login for: $email</h2>";

try {
    // 1. Check DB Connection
    if ($conn) {
        echo "<p style='color:green'>Database Connected Successfully.</p>";
    }

    // 2. Check if user exists
    $stmt = $conn->prepare("SELECT * FROM employees WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        echo "<p style='color:green'>User Found!</p>";
        echo "Stored Hash: " . $user['password'] . "<br>";

        // 3. Verify Password
        if (password_verify($password, $user['password'])) {
            echo "<h3 style='color:green'>Password Verification PASSED!</h3>";
            echo "You should be able to login.";
        } else {
            echo "<h3 style='color:red'>Password Verification FAILED!</h3>";
            echo "The stored password hash does not match 'Anuj@2025'.";

            // Attempt to reset
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE employees SET password = :pass WHERE id = :id");
            $update->execute(['pass' => $new_hash, 'id' => $user['id']]);
            echo "<br><br><b>I have automatically reset the password to 'Anuj@2025'. Please try logging in again.</b>";
        }
    } else {
        echo "<p style='color:red'>User NOT Found.</p>";
        echo "Creating user now...";

        // Create User
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO employees (employee_code, first_name, last_name, email, password, role, status) VALUES ('ADMIN01', 'Anuj', 'Kumar', :email, :pass, 'Admin', 'Active')");
        $stmt->execute(['email' => $email, 'pass' => $hashed_password]);

        echo "<h3 style='color:green'>Admin User Created Successfully!</h3>";
        echo "Please go back and login.";
    }

} catch (PDOException $e) {
    echo "<h3>Error:</h3> " . $e->getMessage();
}
?>