<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'moderator']);

$pdo = getDB();
if (isset($_GET['remove'])) {
    $id = (int) $_GET['remove'];
    $stmt = $pdo->prepare("UPDATE users SET status='removed' WHERE id = ? AND role != 'admin'");
    $stmt->execute([$id]);
    flash('success', 'Member removed.');
    redirect('members.php');
}

$stmt = $pdo->query("SELECT id, full_name, email, role, status, created_at FROM users ORDER BY created_at DESC");
$members = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Members - CONVERGE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<h2>Members</h2>

<?php if ($msg = flash('success')): ?>
    <p class="success"><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<table border="1">
    <tr>
        <th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Action</th>
    </tr>
    <?php foreach ($members as $m): ?>
        <tr>
            <td><?= htmlspecialchars($m['full_name']) ?></td>
            <td><?= htmlspecialchars($m['email']) ?></td>
            <td><?= htmlspecialchars($m['role']) ?></td>
            <td><?= htmlspecialchars($m['status']) ?></td>
            <td><?= htmlspecialchars($m['created_at']) ?></td>
            <td>
                <?php if ($m['role'] != 'admin' && $m['status'] != 'removed'): ?>
                    <a href="?remove=<?= $m['id'] ?>" onclick="return confirm('Remove this member?');">Remove</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>