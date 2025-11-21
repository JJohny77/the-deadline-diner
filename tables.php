<?php include "partials/header.php"; ?>

<h1 class="fw-bold mb-4">Tables Overview</h1>

<?php
// Temporary static data — later we connect DB
$tables = [
    ["num" => 1, "status" => "free", "seats" => 2],
    ["num" => 2, "status" => "occupied", "seats" => 4],
    ["num" => 3, "status" => "reserved", "seats" => 2],
    ["num" => 4, "status" => "free", "seats" => 6],
    ["num" => 5, "status" => "occupied", "seats" => 2],
    ["num" => 6, "status" => "reserved", "seats" => 4],
];
?>

<div class="row g-4">

    <?php foreach ($tables as $t): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <?php 
            $tableNumber = $t["num"];
            $status      = $t["status"];
            $seats       = $t["seats"];
            include "components/table-card.php"; 
            ?>
        </div>
    <?php endforeach; ?>

</div>

<?php include "partials/footer.php"; ?>
