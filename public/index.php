<?php 
include "includes/header.php"; 
include "includes/db.php";

// -------------------------
// BASIC ROLE / USER INFO
// -------------------------
$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $isLoggedIn ? $_SESSION['user_name'] : null;
$userRole   = $isLoggedIn ? $_SESSION['role']      : null;

$isManager  = ($userRole === 'manager');
$isWaiter   = ($userRole === 'waiter');

// -------------------------
// SMALL HELPER FOR COUNTS
// -------------------------
function dd_get_count(mysqli $conn, string $sql): int {
    $res = mysqli_query($conn, $sql);
    if (!$res) return 0;
    $row = mysqli_fetch_assoc($res);
    return isset($row['c']) ? (int)$row['c'] : 0;
}

// -------------------------
// GLOBAL STATS
// -------------------------
$totalTables     = dd_get_count($conn, "SELECT COUNT(*) AS c FROM tables");
$occupiedTables  = dd_get_count($conn, "SELECT COUNT(*) AS c FROM tables WHERE status = 'occupied'");
$freeTables      = dd_get_count($conn, "SELECT COUNT(*) AS c FROM tables WHERE status = 'free'");
$pendingOrders   = dd_get_count($conn, "SELECT COUNT(*) AS c FROM orders WHERE status = 'pending'");
$waitersOnline   = dd_get_count($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'waiter' AND shift_active = 1");
$todaysOrders    = dd_get_count($conn, "SELECT COUNT(*) AS c FROM orders WHERE DATE(created_at) = CURDATE()");

// -------------------------
// EXTRA INFO FOR WAITER
// -------------------------
$myTables   = [];
$myOrders   = [];

if ($isWaiter && $isLoggedIn) {
    $waiterId = (int)$_SESSION['user_id'];

    // Τραπέζια που είναι assigned σε αυτόν τον σερβιτόρο
    $qTables = mysqli_query($conn, "
        SELECT id, name, status, seats
        FROM tables
        WHERE assigned_waiter_id = $waiterId
        ORDER BY name ASC
    ");
    if ($qTables) {
        $myTables = mysqli_fetch_all($qTables, MYSQLI_ASSOC);
    }

    // Pending orders για τα δικά του τραπέζια
    $qOrders = mysqli_query($conn, "
        SELECT o.id, o.table_id, o.created_at, t.name AS table_name
        FROM orders o
        JOIN tables t ON t.id = o.table_id
        WHERE o.status = 'pending'
          AND t.assigned_waiter_id = $waiterId
        ORDER BY o.created_at ASC
        LIMIT 6
    ");
    if ($qOrders) {
        $myOrders = mysqli_fetch_all($qOrders, MYSQLI_ASSOC);
    }
}

// -------------------------
// EXTRA INFO FOR MANAGER: STAFF ONLINE
// -------------------------
$onlineStaff = [];
if ($isManager) {
    $qStaff = mysqli_query($conn, "
        SELECT name, shift_active, shift_started_at
        FROM users
        WHERE role = 'waiter'
        ORDER BY name ASC
    ");
    if ($qStaff) {
        $onlineStaff = mysqli_fetch_all($qStaff, MYSQLI_ASSOC);
    }
}

// -------------------------
// RECENT ACTIVITY (LAST ORDERS)
// -------------------------
$recentActivity = [];
$qActivity = mysqli_query($conn, "
    SELECT 
        o.id,
        o.table_id,
        o.status,
        o.created_at,
        t.name AS table_name
    FROM orders o
    JOIN tables t ON t.id = o.table_id
    ORDER BY o.created_at DESC
    LIMIT 8
");
if ($qActivity) {
    $recentActivity = mysqli_fetch_all($qActivity, MYSQLI_ASSOC);
}
?>

<!-- HERO / WELCOME SECTION -->
<div class="dashboard-hero card shadow-sm mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <p class="text-uppercase text-muted small mb-1 letter-spaced">
                Restaurant Control Center
            </p>

            <?php if ($isLoggedIn): ?>
                <h1 class="display-6 fw-bold mb-2">
                    Welcome back, <?= htmlspecialchars($userName) ?>!
                </h1>
                <p class="text-muted mb-0">
                    You are logged in as 
                    <strong><?= htmlspecialchars(ucfirst($userRole)) ?></strong>.
                    <?php if ($isWaiter): ?>
                        Use this page to quickly see your tables and pending orders.
                    <?php elseif ($isManager): ?>
                        Monitor tables, staff, and orders at a glance.
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <h1 class="display-6 fw-bold mb-2">
                    Welcome to The Deadline Diner
                </h1>
                <p class="text-muted mb-0">
                    Log in to manage tables, staff shifts, and live orders.
                </p>
            <?php endif; ?>
        </div>

        <div class="col-md-4 mt-3 mt-md-0">
            <div class="d-flex flex-md-column justify-content-md-end gap-2 flex-wrap">
                <a href="tables.php" class="btn btn-dark px-4">
                    🍽 Go to Tables
                </a>

                <?php if ($isLoggedIn): ?>
                    <a href="orders.php" class="btn btn-outline-secondary px-4">
                        🧾 View Orders
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-secondary px-4">
                        🔐 Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- STATS CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <p class="stat-label">Active Tables</p>
            <p class="stat-value"><?= $occupiedTables ?></p>
            <p class="stat-sub">
                <?= $totalTables ?> total · <?= $freeTables ?> free
            </p>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <p class="stat-label">Pending Orders</p>
            <p class="stat-value"><?= $pendingOrders ?></p>
            <p class="stat-sub">Awaiting preparation or serving</p>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <p class="stat-label">Waiters Online</p>
            <p class="stat-value"><?= $waitersOnline ?></p>
            <p class="stat-sub">Currently on active shift</p>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <p class="stat-label">Today’s Orders</p>
            <p class="stat-value"><?= $todaysOrders ?></p>
            <p class="stat-sub">Created since midnight</p>
        </div>
    </div>
</div>

<!-- QUICK ACTION CARDS -->
<h2 class="section-title mb-3">Quick Access</h2>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="home-card">
            <div class="home-card-icon bg-icon-blue">
                🍽
            </div>
            <h4 class="home-card-title">Manage Tables</h4>
            <p class="home-card-text">
                View table availability, status, and assignments from the live floor plan.
            </p>
            <a href="tables.php" class="btn btn-dark w-100">Go to Tables</a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="home-card">
            <div class="home-card-icon bg-icon-green">
                👥
            </div>
            <h4 class="home-card-title">Staff Dashboard</h4>
            <p class="home-card-text">
                See waiter shifts, who is online, and control shift start & end.
            </p>
            <a href="staff.php" class="btn btn-dark w-100">Open Staff Dashboard</a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="home-card">
            <div class="home-card-icon bg-icon-orange">
                🧾
            </div>
            <h4 class="home-card-title">Orders & Kitchen Flow</h4>
            <p class="home-card-text">
                Monitor pending, served, and refunded orders in one central view.
            </p>
            <a href="orders.php" class="btn btn-dark w-100">View Orders</a>
        </div>
    </div>
</div>

<?php if ($isManager): ?>
    <!-- MANAGER-ONLY SECTION -->
    <h2 class="section-title mt-4 mb-3">Manager Overview</h2>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="home-card">
                <h5 class="home-card-title mb-3">Online Staff</h5>

                <?php if (empty($onlineStaff)): ?>
                    <p class="text-muted mb-0">No waiters registered yet.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($onlineStaff as $w): ?>
                            <li class="d-flex justify-content-between align-items-center mb-2">
                                <span>
                                    <strong><?= htmlspecialchars($w['name']) ?></strong>
                                    <?php if ($w['shift_active']): ?>
                                        <span class="badge rounded-pill bg-success ms-2">Online</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-secondary ms-2">Offline</span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($w['shift_active'] && !empty($w['shift_started_at'])): ?>
                                    <small class="text-muted">
                                        since <?= htmlspecialchars($w['shift_started_at']) ?>
                                    </small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="home-card">
                <h5 class="home-card-title mb-3">Shortcuts</h5>
                <div class="d-grid gap-2">
                    <a href="menu.php" class="btn btn-outline-dark">
                        🍽 Manage Menu
                    </a>
                    <a href="tables.php" class="btn btn-outline-dark">
                        🧩 Edit Floor Plan
                    </a>
                    <a href="staff.php" class="btn btn-outline-dark">
                        👥 Manage Waiters & Shifts
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isWaiter): ?>
    <!-- WAITER-ONLY SECTIONS -->
    <div class="row g-3 mt-4 mb-4">
        <div class="col-lg-6">
            <h2 class="section-title mb-3">Your Tables</h2>
            <div class="home-card">
                <?php if (empty($myTables)): ?>
                    <p class="text-muted mb-0">
                        You currently have no tables assigned.  
                        Ask the manager to assign tables to you from the Tables or Staff pages.
                    </p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($myTables as $t): ?>
                            <?php
                                $badgeClass = 'bg-secondary';
                                if ($t['status'] === 'free')          $badgeClass = 'bg-success';
                                elseif ($t['status'] === 'occupied') $badgeClass = 'bg-danger';
                                elseif ($t['status'] === 'reserved') $badgeClass = 'bg-warning text-dark';
                            ?>
                            <a href="table.php?id=<?= (int)$t['id'] ?>"
                               class="btn btn-sm btn-outline-dark waiter-table-chip">
                                <?= htmlspecialchars($t['name']) ?>
                                <span class="badge <?= $badgeClass ?> ms-1">
                                    <?= htmlspecialchars(ucfirst($t['status'])) ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-6">
            <h2 class="section-title mb-3">Your Pending Orders</h2>
            <div class="home-card">
                <?php if (empty($myOrders)): ?>
                    <p class="text-muted mb-0">
                        You have no pending orders right now.  
                        New orders assigned to your tables will appear here.
                    </p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($myOrders as $o): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>#<?= (int)$o['id'] ?></strong>
                                    — <?= htmlspecialchars($o['table_name']) ?>
                                    <br>
                                    <small class="text-muted">
                                        Created: <?= htmlspecialchars($o['created_at']) ?>
                                    </small>
                                </div>
                                <a href="table.php?id=<?= (int)$o['table_id'] ?>"
                                   class="btn btn-sm btn-outline-dark">
                                    View table
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- RECENT ACTIVITY (COMMON) -->
<h2 class="section-title mt-4 mb-3">Recent Activity</h2>
<div class="home-card mb-4">
    <?php if (empty($recentActivity)): ?>
        <p class="text-muted mb-0">No activity recorded yet.</p>
    <?php else: ?>
        <ul class="list-unstyled mb-0 recent-activity-list">
            <?php foreach ($recentActivity as $a): ?>
                <?php
                    $icon  = '🧾';
                    $label = ucfirst($a['status']);
                    if ($a['status'] === 'pending')  $icon = '⏳';
                    if ($a['status'] === 'served')   $icon = '✅';
                    if ($a['status'] === 'refunded') $icon = '↩️';
                ?>
                <li class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="me-2"><?= $icon ?></span>
                        <strong>Order #<?= (int)$a['id'] ?></strong>
                        <span class="text-muted">
                            — <?= htmlspecialchars($a['table_name']) ?> (<?= htmlspecialchars($label) ?>)
                        </span>
                    </div>
                    <small class="text-muted"><?= htmlspecialchars($a['created_at']) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>
