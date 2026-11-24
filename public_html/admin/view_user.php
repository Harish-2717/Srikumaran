<?php
require "../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

/* ================ PROFILE UPDATE (AJAX) ================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim($_POST['action'] ?? '') === 'update_profile') {
    // capture any accidental output (PHP warnings etc.) so JSON response stays clean
    ob_start();
    header('Content-Type: application/json; charset=utf-8');
    if (!isset($_SESSION['admin_id'])) { ob_clean(); echo json_encode(['status'=>'error','message'=>'unauthorized']); exit; }
    $user_id = (int)($_POST['user_id'] ?? 0);
        $allowed = [
        'father_name','mother_name','mobile','email','dob','tob',
        'monthly_income','qualification_id','working_city_id','city_id','pincode','raasi_id','star_id',
        'dhosam_type_id','dhosam_name_id','community_id','sub_community_id',
        'kulam_id','gothiram_id','marital_status','address','password','niram',
        'username','other_info'
    ];
    $sets = [];
    $account_status_to_update = null;
    $payment_status_to_update = null;

    foreach ($allowed as $f) {
        if (!isset($_POST[$f])) continue;
        $v = trim($_POST[$f]);

        if ($f === 'password') {
            if ($v === '') continue;
            $sets[] = "`password_hash`='".mysqli_real_escape_string($conn,$v)."'";
            continue;
        }

        /* EMAIL: allow NULL */
        if ($f === 'email') {
            if ($v === '') {
                $sets[] = "`email`=NULL";
            } else {
                $sets[] = "`email`='".mysqli_real_escape_string($conn, $v)."'";
            }
            continue;
        }

        if (substr($f, -3) === '_id') {
            $sets[] = ($v === '') ? "`$f`=NULL" : "`$f`=".(int)$v;
            continue;
        }

        $sets[] = "`$f`='".mysqli_real_escape_string($conn,$v)."'";
    }

    /* account_status -> user_registration_status.status */
    if (isset($_POST['account_status']) && trim($_POST['account_status']) !== '') {
        $v = trim($_POST['account_status']);
        if (in_array($v, ['active','inactive'], true)) {
            $account_status_to_update = mysqli_real_escape_string($conn, $v);
        }
    }

    /* payment_status -> mapped to user_registration_status.status (only when account_status not set) */
    if (isset($_POST['payment_status']) && trim($_POST['payment_status']) !== '') {
        $v = trim($_POST['payment_status']);
        $map = ['paid' => 'paid', 'unpaid' => 'new', 'pending' => 'payment_pending'];
        if (isset($map[$v])) {
            $payment_status_to_update = mysqli_real_escape_string($conn, $map[$v]);
        }
    }

    /* Determine registration status:
       Payment status (paid/unpaid/pending) always takes priority.
       Account status (active/inactive) only used when no payment status change. */
    if ($account_status_to_update === 'inactive') {
        // Explicitly deactivating overrides everything
        $reg_status_to_update = 'inactive';
    } elseif ($payment_status_to_update !== null) {
        // Payment status change takes priority over generic 'active'
        $reg_status_to_update = $payment_status_to_update;
    } elseif ($account_status_to_update === 'active') {
        $reg_status_to_update = 'active';
    } else {
        $reg_status_to_update = null;
    }

    if (!$user_id || (empty($sets) && $reg_status_to_update === null)) {
        ob_clean(); echo json_encode(['status'=>'error','message'=>'invalid_input']); exit;
    }

    $ok = true; $err = '';
    $debug_info = [];
    try {
        if (!empty($sets)) {
            $sql = "UPDATE users SET " . implode(',', $sets) . " WHERE id=$user_id";
            $debug_info['update_sql'] = $sql;
            $res1 = mysqli_query($conn, $sql);
            if (!$res1) { $ok = false; $err = mysqli_error($conn); }
            $debug_info['update_ok'] = (bool)$res1;
            $debug_info['update_affected'] = mysqli_affected_rows($conn);
        }

        if ($ok && $reg_status_to_update !== null) {
            $exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM user_registration_status WHERE user_id=$user_id LIMIT 1"));
            if ($exists) {
                $sql2 = "UPDATE user_registration_status SET status='$reg_status_to_update', updated_at=NOW() WHERE user_id=$user_id";
            } else {
                $sql2 = "INSERT INTO user_registration_status (user_id, status, created_at, updated_at) VALUES ($user_id,'$reg_status_to_update',NOW(),NOW())";
            }
            $debug_info['status_sql'] = $sql2;
            $res2 = mysqli_query($conn, $sql2);
            if (!$res2) { $ok = false; $err = mysqli_error($conn); }
            $debug_info['status_ok'] = (bool)$res2;
            $debug_info['status_affected'] = mysqli_affected_rows($conn);
        }

        if ($ok && isset($_POST['payment_status']) && trim($_POST['payment_status']) !== '') {
            $p_val = trim($_POST['payment_status']);
            $p_approval = ($p_val === 'paid') ? 'approved' : (($p_val === 'pending') ? 'pending' : 'rejected');
            $up_exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM user_payments WHERE user_id=$user_id LIMIT 1"));
            if ($up_exists) {
                mysqli_query($conn, "UPDATE user_payments SET approval_status='$p_approval' WHERE user_id=$user_id");
            } else {
                mysqli_query($conn, "INSERT INTO user_payments (user_id, payment_method, approval_status, created_at) VALUES ($user_id, 'cash', '$p_approval', NOW())");
            }
        }
    } catch (Throwable $e) {
        $ok = false;
        $err = $e->getMessage();
    }

    // handle uploaded files: profile_photo, jathagam_file, other_photos[]
    if ($ok) {
        // ensure we store files in project uploads folder (one level above admin)
        $project_root = dirname(__DIR__); // c:/xampp/.../matrimony
        $debug_info['file_moves'] = [];
        // profile photo
        if (!empty($_FILES['profile_photo']['tmp_name'])) {
            $pf = $_FILES['profile_photo'];
            if ($pf['tmp_name']) {
                $ext = strtolower(pathinfo($pf['name'], PATHINFO_EXTENSION));
                $dest_rel = 'uploads/profile/'.time().'_'.rand(1000,9999).'.'.$ext;
                $dest_abs = $project_root . '/' . $dest_rel;
                @mkdir(dirname($dest_abs), 0755, true);
                $moved = move_uploaded_file($pf['tmp_name'], $dest_abs);
                $debug_info['file_moves']['profile_photo'] = ['moved' => (bool)$moved, 'path' => $dest_rel];
                if ($moved) {
                    mysqli_query($conn, "UPDATE users SET profile_photo='".mysqli_real_escape_string($conn,$dest_rel)."' WHERE id=$user_id");
                }
            }
        }

        // jathagam file
        if (!empty($_FILES['jathagam_file']['tmp_name'])) {
            $jf = $_FILES['jathagam_file'];
            if ($jf['tmp_name']) {
                $ext = strtolower(pathinfo($jf['name'], PATHINFO_EXTENSION));
                $dest_rel = 'uploads/jathagam/'.time().'_'.rand(1000,9999).'.'.$ext;
                $dest_abs = $project_root . '/' . $dest_rel;
                @mkdir(dirname($dest_abs), 0755, true);
                $moved = move_uploaded_file($jf['tmp_name'], $dest_abs);
                $debug_info['file_moves']['jathagam_file'] = ['moved' => (bool)$moved, 'path' => $dest_rel];
                if ($moved) {
                    mysqli_query($conn, "UPDATE users SET jathagam_file='".mysqli_real_escape_string($conn,$dest_rel)."' WHERE id=$user_id");
                }
            }
        }

        // other photos (multiple)
        if (!empty($_FILES['other_photos']['name'][0])) {
            foreach ($_FILES['other_photos']['tmp_name'] as $i => $tmp) {
                if (!$tmp) continue;
                $oname = $_FILES['other_photos']['name'][$i];
                $ext = strtolower(pathinfo($oname, PATHINFO_EXTENSION));
                $dest_rel = 'uploads/other/'.time().'_'.rand(1000,9999).'.'.$ext;
                $dest_abs = $project_root . '/' . $dest_rel;
                @mkdir(dirname($dest_abs), 0755, true);
                $moved = move_uploaded_file($tmp, $dest_abs);
                $debug_info['file_moves']['other_photos'][] = ['moved' => (bool)$moved, 'path' => $dest_rel];
                if ($moved) {
                    mysqli_query($conn, "INSERT INTO user_other_photos (user_id,photo_path) VALUES ($user_id,'".mysqli_real_escape_string($conn,$dest_rel)."')");
                }
            }
        }
    }

    // debug response helper
    if (isset($_POST['debug']) && trim($_POST['debug']) === '1') {
        $posted = $_POST; unset($posted['password']);
        ob_clean();
        echo json_encode(['status'=>'debug','debug'=>$debug_info,'post'=>$posted,'files'=>array_map(function($f){ return is_array($f)? array_keys($f):$f; }, $_FILES)]);
        exit;
    }

    ob_clean(); if ($ok) echo json_encode(['status'=>'success']); else echo json_encode(['status'=>'error','message'=>$err]);
    exit;
}

// Handle file uploads for profile/jathagam/other via normal POST not update_profile action
// (no-op here; uploads handled inside update_profile block via $_FILES)

/* ================= SEARCH LOGIC ================= */
$user_id = 0;
$show_profile = false;
$error_msg = '';

if (!empty($_GET['q'])) {
    $term = mysqli_real_escape_string($conn, trim($_GET['q']));
    $resq = mysqli_query($conn, "SELECT id FROM users WHERE mobile='$term' OR username='$term' LIMIT 1");

    if ($row = mysqli_fetch_assoc($resq)) {
        $user_id = (int)$row['id'];
        $show_profile = true;
    } else {
        $error_msg = "No user found for this mobile number or username";
    }
}

if (!$user_id && !empty($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $show_profile = true;
}

$page_title = "User Profile";
$active = "view_user";

/* ================= FETCH USER ================= */
$data = null;
$age  = '-';
$other_photos = [];

if ($show_profile) {

    $sql = "
    SELECT
        u.*,
        mc.community_name,
        msc.sub_community_name,
        mg.gender_name,
        mq.qualification_name,
        city.city_name,
        wcity.city_name AS working_city_name,
        mk.kulam_name,
        mgot.gothiram_name,
        mdt.dhosam_type,
        mdn.dhosam_name,
        mr.raasi_name,
        ms.star_name,
        urs.status AS reg_status,
        up.approval_status AS payment_approval_status,
        up.payment_proof_path
    FROM users u
    LEFT JOIN master_community mc ON mc.id=u.community_id
    LEFT JOIN master_sub_community msc ON msc.id=u.sub_community_id
    LEFT JOIN master_gender mg ON mg.id=u.gender_id
    LEFT JOIN master_qualification mq ON mq.id=u.qualification_id
    LEFT JOIN master_city city ON city.id=u.city_id
    LEFT JOIN master_city wcity ON wcity.id=u.working_city_id
    LEFT JOIN master_kulam mk ON mk.id=u.kulam_id
    LEFT JOIN master_gothiram mgot ON mgot.id=u.gothiram_id
    LEFT JOIN master_dhosam_type mdt ON mdt.id=u.dhosam_type_id
    LEFT JOIN master_dhosam_name mdn ON mdn.id=u.dhosam_name_id
    LEFT JOIN master_raasi mr ON mr.id=u.raasi_id
    LEFT JOIN master_star ms ON ms.id=u.star_id
    LEFT JOIN user_registration_status urs ON urs.user_id=u.id
    LEFT JOIN user_payments up ON up.user_id=u.id
    WHERE u.id=$user_id
    LIMIT 1
    ";

    $res = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($res);

    if (!$data) {
        $show_profile = false;
        $error_msg = "User record not available";
    } else {
        $age = $data['dob']
            ? (new DateTime())->diff(new DateTime($data['dob']))->y
            : '-';

        /* ===== OTHER PHOTOS FETCH (ADDITION ONLY) ===== */
        $opq = mysqli_query($conn, "SELECT photo_path FROM user_other_photos WHERE user_id=$user_id");
        while ($op = mysqli_fetch_assoc($opq)) {
            $other_photos[] = $op['photo_path'];
        }
        /* ===== BUILD MASTER LISTS FOR DROPDOWNS ===== */
        $opt_qualification = [];
        $rq = mysqli_query($conn, "SELECT id,qualification_name FROM master_qualification ORDER BY qualification_name");
        while ($r = mysqli_fetch_assoc($rq)) $opt_qualification[$r['id']] = $r['qualification_name'];

        $opt_city = [];
        $rc = mysqli_query($conn, "SELECT id,city_name FROM master_city ORDER BY city_name");
        while ($r = mysqli_fetch_assoc($rc)) $opt_city[$r['id']] = $r['city_name'];

        $opt_raasi = [];
        $rr = mysqli_query($conn, "SELECT id,raasi_name FROM master_raasi ORDER BY raasi_name");
        while ($r = mysqli_fetch_assoc($rr)) $opt_raasi[$r['id']] = $r['raasi_name'];

        $opt_star = [];
        $rs = mysqli_query($conn, "SELECT id,star_name FROM master_star ORDER BY star_name");
        while ($r = mysqli_fetch_assoc($rs)) $opt_star[$r['id']] = $r['star_name'];

        $opt_dhosam_type = [];
        $rd = mysqli_query($conn, "SELECT id,dhosam_type FROM master_dhosam_type WHERE status=1 ORDER BY FIELD(dhosam_type, 'Yes', 'No')");
        while ($r = mysqli_fetch_assoc($rd)) $opt_dhosam_type[$r['id']] = $r['dhosam_type'];

        $opt_dhosam_name = [];
        $rnd = mysqli_query($conn, "SELECT id,dhosam_name FROM master_dhosam_name WHERE status=1 AND LOWER(dhosam_name) != 'no' ORDER BY dhosam_name");
        while ($r = mysqli_fetch_assoc($rnd)) $opt_dhosam_name[$r['id']] = $r['dhosam_name'];

        $opt_community = [];
        $rco = mysqli_query($conn, "SELECT id,community_name FROM master_community ORDER BY community_name");
        while ($r = mysqli_fetch_assoc($rco)) $opt_community[$r['id']] = $r['community_name'];

        $opt_sub_community = [];
        $rsc = mysqli_query($conn, "SELECT id,sub_community_name FROM master_sub_community ORDER BY sub_community_name");
        while ($r = mysqli_fetch_assoc($rsc)) $opt_sub_community[$r['id']] = $r['sub_community_name'];

        $opt_kulam = [];
        $rk = mysqli_query($conn, "SELECT id,kulam_name FROM master_kulam ORDER BY kulam_name");
        while ($r = mysqli_fetch_assoc($rk)) $opt_kulam[$r['id']] = $r['kulam_name'];

        $opt_gothiram = [];
        $rg = mysqli_query($conn, "SELECT id,gothiram_name FROM master_gothiram ORDER BY gothiram_name");
        while ($r = mysqli_fetch_assoc($rg)) $opt_gothiram[$r['id']] = $r['gothiram_name'];

        /* ===== STATUS DROPDOWN OPTIONS ===== */
        $opt_account_status = [
            'active'   => 'Active',
            'inactive' => 'Inactive'
        ];

        $opt_marital_status = [
            'unmarried' => 'Unmarried',
            'married'   => 'Married',
            'divorced'  => 'Divorced',
            'widowed'   => 'Widowed',
            'separated' => 'Separated',
            'annulled'  => 'Annulled'
        ];

        $opt_payment_status = [
            'paid'    => 'Paid',
            'unpaid'  => 'Unpaid',
            'pending' => 'Pending'
        ];

        $curr_account_status = (isset($data['reg_status']) && $data['reg_status'] === 'inactive') ? 'inactive' : 'active';
        $curr_marital_status = !empty($data['marital_status']) ? strtolower($data['marital_status']) : 'unmarried';
        if ((isset($data['payment_approval_status']) && $data['payment_approval_status'] === 'approved') || (isset($data['reg_status']) && $data['reg_status'] === 'paid')) {
            $curr_payment_status = 'paid';
        } elseif ((isset($data['payment_approval_status']) && $data['payment_approval_status'] === 'pending') || (isset($data['reg_status']) && $data['reg_status'] === 'payment_pending')) {
            $curr_payment_status = 'pending';
        } else {
            $curr_payment_status = 'unpaid';
        }
    }
}

ob_start();
?>

<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Segoe UI,Roboto,sans-serif;color:#333}
.profile-container{max-width:1400px;margin:0 auto;background:#fff;box-shadow:0 4px 25px rgba(0,0,0,.08);display:flex}
.profile-sidebar{width:300px;background:linear-gradient(180deg,#4f46e5,#3730a3);color:#fff;padding:25px;text-align:center}
.profile-content{flex:1;padding:25px}
.profile-photo{width:180px;height:220px;object-fit:cover;border-radius:10px;border:4px solid rgba(255,255,255,.2)}
.photo-placeholder{width:180px;height:220px;background:#6366f1;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:600;margin:auto}
.user-name{font-size:22px;font-weight:700;margin-top:20px}
.user-subtitle{font-size:14px;opacity:.9;margin-top:6px}

/* Sidebar: add space between the age line and other photos */
.profile-sidebar .user-subtitle{margin-bottom:14px}
.profile-sidebar .section-heading{margin-top:18px}
.profile-sidebar .thumb-grid{margin-top:8px}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:25px}
.info-group{background:#f9fafb;border-radius:8px;padding:20px;border-left:3px solid #4f46e5}
.section-heading{display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:700;margin-bottom:12px;border-bottom:1px solid #e5e7eb;padding-bottom:4px}
.info-row{display:flex;margin-bottom:14px}
.info-label{width:140px;font-size:12px;color:#6b7280;font-weight:600;text-transform:uppercase}
.info-value{flex:1;font-size:14px;font-weight:500}
.address-box{background:#eef2ff;padding:10px;border-radius:6px;font-size:13px}
.view-btn{padding:6px 12px;font-size:11px;font-weight:700;background:#4f46e5;color:#fff;border:none;border-radius:4px;cursor:pointer}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.9);display:none;align-items:center;justify-content:center;z-index:9999}
.modal-image{max-width:90%;max-height:90%;background:#fff;padding:10px;border-radius:10px}

/* ===== THUMBNAILS (ADDITION) ===== */
.thumb-grid{display:grid;grid-template-columns:repeat(auto-fill,70px);gap:8px}
.thumb-img{width:70px;height:70px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid #e5e7eb}
.thumb-img:hover{border-color:#4f46e5}

.alert{
    max-width:1400px;
    margin:20px auto;
    padding:10px 14px;
    background:#fef2f2;
    border-left:4px solid #dc2626;
    color:#991b1b;
    font-size:13px;
}

/* ================ MOBILE RESPONSIVE ================ */
@media (max-width: 1024px) {
    .profile-container {
        flex-direction: column;
        margin: 10px;
        border-radius: 12px;
        overflow: hidden;
    }
    
    .profile-sidebar {
        width: 100%;
        padding: 20px 15px;
        border-radius: 0;
    }
    
    .profile-photo, .photo-placeholder {
        width: 150px;
        height: 180px;
    }
    
    .user-name {
        font-size: 20px;
        margin-top: 15px;
    }
    
    .thumb-grid {
        grid-template-columns: repeat(auto-fill, 60px);
        justify-content: center;
    }
    
    .thumb-img {
        width: 60px;
        height: 60px;
    }
}

@media (max-width: 768px) {
    .profile-content {
        padding: 15px;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .info-group {
        padding: 15px;
    }
    
    .info-row {
        flex-direction: column;
        margin-bottom: 12px;
    }
    
    .info-label {
        width: 100%;
        margin-bottom: 4px;
        font-size: 11px;
    }
    
    .info-value {
        width: 100%;
        font-size: 13px;
    }
    
    .address-box {
        font-size: 12px;
        padding: 8px;
    }
    
    /* Mobile-specific buttons and controls */
    .mobile-action-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 15px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .mobile-action-bar button {
        flex: 1;
        min-width: 120px;
        padding: 8px 12px;
        font-size: 13px;
    }
    
    #fileInputs.mobile-file-inputs {
        flex-direction: column;
        gap: 10px;
        margin: 10px 0;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    #fileInputs.mobile-file-inputs label {
        width: 100%;
        margin: 0;
    }
    
    .section-heading {
        font-size: 14px;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .modal-overlay {
        padding: 20px;
    }
    
    .modal-image {
        max-width: 95%;
        max-height: 80%;
    }
}

@media (max-width: 480px) {
    .profile-sidebar {
        padding: 15px 10px;
    }
    
    .profile-photo, .photo-placeholder {
        width: 130px;
        height: 160px;
    }
    
    .user-name {
        font-size: 18px;
    }
    
    .user-subtitle {
        font-size: 13px;
        margin-bottom: 10px;
    }
    
    .profile-content {
        padding: 12px;
    }
    
    .mobile-action-bar {
        flex-direction: column;
    }
    
    .mobile-action-bar button {
        width: 100%;
    }
    
    .thumb-grid {
        grid-template-columns: repeat(auto-fill, 55px);
    }
    
    .thumb-img {
        width: 55px;
        height: 55px;
    }
    
    .view-btn {
        width: 100%;
        margin-top: 8px;
    }
}

/* Mobile-specific utility classes */
.mobile-only { display: none; }
.desktop-only { display: block; }

@media (max-width: 768px) {
    .mobile-only { display: block; }
    .desktop-only { display: none; }
}

/* Touch-friendly improvements */
@media (hover: none) and (pointer: coarse) {
    button, .view-btn, .thumb-img {
        min-height: 44px;
    }
    
    input, select, textarea {
        font-size: 16px; /* Prevents iOS zoom on focus */
    }
    
    .form-control, .form-select {
        padding: 10px 12px;
    }
}
</style>


<?php if ($error_msg): ?><div class="alert"><?= $error_msg ?></div><?php endif; ?>

<?php if ($show_profile): ?>
<div class="profile-container">

<div class="profile-sidebar">
<?php if ($data['profile_photo']): ?>
<img src="<?= BASE_URL.'/'.$data['profile_photo'] ?>" class="profile-photo">
<?php else: ?><div class="photo-placeholder">NO PHOTO</div><?php endif; ?>
<div class="user-name"><?= $data['name'] ?></div>
<div class="user-subtitle"><?= $data['gender_name'] ?> • <?= $data['community_name'] ?> • <?= $age ?> Years</div>

<?php if ($other_photos): ?>
<div class="section-heading">OTHER PHOTOS</div>
<div class="thumb-grid">
<?php foreach ($other_photos as $p): ?>
<img src="<?= BASE_URL.'/'.$p ?>" class="thumb-img" onclick="viewImage('<?= BASE_URL.'/'.$p ?>')">
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>

<div class="profile-content">
        
        <!-- Desktop-only action buttons -->
        <div style="display:flex;justify-content:flex-end;margin-bottom:12px;gap:8px;" class="desktop-only">
            <button id="editProfile" class="btn btn-primary btn-sm">Edit</button>
            <button id="saveProfile" class="btn btn-success btn-sm d-none">Save</button>
            <button id="cancelEdit" class="btn btn-secondary btn-sm d-none">Cancel</button>
            
            <div id="fileInputs" class="d-none" style="display:flex;gap:12px;align-items:center;margin-left:12px">
                <label style="font-size:12px">Profile Photo<input type="file" id="profilePhotoInput" name="profile_photo" accept="image/*" class="form-control form-control-sm" style="display:block;margin-top:6px"></label>
                <label style="font-size:12px">Jathagam<input type="file" id="jathagamFileInput" name="jathagam_file" accept="image/*" class="form-control form-control-sm" style="display:block;margin-top:6px"></label>
                <label style="font-size:12px">Other Photos<input type="file" id="otherPhotosInput" name="other_photos[]" multiple accept="image/*" class="form-control form-control-sm" style="display:block;margin-top:6px"></label>
            </div>
    
    <?php if ($data['jathagam_file']): ?>
    <button class="view-btn" onclick="viewImage('<?= BASE_URL.'/'.$data['jathagam_file'] ?>')">VIEW HOROSCOPE</button>
    <?php endif; ?>
    <?php if ($data['payment_proof_path']): ?>
    <button class="view-btn" style="background:#10b981;" onclick="viewImage('<?= BASE_URL.'/'.$data['payment_proof_path'] ?>')">VIEW PAYMENT RECORD</button>
    <?php endif; ?>

        </div>

        <!-- Mobile-specific action bar -->
        <div class="mobile-only mobile-action-bar">
            <button id="editProfileMobile" class="btn btn-primary btn-sm">Edit Profile</button>
            <button id="saveProfileMobile" class="btn btn-success btn-sm d-none">Save Changes</button>
            <button id="cancelEditMobile" class="btn btn-secondary btn-sm d-none">Cancel</button>
            
            <?php if ($data['jathagam_file']): ?>
                <button class="view-btn" onclick="viewImage('<?= BASE_URL.'/'.$data['jathagam_file'] ?>')">View Horoscope</button>
            <?php endif; ?>
            <?php if ($data['payment_proof_path']): ?>
                <button class="view-btn" style="background:#10b981;" onclick="viewImage('<?= BASE_URL.'/'.$data['payment_proof_path'] ?>')">View Payment Record</button>
            <?php endif; ?>
            
            <div id="fileInputsMobile" class="d-none mobile-file-inputs">
                <label style="font-size:13px;width:100%;">
                    Profile Photo
                    <input type="file" id="profilePhotoInputMobile" name="profile_photo" accept="image/*" class="form-control form-control-sm">
                </label>
                <label style="font-size:13px;width:100%;">
                    Jathagam File
                    <input type="file" id="jathagamFileInputMobile" name="jathagam_file" accept="image/*" class="form-control form-control-sm">
                </label>
                <label style="font-size:13px;width:100%;">
                    Other Photos (Multiple)
                    <input type="file" id="otherPhotosInputMobile" name="other_photos[]" multiple accept="image/*" class="form-control form-control-sm">
                </label>
            </div>
        </div>

    <div class="info-grid">

<div class="info-group">
<div class="section-heading">BASIC INFORMATION</div>
<div class="info-row" data-field="father_name" data-type="text"><div class="info-label">Father Name</div><div class="info-value" data-value="<?= htmlspecialchars($data['father_name']) ?>"><?= htmlspecialchars($data['father_name']) ?></div></div>
<div class="info-row" data-field="mother_name" data-type="text"><div class="info-label">Mother Name</div><div class="info-value" data-value="<?= htmlspecialchars($data['mother_name']) ?>"><?= htmlspecialchars($data['mother_name']) ?></div></div>
<div class="info-row" data-field="dob" data-type="date"><div class="info-label">DOB</div><div class="info-value" data-value="<?= htmlspecialchars($data['dob']) ?>"><?= htmlspecialchars($data['dob']) ?></div></div>
<div class="info-row" data-field="tob" data-type="text"><div class="info-label">TOB</div><div class="info-value" data-value="<?= htmlspecialchars($data['tob']) ?>"><?= $data['tob'] ? htmlspecialchars(date('h:i A', strtotime($data['tob']))) : '' ?></div></div>
<div class="info-row" data-field="mobile" data-type="text"><div class="info-label">Mobile</div><div class="info-value" data-value="<?= htmlspecialchars($data['mobile']) ?>"><?= htmlspecialchars($data['mobile']) ?></div></div>
<div class="info-row" data-field="email" data-type="email"><div class="info-label">Email</div><div class="info-value" data-value="<?= htmlspecialchars($data['email']) ?>"><?= htmlspecialchars($data['email']) ?></div></div>
<div class="info-row" data-field="qualification_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_qualification), ENT_QUOTES) ?>'><div class="info-label">Qualification</div><div class="info-value" data-value="<?= htmlspecialchars($data['qualification_id']) ?>"><?= $data['qualification_name'] ?></div></div>
<div class="info-row" data-field="working_city_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_city), ENT_QUOTES) ?>'><div class="info-label">Working City</div><div class="info-value" data-value="<?= htmlspecialchars($data['working_city_id']) ?>"><?= $data['working_city_name'] ?></div></div>

<div class="info-row" data-field="monthly_income" data-type="number"><div class="info-label">Monthly Income</div><div class="info-value" data-value="<?= htmlspecialchars($data['monthly_income']) ?>"><?= ($data['monthly_income'] > 0) ? 'Rs ' . number_format($data['monthly_income']) : 'Nil' ?></div></div>
<div class="info-row" data-field="address" data-type="textarea"><div class="info-label">Address</div><div class="info-value" data-value="<?= htmlspecialchars($data['address']) ?>"><div class="address-box"><?= $data['address'] ?></div></div></div>
<div class="info-row" data-field="city_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_city), ENT_QUOTES) ?>'><div class="info-label">City</div><div class="info-value" data-value="<?= htmlspecialchars($data['city_id']) ?>"><?= htmlspecialchars($data['city_name']) ?></div></div>
<div class="info-row" data-field="pincode" data-type="text"><div class="info-label">Pincode</div><div class="info-value" data-value="<?= htmlspecialchars($data['pincode']) ?>"><?= htmlspecialchars($data['pincode']) ?></div></div>
<div class="info-row" data-field="other_info" data-type="text"><div class="info-label">Others</div><div class="info-value" data-value="<?= htmlspecialchars($data['other_info']) ?>"><?= htmlspecialchars($data['other_info']) ?></div></div>


</div>

<div class="info-group">
<div class="section-heading">
<span>HOROSCOPE</span>
</div>

<div class="info-row" data-field="raasi_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_raasi), ENT_QUOTES) ?>'><div class="info-label">Raasi</div><div class="info-value" data-value="<?= htmlspecialchars($data['raasi_id']) ?>"><?= $data['raasi_name'] ?></div></div>
<div class="info-row" data-field="star_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_star), ENT_QUOTES) ?>'><div class="info-label">Star</div><div class="info-value" data-value="<?= htmlspecialchars($data['star_id']) ?>"><?= $data['star_name'] ?></div></div>
<?php $is_dhosam_yes = (strtolower(trim($data['dhosam_type'] ?? '')) === 'yes'); ?>
<div class="info-row" data-field="dhosam_type_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_dhosam_type), ENT_QUOTES) ?>' id="dhosamTypeRow"><div class="info-label">Dhosam Type</div><div class="info-value" data-value="<?= htmlspecialchars($data['dhosam_type_id']) ?>"><?= htmlspecialchars($data['dhosam_type'] ?? '') ?></div></div>
<div class="info-row" data-field="dhosam_name_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_dhosam_name), ENT_QUOTES) ?>' id="dhosamNameRow" style="<?= $is_dhosam_yes ? 'display:flex;' : 'display:none;' ?>"><div class="info-label">Dhosam</div><div class="info-value" data-value="<?= htmlspecialchars($data['dhosam_name_id']) ?>"><?= htmlspecialchars($data['dhosam_name'] ?? '') ?></div></div>

<div class="section-heading">COMMUNITY</div>
<div class="info-row" data-field="community_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_community), ENT_QUOTES) ?>'><div class="info-label">Community</div><div class="info-value" data-value="<?= htmlspecialchars($data['community_id']) ?>"><?= $data['community_name'] ?></div></div>
<div class="info-row" data-field="sub_community_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_sub_community), ENT_QUOTES) ?>'><div class="info-label">Sub Community</div><div class="info-value" data-value="<?= htmlspecialchars($data['sub_community_id']) ?>"><?= $data['sub_community_name'] ?></div></div>
<div class="info-row" data-field="kulam_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_kulam), ENT_QUOTES) ?>'><div class="info-label">Kulam</div><div class="info-value" data-value="<?= htmlspecialchars($data['kulam_id']) ?>"><?= $data['kulam_name'] ?></div></div>
<div class="info-row" data-field="gothiram_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_gothiram), ENT_QUOTES) ?>'><div class="info-label">Gothiram</div><div class="info-value" data-value="<?= htmlspecialchars($data['gothiram_id']) ?>"><?= $data['gothiram_name'] ?></div></div>

<div class="section-heading"><span>PROFILE</span></div>
<?php $profile_statuses = ['new'=>'New','paid'=>'Paid','active'=>'Active','inactive'=>'Inactive','married'=>'Married','payment_pending'=>'Payment Pending','rejected'=>'Rejected']; ?>
<div class="info-row" data-field="username" data-type="text"><div class="info-label">User Name</div><div class="info-value" data-value="<?= htmlspecialchars($data['username']) ?>"><?= htmlspecialchars($data['username']) ?></div></div>
<div class="info-row" data-field="password" data-type="text"><div class="info-label">Password</div><div class="info-value" data-value="<?= htmlspecialchars($data['password_hash']) ?>"><?= htmlspecialchars($data['password_hash']) ?></div></div>
<div class="info-row" data-field="account_status" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_account_status), ENT_QUOTES) ?>'><div class="info-label">Account Status</div><div class="info-value" data-value="<?= htmlspecialchars($curr_account_status) ?>"><?= htmlspecialchars($opt_account_status[$curr_account_status] ?? 'Active') ?></div></div>
<div class="info-row" data-field="marital_status" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_marital_status), ENT_QUOTES) ?>'><div class="info-label">Marital Status</div><div class="info-value" data-value="<?= htmlspecialchars($curr_marital_status) ?>"><?= htmlspecialchars($opt_marital_status[$curr_marital_status] ?? 'Unmarried') ?></div></div>
<div class="info-row" data-field="payment_status" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_payment_status), ENT_QUOTES) ?>'><div class="info-label">Payment Status</div><div class="info-value" data-value="<?= htmlspecialchars($curr_payment_status) ?>"><?= htmlspecialchars($opt_payment_status[$curr_payment_status] ?? 'Unpaid') ?></div></div>

</div>

</div>
</div>
</div>
<?php endif; ?>

<div class="modal-overlay" id="imageModal" onclick="this.style.display='none'">
<img class="modal-image">
</div>

<script>
// Mobile view image modal
function viewImage(src){
    const m = document.getElementById('imageModal');
    m.style.display = 'flex';
    m.querySelector('img').src = src;
}

// Close modal on background click
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});

// Inline edit/save logic with mobile support
(() => {
    const userId = <?= (int)$user_id ?>;
    let editing = false;
    let originalValues = {};

    // Get all button references (desktop and mobile)
    const editBtns = [
        document.getElementById('editProfile'),
        document.getElementById('editProfileMobile')
    ].filter(Boolean);
    
    const saveBtns = [
        document.getElementById('saveProfile'),
        document.getElementById('saveProfileMobile')
    ].filter(Boolean);
    
    const cancelBtns = [
        document.getElementById('cancelEdit'),
        document.getElementById('cancelEditMobile')
    ].filter(Boolean);
    
    const fileInputsContainers = [
        document.getElementById('fileInputs'),
        document.getElementById('fileInputsMobile')
    ].filter(Boolean);
    
    const profilePhotoInputs = [
        document.getElementById('profilePhotoInput'),
        document.getElementById('profilePhotoInputMobile')
    ].filter(Boolean);
    
    const jathagamFileInputs = [
        document.getElementById('jathagamFileInput'),
        document.getElementById('jathagamFileInputMobile')
    ].filter(Boolean);
    
    const otherPhotosInputs = [
        document.getElementById('otherPhotosInput'),
        document.getElementById('otherPhotosInputMobile')
    ].filter(Boolean);

    function enterEdit() {
        originalValues = {};
        document.querySelectorAll('.info-row[data-field]').forEach(row => {
            const field = row.dataset.field;
            const type = row.dataset.type || 'text';
            const value = row.querySelector('.info-value').dataset.value ?? row.querySelector('.info-value').textContent.trim();
            originalValues[field] = value;
            const inputWrap = row.querySelector('.info-value');
            inputWrap.innerHTML = '';
            let input;
            if (type === 'textarea') {
                input = document.createElement('textarea');
                input.className = 'form-control';
                input.rows = 3;
                input.style.width = '100%';
                input.style.fontSize = window.innerWidth <= 768 ? '14px' : '13px';
            } else if (type === 'select') {
                input = document.createElement('select');
                input.className = 'form-select form-select-sm';
                input.style.fontSize = window.innerWidth <= 768 ? '14px' : '13px';
                input.style.width = '100%';
                try {
                    const opts = JSON.parse(row.dataset.options || '{}');
                    const hasEmpty = Object.keys(opts).some(k => k === '');
                    if (!hasEmpty) {
                        const emptyOpt = document.createElement('option');
                        emptyOpt.value = '';
                        emptyOpt.text = '-- Select --';
                        input.appendChild(emptyOpt);
                    }
                    Object.keys(opts).forEach(k => {
                        const o = document.createElement('option'); 
                        o.value = k; 
                        o.text = opts[k];
                        input.appendChild(o);
                    });
                } catch(e) {}
            } else {
                input = document.createElement('input');
                input.type = type === 'number' ? 'number' : (type === 'date' ? 'date' : (type === 'email' ? 'email' : 'text'));
                input.className = 'form-control form-control-sm';
                input.style.fontSize = window.innerWidth <= 768 ? '14px' : '13px';
                input.style.width = '100%';
            }
            input.value = value;
            inputWrap.appendChild(input);

            if (field === 'dhosam_type_id') {
                input.addEventListener('change', function() {
                    const selText = input.options[input.selectedIndex]?.text?.trim().toLowerCase();
                    const dnRow = document.getElementById('dhosamNameRow');
                    if (dnRow) {
                        if (selText === 'yes') {
                            dnRow.style.display = 'flex';
                        } else {
                            dnRow.style.display = 'none';
                            const dnSelect = dnRow.querySelector('select');
                            if (dnSelect) dnSelect.value = '';
                        }
                    }
                });
            }
        });

        // Set initial visibility of Dhosam Name row on entering edit
        const initDtRow = document.getElementById('dhosamTypeRow');
        const initDnRow = document.getElementById('dhosamNameRow');
        if (initDtRow && initDnRow) {
            const dtSelect = initDtRow.querySelector('select');
            if (dtSelect) {
                const selText = dtSelect.options[dtSelect.selectedIndex]?.text?.trim().toLowerCase();
                initDnRow.style.display = (selText === 'yes') ? 'flex' : 'none';
            }
        }
        
        // Show file inputs when editing
        fileInputsContainers.forEach(container => {
            if (container) container.classList.remove('d-none');
        });
        
        editing = true;
        
        // Show/hide appropriate buttons
        editBtns.forEach(btn => btn?.classList.add('d-none'));
        saveBtns.forEach(btn => btn?.classList.remove('d-none'));
        cancelBtns.forEach(btn => btn?.classList.remove('d-none'));
        
        // Scroll to top on mobile when editing
        if (window.innerWidth <= 768) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function exitEdit(revert = false) {
        document.querySelectorAll('.info-row[data-field]').forEach(row => {
            const field = row.dataset.field;
            const type = row.dataset.type || 'text';
            const display = row.querySelector('.info-value');
            const inputEl = display.querySelector('input,textarea,select');
            const val = revert ? (originalValues[field] ?? '') : (inputEl ? inputEl.value : (display.dataset.value ?? display.textContent.trim()));
            
            // Update data-value and visible text
            display.dataset.value = val;
            
            if (field === 'monthly_income') {
                display.innerHTML = (val && Number(val) > 0) ? '₹' + Number(val).toLocaleString() : 'Nil';
            } else if (type === 'textarea' || display.querySelector('.address-box')) {
                display.innerHTML = '<div class="address-box">' + val.replace(/\n/g,'<br>') + '</div>';
            } else if (type === 'select') {
                let label = val;
                try {
                    const opts = JSON.parse(row.dataset.options || '{}');
                    if (opts && typeof opts === 'object') label = opts[val] ?? val;
                } catch(e) {}
                display.textContent = label;
            } else {
                display.textContent = val;
            }
        });
        
        // Hide file inputs when exiting
        fileInputsContainers.forEach(container => {
            if (container) container.classList.add('d-none');
        });
        
        // Update Dhosam Name row visibility after exiting edit
        const exitDtRow = document.getElementById('dhosamTypeRow');
        const exitDnRow = document.getElementById('dhosamNameRow');
        if (exitDtRow && exitDnRow) {
            const dtText = exitDtRow.querySelector('.info-value')?.textContent?.trim().toLowerCase();
            exitDnRow.style.display = (dtText === 'yes') ? 'flex' : 'none';
        }

        editing = false;
        
        // Show/hide appropriate buttons
        editBtns.forEach(btn => btn?.classList.remove('d-none'));
        saveBtns.forEach(btn => btn?.classList.add('d-none'));
        cancelBtns.forEach(btn => btn?.classList.add('d-none'));
        
        // Clear file inputs
        profilePhotoInputs.forEach(input => {
            if (input) input.value = '';
        });
        jathagamFileInputs.forEach(input => {
            if (input) input.value = '';
        });
        otherPhotosInputs.forEach(input => {
            if (input) input.value = '';
        });
    }

    function saveProfile() {
        const form = new FormData();
        form.append('action', 'update_profile');
        form.append('user_id', userId);
        
        // Check if Dhosam Type is No
        const dtRowEl = document.getElementById('dhosamTypeRow');
        const dtSelectEl = dtRowEl ? dtRowEl.querySelector('select') : null;
        const isNoDhosam = dtSelectEl && dtSelectEl.options[dtSelectEl.selectedIndex]?.text?.trim().toLowerCase() !== 'yes';

        // Collect all field values
        document.querySelectorAll('.info-row[data-field]').forEach(row => {
            const field = row.dataset.field;
            if (field === 'dhosam_name_id' && isNoDhosam) {
                form.append(field, '');
                return;
            }
            const input = row.querySelector('input,textarea,select');
            if (!input) return;
            if (field === 'password' && input.value.trim() === '') return; // Skip empty password
            form.append(field, input.value);
        });

        // Append files from either desktop or mobile inputs
        const appendFiles = (inputElement) => {
            if (inputElement && inputElement.files && inputElement.files[0]) {
                form.append(inputElement.name, inputElement.files[0]);
            }
        };
        
        // Try desktop inputs first, then mobile
        if (profilePhotoInputs[0]?.files?.[0]) {
            form.append('profile_photo', profilePhotoInputs[0].files[0]);
        } else if (profilePhotoInputs[1]?.files?.[0]) {
            form.append('profile_photo', profilePhotoInputs[1].files[0]);
        }
        
        if (jathagamFileInputs[0]?.files?.[0]) {
            form.append('jathagam_file', jathagamFileInputs[0].files[0]);
        } else if (jathagamFileInputs[1]?.files?.[0]) {
            form.append('jathagam_file', jathagamFileInputs[1].files[0]);
        }
        
        // Handle multiple other photos
        const otherPhotos = [];
        otherPhotosInputs.forEach(input => {
            if (input?.files) {
                Array.from(input.files).forEach(file => {
                    otherPhotos.push(file);
                });
            }
        });
        
        otherPhotos.forEach((file, index) => {
            form.append('other_photos[]', file);
        });

        // Show loading state
        saveBtns.forEach(btn => {
            if (btn) {
                const originalText = btn.textContent;
                btn.textContent = 'Saving...';
                btn.disabled = true;
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.disabled = false;
                }, 2000);
            }
        });

        fetch('view_user.php', {
            method: 'POST',
            body: form
        }).then(async r => {
            const text = await r.text();
            if (!r.ok) {
                console.error('HTTP error', r.status, text);
                alert('Server error: ' + r.status + '\n' + (text || r.statusText));
                return;
            }
            try {
                const json = JSON.parse(text);
                if (json.status === 'success') {
                    exitEdit(false);
                    alert('Profile updated successfully!');
                    // Small delay before reload to show success message
                    setTimeout(() => location.reload(), 500);
                } else {
                    console.error('Server response', json);
                    alert(json.message || 'Update failed. Please try again.');
                }
            } catch (e) {
                console.error('Failed to parse JSON response', text, e);
                alert('Server error — please check console for details.');
            }
        }).catch(err => {
            console.error('Fetch error', err);
            alert('Network error. Please check your connection.');
        });
    }

    // Add event listeners to all edit buttons
    editBtns.forEach(btn => {
        if (btn) btn.addEventListener('click', enterEdit);
    });
    
    // Add event listeners to all save buttons
    saveBtns.forEach(btn => {
        if (btn) btn.addEventListener('click', saveProfile);
    });
    
    // Add event listeners to all cancel buttons
    cancelBtns.forEach(btn => {
        if (btn) btn.addEventListener('click', () => exitEdit(true));
    });
    
    // Add keyboard support for mobile
    document.addEventListener('keydown', (e) => {
        if (editing && e.key === 'Escape') {
            exitEdit(true);
        }
    });

    // Auto-enter edit mode if edit=1 in URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('edit') === '1' || urlParams.has('edit')) {
        enterEdit();
    }
})();
</script>

<?php
$content = ob_get_clean();
require "layout.php";
?>