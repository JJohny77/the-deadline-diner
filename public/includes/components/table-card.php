<?php
if (!isset($tableNumber) || !isset($status) || !isset($seats)) {
    exit;
}
?>

<?php
// Expected variables:
// $tableNumber (int)
// $status (string) -> "free", "occupied", "reserved"
// $seats (int)

$badgeClass = [
    "free" => "bg-success",
    "occupied" => "bg-danger",
    "reserved" => "bg-warning text-dark"
][$status] ?? "bg-secondary";

$statusLabel = [
    "free" => "Available",
    "occupied" => "Occupied",
    "reserved" => "Reserved"
][$status] ?? "Unknown";
?>

<div class="card table-card shadow-sm">
    <div class="card-body text-center">
        <h5 class="fw-bold mb-1">Table #<?= $tableNumber ?></h5>

        <span class="badge <?= $badgeClass ?> px-3 py-2 mb-2">
            <?= $statusLabel ?>
        </span>

        <p class="text-muted small mb-0"><?= $seats ?> seats</p>

        <a href="#" class="btn btn-dark btn-sm mt-3 w-100">View Details</a>
    </div>
</div>
