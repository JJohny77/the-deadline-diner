<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

// -------------------------
// VALIDATE ORDER ID
// -------------------------
if (!isset($_GET['order']) || !is_numeric($_GET['order'])) {
    echo "<h2>Invalid order selected.</h2>";
    include "includes/footer.php";
    exit;
}

$orderId = intval($_GET['order']);

// -------------------------
// FETCH ORDER DETAILS
// -------------------------
$q = mysqli_query($conn, "
    SELECT orders.*, tables.name AS table_name
    FROM orders
    JOIN tables ON tables.id = orders.table_id
    WHERE orders.id = $orderId
");

if (mysqli_num_rows($q) === 0) {
    echo "<h2>Order not found.</h2>";
    include "includes/footer.php";
    exit;
}

$order = mysqli_fetch_assoc($q);

// -------------------------
// ADD ITEM ACTION
// -------------------------
if (isset($_POST['add_item'])) {
    $menuId   = intval($_POST['menu_id']);
    $quantity = max(1, intval($_POST['quantity']));

    // 1. Προσθέτουμε το item στο order
    mysqli_query($conn, "
        INSERT INTO order_items (order_id, menu_id, quantity)
        VALUES ($orderId, $menuId, $quantity)
    ");

    // 2. Αν το τραπέζι είναι ακόμη free, το κάνουμε occupied
    //    (έχουμε ήδη το $order από πιο πάνω: SELECT orders.*, tables.name AS table_name ...)
    $tableIdForOrder = intval($order['table_id']);

    mysqli_query($conn, "
        UPDATE tables
        SET status = 'occupied'
        WHERE id = $tableIdForOrder
          AND status = 'free'
    ");

    // 3. Redirect back to avoid form resubmission
    header("Location: add_items.php?order=$orderId");
    exit;
}

// -------------------------
// FETCH MENU ITEMS
// (απλό ORDER BY, για να είμαστε σίγουροι ότι δεν σκάει το FIELD())
// -------------------------
$menuQ = mysqli_query($conn, "
    SELECT *
    FROM menu
    ORDER BY category ASC, name ASC
");

if (!$menuQ) {
    die("Menu query error: " . mysqli_error($conn));
}

$menuGrouped = [];
while ($m = mysqli_fetch_assoc($menuQ)) {
    $menuGrouped[$m['category']][] = $m;
}

// Τελική σειρά κατηγοριών που θέλουμε
$categoryOrder = ['starter', 'side', 'main', 'drink', 'dessert'];

// Re-order τα keys του $menuGrouped με βάση το $categoryOrder
uksort($menuGrouped, function ($a, $b) use ($categoryOrder) {
    $pa = array_search($a, $categoryOrder, true);
    $pb = array_search($b, $categoryOrder, true);

    if ($pa === false) $pa = 99; // άγνωστη κατηγορία -> πάει στο τέλος
    if ($pb === false) $pb = 99;

    return $pa <=> $pb;
});

// -------------------------
// FETCH ORDER ITEMS
// -------------------------
$itemsQ = mysqli_query($conn, "
    SELECT order_items.*, menu.name AS item_name, menu.price
    FROM order_items
    JOIN menu ON menu.id = order_items.menu_id
    WHERE order_id = $orderId
");

$orderItems = mysqli_fetch_all($itemsQ, MYSQLI_ASSOC);
?>

<h1 class="fw-bold mb-4">Add Items — Order #<?= $orderId ?></h1>

<h4 class="mb-3">Table: <?= htmlspecialchars($order['table_name']) ?></h4>

<div class="row">
    <!-- LEFT SIDE: Add Item Form -->
    <div class="col-md-5">
        <div class="card p-4 shadow-sm">
            <h4 class="fw-bold mb-3">Add Menu Item</h4>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Select item</label>

                    <select name="menu_id" class="form-select add-items-select" required>
                        <?php foreach ($menuGrouped as $category => $items): ?>
                            <optgroup label="<?= ucfirst($category) ?>">
                                <?php foreach ($items as $item): ?>
                                    <option value="<?= $item['id'] ?>">
                                        <?= htmlspecialchars($item['name']) ?>
                                        — <?= number_format($item['price'], 2) ?>€
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                </div>

                <button type="submit" name="add_item" class="btn btn-dark w-100">
                    Add to Order
                </button>
            </form>
        </div>
    </div>

    <!-- RIGHT SIDE: Current Order Items -->
    <div class="col-md-7">
        <div class="card p-4 shadow-sm">
            <h4 class="fw-bold mb-3">Current Items</h4>

            <?php if (count($orderItems) === 0): ?>
                <p class="text-muted">No items added yet.</p>
            <?php else: ?>
                <ul class="list-group mb-3">
                <?php
                $total = 0;
                foreach ($orderItems as $item):
                    $line = $item['price'] * $item['quantity'];
                    $total += $line;
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">

                    <div>
                        <strong><?= $item['item_name'] ?></strong>
                        <span class="text-muted">(x<?= $item['quantity'] ?>)</span>
                        <br>
                        <small><?= number_format($line, 2) ?>€</small>
                    </div>

                    <div class="btn-group">
                        <!-- Edit Quantity -->
                        <a href="edit_order_item.php?id=<?= $item['id'] ?>&order=<?= $orderId ?>" 
                        class="btn btn-sm btn-warning">
                            Edit
                        </a>

                        <!-- Delete -->
                        <a href="delete_order_item.php?id=<?= $item['id'] ?>&order=<?= $orderId ?>" 
                        class="btn btn-sm btn-danger"
                        onclick="return confirm('Are you sure you want to remove this item?');">
                            Delete
                        </a>
                    </div>

                </li>
                <?php endforeach; ?>
                </ul>
                    
                <h4 class="fw-bold">Total: <?= number_format($total, 2) ?>€</h4>

                <a href="close_order.php?id=<?= $orderId ?>" class="btn btn-success mt-3 w-100">
                    Close Order
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
