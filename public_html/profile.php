<?php
require "config/init.php";
require "navbar.php"; 
// User profile page - for logged-in users to view/edit their own profile
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

/* ================ DELETE OTHER PHOTO (AJAX) ================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim($_POST['action'] ?? '') === 'delete_photo') {
    ob_start();
    header('Content-Type: application/json; charset=utf-8');
    
    if (!isset($_SESSION['user_id'])) { 
        ob_clean(); 
        echo json_encode(['status'=>'error','message'=>'unauthorized']); 
        exit; 
    }
    
    $user_id = (int)$_SESSION['user_id'];
    $photo_path = trim($_POST['photo_path'] ?? '');
    
    if (empty($photo_path)) {
        ob_clean(); 
        echo json_encode(['status'=>'error','message'=>'photo_path_required']); 
        exit;
    }
    
    // Verify the photo belongs to the current user
    $check_sql = "SELECT id FROM user_other_photos WHERE user_id = $user_id AND photo_path = '" . mysqli_real_escape_string($conn, $photo_path) . "'";
    $check_res = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_res) === 0) {
        ob_clean(); 
        echo json_encode(['status'=>'error','message'=>'photo_not_found']); 
        exit;
    }
    
    // Delete from database
    $delete_sql = "DELETE FROM user_other_photos WHERE user_id = $user_id AND photo_path = '" . mysqli_real_escape_string($conn, $photo_path) . "'";
    $delete_res = mysqli_query($conn, $delete_sql);
    
    if ($delete_res) {
        // Delete the actual file
        $full_path = dirname(__FILE__) . '/' . $photo_path;
        if (file_exists($full_path) && is_writable($full_path)) {
            @unlink($full_path);
        }
        
        ob_clean(); 
        echo json_encode(['status'=>'success','message'=>'photo_deleted']); 
        exit;
    } else {
        ob_clean(); 
        echo json_encode(['status'=>'error','message'=>mysqli_error($conn)]); 
        exit;
    }
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

    // fetch current DB status
    $q = mysqli_query($conn, "SELECT status FROM user_registration_status WHERE user_id=$user_id LIMIT 1");
    $row = mysqli_fetch_assoc($q);
    $current_status = $row['status'] ?? '';

    // allow same status OR married only
    if ($status_to_update !== $current_status && $status_to_update !== 'married') {
        ob_clean();
        echo json_encode(['status'=>'error','message'=>'invalid_status']);
        exit;
    }

    $sql2 = "UPDATE user_registration_status 
             SET status='".mysqli_real_escape_string($conn,$status_to_update)."',
                 updated_at=NOW()
             WHERE user_id=$user_id";

    $res2 = mysqli_query($conn, $sql2);
    if (!$res2) {
        ob_clean();
        echo json_encode(['status'=>'error','message'=>mysqli_error($conn)]);
        exit;
    }
}

    // Handle file uploads
    if ($res) {
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
        
        // Other photos
        if (!empty($_FILES['other_photos']['name'][0])) {
            foreach ($_FILES['other_photos']['tmp_name'] as $i => $tmp) {
                if (!$tmp) continue;
                $oname = $_FILES['other_photos']['name'][$i];
                $ext = strtolower(pathinfo($oname, PATHINFO_EXTENSION));
                $dest_rel = 'uploads/other/'.time().'_'.rand(1000,9999).'.'.$ext;
                $dest_abs = $project_root . '/' . $dest_rel;
                @mkdir(dirname($dest_abs), 0755, true);
                if (move_uploaded_file($tmp, $dest_abs)) {
                    mysqli_query($conn, "INSERT INTO user_other_photos (user_id,photo_path) VALUES ($user_id,'".mysqli_real_escape_string($conn,$dest_rel)."')");
                }
            }
        }
    }
    
    ob_clean();
    if ($res) {
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
        /* Base styles */
        .profile-container {
            max-width: 1400px;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 4px 25px rgba(0,0,0,.08);
            display: flex;
            flex-direction: column;
        }
        
        .profile-sidebar {
            width: 100%;
            background: linear-gradient(180deg, #4f46e5, #3730a3);
            color: #fff;
            padding: 20px;
            text-align: center;
            order: 1;
        }
        
        .profile-content {
            flex: 1;
            padding: 20px;
            order: 2;
        }
        
        .profile-photo {
            width: 150px;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
            border: 4px solid rgba(255,255,255,.2);
            margin: 0 auto;
        }
        
        .photo-placeholder {
            width: 150px;
            height: 180px;
            background: #6366f1;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 auto;
        }
        
        .user-name {
            font-size: 20px;
            font-weight: 700;
            margin-top: 15px;
            word-break: break-word;
        }
        
        .user-subtitle {
            font-size: 13px;
            opacity: .9;
            margin-top: 5px;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .profile-sidebar .section-heading {
            margin-top: 15px;
            font-size: 14px;
        }
        
        .profile-sidebar .thumb-grid {
            margin-top: 8px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .info-group {
            background: #f9fafb;
            border-radius: 8px;
            padding: 15px;
            border-left: 3px solid #4f46e5;
        }
        
        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
        }
        
        .info-row {
            display: flex;
            flex-direction: column;
            margin-bottom: 12px;
        }
        
        .info-label {
            width: 100%;
            font-size: 11px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        
        .info-value {
            width: 100%;
            font-size: 13px;
            font-weight: 500;
            word-break: break-word;
        }
        
        .address-box {
            background: #eef2ff;
            padding: 8px;
            border-radius: 6px;
            font-size: 12px;
        }
        
        .view-btn {
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 700;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .modal-image {
            max-width: 95%;
            max-height: 95%;
            background: #fff;
            padding: 10px;
            border-radius: 10px;
        }
        
        .thumb-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, 60px);
            gap: 8px;
            justify-content: center;
        }
        
        .thumb-item {
            position: relative;
            width: 60px;
            height: 60px;
        }
        
        .thumb-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            border: 2px solid #e5e7eb;
        }
        
        .thumb-img:hover {
            border-color: #4f46e5;
        }
        
        .delete-photo-btn {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            border: none;
            font-size: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            z-index: 10;
            transition: background 0.2s;
        }
        
        .delete-photo-btn:hover {
            background: #b91c1c;
        }
        
        /* Action buttons container */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-bottom: 12px;
            gap: 8px;
        }
        
        /* File inputs container */
        #fileInputs {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 10px;
            width: 100%;
        }
        
        #fileInputs label {
            font-size: 11px;
            margin-bottom: 0;
        }
        
        #fileInputs input {
            font-size: 11px;
        }
        
        /* Tablet and larger screens */
        @media (min-width: 768px) {
            .profile-container {
                flex-direction: row;
                margin-top: 30px;
            }
            
            .profile-sidebar {
                width: 300px;
                order: 0;
            }
            
            .profile-content {
                order: 0;
            }
            
            .profile-photo {
                width: 180px;
                height: 220px;
            }
            
            .photo-placeholder {
                width: 180px;
                height: 220px;
            }
            
            .user-name {
                font-size: 22px;
                margin-top: 20px;
            }
            
            .user-subtitle {
                font-size: 14px;
                margin-top: 6px;
                margin-bottom: 14px;
            }
            
            .info-grid {
                grid-template-columns: 1fr 1fr;
                gap: 25px;
            }
            
            .info-group {
                padding: 20px;
            }
            
            .section-heading {
                font-size: 13px;
                margin-bottom: 12px;
            }
            
            .info-row {
                flex-direction: row;
                margin-bottom: 14px;
            }
            
            .info-label {
                width: 140px;
                font-size: 12px;
                margin-bottom: 0;
            }
            
            .info-value {
                font-size: 14px;
            }
            
            .address-box {
                font-size: 13px;
            }
            
            .view-btn {
                font-size: 11px;
            }
            
            .thumb-grid {
                grid-template-columns: repeat(auto-fill, 70px);
            }
            
            .thumb-item {
                width: 70px;
                height: 70px;
            }
            
            .delete-photo-btn {
                top: -8px;
                right: -8px;
                width: 22px;
                height: 22px;
                font-size: 12px;
            }
            
            .action-buttons {
                flex-wrap: nowrap;
            }
            
            #fileInputs {
                flex-direction: row;
                gap: 12px;
                margin-top: 0;
                margin-left: 12px;
                width: auto;
            }
        }
        
        /* Desktop screens */
        @media (min-width: 992px) {
            .profile-sidebar {
                padding: 25px;
            }
            
            .profile-content {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    
    <?php if ($data): ?>
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
            <div class="thumb-item">
                <img src="<?= BASE_URL.'/'.$p ?>" class="thumb-img" onclick="viewImage('<?= BASE_URL.'/'.$p ?>')">
                <button class="delete-photo-btn" onclick="deletePhoto('<?= BASE_URL.'/'.$p ?>', this)" title="Delete photo">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="profile-content">
            <div class="action-buttons">
                <button id="editProfile" class="btn btn-primary btn-sm">Edit</button>
                <button id="saveProfile" class="btn btn-success btn-sm d-none">Save</button>
                <button id="cancelEdit" class="btn btn-secondary btn-sm d-none">Cancel</button>
                
                <?php if ($data['jathagam_file']): ?>
                <button class="view-btn" onclick="viewImage('<?= BASE_URL.'/'.$data['jathagam_file'] ?>')">VIEW HOROSCOPE</button>
                <?php endif; ?>
            </div>
            
            <div id="fileInputs" class="d-none">
                <label>Profile Photo
                    <input type="file" id="profilePhotoInput" name="profile_photo" accept="image/*" class="form-control form-control-sm">
                </label>
                <label>Jathagam
                    <input type="file" id="jathagamFileInput" name="jathagam_file" accept="image/*" class="form-control form-control-sm">
                </label>
                <label>Other Photos
                    <input type="file" id="otherPhotosInput" name="other_photos[]" multiple accept="image/*" class="form-control form-control-sm">
                </label>
            </div>

            <div class="info-grid">
                <div class="info-group">
                    <div class="section-heading">BASIC INFORMATION</div>
                    <div class="info-row" data-field="father_name" data-type="text"><div class="info-label">Father Name</div><div class="info-value" data-value="<?= htmlspecialchars($data['father_name']) ?>"><?= htmlspecialchars($data['father_name']) ?></div></div>
                    <div class="info-row" data-field="mother_name" data-type="text"><div class="info-label">Mother Name</div><div class="info-value" data-value="<?= htmlspecialchars($data['mother_name']) ?>"><?= htmlspecialchars($data['mother_name']) ?></div></div>
                    <div class="info-row" data-field="dob" data-type="date"><div class="info-label">DOB</div><div class="info-value" data-value="<?= htmlspecialchars($data['dob']) ?>"><?= htmlspecialchars($data['dob']) ?></div></div>
                    <div class="info-row" data-field="tob" data-type="text"><div class="info-label">TOB</div><div class="info-value" data-value="<?= htmlspecialchars($data['tob']) ?>"><?= htmlspecialchars($data['tob']) ?></div></div>
                    <div class="info-row" data-field="mobile" data-type="text"><div class="info-label">Mobile</div><div class="info-value" data-value="<?= htmlspecialchars($data['mobile']) ?>"><?= htmlspecialchars($data['mobile']) ?></div></div>
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
                    <div class="section-heading">HOROSCOPE</div>
                    <div class="info-row" data-field="raasi_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_raasi), ENT_QUOTES) ?>'><div class="info-label">Raasi</div><div class="info-value" data-value="<?= htmlspecialchars($data['raasi_id']) ?>"><?= $data['raasi_name'] ?></div></div>
                    <div class="info-row" data-field="star_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_star), ENT_QUOTES) ?>'><div class="info-label">Star</div><div class="info-value" data-value="<?= htmlspecialchars($data['star_id']) ?>"><?= $data['star_name'] ?></div></div>
                    <div class="info-row" data-field="dhosam_type_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_dhosam_type), ENT_QUOTES) ?>'><div class="info-label">Dhosam Type</div><div class="info-value" data-value="<?= htmlspecialchars($data['dhosam_type_id']) ?>"><?= htmlspecialchars($data['dhosam_type']) ?></div></div>
                    <div class="info-row" data-field="dhosam_name_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_dhosam_name), ENT_QUOTES) ?>'><div class="info-label">Dhosam Name</div><div class="info-value" data-value="<?= htmlspecialchars($data['dhosam_name_id']) ?>"><?= htmlspecialchars($data['dhosam_name']) ?></div></div>

                    <div class="section-heading">COMMUNITY</div>
                    <div class="info-row" data-field="community_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_community), ENT_QUOTES) ?>'><div class="info-label">Community</div><div class="info-value" data-value="<?= htmlspecialchars($data['community_id']) ?>"><?= $data['community_name'] ?></div></div>
                    <div class="info-row" data-field="sub_community_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_sub_community), ENT_QUOTES) ?>'><div class="info-label">Sub Community</div><div class="info-value" data-value="<?= htmlspecialchars($data['sub_community_id']) ?>"><?= $data['sub_community_name'] ?></div></div>
                    <div class="info-row" data-field="kulam_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_kulam), ENT_QUOTES) ?>'><div class="info-label">Kulam</div><div class="info-value" data-value="<?= htmlspecialchars($data['kulam_id']) ?>"><?= $data['kulam_name'] ?></div></div>
                    <div class="info-row" data-field="gothiram_id" data-type="select" data-options='<?= htmlspecialchars(json_encode($opt_gothiram), ENT_QUOTES) ?>'><div class="info-label">Gothiram</div><div class="info-value" data-value="<?= htmlspecialchars($data['gothiram_id']) ?>"><?= $data['gothiram_name'] ?></div></div>

                    <div class="section-heading"><span>SETTINGS</span></div>
                    <div class="info-row"><div class="info-label">User Name</div><div class="info-value"><?= htmlspecialchars($data['username']) ?></div></div>
                    <div class="info-row" data-field="password" data-type="text"><div class="info-label">Password</div><div class="info-value" data-value="<?= htmlspecialchars($data['password_hash']) ?>"><?= htmlspecialchars($data['password_hash']) ?></div></div>
<div class="info-row" data-field="status" data-type="select" data-options='<?= htmlspecialchars(json_encode(array_unique([$data["status"]=>ucfirst($data["status"]),"married"=>"Married"])),ENT_QUOTES) ?>'><div class="info-label">Status</div><div class="info-value" data-value="<?= htmlspecialchars($data["status"]) ?>"><?= htmlspecialchars(ucfirst($data["status"])) ?></div></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="modal-overlay" id="imageModal" onclick="this.style.display='none'">
    <img class="modal-image">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function viewImage(src){
        const m=document.getElementById('imageModal');
        m.style.display='flex';
        m.querySelector('img').src=src;
    }

    // Delete photo function
    function deletePhoto(fullPath, btnElement) {
        if (!confirm('Are you sure you want to delete this photo?')) {
            return;
        }
        
        // Extract the relative path (remove BASE_URL part)
        const baseUrl = '<?= BASE_URL ?>';
        let photoPath = fullPath;
        if (fullPath.startsWith(baseUrl + '/')) {
            photoPath = fullPath.substring(baseUrl.length + 1);
        }
        
        // Show loading state
        const originalHTML = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btnElement.disabled = true;
        
        // Send AJAX request
        const formData = new FormData();
        formData.append('action', 'delete_photo');
        formData.append('photo_path', photoPath);
        
        fetch('profile.php', {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const text = await response.text();
            try {
                const result = JSON.parse(text);
                if (result.status === 'success') {
                    // Remove the photo element from DOM
                    const thumbItem = btnElement.closest('.thumb-item');
                    if (thumbItem) {
                        thumbItem.remove();
                        
                        // If no photos left, hide the "OTHER PHOTOS" section
                        const thumbGrid = document.querySelector('.thumb-grid');
                        if (thumbGrid && thumbGrid.children.length === 0) {
                            const sectionHeading = document.querySelector('.profile-sidebar .section-heading');
                            if (sectionHeading && sectionHeading.textContent.includes('OTHER PHOTOS')) {
                                sectionHeading.remove();
                            }
                        }
                        
                        alert('Photo deleted successfully!');
                    }
                } else {
                    alert('Error: ' + (result.message || 'Failed to delete photo'));
                    btnElement.innerHTML = originalHTML;
                    btnElement.disabled = false;
                }
            } catch (e) {
                console.error('Error parsing response:', text, e);
                alert('Server error occurred');
                btnElement.innerHTML = originalHTML;
                btnElement.disabled = false;
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            alert('Network error occurred');
            btnElement.innerHTML = originalHTML;
            btnElement.disabled = false;
        });
    }

    (() => {
        const userId = <?= (int)$user_id ?>;
        const editBtn = document.getElementById('editProfile');
        const saveBtn = document.getElementById('saveProfile');
        const cancelBtn = document.getElementById('cancelEdit');
        let editing = false;
        let originalValues = {};

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
                } else if (type === 'select') {
                    input = document.createElement('select');
                    input.className = 'form-select form-select-sm';
                    try {
                        const opts = JSON.parse(row.dataset.options || '{}');
                        Object.keys(opts).forEach(k => {
                            const o = document.createElement('option'); o.value = k; o.text = opts[k];
                            input.appendChild(o);
                        });
                    } catch(e) {}
                } else {
                    input = document.createElement('input');
                    input.type = type === 'number' ? 'number' : (type === 'date' ? 'date' : (type === 'email' ? 'email' : 'text'));
                    input.className = 'form-control form-control-sm';
                }
                input.value = value;
                inputWrap.appendChild(input);
            });
            const fileBox = document.getElementById('fileInputs'); if (fileBox) fileBox.classList.remove('d-none');
            editing = true;
            editBtn.classList.add('d-none');
            saveBtn.classList.remove('d-none');
            cancelBtn.classList.remove('d-none');
        }

        function exitEdit(revert=false) {
            document.querySelectorAll('.info-row[data-field]').forEach(row => {
                const field = row.dataset.field;
                const type = row.dataset.type || 'text';
                const display = row.querySelector('.info-value');
                const inputEl = display.querySelector('input,textarea,select');
                const val = revert ? (originalValues[field] ?? '') : (inputEl ? inputEl.value : (display.dataset.value ?? display.textContent.trim()));
                display.dataset.value = val;
                if (field === 'monthly_income') {
                    display.innerHTML = val ? '₹' + Number(val).toLocaleString() : '';
                } else if (display.querySelector('.address-box')) {
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
            const fileBox2 = document.getElementById('fileInputs'); if (fileBox2) fileBox2.classList.add('d-none');
            editing = false;
            editBtn.classList.remove('d-none');
            saveBtn.classList.add('d-none');
            cancelBtn.classList.add('d-none');
        }

        function saveProfile() {
            const form = new FormData();
            form.append('action','update_profile');
            document.querySelectorAll('.info-row[data-field]').forEach(row => {
                const field = row.dataset.field;
                const input = row.querySelector('input,textarea,select');
                if (!input) return;
                if (field === 'password' && input.value.trim() === '') return;
                form.append(field, input.value);
            });

            const pf = document.getElementById('profilePhotoInput'); if (pf && pf.files && pf.files[0]) form.append('profile_photo', pf.files[0]);
            const jf = document.getElementById('jathagamFileInput'); if (jf && jf.files && jf.files[0]) form.append('jathagam_file', jf.files[0]);
            const of = document.getElementById('otherPhotosInput'); if (of && of.files && of.files.length) {
                Array.from(of.files).forEach((f,i)=> form.append('other_photos[]', f));
            }

            fetch('profile.php', {
                method: 'POST',
                body: form
            }).then(async r => {
                const text = await r.text();
                try {
                    const json = JSON.parse(text);
                    if (json.status === 'success') {
                        exitEdit(false);
                        alert('Profile updated successfully!');
                        location.reload();
                    } else {
                        alert(json.message || 'Update failed');
                    }
                } catch (e) {
                    console.error('Error:', text, e);
                    alert('Server error — check console');
                }
            }).catch(err => {
                console.error('Fetch error', err);
                alert('Network error');
            });
        }

        if (editBtn) editBtn.addEventListener('click', enterEdit);
        if (cancelBtn) cancelBtn.addEventListener('click', () => exitEdit(true));
        if (saveBtn) saveBtn.addEventListener('click', saveProfile);
    })();
    </script>
</body>
</html>