<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

$isManager = isset($_SESSION['role']) && $_SESSION['role'] === 'manager';

$errors = [];
$success = null;

// ===================================================
// ADD NEW WAITER (ONLY MANAGER SEES THIS)
// ===================================================
if ($isManager && isset($_POST['add_waiter'])) {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $errors[] = "All fields are required to add a waiter.";
    } else {
        $emailEsc = mysqli_real_escape_string($conn, $email);

        $exists = mysqli_query($conn, "
            SELECT id FROM users WHERE email='$emailEsc' LIMIT 1
        ");

        if (mysqli_num_rows($exists) > 0) {
            $errors[] = "Email is already in use.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $nameEsc = mysqli_real_escape_string($conn, $name);

            mysqli_query($conn, "
                INSERT INTO users (name, email, password_hash, role, created_at, shift_active)
                VALUES ('$nameEsc', '$emailEsc', '$hash', 'waiter', NOW(), 0)
            ");

            $success = "Waiter added successfully!";
        }
    }
}

// ===================================================
// EDIT WAITER (NAME & EMAIL)
// ===================================================
if ($isManager && isset($_POST['edit_waiter'])) {
    $id    = intval($_POST['waiter_id']);
    $name  = trim($_POST['edit_name']);
    $email = trim($_POST['edit_email']);

    if ($name === '' || $email === '') {
        $errors[] = "Name and email cannot be empty.";
    } else {
        $emailEsc = mysqli_real_escape_string($conn, $email);
        $nameEsc  = mysqli_real_escape_string($conn, $name);

        mysqli_query($conn, "
            UPDATE users
            SET name='$nameEsc', email='$emailEsc'
            WHERE id=$id AND role='waiter'
        ");

        $success = "Waiter updated successfully!";
    }
}

// ===================================================
// RESET PASSWORD
// ===================================================
if ($isManager && isset($_POST['reset_password'])) {
    $id       = intval($_POST['waiter_id']);
    $password = trim($_POST['new_password']);

    if ($password === '') {
        $errors[] = "Password cannot be empty.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        mysqli_query($conn, "
            UPDATE users
            SET password_hash='$hash'
            WHERE id=$id AND role='waiter'
        ");

        $success = "Password updated successfully!";
    }
}

// ===================================================
// DELETE WAITER
// ===================================================
if ($isManager && isset($_POST['delete_waiter'])) {
    $id = intval($_POST['waiter_id']);

    // remove from tables
    mysqli_query($conn, "
        UPDATE tables SET assigned_waiter_id=NULL
        WHERE assigned_waiter_id=$id
    ");

    // remove waiter
    mysqli_query($conn, "
        DELETE FROM users 
        WHERE id=$id AND role='waiter'
    ");

    $success = "Waiter deleted successfully!";
}

// ===================================================
// FETCH ALL WAITERS
// ===================================================
$waitersQ = mysqli_query($conn, "
    SELECT * FROM users WHERE role='waiter' ORDER BY name ASC
");
$waiters = mysqli_fetch_all($waitersQ, MYSQLI_ASSOC);
?>

<div class="container my-4">

    <h1 class="fw-bold mb-4">Staff Dashboard</h1>

    <!-- ERRORS & SUCCESS -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                • <?= htmlspecialchars($e) ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>


    <!-- ===================================================== -->
    <!-- ADD NEW WAITER (ONLY FOR OWNER) -->
    <!-- ===================================================== -->
    <?php if ($isManager): ?>
    <div class="card p-4 shadow-sm mb-4" style="max-width: 600px;">
        <h4 class="fw-bold mb-3">Add New Waiter</h4>

        <form method="POST" class="row g-2">
            <div class="col-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="col-12">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="col-12">
                <button name="add_waiter" class="btn btn-dark w-100 mt-2">
                    Add Waiter
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>


    <!-- ===================================================== -->
    <!-- SHOW ALL WAITERS + MANAGEMENT TOOLS -->
    <!-- ===================================================== -->
    <div class="row">
        <?php foreach ($waiters as $w): ?>
            <div class="col-md-4 mb-3">
                <div class="card p-3 shadow-sm">

                    <h4 class="fw-bold mb-2"><?= htmlspecialchars($w['name']) ?></h4>

                    <?php if ($w['shift_active']): ?>
                        <span class="badge bg-success">Online</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Offline</span>
                    <?php endif; ?>

                    <p class="text-muted mt-2">
                        Email: <?= htmlspecialchars($w['email']) ?><br>
                        Shift: <?= $w['shift_active'] ? $w['shift_started_at'] : 'Not started' ?>
                    </p>

                    <hr>

                    <!-- START/END SHIFT -->
                    <?php if (!$w['shift_active']): ?>
                        <a href="start_shift.php?id=<?= $w['id'] ?>" 
                           class="btn btn-dark w-100 mb-2">Start Shift</a>
                    <?php else: ?>
                        <a href="end_shift.php?id=<?= $w['id'] ?>" 
                           class="btn btn-danger w-100 mb-2">End Shift</a>
                    <?php endif; ?>


                    <!-- MANAGEMENT BUTTONS - ONLY OWNER -->
                    <?php if ($isManager): ?>
                        <button class="btn btn-outline-primary w-100 mt-2"
                                onclick="openEditModal(<?= $w['id'] ?>, '<?= $w['name'] ?>', '<?= $w['email'] ?>')">
                            Edit Waiter
                        </button>

                        <button class="btn btn-outline-secondary w-100 mt-2"
                                onclick="openPasswordModal(<?= $w['id'] ?>)">
                            Reset Password
                        </button>

                        <form method="POST" class="mt-2">
                            <input type="hidden" name="waiter_id" value="<?= $w['id'] ?>">
                            <button name="delete_waiter"
                                    onclick="return confirm('Delete this waiter?');"
                                    class="btn btn-outline-danger w-100">
                                Delete Waiter
                            </button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- ====================================== -->
<!-- EDIT WAITER MODAL -->
<!-- ====================================== -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Edit Waiter</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <input type="hidden" name="waiter_id" id="editWaiterId">

            <label class="form-label">Name</label>
            <input type="text" name="edit_name" id="editName" class="form-control" required>

            <label class="form-label mt-3">Email</label>
            <input type="email" name="edit_email" id="editEmail" class="form-control" required>
        </div>

        <div class="modal-footer">
            <button class="btn btn-primary" name="edit_waiter">Save Changes</button>
        </div>
    </form>
  </div>
</div>

<!-- ====================================== -->
<!-- RESET PASSWORD MODAL -->
<!-- ====================================== -->
<div class="modal fade" id="passwordModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Reset Password</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <input type="hidden" name="waiter_id" id="passwordWaiterId">

            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" name="reset_password">Update Password</button>
        </div>
    </form>
  </div>
</div>

<script>
function openEditModal(id, name, email) {
    document.getElementById("editWaiterId").value = id;
    document.getElementById("editName").value = name;
    document.getElementById("editEmail").value = email;
    new bootstrap.Modal(document.getElementById("editModal")).show();
}

function openPasswordModal(id) {
    document.getElementById("passwordWaiterId").value = id;
    new bootstrap.Modal(document.getElementById("passwordModal")).show();
}
</script>

<?php include "includes/footer.php"; ?>
