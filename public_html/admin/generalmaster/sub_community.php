<?php
require "../../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

$page_title = "Sub Community Master";
$page_icon  = "users";
$active     = "masters";

/* ---------- FLASH ---------- */
$msg_error = $_SESSION['error'] ?? '';
$msg_success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

/* ---------- PAGINATION ---------- */
$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$totalRes = mysqli_query($conn, "SELECT COUNT(*) total FROM master_sub_community");
$total = mysqli_fetch_assoc($totalRes)['total'];
$pages = ceil($total / $limit);

/* ---------- COMMUNITY LIST ---------- */
$communities = mysqli_query(
    $conn,
    "SELECT id, community_name FROM master_community WHERE status=1"
);

/* ---------- EDIT ---------- */
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM master_sub_community WHERE id=$id");
    $edit = mysqli_fetch_assoc($res);
}

/* ---------- ADD / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $community_id = (int)$_POST['community_id'];
    $name = mysqli_real_escape_string($conn, $_POST['sub_community_name']);
    $status = isset($_POST['status']) ? 1 : 0;

    if (!empty($_POST['id'])) {

        $id = (int)$_POST['id'];
        mysqli_query($conn,
            "UPDATE master_sub_community 
             SET community_id=$community_id,
                 sub_community_name='$name',
                 status=$status
             WHERE id=$id"
        );
        $_SESSION['success'] = "Sub Community updated.";

    } else {

        mysqli_query($conn,
            "INSERT INTO master_sub_community 
             (community_id, sub_community_name, status)
             VALUES ($community_id,'$name',$status)"
        );
        $_SESSION['success'] = "Sub Community added.";
    }

    header("Location: " . BASE_URL . "/admin/generalmaster/sub_community?page=$page");
    exit;
}

/* ---------- DELETE ---------- */
if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM master_sub_community WHERE id=$id");
    $_SESSION['success'] = "Sub Community deleted.";

    header("Location: " . BASE_URL . "/admin/generalmaster/sub_community?page=$page");
    exit;
}

/* ---------- LIST ---------- */
$list = mysqli_query($conn,
    "SELECT sc.*, mc.community_name
     FROM master_sub_community sc
     JOIN master_community mc ON mc.id=sc.community_id
     ORDER BY sc.id DESC
     LIMIT $limit OFFSET $offset"
);

ob_start();
?>

<style>
/* 🔴 SAME CSS AS COMMUNITY FILE – NO CHANGES */
.form-inline{display:flex;gap:12px;align-items:center;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:20px;max-width:700px}
.input{padding:8px;border:1px solid #e5e7eb;border-radius:6px;font-size:14px}
.input-text{flex:1}
.btn{padding:8px 16px;border-radius:6px;border:none;cursor:pointer;font-size:13px}
.btn-primary{background:#3b82f6;color:#fff}
.btn-edit{background:#f59e0b;color:#fff}
.btn-danger{background:#ef4444;color:#fff}
.alert{padding:12px;border-radius:6px;margin-bottom:16px;font-size:14px}
.alert-error{background:#fee2e2;color:#b91c1c}
.alert-success{background:#dcfce7;color:#166534}
.table{width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden}
.table th,.table td{padding:12px;border-bottom:1px solid #e5e7eb;font-size:14px}
.table th{background:#f9fafb;text-align:left}
.pagination{margin-top:20px;display:flex;gap:6px}
.pagination a{padding:6px 10px;border:1px solid #e5e7eb;border-radius:6px;text-decoration:none;font-size:13px;color:#111827}
.pagination a.active{background:#3b82f6;color:#fff;border-color:#3b82f6}
</style>

<?php if ($msg_error): ?><div class="alert alert-error"><?= $msg_error ?></div><?php endif; ?>
<?php if ($msg_success): ?><div class="alert alert-success"><?= $msg_success ?></div><?php endif; ?>

<!-- FORM -->
<form method="post" class="form-inline">
<?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>

<select name="community_id" class="input" required>
    <option value="">Community</option>
    <?php while ($c=mysqli_fetch_assoc($communities)): ?>
    <option value="<?= $c['id'] ?>"
        <?= ($edit && $edit['community_id']==$c['id'])?'selected':'' ?>>
        <?= htmlspecialchars($c['community_name']) ?>
    </option>
    <?php endwhile; ?>
</select>

<input class="input input-text" name="sub_community_name"
       placeholder="Sub Community Name" required
       value="<?= $edit['sub_community_name'] ?? '' ?>">

<label>
<input type="checkbox" name="status"
<?= (!isset($edit) || $edit['status']==1)?'checked':'' ?>> Active
</label>

<button class="btn btn-primary"><?= $edit?'Update':'Add' ?></button>

<?php if ($edit): ?>
<a href="<?= BASE_URL ?>/admin/generalmaster/sub_community?page=<?= $page ?>"
   class="btn btn-edit">Cancel</a>
<?php endif; ?>
</form>

<!-- TABLE -->
<table class="table">
<tr>
<th>ID</th>
<th>Community</th>
<th>Sub Community</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php while($row=mysqli_fetch_assoc($list)): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= htmlspecialchars($row['community_name']) ?></td>
<td><?= htmlspecialchars($row['sub_community_name']) ?></td>
<td><?= $row['status']?'Active':'Inactive' ?></td>
<td>
<a class="btn btn-edit"
href="<?= BASE_URL ?>/admin/generalmaster/sub_community?edit=<?= $row['id'] ?>&page=<?= $page ?>">Edit</a>
<a class="btn btn-danger"
onclick="return confirm('Delete?')"
href="<?= BASE_URL ?>/admin/generalmaster/sub_community?delete=<?= $row['id'] ?>&page=<?= $page ?>">Delete</a>
</td>
</tr>
<?php endwhile; ?>
</table>

<div class="pagination">
<?php for($i=1;$i<=$pages;$i++): ?>
<a class="<?= ($i==$page)?'active':'' ?>"
href="<?= BASE_URL ?>/admin/generalmaster/sub_community?page=<?= $i ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

<?php
$content = ob_get_clean();
require "../layout.php";
