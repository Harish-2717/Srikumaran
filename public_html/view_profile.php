<?php
require "config/init.php";

require "navbar.php"; 

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
        'kulam_id','gothiram_id','marital_status','address','username','password','status'
    ];
    $sets = [];
    $status_to_update = null;
    foreach ($allowed as $f) {
        if (!isset($_POST[$f])) continue;
        $v = trim($_POST[$f]);
        if ($f === 'password') {
            if ($v === '') continue; // skip empty password
            $v_esc = mysqli_real_escape_string($conn, $v);
            $sets[] = "`password_hash`='".$v_esc."'";
            continue;
        }
        if ($f === 'status') {
            $status_to_update = mysqli_real_escape_string($conn, $v);
            continue;
        }
        // treat *_id fields as integers and allow NULL
        if (substr($f, -3) === '_id') {
            if ($v === '') {
                $sets[] = "`$f`=NULL";
            } else {
                $sets[] = "`$f`=" . ((int)$v);
            }
            continue;
        }
        $v_esc = mysqli_real_escape_string($conn, $v);
        $sets[] = "`$f`='".$v_esc."'";
    }

    if (!$user_id || (empty($sets) && $status_to_update === null)) { ob_clean(); echo json_encode(['status'=>'error','message'=>'invalid_input']); exit; }

    $ok = true; $err = '';
    $debug_info = [];
    if (!empty($sets)) {
        $sql = "UPDATE users SET " . implode(',', $sets) . " WHERE id=$user_id";
        $debug_info['update_sql'] = $sql;
        $res1 = mysqli_query($conn, $sql);
        if (!$res1) { $ok = false; $err = mysqli_error($conn); }
        $debug_info['update_ok'] = (bool)$res1;
        $debug_info['update_affected'] = mysqli_affected_rows($conn);
    }

    if ($ok && $status_to_update !== null) {
        $allowed_status = ['new','paid','active','inactive','married','payment_pending','rejected'];
        if (!in_array($status_to_update, $allowed_status, true)) {
            ob_clean(); echo json_encode(['status'=>'error','message'=>'invalid_status']); exit;
        }
        $sql2 = "UPDATE user_registration_status SET status='".mysqli_real_escape_string($conn,$status_to_update)."', updated_at=NOW() WHERE user_id=$user_id";
        $debug_info['status_sql'] = $sql2;
        $res2 = mysqli_query($conn, $sql2);
        if (!$res2) { $ok = false; $err = mysqli_error($conn); }
        $debug_info['status_ok'] = (bool)$res2;
        $debug_info['status_affected'] = mysqli_affected_rows($conn);
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
        urs.status
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
        $rd = mysqli_query($conn, "SELECT id,dhosam_type FROM master_dhosam_type ORDER BY dhosam_type");
        while ($r = mysqli_fetch_assoc($rd)) $opt_dhosam_type[$r['id']] = $r['dhosam_type'];

        $opt_dhosam_name = [];
        $rnd = mysqli_query($conn, "SELECT id,dhosam_name FROM master_dhosam_name ORDER BY dhosam_name");
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

        /* ===== MARITAL STATUS OPTIONS (static list) ===== */
        $opt_marital = [
            'unmarried' => 'Unmarried',
            'married'   => 'Married',
            'divorced'  => 'Divorced',
            'widowed'   => 'Widowed',
            'separated' => 'Separated',
            'annulled'  => 'Annulled'
        ];
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


.view-btn{
    padding:6px 12px;
    font-size:11px;
    font-weight:700;
    background:#ffffff;
    color:#4f46e5;
    border:none;
    border-radius:4px;
    cursor:pointer
}
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
    
    .view-btn {
        margin-top: 15px;
        padding: 8px 16px;
        font-size: 12px;
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
        padding: 10px;
        font-size: 13px;
    }
}

/* Touch-friendly improvements */
@media (hover: none) and (pointer: coarse) {
    button, .view-btn, .thumb-img {
        min-height: 44px;
    }
    
    input, select, textarea {
        font-size: 16px; /* Prevents iOS zoom on focus */
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

<?php if ($data['jathagam_file']): ?>
    <button class="view-btn" onclick="viewImage('<?= BASE_URL.'/'.$data['jathagam_file'] ?>')">VIEW JADAGAM</button>
<?php endif; ?>
</div>

<div class="profile-content">
    <div class="info-grid">

<div class="info-group">
<div class="section-heading">BASIC INFORMATION</div>
<div class="info-row" data-field="father_name" data-type="text"><div class="info-label">Father Name</div><div class="info-value" data-value="<?= htmlspecialchars($data['father_name']) ?>"><?= htmlspecialchars($data['father_name']) ?></div></div>
<div class="info-row" data-field="mother_name" data-type="text"><div class="info-label">Mother Name</div><div class="info-value" data-value="<?= htmlspecialchars($data['mother_name']) ?>"><?= htmlspecialchars($data['mother_name']) ?></div></div>
<div class="info-row" data-field="dob" data-type="date"><div class="info-label">DOB</div><div class="info-value" data-value="<?= htmlspecialchars($data['dob']) ?>"><?= htmlspecialchars($data['dob']) ?></div></div>
<div class="info-row" data-field="tob" data-type="text"><div class="info-label">TOB</div><div class="info-value" data-value="<?= htmlspecialchars($data['tob']) ?>"><?= htmlspecialchars($data['tob']) ?></div></div>
<div class="info-row" data-field="mobile" data-type="text"><div class="info-label">Mobile</div>
<div class="info-value" data-value=" +91 87540 32742"> +91 87540 32742 / 0422 2666 505</div></div>
<div class="info-row" data-field="email" data-type="email"><div class="info-label">Email</div><div class="info-value" data-value="<?= htmlspecialchars($data['email']) ?>"><?= htmlspecialchars($data['email']) ?></div></div>
<div class="info-row" data-field="qualification_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_qualification), ENT_QUOTES) ?>'><div class="info-label">Qualification</div><div class="info-value" data-value="<?= htmlspecialchars($data['qualification_id']) ?>"><?= $data['qualification_name'] ?></div></div>
<div class="info-row" data-field="working_city_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_city), ENT_QUOTES) ?>'><div class="info-label">Working City</div><div class="info-value" data-value="<?= htmlspecialchars($data['working_city_id']) ?>"><?= $data['working_city_name'] ?></div></div>

<div class="info-row" data-field="marital_status" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_marital), ENT_QUOTES) ?>'><div class="info-label">Marital Status</div><div class="info-value" data-value="<?= htmlspecialchars($data['marital_status']) ?>"><?= htmlspecialchars($opt_marital[$data['marital_status']] ?? ucfirst($data['marital_status'])) ?></div></div>
<div class="info-row" data-field="monthly_income" data-type="number"><div class="info-label">Monthly Income</div><div class="info-value" data-value="<?= htmlspecialchars($data['monthly_income']) ?>">₹<?= number_format($data['monthly_income']) ?></div></div>
<div class="info-row" data-field="address" data-type="textarea"><div class="info-label">Address</div><div class="info-value" data-value="<?= htmlspecialchars($data['address']) ?>"><div class="address-box"><?= $data['address'] ?></div></div></div>
<div class="info-row" data-field="city_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_city), ENT_QUOTES) ?>'><div class="info-label">City</div><div class="info-value" data-value="<?= htmlspecialchars($data['city_id']) ?>"><?= htmlspecialchars($data['city_name']) ?></div></div>
<div class="info-row" data-field="pincode" data-type="text"><div class="info-label">Pincode</div><div class="info-value" data-value="<?= htmlspecialchars($data['pincode']) ?>"><?= htmlspecialchars($data['pincode']) ?></div></div>
</div>

<div class="info-group">
<div class="section-heading">
<span>HOROSCOPE</span>
</div>

<div class="info-row" data-field="raasi_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_raasi), ENT_QUOTES) ?>'><div class="info-label">Raasi</div><div class="info-value" data-value="<?= htmlspecialchars($data['raasi_id']) ?>"><?= $data['raasi_name'] ?></div></div>
<div class="info-row" data-field="star_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_star), ENT_QUOTES) ?>'><div class="info-label">Star</div><div class="info-value" data-value="<?= htmlspecialchars($data['star_id']) ?>"><?= $data['star_name'] ?></div></div>
<div class="info-row" data-field="dhosam_type_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_dhosam_type), ENT_QUOTES) ?>'><div class="info-label">Dhosam Type</div><div class="info-value" data-value="<?= htmlspecialchars($data['dhosam_type_id']) ?>"><?= htmlspecialchars($data['dhosam_type']) ?></div></div>
<div class="info-row" data-field="dhosam_name_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_dhosam_name), ENT_QUOTES) ?>'><div class="info-label">Dhosam Name</div><div class="info-value" data-value="<?= htmlspecialchars($data['dhosam_name_id']) ?>"><?= htmlspecialchars($data['dhosam_name']) ?></div></div>

<div class="section-heading">COMMUNITY</div>
<div class="info-row" data-field="community_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_community), ENT_QUOTES) ?>'><div class="info-label">Community</div><div class="info-value" data-value="<?= htmlspecialchars($data['community_id']) ?>"><?= $data['community_name'] ?></div></div>
<div class="info-row" data-field="sub_community_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_sub_community), ENT_QUOTES) ?>'><div class="info-label">Sub Community</div><div class="info-value" data-value="<?= htmlspecialchars($data['sub_community_id']) ?>"><?= $data['sub_community_name'] ?></div></div>
<div class="info-row" data-field="kulam_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_kulam), ENT_QUOTES) ?>'><div class="info-label">Kulam</div><div class="info-value" data-value="<?= htmlspecialchars($data['kulam_id']) ?>"><?= $data['kulam_name'] ?></div></div>
<div class="info-row" data-field="gothiram_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_gothiram), ENT_QUOTES) ?>'><div class="info-label">Gothiram</div><div class="info-value" data-value="<?= htmlspecialchars($data['gothiram_id']) ?>"><?= $data['gothiram_name'] ?></div></div>

<div class="section-heading"><span>PROFILE</span></div>
<?php $profile_statuses = ['new'=>'New','paid'=>'Paid','active'=>'Active','inactive'=>'Inactive','married'=>'Married','payment_pending'=>'Payment Pending','rejected'=>'Rejected']; ?>
<div class="info-row" data-field="username" data-type="text"><div class="info-label">User Name</div><div class="info-value" data-value="<?= htmlspecialchars($data['username']) ?>"><?= htmlspecialchars($data['username']) ?></div></div>
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

// Add mobile-specific touch improvements
document.addEventListener('DOMContentLoaded', function() {
    // Make thumbnails more touch-friendly on mobile
    const thumbImgs = document.querySelectorAll('.thumb-img');
    thumbImgs.forEach(img => {
        img.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.95)';
        });
        img.addEventListener('touchend', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Prevent zoom on iOS when focusing inputs
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            if (window.innerWidth <= 768) {
                this.style.fontSize = '16px';
            }
        });
        input.addEventListener('blur', function() {
            this.style.fontSize = '';
        });
    });
});
</script>