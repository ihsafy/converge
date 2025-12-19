<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$pdo = getDB();

// Redirect if not a member (Admins go to admin_dashboard.php)
if ($user['role'] !== 'member') {
    redirect('admin_dashboard.php');
}

// --- HANDLE EVENT ACTIONS (Join/Leave) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = (int)$_POST['event_id'];
    $action  = $_POST['action'];

    if ($action === 'join') {
        // Check capacity
        $check = $pdo->prepare("SELECT count(*) as registered, max_capacity FROM event_registrations r RIGHT JOIN events e ON r.event_id = e.id WHERE e.id = ?");
        $check->execute([$eventId]);
        $data = $check->fetch();

        // Check if already registered
        $isReg = $pdo->prepare("SELECT id FROM event_registrations WHERE user_id = ? AND event_id = ?");
        $isReg->execute([$user['id'], $eventId]);

        if (!$isReg->fetch() && $data['registered'] < $data['max_capacity']) {
            $stmt = $pdo->prepare("INSERT INTO event_registrations (user_id, event_id) VALUES (?, ?)");
            $stmt->execute([$user['id'], $eventId]);
            flash('success', '🎉 You are in! Event added to your schedule.');
        } else {
            flash('error', '⚠️ Could not join (Event might be full or already joined).');
        }
    } elseif ($action === 'leave') {
        $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE user_id = ? AND event_id = ?");
        $stmt->execute([$user['id'], $eventId]);
        flash('success', 'You have left the event.');
    }
    // Refresh the page
    redirect('dashboard.php');
}

// --- FETCH DATA ---
$myEventsSql = "SELECT e.* FROM events e JOIN event_registrations r ON e.id = r.event_id WHERE r.user_id = ? ORDER BY e.event_date ASC";
$stmt = $pdo->prepare($myEventsSql);
$stmt->execute([$user['id']]);
$myList = $stmt->fetchAll();

$allEventsSql = "SELECT e.*, (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as current_count FROM events e WHERE e.event_date >= NOW() AND e.id NOT IN (SELECT event_id FROM event_registrations WHERE user_id = ?) ORDER BY e.event_date ASC";
$stmt = $pdo->prepare($allEventsSql);
$stmt->execute([$user['id']]);
$availList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - CONVERGE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #7c3aed;
            --bg-color: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            color: #334155;
            margin: 0;
            padding-bottom: 50px;
            overflow-x: hidden;
        }

        /* --- BACKGROUND BLOBS --- */
        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.6;
            animation: float 10s infinite alternate;
        }
        .blob-1 { top: -100px; left: -100px; width: 500px; height: 500px; background: #e0e7ff; border-radius: 40% 60% 70% 30%; }
        .blob-2 { top: 200px; right: -100px; width: 400px; height: 400px; background: #f3e8ff; border-radius: 60% 40% 30% 70%; animation-delay: -5s; }

        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(20px, 40px); }
        }

        /* --- NAVBAR --- */
        .navbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.3);
            position: sticky; top: 0; z-index: 50;
            padding: 15px 0;
        }
        .nav-container {
            max-width: 1100px; margin: 0 auto; padding: 0 20px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .logo { font-weight: 800; font-size: 1.5rem; color: var(--primary); text-decoration: none; }
        
        .nav-links a {
            text-decoration: none; color: #64748b; font-weight: 600; margin-left: 20px;
            transition: color 0.2s; font-size: 0.95rem;
        }
        .nav-links a:hover { color: var(--primary); }
        .nav-links .logout { color: #ef4444; }

        /* --- CONTAINER --- */
        .main-container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }

        /* --- WELCOME BANNER --- */
        .welcome-banner {
            background: linear-gradient(135deg, #e0e7ff, #f3e8ff); 
            color: #1e293b; 
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.15);
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            border: 1px solid #c7d2fe;
        }
        .welcome-banner h1 { margin: 0; font-size: 2.2rem; font-weight: 800; color: var(--primary); }
        
        .welcome-banner p { 
            margin: 10px 0 0; 
            color: black; 
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .banner-circle {
            position: absolute; width: 200px; height: 200px; background: rgba(255,255,255,0.4);
            border-radius: 50%; top: -50px; right: -50px;
        }

        /* --- SECTIONS --- */
        .section-header { 
            display: flex; align-items: center; justify-content: space-between; 
            margin-bottom: 25px; margin-top: 50px; 
        }
        .section-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        .count-badge { background: #e2e8f0; color: #475569; padding: 2px 10px; border-radius: 12px; font-size: 0.85rem; }

        /* --- CARD GRID --- */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }

        .card {
            background: white; border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;
            transition: all 0.3s ease; display: flex; flex-direction: column;
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.1); }

        .card-body { padding: 25px; flex-grow: 1; }
        
        /* TITLE LINK STYLE */
        .event-title-link { text-decoration: none; color: inherit; transition: color 0.2s; }
        .event-title-link:hover { color: var(--primary); text-decoration: underline; }

        .date-badge {
            display: inline-block; padding: 6px 12px; border-radius: 8px;
            font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
            margin-bottom: 15px;
        }
        .date-blue { background: #eff6ff; color: #2563eb; }
        .date-purple { background: #f3e8ff; color: #7c3aed; }

        .card h3 { margin: 0 0 10px; font-size: 1.25rem; color: #1e293b; }
        .location { color: #64748b; font-size: 0.95rem; margin-bottom: 15px; display: flex; align-items: center; gap: 5px; }
        
        /* DESCRIPTION STYLE */
        .description {
            font-size: 0.95rem; color: #475569; line-height: 1.5; margin-bottom: 0;
            display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
            background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #f1f5f9;
            margin-top: 10px;
        }

        .card-footer {
            padding: 20px 25px; border-top: 1px solid #f1f5f9; background: #fafafa;
            display: flex; justify-content: space-between; align-items: center;
        }

        .btn {
            border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;
            transition: background 0.2s; font-size: 0.9rem; width: 100%;
        }
        .btn-join { background: var(--primary); color: white; }
        .btn-join:hover { background: #4338ca; }
        
        .btn-leave { background: white; border: 1px solid #fee2e2; color: #ef4444; }
        .btn-leave:hover { background: #fee2e2; }
        .btn-disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: center; font-weight: 500; }
        .success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .my-event-card { border-left: 5px solid var(--primary); }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">CONVERGE.</a>
            <div class="nav-links">
                <a href="profile.php">My Profile</a>
                <a href="logout.php" class="logout">Log Out</a>
            </div>
        </div>
    </nav>

    <div class="main-container animate-fade-up">

        <?php if ($msg = flash('success')): ?> <div class="alert success"><?= $msg ?></div> <?php endif; ?>
        <?php if ($msg = flash('error')): ?> <div class="alert error"><?= $msg ?></div> <?php endif; ?>

        <div class="welcome-banner">
            <div class="banner-circle"></div>
            <h1>Hello, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>! 👋</h1>
            <p>Welcome back to your dashboard. Here is what's happening in the community.</p>
        </div>

        <div class="section-header">
            <div class="section-title">📅 Your Schedule <span class="count-badge"><?= count($myList) ?></span></div>
        </div>

        <?php if (empty($myList)): ?>
            <div style="text-align:center; padding: 40px; background: white; border-radius: 16px; border: 2px dashed #e2e8f0; color: #94a3b8;">
                <p>You haven't joined any events yet.</p>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($myList as $e): ?>
                <div class="card my-event-card">
                    <div class="card-body">
                        <span class="date-badge date-purple"><?= date('M d • h:i A', strtotime($e['event_date'])) ?></span>
                        
                        <h3>
                            <a href="event_details.php?id=<?= $e['id'] ?>" class="event-title-link">
                                <?= htmlspecialchars($e['title']) ?>
                            </a>
                        </h3>
                        
                        <div class="location">📍 <?= htmlspecialchars($e['location']) ?></div>
                        
                        <?php if(!empty($e['description'])): ?>
                            <div class="description"><?= htmlspecialchars($e['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <form method="post" style="width:100%">
                            <input type="hidden" name="event_id" value="<?= $e['id'] ?>">
                            <input type="hidden" name="action" value="leave">
                            <button class="btn btn-leave" onclick="return confirm('Are you sure you want to cancel?')">Cancel Registration</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="section-header">
            <div class="section-title">🚀 Explore Events <span class="count-badge"><?= count($availList) ?></span></div>
        </div>

        <?php if (empty($availList)): ?>
            <div style="text-align:center; padding: 40px; color: #94a3b8;">No new upcoming events found. Check back later!</div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($availList as $e): ?>
                    <?php $isFull = ($e['current_count'] >= $e['max_capacity']); ?>
                    <div class="card">
                        <div class="card-body">
                            <span class="date-badge date-blue"><?= date('M d • h:i A', strtotime($e['event_date'])) ?></span>
                            
                            <h3>
                                <a href="event_details.php?id=<?= $e['id'] ?>" class="event-title-link">
                                    <?= htmlspecialchars($e['title']) ?>
                                </a>
                            </h3>

                            <div class="location">📍 <?= htmlspecialchars($e['location']) ?></div>
                            
                            <?php if(!empty($e['description'])): ?>
                                <div class="description"><?= htmlspecialchars($e['description']) ?></div>
                            <?php endif; ?>

                            <div style="font-size: 0.85rem; color: #64748b; margin-top:15px; font-weight:600;">
                                Capacity: <?= $e['current_count'] ?> / <?= $e['max_capacity'] ?> Spots
                            </div>
                        </div>
                        <div class="card-footer">
                            <?php if (!$isFull): ?>
                                <form method="post" style="width:100%">
                                    <input type="hidden" name="event_id" value="<?= $e['id'] ?>">
                                    <input type="hidden" name="action" value="join">
                                    <button class="btn btn-join">Join Event</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-disabled" disabled>Event Full</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>