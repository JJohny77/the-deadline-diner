<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

if (!isset($_GET['id']) || !isset($_GET['order'])) {
    header("Location: tables.php");
    exit;
}

$itemId = intval($_GET['id']);
$orderId = intval($_GET['order']);

// Delete item
mysqli_query($conn, "DELETE FROM order_items WHERE id = $itemId");

header("Location: add_items.php?order=$orderId");
exit;
?>
