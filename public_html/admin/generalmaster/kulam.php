<?php
require "../../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

$page_title = "Kulam Master";
$page_icon  = "sitemap";
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

$totalRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM master_kulam");
$total    = mysqli_fetch_assoc($totalRes)['total'];
$pages    = ceil($total / $limit);

/* ---------- COMMUNITY LIST ---------- */
$communities = mysqli_query(
    $conn,
    "SELECT id, community_name 
     FROM master_community 
     WHERE status = 1 
     ORDER BY community_name"
);

/* ---------- EDIT ---------- */
$edit = null;
if (isset($_GET['edit'])) {
    $id   = (int)$_GET['edit'];
    $res  = mysqli_query($conn, "SELECT * FROM master_kulam WHERE id=$id");
    $edit = mysqli_fetch_assoc($res);
}

/* ---------- ADD / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $community_id = (int)$_POST['community_id'];
    $kulam_name   = mysqli_real_escape_string($conn, $_POST['kulam_name']);
    $status       = isset($_POST['status']) ? 1 : 0;

    if (!empty($_POST['id'])) {

        $id = (int)$_POST['id'];

        mysqli_query($conn,
            "UPDATE master_kulam 
             SET community_id=$community_id, kulam_name='$kulam_name', status=$status 
             WHERE id=$id"
        );

        $_SESSION['success'] = "Kulam updated successfully.";

    } else {

        mysqli_query($conn,
            "INSERT INTO master_kulam (community_id, kulam_name, status)
             VALUES ($community_id, '$kulam_name', $status)"
        );

        $_SESSION['success'] = "Kulam added successfully.";
    }

    header("Location: " . BASE_URL . "/admin/generalmaster/kulam?page=$page");
    exit;
}

/* ---------- DELETE (BLOCK IF USED) ---------- */
if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    // OPTIONAL: if users table exists
    $userUsed = 0;
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'users'")) > 0) {
        $checkUsers = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS cnt 
             FROM users 
             WHERE kulam_id = $id"
        );
        $userUsed = mysqli_fetch_assoc($checkUsers)['cnt'];
    }

    if ($userUsed > 0) {
        $_SESSION['error'] = "Cannot delete. Kulam is already used.";
    } else {
        mysqli_query($conn, "DELETE FROM master_kulam WHERE id=$id");
        $_SESSION['success'] = "Kulam deleted successfully.";
    }

    header("Location: " . BASE_URL . "/admin/generalmaster/kulam?page=$page");
    exit;
}

/* ---------- LIST ---------- */
$list = mysqli_query(
    $conn,
    "SELECT k.*, c.community_name
     FROM master_kulam k
     JOIN master_community c ON c.id = k.community_id
     ORDER BY k.id DESC
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
    max-width: 900px;
    flex-wrap: wrap;
}

.input, select {
    padding: 8px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
}

.input-text {
    flex: 1;
    min-width: 220px;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 13px;
}

.btn-primary { background:#3b82f6;color:#fff; }
.btn-edit    { background:#f59e0b;color:#fff; }
.btn-danger  { background:#ef4444;color:#fff; }

.alert {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 14px;
}

.alert-error { background:#fee2e2;color:#b91c1c; }
.alert-success { background:#dcfce7;color:#166534; }

.table {
    width: 100%;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

.table th, .table td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

.table th {
    background: #f9fafb;
    text-align: left;
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
    font-size: 13px;
    color: #111827;
}

.pagination a.active {
    background: #3b82f6;
    color: #fff;
    border-color: #3b82f6;
}
</style>

<?php if ($msg_error): ?>
    <div class="alert alert-error"><?= $msg_error ?></div>
<?php endif; ?>

<?php if ($msg_success): ?>
    <div class="alert alert-success"><?= $msg_success ?></div>
<?php endif; ?>

<!-- ADD / EDIT FORM -->
<form method="post" class="form-inline">
    <?php if ($edit): ?>
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
    <?php endif; ?>

    <select name="community_id" required>
        <option value="">Select Community</option>
        <?php
        mysqli_data_seek($communities, 0);
        while ($c = mysqli_fetch_assoc($communities)):
        ?>
            <option 
                value="<?= $c['id'] ?>"
                <?= ($edit && $edit['community_id'] == $c['id']) ? 'selected' : '' ?>
            >
                <?= htmlspecialchars($c['community_name']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <input 
        class="input input-text"
        name="kulam_name"
        placeholder="Kulam Name"
        required
        value="<?= $edit['kulam_name'] ?? '' ?>"
    >

    <label>
        <input 
            type="checkbox" 
            name="status"
            <?= (!isset($edit) || $edit['status'] == 1) ? 'checked' : '' ?>
        >
        Active
    </label>

    <button class="btn btn-primary">
        <?= $edit ? "Update" : "Add" ?>
    </button>

    <?php if ($edit): ?>
        <a 
            href="<?= BASE_URL ?>/admin/generalmaster/kulam?page=<?= $page ?>"
            class="btn btn-edit"
        >Cancel</a>
    <?php endif; ?>
</form>

<!-- LIST -->
<table class="table">
    <tr>
        <th width="60">ID</th>
        <th>Community</th>
        <th>Kulam</th>
        <th width="120">Status</th>
        <th width="160">Action</th>
    </tr>

    <?php while ($row = mysqli_fetch_assoc($list)): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['community_name']) ?></td>
            <td><?= htmlspecialchars($row['kulam_name']) ?></td>
            <td><?= $row['status'] ? 'Active' : 'Inactive' ?></td>
            <td>
                <a 
                    href="<?= BASE_URL ?>/admin/generalmaster/kulam?edit=<?= $row['id'] ?>&page=<?= $page ?>"
                    class="btn btn-edit"
                >Edit</a>

                <a 
                    href="<?= BASE_URL ?>/admin/generalmaster/kulam?delete=<?= $row['id'] ?>&page=<?= $page ?>"
                    class="btn btn-danger"
                    onclick="return confirm('Delete this kulam?')"
                >Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

<!-- PAGINATION -->
<div class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a 
            href="<?= BASE_URL ?>/admin/generalmaster/kulam?page=<?= $i ?>"
            class="<?= ($i == $page) ? 'active' : '' ?>"
        ><?= $i ?></a>
    <?php endfor; ?>
</div>

<?php
$content = ob_get_clean();
require "../layout.php";
