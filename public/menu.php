<?php
include "includes/manager_only.php";
include "includes/header.php";
include "includes/db.php";

// Fetch all menu items
$menuQuery = mysqli_query($conn, "
    SELECT * FROM menu
    ORDER BY 
        FIELD(category, 'starter', 'side', 'main', 'drink', 'dessert'),
        name ASC
");

$items = mysqli_fetch_all($menuQuery, MYSQLI_ASSOC);
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold">Menu Items</h1>
        <a href="menu_add.php" class="btn btn-dark">+ Add New Item</a>
    </div>

    <table class="table table-striped table-hover shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Price (€)</th>
                <th style="width: 160px;">Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php if (count($items) === 0): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        No menu items found.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= $item['name'] ?></td>
                        <td><?= $item['category'] ?></td>
                        <td><?= number_format($item['price'], 2) ?>€</td>
                        <td>
                            <a href="menu_edit.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="menu_delete.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include "includes/footer.php"; ?>
