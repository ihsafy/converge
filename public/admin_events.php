<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$pdo = getDB();

// Only Admins/Managers can access
if (!in_array($user['role'], ['super_admin', 'event_manager', 'member_manager'])) {
    flash('error', 'Access Denied');
    redirect('admin_dashboard.php');
}

// --- 1. HANDLE ADD EVENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);
    $dateInput = $_POST['event_date'];
    $cap   = (int)$_POST['max_capacity'];
    $loc   = trim($_POST['location']);

    // --- NEW: Handle Image Upload ---
    $imagePath = null;
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed)) {
            // Create unique filename
            $newFilename = 'event_' . uniqid() . '.' . $ext;
            $targetPath = __DIR__ . '/uploads/' . $newFilename;
            
            // Move file
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $targetPath)) {
                $imagePath = $newFilename;
            }
        }
    }

    if ($title && $dateInput && $cap > 0) {
        try {
            // --- UPDATED SQL: Added image_path ---
            $sql = "INSERT INTO events (title, description, event_date, location, max_capacity, image_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $desc, $dateInput, $loc, $cap, $imagePath, $user['id']]);
            flash('success', 'Event Created Successfully!');
        } catch (PDOException $e) {
            flash('error', "Database Error: " . $e->getMessage());
        }
        redirect('admin_events.php');
    } else {
        flash('error', 'Please fill in all required fields.');
    }
}

// --- 2. HANDLE DELETE EVENT ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Optional: Delete the image file from folder if you want to clean up
        // $stmt = $pdo->prepare("SELECT image_path FROM events WHERE id = ?");
        // $stmt->execute([$id]);
        // $img = $stmt->fetchColumn();
        // if($img && file_exists(__DIR__.'/uploads/'.$img)) { unlink(__DIR__.'/uploads/'.$img); }

        $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
        flash('success', 'Event deleted.');
    } catch (Exception $e) {
        flash('error', 'Could not delete event.');
    }
    redirect('admin_events.php');
}

// --- 3. FETCH EVENTS ---
// --- UPDATED SQL: Added e.image_path ---
$sql = "
    SELECT e.id, e.title, e.location, e.max_capacity, e.image_path, e.event_date as final_date,
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registered_count 
    FROM events e 
    ORDER BY e.event_date DESC
";

try {
    $events = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Events - CONVERGE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* INLINE STYLES FOR LAYOUT */
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; }
        
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title { margin: 5px 0 0; color: #0f172a; font-size: 1.8rem; font-weight: 700; }
        .back-link { text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.9rem; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 500px; max-width: 90%; position: relative; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); max-height: 90vh; overflow-y: auto; }
        .close-btn { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: #64748b; }

        /* Buttons */
        .btn-create { background: #2563eb; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-create:hover { background: #1d4ed8; }
        
        /* Grid Layout */
        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        
        /* Event Card Class */
        .event-card { 
            background: white; border-radius: 16px; overflow: hidden; /* Important for image */
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
            border: 1px solid #f1f5f9; 
            display: flex; flex-direction: column; justify-content: space-between;
        }

        /* NEW: Admin Card Image */
        .admin-card-img { width: 100%; height: 150px; object-fit: cover; border-bottom: 1px solid #f1f5f9; }
        .admin-card-placeholder { width: 100%; height: 150px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-weight: 600; }

        .card-body { padding: 20px; }
        
        .event-date-badge { 
            color: #2563eb; font-weight: 700; font-size: 0.8rem; 
            text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; 
        }
        .event-title { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin: 0 0 8px 0; line-height: 1.3; }
        .event-location { color: #64748b; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; margin-bottom: 20px; }
        
        /* Progress Bar Classes */
        .progress-wrapper { background: #e2e8f0; border-radius: 99px; height: 8px; width: 100%; overflow: hidden; margin-bottom: 8px; }
        .progress-fill { height: 100%; background: #10b981; border-radius: 99px; transition: width 0.4s ease; }
        .progress-fill.is-full { background: #ef4444; }
        
        .stats-row { display: flex; justify-content: space-between; font-size: 0.85rem; color: #475569; font-weight: 600; margin-bottom: 20px; }

        /* Action Buttons */
        .card-footer { border-top: 1px solid #f1f5f9; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; background: #fafafa; }
        .btn-text-blue { color: #2563eb; text-decoration: none; font-weight: 600; font-size: 0.95rem; }
        .btn-text-red { color: #dc2626; text-decoration: none; font-weight: 600; font-size: 0.95rem; }
        .btn-text-blue:hover, .btn-text-red:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container animate-fade-up">
    
    <div class="header-flex">
        <div>
            <a href="admin_dashboard.php" class="back-link">&larr; Back to Dashboard</a>
            <h1 class="page-title">Event Management</h1>
        </div>
        <button onclick="document.getElementById('createModal').style.display='flex'" class="btn-create">
            + Create New Event
        </button>
    </div>

    <?php if ($msg = flash('success')): ?> <div class="success"><?= $msg ?></div> <?php endif; ?>
    <?php if ($msg = flash('error')): ?> <div class="error"><?= $msg ?></div> <?php endif; ?>

    <div class="events-grid">
        <?php foreach ($events as $e): ?>
            <?php 
                // Logic
                $count = $e['registered_count'];
                $cap = $e['max_capacity'];
                $percent = ($cap > 0) ? ($count / $cap) * 100 : 0;
                $isFull = ($count >= $cap);
            ?>
            
            <div class="event-card">
                <?php if (!empty($e['image_path']) && file_exists('uploads/' . $e['image_path'])): ?>
                    <img src="uploads/<?= htmlspecialchars($e['image_path']) ?>" class="admin-card-img" alt="Cover">
                <?php else: ?>
                    <div class="admin-card-placeholder">No Image</div>
                <?php endif; ?>

                <div class="card-body">
                    <div class="event-date-badge">
                        <?= date('M d, Y • h:i A', strtotime($e['final_date'])) ?>
                    </div>
                    <h3 class="event-title"><?= htmlspecialchars($e['title']) ?></h3>
                    <div class="event-location">
                        <span>📍</span> <?= htmlspecialchars($e['location']) ?>
                    </div>

                    <div class="progress-wrapper">
                        <div class="progress-fill <?= $isFull ? 'is-full' : '' ?>" style="width: <?= $percent ?>%;"></div>
                    </div>
                    <div class="stats-row">
                        <span><?= $count ?> / <?= $cap ?> Registered</span>
                        <span><?= round($percent) ?>%</span>
                    </div>
                </div>

                <div class="card-footer">
                    <a href="admin_event_view.php?id=<?= $e['id'] ?>" class="btn-text-blue">View Attendees &rarr;</a>
                    <a href="?delete=<?= $e['id'] ?>" onclick="return confirm('Are you sure you want to delete this event? This will also remove all registrations.')" class="btn-text-red">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if(empty($events)): ?>
        <div style="text-align:center; padding:50px; color:#94a3b8;">
            <p>No events found. Create one to get started!</p>
        </div>
    <?php endif; ?>

</div>

<div id="createModal" class="modal">
    <div class="modal-content animate-fade-up">
        <span class="close-btn" onclick="document.getElementById('createModal').style.display='none'">&times;</span>
        <h2 style="margin-top:0; color:#1e293b;">Create New Event</h2>
        
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="add_event" value="1">
            
            <label style="font-weight:600; display:block; margin-bottom:5px;">Event Title</label>
            <input type="text" name="title" required style="width:100%; padding:12px; margin-bottom:15px; border:1px solid #cbd5e1; border-radius:8px;">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="font-weight:600; display:block; margin-bottom:5px;">Date & Time</label>
                    <input type="datetime-local" name="event_date" required style="width:100%; padding:12px; margin-bottom:15px; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-weight:600; display:block; margin-bottom:5px;">Capacity</label>
                    <input type="number" name="max_capacity" value="50" required style="width:100%; padding:12px; margin-bottom:15px; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
            </div>

            <label style="font-weight:600; display:block; margin-bottom:5px;">Cover Image (Optional)</label>
            <input type="file" name="event_image" accept="image/*" style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #cbd5e1; border-radius:8px; background:#f8fafc;">

            <label style="font-weight:600; display:block; margin-bottom:5px;">Location</label>
            <input type="text" name="location" required style="width:100%; padding:12px; margin-bottom:15px; border:1px solid #cbd5e1; border-radius:8px;">

            <label style="font-weight:600; display:block; margin-bottom:5px;">Description</label>
            <textarea name="description" rows="3" style="width:100%; padding:12px; margin-bottom:20px; border:1px solid #cbd5e1; border-radius:8px; font-family:inherit;"></textarea>

            <button type="submit" class="btn-create" style="width: 100%; justify-content: center; display: flex;">Publish Event</button>
        </form>
    </div>
</div>

<script>
    window.onclick = function(e) {
        if (e.target == document.getElementById('createModal')) {
            document.getElementById('createModal').style.display = "none";
        }
    }
</script>
</body>
</html>