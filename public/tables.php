<?php 
include "includes/header.php"; 
include "includes/db.php";

// Fetch tables from DB
$query = "SELECT * FROM tables ORDER BY id ASC";
$result = mysqli_query($conn, $query);

$tables = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<h1 class="fw-bold mb-4">Tables Overview</h1>

<div class="row g-4">

<?php foreach ($tables as $t): ?>
    <div class="col-6 col-md-4 col-lg-3">

        <?php
        $tableNumber = $t["id"];
        $status      = $t["status"];
        $seats       = $t["seats"];

        include "includes/components/table-card.php"; 
        ?>

    </div>
<?php endforeach; ?>

</div>

<?php include "includes/footer.php"; ?>
