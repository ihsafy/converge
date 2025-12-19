<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$user = current_user();

// --- PERMISSION CHECK ---
$allowedRoles = ['super_admin', 'event_manager', 'member_manager', 'read_only'];

if (!in_array($user['role'], $allowedRoles)) {
    flash('error', 'Access Denied: You do not have admin permissions.');
    redirect('dashboard.php');
}

$pdo = getDB();
$memberCount = $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$eventCount  = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - CONVERGE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* --- DASHBOARD SPECIFIC STYLES --- */
        
        /* 1. Header Area */
        .dash-header {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 3rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f3f4f6;
        }
        .welcome-text {
            font-size: 1.2rem; /* Bigger text */
            color: #4b5563;
            margin-top: 5px;
        }
        
        /* 2. Bigger Buttons */
        .btn-header {
            padding: 12px 24px; /* Larger click area */
            font-size: 1rem;    /* Larger text */
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn-header:hover { transform: translateY(-2px); }
        .btn-view-mode { background: #4b5563; color: white; margin-right: 10px; }
        .btn-logout-mode { background: #ef4444; color: white; }

        /* 3. The Big Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 3rem;
        }
        .big-stat-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        .stat-label {
            font-size: 1.1rem;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .stat-number {
            font-size: 4rem; /* HUGE NUMBERS */
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 5px;
        }
        .stat-role {
            font-size: 1.8rem; /* Big text for role */
            font-weight: 800;
            color: #10b981; /* Green color */
            line-height: 1.2;
        }

        /* 4. Console Grid */
        .console-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        .console-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            text-decoration: none;
            display: block;
            transition: all 0.2s;
        }
        .console-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }
        .console-icon { font-size: 3rem; margin-bottom: 15px; display: block; }
        .console-title { font-size: 1.4rem; font-weight: 700; color: #111827; }
        .console-desc { font-size: 1rem; color: #6b7280; margin-top: 5px; }
    </style>
</head>
<body>
<div class="container animate-fade-up">
    
    <div class="dash-header">
        <div>
            <h1 style="color: var(--primary); margin: 0; font-size: 2.2rem;">Admin Dashboard</h1>
            <div class="welcome-text">
                Welcome, <strong><?= htmlspecialchars($user['full_name']) ?></strong>
            </div>
        </div>
        <div>
            <a href="logout.php" class="btn-header btn-logout-mode">Logout</a>
        </div>
    </div>

    <div class="stats-container">
        <div class="big-stat-card">
            <div class="stat-label">Total Members</div>
            <div class="stat-number"><?= $memberCount ?></div>
        </div>
        <div class="big-stat-card">
            <div class="stat-label">Total Events</div>
            <div class="stat-number"><?= $eventCount ?></div>
        </div>
        <div class="big-stat-card">
            <div class="stat-label">My Role</div>
            <div class="stat-role">
                <?= strtoupper(str_replace('_', ' ', $user['role'])) ?>
            </div>
        </div>
    </div>

    <h3 style="font-size: 1.5rem; margin-bottom: 20px; color: #374151;">Management Console</h3>
    
    <div class="console-grid">
        
        <?php if (in_array($user['role'], ['super_admin', 'member_manager', 'read_only'])): ?>
        <a href="admin_members.php" class="console-card">
            <span class="console-icon">👥</span>
            <div class="console-title">Manage Members</div>
            <div class="console-desc">Add, edit, or remove users</div>
        </a>
        <?php endif; ?>

        <?php if (in_array($user['role'], ['super_admin', 'event_manager', 'read_only'])): ?>
        <a href="admin_events.php" class="console-card">
            <span class="console-icon">📅</span>
            <div class="console-title">Manage Events</div>
            <div class="console-desc">Post updates and track schedules</div>
        </a>
        <?php endif; ?>

        <?php if (in_array($user['role'], ['super_admin', 'event_manager', 'member_manager'])): ?>
        <a href="analytics.php" class="console-card">
            <span class="console-icon">📊</span>
            <div class="console-title">Analytics</div>
            <div class="console-desc">View User & Event Ratios</div>
        </a>
        <?php endif; ?>

        <a href="profile.php" class="console-card">
            <span class="console-icon">⚙️</span>
            <div class="console-title">My Settings</div>
            <div class="console-desc">Update password or photo</div>
        </a>

    </div>

</div>
</body>
</html>