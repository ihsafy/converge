<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = getDB();
$user = current_user();

$event_id = (int) $_POST['event_id'];
$user_id  = (int) $_POST['user_id'];
$role     = $_POST['role'] ?? '';

$allowed = ['anchor', 'volunteer', 'crafting'];
if (!in_array($role, $allowed)) {
    die('Invalid role');
}

/* VERIFY ORGANIZER */
$stmt = $pdo->prepare("SELECT created_by FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$owner = $stmt->fetchColumn();

if ($owner != $user['id']) {
    die('Unauthorized');
}

/* VERIFY REGISTRATION */
$stmt = $pdo->prepare("
    SELECT id FROM event_registrations 
    WHERE event_id = ? AND user_id = ?
");
$stmt->execute([$event_id, $user_id]);
if (!$stmt->fetch()) {
    die('User not registered');
}

/* INSERT / UPDATE ROLE */
$stmt = $pdo->prepare("
    INSERT INTO event_roles (event_id, user_id, role, assigned_by)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE role = VALUES(role)
");
$stmt->execute([$event_id, $user_id, $role, $user['id']]);

flash('success', 'Role assigned successfully.');
redirect("event_register.php?id=$event_id");
