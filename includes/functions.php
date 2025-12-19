<?php

function redirect(string $path) {
    header("Location: " . BASE_URL . ltrim($path, '/'));
    exit;
}

function flash(string $key, string $message = null) {
    if ($message === null) {
        if (!empty($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
        return null;
    } else {
        $_SESSION['flash'][$key] = $message;
    }
}