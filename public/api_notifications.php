<?php
// 1. Start "Recording" output immediately (captures any stray spaces)
ob_start();

require_once __DIR__ . '/../includes/auth.php';

// Turn off error printing to screen
ini_set('display_errors', 0);

try {
    if (!is_logged_in()) { 
        // Clear any stray spaces captured so far
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([]); 
        exit; 
    }

    $user = current_user();
    $pdo = getDB();

    // Fetch unread notifications
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. THE MAGIC FIX: Delete everything PHP has tried to print so far (spaces, newlines)
    ob_clean();

    // 3. Now send the clean JSON
    header('Content-Type: application/json');
    echo json_encode($notifs);

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([['message' => 'Error: ' . $e->getMessage()]]);
}
?>