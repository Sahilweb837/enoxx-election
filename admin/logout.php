<?php
require_once 'config.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
    // Remove session from database
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_token = ?");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['session_token']]);
    
    // Log activity
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: index.php');
exit();
?>