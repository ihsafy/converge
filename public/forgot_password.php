<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(16));
        $pdo->prepare("UPDATE users SET reset_token = ? WHERE id = ?")->execute([$token, $user['id']]);
        
        // SIMULATED EMAIL (This would normally be sent via SMTP)
        $resetLink = "reset_password.php?token=$token";
        flash('success', "SIMULATION: Reset email sent! <br> <a href='$resetLink'>Click here to reset password</a>");
    } else {
        flash('error', 'Email not found.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; height:100vh; background:#f3f4f6;">
    <div class="container" style="max-width:400px;">
        <h2 style="text-align:center;">Recover Password</h2>
        <?php if ($msg = flash('success')): ?> <div class="success"><?= $msg ?></div> <?php endif; ?>
        <?php if ($msg = flash('error')): ?> <div class="error"><?= $msg ?></div> <?php endif; ?>

        <div class="card">
            <form method="post">
                <label>Enter your email</label>
                <input type="email" name="email" required>
                <button type="submit">Send Reset Link</button>
            </form>
            <p style="text-align:center; margin-top:10px;"><a href="login.php">Back to Login</a></p>
        </div>
    </div>
</body>
</html>