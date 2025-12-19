<?php
require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'];
    $postToken = $_POST['token'];

    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ?");
    $stmt->execute([$postToken]);
    $user = $stmt->fetch();

    if ($user) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL WHERE id = ?")->execute([$hash, $user['id']]);
        flash('success', 'Password updated! Please log in.');
        header("Location: login.php");
        exit;
    } else {
        flash('error', 'Invalid or expired token.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; height:100vh; background:#f3f4f6;">
    <div class="container" style="max-width:400px;">
        <h2>Set New Password</h2>
        <?php if ($msg = flash('error')): ?> <div class="error"><?= $msg ?></div> <?php endif; ?>
        <div class="card">
            <form method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <label>New Password</label>
                <input type="password" name="password" required>
                <button type="submit">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>