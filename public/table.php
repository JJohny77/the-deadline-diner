<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

// ===============================
// VALIDATE TABLE ID
// ===============================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<h2>Invalid table selected.</h2>";
    include "includes/footer.php";
    exit;
}

$tableId = (int)$_GET['id'];

// ===============================
// FETCH TABLE + ASSIGNED WAITER
// ===============================
$q = mysqli_query($conn, "
    SELECT t.*, w.name AS assigned_waiter_name
    FROM tables t
    LEFT JOIN users w ON w.id = t.assigned_waiter_id
    WHERE t.id = $tableId
");

if (!$q || mysqli_num_rows($q) === 0) {
    echo "<h2>Table not found.</h2>";
    include "includes/footer.php";
    exit;
}

$table = mysqli_fetch_assoc($q);

// ===============================
// HANDLE STATUS CHANGE
// ===============================
if (isset($_POST['change_status'])) {
    $newStatus = mysqli_real_escape_string($conn, $_POST['change_status']);
    $allowed   = ['free', 'occupied', 'reserved'];

    if (in_array($newStatus, $allowed, true)) {
        mysqli_query($conn, "UPDATE tables SET status='$newStatus' WHERE id=$tableId");
        $table['status'] = $newStatus;
    }
}

// ===============================
// FETCH LAST ORDER
// ===============================
$lastOrderQ = mysqli_query($conn, "
    SELECT *
    FROM orders
    WHERE table_id = $tableId
    ORDER BY created_at DESC
    LIMIT 1
");

$lastOrder      = null;
$lastOrderItems = [];

if ($lastOrderQ && mysqli_num_rows($lastOrderQ) > 0) {
    $lastOrder = mysqli_fetch_assoc($lastOrderQ);

    $itemsQ = mysqli_query($conn, "
        SELECT oi.*, m.name AS item_name, m.price
        FROM order_items oi
        JOIN menu m ON m.id = oi.menu_id
        WHERE oi.order_id = {$lastOrder['id']}
    ");

    if ($itemsQ) {
        $lastOrderItems = mysqli_fetch_all($itemsQ, MYSQLI_ASSOC);
    }
}

// ===============================
// DETERMINE ACTIVE ORDER
// ===============================
$activeOrder = null;

if ($lastOrder && $lastOrder['status'] === "pending" && count($lastOrderItems) > 0) {
    $activeOrder = $lastOrder;
    $activeOrder['items'] = $lastOrderItems;

    // Auto-occupy table
    if ($table['status'] !== "occupied") {
        mysqli_query($conn, "UPDATE tables SET status='occupied' WHERE id=$tableId");
        $table['status'] = "occupied";
    }
}
?>

<div class="container my-4">

    <!-- SUCCESS MESSAGES -->
    <?php if (isset($_GET['closed'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <strong>Order closed successfully!</strong> The table is now free.
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['refunded'])): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <strong>Order refunded successfully!</strong>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <h1 class="fw-bold mb-4">Table #<?= $table['id'] ?> Details</h1>

    <!-- TABLE CARD -->
    <div class="card p-4 shadow-sm mb-4">
        <h4 class="fw-bold"><?= htmlspecialchars($table['name']) ?></h4>

        <span class="badge
            <?= $table['status'] === 'free'      ? 'bg-success'               : '' ?>
            <?= $table['status'] === 'occupied'  ? 'bg-danger'                : '' ?>
            <?= $table['status'] === 'reserved'  ? 'bg-warning text-dark'     : '' ?>
        ">
            <?= ucfirst($table['status']) ?>
        </span>

        <p class="mt-2 text-muted"><?= (int)$table['seats'] ?> seats</p>

        <?php if ($table['assigned_waiter_id']): ?>
            <p class="text-muted small">
                Assigned to:
                <strong><?= htmlspecialchars($table['assigned_waiter_name']) ?></strong>
            </p>
        <?php endif; ?>

        <form method="POST" class="mt-3 d-flex gap-2 flex-wrap">
            <button name="change_status" value="free"      class="btn btn-outline-success btn-sm">Mark Free</button>
            <button name="change_status" value="occupied"  class="btn btn-outline-danger  btn-sm">Mark Occupied</button>
            <button name="change_status" value="reserved"  class="btn btn-outline-warning btn-sm">Mark Reserved</button>
        </form>
    </div>

    <!-- ACTIVE ORDER -->
    <?php if ($activeOrder): ?>

        <div class="card p-4 shadow-sm">
            <h4 class="fw-bold">
                Active Order #<?= $activeOrder['id'] ?>
                <?php if ($table['assigned_waiter_name']): ?>
                    <small class="text-muted">(<?= htmlspecialchars($table['assigned_waiter_name']) ?>)</small>
                <?php endif; ?>
            </h4>

            <p><strong>Created:</strong> <?= $activeOrder['created_at'] ?></p>

            <hr>
            <h5>Items</h5>

            <ul class="list-group mb-3">
                <?php foreach ($activeOrder['items'] as $item): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <?= htmlspecialchars($item['item_name']) ?> (x<?= (int)$item['quantity'] ?>)
                        <strong><?= number_format($item['price'] * $item['quantity'], 2) ?>€</strong>
                    </li>
                <?php endforeach; ?>
            </ul>

            <a href="add_items.php?order=<?= $activeOrder['id'] ?>" class="btn btn-dark mt-3">Add Items</a>
            <a href="close_order.php?id=<?= $activeOrder['id'] ?>" class="btn btn-success mt-3">Close Order</a>
            <a href="cancel_order.php?id=<?= $activeOrder['id'] ?>" class="btn btn-danger mt-3"
               onclick="return confirm('Cancel this order?');">
               Cancel Order
            </a>
        </div>

    <!-- SERVED ORDER -->
    <?php elseif ($lastOrder && $lastOrder['status'] === "served"): ?>

        <div class="card p-4 shadow-sm">
            <h4 class="fw-bold">Order #<?= $lastOrder['id'] ?></h4>
            <p><strong>Status:</strong> Served</p>

            <hr>
            <h5>Items</h5>

            <ul class="list-group mb-3">
                <?php foreach ($lastOrderItems as $item): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <?= htmlspecialchars($item['item_name']) ?> (x<?= (int)$item['quantity'] ?>)
                        <strong><?= number_format($item['price'] * $item['quantity'], 2) ?>€</strong>
                    </li>
                <?php endforeach; ?>
            </ul>

            <a href="refund_order.php?id=<?= $lastOrder['id'] ?>"
               class="btn btn-warning mt-3"
               onclick="return confirm('Refund this order?');">
                Refund
            </a>

            <!-- NEW BUTTON HERE -->
            <a href="new_order.php?table=<?= $tableId ?>" 
            class="btn btn-dark mt-3 w-100">
            Create New Order
            </a>
        </div>

    <!-- REFUNDED ORDER -->
    <?php elseif ($lastOrder && $lastOrder['status'] === "refunded"): ?>

        <div class="card p-4 shadow-sm">
            <h4 class="fw-bold">Order #<?= $lastOrder['id'] ?></h4>
            <p><strong>Status:</strong> Refunded</p>

            <div class="alert alert-info mt-3">This order has been refunded.</div>

            <a href="new_order.php?table=<?= $tableId ?>" class="btn btn-dark">Create New Order</a>
        </div>

    <!-- NO ORDER -->
    <?php else: ?>

        <div class="card p-4 shadow-sm text-center">
            <h4>No active order</h4>
            <p class="text-muted">There is no pending order.</p>
            <a href="new_order.php?table=<?= $tableId ?>" class="btn btn-dark mt-3">Create New Order</a>
        </div>

    <?php endif; ?>

</div>

<?php include "includes/footer.php"; ?>
