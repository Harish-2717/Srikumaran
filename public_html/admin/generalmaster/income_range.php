<?php
require "../../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

$page_title = "Income Range";
$page_icon  = "money-bill";
$active     = "masters";

/* ---------- FLASH MESSAGE ---------- */
$msg_error   = $_SESSION['error'] ?? '';
$msg_success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

/* ---------- PAGINATION ---------- */
$limit  = 10;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page   = ($page < 1) ? 1 : $page;
$offset = ($page - 1) * $limit;

$totalRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM master_income_range");
$total    = mysqli_fetch_assoc($totalRes)['total'];
$pages    = ceil($total / $limit);

/* ---------- EDIT ---------- */
$edit = null;
if (isset($_GET['edit'])) {
    $id   = (int)$_GET['edit'];
    $res  = mysqli_query($conn, "SELECT * FROM master_income_range WHERE id=$id");
    $edit = mysqli_fetch_assoc($res);
}

/* ---------- ADD / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $income_from = (int)$_POST['income_from'];
    $income_to   = (int)$_POST['income_to'];
    $status      = isset($_POST['status']) ? 1 : 0;

    if ($income_from < 0 || $income_to <= 0 || $income_from >= $income_to) {
        $_SESSION['error'] = "Invalid income range.";
        header("Location: " . BASE_URL . "/admin/generalmaster/income_range");
        exit;
    }

    if (!empty($_POST['id'])) {

        $id = (int)$_POST['id'];

        mysqli_query($conn,
            "UPDATE master_income_range
             SET income_from=$income_from,
                 income_to=$income_to,
                 status=$status
             WHERE id=$id"
        );

        $_SESSION['success'] = "Income range updated successfully.";

    } else {

        mysqli_query($conn,
            "INSERT INTO master_income_range (income_from, income_to, status)
             VALUES ($income_from, $income_to, $status)"
        );

        $_SESSION['success'] = "Income range added successfully.";
    }

    header("Location: " . BASE_URL . "/admin/generalmaster/income_range?page=$page");
    exit;
}

/* ---------- DELETE (BLOCK IF USED) ---------- */
if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    // OPTIONAL: if users table exists
    $used = 0;
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'users'")) > 0) {
        $check = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS cnt FROM users WHERE income_range_id=$id"
        );
        $used = mysqli_fetch_assoc($check)['cnt'];
    }

    if ($used > 0) {
        $_SESSION['error'] = "Cannot delete. Income range is already used.";
    } else {
        mysqli_query($conn, "DELETE FROM master_income_range WHERE id=$id");
        $_SESSION['success'] = "Income range deleted successfully.";
    }

    header("Location: " . BASE_URL . "/admin/generalmaster/income_range?page=$page");
    exit;
}

/* ---------- LIST ---------- */
$list = mysqli_query(
    $conn,
    "SELECT * FROM master_income_range
     ORDER BY income_from ASC
     LIMIT $limit OFFSET $offset"
);

ob_start();
?>

<style>
.form-inline {
    display: flex;
    gap: 12px;
    align-items: center;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
    max-width: 800px;
}

.input {
    padding: 8px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    width: 140px;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
}

.btn-primary { background:#3b82f6;color:#fff; }
.btn-edit    { background:#f59e0b;color:#fff; }
.btn-danger  { background:#ef4444;color:#fff; }

.table {
    width: 100%;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

.table th, .table td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.pagination {
    margin-top: 20px;
    display: flex;
    gap: 6px;
}

.pagination a {
    padding: 6px 10px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    text-decoration: none;
}

.pagination a.active {
    background: #3b82f6;
    color: #fff;
}
</style>

<?php if ($msg_error): ?><div class="alert alert-error"><?= $msg_error ?></div><?php endif; ?>
<?php if ($msg_success): ?><div class="alert alert-success"><?= $msg_success ?></div><?php endif; ?>

<!-- ADD / EDIT -->
<form method="post" class="form-inline">
    <?php if ($edit): ?>
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
    <?php endif; ?>

    <input class="input" type="number" name="income_from" placeholder="Income From" required value="<?= $edit['income_from'] ?? '' ?>">
    <input class="input" type="number" name="income_to" placeholder="Income To" required value="<?= $edit['income_to'] ?? '' ?>">

    <label>
        <input type="checkbox" name="status" <?= (!isset($edit) || $edit['status']) ? 'checked' : '' ?>>
        Active
    </label>

    <button class="btn btn-primary"><?= $edit ? "Update" : "Add" ?></button>

    <?php if ($edit): ?>
        <a href="<?= BASE_URL ?>/admin/generalmaster/income_range" class="btn btn-edit">Cancel</a>
    <?php endif; ?>
</form>

<!-- LIST -->
<table class="table">
<tr>
    <th>ID</th>
    <th>Income Range</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php while ($row = mysqli_fetch_assoc($list)): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td>₹ <?= number_format($row['income_from']) ?> – <?= number_format($row['income_to']) ?></td>
    <td><?= $row['status'] ? 'Active' : 'Inactive' ?></td>
    <td>
        <a href="<?= BASE_URL ?>/admin/generalmaster/income_range?edit=<?= $row['id'] ?>" class="btn btn-edit">Edit</a>
        <a href="<?= BASE_URL ?>/admin/generalmaster/income_range?delete=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete income range?')">Delete</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

<div class="pagination">
<?php for ($i=1; $i<=$pages; $i++): ?>
    <a href="<?= BASE_URL ?>/admin/generalmaster/income_range?page=<?= $i ?>" class="<?= ($i==$page)?'active':'' ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

<?php
$content = ob_get_clean();
require "../layout.php";
