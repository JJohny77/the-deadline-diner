<?php
// includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Αν δεν υπάρχει logged-in user → redirect στο login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
