<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();
$pdo = getDB();
$user = current_user();

/* ONLY EVENT MANAGER OR SUPER ADMIN CAN ASSIGN ROLES */
if (!in_array($user['role'], ['super_admin', 'event_manager'])) {
    flash('error', 'Access denied.');
    redirect('admin_dashboard.php');
}

/* VALIDATE EVENT ID */
$eventId = $_GET['id'] ?? 0;
if (!$eventId) {
    flash('error', 'Invalid Event');
    redirect('admin_events.php');
}

/* FETCH EVENT */
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    flash('error', 'Event not found');
    redirect('admin_events.php');
}

/* HANDLE ROLE ASSIGNMENT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_role'])) {

    $memberId = (int) $_POST['user_id'];
    $role     = $_POST['role'];

    $allowed = ['anchor','volunteer','crafting'];
    if (!in_array($role, $allowed)) {
        flash('error', 'Invalid role selected');
        redirect("admin_event_view.php?id=$eventId");
    }

    $stmt = $pdo->prepare("
        INSERT INTO event_roles (event_id, user_id, role, assigned_by)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE role = VALUES(role)
    ");
    $stmt->execute([$eventId, $memberId, $role, $user['id']]);

    flash('success', 'Role assigned successfully');
    redirect("admin_event_view.php?id=$eventId");
}

/* FETCH ATTENDEES WITH ROLES */
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.full_name,
        u.email,
        er.created_at,
        r.role
    FROM event_registrations er
    JOIN users u ON er.user_id = u.id
    LEFT JOIN event_roles r 
        ON r.user_id = u.id AND r.event_id = er.event_id
    WHERE er.event_id = ?
");
$stmt->execute([$eventId]);
$attendees = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendees - <?= htmlspecialchars($event['title']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">

<a href="admin_events.php">&larr; Back to Events</a>

<h2>Attendees for: <?= htmlspecialchars($event['title']) ?></h2>

<?php if ($msg = flash('success')): ?>
<div class="success"><?= $msg ?></div>
<?php endif; ?>

<?php if ($msg = flash('error')): ?>
<div class="error"><?= $msg ?></div>
<?php endif; ?>

<table style="width:100%; margin-top:20px;">
<tr>
    <th>Name</th>
    <th>Email</th>
    <th>Registered At</th>
    <th>Assign Role</th>
</tr>

<?php if (!$attendees): ?>
<tr>
    <td colspan="4">No members registered yet.</td>
</tr>
<?php endif; ?>

<?php foreach ($attendees as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['full_name']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= htmlspecialchars($row['created_at']) ?></td>
    <td>
        <form method="post">
            <input type="hidden" name="assign_role" value="1">
            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
            <select name="role" onchange="this.form.submit()">
                <option value="">Select Role</option>
                <option value="anchor" <?= $row['role']=='anchor'?'selected':'' ?>>Anchor</option>
                <option value="volunteer" <?= $row['role']=='volunteer'?'selected':'' ?>>Volunteer</option>
                <option value="crafting" <?= $row['role']=='crafting'?'selected':'' ?>>Crafting</option>
            </select>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>

</div>
</body>
</html>
