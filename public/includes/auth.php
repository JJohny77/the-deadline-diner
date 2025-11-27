<?php
// Always start session once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if current full-login user is the owner/manager
 */
function isOwner()
{
    return isset($_SESSION['user_id'], $_SESSION['role'])
        && $_SESSION['role'] === 'manager';
}

/**
 * Array with all waiters currently "on shift"
 * (we'll fill this από το Staff page στο επόμενο βήμα)
 */
if (!isset($_SESSION['active_waiters']) || !is_array($_SESSION['active_waiters'])) {
    $_SESSION['active_waiters'] = [];
}

/**
 * Check if specific waiter id is on shift
 */
function isWaiterOnShift($waiterId)
{
    return isset($_SESSION['active_waiters'][$waiterId])
        && $_SESSION['active_waiters'][$waiterId] === true;
}

/**
 * Require OWNER (full login) – for admin-only pages
 * e.g. menu.php, orders.php, διαχείριση προσωπικού κλπ.
 */
function requireOwner()
{
    if (!isOwner()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Require either:
 *  - owner logged in  OR
 *  - at least one waiter on shift
 *
 * Αυτό θα το βάλουμε σε σελίδες όπως tables.php, table.php, add_items.php, new_order.php κ.λπ.
 */
function requireOwnerOrWaiterShift()
{
    // Αν είναι ήδη owner, όλα καλά
    if (isOwner()) {
        return;
    }

    // Αν δεν υπάρχει κανένας σερβιτόρος σε βάρδια → στείλε στο Staff να γίνει Start Shift
    if (empty($_SESSION['active_waiters'])) {
        header("Location: staff.php?need_shift=1");
        exit;
    }
}
