<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']); // π.χ. "tables.php"
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Deadline Diner</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">The Deadline Diner</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto gap-3">

                <!-- PUBLIC LINKS -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active fw-bold' : '' ?>" 
                       href="index.php">Home</a>
                </li>

                <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'staff.php' ? 'active fw-bold' : '' ?>" 
                           href="staff.php">Staff</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'tables.php' ? 'active fw-bold' : '' ?>" 
                       href="tables.php">Tables</a>
                </li>

                <!-- MANAGER LINKS -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager'): ?>

                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'menu.php' ? 'active fw-bold' : '' ?>" 
                           href="menu.php">Menu</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'orders.php' ? 'active fw-bold' : '' ?>" 
                           href="orders.php">Orders</a>
                    </li>
                <?php endif; ?>


                <!-- AUTH LINKS -->
                <?php if (!isset($_SESSION['user_id'])): ?>

                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'login.php' ? 'active fw-bold' : '' ?>"
                           href="login.php">Login</a>
                    </li>

                <?php else: ?>

                    <li class="nav-item d-flex align-items-center fw-bold">
                        <span class="navbar-text text-light">
                            <?= htmlspecialchars($_SESSION['user_name']) ?>
                            <small class="text-muted">(<?= htmlspecialchars($_SESSION['role']) ?>)</small>
                        </span>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>

                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<div class="page-wrapper container py-4">
