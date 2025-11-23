<?php
include "includes/auth.php";
include "includes/header.php";

include "includes/db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid order.");
}

$orderId = intval($_GET['id']);

// Fetch order
$q = mysqli_query($conn, "SELECT * FROM orders WHERE id=$orderId LIMIT 1");
if (mysqli_num_rows($q) === 0) {
    die("Order not found.");
}

$order = mysqli_fetch_assoc($q);

// Only pending orders can be cancelled
if ($order['status'] !== 'pending') {
    die("This order cannot be cancelled.");
}

// Mark as cancelled
mysqli_query($conn, "
    UPDATE orders SET status='cancelled' WHERE id=$orderId
");

// FREE THE TABLE
mysqli_query($conn, "
    UPDATE tables
    SET status='free'
    WHERE id = {$order['table_id']}
");

// OPTIONAL: delete items
// mysqli_query($conn, "DELETE FROM order_items WHERE order_id=$orderId");

header("Location: tables.php?cancelled=1");
exit;
?>
