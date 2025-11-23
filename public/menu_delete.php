<?php
include "includes/header.php";
include "includes/db.php";

// -----------------------------------
// VALIDATE menu ID
// -----------------------------------
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<h2>Invalid menu item.</h2>";
    include "includes/footer.php";
    exit;
}

$menuId = intval($_GET['id']);

// -----------------------------------
// FETCH MENU ITEM
// -----------------------------------
$q = mysqli_query($conn, "SELECT * FROM menu WHERE id = $menuId LIMIT 1");

if (mysqli_num_rows($q) === 0) {
    echo "<h2>Menu item not found.</h2>";
    include "includes/footer.php";
    exit;
}

$item = mysqli_fetch_assoc($q);

// -----------------------------------
// DELETE CONFIRMED?
// -----------------------------------
if (isset($_POST['confirm_delete'])) {

    mysqli_query($conn, "DELETE FROM menu WHERE id = $menuId");

    header("Location: menu.php?deleted=1");
    exit;
}

?>

<div class="container my-4">

    <h1 class="fw-bold mb-4 text-danger">Delete Menu Item</h1>

    <div class="card p-4 shadow-sm" style="max-width: 600px;">
        <h4 class="fw-bold">Are you sure?</h4>

        <p class="mt-3">
            You are about to delete:<br>
            <strong><?= htmlspecialchars($item['name']) ?></strong><br>
            <span class="text-muted">(<?= htmlspecialchars($item['category']) ?> — <?= number_format($item['price'], 2) ?>€)</span>
        </p>

        <p class="text-danger"><strong>This action cannot be undone.</strong></p>

        <form method="POST" class="d-flex gap-3 mt-4">
            <button name="confirm_delete" class="btn btn-danger">Yes, Delete</button>
            <a href="menu.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

</div>

<?php include "includes/footer.php"; ?>
