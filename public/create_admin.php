<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

echo "<h2>Creating Super Admin...</h2>";

// 1. Settings
$name  = "System Admin";
$email = "admin@converge.com";
$pass  = "admin"; // This is the password you want
$role  = "super_admin";
$desig = "Head Administrator";

// 2. Hash the password (CRITICAL STEP)
$hash = password_hash($pass, PASSWORD_DEFAULT);

try {
    // 3. Delete old admin if exists (to avoid duplicate error)
    $pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);

    // 4. Insert new admin
    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, password_hash, role, designation, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'active', NOW())
    ");
    
    $stmt->execute([$name, $email, $hash, $role, $desig]);

    echo "<h3 style='color:green'>✅ Success!</h3>";
    echo "<p>Admin User Created.</p>";
    echo "<p><strong>Email:</strong> $email</p>";
    echo "<p><strong>Password:</strong> $pass</p>";
    echo "<br><a href='login.php'>Go to Login Page</a>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>❌ Error</h3>";
    echo "Database Error: " . $e->getMessage();
}
?>