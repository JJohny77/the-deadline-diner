<?php
include "includes/auth.php";
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

$tableId = $order['table_id'];

// Only served orders can be refunded
if ($order['status'] !== 'served') {
    die("Only served orders can be refunded.");
}

// Calculate total amount
$totalQ = mysqli_query($conn, "
    SELECT SUM(oi.quantity * m.price) AS total
    FROM order_items oi
    JOIN menu m ON m.id = oi.menu_id
    WHERE oi.order_id = $orderId
");
$total = mysqli_fetch_assoc($totalQ)['total'] ?? 0;

// Insert refund log
mysqli_query($conn, "
    INSERT INTO refund_logs (order_id, amount)
    VALUES ($orderId, $total)
");

// Mark order as refunded
mysqli_query($conn, "
    UPDATE orders SET status='refunded'
    WHERE id = $orderId
");

// Free the table
mysqli_query($conn, "
    UPDATE tables
    SET status='free'
    WHERE id = $tableId
");

// Redirect back to table with success message
header("Location: table.php?id=$tableId&refunded=1");
exit;
?>
