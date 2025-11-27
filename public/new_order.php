<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

// --- Validate table ---
if (!isset($_GET['table']) || !is_numeric($_GET['table'])) {
    die("Invalid table ID");
}

$tableId = intval($_GET['table']);

// --- Create new order ---
$sql = "INSERT INTO orders (table_id, status) VALUES ($tableId, 'pending')";
mysqli_query($conn, $sql);

$newOrderId = mysqli_insert_id($conn);

// --- Assign Table To Waiter ---
mysqli_query($conn, "
    UPDATE tables
    SET assigned_waiter_id = {$_SESSION['user_id']}
    WHERE id = $tableId
");

// --- Redirect to add items page ---
header("Location: add_items.php?order=$newOrderId");
exit;
?>
