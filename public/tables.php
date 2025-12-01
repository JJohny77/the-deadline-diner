<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

$isManager = isset($_SESSION['role']) && $_SESSION['role'] === "manager";

// ===============================
// AJAX: SAVE POSITION (DRAG & DROP)
// ===============================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'save_position' &&
    $isManager
) {
    $tableId = isset($_POST['table_id']) ? (int)$_POST['table_id'] : 0;
    $x       = isset($_POST['x']) ? (int)$_POST['x'] : 0;
    $y       = isset($_POST['y']) ? (int)$_POST['y'] : 0;

    if ($tableId > 0) {
        mysqli_query($conn, "
            UPDATE tables
            SET pos_x = $x, pos_y = $y
            WHERE id = $tableId
        ");
        echo "OK";
    } else {
        echo "ERROR";
    }
    exit; // πολύ σημαντικό για το AJAX
}

// ===============================
// HANDLE ADD / EDIT / DELETE TABLE (όπως πριν)
// ===============================
if ($isManager && isset($_POST['add_table'])) {
    $name  = mysqli_real_escape_string($conn, trim($_POST['name']));
    $seats = (int) $_POST['seats'];

    if ($name !== "" && $seats > 0) {
        mysqli_query($conn, "
            INSERT INTO tables (name, seats, status, pos_x, pos_y)
            VALUES ('$name', $seats, 'free', 50, 50)
        ");
        $added = true;
    }
}

if ($isManager && isset($_POST['edit_table'])) {
    $id    = (int) $_POST['id'];
    $name  = mysqli_real_escape_string($conn, trim($_POST['name']));
    $seats = (int) $_POST['seats'];

    if ($id > 0 && $name !== "" && $seats > 0) {
        mysqli_query($conn, "
            UPDATE tables
            SET name = '$name', seats = $seats
            WHERE id = $id
        ");
        $edited = true;
    }
}

if ($isManager && isset($_POST['delete_table'])) {
    $id = (int) $_POST['id'];

    if ($id > 0) {
        // καθάρισε assigned_waiter_id για αυτό το τραπέζι (προαιρετικό)
        mysqli_query($conn, "
            UPDATE tables
            SET assigned_waiter_id = NULL
            WHERE id = $id
        ");

        mysqli_query($conn, "DELETE FROM tables WHERE id = $id");
        $deleted = true;
    }
}

// ===============================
// FETCH ALL TABLES
// ===============================
$query  = "SELECT * FROM tables ORDER BY id ASC";
$result = mysqli_query($conn, $query);
$tables = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="fw-bold">Tables Floor Plan</h1>

    <?php if ($isManager): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTableModal">
            + Add Table
        </button>
    <?php endif; ?>
</div>

<?php if (!empty($added)): ?>
    <div class="alert alert-success">Table added successfully!</div>
<?php endif; ?>

<?php if (!empty($edited)): ?>
    <div class="alert alert-info">Table updated successfully!</div>
<?php endif; ?>

<?php if (!empty($deleted)): ?>
    <div class="alert alert-danger">Table deleted.</div>
<?php endif; ?>

<div class="mb-3 table-legend">
    <span class="badge bg-success">Free</span>
    <span class="badge bg-danger">Occupied</span>
    <span class="badge bg-warning text-dark">Reserved</span>
</div>

<div class="restaurant-floor" id="restaurantFloor">
    <?php foreach ($tables as $t): ?>
        <?php
            $id    = (int) $t['id'];
            $name  = $t['name'];
            $seats = (int) $t['seats'];

            // default positions αν είναι null
            $posX = isset($t['pos_x']) ? (int)$t['pos_x'] : 20 + ($id % 5) * 120;
            $posY = isset($t['pos_y']) ? (int)$t['pos_y'] : 20 + (int)($id / 5) * 120;

            // διάμετρος με βάση τα seats
            if ($seats <= 2) {
                $diameter = 60;
            } elseif ($seats <= 4) {
                $diameter = 80;
            } elseif ($seats <= 6) {
                $diameter = 100;
            } else {
                $diameter = 120;
            }

            // χρώμα με βάση status
            $bgColor = '#6c757d';
            if ($t['status'] === 'free') {
                $bgColor = '#198754'; // green
            } elseif ($t['status'] === 'occupied') {
                $bgColor = '#dc3545'; // red
            } elseif ($t['status'] === 'reserved') {
                $bgColor = '#ffc107'; // yellow
            }
        ?>

        <div 
            class="table-item"
            data-id="<?= $id ?>"
            data-can-drag="<?= $isManager ? '1' : '0' ?>"
            style="
                left: <?= $posX ?>px;
                top: <?= $posY ?>px;
                width: <?= $diameter ?>px;
                height: <?= $diameter ?>px;
                background: <?= $bgColor ?>;
            "
        >
            <div class="table-label">
                <?= htmlspecialchars($name) ?><br>
                <small><?= $seats ?> seats</small>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($isManager && count($tables) > 0): ?>
    <h3 class="fw-bold mt-4 mb-3">Manage Tables</h3>
    <div class="table-responsive mb-5">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Seats</th>
                    <th>Status</th>
                    <th>Position</th>
                    <th style="width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tables as $t): ?>
                    <tr>
                        <td><?= (int)$t['id'] ?></td>
                        <td><?= htmlspecialchars($t['name']) ?></td>
                        <td><?= (int)$t['seats'] ?></td>
                        <td><?= htmlspecialchars(ucfirst($t['status'])) ?></td>
                        <td>
                            (<?= (int)$t['pos_x'] ?>, <?= (int)$t['pos_y'] ?>)
                        </td>
                        <td>
                            <button 
                                class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editTableModal"
                                data-id="<?= (int)$t['id'] ?>"
                                data-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>"
                                data-seats="<?= (int)$t['seats'] ?>"
                            >
                                Edit
                            </button>

                            <form method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete this table;');">
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                <button name="delete_table" class="btn btn-sm btn-outline-danger">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if (isset($_GET['cancelled'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Order cancelled.</strong> The order has been removed.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ================= ADD TABLE MODAL ================= -->
<div class="modal fade" id="addTableModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Add New Table</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" required placeholder="Table 1">
            </div>
            <div class="mb-3">
                <label class="form-label">Seats</label>
                <input type="number" name="seats" class="form-control" min="1" value="2" required>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" name="add_table">Add</button>
        </div>
    </form>
  </div>
</div>

<!-- ================= EDIT TABLE MODAL ================= -->
<div class="modal fade" id="editTableModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Edit Table</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <input type="hidden" name="id" id="editTableId">

            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" id="editTableName" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Seats</label>
                <input type="number" name="seats" id="editTableSeats" class="form-control" min="1" required>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" name="edit_table">Save</button>
        </div>
    </form>
  </div>
</div>

<script>
// πιο καθαρό JS για drag & drop και open table
document.addEventListener('DOMContentLoaded', function () {
    const floor = document.getElementById('restaurantFloor');
    if (!floor) return;

    const tables = document.querySelectorAll('.table-item');

    tables.forEach(function (el) {
        const id = el.dataset.id;
        const canDrag = el.dataset.canDrag === '1';

        // Άνοιγμα table page:
        if (canDrag) {
            el.addEventListener('dblclick', function () {
                window.location = 'table.php?id=' + id;
            });
        } else {
            el.addEventListener('click', function () {
                window.location = 'table.php?id=' + id;
            });
        }

        if (!canDrag) return; // μόνο ο manager μπορεί να κάνει drag

        let isDragging = false;

        el.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return; // μόνο left click
            e.preventDefault();

            const rectFloor = floor.getBoundingClientRect();
            const rectEl = el.getBoundingClientRect();

            const shiftX = e.clientX - rectEl.left;
            const shiftY = e.clientY - rectEl.top;

            function moveAt(pageX, pageY) {
                let x = pageX - rectFloor.left - shiftX;
                let y = pageY - rectFloor.top - shiftY;

                // Όρια μέσα στο floor
                x = Math.max(0, Math.min(x, floor.clientWidth - el.offsetWidth));
                y = Math.max(0, Math.min(y, floor.clientHeight - el.offsetHeight));

                el.style.left = x + 'px';
                el.style.top = y + 'px';
            }

            function onMouseMove(eMove) {
                isDragging = true;
                moveAt(eMove.pageX, eMove.pageY);
            }

            document.addEventListener('mousemove', onMouseMove);

            function onMouseUp() {
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);

                if (isDragging) {
                    // Αποθήκευση θέσης στο backend
                    const formData = new FormData();
                    formData.append('action', 'save_position');
                    formData.append('table_id', id);
                    formData.append('x', parseInt(el.style.left, 10));
                    formData.append('y', parseInt(el.style.top, 10));

                    fetch('tables.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });
                }

                isDragging = false;
            }

            document.addEventListener('mouseup', onMouseUp);
        });

        el.ondragstart = function () {
            return false;
        };
    });

    // fill edit modal from row buttons
    const editModal = document.getElementById('editTableModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            const id    = button.getAttribute('data-id');
            const name  = button.getAttribute('data-name');
            const seats = button.getAttribute('data-seats');

            document.getElementById('editTableId').value    = id;
            document.getElementById('editTableName').value  = name;
            document.getElementById('editTableSeats').value = seats;
        });
    }
    // ============================
    //  TOUCH DRAG SUPPORT (Mobile)
    // ============================
    tables.forEach(function (el) {
        const canDrag = el.dataset.canDrag === '1';
        if (!canDrag) return;

        el.addEventListener('touchstart', function (e) {
            const touch = e.touches[0];

            const rectFloor = floor.getBoundingClientRect();
            const rectEl = el.getBoundingClientRect();

            const shiftX = touch.clientX - rectEl.left;
            const shiftY = touch.clientY - rectEl.top;

            function moveAt(touchEvent) {
                const t = touchEvent.touches[0];
                let x = t.clientX - rectFloor.left - shiftX;
                let y = t.clientY - rectFloor.top - shiftY;

                // Όρια για να μην φεύγει εκτός
                x = Math.max(0, Math.min(x, floor.clientWidth - el.offsetWidth));
                y = Math.max(0, Math.min(y, floor.clientHeight - el.offsetHeight));

                el.style.left = x + 'px';
                el.style.top = y + 'px';
            }

            function onTouchMove(eMove) {
                moveAt(eMove);
            }

            document.addEventListener('touchmove', onTouchMove);

            function onTouchEnd() {
                document.removeEventListener('touchmove', onTouchMove);
                document.removeEventListener('touchend', onTouchEnd);

                // Save position after drag end
                const formData = new FormData();
                formData.append('action', 'save_position');
                formData.append('table_id', el.dataset.id);
                formData.append('x', parseInt(el.style.left, 10));
                formData.append('y', parseInt(el.style.top, 10));

                fetch('tables.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
            }

            document.addEventListener('touchend', onTouchEnd);
        });
    });
});
</script>

<?php include "includes/footer.php"; ?>
