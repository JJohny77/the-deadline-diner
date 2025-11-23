<?php
include "includes/header.php";
include "includes/db.php";

// -------------------------
// VALIDATE table ID
// -------------------------
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<h2>Invalid table selected.</h2>";
    include "includes/footer.php";
    exit;
}

$tableId = intval($_GET['id']);

// -------------------------
// FETCH TABLE DETAILS
// -------------------------
$q = mysqli_query($conn, "SELECT * FROM tables WHERE id = $tableId");

if (mysqli_num_rows($q) === 0) {
    echo "<h2>Table not found.</h2>";
    include "includes/footer.php";
    exit;
}

$table = mysqli_fetch_assoc($q);

// -------------------------
// HANDLE STATUS CHANGE
// -------------------------
if (isset($_POST['change_status'])) {
    $newStatus = mysqli_real_escape_string($conn, $_POST['change_status']);
    $allowed = ['free', 'occupied', 'reserved'];

    if (in_array($newStatus, $allowed)) {
        mysqli_query($conn, "UPDATE tables SET status='$newStatus' WHERE id=$tableId");
        $table['status'] = $newStatus;
    }
}

// -------------------------
// FETCH ACTIVE ORDER ONLY IF IT HAS ITEMS
// -------------------------
$activeOrder = null;
$orderItems = [];

$orderQ = mysqli_query($conn, "
    SELECT o.*
    FROM orders o
    WHERE o.table_id = $tableId
      AND o.status = 'pending'
      AND EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id)
    ORDER BY o.created_at DESC
    LIMIT 1
");

if ($orderQ && mysqli_num_rows($orderQ) > 0) {
    $activeOrder = mysqli_fetch_assoc($orderQ);

    $orderId = $activeOrder['id'];

    $itemsQ = mysqli_query($conn, "
        SELECT oi.*, m.name AS item_name, m.price
        FROM order_items oi
        JOIN menu m ON m.id = oi.menu_id
        WHERE oi.order_id = $orderId
    ");

    $orderItems = mysqli_fetch_all($itemsQ, MYSQLI_ASSOC);

    $activeOrder['items'] = $orderItems;

    // AUTO–SET TABLE TO OCCUPIED IF HAS ACTIVE ORDER
    if ($table['status'] !== 'occupied') {
        mysqli_query($conn, "UPDATE tables SET status='occupied' WHERE id=$tableId");
        $table['status'] = 'occupied';
    }
}
?>

<div class="container my-4">

    <?php if (isset($_GET['closed'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <strong>Order closed successfully!</strong> The table is now free.
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <h1 class="fw-bold mb-4">Table #<?= $table['id'] ?> Details</h1>

    <!-- TABLE CARD -->
    <div class="card p-4 shadow-sm mb-4">
        <h4 class="fw-bold"><?= $table['name'] ?></h4>

        <span class="badge 
            <?= $table['status'] === 'free' ? 'bg-success' : '' ?>
            <?= $table['status'] === 'occupied' ? 'bg-danger' : '' ?>
            <?= $table['status'] === 'reserved' ? 'bg-warning text-dark' : '' ?>
        ">
            <?= ucfirst($table['status']) ?>
        </span>

        <p class="mt-2 text-muted"><?= $table['seats'] ?> seats</p>

        <form method="POST" class="mt-3 d-flex gap-2 flex-wrap">
            <button name="change_status" value="free" class="btn btn-outline-success btn-sm">Mark Free</button>
            <button name="change_status" value="occupied" class="btn btn-outline-danger btn-sm">Mark Occupied</button>
            <button name="change_status" value="reserved" class="btn btn-outline-warning btn-sm">Mark Reserved</button>
        </form>
    </div>

    <!-- ORDER SECTION -->
    <?php if ($activeOrder): ?>
        <div class="card p-4 shadow-sm">
            <h4 class="fw-bold">Active Order #<?= $activeOrder['id'] ?></h4>
            <p><strong>Created:</strong> <?= $activeOrder['created_at'] ?></p>

            <hr>

            <h5>Items</h5>

            <?php if (count($activeOrder['items']) > 0): ?>
                <ul class="list-group mb-3">
                    <?php foreach ($activeOrder['items'] as $item): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?= $item['item_name'] ?> (x<?= $item['quantity'] ?>)
                            <strong><?= number_format($item['price'] * $item['quantity'], 2) ?>€</strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <a href="add_items.php?order=<?= $activeOrder['id'] ?>" class="btn btn-dark mt-3">Add Items</a>
            <a href="close_order.php?id=<?= $activeOrder['id'] ?>" class="btn btn-success mt-3">Close Order</a>
        </div>

    <?php else: ?>

        <div class="card p-4 shadow-sm text-center">
            <h4>No active order</h4>
            <p class="text-muted">No order with items exists for this table.</p>
            <a href="new_order.php?table=<?= $tableId ?>" class="btn btn-dark mt-3">Create New Order</a>
        </div>

    <?php endif; ?>

</div>

<?php include "includes/footer.php"; ?>
