

<?php
require "../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

$page_title = "New Registrations";
$active = "new_registration";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (trim($_POST['action'] ?? '') === 'update_status')) {
    header('Content-Type: application/json; charset=utf-8');
    $user_id = (int)($_POST['user_id'] ?? 0);
    $status  = trim($_POST['status'] ?? '');

    $allowed = ['new','active','inactive','married'];

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'invalid_user']);
        exit;
    }
    if (!in_array($status, $allowed, true)) {
        echo json_encode(['status' => 'error', 'message' => 'invalid_status']);
        exit;
    }

    $status_esc = mysqli_real_escape_string($conn, $status);
    $sql = "UPDATE user_registration_status SET status='$status_esc', updated_at=NOW() WHERE user_id=$user_id";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

// Delete user by ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (trim($_POST['action'] ?? '') === 'delete_user')) {
    header('Content-Type: application/json; charset=utf-8');
    $user_id = (int)($_POST['user_id'] ?? 0);

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'invalid_user']);
        exit;
    }

    // Optional: also delete status row; adjust if you use foreign keys
    mysqli_query($conn, "DELETE FROM user_registration_status WHERE user_id=$user_id");
    $res = mysqli_query($conn, "DELETE FROM users WHERE id=$user_id");

    if ($res) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

/* ================= FILTERS ================= */
$search = trim($_GET['q'] ?? '');
$phone  = trim($_GET['phone'] ?? '');
$community = (int)($_GET['community'] ?? 0);
$gender    = (int)($_GET['gender'] ?? 0);

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "urs.status='new'";

if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND u.name LIKE '%$s%'";
}
if ($phone) {
    $p = mysqli_real_escape_string($conn, $phone);
    $where .= " AND u.mobile LIKE '%$p%'";
}
if ($community) $where .= " AND u.community_id=$community";
if ($gender) $where .= " AND u.gender_id=$gender";

/* ================= COMMUNITY BADGES (ADDED) ================= */
$community_counts = mysqli_query($conn, "
    SELECT 
        mc.id,
        mc.community_name,
        COUNT(*) AS total
    FROM user_registration_status urs
    JOIN users u ON u.id = urs.user_id
    LEFT JOIN master_community mc ON mc.id = u.community_id
    WHERE urs.status = 'new'
    GROUP BY mc.id
    ORDER BY total DESC
");

/* ================= DATA ================= */
$total = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) total
    FROM user_registration_status urs
    JOIN users u ON u.id=urs.user_id
    WHERE $where
"))['total'];

$pages = ceil($total / $limit);

$rows = mysqli_query($conn, "
    SELECT u.id, u.name, u.mobile, u.created_at, urs.status,
           mc.community_name, mg.gender_name
    FROM user_registration_status urs
    JOIN users u ON u.id=urs.user_id
    LEFT JOIN master_community mc ON mc.id=u.community_id
    LEFT JOIN master_gender mg ON mg.id=u.gender_id
    WHERE $where
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
");

$communities = mysqli_query($conn, "SELECT id, community_name FROM master_community");
$genders = mysqli_query($conn, "SELECT id, gender_name FROM master_gender");

ob_start();
?>




<style>
:root {
    --primary-light: #e3f2fd;
    --border-color: #e2e8f0;
    --hover-bg: #f8fafc;
}

/* Minimal table styling */
.data-table {
    width: 100%;
    font-size: 0.875rem;
    border-collapse: separate;
    border-spacing: 0;
}

.data-table th {
    font-weight: 600;
    color: #475569;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    background-color: #f8fafc;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--border-color);
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    color: #334155;
}

.data-table tbody tr {
    transition: background-color 0.15s ease;
}

.data-table tbody tr:hover {
    background-color: var(--hover-bg);
}

/* Status badge styling */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.status-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.status-new { background-color: #fef3c7; color: #92400e; border-color: #fde68a; }
.status-paid { background-color: #d1fae5; color: #065f46; border-color: #a7f3d0; }
.status-active { background-color: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
.status-inactive { background-color: #f1f5f9; color: #475569; border-color: #e2e8f0; }
.status-married { background-color: #f3e8ff; color: #6b21a8; border-color: #e9d5ff; }

/* Simple dropdown fallback styling (in case Bootstrap JS/CSS isn't loaded) */
.dropdown { position: relative; display: inline-block; }
.dropdown-menu { display: none; position: absolute; top: 100%; left: 0; min-width: 12rem; background: #fff; border: 1px solid var(--border-color); border-radius: .375rem; padding: .25rem 0; z-index: 1000; box-shadow: 0 6px 18px rgba(15,23,42,0.08); }
.dropdown-menu.show { display: block; }
.dropdown-menu .dropdown-item { display: flex; align-items: center; gap: .5rem; padding: .5rem 1rem; color: #0f172a; text-decoration: none; }
.dropdown-menu .dropdown-item:hover { background: var(--hover-bg); }

/* Pagination */
.pagination-minimal {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    color: #475569;
    text-decoration: none;
    transition: all 0.2s;
    border: 1px solid var(--border-color);
    background: white;
}

.page-link:hover {
    background-color: var(--hover-bg);
    border-color: #cbd5e1;
}

.page-link.active {
    background-color: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

/* Filters */
.filter-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.filter-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.375rem;
}

.filter-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid var(--border-color);
    border-radius: 0.375rem;
    transition: border-color 0.2s;
}

.filter-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Action buttons */
.btn-minimal {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    border-radius: 0.375rem;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-view {
    background: white;
    border: 1px solid var(--border-color);
    color: #475569;
}

.btn-view:hover {
    background: var(--hover-bg);
    border-color: #cbd5e1;
}

/* Modal styling */
.modal-minimal .modal-content {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.modal-minimal .modal-header {
    border-bottom: 1px solid var(--border-color);
    padding: 1rem 1.5rem;
}

.modal-minimal .modal-body {
    padding: 1.5rem;
}

.modal-minimal .modal-footer {
    border-top: 1px solid var(--border-color);
    padding: 1rem 1.5rem;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #64748b;
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
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

.community-badge:hover {
    background: var(--hover-bg);
}

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
</style>

<!-- ===== COMMUNITY WISE BADGES (ADDED) ===== -->
<div class="community-badges">
    <a href="new_registration.php"
       class="community-badge <?= !$community ? 'active' : '' ?>">
        All
        <span class="community-count"><?= (int)$total ?></span>
    </a>

    <?php while($cc = mysqli_fetch_assoc($community_counts)): ?>
        <?php if (!$cc['id']) continue; ?>
        <a href="?community=<?= $cc['id'] ?>"
           class="community-badge <?= $community == $cc['id'] ? 'active' : '' ?>">
            <?= htmlspecialchars($cc['community_name']) ?>
            <span class="community-count"><?= (int)$cc['total'] ?></span>
        </a>
    <?php endwhile; ?>
</div>



<!-- Filter Card -->
<div class="filter-card">
    <form class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="filter-label">Name</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
                   class="filter-input" placeholder="Search by name...">
        </div>
        <div class="col-md-2">
            <label class="filter-label">Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" 
                   class="filter-input" placeholder="Mobile number">
        </div>
        <div class="col-md-2">
            <label class="filter-label">Community</label>
            <select name="community" class="filter-input">
                <option value="">All Communities</option>
                <?php while($c = mysqli_fetch_assoc($communities)): ?>
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
                while($g = mysqli_fetch_assoc($genders)): ?>
                <option value="<?= $g['id'] ?>" <?= $gender == $g['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['gender_name']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm px-4">Filter</button>
            <a href="new_registration.php" class="btn btn-outline-secondary btn-sm px-4">Reset</a>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if(mysqli_num_rows($rows) == 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <h4 class="h5 mb-2">No new registrations</h4>
            <p class="text-muted">All registration requests have been processed.</p>
        </div>
        <?php else: ?>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Community</th>
                        <th>Mobile</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = $offset + 1; 
                    $status_config = [
                        'new' => ['label' => 'New', 'class' => 'status-new'],
                        'active' => ['label' => 'Active', 'class' => 'status-active'],
                        'inactive' => ['label' => 'Inactive', 'class' => 'status-inactive'],
                        'married' => ['label' => 'Married', 'class' => 'status-married']
                    ];
                    
                    while($r = mysqli_fetch_assoc($rows)): 
                        $current_status = $r['status'] ?? 'new';
                        $status_info = $status_config[$current_status] ?? ['label' => 'Unknown', 'class' => 'status-inactive'];
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <div class="fw-medium"><?= htmlspecialchars($r['name']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($r['gender_name']) ?></td>
                        <td><?= htmlspecialchars($r['community_name']) ?></td>
                        <td><?= htmlspecialchars($r['mobile']) ?></td>
                        <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                        <td>
                            <div class="dropdown">
                                <span class="status-badge <?= $status_info['class'] ?> dropdown-toggle" 
                                      data-bs-toggle="dropdown">
                                    <?= $status_info['label'] ?>
                                </span>
                                <div class="dropdown-menu shadow-sm border">
                                    <?php foreach($status_config as $status_key => $status_data): 
                                        if ($status_key === $current_status) continue; ?>
                                    <a class="dropdown-item status-action" 
                                       href="#"
                                       data-user="<?= $r['id'] ?>"
                                       data-status="<?= $status_key ?>">
                                        <span class="status-badge <?= $status_data['class'] ?> me-2" style="border: none;"></span>
                                        <?= $status_data['label'] ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="view_user.php?id=<?= $r['id'] ?>" 
                               class="btn-minimal btn-view">
                                View
                            </a>
                            <a href="view_user.php?id=<?= $r['id'] ?>&edit=1" 
                               class="btn-minimal btn-view">
                                Edit
                            </a>
                            <button type="button"
                                    class="btn-minimal btn-delete text-danger ms-2"
                                    data-user="<?= $r['id'] ?>">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if($pages > 1): ?>
<nav class="pagination-minimal">
    <?php 
    $start_page = max(1, $page - 2);
    $end_page = min($pages, $start_page + 4);
    $start_page = max(1, $end_page - 4);
    
    if($page > 1): ?>
    <a href="?page=<?= $page-1 ?>&q=<?= urlencode($search) ?>&phone=<?= urlencode($phone) ?>&community=<?= $community ?>&gender=<?= $gender ?>"
       class="page-link">
        &laquo;
    </a>
    <?php endif;
    
    for($p = $start_page; $p <= $end_page; $p++): ?>
    <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&phone=<?= urlencode($phone) ?>&community=<?= $community ?>&gender=<?= $gender ?>"
       class="page-link <?= $p == $page ? 'active' : '' ?>">
        <?= $p ?>
    </a>
    <?php endfor;
    
    if($page < $pages): ?>
    <a href="?page=<?= $page+1 ?>&q=<?= urlencode($search) ?>&phone=<?= urlencode($phone) ?>&community=<?= $community ?>&gender=<?= $gender ?>"
       class="page-link">
        &raquo;
    </a>
    <?php endif; ?>
</nav>
<?php endif; ?>


<!-- Status Update Modal -->
<div class="modal fade modal-minimal" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Change registration status to <strong id="modalStatus"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="confirmStatus">Update Status</button>
            </div>
        </div>
    </div>
</div>

<!-- Ensure jQuery and Bootstrap JS are available -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    const statusLabels = {
        'new': 'new',
        'active': 'Active',
        'inactive': 'Inactive',
        'married': 'Married'
    };

    // Immediate status change with confirm and AJAX
    $(document).on('click', '.status-action', function(e) {
        e.preventDefault();
        const userId = $(this).data('user');
        const status = $(this).data('status');

        const label = statusLabels[status] || status.replace('_',' ');
        if (!confirm('Change status to "' + label + '"?')) return;

        const $el = $(this);
        $el.addClass('disabled');

        $.post('new_registration.php', {
            action: 'update_status',
            user_id: userId,
            status: status
        }, function(response) {
            console.log('status update response:', response);
            try {
                const json = (typeof response === 'object') ? response : JSON.parse(response);
                if (json.status === 'success') {
                    $(`[data-user="${userId}"]`).closest('tr').fadeOut(300, function(){ $(this).remove(); });
                    showToast('Status updated', 'success');
                } else {
                    const msg = json.message || 'Failed to update status';
                    alert(msg);
                    showToast(msg, 'error');
                    $el.removeClass('disabled');
                }
            } catch (e) {
                alert('Unexpected server response');
                showToast('Unexpected server response', 'error');
                $el.removeClass('disabled');
            }
        }).fail(function(){
            showToast('Network error', 'error');
            $el.removeClass('disabled');
        });
    });

    // Delete user (row) by ID
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const userId = $(this).data('user');
        if (!userId) return;

        if (!confirm('Are you sure you want to delete this registration?')) {
            return;
        }

        const $row = $(this).closest('tr');
        $(this).prop('disabled', true);

        $.post('new_registration.php', {
            action: 'delete_user',
            user_id: userId
        }, function(response) {
            try {
                const json = (typeof response === 'object') ? response : JSON.parse(response);
                if (json.status === 'success') {
                    $row.fadeOut(300, function(){ $(this).remove(); });
                    showToast('Registration deleted', 'success');
                } else {
                    const msg = json.message || 'Failed to delete';
                    alert(msg);
                    showToast(msg, 'error');
                    $('.btn-delete[data-user="'+userId+'"]').prop('disabled', false);
                }
            } catch (e) {
                alert('Unexpected server response');
                showToast('Unexpected server response', 'error');
                $('.btn-delete[data-user="'+userId+'"]').prop('disabled', false);
            }
        }).fail(function(){
            showToast('Network error', 'error');
            $('.btn-delete[data-user="'+userId+'"]').prop('disabled', false);
        });
    });

    // Toggle dropdown menu when status badge is clicked (fallback if Bootstrap dropdown not available)
    $(document).on('click', '.status-badge.dropdown-toggle', function(e){
        e.preventDefault();
        e.stopPropagation();
        const $dd = $(this).closest('.dropdown');
        const $menu = $dd.find('.dropdown-menu').first();
        // close others
        $('.dropdown-menu.show').not($menu).removeClass('show');
        $menu.toggleClass('show');
    });

    // Close dropdowns on outside click
    $(document).on('click', function(e){
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu.show').removeClass('show');
        }
    });

    // Toast notification function
    function showToast(message, type = 'info') {
        const bg = type === 'success' ? 'success' : 'danger';
        const toast = $(
            '<div class="toast align-items-center text-bg-' + bg + ' border-0 position-fixed bottom-0 end-0 m-3" role="alert">' +
                '<div class="d-flex">' +
                    '<div class="toast-body">' + message + '</div>' +
                    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
                '</div>' +
            '</div>'
        );
        $('body').append(toast);
        const bsToast = new bootstrap.Toast(toast[0]);
        bsToast.show();
        toast.on('hidden.bs.toast', function() { $(this).remove(); });
    }
});
</script>

<?php
$content = ob_get_clean();
require "layout.php";