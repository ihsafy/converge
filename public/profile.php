<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

require_login();
$pdo = getDB();
$user = current_user();

// 1. HANDLE FILE UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $newFileName = 'user_' . $user['id'] . '_' . uniqid() . '.' . $ext;
            // Ensure uploads directory exists
            if (!is_dir(__DIR__ . '/uploads')) { mkdir(__DIR__ . '/uploads', 0755, true); }
            
            move_uploaded_file($file['tmp_name'], __DIR__ . '/uploads/' . $newFileName);
            
            // Update DB
            $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?")->execute([$newFileName, $user['id']]);
            
            flash('success', 'Profile photo updated!');
            header("Location: profile.php"); exit;
        } else {
            flash('error', 'Only JPG, PNG, and GIF files are allowed.');
        }
    }
}

// 2. HANDLE PASSWORD RESET
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $newPass = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($newPass !== $confirm) {
        flash('error', 'Passwords do not match.');
    } elseif (strlen($newPass) < 6) {
        flash('error', 'Password must be at least 6 characters.');
    } else {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $user['id']]);
        flash('success', 'Password changed successfully!');
        header("Location: profile.php"); exit;
    }
}

// 3. GET FRESH DATA
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$freshUser = $stmt->fetch();

$imgFile = $freshUser['profile_image'] ?? '';
$displayImg = (!empty($imgFile) && file_exists(__DIR__ . '/uploads/' . $imgFile)) 
    ? 'uploads/' . $imgFile 
    : "https://ui-avatars.com/api/?name=".urlencode($freshUser['full_name'])."&background=random&size=256";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Profile Settings - CONVERGE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --font-main: 'Inter', sans-serif;
            --bg-body: #f3f4f6;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: var(--bg-body);
            font-family: var(--font-main);
            color: #1f2937;
        }

        /* --- Navbar Style --- */
        nav {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .nav-brand { font-weight: 700; font-size: 1.25rem; color: #4f46e5; letter-spacing: -0.5px; }
        .nav-links a { text-decoration: none; font-weight: 500; margin-left: 20px; font-size: 0.95rem; }
        .nav-link-dash { color: #6b7280; }
        .nav-link-logout { color: #ef4444; }

        /* --- Profile Gradient Card --- */
        .profile-card {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); /* The Purple/Blue Gradient */
            border-radius: 24px;
            padding: 3rem 2rem;
            text-align: center;
            color: white;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .profile-avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.4);
            margin-bottom: 1rem;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .user-name { font-size: 1.8rem; font-weight: 700; margin: 0; }
        .user-email { opacity: 0.9; margin-top: 5px; font-weight: 400; font-size: 1rem; color: #e0e7ff; }

        /* --- Custom Upload Button --- */
        .upload-wrapper {
            position: relative;
            display: inline-block;
            margin-top: 1.5rem;
        }
        .btn-upload {
            background: rgba(0, 0, 0, 0.25); /* Semi-transparent dark pill */
            color: white;
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(4px);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-upload:hover { background: rgba(0, 0, 0, 0.4); transform: translateY(-1px); }
        .file-input-hidden {
            position: absolute; left: 0; top: 0; width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }

        /* --- Password Form Card --- */
        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--card-shadow);
        }
        .section-title {
            font-size: 1.2rem; font-weight: 700; color: #111827; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }

        .form-group label {
            display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
            outline: none;
            background: #f9fafb;
        }
        .form-control:focus {
            border-color: #6366f1;
            background: white;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn-save {
            margin-top: 1.5rem;
            width: 100%;
            padding: 14px;
            background: #1f2937;
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.2s;
        }
        .btn-save:hover { background: #111827; }

    </style>
</head>
<body>

<div class="container" style="max-width: 800px; margin: 0 auto; padding: 20px;">
    
    <nav>
        <div class="nav-brand">CONVERGE</div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link-dash">Dashboard</a>
            <a href="logout.php" class="nav-link-logout">Logout</a>
        </div>
    </nav>

    <?php if ($msg = flash('success')): ?> 
        <div class="success animate-fade-up" style="margin-bottom: 20px;"><?= htmlspecialchars($msg) ?></div> 
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?> 
        <div class="error animate-fade-up" style="margin-bottom: 20px;"><?= htmlspecialchars($msg) ?></div> 
    <?php endif; ?>

    <div class="profile-card animate-fade-up">
        <img src="<?= htmlspecialchars($displayImg) ?>" class="profile-avatar">
        <h2 class="user-name"><?= htmlspecialchars($freshUser['full_name']) ?></h2>
        <div class="user-email"><?= htmlspecialchars($freshUser['email']) ?></div>
        
        <form method="post" enctype="multipart/form-data">
            <div class="upload-wrapper">
                <button type="button" class="btn-upload">
                    📸 Change Photo
                </button>
                <input type="file" name="profile_pic" class="file-input-hidden" 
                       accept="image/*" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <div class="settings-card animate-fade-up">
        <div class="section-title">
            <span>🔒</span> Change Password
        </div>
        
        <form method="post">
            <input type="hidden" name="change_password" value="1">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-save">Update Password</button>
        </form>
    </div>

</div>

</body>
</html>