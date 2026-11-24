<?php
require "../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

$page_title = "Dashboard";
$active = "dashboard";

/* ======================
   FETCH COUNTS
====================== */
$sql = "
SELECT
    (SELECT COUNT(*) FROM users) AS total_users,
    (SELECT COUNT(*) FROM users WHERE gender_id = 1) AS male_users,
    (SELECT COUNT(*) FROM users WHERE gender_id = 2) AS female_users,
    (SELECT COUNT(*) FROM users WHERE marital_status = 'unmarried') AS unmarried_users,
    (SELECT COUNT(*) FROM users WHERE marital_status = 'married') AS married_users,
    (SELECT COUNT(*) FROM user_registration_status WHERE status = 'new') AS new_users,
    (SELECT COUNT(*) FROM user_registration_status WHERE status = 'paid') AS paid_users,
    (SELECT COUNT(*) FROM user_registration_status WHERE status = 'active') AS active_users,
    (SELECT COUNT(*) FROM user_registration_status WHERE status = 'inactive') AS inactive_users,
    (SELECT COUNT(*) FROM user_registration_status WHERE status = 'payment_pending') AS payment_pending_users,
    (SELECT COUNT(*) FROM user_registration_status WHERE status = 'rejected') AS rejected_users,
    (SELECT COUNT(*) FROM user_registration_status WHERE status = 'married') AS status_married_users
";
$res   = mysqli_query($conn, $sql);
$stats = mysqli_fetch_assoc($res);

// Last 5 registrations
$recent_sql = "
    SELECT u.id, u.name, u.mobile, mc.community_name, mg.gender_name,
           urs.status, u.created_at
    FROM users u
    LEFT JOIN user_registration_status urs ON urs.user_id = u.id
    LEFT JOIN master_community mc ON mc.id = u.community_id
    LEFT JOIN master_gender mg ON mg.id = u.gender_id
    ORDER BY u.created_at DESC LIMIT 5
";
$recent_rows = mysqli_query($conn, $recent_sql);

ob_start();
?>

<style>
.dash-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px 22px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    border: 1px solid #f0f0f0;
    transition: box-shadow .2s, transform .2s;
    text-decoration: none;
    color: inherit;
}
.dash-stat-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,.12);
    transform: translateY(-2px);
    color: inherit;
}
.dash-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.dash-label {
    font-size: 11px; font-weight: 600; color: #9ca3af;
    text-transform: uppercase; letter-spacing: .6px; margin-bottom: 4px;
}
.dash-value {
    font-size: 26px; font-weight: 700; color: #111827; line-height: 1;
}
.dash-section-title {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; color: #9ca3af; margin: 28px 0 12px;
    padding-bottom: 8px; border-bottom: 1px solid #f0f0f0;
}
.dash-recent-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
.dash-recent-table th {
    font-size: 11px; font-weight: 600; color: #9ca3af;
    text-transform: uppercase; padding: 10px 14px;
    background: #f9fafb; border-bottom: 1px solid #f0f0f0;
}
.dash-recent-table td { padding: 11px 14px; border-bottom: 1px solid #f9f9f9; color: #374151; }
.dash-recent-table tbody tr:hover { background: #f9fafb; }
.dash-recent-table tbody tr:last-child td { border-bottom: none; }
.status-pill {
    display: inline-block; padding: 2px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px;
}
.pill-new      { background: #eff6ff; color: #3b82f6; }
.pill-active   { background: #f0fdf4; color: #16a34a; }
.pill-paid     { background: #ecfdf5; color: #059669; }
.pill-inactive { background: #f9fafb; color: #9ca3af; }
.pill-pending  { background: #fffbeb; color: #d97706; }
.pill-rejected { background: #fff1f2; color: #e11d48; }
.pill-married  { background: #fdf4ff; color: #9333ea; }
.dash-action-btn {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;
    text-decoration: none; border: 1px solid; white-space: nowrap;
    transition: opacity .15s;
}
.dash-action-btn:hover { opacity: .75; }
.dash-quick-links { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
.dash-quick-link {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600;
    text-decoration: none; border: 1px solid #e5e7eb; color: #374151;
    background: #fff; transition: all .15s;
}
.dash-quick-link:hover { background: #3b82f6; color: #fff; border-color: #3b82f6; }
</style>

<div class="container-fluid px-0">

    <!-- QUICK LINKS -->
    <div class="dash-quick-links">
        <a href="<?= BASE_URL ?>/admin/active_members"   class="dash-quick-link"><i class="fas fa-users"></i> Active Members</a>
        <a href="<?= BASE_URL ?>/admin/new_registration" class="dash-quick-link"><i class="fas fa-user-plus"></i> New Registrations</a>
        <a href="<?= BASE_URL ?>/admin/paid_members"     class="dash-quick-link"><i class="fas fa-credit-card"></i> Paid Members</a>
        <a href="<?= BASE_URL ?>/admin/inactive_members" class="dash-quick-link"><i class="fas fa-user-slash"></i> Unpaid Members</a>
        <a href="<?= BASE_URL ?>/admin/add_new_member"   class="dash-quick-link"><i class="fas fa-user-edit"></i> Add Member</a>
    </div>

    <!-- MEMBERS OVERVIEW -->
    <div class="dash-section-title">Members Overview</div>
    <div class="row g-3 mb-2">

        <div class="col-6 col-md-3">
            <div class="dash-stat-card">
                <div class="dash-icon" style="background:#eff6ff;color:#3b82f6"><i class="fas fa-users"></i></div>
                <div><div class="dash-label">Total Members</div><div class="dash-value"><?= (int)$stats['total_users'] ?></div></div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <a href="<?= BASE_URL ?>/admin/active_members?gender=1" class="dash-stat-card">
                <div class="dash-icon" style="background:#eff6ff;color:#2563eb"><i class="fas fa-mars"></i></div>
                <div><div class="dash-label">Male</div><div class="dash-value"><?= (int)$stats['male_users'] ?></div></div>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="<?= BASE_URL ?>/admin/active_members?gender=2" class="dash-stat-card">
                <div class="dash-icon" style="background:#fdf4ff;color:#9333ea"><i class="fas fa-venus"></i></div>
                <div><div class="dash-label">Female</div><div class="dash-value"><?= (int)$stats['female_users'] ?></div></div>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <div class="dash-stat-card">
                <div class="dash-icon" style="background:#f0fdf4;color:#16a34a"><i class="fas fa-heart"></i></div>
                <div><div class="dash-label">Unmarried</div><div class="dash-value"><?= (int)$stats['unmarried_users'] ?></div></div>
            </div>
        </div>

    </div>

    <!-- REGISTRATION STATUS -->
    <div class="dash-section-title">Registration Status</div>
    <div class="row g-3 mb-4">

        <div class="col-6 col-md-2">
            <a href="<?= BASE_URL ?>/admin/new_registration" class="dash-stat-card">
                <div class="dash-icon" style="background:#eff6ff;color:#3b82f6"><i class="fas fa-user-clock"></i></div>
                <div><div class="dash-label">New</div><div class="dash-value"><?= (int)$stats['new_users'] ?></div></div>
            </a>
        </div>

        <div class="col-6 col-md-2">
            <a href="<?= BASE_URL ?>/admin/active_members" class="dash-stat-card">
                <div class="dash-icon" style="background:#f0fdf4;color:#16a34a"><i class="fas fa-check-circle"></i></div>
                <div><div class="dash-label">Active</div><div class="dash-value"><?= (int)$stats['active_users'] ?></div></div>
            </a>
        </div>

        <div class="col-6 col-md-2">
            <a href="<?= BASE_URL ?>/admin/paid_members" class="dash-stat-card">
                <div class="dash-icon" style="background:#ecfdf5;color:#059669"><i class="fas fa-credit-card"></i></div>
                <div><div class="dash-label">Paid</div><div class="dash-value"><?= (int)$stats['paid_users'] ?></div></div>
            </a>
        </div>

        <div class="col-6 col-md-2">
            <a href="<?= BASE_URL ?>/admin/inactive_members" class="dash-stat-card">
                <div class="dash-icon" style="background:#fffbeb;color:#d97706"><i class="fas fa-hourglass-half"></i></div>
                <div><div class="dash-label">Unpaid</div><div class="dash-value"><?= (int)$stats['new_users'] ?></div></div>
            </a>
        </div>

        <div class="col-6 col-md-2">
            <div class="dash-stat-card">
                <div class="dash-icon" style="background:#fffbeb;color:#b45309"><i class="fas fa-clock"></i></div>
                <div><div class="dash-label">Pending</div><div class="dash-value"><?= (int)$stats['payment_pending_users'] ?></div></div>
            </div>
        </div>

        <div class="col-6 col-md-2">
            <div class="dash-stat-card">
                <div class="dash-icon" style="background:#fff1f2;color:#e11d48"><i class="fas fa-ban"></i></div>
                <div><div class="dash-label">Rejected</div><div class="dash-value"><?= (int)$stats['rejected_users'] ?></div></div>
            </div>
        </div>

    </div>

    <!-- RECENT REGISTRATIONS -->
    <div class="dash-section-title">Recent Registrations</div>
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;overflow:hidden;">
        <div class="table-responsive">
            <table class="dash-recent-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Community</th>
                        <th>Gender</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $pill_map   = ['new'=>'pill-new','active'=>'pill-active','paid'=>'pill-paid','inactive'=>'pill-inactive','payment_pending'=>'pill-pending','rejected'=>'pill-rejected','married'=>'pill-married'];
                $pill_label = ['new'=>'New','active'=>'Active','paid'=>'Paid','inactive'=>'Inactive','payment_pending'=>'Pending','rejected'=>'Rejected','married'=>'Married'];
                $ri = 1;
                while ($rr = mysqli_fetch_assoc($recent_rows)):
                    $st = $rr['status'] ?? 'new';
                    $pc = $pill_map[$st]   ?? 'pill-new';
                    $pl = $pill_label[$st] ?? ucfirst($st);
                ?>
                    <tr>
                        <td><?= $ri++ ?></td>
                        <td><strong><?= htmlspecialchars($rr['name']) ?></strong></td>
                        <td><?= htmlspecialchars($rr['mobile']) ?></td>
                        <td><?= htmlspecialchars($rr['community_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($rr['gender_name'] ?? '—') ?></td>
                        <td><span class="status-pill <?= $pc ?>"><?= $pl ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <a href="view_user.php?id=<?= $rr['id'] ?>" class="dash-action-btn" style="color:#3b82f6;border-color:#bfdbfe;"><i class="fas fa-eye"></i> View</a>
                                <a href="view_user.php?id=<?= $rr['id'] ?>&edit=1" class="dash-action-btn" style="color:#059669;border-color:#a7f3d0;"><i class="fas fa-pen"></i> Edit</a>
                                <a href="delete_member.php?id=<?= $rr['id'] ?>" onclick="return confirm('Delete this member?');" class="dash-action-btn" style="color:#e11d48;border-color:#fecdd3;"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require "layout.php";