<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

require_login();
$pdo = getDB();
$user = current_user();

// --- 1. SMART AVATAR SYSTEM ---
$stmtUser = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmtUser->execute([$user['id']]);
$freshUser = $stmtUser->fetch();
$dbImage = $freshUser['profile_image'] ?? '';

$hasPhoto = false;
$photoPath = '';
if (!empty($dbImage) && file_exists(__DIR__ . '/uploads/' . $dbImage)) {
    $hasPhoto = true;
    $photoPath = 'uploads/' . $dbImage;
}
$initial = strtoupper(substr($user['full_name'], 0, 1));

// --- 2. HANDLE CREATE EVENT (Admins/Mods) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (in_array($user['role'], ['admin', 'moderator'])) {
        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $loc      = trim($_POST['location'] ?? '');
        $date     = $_POST['event_date'] ?? '';
        $capacity = (int)($_POST['max_capacity'] ?? 0);

        // --- NEW: Handle Image Upload ---
        $imagePath = null;
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $newFilename = 'event_' . uniqid() . '.' . $ext;
                $targetPath = __DIR__ . '/uploads/' . $newFilename;
                if (move_uploaded_file($_FILES['event_image']['tmp_name'], $targetPath)) {
                    $imagePath = $newFilename;
                }
            }
        }

        if ($title && $date && $capacity > 0) {
            // --- UPDATED: Insert query now includes image_path ---
            $stmt = $pdo->prepare("INSERT INTO events (title, description, location, event_date, max_capacity, image_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $desc, $loc, $date, $capacity, $imagePath, $user['id']]);
            
            flash('success', 'Event created successfully!');
            header("Location: events.php");
            exit;
        } else {
            flash('error', 'Title, Date, and Capacity are required.');
        }
    }
}

// --- 3. FETCH EVENTS ---
$stmt = $pdo->query("SELECT * FROM events WHERE event_date >= NOW() ORDER BY event_date ASC");
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - CONVERGE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary: #4f46e5;
            --bg-color: #f8fafc;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-color); color: #334155; margin: 0; padding-bottom: 50px; }

        /* --- BACKGROUND BLOBS --- */
        .blob { position: absolute; filter: blur(80px); z-index: -1; opacity: 0.6; animation: float 10s infinite alternate; }
        .blob-1 { top: -100px; left: -100px; width: 500px; height: 500px; background: #e0e7ff; border-radius: 40% 60% 70% 30%; }
        .blob-2 { top: 200px; right: -100px; width: 400px; height: 400px; background: #f3e8ff; border-radius: 60% 40% 30% 70%; animation-delay: -5s; }
        @keyframes float { 0% { transform: translate(0, 0); } 100% { transform: translate(20px, 40px); } }

        /* --- NAVBAR --- */
        .navbar { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.3); position: sticky; top: 0; z-index: 50; padding: 15px 0; }
        .nav-container { max-width: 1100px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-weight: 800; font-size: 1.5rem; color: var(--primary); text-decoration: none; }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.95rem; transition: color 0.2s; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary); }
        .logout { color: #ef4444 !important; }

        /* --- AVATAR --- */
        .avatar-container { width: 40px; height: 40px; border-radius: 50%; overflow: hidden; border: 2px solid #e0e7ff; background: white; display:flex; align-items:center; justify-content:center; }
        .avatar-img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-circle { width: 100%; height: 100%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; }

        /* --- LAYOUT --- */
        .main-container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h2 { font-size: 1.8rem; font-weight: 800; margin: 0; color: #1e293b; }
        .page-title p { color: #64748b; margin: 5px 0 0; }

        .toggle-btn { background: var(--primary); color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); transition: transform 0.2s; }
        .toggle-btn:hover { transform: translateY(-2px); background: #4338ca; }

        /* --- FORM --- */
        .create-card { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); margin-bottom: 40px; border: 1px solid #e2e8f0; display: none; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 0.9rem; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; box-sizing: border-box; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--primary); ring: 2px solid #e0e7ff; }
        .btn-submit { background: #10b981; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 10px; }

        /* --- EVENTS GRID --- */
        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        .event-card { background: white; border-radius: 16px; overflow: hidden; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: transform 0.2s; display: flex; flex-direction: column; }
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.1); }
        
        /* NEW: Event Image Styling */
        .event-img { width: 100%; height: 180px; object-fit: cover; border-bottom: 1px solid #f1f5f9; }
        .no-img-placeholder { width: 100%; height: 180px; background: linear-gradient(135deg, #e0e7ff 0%, #f3e8ff 100%); display:flex; align-items:center; justify-content:center; color:#a5b4fc; font-weight:700; letter-spacing: 1px; }

        .card-body { padding: 25px; flex-grow: 1; }
        .date-badge { display: inline-block; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; background: #eff6ff; color: #2563eb; margin-bottom: 15px; }
        
        .event-card h3 { margin: 0 0 10px; font-size: 1.25rem; color: #1e293b; }
        .location { color: #64748b; font-size: 0.95rem; margin-bottom: 15px; display: flex; align-items: center; gap: 5px; }
        
        .event-desc { color: #475569; font-size: 0.95rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 15px; }

        .card-footer { padding: 20px 25px; border-top: 1px solid #f1f5f9; background: #fafafa; text-align: right; }
        .link-details { color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .link-details:hover { text-decoration: underline; }

        /* Utilities */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">CONVERGE.</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="events.php" class="active">Events</a>
                <?php if (in_array($user['role'], ['admin', 'moderator'])): ?>
                    <a href="members.php">Members</a>
                <?php endif; ?>
                <a href="logout.php" class="logout">Log Out</a>
                
                <a href="profile.php" title="My Profile">
                    <div class="avatar-container">
                        <?php if ($hasPhoto): ?>
                            <img src="<?= htmlspecialchars($photoPath) ?>" alt="Profile" class="avatar-img"
                                 onerror="this.style.display='none'; document.getElementById('backup-avatar').style.display='flex';">
                            <div id="backup-avatar" class="avatar-circle" style="display:none;"><?= htmlspecialchars($initial) ?></div>
                        <?php else: ?>
                            <div class="avatar-circle"><?= htmlspecialchars($initial) ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </div>
    </nav>

    <div class="main-container animate-fade-up">

        <?php if ($msg = flash('success')): ?> <div class="alert success"><?= $msg ?></div> <?php endif; ?>
        <?php if ($msg = flash('error')): ?> <div class="alert error"><?= $msg ?></div> <?php endif; ?>

        <div class="page-header">
            <div class="page-title">
                <h2>All Events</h2>
                <p>Browse upcoming gatherings and workshops.</p>
            </div>
            <?php if (in_array($user['role'], ['admin', 'moderator'])): ?>
                <button class="toggle-btn" onclick="let f=document.getElementById('createEventForm'); f.style.display = (f.style.display==='block'?'none':'block');">
                    + Create New Event
                </button>
            <?php endif; ?>
        </div>

        <?php if (in_array($user['role'], ['admin', 'moderator'])): ?>
            <div id="createEventForm" class="create-card">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <h3 style="margin:0;">Create New Event</h3>
                    <span onclick="document.getElementById('createEventForm').style.display='none'" style="cursor:pointer; font-size: 1.5rem; color:#94a3b8;">&times;</span>
                </div>
                
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Event Title</label>
                        <input type="text" name="title" required placeholder="e.g. Summer Coding Bootcamp">
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Date & Time</label>
                            <input type="datetime-local" name="event_date" required>
                        </div>
                        <div class="form-group">
                            <label>Max Capacity</label>
                            <input type="number" name="max_capacity" min="1" value="50" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Cover Image (Optional)</label>
                        <input type="file" name="event_image" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="e.g. Conference Room A or Online Link">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="What is this event about?"></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Publish Event</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="events-grid">
            <?php if (empty($events)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #94a3b8;">
                    No upcoming events found.
                </div>
            <?php else: ?>
                <?php foreach ($events as $e): ?>
                    <div class="event-card">
                        
                        <?php if (!empty($e['image_path'])): ?>
                            <img src="uploads/<?= htmlspecialchars($e['image_path']) ?>" class="event-img" alt="Event Cover">
                        <?php else: ?>
                            <div class="no-img-placeholder">CONVERGE</div>
                        <?php endif; ?>

                        <div class="card-body">
                            <span class="date-badge"><?= date('M d • h:i A', strtotime($e['event_date'])) ?></span>
                            <h3><?= htmlspecialchars($e['title']) ?></h3>
                            <div class="location">📍 <?= htmlspecialchars($e['location'] ?: 'Online') ?></div>
                            <div class="event-desc"><?= htmlspecialchars($e['description']) ?></div>
                            
                            <div style="font-size: 0.85rem; color: #64748b; font-weight: 500;">
                                Max Capacity: <?= $e['max_capacity'] ?> people
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="event_details.php?id=<?= $e['id'] ?>" class="link-details">View Details &rarr;</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>