<?php
// public/event_details.php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$pdo = getDB();

// Redirect if no ID provided
if (!isset($_GET['id'])) {
    redirect('dashboard.php');
}

$eventId = (int)$_GET['id'];

// --- HANDLE POST ACTIONS (Join/Leave from details page) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    // Check capacity and registration status again
    $check = $pdo->prepare("SELECT count(*) as registered, max_capacity FROM event_registrations r RIGHT JOIN events e ON r.event_id = e.id WHERE e.id = ?");
    $check->execute([$eventId]);
    $data = $check->fetch();

    $isReg = $pdo->prepare("SELECT id FROM event_registrations WHERE user_id = ? AND event_id = ?");
    $isReg->execute([$user['id'], $eventId]);
    $alreadyRegistered = (bool)$isReg->fetch();

    if ($action === 'join') {
        if (!$alreadyRegistered && $data['registered'] < $data['max_capacity']) {
            $stmt = $pdo->prepare("INSERT INTO event_registrations (user_id, event_id) VALUES (?, ?)");
            $stmt->execute([$user['id'], $eventId]);
            flash('success', '🎉 You have joined the event!');
        } else {
            flash('error', '⚠️ Could not join (Full or already registered).');
        }
    } elseif ($action === 'leave') {
        $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE user_id = ? AND event_id = ?");
        $stmt->execute([$user['id'], $eventId]);
        flash('success', 'You have left the event.');
    }
    // Refresh to show updated status
    redirect("event_details.php?id=$eventId");
}

// --- FETCH EVENT DETAILS ---
// We fetch the event info AND the count of currently registered users
$stmt = $pdo->prepare("SELECT e.*, 
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as current_count 
    FROM events e WHERE e.id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

// If event doesn't exist, go back
if (!$event) {
    flash('error', 'Event not found.');
    redirect('dashboard.php');
}

// Check if current user is registered
$stmt = $pdo->prepare("SELECT id FROM event_registrations WHERE user_id = ? AND event_id = ?");
$stmt->execute([$user['id'], $eventId]);
$isRegistered = (bool)$stmt->fetch();

$isFull = ($event['current_count'] >= $event['max_capacity']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= htmlspecialchars($event['title']) ?> - Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #334155; padding-bottom: 50px; margin:0; }
        
        .navbar { background: rgba(255,255,255,0.8); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.3); position: sticky; top: 0; padding: 15px 0; z-index: 50; }
        .nav-container { max-width: 1100px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-weight: 800; font-size: 1.5rem; color: #4f46e5; text-decoration: none; }
        .nav-links a { text-decoration: none; color: #64748b; font-weight: 600; margin-left: 20px; }
        
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        
        /* --- UPDATED DETAIL CARD --- */
        /* Removed padding here so the image can touch the edges */
        .detail-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
        
        /* New Wrapper for text content */
        .card-content { padding: 40px; }

        /* HERO IMAGE STYLES */
        .event-hero { width: 100%; height: 350px; object-fit: cover; display: block; }
        .event-hero-placeholder { width: 100%; height: 200px; background: linear-gradient(135deg, #e0e7ff 0%, #4f46e5 100%); display:flex; align-items:center; justify-content:center; color:white; font-size: 2rem; font-weight: 800; letter-spacing: 2px; }

        .event-header { margin-bottom: 30px; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; }
        .event-title { font-size: 2.2rem; font-weight: 800; color: #1e293b; margin: 0 0 15px 0; line-height: 1.2; }
        
        .meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .meta-item { display: flex; align-items: center; gap: 10px; font-size: 1rem; color: #475569; font-weight: 500; }
        .icon { font-size: 1.2rem; }
        
        .description-box { background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; line-height: 1.8; color: #334155; font-size: 1.05rem; margin-bottom: 30px; }
        
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; }
        
        .btn { padding: 12px 25px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; font-size: 1rem; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }
        .btn-join { background: #4f46e5; color: white; }
        .btn-leave { background: #fee2e2; color: #ef4444; }
        .btn-disabled { background: #cbd5e1; color: #64748b; cursor: not-allowed; }
        
        .btn-back { text-decoration: none; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 5px; }
        .btn-back:hover { color: #1e293b; }

        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; margin-bottom: 15px; }
        .status-open { background: #dcfce7; color: #166534; }
        .status-full { background: #fee2e2; color: #991b1b; }
        .status-registered { background: #f3e8ff; color: #7c3aed; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="dashboard.php" class="logo">CONVERGE.</a>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="events.php">Events</a>
            <a href="logout.php" style="color:#ef4444;">Log Out</a>
        </div>
    </div>
</nav>

<div class="container animate-fade-up">
    <?php if ($msg = flash('success')): ?> 
        <div style="background:#dcfce7; color:#166534; padding:15px; border-radius:10px; margin-bottom:20px; text-align:center; font-weight:500; border:1px solid #bbf7d0;"><?= $msg ?></div> 
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?> 
        <div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:10px; margin-bottom:20px; text-align:center; font-weight:500; border:1px solid #fecaca;"><?= $msg ?></div> 
    <?php endif; ?>

    <div class="detail-card">
        
        <?php if (!empty($event['image_path']) && file_exists('uploads/' . $event['image_path'])): ?>
            <img src="uploads/<?= htmlspecialchars($event['image_path']) ?>" class="event-hero" alt="Event Cover">
        <?php else: ?>
            <div class="event-hero-placeholder">CONVERGE</div>
        <?php endif; ?>
        <div class="card-content">
            <div class="event-header">
                <?php if ($isRegistered): ?>
                    <span class="status-badge status-registered">✅ Registered</span>
                <?php elseif ($isFull): ?>
                    <span class="status-badge status-full">Full Capacity</span>
                <?php else: ?>
                    <span class="status-badge status-open">Registration Open</span>
                <?php endif; ?>
                
                <h1 class="event-title"><?= htmlspecialchars($event['title']) ?></h1>
            </div>

            <div class="meta-grid">
                <div class="meta-item"><span class="icon">📅</span> <?= date('F d, Y', strtotime($event['event_date'])) ?></div>
                <div class="meta-item"><span class="icon">⏰</span> <?= date('h:i A', strtotime($event['event_date'])) ?></div>
                <div class="meta-item"><span class="icon">📍</span> <?= htmlspecialchars($event['location']) ?></div>
                <div class="meta-item"><span class="icon">👥</span> <?= $event['current_count'] ?> / <?= $event['max_capacity'] ?> Registered</div>
            </div>

            <h3>About this Event</h3>
            <div class="description-box">
                <?= nl2br(htmlspecialchars($event['description'])) ?>
            </div>

            <div class="action-bar">
                <a href="events.php" class="btn-back">&larr; Back to Events</a>

                <form method="post">
                    <input type="hidden" name="action" value="<?= $isRegistered ? 'leave' : 'join' ?>">
                    
                    <?php if ($isRegistered): ?>
                        <button class="btn btn-leave" onclick="return confirm('Are you sure you want to cancel your registration?')">Cancel Registration</button>
                    <?php elseif ($isFull): ?>
                        <button type="button" class="btn btn-disabled" disabled>Event is Full</button>
                    <?php else: ?>
                        <button class="btn btn-join">Join This Event</button>
                    <?php endif; ?>
                </form>
            </div>
        </div> </div>
</div>

</body>
</html>