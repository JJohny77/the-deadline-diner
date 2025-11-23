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

// --- Redirect to add items page ---
header("Location: add_items.php?order=$newOrderId");
exit;
?>
