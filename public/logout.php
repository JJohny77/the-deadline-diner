<?php
// logout.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Καθαρίζουμε τα session variables
$_SESSION = [];

// Καταστρέφουμε το session cookie (προαιρετικά, αλλά καλό)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Καταστροφή session
session_destroy();

// Redirect στο login
header("Location: login.php");
exit;
