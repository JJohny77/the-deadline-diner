<?php
include "includes/db.php";
include "includes/header.php";

// Validate table ID
if (!isset($_GET['table']) || !is_numeric($_GET['table'])) {
    echo "<h2 class='text-center mt-5'>Invalid table</h2>";
    include "includes/footer.php";
    exit;
}
$tableId = intval($_GET['table']);

// STEP 1 — SHOW WAITER SELECTION FORM
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    // Fetch only ONLINE waiters
    $waitersQ = mysqli_query($conn, "
        SELECT id, name 
        FROM users 
        WHERE role='waiter' AND shift_active = 1
        ORDER BY name
    ");
?>

<div class="container my-5" style="max-width: 500px;">
    <h2 class="fw-bold mb-4 text-center">Select Waiter for Table #<?= $tableId ?></h2>

    <form method="POST" class="card p-4 shadow-sm">

        <input type="hidden" name="table" value="<?= $tableId ?>">

        <label class="form-label fw-bold">Waiter</label>
        <select name="waiter_id" class="form-select" required>
            <option value="">-- Select Waiter --</option>

            <?php while ($w = mysqli_fetch_assoc($waitersQ)): ?>
                <option value="<?= $w['id'] ?>">
                    <?= htmlspecialchars($w['name']) ?>
                </option>
            <?php endwhile; ?>

        </select>

        <button class="btn btn-dark w-100 mt-3">Create Order</button>
    </form>
</div>

<?php
    include "includes/footer.php";
    exit;
}


// STEP 2 — HANDLE FORM SUBMIT
$waiterId = intval($_POST['waiter_id']);
$tableId = intval($_POST['table']);

// Create order
mysqli_query($conn, "
    INSERT INTO orders (table_id, status)
    VALUES ($tableId, 'pending')
");

$newOrderId = mysqli_insert_id($conn);

// Assign waiter to table
mysqli_query($conn, "
    UPDATE tables 
    SET assigned_waiter_id = $waiterId
    WHERE id = $tableId
");

// Redirect to add items page
header("Location: add_items.php?order=" . $newOrderId);
exit;

?>
