<?php
require "../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

$page_title = "Paid Members";
$active = "paid_members";

/* ================= FILTERS ================= */
$search    = trim($_GET['q'] ?? '');
$phone     = trim($_GET['phone'] ?? '');
$community = (int)($_GET['community'] ?? 0);
$gender    = (int)($_GET['gender'] ?? 0);

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$where_conditions = ["((urs.status = 'paid') OR (up.approval_status = 'approved'))", "urs.status != 'deleted'"];

if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where_conditions[] = "u.name LIKE '%$s%'";
}
if ($phone) {
    $p = mysqli_real_escape_string($conn, $phone);
    $where_conditions[] = "u.mobile LIKE '%$p%'";
}
if ($community) {
    $where_conditions[] = "u.community_id = $community";
}
if ($gender) {
    $where_conditions[] = "u.gender_id = $gender";
}

$where_clause = implode(' AND ', $where_conditions);

/* ================= ALL COUNTS ================= */
$all_counts = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        SUM(CASE WHEN mg.gender_name = 'Male'   THEN 1 ELSE 0 END) as male_count,
        SUM(CASE WHEN mg.gender_name = 'Female' THEN 1 ELSE 0 END) as female_count
    FROM users u
    INNER JOIN user_registration_status urs ON urs.user_id = u.id
    LEFT JOIN user_payments up ON up.user_id = u.id
    LEFT JOIN master_gender mg ON mg.id = u.gender_id
    WHERE ((urs.status = 'paid') OR (up.approval_status = 'approved')) AND urs.status != 'deleted'
"));

/* ================= COMMUNITY BADGES ================= */
$community_counts = mysqli_query($conn, "
    SELECT
        mc.id,
        mc.community_name,
        SUM(CASE WHEN mg.gender_name = 'Male'   THEN 1 ELSE 0 END) as male_count,
        SUM(CASE WHEN mg.gender_name = 'Female' THEN 1 ELSE 0 END) as female_count,
        COUNT(DISTINCT u.id) AS total
    FROM users u
    INNER JOIN user_registration_status urs ON urs.user_id = u.id
    LEFT JOIN user_payments up ON up.user_id = u.id
    LEFT JOIN master_community mc ON mc.id = u.community_id
    LEFT JOIN master_gender mg ON mg.id = u.gender_id
    WHERE ((urs.status = 'paid') OR (up.approval_status = 'approved')) AND urs.status != 'deleted'
    GROUP BY mc.id
    ORDER BY total DESC
");

/* ================= TOTAL COUNT ================= */
$total_result = mysqli_query($conn, "
    SELECT COUNT(DISTINCT u.id) as total
    FROM users u
    INNER JOIN user_registration_status urs ON urs.user_id = u.id
    LEFT JOIN user_payments up ON up.user_id = u.id
    LEFT JOIN master_community mc ON mc.id = u.community_id
    LEFT JOIN master_gender mg ON mg.id = u.gender_id
    WHERE $where_clause
");

$total_row = mysqli_fetch_assoc($total_result);
$total  = $total_row['total'];
$pages  = ceil($total / $limit);

/* ================= DATA ================= */
$rows = mysqli_query($conn, "
    SELECT DISTINCT
        u.id,
        u.name,
        u.username,
        u.mobile,
        mc.community_name,
        mg.gender_name,
        urs.status,
        COALESCE(up.created_at, urs.updated_at) AS start_date
    FROM users u
    INNER JOIN user_registration_status urs ON urs.user_id = u.id
    LEFT JOIN user_payments up ON up.user_id = u.id
    LEFT JOIN master_community mc ON mc.id = u.community_id
    LEFT JOIN master_gender mg ON mg.id = u.gender_id
    WHERE $where_clause
    ORDER BY start_date DESC
    LIMIT $limit OFFSET $offset
");

/* ================= DROPDOWN DATA ================= */
$communities = mysqli_query($conn, "SELECT id, community_name FROM master_community ORDER BY community_name");
$genders     = mysqli_query($conn, "SELECT id, gender_name FROM master_gender");

ob_start();
?>

<style>
:root {
    --primary-light: #e3f2fd;
    --border-color: #e2e8f0;
    --hover-bg: #f8fafc;
}

.community-badges {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-bottom: 1.25rem;
}

.community-badge {
    padding: .35rem .75rem;
    font-size: .75rem;
    border-radius: 999px;
    border: 1px solid var(--border-color);
    background: #fff;
    color: #334155;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
}

.community-badge:hover { background: var(--hover-bg); }

.community-badge.active {
    background: #3b82f6;
    color: #fff;
    border-color: #3b82f6;
}

.community-count {
    background: #e5e7eb;
    padding: 0 .45rem;
    border-radius: 999px;
    font-weight: 600;
    font-size: .7rem;
}

.community-badge.active .community-count {
    background: rgba(255,255,255,.25);
    color: #fff;
}

.data-table { width:100%; font-size:0.875rem; border-collapse:separate; border-spacing:0; }
.data-table th { font-weight:600; color:#475569; text-transform:uppercase; font-size:0.75rem; background:#f8fafc; padding:0.875rem 1rem; border-bottom:1px solid var(--border-color); }
.data-table td { padding:1rem; border-bottom:1px solid var(--border-color); color:#334155; }
.data-table tbody tr:hover { background-color:var(--hover-bg); }
.filter-card { background:white; border:1px solid var(--border-color); border-radius:0.5rem; padding:1.25rem; margin-bottom:1.5rem; }
.filter-label { font-size:0.75rem; font-weight:500; color:#64748b; text-transform:uppercase; margin-bottom:0.375rem; }
.filter-input { width:100%; padding:0.5rem 0.75rem; font-size:0.875rem; border:1px solid var(--border-color); border-radius:0.375rem; }
.pagination-minimal { display:flex; justify-content:center; gap:0.5rem; margin-top:2rem; }
.page-link { width:2.5rem; height:2.5rem; display:flex; align-items:center; justify-content:center; border:1px solid var(--border-color); border-radius:0.375rem; text-decoration:none; }
.page-link.active { background:#3b82f6; color:#fff; }
.empty-state { text-align:center; padding:3rem 1rem; color:#64748b; }
.btn-minimal { padding:0.25rem 0.75rem; border:1px solid var(--border-color); border-radius:0.25rem; font-size:0.75rem; text-decoration:none; color:#475569; }
.btn-minimal:hover { background:var(--hover-bg); }
</style>

<div class="container-fluid">

<!-- COMMUNITY BADGES -->
<div class="community-badges">
    <a href="paid_members.php"
       class="community-badge <?= !$community ? 'active' : '' ?>">
        All
        <span class="community-count">M:<?= (int)$all_counts['male_count'] ?>/F:<?= (int)$all_counts['female_count'] ?></span>
    </a>

    <?php
    mysqli_data_seek($community_counts, 0);
    while ($cc = mysqli_fetch_assoc($community_counts)):
    ?>
        <?php if (!$cc['id']) continue; ?>
        <a href="?community=<?= $cc['id'] ?>"
           class="community-badge <?= $community == $cc['id'] ? 'active' : '' ?>">
            <?= htmlspecialchars($cc['community_name']) ?>
            <span class="community-count">M:<?= (int)$cc['male_count'] ?>/F:<?= (int)$cc['female_count'] ?></span>
        </a>
    <?php endwhile; ?>
</div>

<!-- FILTER -->
<div class="filter-card">
<form class="row g-3 align-items-end" method="get">
    <div class="col-md-3">
        <label class="filter-label">Name</label>
        <input name="q" value="<?= htmlspecialchars($search) ?>" class="filter-input" autocomplete="off">
    </div>
    <div class="col-md-2">
        <label class="filter-label">Phone</label>
        <input name="phone" value="<?= htmlspecialchars($phone) ?>" class="filter-input" autocomplete="off">
    </div>
    <div class="col-md-2">
        <label class="filter-label">Community</label>
        <select name="community" class="filter-input">
            <option value="">All Communities</option>
            <?php
            mysqli_data_seek($communities, 0);
            while ($c = mysqli_fetch_assoc($communities)):
            ?>
                <option value="<?= $c['id'] ?>" <?= $community == $c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['community_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="filter-label">Gender</label>
        <select name="gender" class="filter-input">
            <option value="">All Genders</option>
            <?php
            mysqli_data_seek($genders, 0);
            while ($g = mysqli_fetch_assoc($genders)):
            ?>
                <option value="<?= $g['id'] ?>" <?= $gender == $g['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['gender_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-primary btn-sm px-4">Search</button>
        <a href="paid_members.php" class="btn btn-outline-secondary btn-sm px-2">Reset</a>
    </div>
</form>
</div>

<!-- TABLE -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">

    <?php if (mysqli_num_rows($rows) == 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon">💳</div>
            <h4 class="h5 mb-2">No Paid Members</h4>
            <p class="text-muted">No paid members found with the current filters.</p>
        </div>
    <?php else: ?>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>UserName</th>
                    <th>Gender</th>
                    <th>Community</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>

            <?php $i = $offset + 1; while ($r = mysqli_fetch_assoc($rows)): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><div class="fw-medium"><?= htmlspecialchars($r['name']) ?></div></td>
                <td><div class="fw-medium"><?= htmlspecialchars($r['username']) ?></div></td>
                <td><?= htmlspecialchars($r['gender_name']) ?></td>
                <td><?= htmlspecialchars($r['community_name']) ?></td>
                <td><?= htmlspecialchars($r['mobile']) ?></td>
                <td>
                    <span class="badge bg-info text-white">PAID</span>
                </td>
                <td>
                    <a href="view_user.php?id=<?= $r['id'] ?>" class="btn-minimal btn-view">View</a>
    <a href="view_user.php?id=<?= $r['id'] ?>&edit=1" class="btn-minimal btn-view">Edit</a>
                    <a href="download_a4.php?id=<?= $r['id'] ?>" class="btn-minimal btn-view" target="_blank">Download A4</a>
                    <a href="delete_member.php?id=<?= $r['id'] ?>"
                       class="btn-minimal btn-view"
                       style="color: red; border-color: red;"
                       onclick="return confirm('Are you sure you want to delete this member?');">
                       Delete
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>

            </tbody>
        </table>
    </div>

    <?php endif; ?>

    </div>
</div>

<?php if ($pages > 1): ?>
<nav class="pagination-minimal">
    <?php
    $start_page = max(1, $page - 2);
    $end_page   = min($pages, $start_page + 4);
    $start_page = max(1, $end_page - 4);

    if ($page > 1): ?>
    <a href="?page=<?= $page-1 ?>&q=<?= urlencode($search) ?>&phone=<?= urlencode($phone) ?>&community=<?= $community ?>&gender=<?= $gender ?>" class="page-link">&laquo;</a>
    <?php endif;

    for ($p = $start_page; $p <= $end_page; $p++): ?>
    <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&phone=<?= urlencode($phone) ?>&community=<?= $community ?>&gender=<?= $gender ?>" class="page-link <?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor;

    if ($page < $pages): ?>
    <a href="?page=<?= $page+1 ?>&q=<?= urlencode($search) ?>&phone=<?= urlencode($phone) ?>&community=<?= $community ?>&gender=<?= $gender ?>" class="page-link">&raquo;</a>
    <?php endif; ?>
</nav>
<?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require "layout.php";

