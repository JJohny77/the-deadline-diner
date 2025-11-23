<?php
include "includes/manager_only.php";
include "includes/header.php";
include "includes/db.php";

// -----------------------------------
// VALIDATE ID
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
// HANDLE UPDATE
// -----------------------------------
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $category = trim($_POST['category']);

    if ($name === "") $errors[] = "Name cannot be empty.";
    if ($price === "" || !is_numeric($price)) $errors[] = "Price must be a valid number.";
    if ($category === "") $errors[] = "Category cannot be empty.";

    if (empty($errors)) {

        $nameEsc = mysqli_real_escape_string($conn, $name);
        $categoryEsc = mysqli_real_escape_string($conn, $category);
        $priceEsc = floatval($price);

        $updateQ = "
            UPDATE menu
            SET name = '$nameEsc', price = $priceEsc, category = '$categoryEsc'
            WHERE id = $menuId
        ";

        if (mysqli_query($conn, $updateQ)) {
            header("Location: menu.php?updated=1");
            exit;
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container my-4">
    <h1 class="fw-bold mb-4">Edit Menu Item</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong><br>
            <?php foreach ($errors as $e): ?>
                • <?= $e ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm" style="max-width: 600px;">

        <label class="fw-bold mt-2">Item Name</label>
        <input 
            type="text" 
            name="name" 
            class="form-control"
            value="<?= htmlspecialchars($item['name']) ?>" 
            required
        >

        <label class="fw-bold mt-3">Price (€)</label>
        <input 
            type="number" 
            step="0.01" 
            name="price" 
            class="form-control"
            value="<?= htmlspecialchars($item['price']) ?>"
            required
        >

        <label class="fw-bold mt-3">Category</label>
        <input 
            type="text" 
            name="category" 
            class="form-control"
            value="<?= htmlspecialchars($item['category']) ?>" 
            required
        >

        <button class="btn btn-dark mt-4">Save Changes</button>
    </form>

</div>

<?php include "includes/footer.php"; ?>
