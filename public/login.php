<?php
// 1. Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture Input (Can be Email OR Unique ID)
    $input = trim($_POST['login_id'] ?? ''); 
    $pass  = $_POST['password'] ?? '';

    // Search Database for EITHER Email OR Unique ID
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = ? OR unique_id = ?) AND status != 'removed'");
    $stmt->execute([$input, $input]);
    $user = $stmt->fetch();

    // Verify Password
    if ($user && password_verify($pass, $user['password_hash'])) {
        login_user($user);
        
        // Redirect based on Role
        $adminRoles = ['super_admin', 'event_manager', 'member_manager', 'read_only'];
        
        if (in_array($user['role'], $adminRoles)) {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    } else {
        flash('error', 'Invalid ID/Email or Password.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CONVERGE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f3f4f6; }
        .login-container { width: 100%; max-width: 400px; }
        
        /* Tab Switcher */
        .login-toggle { display: flex; background: #e5e7eb; padding: 5px; border-radius: 8px; margin-bottom: 2rem; }
        .toggle-btn { flex: 1; padding: 12px; text-align: center; cursor: pointer; border-radius: 6px; font-weight: 600; color: #6b7280; transition: all 0.3s ease; }
        
        /* Active States */
        .toggle-btn.active { background: white; color: var(--primary); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .toggle-btn.active-admin { background: white; color: #ef4444; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }

        .form-title { text-align: center; margin-bottom: 1.5rem; transition: all 0.2s; }
        
        /* Create Account Box */
        .create-account-box {
            text-align: center; 
            margin-top: 1.5rem; 
            padding-top: 1.5rem; 
            border-top: 1px solid #eee;
        }
        .create-account-box a {
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        .create-account-box a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container login-container animate-fade-up">
    
    <div style="text-align: center; margin-bottom: 2rem;">
        <h1 style="color: var(--primary); margin: 0; font-size: 2rem;">CONVERGE</h1>
        <p style="color: #6b7280;">Club Management System</p>
    </div>

    <?php if ($msg = flash('error')): ?> <div class="error"><?= htmlspecialchars($msg) ?></div> <?php endif; ?>
    <?php if ($msg = flash('success')): ?> <div class="success"><?= htmlspecialchars($msg) ?></div> <?php endif; ?>

    <div class="card">
        <div class="login-toggle">
            <div class="toggle-btn active" id="btn-member" onclick="switchTab('member')">Member Login</div>
            <div class="toggle-btn" id="btn-admin" onclick="switchTab('admin')">Admin Portal</div>
        </div>

        <h3 id="form-title" class="form-title">Welcome Member</h3>

        <form method="post">
            <label id="input-label">Email Address</label>
            <input type="text" name="login_id" id="login_input" placeholder="name@example.com" required>

            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
            
            <div style="text-align: right; margin-bottom: 15px;">
                <a href="forgot_password.php" style="font-size: 0.85rem; color: #6b7280; text-decoration: none;">Forgot Password?</a>
            </div>

            <button type="submit" id="submit-btn">Sign In</button>
        </form>

        <div class="create-account-box">
            <p style="margin: 0; color: #6b7280;">New to the club?</p>
            <a href="register.php" style="font-size: 1.1rem;">Create an Account &rarr;</a>
        </div>
    </div>

</div>

<script>
    function switchTab(type) {
        // Get Elements
        const title = document.getElementById('form-title');
        const btn = document.getElementById('submit-btn');
        const btnMember = document.getElementById('btn-member');
        const btnAdmin = document.getElementById('btn-admin');
        const label = document.getElementById('input-label');
        const input = document.getElementById('login_input');

        if (type === 'admin') {
            // --- ADMIN MODE ---
            btnMember.className = 'toggle-btn';
            btnAdmin.className = 'toggle-btn active-admin';
            
            title.innerText = 'Admin Access';
            title.style.color = '#ef4444';
            
            label.innerText = 'Admin ID (or Email)';
            input.placeholder = '2022-1-60-001';
            
            btn.style.background = 'linear-gradient(135deg, #ef4444, #b91c1c)';
            btn.innerText = 'Access Console';
        } else {
            // --- MEMBER MODE ---
            btnMember.className = 'toggle-btn active';
            btnAdmin.className = 'toggle-btn';
            
            title.innerText = 'Welcome Member';
            title.style.color = '#111827';
            
            label.innerText = 'Email Address';
            input.placeholder = 'name@example.com';
            
            btn.style.background = 'linear-gradient(135deg, #4f46e5, #4338ca)';
            btn.innerText = 'Sign In';
        }
    }
</script>

</body>
</html>