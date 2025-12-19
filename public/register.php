<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $type  = $_POST['reg_type'] ?? 'member';
    $uid   = trim($_POST['unique_id'] ?? '');
    $desig = trim($_POST['designation'] ?? 'Member'); // NEW: Capture Designation

    if ($name === '' || $email === '' || $pass === '') {
        flash('error', 'All fields are required.');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            flash('error', 'Email already registered.');
        } else {
            $role = 'member';
            if ($type === 'admin') {
                if (empty($uid)) {
                    flash('error', 'Admin ID is required.'); header("Location: register.php"); exit;
                }
                // Validate ID format 2022-1-60-001
                if (!preg_match('/^\d{4}-\d{1}-\d{2}-\d{3}$/', $uid)) {
                    flash('error', 'Invalid ID Format.'); header("Location: register.php"); exit;
                }
                $role = 'super_admin';
            } else {
                $uid = null;
                $desig = 'Member'; // Force Member designation for non-admins
            }

            $hash = password_hash($pass, PASSWORD_DEFAULT);
            // Insert with Designation
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status, unique_id, designation) VALUES (?, ?, ?, ?, 'active', ?, ?)");
            
            if ($stmt->execute([$name, $email, $hash, $role, $uid, $desig])) {
                flash('success', "Account created! Please log in.");
                header("Location: login.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Join Us - CONVERGE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f3f4f6; }
        .register-container { width: 100%; max-width: 450px; }
        .reg-toggle { display: flex; background: #e5e7eb; padding: 5px; border-radius: 8px; margin-bottom: 1.5rem; }
        .toggle-btn { flex: 1; padding: 12px; text-align: center; cursor: pointer; border-radius: 6px; font-weight: 600; color: #6b7280; transition: all 0.3s ease; }
        .toggle-btn.active { background: white; color: var(--primary); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .toggle-btn.active-admin { background: white; color: #ef4444; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        #admin-fields { display: none; }
    </style>
</head>
<body>

<div class="container register-container animate-fade-up">
    <div style="text-align: center; margin-bottom: 2rem;">
        <h1 style="color: var(--primary); margin: 0; font-size: 2rem;">CONVERGE</h1>
        <p style="color: #6b7280;">Create your account</p>
    </div>

    <?php if ($msg = flash('error')): ?> <div class="error"><?= htmlspecialchars($msg) ?></div> <?php endif; ?>

    <div class="card">
        <div class="reg-toggle">
            <div class="toggle-btn active" id="btn-member" onclick="switchReg('member')">Join as Member</div>
            <div class="toggle-btn" id="btn-admin" onclick="switchReg('admin')">Admin Registration</div>
        </div>

        <form method="post">
            <input type="hidden" name="reg_type" id="reg_type" value="member">

            <label>Full Name</label>
            <input type="text" name="full_name" placeholder="John Doe" required>

            <label>Email Address</label>
            <input type="email" name="email" placeholder="name@example.com" required>

            <div id="admin-fields" class="animate-fade-up">
                <label style="color: #ef4444; font-weight: bold;">Admin Unique ID</label>
                <input type="text" name="unique_id" id="unique_id_input" placeholder="2022-1-60-001">
                
                <label style="color: #ef4444; font-weight: bold;">Designation</label>
                <input type="text" name="designation" id="designation_input" placeholder="e.g. Secretary, Head of Events">
            </div>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" id="submit-btn">Create Member Account</button>
        </form>
        
        <div style="text-align: center; margin-top: 1.5rem;">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>
    </div>
</div>

<script>
    function switchReg(type) {
        const btnMember = document.getElementById('btn-member');
        const btnAdmin = document.getElementById('btn-admin');
        const adminFields = document.getElementById('admin-fields');
        const submitBtn = document.getElementById('submit-btn');
        const regTypeInput = document.getElementById('reg_type');
        const uidInput = document.getElementById('unique_id_input');

        if (type === 'admin') {
            btnMember.className = 'toggle-btn';
            btnAdmin.className = 'toggle-btn active-admin';
            adminFields.style.display = 'block';
            regTypeInput.value = 'admin';
            uidInput.required = true;
            submitBtn.style.background = 'linear-gradient(135deg, #ef4444, #b91c1c)';
            submitBtn.innerText = 'Create Admin Account';
        } else {
            btnMember.className = 'toggle-btn active';
            btnAdmin.className = 'toggle-btn';
            adminFields.style.display = 'none';
            regTypeInput.value = 'member';
            uidInput.required = false;
            submitBtn.style.background = 'linear-gradient(135deg, #4f46e5, #4338ca)';
            submitBtn.innerText = 'Create Member Account';
        }
    }
</script>
</body>
</html>