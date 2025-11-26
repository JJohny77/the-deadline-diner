<?php
// --- Προστασία σελίδας (μόνο manager) ---
require_once "includes/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    // Αν δεν είναι manager, τον πετάμε στο login
    header("Location: login.php");
    exit;
}

// --- Φέρνουμε όλα τα users από τη βάση ---
$usersQ = mysqli_query(
    $conn,
    "SELECT id, name, email, role, created_at 
     FROM users 
     ORDER BY role DESC, name ASC"
);

$managers = [];
$staff    = [];

if ($usersQ && mysqli_num_rows($usersQ) > 0) {
    while ($u = mysqli_fetch_assoc($usersQ)) {
        if ($u['role'] === 'manager') {
            $managers[] = $u;
        } else {
            $staff[] = $u;
        }
    }
}

include "includes/header.php";
?>

<h1 class="fw-bold mb-4">Staff Dashboard</h1>
<p class="text-muted mb-4">
    Overview of all staff members in <strong>The Deadline Diner</strong>.
</p>

<!-- MANAGERS SECTION -->
<?php if (count($managers) > 0): ?>
    <h4 class="mb-3">Management</h4>
    <div class="row g-3 mb-4">
        <?php foreach ($managers as $user): ?>
            <div class="col-md-4">
                <div class="card table-card shadow-sm h-100">
                    <div class="card-body">
                        <span class="badge bg-primary mb-2">Manager</span>
                        <h5 class="card-title mb-1">
                            <?= htmlspecialchars($user['name']) ?>
                        </h5>
                        <p class="card-text text-muted mb-2">
                            <?= htmlspecialchars($user['email']) ?>
                        </p>
                        <p class="card-text">
                            <small class="text-muted">
                                Joined: <?= htmlspecialchars($user['created_at']) ?>
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- STAFF SECTION -->
<h4 class="mb-3">Waiters / Staff</h4>

<?php if (count($staff) === 0): ?>
    <div class="alert alert-info">
        No staff members found yet.  
        You can insert them manually in the <code>users</code> table for τώρα.
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($staff as $user): ?>
            <div class="col-md-4">
                <div class="card table-card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-secondary mb-2">Staff</span>

                        <h5 class="card-title mb-1">
                            <?= htmlspecialchars($user['name']) ?>
                        </h5>

                        <p class="card-text text-muted mb-2">
                            <?= htmlspecialchars($user['email']) ?>
                        </p>

                        <p class="card-text mt-auto">
                            <small class="text-muted">
                                Joined: <?= htmlspecialchars($user['created_at']) ?>
                            </small>
                        </p>

                        <!-- Μελλοντικά εδώ μπορούμε να βάλουμε Edit / Deactivate κουμπιά -->
                        <!--
                        <div class="mt-2 d-flex gap-2">
                            <a href="#" class="btn btn-sm btn-outline-dark">Edit</a>
                            <a href="#" class="btn btn-sm btn-outline-danger">Deactivate</a>
                        </div>
                        -->
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>
