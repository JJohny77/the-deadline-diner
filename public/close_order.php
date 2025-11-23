<?php
include "includes/db.php";

// -------------------------
// VALIDATE ORDER ID
// -------------------------
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid order ID");
}

$orderId = intval($_GET['id']);

// -------------------------
// FETCH ORDER DETAILS
// -------------------------
$q = mysqli_query($conn, "
    SELECT * FROM orders WHERE id = $orderId LIMIT 1
");

if (mysqli_num_rows($q) === 0) {
    die("Order not found");
}

$order = mysqli_fetch_assoc($q);
$tableId = $order['table_id'];

// -------------------------
// MARK ORDER AS SERVED
// -------------------------
mysqli_query($conn, "
    UPDATE orders 
    SET status = 'served'
    WHERE id = $orderId
");

// -------------------------
// FREE THE TABLE
// -------------------------
mysqli_query($conn, "
    UPDATE tables
    SET status = 'free'
    WHERE id = $tableId
");

// -------------------------
// REDIRECT BACK TO TABLE PAGE
// -------------------------
header("Location: table.php?id=$tableId&closed=1");
exit;
?>
