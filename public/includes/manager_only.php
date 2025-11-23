<?php
// includes/manager_only.php

// Χρησιμοποιεί το auth.php για να βεβαιωθεί ότι ο χρήστης είναι logged in
require_once __DIR__ . '/auth.php';

// Επιτρέπουμε ΜΟΝΟ manager role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    http_response_code(403);
    echo "<h2 style='margin:2rem; color:#b71c1c;'>Access denied — Managers only.</h2>";
    exit;
}
