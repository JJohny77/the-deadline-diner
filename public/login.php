<?php
include "includes/db.php";

// Ξεκίνα session ΑΝ δεν τρέχει ήδη
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Αν είναι ήδη logged in, στείλ' τον στην αρχική
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '') {
        $errors[] = "Email is required.";
    }
    if ($password === '') {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $emailEsc = mysqli_real_escape_string($conn, $email);

        $q = mysqli_query($conn, "
            SELECT * 
            FROM users 
            WHERE email = '$emailEsc'
            LIMIT 1
        ");

        if ($q && mysqli_num_rows($q) === 1) {
            $user = mysqli_fetch_assoc($q);

            if (password_verify($password, $user['password_hash'])) {
                // Επιτυχής login → σώζουμε στα session
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role']      = $user['role'];

                header("Location: index.php");
                exit;
            } else {
                $errors[] = "Wrong email or password.";
            }
        } else {
            $errors[] = "Wrong email or password.";
        }
    }
}

include "includes/header.php";
?>

<div class="container my-5" style="max-width: 480px;">
    <h1 class="fw-bold mb-4 text-center">Login</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                • <?= htmlspecialchars($e) ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label fw-bold">Email</label>
            <input 
                type="email" 
                name="email" 
                class="form-control" 
                required 
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            >
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Password</label>
            <input 
                type="password" 
                name="password" 
                class="form-control" 
                required
            >
        </div>

        <button class="btn btn-dark w-100 mt-2">Login</button>
    </form>
</div>

<?php include "includes/footer.php"; ?>
