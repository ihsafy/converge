<?php
// Start the session strictly once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'converge_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// 1. Connect to Database
function getDB() {
    static $pdo;
    if (!$pdo) {
        try {
            $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

// 2. Log User In
function login_user($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
}

// 3. Log User Out
function logout_user() {
    unset($_SESSION['user_id']);
    unset($_SESSION['role']);
    unset($_SESSION['full_name']);
    session_destroy();
}

// 4. Check if Logged In
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// 5. Force Login (Security Gate)
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

// 6. Get Current User Data
function current_user() {
    if (!is_logged_in()) return null;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// 7. Flash Messages
function flash($name, $message = '') {
    if (!empty($message)) {
        $_SESSION[$name] = $message;
    } elseif (isset($_SESSION[$name])) {
        $msg = $_SESSION[$name];
        unset($_SESSION[$name]);
        return $msg;
    }
    return false;
}

// 8. Redirect Helper
function redirect($path) {
    header("Location: $path");
    exit;
}

// NOTICE: No closing tag here. This is intentional to prevent API errors.