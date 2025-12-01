<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

$isManager = isset($_SESSION['role']) && $_SESSION['role'] === "manager";

// ===============================
// HANDLE ADD TABLE
// ===============================
if ($isManager && isset($_POST['add_table'])) {
    $name  = mysqli_real_escape_string($conn, $_POST['name']);
    $seats = intval($_POST['seats']);

    mysqli_query($conn, "
        INSERT INTO tables (name, seats, status)
        VALUES ('$name', $seats, 'free')
    ");

    header("Location: tables.php?added=1");
    exit;
}

// ===============================
// HANDLE EDIT TABLE
// ===============================
if ($isManager && isset($_POST['edit_table'])) {
    $id    = intval($_POST['id']);
    $name  = mysqli_real_escape_string($conn, $_POST['name']);
    $seats = intval($_POST['seats']);

    mysqli_query($conn, "
        UPDATE tables
        SET name='$name', seats=$seats
        WHERE id=$id
    ");

    header("Location: tables.php?edited=1");
    exit;
}

// ===============================
// HANDLE DELETE TABLE
// ===============================
if ($isManager && isset($_POST['delete_table'])) {
    $id = intval($_POST['id']);

    mysqli_query($conn, "DELETE FROM tables WHERE id=$id");

    header("Location: tables.php?deleted=1");
    exit;
}

// Fetch all tables
$query = "SELECT * FROM tables ORDER BY id ASC";
$result = mysqli_query($conn, $query);
$tables = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="fw-bold">Tables Overview</h1>

    <?php if ($isManager): ?>
        <button class="btn btn-primary" onclick="openAddModal()">+ Add Table</button>
    <?php endif; ?>
</div>

<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success">Table added successfully!</div>
<?php endif; ?>

<?php if (isset($_GET['edited'])): ?>
    <div class="alert alert-info">Table updated!</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger">Table deleted.</div>
<?php endif; ?>

<div class="row g-4">

<?php foreach ($tables as $t): ?>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card p-3 shadow-sm">

            <h5 class="fw-bold mb-1"><?= htmlspecialchars($t['name']) ?></h5>

            <span class="badge 
              <?= $t['status'] === 'free' ? 'bg-success' : '' ?>
              <?= $t['status'] === 'occupied' ? 'bg-danger' : '' ?>
              <?= $t['status'] === 'reserved' ? 'bg-warning text-dark' : '' ?>">
              <?= ucfirst($t['status']) ?>
            </span>

            <p class="mt-2 mb-1 text-muted"><?= $t['seats'] ?> seats</p>

            <a href="table.php?id=<?= $t['id'] ?>" class="btn btn-dark btn-sm mt-2 w-100">View</a>

            <?php if ($isManager): ?>
                <button class="btn btn-warning btn-sm mt-2 w-100"
                        onclick="openEditModal(<?= $t['id'] ?>, '<?= htmlspecialchars($t['name']) ?>', <?= $t['seats'] ?>)">
                    Edit
                </button>

                <button class="btn btn-danger btn-sm mt-2 w-100"
                        onclick="openDeleteModal(<?= $t['id'] ?>)">
                    Delete
                </button>
            <?php endif; ?>

        </div>
    </div>
<?php endforeach; ?>

</div>


<!-- ========================================= -->
<!-- ADD TABLE MODAL -->
<!-- ========================================= -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Add Table</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <label class="form-label fw-bold">Name</label>
        <input type="text" name="name" class="form-control" required>

        <label class="form-label fw-bold mt-3">Seats</label>
        <input type="number" name="seats" class="form-control" min="1" required>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" name="add_table">Add</button>
      </div>

    </form>
  </div>
</div>


<!-- ========================================= -->
<!-- EDIT TABLE MODAL -->
<!-- ========================================= -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Edit Table</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <input type="hidden" name="id" id="edit_id">

        <label class="form-label fw-bold">Name</label>
        <input type="text" name="name" id="edit_name" class="form-control" required>

        <label class="form-label fw-bold mt-3">Seats</label>
        <input type="number" name="seats" id="edit_seats" class="form-control" required>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" name="edit_table">Save</button>
      </div>

    </form>
  </div>
</div>


<!-- ========================================= -->
<!-- DELETE TABLE MODAL -->
<!-- ========================================= -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Delete Table</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p class="fw-bold text-danger">Are you sure you want to delete this table?</p>
        <input type="hidden" name="id" id="delete_id">
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger" name="delete_table">Delete</button>
      </div>

    </form>
  </div>
</div>


<script>
function openAddModal() {
    new bootstrap.Modal(document.getElementById("addModal")).show();
}

function openEditModal(id, name, seats) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_name").value = name;
    document.getElementById("edit_seats").value = seats;

    new bootstrap.Modal(document.getElementById("editModal")).show();
}

function openDeleteModal(id) {
    document.getElementById("delete_id").value = id;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}
</script>

<?php include "includes/footer.php"; ?>
