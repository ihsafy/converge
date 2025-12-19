<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

require_login();
$pdo = getDB();
$user = current_user();

// --- FORCE FETCH USER ROLE ---
$roleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$roleStmt->execute([$user['id']]);
$real_role = $roleStmt->fetchColumn(); 

// 1. Validate ID
$eventId = $_GET['id'] ?? 0;
if ($eventId == 0) {
    flash('error', 'Invalid Event ID.');
    redirect('events.php');
}

// 2. Fetch Event Details
$stmt = $pdo->prepare("
    SELECT e.*, u.full_name as organizer 
    FROM events e 
    JOIN users u ON e.created_by = u.id 
    WHERE e.id = ?
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    flash('error', 'Event not found.');
    redirect('events.php');
}

// 3. Check if User is already registered
$regStmt = $pdo->prepare("SELECT id FROM event_registrations WHERE user_id = ? AND event_id = ?");
$regStmt->execute([$user['id'], $eventId]);
$isRegistered = $regStmt->fetch();

// --- DEFINING ROLES FOR LOGIC ---
$is_admin     = ($real_role === 'admin' || $real_role === 'super_admin');
$is_organizer = ($user['id'] == $event['created_by']);

// 4. Handle Join / Cancel Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Security Check: Block Admin AND Organizer from POST action
    if ($is_admin || $is_organizer) {
        flash('error', 'Admins and Organizers are not allowed to join events.');
        redirect("event_register.php?id=$eventId");
    }

    if (isset($_POST['join_event'])) {
        try {
            $ins = $pdo->prepare("INSERT INTO event_registrations (user_id, event_id, status) VALUES (?, ?, 'registered')");
            $ins->execute([$user['id'], $eventId]);
            flash('success', 'You have successfully joined this event!');
        } catch (Exception $e) {
            flash('error', 'Could not register. You might already be joined.');
        }
    } elseif (isset($_POST['leave_event'])) {
        $del = $pdo->prepare("DELETE FROM event_registrations WHERE user_id = ? AND event_id = ?");
        $del->execute([$user['id'], $eventId]);
        flash('success', 'Registration cancelled.');
    }
    header("Location: event_register.php?id=$eventId");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= htmlspecialchars($event['title']) ?> - CONVERGE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .event-banner { width: 100%; height: 300px; object-fit: cover; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #ddd; }
        .event-meta { display: flex; gap: 20px; color: #666; font-size: 0.95rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .meta-item { display: flex; align-items: center; gap: 8px; background: #f9fafb; padding: 8px 15px; border-radius: 50px; }
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 6px; font-weight: bold; font-size: 0.8rem; text-transform: uppercase; }
        .status-upcoming { background: #dcfce7; color: #166534; }
        .status-passed { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
<div class="container animate-fade-up">
    
    <nav>
        <a href="events.php">&larr; Back to Events</a>
        <div style="font-weight: bold; color: var(--primary);">Event Details</div>
    </nav>

    <?php if ($msg = flash('success')): ?> <div class="success"><?= $msg ?></div> <?php endif; ?>
    <?php if ($msg = flash('error')): ?> <div class="error"><?= $msg ?></div> <?php endif; ?>

    <div class="card" style="padding: 0; overflow: hidden;">
        
        <?php if (!empty($event['event_image'])): ?>
            <img src="uploads/<?= htmlspecialchars($event['event_image']) ?>" class="event-banner" style="border-radius: 0; margin: 0;">
        <?php else: ?>
            <div style="height: 150px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); display: flex; align-items: center; justify-content: center; color: #6366f1;">
                (No Image Provided)
            </div>
        <?php endif; ?>

        <div style="padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                <h1 style="margin: 0; font-size: 2rem; color: #111827;"><?= htmlspecialchars($event['title']) ?></h1>
                
                <?php $isPast = strtotime($event['start_time']) < time(); ?>
                <?php if ($isPast): ?>
                    <span class="status-badge status-passed">Event Ended</span>
                <?php else: ?>
                    <span class="status-badge status-upcoming">Upcoming</span>
                <?php endif; ?>
            </div>

            <div class="event-meta">
                <div class="meta-item">📅 <?= date('M d, Y - h:i A', strtotime($event['start_time'])) ?></div>
                <div class="meta-item">📍 <?= htmlspecialchars($event['location'] ?? 'Online') ?></div>
                <div class="meta-item">👤 <?= htmlspecialchars($event['organizer']) ?></div>
            </div>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

            <h3 style="margin-top: 0;">About this Event</h3>
            <p style="line-height: 1.8; color: #4b5563; white-space: pre-wrap;"><?= htmlspecialchars($event['description']) ?></p>

            <div style="margin-top: 3rem; text-align: center;">
                <?php if ($isRegistered): ?>
                    
                    <div style="background: #f0fdf4; padding: 20px; border-radius: 12px; border: 1px solid #bbf7d0;">
                        <h3 style="color: #166534; margin: 0 0 10px 0;">✅ You are going!</h3>
                        <p style="margin-bottom: 20px;">We have reserved your spot.</p>
                        <?php if (!$isPast): ?>
                            <form method="post">
                                <input type="hidden" name="leave_event" value="1">
                                <button type="submit" style="background: white; color: #dc2626; border: 1px solid #dc2626;">Cancel Registration</button>
                            </form>
                        <?php endif; ?>
                    </div>

                <?php elseif (!$isPast): ?>
                    
                    <?php if ($is_admin): ?>
                        <div style="background: #f3f4f6; padding: 15px 30px; border-radius: 8px; display: inline-block; color: #555; border: 1px dashed #ccc;">
                            🔒 <strong>Admin Mode</strong><br>
                            <span style="font-size: 0.9em;">I am the Super Admin. I have full access and control over system settings and user management.</span>
                        </div>

                    <?php elseif ($is_organizer): ?>
                        <div style="background: #f3f4f6; padding: 15px 30px; border-radius: 8px; display: inline-block; color: #555; border: 1px dashed #ccc;">
                            🎓 <strong>Organizer View</strong><br>
                            <span style="font-size: 0.9em;">You created this event.</span>
                        </div>

                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="join_event" value="1">
                            <button type="submit" style="font-size: 1.2rem; padding: 15px 40px;">
                                🎟️ Join Event Now
                            </button>
                        </form>
                    <?php endif; ?>

                <?php else: ?>
                    <button disabled style="background: #ccc; cursor: not-allowed;">Registration Closed</button>
                <?php endif; ?>
                
                </div>

        </div>
    </div>
</div>
</body>
</html>