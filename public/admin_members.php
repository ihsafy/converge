<?php
require_once __DIR__ . '/../includes/auth.php';
// require_once __DIR__ . '/../includes/mailer.php'; // Uncomment if you have the mailer setup
require_login();

$currentUser = current_user();
$pdo = getDB();

// 1. PERMISSION CHECK (Page Access)
$allowed_roles = ['super_admin', 'member_manager', 'event_manager'];
if (!in_array($currentUser['role'], $allowed_roles)) {
    flash('error', 'Access Denied.');
    redirect('dashboard.php');
}

// ---------------------------------------------------------
// 2. HELPER: DUMMY MAILER (If you don't have mailer.php yet)
// ---------------------------------------------------------
if (!function_exists('send_welcome_email')) {
    function send_welcome_email($to, $name, $pass) {
        // In a real app, this sends an actual email.
        // For now, we just log it or do nothing to prevent errors.
        return true; 
    }
}

// ---------------------------------------------------------
// 3. HANDLE BULK CSV IMPORT
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $handle = fopen($fileTmpPath, 'r');
            fgetcsv($handle); // Skip header row
            
            $success = 0;
            $skipped = 0;
            $rawPassword = "Welcome123"; 
            $defaultPassHash = password_hash($rawPassword, PASSWORD_DEFAULT);

            while (($row = fgetcsv($handle)) !== false) {
                // CSV FORMAT: Name, Email, UniqueID, Phone, Role
                $name  = trim($row[0] ?? '');
                $email = trim($row[1] ?? '');
                $uid   = trim($row[2] ?? '');
                $phone = trim($row[3] ?? '');
                $role  = strtolower(trim($row[4] ?? 'member'));

                // Basic Validation
                if (empty($name) || empty($email)) { $skipped++; continue; }

                // Check Duplicates
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $check->execute([$email]);
                if ($check->fetch()) { $skipped++; continue; }

                try {
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, unique_id, password_hash, contact_info, role) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $uid ?: null, $defaultPassHash, $phone, $role]);
                    
                    // Send Email
                    send_welcome_email($email, $name, $rawPassword);
                    $success++;
                } catch (Exception $e) {
                    $skipped++;
                }
            }
            fclose($handle);
            flash('success', "Import Complete! Added: $success. Skipped: $skipped");
            header("Location: admin_members.php"); exit;
        } else {
            flash('error', 'Please upload a valid .CSV file');
        }
    }
}

// ---------------------------------------------------------
// 4. HANDLE MANUAL ADD MEMBER
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $name    = trim($_POST['full_name']);
    $email   = trim($_POST['email']);
    $uid     = trim($_POST['unique_id']); 
    $desig   = trim($_POST['designation']);
    $contact = trim($_POST['contact_info']);
    $role    = $_POST['role'] ?? 'member'; 
    
    // Security: Managers cannot create Admins
    if ($currentUser['role'] !== 'super_admin' && $role !== 'member') {
        $role = 'member'; // Force role to member if manager tries to create an admin
    }

    if (empty($uid)) $uid = null;
    $tempPass = bin2hex(random_bytes(4)); // Random 8 chars
    $hash = password_hash($tempPass, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, unique_id, password_hash, designation, contact_info, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $uid, $hash, $desig, $contact, $role]);
        $lastId = $pdo->lastInsertId();

        // Handle Profile Pic
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $newName = "user_{$lastId}_".uniqid().".$ext";
            if (!is_dir(__DIR__.'/uploads')) mkdir(__DIR__.'/uploads');
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], __DIR__.'/uploads/'.$newName);
            $pdo->prepare("UPDATE users SET profile_image=? WHERE id=?")->execute([$newName, $lastId]);
        }

        send_welcome_email($email, $name, $tempPass);
        flash('success', "User created! Email sent to <b>$email</b>.");
    } catch (PDOException $e) {
        flash('error', 'Error: Email or ID already exists.');
    }
    header("Location: admin_members.php"); exit;
}

// ---------------------------------------------------------
// 5. HANDLE DELETE
// ---------------------------------------------------------
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    // Fetch target user to check role
    $targetStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $targetStmt->execute([$deleteId]);
    $targetUser = $targetStmt->fetch();

    if ($targetUser) {
        // PERMISSION CHECK:
        // 1. Cannot delete yourself.
        // 2. Managers cannot delete Admins.
        if ($deleteId == $currentUser['id']) {
            flash('error', 'You cannot delete yourself.');
        } elseif ($currentUser['role'] !== 'super_admin' && $targetUser['role'] !== 'member') {
            flash('error', '⚠️ Permission Denied: You can only remove members.');
        } else {
            // Safe to delete
            $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$deleteId]);
            $pdo->prepare("DELETE FROM event_registrations WHERE user_id = ?")->execute([$deleteId]);
            $pdo->prepare("DELETE FROM events WHERE created_by = ?")->execute([$deleteId]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$deleteId]);
            flash('success', 'User deleted successfully.');
        }
    }
    header("Location: admin_members.php"); exit;
}

// ---------------------------------------------------------
// 6. FETCH MEMBERS (THE VISIBILITY FIX)
// ---------------------------------------------------------
if ($currentUser['role'] === 'super_admin') {
    // Super Admin sees EVERYONE
    $sql = "SELECT * FROM users ORDER BY created_at DESC";
} else {
    // Managers only see 'member' roles (Hides Super Admins & other Managers)
    $sql = "SELECT * FROM users WHERE role = 'member' ORDER BY created_at DESC";
}
$members = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Members - CONVERGE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #334155; }
        
        /* Layout */
        .container { max-width: 1100px; margin: 40px auto; padding: 20px; }
        
        /* Header & Import */
        .import-box {
            background: white; border: 2px dashed #cbd5e1; padding: 20px;
            border-radius: 12px; margin-bottom: 30px; display: flex;
            align-items: center; justify-content: space-between; gap: 15px;
        }
        .file-input-custom { border: 1px solid #e2e8f0; padding: 8px; border-radius: 6px; background: #f8fafc; }
        .btn-import {
            background: #059669; color: white; border: none; padding: 10px 20px;
            border-radius: 8px; font-weight: 600; cursor: pointer;
        }
        .btn-import:hover { background: #047857; }

        /* Controls */
        .controls-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .search-bar { padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 8px; width: 300px; }
        .btn-add {
            background: #2563eb; color: white; border: none; padding: 10px 20px;
            border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none;
        }
        
        /* Table */
        .table-card { background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; text-align: left; padding: 16px; font-weight: 700; color: #475569; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 16px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        
        /* Badges */
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .uid-badge { background: #f1f5f9; padding: 4px 8px; border-radius: 6px; color: #475569; font-size: 0.85rem; font-family: monospace; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 16px; width: 500px; max-width: 90%; position: relative; }
        .close-modal { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: #64748b; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
    <script>
        function searchTable() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let rows = document.querySelectorAll("tbody tr");
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? "" : "none";
            });
        }
    </script>
</head>
<body>

<div class="container animate-fade-up">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="margin:0; font-size: 1.8rem; color: #1e293b;">Member Management</h1>
        <a href="admin_dashboard.php" style="text-decoration: none; color: #64748b; font-weight: 600;">&larr; Back to Dashboard</a>
    </div>

    <?php if ($msg = flash('success')): ?> <div class="alert success"><?= $msg ?></div> <?php endif; ?>
    <?php if ($msg = flash('error')): ?> <div class="alert error"><?= $msg ?></div> <?php endif; ?>

    <div class="import-box">
        <div>
            <h4 style="margin:0; color:#1e293b;">📄 Bulk Import Members</h4>
            <p style="margin:5px 0 0; font-size:0.85rem; color:#64748b;">
                Upload CSV: <code>Name, Email, UniqueID, Phone, Role</code>
            </p>
        </div>
        <form method="post" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center;">
            <input type="file" name="csv_file" accept=".csv" required class="file-input-custom">
            <button type="submit" name="import_csv" class="btn-import">Import</button>
        </form>
    </div>

    <div class="controls-bar">
        <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="🔍 Search Name, Email, ID..." class="search-bar">
        <button onclick="document.getElementById('addMemberModal').style.display='flex'" class="btn-add">
            + Add Member
        </button>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Unique ID</th>
                    <th>Contact Info</th>
                    <th>Role</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">No members found.</td></tr>
                <?php else: ?>
                    <?php foreach ($members as $m): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="<?= $m['profile_image'] ? 'uploads/'.$m['profile_image'] : 'https://ui-avatars.com/api/?background=random&name='.urlencode($m['full_name']) ?>" 
                                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <div>
                                    <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($m['full_name']) ?></div>
                                    <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($m['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?= !empty($m['unique_id']) ? '<span class="uid-badge">'.$m['unique_id'].'</span>' : '<span style="color:#cbd5e1">—</span>' ?>
                        </td>
                        <td style="font-size: 0.9rem; color: #475569;">
                            <?= !empty($m['contact_info']) ? htmlspecialchars($m['contact_info']) : '—' ?>
                        </td>
                        <td>
                            <?php 
                                $badgeColor = match($m['role']) {
                                    'super_admin' => 'background:#fee2e2; color:#991b1b;',
                                    'event_manager' => 'background:#ffedd5; color:#9a3412;',
                                    'member_manager' => 'background:#e0e7ff; color:#3730a3;',
                                    default => 'background:#dcfce7; color:#166534;'
                                };
                            ?>
                            <span class="role-badge" style="<?= $badgeColor ?>">
                                <?= strtoupper(str_replace('_',' ',$m['role'])) ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a href="admin_view_profile.php?id=<?= $m['id'] ?>" style="color:#2563eb; text-decoration:none; margin-right:15px; font-weight:600; font-size:0.9rem;">View</a>
                            
                            <?php if ($currentUser['role'] === 'super_admin' || $m['role'] === 'member'): ?>
                                <a href="?delete=<?= $m['id'] ?>" onclick="return confirm('Are you sure?')" 
                                   style="color:#ef4444; text-decoration:none; font-weight:600; font-size:0.9rem;">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="addMemberModal" class="modal">
    <div class="modal-content animate-fade-up">
        <span class="close-modal" onclick="document.getElementById('addMemberModal').style.display='none'">&times;</span>
        <h3 style="margin-top:0;">Add New User</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="add_member" value="1">
            
            <label style="font-weight:600; font-size:0.9rem;">Full Name</label>
            <input type="text" name="full_name" required style="width:100%; padding:10px; margin:5px 0 15px; border:1px solid #ddd; border-radius:6px; box-sizing: border-box;">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="font-weight:600; font-size:0.9rem;">Email</label>
                    <input type="email" name="email" required style="width:100%; padding:10px; margin:5px 0 15px; border:1px solid #ddd; border-radius:6px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-weight:600; font-size:0.9rem;">Unique ID</label>
                    <input type="text" name="unique_id" placeholder="Optional" style="width:100%; padding:10px; margin:5px 0 15px; border:1px solid #ddd; border-radius:6px; box-sizing: border-box;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="font-weight:600; font-size:0.9rem;">Role</label>
                    <select name="role" style="width:100%; padding:10px; margin:5px 0 15px; border:1px solid #ddd; border-radius:6px; box-sizing: border-box;">
                        <option value="member">Member</option>
                        <?php if($currentUser['role'] === 'super_admin'): ?>
                            <option value="event_manager">Event Manager</option>
                            <option value="member_manager">Member Manager</option>
                            <option value="super_admin">Super Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label style="font-weight:600; font-size:0.9rem;">Contact</label>
                    <input type="text" name="contact_info" style="width:100%; padding:10px; margin:5px 0 15px; border:1px solid #ddd; border-radius:6px; box-sizing: border-box;">
                </div>
            </div>
            
            <label style="font-weight:600; font-size:0.9rem;">Profile Picture</label>
            <input type="file" name="profile_pic" accept="image/*" style="margin-bottom:20px;">
            
            <button type="submit" style="background:#2563eb; color:white; border:none; padding:12px; width:100%; border-radius:6px; font-weight:bold; cursor:pointer;">Create User</button>
        </form>
    </div>
</div>

<script>
    window.onclick = function(e) {
        if (e.target == document.getElementById('addMemberModal')) {
            document.getElementById('addMemberModal').style.display = "none";
        }
    }
</script>
</body>
</html>