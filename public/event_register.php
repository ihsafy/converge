<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

require_login();
$pdo  = getDB();
$user = current_user();

/* FETCH REAL ROLE */
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$real_role = $stmt->fetchColumn();

/* VALIDATE EVENT ID */
$eventId = $_GET['id'] ?? 0;
if (!$eventId) {
    flash('error', 'Invalid Event ID.');
    redirect('events.php');
}

/* FETCH EVENT */
$stmt = $pdo->prepare("
    SELECT e.*, u.full_name AS organizer
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

/* CHECK REGISTRATION */
$stmt = $pdo->prepare("
    SELECT id FROM event_registrations
    WHERE user_id = ? AND event_id = ?
");
$stmt->execute([$user['id'], $eventId]);
$isRegistered = $stmt->fetch();

/* ROLE FLAGS */
$is_admin         = in_array($real_role, ['admin','super_admin']);
$is_event_manager = ($real_role === 'event_manager');
$isPast           = strtotime($event['start_time']) < time();

/* FETCH REGISTERED MEMBERS (FOR EVENT MANAGER) */
$registeredMembers = [];
if ($is_event_manager) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.email, r.role
        FROM event_registrations er
        JOIN users u ON er.user_id = u.id
        LEFT JOIN event_roles r
            ON r.user_id = u.id AND r.event_id = er.event_id
        WHERE er.event_id = ?
    ");
    $stmt->execute([$eventId]);
    $registeredMembers = $stmt->fetchAll();
}

/* HANDLE JOIN / LEAVE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($is_admin || $is_event_manager) {
        flash('error', 'Admins and Event Managers cannot join events.');
        redirect("event_register.php?id=$eventId");
    }

    if (isset($_POST['join_event'])) {
        try {
            $pdo->prepare("
                INSERT INTO event_registrations (user_id, event_id, status)
                VALUES (?, ?, 'registered')
            ")->execute([$user['id'], $eventId]);
            flash('success', 'Successfully registered!');
        } catch (Exception $e) {
            flash('error', 'Already registered.');
        }
    }

    if (isset($_POST['leave_event'])) {
        $pdo->prepare("
            DELETE FROM event_registrations
            WHERE user_id = ? AND event_id = ?
        ")->execute([$user['id'], $eventId]);
        flash('success', 'Registration cancelled.');
    }

    redirect("event_register.php?id=$eventId");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($event['title']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">

<h1><?= htmlspecialchars($event['title']) ?></h1>
<p><?= htmlspecialchars($event['description']) ?></p>

<!-- EVENT MANAGER ROLE ASSIGNMENT -->
<?php if ($is_event_manager && !$isPast): ?>
<hr>
<h3>🎭 Assign Roles (Event Manager)</h3>

<?php if (!$registeredMembers): ?>
<p>No registered members yet.</p>
<?php else: ?>
<table width="100%">
<tr><th>Name</th><th>Email</th><th>Role</th></tr>
<?php foreach ($registeredMembers as $m): ?>
<tr>
<td><?= htmlspecialchars($m['full_name']) ?></td>
<td><?= htmlspecialchars($m['email']) ?></td>
<td>
<form method="post" action="assign_event_role.php">
<input type="hidden" name="event_id" value="<?= $eventId ?>">
<input type="hidden" name="user_id" value="<?= $m['id'] ?>">
<select name="role" onchange="this.form.submit()">
<option value="">-- Select --</option>
<option value="anchor" <?= $m['role']=='anchor'?'selected':'' ?>>Anchor</option>
<option value="volunteer" <?= $m['role']=='volunteer'?'selected':'' ?>>Volunteer</option>
<option value="crafting" <?= $m['role']=='crafting'?'selected':'' ?>>Crafting</option>
</select>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php endif; ?>

<hr>

<!-- MEMBER VIEW -->
<?php if ($isRegistered): ?>
<h3>✅ You are registered</h3>

<?php
$stmt = $pdo->prepare("
    SELECT role FROM event_roles
    WHERE event_id = ? AND user_id = ?
");
$stmt->execute([$eventId, $user['id']]);
$myRole = $stmt->fetchColumn();
?>

<?php if ($myRole): ?>
<p><strong>Your Role:</strong> <?= ucfirst($myRole) ?></p>
<?php else: ?>
<p><em>Your role has not been assigned yet.</em></p>
<?php endif; ?>

<form method="post">
<input type="hidden" name="leave_event">
<button type="submit">Cancel Registration</button>
</form>

<?php elseif (!$isPast && !$is_admin && !$is_event_manager): ?>
<form method="post">
<input type="hidden" name="join_event">
<button type="submit">Join Event</button>
</form>
<?php endif; ?>

</div>
</body>
</html>
