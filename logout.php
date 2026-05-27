<?php
// ============================================================
// G&G Support Portal — logout.php  (root level)
// Destroys session and redirects to login
// ============================================================

require_once __DIR__ . '/includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();

header('Location: ' . BASE_URL . '/index.php?logout=1');
exit;
