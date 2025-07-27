<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// If role not set in session, redirect to login
if (!isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

// Handle both single role and array of allowed roles
if (
    (is_array($required_role) && !in_array($_SESSION['role'], $required_role)) ||
    (!is_array($required_role) && $_SESSION['role'] !== $required_role)
) {
    header("Location: ../login.php");
    exit;
}
