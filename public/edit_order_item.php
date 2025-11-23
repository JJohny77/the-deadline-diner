<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

if (!isset($_GET['id']) || !isset($_GET['order']) || !is_numeric($_GET['id'])) {
    echo "<h2>Invalid item.</h2>";
    include "includes/footer.php";
    exit;
}

$itemId = intval($_GET['id']);
$orderId = intval($_GET['order']);

$q = mysqli_query($conn, "
    SELECT order_items.*, menu.name AS item_name 
    FROM order_items
    JOIN menu ON menu.id = order_items.menu_id
    WHERE order_items.id = $itemId
");

if (mysqli_num_rows($q) === 0) {
    echo "<h2>Item not found.</h2>";
    include "includes/footer.php";
    exit;
}

$item = mysqli_fetch_assoc($q);

// HANDLE UPDATE
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $quantity = max(1, intval($_POST['quantity']));

    mysqli_query($conn, "
        UPDATE order_items
        SET quantity = $quantity
        WHERE id = $itemId
    ");

    header("Location: add_items.php?order=$orderId");
    exit;
}
?>

<div class="container my-4" style="max-width: 500px;">
    <h2 class="fw-bold mb-3">Edit Quantity</h2>

    <div class="card p-4 shadow-sm">

        <p><strong>Item:</strong> <?= $item['item_name'] ?></p>

        <form method="POST">
            <label class="form-label mt-2">Quantity</label>
            <input 
                type="number" 
                name="quantity" 
                class="form-control"
                min="1"
                value="<?= $item['quantity'] ?>"
                required
            >

            <button class="btn btn-dark mt-4 w-100">Save</button>
        </form>

    </div>
</div>

<?php include "includes/footer.php"; ?>
