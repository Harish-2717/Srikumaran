<?php
require "config/init.php";

// User profile page - for logged-in users to view/edit their own profile
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

/* ================ DELETE PHOTO (AJAX) ================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim($_POST['action'] ?? '') === 'delete_photo') {
    ob_start();
    header('Content-Type: application/json; charset=utf-8');
    if (!isset($_SESSION['user_id'])) { ob_clean(); echo json_encode(['status'=>'error','message'=>'unauthorized']); exit; }
    
    $user_id = (int)$_SESSION['user_id'];
    $photo_id = (int)($_POST['photo_id'] ?? 0);
    
    if ($photo_id <= 0) { ob_clean(); echo json_encode(['status'=>'error','message'=>'invalid_photo']); exit; }
    
    $photo_check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo_path FROM user_other_photos WHERE id=$photo_id AND user_id=$user_id"));
    if (!$photo_check) { ob_clean(); echo json_encode(['status'=>'error','message'=>'not_found']); exit; }
    
    $photo_path = dirname(__FILE__) . '/' . $photo_check['photo_path'];
    if (file_exists($photo_path)) @unlink($photo_path);
    
    $del = mysqli_query($conn, "DELETE FROM user_other_photos WHERE id=$photo_id AND user_id=$user_id");
    
    ob_clean();
    if ($del) {
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error','message'=>mysqli_error($conn)]);
    }
    exit;
}

/* ================ SET PROFILE FROM OTHER PHOTO (AJAX) ================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim($_POST['action'] ?? '') === 'set_profile_photo') {
    ob_start();
    header('Content-Type: application/json; charset=utf-8');
    if (!isset($_SESSION['user_id'])) { ob_clean(); echo json_encode(['status'=>'error','message'=>'unauthorized']); exit; }
    $user_id = (int)$_SESSION['user_id'];
    $photo_id = (int)($_POST['photo_id'] ?? 0);
    if ($photo_id <= 0) { ob_clean(); echo json_encode(['status'=>'error','message'=>'invalid_photo']); exit; }
    $photo_check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo_path FROM user_other_photos WHERE id=$photo_id AND user_id=$user_id"));
    if (!$photo_check) { ob_clean(); echo json_encode(['status'=>'error','message'=>'not_found']); exit; }
    $photo_rel = mysqli_real_escape_string($conn, $photo_check['photo_path']);
    $upd = mysqli_query($conn, "UPDATE users SET profile_photo='$photo_rel' WHERE id=$user_id");
    ob_clean();
    if ($upd) echo json_encode(['status'=>'success']); else echo json_encode(['status'=>'error','message'=>mysqli_error($conn)]);
    exit;
}

/* ================ UPLOAD OTHER PHOTOS (AJAX) ================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim($_POST['action'] ?? '') === 'upload_other_photos') {
    ob_start();
    header('Content-Type: application/json; charset=utf-8');
    if (!isset($_SESSION['user_id'])) { ob_clean(); echo json_encode(['status'=>'error','message'=>'unauthorized']); exit; }
    
    $user_id = (int)$_SESSION['user_id'];
    $uploaded_photos = [];
    $errors = [];
    
    if (!empty($_FILES['photos']['name'][0])) {
        $project_root = dirname(__FILE__);
        
        foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
            if (!$tmp) continue;
            
            $oname = $_FILES['photos']['name'][$i];
            $ext = strtolower(pathinfo($oname, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($ext, $allowed_ext)) {
                $errors[] = "File $oname has invalid extension";
                continue;
            }
            
            $dest_rel = 'uploads/other/'.time().'_'.rand(1000,9999).'_'.$i.'.'.$ext;
            $dest_abs = $project_root . '/' . $dest_rel;
            @mkdir(dirname($dest_abs), 0755, true);
            
            if (move_uploaded_file($tmp, $dest_abs)) {
                mysqli_query($conn, "INSERT INTO user_other_photos (user_id,photo_path) VALUES ($user_id,'".mysqli_real_escape_string($conn,$dest_rel)."')");
                $photo_id = mysqli_insert_id($conn);
                $uploaded_photos[] = [
                    'id' => $photo_id,
                    'path' => $dest_rel
                ];
            } else {
                $errors[] = "Failed to upload $oname";
            }
        }
    }
    
    ob_clean();
    if (!empty($uploaded_photos)) {
        echo json_encode(['status'=>'success', 'photos'=>$uploaded_photos]);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'No photos uploaded', 'errors'=>$errors]);
    }
    exit;
}

/* ================ PROFILE UPDATE (AJAX) ================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim($_POST['action'] ?? '') === 'update_profile') {
    ob_start();
    header('Content-Type: application/json; charset=utf-8');
    if (!isset($_SESSION['user_id'])) { ob_clean(); echo json_encode(['status'=>'error','message'=>'unauthorized']); exit; }
    
    $user_id = (int)$_SESSION['user_id'];
    $allowed = [
        'father_name','mother_name','mobile','email','dob','tob',
        'monthly_income','qualification_id','working_city_id','city_id','pincode','raasi_id','star_id',
        'dhosam_type_id','dhosam_name_id','community_id','sub_community_id',
        'kulam_id','gothiram_id','marital_status','address','password','status'
    ];
    
    $sets = [];
    $status_to_update = null;
    foreach ($allowed as $f) {
        if (!isset($_POST[$f])) continue;
        $v = trim($_POST[$f]);
        
        if ($f === 'password') {
            if ($v === '') continue;
            $v_esc = mysqli_real_escape_string($conn, $v);
            $sets[] = "`password_hash`='".$v_esc."'";
            continue;
        }
        
        if ($f === 'status') {
            $status_to_update = mysqli_real_escape_string($conn, $v);
            continue;
        }
        
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

    if (empty($sets) && $status_to_update === null) { ob_clean(); echo json_encode(['status'=>'error','message'=>'no_changes']); exit; }

    $ok = true; $err = '';
    if (!empty($sets)) {
        $sql = "UPDATE users SET " . implode(',', $sets) . " WHERE id=$user_id";
        $res = mysqli_query($conn, $sql);
        if (!$res) { $ok = false; $err = mysqli_error($conn); }
    }
    
    if ($ok && $status_to_update !== null) {
        $allowed_status = ['married'];
        if (!in_array($status_to_update, $allowed_status, true)) {
            ob_clean(); echo json_encode(['status'=>'error','message'=>'invalid_status']); exit;
        }
        $sql2 = "UPDATE user_registration_status SET status='".mysqli_real_escape_string($conn,$status_to_update)."', updated_at=NOW() WHERE user_id=$user_id";
        $res2 = mysqli_query($conn, $sql2);
        if (!$res2) { $ok = false; $err = mysqli_error($conn); }
    }
    
    // Handle file uploads
    if ($ok) {
        $project_root = dirname(__FILE__);
        
        // Profile photo
        if (!empty($_FILES['profile_photo']['tmp_name'])) {
            $pf = $_FILES['profile_photo'];
            $ext = strtolower(pathinfo($pf['name'], PATHINFO_EXTENSION));
            $dest_rel = 'uploads/profile/'.time().'_'.rand(1000,9999).'.'.$ext;
            $dest_abs = $project_root . '/' . $dest_rel;
            @mkdir(dirname($dest_abs), 0755, true);
            if (move_uploaded_file($pf['tmp_name'], $dest_abs)) {
                mysqli_query($conn, "UPDATE users SET profile_photo='".mysqli_real_escape_string($conn,$dest_rel)."' WHERE id=$user_id");
            }
        }
        
        // Jathagam file
        if (!empty($_FILES['jathagam_file']['tmp_name'])) {
            $jf = $_FILES['jathagam_file'];
            $ext = strtolower(pathinfo($jf['name'], PATHINFO_EXTENSION));
            $dest_rel = 'uploads/jathagam/'.time().'_'.rand(1000,9999).'.'.$ext;
            $dest_abs = $project_root . '/' . $dest_rel;
            @mkdir(dirname($dest_abs), 0755, true);
            if (move_uploaded_file($jf['tmp_name'], $dest_abs)) {
                mysqli_query($conn, "UPDATE users SET jathagam_file='".mysqli_real_escape_string($conn,$dest_rel)."' WHERE id=$user_id");
            }
        }
    }
    
    ob_clean();
    if ($ok) {
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error','message'=>mysqli_error($conn)]);
    }
    exit;
}

/* ================= FETCH USER PROFILE ================= */
$user_id = (int)$_SESSION['user_id'];
$data = null;
$age = '-';
$other_photos = [];

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

if ($data) {
    $age = $data['dob']
        ? (new DateTime())->diff(new DateTime($data['dob']))->y
        : '-';

    /* ===== OTHER PHOTOS ===== */
    $opq = mysqli_query($conn, "SELECT id,photo_path FROM user_other_photos WHERE user_id=$user_id");
    while ($op = mysqli_fetch_assoc($opq)) {
        $other_photos[] = $op;
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

    $opt_marital = [
        'unmarried' => 'Unmarried',
        'married'   => 'Married',
        'divorced'  => 'Divorced',
        'widowed'   => 'Widowed',
        'separated' => 'Separated',
        'annulled'  => 'Annulled'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Matrimony</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;color:#333;background:#f8fafc}
.profile-container{max-width:1400px;margin:20px auto;background:#fff;box-shadow:0 5px 15px rgba(0,0,0,0.08);border-radius:12px;overflow:hidden}
.profile-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:30px;color:white}
.profile-content{padding:30px;display:grid;grid-template-columns:1fr 1fr;gap:30px}
@media(max-width:992px){.profile-content{grid-template-columns:1fr}}
.info-card{background:#fff;border-radius:10px;padding:25px;box-shadow:0 2px 8px rgba(0,0,0,0.05);border:1px solid #e9ecef}
.section-title{font-size:16px;font-weight:700;color:#4f46e5;margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center}
.info-row{display:flex;margin-bottom:15px;align-items:flex-start}
.info-label{width:160px;font-size:13px;color:#64748b;font-weight:600;flex-shrink:0}
.info-value{flex:1;font-size:14px;font-weight:500;color:#1e293b}
.info-value .form-control,.info-value .form-select{padding:6px 12px;font-size:13px}
.address-box{background:#f8fafc;padding:12px;border-radius:8px;border-left:3px solid#4f46e5;font-size:13px}
.btn-group-sm{display:flex;gap:8px;justify-content:flex-end}
/* Profile Photo Section */
.profile-photo-section{text-align:center;margin-bottom:30px}
.profile-photo-wrapper{position:relative;display:inline-block;border-radius:12px;overflow:hidden;box-shadow:0 10px 25px rgba(0,0,0,0.1)}
.profile-photo{width:220px;height:280px;object-fit:cover}
.profile-photo-placeholder{width:220px;height:280px;background:linear-gradient(135deg,#cbd5e1,#94a3b8);display:flex;align-items:center;justify-content:center;color:white;font-size:16px;font-weight:600}
.photo-actions{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.7);padding:12px;display:flex;justify-content:center;gap:10px;transform:translateY(100%);transition:transform 0.3s}
.profile-photo-wrapper:hover .photo-actions{transform:translateY(0)}
.photo-action-btn{width:36px;height:36px;border-radius:50%;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;transition:all 0.2s}
.photo-action-btn.edit{background:#3b82f6;color:white}
.photo-action-btn.edit:hover{background:#2563eb}
.photo-action-btn.delete{background:#ef4444;color:white}
.photo-action-btn.delete:hover{background:#dc2626}
.photo-action-btn.view{background:#10b981;color:white}
.photo-action-btn.view:hover{background:#059669}
.user-info-header{text-align:center;margin-top:20px}
.user-name{font-size:24px;font-weight:700;margin-bottom:5px}
.user-details{font-size:14px;color:rgba(255,255,255,0.9)}
/* Other Photos Section */
.other-photos-section{margin-top:30px}
.photos-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(100px, 1fr));gap:12px;margin-top:15px}
.photo-item{position:relative;border-radius:8px;overflow:hidden;box-shadow:0 3px 6px rgba(0,0,0,0.1);transition:transform 0.2s}
.photo-item:hover{transform:translateY(-3px);box-shadow:0 6px 12px rgba(0,0,0,0.15)}
.photo-item img{width:100%;height:100px;object-fit:cover;display:block}
.photo-remove-btn{position:absolute;top:5px;right:5px;width:24px;height:24px;background:rgba(239,68,68,0.9);color:white;border:none;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;cursor:pointer;opacity:0;transition:opacity 0.2s}
.photo-item:hover .photo-remove-btn{opacity:1}
.photo-remove-btn:hover{background:#dc2626}
.photo-set-profile{position:absolute;bottom:5px;left:5px;width:24px;height:24px;background:rgba(59,130,246,0.9);color:white;border:none;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;cursor:pointer;opacity:0;transition:opacity 0.2s}
.photo-item:hover .photo-set-profile{opacity:1}
.photo-set-profile:hover{background:#2563eb}
.add-photo-btn{display:flex;align-items:center;justify-content:center;border:2px dashed#cbd5e1;border-radius:8px;height:100px;background:#f8fafc;color:#64748b;cursor:pointer;transition:all 0.2s}
.add-photo-btn:hover{background:#e2e8f0;border-color:#94a3b8;color:#475569}
.add-photo-btn i{font-size:24px;margin-bottom:5px}
/* Jathagam Section */
.jathagam-section{display:flex;align-items:center;justify-content:space-between;background:#f0f9ff;padding:15px;border-radius:8px;border-left:4px solid#3b82f6;margin-top:20px}
.jathagam-info{display:flex;align-items:center;gap:10px}
.jathagam-icon{font-size:20px;color:#3b82f6}
.jathagam-action-btn{background:#3b82f6;color:white;border:none;padding:6px 12px;border-radius:6px;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:5px;transition:background 0.2s}
.jathagam-action-btn:hover{background:#2563eb}
/* Modal */
.modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px}
.modal-content{background:white;border-radius:12px;max-width:90vw;max-height:90vh;overflow:auto;position:relative}
.modal-image{max-width:100%;max-height:70vh;display:block;margin:0 auto}
.modal-actions{position:absolute;top:15px;right:15px;display:flex;gap:10px}
/* Upload Progress */
.upload-progress{display:none;background:#e2e8f0;border-radius:8px;height:6px;margin-top:10px;overflow:hidden}
.upload-progress-bar{height:100%;background:#4f46e5;width:0%;transition:width 0.3s}
.upload-status{font-size:12px;color:#64748b;margin-top:5px}
/* Action Buttons */
.action-buttons{display:flex;gap:10px;justify-content:flex-end;margin-top:20px}
.btn-edit,.btn-save,.btn-cancel{padding:8px 16px;font-size:14px;border-radius:6px;font-weight:600;cursor:pointer;transition:all 0.2s}
.btn-edit{background:#3b82f6;color:white;border:none}
.btn-edit:hover{background:#2563eb}
.btn-save{background:#10b981;color:white;border:none}
.btn-save:hover{background:#059669}
.btn-cancel{background:#64748b;color:white;border:none}
.btn-cancel:hover{background:#475569}
/* Mobile adjustments */
@media(max-width:768px){.info-row{flex-direction:column;gap:5px}.info-label{width:100%}.profile-photo{width:180px;height:230px}.profile-photo-placeholder{width:180px;height:230px}}
    </style>
</head>
<body>
    <?php require "navbar.php"; ?>
    
    <?php if ($data): ?>
    <div class="profile-container">
        <!-- Header -->
        <div class="profile-header">
            <div class="profile-photo-section">
                <div class="profile-photo-wrapper">
                    <?php if ($data['profile_photo']): ?>
                        <img src="<?= BASE_URL.'/'.$data['profile_photo'] ?>" class="profile-photo" id="profilePhotoImg">
                    <?php else: ?>
                        <div class="profile-photo-placeholder">NO PHOTO</div>
                    <?php endif; ?>
                    <div class="photo-actions">
                        <button class="photo-action-btn edit" onclick="document.getElementById('profilePhotoInput').click()" title="Change Profile Photo">
                            <i class="fas fa-camera"></i>
                        </button>
                        <?php if ($data['profile_photo']): ?>
                        <button class="photo-action-btn view" onclick="viewImage('<?= BASE_URL.'/'.$data['profile_photo'] ?>')" title="View Full Size">
                            <i class="fas fa-expand"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="user-info-header">
                    <h1 class="user-name"><?= htmlspecialchars($data['name']) ?></h1>
                    <p class="user-details">
                        <?= htmlspecialchars($data['gender_name']) ?> • 
                        <?= htmlspecialchars($data['community_name']) ?> • 
                        <?= $age ?> Years
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="profile-content">
            <!-- Left Column -->
            <div class="left-column">
                <!-- Basic Information Card -->
                <div class="info-card">
                    <div class="section-title">
                        <span>BASIC INFORMATION</span>
                        <div class="btn-group-sm" id="basicInfoButtons">
                            <button id="editProfile" class="btn-edit">Edit</button>
                            <button id="saveProfile" class="btn-save" style="display:none">Save</button>
                            <button id="cancelEdit" class="btn-cancel" style="display:none">Cancel</button>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="father_name" data-type="text">
                        <div class="info-label">Father Name</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['father_name']) ?>">
                            <?= htmlspecialchars($data['father_name']) ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="mother_name" data-type="text">
                        <div class="info-label">Mother Name</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['mother_name']) ?>">
                            <?= htmlspecialchars($data['mother_name']) ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="dob" data-type="date">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['dob']) ?>">
                            <?= htmlspecialchars($data['dob']) ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="tob" data-type="text">
                        <div class="info-label">Time of Birth</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['tob']) ?>">
                            <?= htmlspecialchars($data['tob']) ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="mobile" data-type="text">
                        <div class="info-label">Mobile</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['mobile']) ?>">
                            <?= htmlspecialchars($data['mobile']) ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="email" data-type="email">
                        <div class="info-label">Email</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['email']) ?>">
                            <?= htmlspecialchars($data['email']) ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="qualification_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_qualification), ENT_QUOTES) ?>'>
                        <div class="info-label">Qualification</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['qualification_id']) ?>">
                            <?= $data['qualification_name'] ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="working_city_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_city), ENT_QUOTES) ?>'>
                        <div class="info-label">Working City</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['working_city_id']) ?>">
                            <?= $data['working_city_name'] ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="marital_status" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_marital), ENT_QUOTES) ?>'>
                        <div class="info-label">Marital Status</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['marital_status']) ?>">
                            <?= htmlspecialchars($opt_marital[$data['marital_status']] ?? ucfirst($data['marital_status'])) ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="monthly_income" data-type="number">
                        <div class="info-label">Monthly Income</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['monthly_income']) ?>">
                            ₹<?= number_format($data['monthly_income']) ?>
                        </div>
                    </div>
                </div>

                <!-- Address Card -->
                <div class="info-card">
                    <div class="section-title">ADDRESS</div>
                    
                    <div class="info-row" data-field="address" data-type="textarea">
                        <div class="info-label">Full Address</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['address']) ?>">
                            <div class="address-box"><?= nl2br(htmlspecialchars($data['address'])) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="city_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_city), ENT_QUOTES) ?>'>
                        <div class="info-label">City</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['city_id']) ?>">
                            <?= htmlspecialchars($data['city_name']) ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="pincode" data-type="text">
                        <div class="info-label">Pincode</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['pincode']) ?>">
                            <?= htmlspecialchars($data['pincode']) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="right-column">
                <!-- Horoscope Card -->
                <div class="info-card">
                    <div class="section-title">HOROSCOPE DETAILS</div>
                    
                    <div class="info-row" data-field="raasi_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_raasi), ENT_QUOTES) ?>'>
                        <div class="info-label">Raasi</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['raasi_id']) ?>">
                            <?= $data['raasi_name'] ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="star_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_star), ENT_QUOTES) ?>'>
                        <div class="info-label">Star</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['star_id']) ?>">
                            <?= $data['star_name'] ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="dhosam_type_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_dhosam_type), ENT_QUOTES) ?>'>
                        <div class="info-label">Dhosam Type</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['dhosam_type_id']) ?>">
                            <?= htmlspecialchars($data['dhosam_type']) ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="dhosam_name_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_dhosam_name), ENT_QUOTES) ?>'>
                        <div class="info-label">Dhosam Name</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['dhosam_name_id']) ?>">
                            <?= htmlspecialchars($data['dhosam_name']) ?>
                        </div>
                    </div>

                    <!-- Jathagam File -->
                    <?php if ($data['jathagam_file']): ?>
                    <div class="jathagam-section">
                        <div class="jathagam-info">
                            <i class="fas fa-file-alt jathagam-icon"></i>
                            <span>Horoscope/Jathagam uploaded</span>
                        </div>
                        <button class="jathagam-action-btn" onclick="viewImage('<?= BASE_URL.'/'.$data['jathagam_file'] ?>')">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Community Card -->
                <div class="info-card">
                    <div class="section-title">COMMUNITY DETAILS</div>
                    
                    <div class="info-row" data-field="community_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_community), ENT_QUOTES) ?>'>
                        <div class="info-label">Community</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['community_id']) ?>">
                            <?= $data['community_name'] ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="sub_community_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_sub_community), ENT_QUOTES) ?>'>
                        <div class="info-label">Sub Community</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['sub_community_id']) ?>">
                            <?= $data['sub_community_name'] ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="kulam_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_kulam), ENT_QUOTES) ?>'>
                        <div class="info-label">Kulam</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['kulam_id']) ?>">
                            <?= $data['kulam_name'] ?>
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="gothiram_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_gothiram), ENT_QUOTES) ?>'>
                        <div class="info-label">Gothiram</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['gothiram_id']) ?>">
                            <?= $data['gothiram_name'] ?>
                        </div>
                    </div>
                </div>

                <!-- Settings Card -->
                <div class="info-card">
                    <div class="section-title">ACCOUNT SETTINGS</div>
                    
                    <div class="info-row">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?= htmlspecialchars($data['username']) ?></div>
                    </div>
                    
                    <div class="info-row" data-field="password" data-type="text">
                        <div class="info-label">Password</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['password_hash']) ?>">
                            ••••••••
                        </div>
                    </div>
                    
                    <div class="info-row" data-field="status" data-type="select" data-options='<?= htmlspecialchars(json_encode(['married'=>'Married']), ENT_QUOTES) ?>'>
                        <div class="info-label">Profile Status</div>
                        <div class="info-value" data-value="<?= htmlspecialchars($data['status']) ?>">
                            <?= $data['status'] === 'married' ? 'Married (Hidden)' : 'Active' ?>
                        </div>
                    </div>
                </div>

                <!-- Other Photos Card -->
                <div class="info-card">
                    <div class="section-title">
                        <span>OTHER PHOTOS</span>
                        <input type="file" id="otherPhotosInput" name="photos[]" multiple accept="image/*" style="display:none">
                    </div>
                    
                    <div class="photos-grid" id="otherPhotosGrid">
                        <!-- Add Photo Button -->
                        <div class="add-photo-btn" onclick="document.getElementById('otherPhotosInput').click()">
                            <div style="text-align:center">
                                <i class="fas fa-plus"></i>
                                <div style="font-size:11px;margin-top:5px">Add Photos</div>
                            </div>
                        </div>
                        
                        <!-- Existing Photos -->
                        <?php foreach ($other_photos as $p): ?>
                        <div class="photo-item" data-photo-id="<?= $p['id'] ?>">
                            <img src="<?= BASE_URL.'/'.$p['photo_path'] ?>" 
                                 onclick="viewImageWithId('<?= BASE_URL.'/'.$p['photo_path'] ?>', <?= $p['id'] ?>)">
                            <button class="photo-remove-btn" onclick="deletePhoto(<?= $p['id'] ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                            <button class="photo-set-profile" onclick="setProfileFromOther(<?= $p['id'] ?>)" title="Set as Profile">
                                <i class="fas fa-user-circle"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Upload Progress -->
                    <div class="upload-progress" id="uploadProgress">
                        <div class="upload-progress-bar" id="uploadProgressBar"></div>
                    </div>
                    <div class="upload-status" id="uploadStatus"></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hidden File Inputs -->
    <input type="file" id="profilePhotoInput" name="profile_photo" accept="image/*" style="display:none">
    <input type="file" id="jathagamFileInput" name="jathagam_file" accept="image/*,.pdf,.doc,.docx" style="display:none">

    <!-- Image Modal -->
    <div class="modal-overlay" id="imageModal">
        <div class="modal-content">
            <div class="modal-actions">
                <button class="btn btn-sm btn-primary" id="modalSetProfileBtn" style="display:none">
                    <i class="fas fa-user-circle"></i> Set Profile
                </button>
                <button class="btn btn-sm btn-danger" id="modalDeleteBtn" style="display:none">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <button class="btn btn-sm btn-secondary" id="modalCloseBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <img class="modal-image" id="modalImage">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentModalPhotoId = null;
    let editing = false;
    let originalValues = {};

    // View Image Functions
    function viewImage(src) {
        const modal = document.getElementById('imageModal');
        const img = document.getElementById('modalImage');
        img.src = src;
        modal.style.display = 'flex';
        currentModalPhotoId = null;
        document.getElementById('modalSetProfileBtn').style.display = 'none';
        document.getElementById('modalDeleteBtn').style.display = 'none';
    }

    function viewImageWithId(src, photoId) {
        const modal = document.getElementById('imageModal');
        const img = document.getElementById('modalImage');
        img.src = src;
        modal.style.display = 'flex';
        currentModalPhotoId = photoId;
        document.getElementById('modalSetProfileBtn').style.display = 'inline-block';
        document.getElementById('modalDeleteBtn').style.display = 'inline-block';
    }

    function closeModal() {
        document.getElementById('imageModal').style.display = 'none';
        currentModalPhotoId = null;
    }

    // Photo Management Functions
    function deletePhoto(photoId) {
        if (!confirm('Are you sure you want to delete this photo?')) return;
        
        const form = new FormData();
        form.append('action', 'delete_photo');
        form.append('photo_id', photoId);
        
        fetch('profile.php', { method: 'POST', body: form })
            .then(async r => {
                const text = await r.text();
                try {
                    const json = JSON.parse(text);
                    if (json.status === 'success') {
                        const photoItem = document.querySelector(`.photo-item[data-photo-id="${photoId}"]`);
                        if (photoItem) {
                            photoItem.remove();
                        }
                        closeModal();
                    } else {
                        alert(json.message || 'Delete failed');
                    }
                } catch (e) {
                    console.error('Error:', text, e);
                    alert('Server error');
                }
            })
            .catch(err => {
                console.error('Fetch error', err);
                alert('Network error');
            });
    }

    function setProfileFromOther(photoId) {
        if (!confirm('Set this photo as your profile photo?')) return;
        
        const form = new FormData();
        form.append('action', 'set_profile_photo');
        form.append('photo_id', photoId);
        
        fetch('profile.php', { method: 'POST', body: form })
            .then(async r => {
                const text = await r.text();
                try {
                    const json = JSON.parse(text);
                    if (json.status === 'success') {
                        location.reload();
                    } else {
                        alert(json.message || 'Failed to set profile');
                    }
                } catch (e) {
                    console.error('Error:', text, e);
                    alert('Server error');
                }
            })
            .catch(err => {
                console.error('Fetch error', err);
                alert('Network error');
            });
    }

    // Upload Other Photos
    document.getElementById('otherPhotosInput').addEventListener('change', function(e) {
        if (!this.files.length) return;
        
        const form = new FormData();
        form.append('action', 'upload_other_photos');
        
        Array.from(this.files).forEach(file => {
            form.append('photos[]', file);
        });
        
        // Show progress
        const progress = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('uploadProgressBar');
        const status = document.getElementById('uploadStatus');
        
        progress.style.display = 'block';
        progressBar.style.width = '0%';
        status.textContent = 'Uploading...';
        
        fetch('profile.php', {
            method: 'POST',
            body: form
        })
        .then(async r => {
            const text = await r.text();
            try {
                const json = JSON.parse(text);
                progressBar.style.width = '100%';
                
                if (json.status === 'success') {
                    status.textContent = 'Upload successful!';
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    status.textContent = 'Upload failed: ' + (json.message || 'Unknown error');
                    progressBar.style.background = '#ef4444';
                }
            } catch (e) {
                console.error('Error:', text, e);
                status.textContent = 'Upload failed';
                progressBar.style.background = '#ef4444';
            }
        })
        .catch(err => {
            console.error('Fetch error', err);
            status.textContent = 'Network error';
            progressBar.style.background = '#ef4444';
        });
    });

    // Profile Photo Upload
    document.getElementById('profilePhotoInput').addEventListener('change', function() {
        if (!this.files.length) return;
        
        const form = new FormData();
        form.append('action', 'update_profile');
        form.append('profile_photo', this.files[0]);
        
        fetch('profile.php', {
            method: 'POST',
            body: form
        })
        .then(async r => {
            const text = await r.text();
            try {
                const json = JSON.parse(text);
                if (json.status === 'success') {
                    location.reload();
                } else {
                    alert('Failed to upload profile photo');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Server error');
            }
        })
        .catch(err => {
            console.error('Fetch error', err);
            alert('Network error');
        });
    });

    // Jathagam File Upload
    document.getElementById('jathagamFileInput').addEventListener('change', function() {
        if (!this.files.length) return;
        
        const form = new FormData();
        form.append('action', 'update_profile');
        form.append('jathagam_file', this.files[0]);
        
        fetch('profile.php', {
            method: 'POST',
            body: form
        })
        .then(async r => {
            const text = await r.text();
            try {
                const json = JSON.parse(text);
                if (json.status === 'success') {
                    location.reload();
                } else {
                    alert('Failed to upload horoscope');
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Server error');
            }
        })
        .catch(err => {
            console.error('Fetch error', err);
            alert('Network error');
        });
    });

    // Profile Edit Functions
    function enterEditMode() {
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
                input.value = value;
            } else if (type === 'select') {
                input = document.createElement('select');
                input.className = 'form-select form-select-sm';
                try {
                    const opts = JSON.parse(row.dataset.options || '{}');
                    Object.keys(opts).forEach(k => {
                        const o = document.createElement('option');
                        o.value = k;
                        o.text = opts[k];
                        if (k == value) o.selected = true;
                        input.appendChild(o);
                    });
                } catch(e) {
                    console.error('Error parsing options:', e);
                }
            } else if (type === 'number') {
                input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm';
                input.value = value ? parseFloat(value) : '';
            } else if (type === 'date') {
                input = document.createElement('input');
                input.type = 'date';
                input.className = 'form-control form-control-sm';
                input.value = value;
            } else {
                input = document.createElement('input');
                input.type = type === 'email' ? 'email' : 'text';
                input.className = 'form-control form-control-sm';
                input.value = value;
            }
            
            inputWrap.appendChild(input);
        });
        
        // Show jathagam upload option
        const jathagamSection = document.querySelector('.jathagam-section');
        if (jathagamSection) {
            jathagamSection.innerHTML = `
                <div class="jathagam-info">
                    <i class="fas fa-file-alt jathagam-icon"></i>
                    <span>Upload Horoscope/Jathagam</span>
                </div>
                <button class="jathagam-action-btn" onclick="document.getElementById('jathagamFileInput').click()">
                    <i class="fas fa-upload"></i> Upload
                </button>
            `;
        }
        
        editing = true;
        document.getElementById('editProfile').style.display = 'none';
        document.getElementById('saveProfile').style.display = 'inline-block';
        document.getElementById('cancelEdit').style.display = 'inline-block';
    }

    function exitEditMode(revert = false) {
        document.querySelectorAll('.info-row[data-field]').forEach(row => {
            const field = row.dataset.field;
            const type = row.dataset.type || 'text';
            const display = row.querySelector('.info-value');
            const inputEl = display.querySelector('input,textarea,select');
            
            const val = revert ? (originalValues[field] ?? '') : (inputEl ? inputEl.value : (display.dataset.value ?? display.textContent.trim()));
            display.dataset.value = val;
            
            if (field === 'monthly_income') {
                display.innerHTML = val ? '₹' + Number(val).toLocaleString() : '';
            } else if (field === 'address') {
                display.innerHTML = '<div class="address-box">' + (val ? val.replace(/\n/g,'<br>') : '') + '</div>';
            } else if (type === 'select') {
                let label = val;
                try {
                    const opts = JSON.parse(row.dataset.options || '{}');
                    if (opts && typeof opts === 'object') label = opts[val] ?? val;
                } catch(e) {}
                display.textContent = label;
            } else if (field === 'password') {
                display.textContent = '••••••••';
            } else if (field === 'status') {
                display.textContent = val === 'married' ? 'Married (Hidden)' : 'Active';
            } else {
                display.textContent = val;
            }
        });
        
        editing = false;
        document.getElementById('editProfile').style.display = 'inline-block';
        document.getElementById('saveProfile').style.display = 'none';
        document.getElementById('cancelEdit').style.display = 'none';
    }

    function saveProfile() {
        const form = new FormData();
        form.append('action', 'update_profile');
        let statusValue = null;
        
        document.querySelectorAll('.info-row[data-field]').forEach(row => {
            const field = row.dataset.field;
            const input = row.querySelector('input,textarea,select');
            if (!input) return;
            
            if (field === 'password' && input.value.trim() === '') return;
            if (field === 'status') statusValue = input.value;
            
            form.append(field, input.value);
        });
        
        fetch('profile.php', {
            method: 'POST',
            body: form
        })
        .then(async r => {
            const text = await r.text();
            try {
                const json = JSON.parse(text);
                if (json.status === 'success') {
                    exitEditMode(false);
                    if (statusValue === 'married') {
                        alert('Status changed to Married. Your profile will not be visible to others.');
                    } else {
                        alert('Profile updated successfully!');
                    }
                    location.reload();
                } else {
                    alert(json.message || 'Update failed');
                }
            } catch (e) {
                console.error('Error:', text, e);
                alert('Server error — check console');
            }
        })
        .catch(err => {
            console.error('Fetch error', err);
            alert('Network error');
        });
    }

    // Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Edit Profile
        document.getElementById('editProfile').addEventListener('click', enterEditMode);
        document.getElementById('cancelEdit').addEventListener('click', () => exitEditMode(true));
        document.getElementById('saveProfile').addEventListener('click', saveProfile);
        
        // Modal
        document.getElementById('modalCloseBtn').addEventListener('click', closeModal);
        document.getElementById('modalSetProfileBtn').addEventListener('click', () => {
            if (currentModalPhotoId) setProfileFromOther(currentModalPhotoId);
        });
        document.getElementById('modalDeleteBtn').addEventListener('click', () => {
            if (currentModalPhotoId) deletePhoto(currentModalPhotoId);
        });
        
        // Close modal on overlay click
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        // Escape key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    });
    </script>
</body>
</html>