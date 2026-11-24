<?php
require "../../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

$page_title = "Community Master";
$page_icon  = "users";
$active     = "masters";

/* ---------- FLASH MESSAGE ---------- */
$msg_error = $_SESSION['error'] ?? '';
$msg_success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

/* ---------- PAGINATION ---------- */
$limit  = 10;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page   = ($page < 1) ? 1 : $page;
$offset = ($page - 1) * $limit;

$totalRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM master_community");
$total    = mysqli_fetch_assoc($totalRes)['total'];
$pages    = ceil($total / $limit);

/* ---------- EDIT ---------- */
$edit = null;
if (isset($_GET['edit'])) {
    $id   = (int)$_GET['edit'];
    $res  = mysqli_query($conn, "SELECT * FROM master_community WHERE id=$id");
    $edit = mysqli_fetch_assoc($res);
}

/* ---------- ADD / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $community_name = mysqli_real_escape_string($conn, $_POST['community_name']);
    $status         = isset($_POST['status']) ? 1 : 0;

    if (!empty($_POST['id'])) {

        $id = (int)$_POST['id'];

        // Update community
        mysqli_query($conn,
            "UPDATE master_community 
             SET community_name='$community_name', status=$status 
             WHERE id=$id"
        );

        // If community becomes inactive → inactivate all kulams
        if ($status == 0) {
            mysqli_query($conn,
                "UPDATE master_kulam 
                 SET status = 0 
                 WHERE community_id = $id"
            );
        }

        $_SESSION['success'] = "Community updated successfully.";

    } else {

        mysqli_query($conn,
            "INSERT INTO master_community (community_name, status)
             VALUES ('$community_name', $status)"
        );

        $_SESSION['success'] = "Community added successfully.";
    }

    header("Location: " . BASE_URL . "/admin/generalmaster/community?page=$page");
    exit;
}

/* ---------- DELETE (BLOCK IF USED) ---------- */
if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    // Check kulam usage
    $checkKulam = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS cnt 
         FROM master_kulam 
         WHERE community_id = $id"
    );
    $kulamUsed = mysqli_fetch_assoc($checkKulam)['cnt'];

    // OPTIONAL: if users table exists
    $userUsed = 0;
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'users'")) > 0) {
        $checkUsers = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS cnt 
             FROM users 
             WHERE community_id = $id"
        );
        $userUsed = mysqli_fetch_assoc($checkUsers)['cnt'];
    }

    if ($kulamUsed > 0 || $userUsed > 0) {
        $_SESSION['error'] = "Cannot delete. Community is already in use.";
    } else {
        mysqli_query($conn, "DELETE FROM master_community WHERE id=$id");
        $_SESSION['success'] = "Community deleted successfully.";
    }

    header("Location: " . BASE_URL . "/admin/generalmaster/community?page=$page");
    exit;
}

/* ---------- LIST ---------- */
$list = mysqli_query(
    $conn,
    "SELECT * FROM master_community 
     ORDER BY id DESC 
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
    max-width: 700px;
}

.input {
    padding: 8px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
}

.input-text {
    flex: 1;
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

    <input 
        class="input input-text"
        name="community_name"
        placeholder="Community Name"
        required
        value="<?= $edit['community_name'] ?? '' ?>"
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
            href="<?= BASE_URL ?>/admin/generalmaster/community?page=<?= $page ?>"
            class="btn btn-edit"
        >Cancel</a>
    <?php endif; ?>
</form>

<!-- LIST -->
<table class="table">
    <tr>
        <th width="60">ID</th>
        <th>Community</th>
        <th width="120">Status</th>
        <th width="160">Action</th>
    </tr>

    <?php while ($row = mysqli_fetch_assoc($list)): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['community_name']) ?></td>
            <td><?= $row['status'] ? 'Active' : 'Inactive' ?></td>
            <td>
                <a 
                    href="<?= BASE_URL ?>/admin/generalmaster/community?edit=<?= $row['id'] ?>&page=<?= $page ?>"
                    class="btn btn-edit"
                >Edit</a>

                <a 
                    href="<?= BASE_URL ?>/admin/generalmaster/community?delete=<?= $row['id'] ?>&page=<?= $page ?>"
                    class="btn btn-danger"
                    onclick="return confirm('Delete this community?')"
                >Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

<!-- PAGINATION -->
<div class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a 
            href="<?= BASE_URL ?>/admin/generalmaster/community?page=<?= $i ?>"
            class="<?= ($i == $page) ? 'active' : '' ?>"
        ><?= $i ?></a>
    <?php endfor; ?>
</div>

<?php
$content = ob_get_clean();
require "../layout.php";
