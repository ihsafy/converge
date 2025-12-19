<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
require_login();

$user = current_user();
$pdo = getDB();

if (!in_array($user['role'], ['super_admin', 'member_manager', 'read_only'])) {
    flash('error', 'Access Denied.'); redirect('admin_dashboard.php');
}
$canEdit = in_array($user['role'], ['super_admin', 'member_manager']);
$memberId = $_GET['id'] ?? 0;

// 1. Handle Edit Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $canEdit) {
    // ... (Your existing update logic)
    $name = $_POST['full_name']; $email = $_POST['email']; 
    $desig = $_POST['designation']; $contact = $_POST['contact_info']; $role = $_POST['role'];
    $pdo->prepare("UPDATE users SET full_name=?, email=?, designation=?, contact_info=?, role=? WHERE id=?")
        ->execute([$name, $email, $desig, $contact, $role, $memberId]);
    flash('success', 'Profile updated.'); header("Location: admin_view_profile.php?id=$memberId"); exit;
}

// 2. NEW: Handle Password Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $canEdit) {
    $newPass = $_POST['new_pass'];
    $hash = password_hash($newPass, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $memberId]);
    flash('success', "Password reset to: <b>$newPass</b>");
    header("Location: admin_view_profile.php?id=$memberId"); exit;
}

// Fetch Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?"); $stmt->execute([$memberId]); $member = $stmt->fetch();
if (!$member) { flash('error', 'Member not found.'); redirect('admin_members.php'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container animate-fade-up">
    <nav>
        <a href="admin_members.php">&larr; Back to Members</a>
        <div style="float:right;">Profile View</div>
    </nav>

    <div class="card" style="display: flex; align-items: center; gap: 20px; background: linear-gradient(135deg, var(--primary), #818cf8); color: white;">
        <img src="<?= $member['profile_image'] ? 'uploads/'.$member['profile_image'] : 'https://ui-avatars.com/api/?name='.$member['full_name'] ?>" 
             style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid white;">
        <div style="flex-grow: 1;">
            <h2 style="margin:0; color:white;"><?= htmlspecialchars($member['full_name']) ?></h2>
            <p style="opacity:0.9; margin:5px 0;"><?= htmlspecialchars($member['email']) ?></p>
            <span class="role-badge" style="background:rgba(0,0,0,0.2);"><?= strtoupper(str_replace('_',' ',$member['role'])) ?></span>
        </div>
        <?php if ($canEdit): ?>
            <button onclick="document.getElementById('editModal').style.display='flex'" style="width: auto; background: white; color: var(--primary);">✎ Edit</button>
        <?php endif; ?>
    </div>

    <?php if ($msg = flash('success')): ?> <div class="success"><?= $msg ?></div> <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="card">
            <h3>Details</h3>
            <p><strong>Designation:</strong> <?= htmlspecialchars($member['designation'] ?? '-') ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($member['contact_info'] ?? '-') ?></p>
            
            <?php if ($canEdit): ?>
                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                <h4>⚠️ Admin Actions</h4>
                <form method="post" style="display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="reset_password" value="1">
                    <input type="text" name="new_pass" placeholder="New Password" required style="margin:0; padding: 8px;">
                    <button type="submit" style="width: auto; background: #ef4444; padding: 8px 15px;">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
        </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
        <h3>Edit Profile</h3>
        <form method="post">
            <input type="hidden" name="update_profile" value="1">
            <label>Name</label><input type="text" name="full_name" value="<?= $member['full_name'] ?>">
            <label>Email</label><input type="email" name="email" value="<?= $member['email'] ?>">
            <label>Designation</label><input type="text" name="designation" value="<?= $member['designation'] ?>">
            <label>Contact</label><input type="text" name="contact_info" value="<?= $member['contact_info'] ?>">
            <label>Role</label>
            <select name="role">
                <option value="member" <?= $member['role']=='member'?'selected':'' ?>>Member</option>
                <option value="super_admin" <?= $member['role']=='super_admin'?'selected':'' ?>>Super Admin</option>
            </select>
            <button type="submit">Save</button>
        </form>
    </div>
</div>
</body>
</html>