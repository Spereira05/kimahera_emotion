<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

// Check if user is logged in before logging out
if (isLoggedIn()) {
    // Log the logout activity if user info is available
    if (isset($_SESSION['usuario_id'])) {
        logActivity($_SESSION['usuario_id'], 'user_logout', 'User logged out');
    }

    // Clear all session variables
    $_SESSION = array();

    // Destroy the session cookie if it exists
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Redirect to login page with success message
    session_start(); // Start new session for message
    $_SESSION['sucesso'] = 'Sessão terminada com sucesso.';
    header('Location: ../login.php');
    exit();
} else {
    // User wasn't logged in, redirect to login
    header('Location: ../login.php');
    exit();
}
?>
