<?php
include "includes/auth.php";   // ΔΕΝ κλειδώνει τίποτα — απλώς κρατά session
include "includes/header.php";
include "includes/db.php";

// Fetch all waiters
$usersQ = mysqli_query($conn, "
    SELECT id, name, role, shift_active, shift_started_at
    FROM users
    WHERE role = 'waiter'
    ORDER BY name ASC
");
$waiters = mysqli_fetch_all($usersQ, MYSQLI_ASSOC);

// Handle Start Shift
if (isset($_POST['start_shift'])) {
    $id = intval($_POST['user_id']);
    $password = $_POST['password'];

    // Validate password
    $q = mysqli_query($conn, "SELECT password_hash FROM users WHERE id=$id");
    $row = mysqli_fetch_assoc($q);

    if ($row && password_verify($password, $row['password_hash'])) {
        mysqli_query($conn, "
            UPDATE users 
            SET shift_active = 1, shift_started_at = NOW()
            WHERE id = $id
        ");
        header("Location: staff.php?started=1");
        exit;
    } else {
        header("Location: staff.php?error=password");
        exit;
    }
}

// Handle End Shift
if (isset($_POST['end_shift'])) {
    $id = intval($_POST['user_id']);
    $password = $_POST['password'];

    $q = mysqli_query($conn, "SELECT password_hash FROM users WHERE id=$id");
    $row = mysqli_fetch_assoc($q);

    if ($row && password_verify($password, $row['password_hash'])) {
        mysqli_query($conn, "
            UPDATE users 
            SET shift_active = 0, shift_started_at = NULL
            WHERE id = $id
        ");
        header("Location: staff.php?ended=1");
        exit;
    } else {
        header("Location: staff.php?error=password");
        exit;
    }
}
?>

<div class="container my-4">

    <?php if (isset($_GET['started'])): ?>
        <div class="alert alert-success">Shift started successfully!</div>
    <?php endif; ?>

    <?php if (isset($_GET['ended'])): ?>
        <div class="alert alert-warning">Shift ended.</div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] == 'password'): ?>
        <div class="alert alert-danger">Incorrect password!</div>
    <?php endif; ?>

    <h1 class="fw-bold mb-4">Staff Dashboard</h1>

    <div class="row">

        <?php foreach ($waiters as $w): ?>
            <div class="col-md-4 mb-3">
                <div class="card p-3 shadow-sm">

                    <h4 class="fw-bold mb-2"><?= htmlspecialchars($w['name']) ?></h4>

                    <?php if ($w['shift_active']): ?>
                        <span class="badge bg-success">Online</span>
                        <p class="text-muted mt-2">
                            Started: <?= $w['shift_started_at'] ?>
                        </p>
                    <?php else: ?>
                        <span class="badge bg-secondary">Offline</span>
                    <?php endif; ?>

                    <hr>

                    <!-- START SHIFT BUTTON -->
                    <?php if (!$w['shift_active']): ?>
                        <button 
                            class="btn btn-dark w-100"
                            onclick="openModal(<?= $w['id'] ?>, 'start')">
                            Start Shift
                        </button>
                    <?php endif; ?>

                    <!-- END SHIFT BUTTON -->
                    <?php if ($w['shift_active']): ?>
                        <button 
                            class="btn btn-danger w-100"
                            onclick="openModal(<?= $w['id'] ?>, 'end')">
                            End Shift
                        </button>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>

    </div>
</div>

<!-- PASSWORD MODAL -->
<div class="modal fade" id="pwModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      
      <div class="modal-header">
        <h5 id="pwModalTitle" class="modal-title">Enter Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
          <input type="hidden" name="user_id" id="modalUserId">
          <input type="password" name="password" class="form-control" placeholder="Password" required>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="pwModalButton" name="">Confirm</button>
      </div>

    </form>
  </div>
</div>

<script>
function openModal(userId, action) {
    document.getElementById("modalUserId").value = userId;

    if (action === 'start') {
        document.getElementById("pwModalTitle").innerText = "Start Shift";
        document.getElementById("pwModalButton").innerText = "Start";
        document.getElementById("pwModalButton").name = "start_shift";
    } else {
        document.getElementById("pwModalTitle").innerText = "End Shift";
        document.getElementById("pwModalButton").innerText = "End";
        document.getElementById("pwModalButton").name = "end_shift";
    }

    new bootstrap.Modal(document.getElementById("pwModal")).show();
}
</script>

<?php include "includes/footer.php"; ?>
