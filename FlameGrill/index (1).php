<?php
// ============================================================
// index.php — Main Entry Point
// Redirects to dashboard if logged in, otherwise to login
// ============================================================

require_once 'db_connection.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>