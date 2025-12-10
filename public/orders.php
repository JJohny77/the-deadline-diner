<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

// ---- CLEANUP COMPLETED ORDERS (only for manager) ----
$cleanupDone = false;

if (
    isset($_POST['cleanup_orders']) &&
    isset($_SESSION['role']) &&
    $_SESSION['role'] === 'manager'
) {
    // 1) Σβήνουμε πρώτα τυχόν refunds που ανήκουν σε μη–pending orders
    mysqli_query($conn, "
        DELETE FROM refund_logs
        WHERE order_id IN (
            SELECT id FROM orders WHERE status <> 'pending'
        )
    ");

    // 2) Σβήνουμε τα order_items των μη–pending orders
    mysqli_query($conn, "
        DELETE FROM order_items
        WHERE order_id IN (
            SELECT id FROM orders WHERE status <> 'pending'
        )
    ");

    // 3) Και τέλος, σβήνουμε τα ίδια τα orders (served / refunded / cancelled κ.λπ.)
    mysqli_query($conn, "
        DELETE FROM orders
        WHERE status <> 'pending'
    ");

    $cleanupDone = true;
}

// -------------------------------------------------
// FETCH ALL ORDERS + TABLE + WAITER INFO
// -------------------------------------------------
$q = mysqli_query($conn, "
    SELECT 
        o.*,
        t.name  AS table_name,
        t.status AS table_status,
        w.name  AS waiter_name
    FROM orders o
    JOIN tables t ON t.id = o.table_id
    LEFT JOIN users w ON w.id = t.assigned_waiter_id
    ORDER BY 
        CASE o.status
            WHEN 'pending'  THEN 1
            WHEN 'served'   THEN 2
            WHEN 'refunded' THEN 3
            ELSE 4
        END,
        o.created_at ASC,
        o.id ASC
");

$orders = mysqli_fetch_all($q, MYSQLI_ASSOC);

// Group by status για να βλέπουν ξεκάθαρα τα pending
$grouped = [
    'pending'  => [],
    'served'   => [],
    'refunded' => [],
    'other'    => [],
];

foreach ($orders as $o) {
    $status = $o['status'];
    if (isset($grouped[$status])) {
        $grouped[$status][] = $o;
    } else {
        $grouped['other'][] = $o;
    }
}

// Helper για να ζωγραφίζουμε λίστα orders ανά status
function renderOrderList($label, $orders, $conn) {

    echo "<h2 class='mt-5 mb-4'>$label Orders</h2>";

    if (empty($orders)) {
        echo "<p class='text-muted mb-5'>No $label orders.</p>";
        return;
    }

    // --------------------------------------------------
    // 1) PENDING ORDERS → GROUP BY WAITER
    // --------------------------------------------------
    if ($label === 'Pending') {

        // Group orders by waiter name (or Unassigned)
        $byWaiter = [];

        foreach ($orders as $o) {
            $waiter = $o['waiter_name'] ?? null;
            if (!$waiter) $waiter = "Unassigned";

            if (!isset($byWaiter[$waiter])) {
                $byWaiter[$waiter] = [];
            }

            $byWaiter[$waiter][] = $o;
        }

        // Render sections per waiter
        foreach ($byWaiter as $waiterName => $ordersForWaiter) {
            echo "<h4 class='mt-4 mb-3 fw-bold'>👤 " . htmlspecialchars($waiterName) . "</h4>";
            echo "<div class='row g-3'>";

            foreach ($ordersForWaiter as $order) {
                renderSingleOrderCard($order, $conn);
            }

            echo "</div>";
        }

        return;
    }

    // --------------------------------------------------
    // 2) SERVED / REFUNDED → SINGLE FLAT LIST
    // --------------------------------------------------
    echo "<div class='row g-3'>";

    foreach ($orders as $order) {
        renderSingleOrderCard($order, $conn);
    }

    echo "</div>";
}

function renderSingleOrderCard($order, $conn) {

    $orderId = (int)$order['id'];

    $itemsQ = mysqli_query($conn, "
        SELECT m.name, m.price, oi.quantity
        FROM order_items oi
        JOIN menu m ON m.id = oi.menu_id
        WHERE oi.order_id = $orderId
    ");

    $items = [];
    $total = 0;

    while ($row = mysqli_fetch_assoc($itemsQ)) {
        $line = $row['price'] * $row['quantity'];
        $total += $line;
        $items[] = [
            'name' => $row['name'],
            'price' => $row['price'],
            'quantity' => $row['quantity'],
            'line_total' => $line
        ];
    }

    ?>

    <div class="col-12 col-md-6 col-lg-4">
        <div class="card p-3 shadow-sm h-100">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="fw-bold mb-0">Order #<?= $order['id'] ?></h5>
                <span class="badge bg-secondary"><?= ucfirst($order['status']) ?></span>
            </div>

            <p class="mb-1 order-card-label"><strong>Table:</strong> <?= htmlspecialchars($order['table_name']) ?></p>

            <?php if (!empty($order['waiter_name'])): ?>
                <p class="mb-1 order-card-label"><strong>Waiter:</strong> <?= htmlspecialchars($order['waiter_name']) ?></p>
            <?php endif; ?>

            <p class="mb-1 order-card-label">
                <small>Created: <?= $order['created_at'] ?></small>
            </p>

            <hr class="my-2">

            <h6 class="fw-bold mb-2 order-card-heading">Items</h6>

            <?php if (empty($items)): ?>
                <p class="text-muted">No items added.</p>
            <?php else: ?>
                <ul class="list-group mb-2">
                    <?php foreach ($items as $it): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <?= htmlspecialchars($it['name']) ?>
                                <small class="text-muted">(x<?= (int)$it['quantity'] ?>)</small>
                            </span>
                            <strong><?= number_format($it['line_total'], 2) ?>€</strong>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <p class="fw-bold mb-0 order-card-total">
                    Total: <?= number_format($total, 2) ?>€
                </p>

            <?php endif; ?>

            <a href="table.php?id=<?= $order['table_id'] ?>"
               class="btn btn-outline-dark btn-sm mt-3 w-100">Go to Table</a>

        </div>
    </div>

    <?php
}

?>

<!-- ΤΙΤΛΟΣ + ΚΟΥΜΠΙ PDF -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="fw-bold mb-0">Orders Overview</h1>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager'): ?>
        <a href="daily_report.php" class="btn btn-outline-secondary" target="_blank">
            Export Today as PDF
        </a>
    <?php endif; ?>
</div>


<?php if ($cleanupDone): ?>
    <div class="alert alert-success">
        Completed orders (served / refunded / other) have been cleaned up.
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager'): ?>
    <form method="POST" class="mb-3">
        <button type="submit" 
                name="cleanup_orders" 
                class="btn btn-danger"
                onclick="return confirm('⚠️ This will delete all non-pending orders (served/refunded). Continue;');">
            Clean Up Completed Orders
        </button>
    </form>
<?php endif; ?>

<p class="text-muted mb-4">
    Pending orders appear first, so οι σερβιτόροι μπορούν να βλέπουν με σειρά
    ποια τραπέζια περιμένουν και τι περιέχει κάθε παραγγελία.
</p>

<?php
renderOrderList('Pending',  $grouped['pending'],  $conn);
renderOrderList('Served',   $grouped['served'],   $conn);
renderOrderList('Refunded', $grouped['refunded'], $conn);
if (!empty($grouped['other'])) {
    renderOrderList('Other', $grouped['other'], $conn);
}
?>

<?php include "includes/footer.php"; ?>
