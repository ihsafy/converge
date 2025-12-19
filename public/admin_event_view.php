<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$pdo = getDB();

$event_id = $_GET['id'] ?? 0;

// 1. Get Event Info
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) die("Event not found");

// 2. Get Registered Users
$sql = "
    SELECT u.full_name, u.email, u.unique_id, u.contact_info, r.created_at as reg_date
    FROM event_registrations r
    JOIN users u ON r.user_id = u.id
    WHERE r.event_id = ?
    ORDER BY r.created_at ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$event_id]);
$attendees = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendees - <?= htmlspecialchars($event['title']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:#f3f4f6; padding: 40px;">
    <div class="container" style="background:white; padding:30px; border-radius:12px; max-width:800px;">
        <a href="admin_events.php">&larr; Back to Events</a>
        <h2>Attendees for: <?= htmlspecialchars($event['title']) ?></h2>
        
        <table style="width:100%; border-collapse:collapse; margin-top:20px;">
            <tr style="background:#f8fafc; text-align:left;">
                <th style="padding:10px;">Name</th>
                <th style="padding:10px;">Email</th>
                <th style="padding:10px;">ID</th>
                <th style="padding:10px;">Registered At</th>
            </tr>
            <?php foreach($attendees as $a): ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:10px; font-weight:bold;"><?= htmlspecialchars($a['full_name']) ?></td>
                <td style="padding:10px;"><?= htmlspecialchars($a['email']) ?></td>
                <td style="padding:10px;"><?= htmlspecialchars($a['unique_id']) ?></td>
                <td style="padding:10px; font-size:0.9em; color:#666;"><?= $a['reg_date'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php if(count($attendees) == 0) echo "<p>No one has registered yet.</p>"; ?>
    </div>
</body>
</html>