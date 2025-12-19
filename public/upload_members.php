<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

require_role(['admin']);

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Please select a valid CSV file.');
    } else {
        $tmpName = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($tmpName, 'r');
        if ($handle === false) {
            flash('error', 'Cannot read uploaded file.');
        } else {
            $header = fgetcsv($handle);
            $expected = ['full_name', 'email', 'role'];
            $normalized = array_map('strtolower', $header);

            if ($normalized !== $expected) {
                flash('error', 'Invalid CSV format. Expected headers: full_name,email,role');
            } else {
                $insertCount = 0;
                $pdo->beginTransaction();
                try {
                    while (($row = fgetcsv($handle)) !== false) {
                        [$name, $email, $role] = $row;
                        $name  = trim($name);
                        $email = trim($email);
                        $role  = trim(strtolower($role));
                        if ($name === '' || $email === '' || !in_array($role, ['admin','moderator','member'], true)) {
                            throw new Exception("Invalid data in CSV.");
                        }

                        // random password for imported users
                        $plainPass = bin2hex(random_bytes(4));
                        $hash = password_hash($plainPass, PASSWORD_DEFAULT);

                        $stmt = $pdo->prepare("
                            INSERT INTO users (full_name, email, password_hash, role)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$name, $email, $hash, $role]);
                        $insertCount++;

                        // send email
                        send_app_mail(
                            $email,
                            $name,
                            'Your CONVERGE Account',
                            "Hi $name,\n\nYou have been added to the club system.\nEmail: $email\nPassword: $plainPass\n\nPlease log in and change your password."
                        );
                    }
                    $pdo->commit();
                    flash('success', "Successfully imported $insertCount members.");
                } catch (Exception $e) {
                    $pdo->rollBack();
                    flash('error', 'Error in CSV data. Upload rejected.');
                }
            }
            fclose($handle);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Members (CSV) - CONVERGE</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<h2>Upload Members via CSV</h2>

<?php if ($msg = flash('error')): ?>
    <p class="error"><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>
<?php if ($msg = flash('success')): ?>
    <p class="success"><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>Select CSV file (full_name,email,role):</label>
    <input type="file" name="csv_file" accept=".csv" required>
    <button type="submit">Upload</button>
</form>

<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>