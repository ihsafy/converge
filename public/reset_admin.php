<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

// 1. configuration
$email = 'admin@converge.com';
$password = 'admin'; // The password you want
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // 2. Remove any existing account with this email to avoid errors
    $pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);

    // 3. Insert the Super Admin
    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, password_hash, role, designation, status)
        VALUES ('System Admin', ?, ?, 'super_admin', 'Head Administrator', 'active')
    ");
    $stmt->execute([$email, $hash]);

    echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
    echo "<h1 style='color:green'>✅ Admin Account Restored</h1>";
    echo "<p>You can now login with:</p>";
    echo "<p>Email: <b>$email</b></p>";
    echo "<p>Password: <b>$password</b></p>";
    echo "<br><a href='login.php' style='background:blue; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Go to Login</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}