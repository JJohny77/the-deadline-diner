<?php
include "includes/header.php";
include "includes/db.php";

// -----------------------------
// HANDLE FORM SUBMISSION
// -----------------------------
$errors = [];
$success = false;

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

        $query = "
            INSERT INTO menu (name, price, category)
            VALUES ('$nameEsc', $priceEsc, '$categoryEsc')
        ";

        if (mysqli_query($conn, $query)) {
            header("Location: menu.php?added=1");
            exit;
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container my-4" style="max-width: 650px;">
    <h1 class="fw-bold mb-4">Add Menu Item</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong><br>
            <?php foreach ($errors as $e): ?>
                • <?= $e ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">

        <label class="fw-bold mt-2">Item Name</label>
        <input type="text" name="name" class="form-control" required>

        <label class="fw-bold mt-3">Price (€)</label>
        <input type="number" step="0.01" name="price" class="form-control" required>

        <label class="fw-bold mt-3">Category</label>
        <select name="category" class="form-select" required>

            <option value="">-- Select Category --</option>

            <option value="starter">Starter</option>
            <option value="side">Side</option>
            <option value="main">Main Dish</option>
            <option value="drink">Drink</option>
            <option value="dessert">Dessert</option>

        </select>

        <button class="btn btn-dark mt-4">Add Item</button>
    </form>

</div>

<?php include "includes/footer.php"; ?>
