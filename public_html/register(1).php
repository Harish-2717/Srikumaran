<?php
require "config/init.php";

function esc($v){
    global $conn;
    return mysqli_real_escape_string($conn, trim($v));
}

$error = "";

/* ================= MASTER DATA ================= */
$communities    = mysqli_query($conn,"SELECT id, community_name FROM master_community WHERE status=1");
$genders        = mysqli_query($conn,"SELECT id, gender_name FROM master_gender WHERE status=1");
$cities         = mysqli_query($conn,"SELECT id, city_name FROM master_city WHERE status=1");
$qualifications = mysqli_query($conn,"SELECT id, qualification_name FROM master_qualification WHERE status=1");
$kulams         = mysqli_query($conn,"SELECT id, kulam_name FROM master_kulam WHERE status=1");
$gothirams      = mysqli_query($conn,"SELECT id, gothiram_name FROM master_gothiram WHERE status=1");
$dhosam_types   = mysqli_query($conn,"SELECT id, dhosam_type FROM master_dhosam_type WHERE status=1");
$dhosam_names   = mysqli_query($conn,"SELECT id, dhosam_name FROM master_dhosam_name WHERE status=1");
$raasis         = mysqli_query($conn,"SELECT id, raasi_name FROM master_raasi WHERE status=1");
$stars          = mysqli_query($conn,"SELECT id, star_name FROM master_star WHERE status=1");

/* ================= SUBMIT ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        mysqli_begin_transaction($conn);

        $mobile = esc($_POST['mobile']);
        $email  = esc($_POST['email']);

        if(mysqli_num_rows(mysqli_query($conn,"SELECT id FROM users WHERE mobile='$mobile' LIMIT 1")))
            throw new Exception("Mobile already registered");

        if($email && mysqli_num_rows(mysqli_query($conn,"SELECT id FROM users WHERE email='$email' LIMIT 1")))
            throw new Exception("Email already registered");

        /* ===== TIME OF BIRTH ===== */
        $tob = '';
        if(!empty($_POST['tob_hours']) && !empty($_POST['tob_minutes'])) {
            $hours   = (int)$_POST['tob_hours'];
            $minutes = (int)$_POST['tob_minutes'];
            $ampm    = $_POST['tob_ampm'];

            if($ampm === 'PM' && $hours < 12) $hours += 12;
            if($ampm === 'AM' && $hours == 12) $hours = 0;

            $tob = sprintf("%02d:%02d", $hours, $minutes);
        }

        /* ===== VALIDATE MASTER IDS ===== */
        $field_mappings = [
            'qualification_id' => 'master_qualification',
            'kulam_id'         => 'master_kulam',
            'gothiram_id'      => 'master_gothiram',
            'dhosam_type_id'   => 'master_dhosam_type',
            'dhosam_name_id'   => 'master_dhosam_name',
            'raasi_id'         => 'master_raasi',
            'star_id'          => 'master_star'
        ];

        $processed_values = [];

        foreach($field_mappings as $field => $table){
            $val = $_POST[$field] ?? '';
            if($val && is_numeric($val)){
                $id = (int)$val;
                $chk = mysqli_query($conn,"SELECT id FROM $table WHERE id=$id LIMIT 1");
                $processed_values[$field] = mysqli_num_rows($chk) ? $id : "NULL";
            } else {
                $processed_values[$field] = "NULL";
            }
        }

        $working_city_id = $_POST['working_city_id'] ? (int)$_POST['working_city_id'] : "NULL";
        $monthly_income  = $_POST['monthly_income'] ? "'".esc($_POST['monthly_income'])."'" : "NULL";
        $marital_status  = $_POST['marital_status'] ? "'".esc($_POST['marital_status'])."'" : "'unmarried'";

        /* ===== FILE UPLOADS ===== */
        $profile_photo = "NULL";
        if(!empty($_FILES['profile_photo']['name'])){
            $ext  = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $path = "uploads/profile/".time()."_".rand(1000,9999).".".$ext;
            if(move_uploaded_file($_FILES['profile_photo']['tmp_name'],$path)){
                $profile_photo = "'$path'";
            }
        }

        $jathagam_file = "NULL";
        if(!empty($_FILES['jathagam_file']['name'])){
            $ext  = strtolower(pathinfo($_FILES['jathagam_file']['name'], PATHINFO_EXTENSION));
            $path = "uploads/jathagam/".time()."_".rand(1000,9999).".".$ext;
            if(move_uploaded_file($_FILES['jathagam_file']['tmp_name'],$path)){
                $jathagam_file = "'$path'";
            }
        }

        /* ===== INSERT USER (TEMP PASSWORD) ===== */
        $sql = "
        INSERT INTO users (
            name, father_name, mother_name,
            community_id, sub_community_id, gender_id, dob, tob,
            email, mobile, address, city_id, pincode,
            qualification_id, working_city_id, monthly_income, marital_status,
            kulam_id, gothiram_id, dhosam_type_id, dhosam_name_id,
            raasi_id, star_id,
            profile_photo, jathagam_file,
            password_hash, created_at
        ) VALUES (
            '".esc($_POST['name'])."',
            '".esc($_POST['father_name'])."',
            '".esc($_POST['mother_name'])."',
            ".(int)$_POST['community_id'].",
            ".($_POST['sub_community_id'] ?: "NULL").",
            ".(int)$_POST['gender_id'].",
            '{$_POST['dob']}',
            ".($tob ? "'$tob'" : "NULL").",
            ".($email ? "'$email'" : "NULL").",
            '$mobile',
            '".esc($_POST['address'])."',
            ".(int)$_POST['city_id'].",
            '".esc($_POST['pincode'])."',
            {$processed_values['qualification_id']},
            $working_city_id,
            $monthly_income,
            $marital_status,
            {$processed_values['kulam_id']},
            {$processed_values['gothiram_id']},
            {$processed_values['dhosam_type_id']},
            {$processed_values['dhosam_name_id']},
            {$processed_values['raasi_id']},
            {$processed_values['star_id']},
            $profile_photo,
            $jathagam_file,
            '',
            NOW()
        )";

        mysqli_query($conn,$sql) or throw new Exception(mysqli_error($conn));
        $uid = mysqli_insert_id($conn);

        /* ===== USERNAME + PASSWORD (LAST 4 DIGITS) ===== */
        $username = 'skm_' . str_pad($uid, 4, '0', STR_PAD_LEFT);
        $plain_password = substr($username, -4); // last 4 digits
        $password_hash = $plain_password; // store plain 4-digit password

        mysqli_query($conn,"
            UPDATE users 
            SET username='$username', password_hash='$password_hash' 
            WHERE id=$uid
        ") or throw new Exception(mysqli_error($conn));

        /* ===== OTHER PHOTOS ===== */
        if(isset($_FILES['other_photos']) && is_array($_FILES['other_photos']['name'])) {
            // Create uploads/other directory if it doesn't exist
            $other_upload_dir = "uploads/other/";
            if(!is_dir($other_upload_dir)) {
                mkdir($other_upload_dir, 0755, true);
            }
            
            for($i = 0; $i < count($_FILES['other_photos']['name']); $i++) {
                // Skip empty file inputs
                if(empty($_FILES['other_photos']['name'][$i]) || 
                   $_FILES['other_photos']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                
                $tmp_name = $_FILES['other_photos']['tmp_name'][$i];
                $file_name = $_FILES['other_photos']['name'][$i];
                
                // Get file extension
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                // Validate file type
                if(!in_array($ext, $allowed_extensions)) {
                    continue;
                }
                
                // Generate unique filename
                $path = $other_upload_dir . time() . "_" . rand(1000,9999) . "_$uid" . "_$i.$ext";
                
                // Move uploaded file
                if(move_uploaded_file($tmp_name, $path)){
                    mysqli_query($conn,"
                        INSERT INTO user_other_photos (user_id, photo_path, created_at)
                        VALUES ($uid, '$path', NOW())
                    ") or throw new Exception(mysqli_error($conn));
                }
            }
        }

        mysqli_query($conn,"INSERT INTO user_registration_status (user_id,status,created_at)
                            VALUES ($uid,'new',NOW())");

        mysqli_query($conn,"INSERT INTO user_payments (user_id,payment_method,approval_status,created_at)
                            VALUES ($uid,'not_paid','not_paid',NOW())");

        mysqli_commit($conn);
        header("Location: login.php");
        exit;

    } catch(Exception $e){
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

/* ===== MARITAL OPTIONS ===== */
$marital_options = [
    'unmarried' => 'Unmarried',
    'married'   => 'Married',
    'divorced'  => 'Divorced',
    'widowed'   => 'Widowed',
    'separated' => 'Separated'
];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sri Kumaran Mana Maalai</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tom Select CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        :root {
            --primary-blue: #0A1F44;
            --secondary-blue: #2C3E76;
            --accent-blue: #4A6FDC;
            --light-blue: #E8F0FE;
            --white: #FFFFFF;
            --off-white: #F8FAFF;
            --text-dark: #1A1A2E;
            --text-light: #4A5568;
            --transition: all 0.3s ease;
            --radius: 6px;
            --shadow-sm: 0 2px 8px rgba(10, 31, 68, 0.05);
            --shadow-md: 0 4px 16px rgba(10, 31, 68, 0.08);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Disable autofill styling */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        textarea:-webkit-autofill,
        textarea:-webkit-autofill:hover,
        textarea:-webkit-autofill:focus,
        select:-webkit-autofill,
        select:-webkit-autofill:hover,
        select:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--text-dark);
            -webkit-box-shadow: 0 0 0px 1000px var(--off-white) inset !important;
            transition: background-color 5000s ease-in-out 0s;
            background-color: var(--off-white) !important;
        }

        .form-container-wrapper {
            width: 100%;
            height: calc(100vh - 80px);
            padding: 15px;
            margin: 0;
            box-sizing: border-box;
        }

        .compact-form-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 0;
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            height: 100%;
            min-height: calc(100vh - 90px);
        }

        .form-left-panel {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .form-left-content {
            flex: 1;
        }

        .form-logo {
            height: 40px;
            margin-bottom: 20px;
        }

        .form-left-content h1 {
            font-size: 1.5rem;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .form-left-content p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .login-link {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .login-link a {
            color: white;
            text-decoration: underline;
            margin-left: 5px;
        }

        /* FORM RIGHT PANEL */
        .form-right-panel {
            padding: 25px;
            overflow-y: auto;
            height: 100%;
            max-height: calc(100vh - 90px);
        }

        .form-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(10, 31, 68, 0.1);
        }

        .form-header h2 {
            color: var(--primary-blue);
            font-size: 1.4rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-header p {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* 3 COLUMN FORM GRID */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-label.required::after {
            content: ' *';
            color: #DC3545;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(10, 31, 68, 0.15);
            border-radius: var(--radius);
            font-size: 0.95rem;
            transition: var(--transition);
            background: var(--off-white);
            height: 42px;
            box-sizing: border-box;
            autocomplete: "off";
        }

        .form-control:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px var(--off-white) inset !important;
            box-shadow: 0 0 0 30px var(--off-white) inset !important;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(74, 111, 220, 0.1);
            background: var(--white);
        }

        textarea.form-control {
            height: auto;
            min-height: 80px;
            resize: vertical;
            padding: 12px;
        }

        /* TOB Time Input */
        .tob-container {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .tob-input {
            width: 70px;
            text-align: center;
        }

        .tob-separator {
            color: var(--text-light);
        }

        .tob-ampm {
            width: 80px;
        }

        /* Tom Select Custom Styles */
        .tom-select-wrapper {
            position: relative;
        }

        .ts-control {
            border: 1px solid rgba(10, 31, 68, 0.15) !important;
            border-radius: var(--radius) !important;
            background: var(--off-white) !important;
            padding: 10px 12px !important;
            height: 42px !important;
            box-shadow: none !important;
            transition: var(--transition) !important;
            font-size: 0.95rem !important;
        }

        .ts-control.focus {
            border-color: var(--accent-blue) !important;
            box-shadow: 0 0 0 3px rgba(74, 111, 220, 0.1) !important;
            background: var(--white) !important;
        }

        .ts-dropdown {
            border: 1px solid rgba(10, 31, 68, 0.15) !important;
            border-radius: var(--radius) !important;
            margin-top: 4px !important;
            box-shadow: var(--shadow-md) !important;
            background: var(--white) !important;
            z-index: 1000 !important;
        }

        .ts-dropdown .option {
            padding: 10px 12px !important;
            cursor: pointer !important;
            font-size: 0.95rem !important;
        }

        .ts-dropdown .option:hover {
            background: var(--light-blue) !important;
        }

        .ts-dropdown .option.selected {
            background: var(--accent-blue) !important;
            color: white !important;
        }

        .ts-dropdown .no-results {
            padding: 12px !important;
            color: var(--text-light) !important;
            font-style: italic !important;
            font-size: 0.9rem !important;
        }

        .ts-wrapper.single .ts-control input {
            height: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            font-size: 0.95rem !important;
        }

        .error-message {
            color: #DC3545;
            font-size: 0.8rem;
            margin-top: 5px;
            display: block;
            min-height: 16px;
        }

        /* Photo upload row */
        .photo-upload-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 15px;
        }

        .photo-upload-item {
            display: flex;
            flex-direction: column;
        }

        /* FILE UPLOAD */
        .file-upload-container {
            border: 1px dashed rgba(10, 31, 68, 0.2);
            border-radius: var(--radius);
            padding: 15px;
            text-align: center;
            background: var(--off-white);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            font-size: 0.9rem;
            flex: 1;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .file-upload-container:hover {
            border-color: var(--accent-blue);
            background: var(--light-blue);
        }

        .file-upload-container input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-icon {
            font-size: 1.5rem;
            color: var(--accent-blue);
            margin-bottom: 10px;
        }

        .file-upload-label {
            font-size: 0.9rem;
            color: var(--text-light);
            display: block;
        }

        .preview-container {
            margin-top: 10px;
        }

        .preview-image {
            max-width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: var(--radius);
            border: 1px solid rgba(10, 31, 68, 0.1);
        }

        /* Multiple photos preview */
        .other-photos-preview {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-top: 15px;
        }

        .other-photo-item {
            position: relative;
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid rgba(10, 31, 68, 0.1);
        }

        .other-photo-item img {
            width: 100%;
            height: 90px;
            object-fit: cover;
        }

        .remove-photo {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.8rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .remove-photo:hover {
            background: #dc3545;
            transform: scale(1.1);
        }

        .photo-number {
            position: absolute;
            bottom: 5px;
            left: 5px;
            background: rgba(10, 31, 68, 0.8);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ALERT */
        .alert-custom {
            grid-column: 1 / -1;
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #DC3545;
            padding: 12px 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .alert-custom.success {
            background: rgba(25, 135, 84, 0.1);
            border-color: rgba(25, 135, 84, 0.2);
            color: #198754;
        }

        /* SUBMIT SECTION */
        .submit-section {
            grid-column: 1 / -1;
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(10, 31, 68, 0.1);
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--accent-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            padding: 12px 40px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-sm);
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .form-footer-links {
            margin-top: 15px;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .form-footer-links a {
            color: var(--accent-blue);
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer-links a:hover {
            text-decoration: underline;
        }

        /* SCROLLBAR */
        .form-right-panel::-webkit-scrollbar {
            width: 6px;
        }
        
        .form-right-panel::-webkit-scrollbar-track {
            background: rgba(10, 31, 68, 0.05);
            border-radius: 10px;
        }
        
        .form-right-panel::-webkit-scrollbar-thumb {
            background: rgba(10, 31, 68, 0.15);
            border-radius: 10px;
        }
        
        .form-right-panel::-webkit-scrollbar-thumb:hover {
            background: rgba(10, 31, 68, 0.25);
        }

        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .form-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 992px) {
            .compact-form-container {
                grid-template-columns: 1fr;
                height: auto;
                min-height: auto;
            }
            
            .form-left-panel {
                padding: 20px;
            }
            
            .form-right-panel {
                max-height: none;
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .photo-upload-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .other-photos-preview {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .form-container-wrapper {
                height: auto;
                min-height: auto;
                padding: 10px;
            }
            
            .compact-form-container {
                min-height: auto;
            }
            
            .form-right-panel {
                padding: 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .form-header h2 {
                font-size: 1.2rem;
            }
            
            .submit-btn {
                padding: 12px 30px;
                width: 100%;
                justify-content: center;
            }
            
            .other-photos-preview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .tob-container {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 576px) {
            .form-container-wrapper {
                padding: 8px;
            }
            
            .form-right-panel {
                padding: 12px;
            }
            
            .form-grid {
                gap: 12px;
            }
            
            .other-photos-preview {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Include Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- FORM CONTAINER -->
    <div class="form-container-wrapper">
        <div class="compact-form-container">
            <!-- Left Panel (Compact) -->
            <div class="form-left-panel">
                <div class="form-left-content">
                    <img src="<?= BASE_URL ?>/logo.png" alt="Logo" class="form-logo">
                    <h1>Join Our Community</h1>
                    <p>Find your perfect match with traditional values and modern compatibility.</p>
                    <div class="login-link">
                        Already have an account? 
                        <a href="login.php">Login here</a>
                    </div>
                </div>
            </div>

            <!-- Right Panel (Form) -->
            <div class="form-right-panel">
                <?php if($error): ?>
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" id="regForm" class="form-grid" autocomplete="off">
                    <!-- Row 1 -->
                    <div class="form-group">
                        <label class="form-label required">Full Name</label>
                        <input type="text" class="form-control" name="name" required placeholder="Full name" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Mobile</label>
                        <input type="text" class="form-control" name="mobile" id="mobile" required placeholder="10-digit" autocomplete="off">
                        <div id="mobile_err" class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email" placeholder="email@example.com" autocomplete="off">
                        <div id="email_err" class="error-message"></div>
                    </div>

                    <!-- Row 2 -->
                   
                    <div class="form-group">
                        <label class="form-label required">Gender</label>
                        <select class="form-select" name="gender_id" required autocomplete="off">
                            <option value="">Select Gender</option>
                            <?php while($g=mysqli_fetch_assoc($genders)) echo "<option value='{$g['id']}'>{$g['gender_name']}</option>"; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Date of Birth</label>
                        <input type="date" class="form-control" name="dob" required autocomplete="off">
                    </div>

                    <!-- Row 3 -->
                    <div class="form-group">
                        <label class="form-label">Time of Birth</label>
                        <div class="tob-container">
                            <select class="form-select tob-input" name="tob_hours" autocomplete="off">
                                <option value="">HH</option>
                                <?php for($i=1; $i<=12; $i++): ?>
                                    <option value="<?= sprintf("%02d", $i) ?>"><?= sprintf("%02d", $i) ?></option>
                                <?php endfor; ?>
                            </select>
                            <span class="tob-separator">:</span>
                            <select class="form-select tob-input" name="tob_minutes" autocomplete="off">
                                <option value="">MM</option>
                                <?php for($i=0; $i<60; $i++): ?>
                                    <option value="<?= sprintf("%02d", $i) ?>"><?= sprintf("%02d", $i) ?></option>
                                <?php endfor; ?>
                            </select>
                            <select class="form-select tob-ampm" name="tob_ampm" autocomplete="off">
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Marital Status</label>
                        <select class="form-select" name="marital_status" autocomplete="off">
                            <option value="">Select Status</option>
                            <?php foreach($marital_options as $k=>$v) echo "<option value='{$k}'>{$v}</option>"; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Community</label>
                        <select class="form-select" name="community_id" required id="communitySelect" autocomplete="off">
                            <option value="">Select Community</option>
                            <?php while($cm=mysqli_fetch_assoc($communities)) echo "<option value='{$cm['id']}'>{$cm['community_name']}</option>"; ?>
                        </select>
                    </div>

                    <!-- Row 4 -->
                    <div class="form-group">
                        <label class="form-label">Sub Community</label>
                        <select class="form-select" name="sub_community_id" id="sub_community" autocomplete="off">
                            <option value="">Select Sub Community</option>
                        </select>
                    </div>

                    <!-- Kulam with searchable dropdown -->
                    <div class="form-group">
                        <label class="form-label">Kulam</label>
                        <select name="kulam_id" id="kulamSelect" class="searchable-select" autocomplete="off">
                            <option value="">Select Kulam</option>
                            <?php 
                            mysqli_data_seek($kulams, 0);
                            while($k=mysqli_fetch_assoc($kulams)): 
                            ?>
                                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['kulam_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Gothiram with searchable dropdown -->
                    <div class="form-group">
                        <label class="form-label">Gothiram</label>
                        <select name="gothiram_id" id="gothiramSelect" class="searchable-select" autocomplete="off">
                            <option value="">Select Gothiram</option>
                            <?php 
                            mysqli_data_seek($gothirams, 0);
                            while($gt=mysqli_fetch_assoc($gothirams)): 
                            ?>
                                <option value="<?= $gt['id'] ?>"><?= htmlspecialchars($gt['gothiram_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Row 5 -->
                    <!-- Raasi with searchable dropdown -->
                    <div class="form-group">
                        <label class="form-label">Raasi</label>
                        <select name="raasi_id" id="raasiSelect" class="searchable-select" autocomplete="off">
                            <option value="">Select Raasi</option>
                            <?php 
                            mysqli_data_seek($raasis, 0);
                            while($r=mysqli_fetch_assoc($raasis)): 
                            ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['raasi_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Star with searchable dropdown -->
                    <div class="form-group">
                        <label class="form-label">Star</label>
                        <select name="star_id" id="starSelect" class="searchable-select" autocomplete="off">
                            <option value="">Select Star</option>
                            <?php 
                            mysqli_data_seek($stars, 0);
                            while($s=mysqli_fetch_assoc($stars)): 
                            ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['star_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Father's Name</label>
                        <input type="text" class="form-control" name="father_name" placeholder="Father's name" autocomplete="off">
                    </div>

                    <!-- Row 6 -->
                    <div class="form-group">
                        <label class="form-label">Mother's Name</label>
                        <input type="text" class="form-control" name="mother_name" placeholder="Mother's name" autocomplete="off">
                    </div>

                    <!-- Dhosam Type with searchable dropdown -->
                    <div class="form-group">
                        <label class="form-label">Dhosam Type</label>
                        <select name="dhosam_type_id" id="dhosamTypeSelect" class="searchable-select" autocomplete="off">
                            <option value="">Select Type</option>
                            <?php 
                            mysqli_data_seek($dhosam_types, 0);
                            while($dt=mysqli_fetch_assoc($dhosam_types)): 
                            ?>
                                <option value="<?= $dt['id'] ?>"><?= htmlspecialchars($dt['dhosam_type']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Dhosam Name with searchable dropdown -->
                    <div class="form-group">
                        <label class="form-label">Dhosam</label>
                        <select name="dhosam_name_id" id="dhosamNameSelect" class="searchable-select" autocomplete="off">
                            <option value="">Select Dhosam</option>
                            <?php 
                            mysqli_data_seek($dhosam_names, 0);
                            while($dn=mysqli_fetch_assoc($dhosam_names)): 
                            ?>
                                <option value="<?= $dn['id'] ?>"><?= htmlspecialchars($dn['dhosam_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Row 7 -->
                    <!-- Qualification with searchable dropdown -->
                    <div class="form-group">
                        <label class="form-label">Qualification</label>
                        <select name="qualification_id" id="qualificationSelect" class="searchable-select" autocomplete="off">
                            <option value="">Select Qualification</option>
                            <?php 
                            mysqli_data_seek($qualifications, 0);
                            while($q=mysqli_fetch_assoc($qualifications)): 
                            ?>
                                <option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['qualification_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Monthly Income</label>
                        <input type="text" class="form-control" name="monthly_income" placeholder="Monthly income" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label required">City</label>
                        <select class="form-select" name="city_id" required autocomplete="off">
                            <option value="">Select City</option>
                            <?php mysqli_data_seek($cities,0); while($c=mysqli_fetch_assoc($cities)) echo "<option value='{$c['id']}'>{$c['city_name']}</option>"; ?>
                        </select>
                    </div>

                    <!-- Row 8 -->
                    <div class="form-group">
                        <label class="form-label">Working City</label>
                        <select class="form-select" name="working_city_id" autocomplete="off">
                            <option value="">Select City</option>
                            <?php mysqli_data_seek($cities,0); while($c=mysqli_fetch_assoc($cities)) echo "<option value='{$c['id']}'>{$c['city_name']}</option>"; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pincode</label>
                        <input type="text" class="form-control" name="pincode" placeholder="6-digit pincode" autocomplete="off">
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3" placeholder="Complete address" autocomplete="off"></textarea>
                    </div>

                    <!-- Photo Uploads in One Row -->
                    <div class="form-group full-width">
                        <label class="form-label">Upload Photos</label>
                        <div class="photo-upload-row">
                            <div class="photo-upload-item">
                                <div class="file-upload-container">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <span class="file-upload-label">Profile Photo</span>
                                    <input type="file" class="form-control" name="profile_photo" id="profilePhoto" accept="image/*">
                                </div>
                                <div class="preview-container" id="profilePreview"></div>
                            </div>

                            <div class="photo-upload-item">
                                <div class="file-upload-container">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <span class="file-upload-label">Jathagam File</span>
                                    <input type="file" class="form-control" name="jathagam_file" id="jathagamPhoto" accept="image/*,.pdf">
                                </div>
                                <div class="preview-container" id="jathagamPreview"></div>
                            </div>

                            <div class="photo-upload-item">
                                <div class="file-upload-container">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-images"></i>
                                    </div>
                                    <span class="file-upload-label">Other Photos</span>
                                    <input type="file" class="form-control" name="other_photos[]" id="otherPhotos" multiple accept="image/*">
                                </div>
                                <div class="preview-container" id="otherPreviewContainer"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Other Photos Preview -->
                    <div class="form-group full-width">
                        <div class="other-photos-preview" id="otherPhotosPreview"></div>
                    </div>

                    <!-- Submit Section -->
                    <div class="submit-section">
                        <button type="submit" class="submit-btn" id="submitBtn">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                        
                        
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tom Select JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    
    <script>
        // Disable autocomplete on all form fields
        $(document).ready(function() {
            $('input, select, textarea').attr('autocomplete', 'off');
            
            // Set max date for DOB (18 years ago)
            const today = new Date();
            const maxDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            $('input[name="dob"]').attr('max', maxDate.toISOString().split('T')[0]);
            
            // Adjust form height dynamically
            function adjustFormHeight() {
                const navbarHeight = $('nav').outerHeight() || 60;
                const windowHeight = $(window).height();
                const formWrapper = $('.form-container-wrapper');
                
                formWrapper.css('height', (windowHeight - navbarHeight - 10) + 'px');
                $('.form-right-panel').css('max-height', (windowHeight - navbarHeight - 10) + 'px');
            }
            
            adjustFormHeight();
            $(window).resize(adjustFormHeight);
            
            // Tom Select configuration for all searchable dropdowns (without create option)
            const tomSelectConfig = {
                maxOptions: 100,
                maxItems: 1,
                searchField: 'text',
                placeholder: 'Type to search...',
                allowEmptyOption: true,
                closeAfterSelect: true,
                highlight: true,
                render: {
                    no_results: function(data, escape) {
                        return '<div class="no-results">No results found for "' + escape(data.input) + '"</div>';
                    }
                }
            };
            
            // Initialize all searchable dropdowns
            const searchableDropdowns = [
                { id: 'kulamSelect', name: 'kulam_id' },
                { id: 'gothiramSelect', name: 'gothiram_id' },
                { id: 'raasiSelect', name: 'raasi_id' },
                { id: 'starSelect', name: 'star_id' },
                { id: 'dhosamTypeSelect', name: 'dhosam_type_id' },
                { id: 'dhosamNameSelect', name: 'dhosam_name_id' },
                { id: 'qualificationSelect', name: 'qualification_id' }
            ];
            
            // Store TomSelect instances
            window.tomSelectInstances = {};
            
            searchableDropdowns.forEach(function(dropdown) {
                const element = document.getElementById(dropdown.id);
                if (element) {
                    window.tomSelectInstances[dropdown.id] = new TomSelect(element, tomSelectConfig);
                }
            });
            
            // Community change handler
            $("#communitySelect").on("change", function () {
                let cid = $(this).val();
                $("#sub_community").html('<option value="">Loading...</option>');
                if (!cid) { 
                    $("#sub_community").html('<option value="">Select Sub Community</option>'); 
                    return; 
                }
                $.post("get_sub_communities.php", { community_id: cid }, function(res) {
                    $("#sub_community").html(res);
                });
            });
            
            // File preview functions
            function previewSingle(input, target){
                target.html('');
                if(input.files && input.files[0]){
                    let r = new FileReader();
                    r.onload = function(e) {
                        target.html(`<img src="${e.target.result}" class="preview-image" alt="Preview">`);
                    };
                    r.readAsDataURL(input.files[0]);
                }
            }
            
            $("#profilePhoto").on("change", function(){
                previewSingle(this, $("#profilePreview"));
            });
            
            $("#jathagamPhoto").on("change", function(){
                let fileName = this.files[0]?.name || '';
                if (fileName.toLowerCase().endsWith('.pdf')) {
                    $("#jathagamPreview").html(`
                        <div style="background: var(--light-blue); padding: 15px; border-radius: var(--radius); font-size: 0.9rem; text-align: center;">
                            <i class="fas fa-file-pdf" style="color: #dc3545; font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                            <strong>${fileName}</strong><br>
                            <small style="color: var(--text-light);">PDF File Ready to Upload</small>
                        </div>
                    `);
                } else {
                    previewSingle(this, $("#jathagamPreview"));
                }
            });
            
            // Multiple file preview
            let otherPhotos = [];
            
            $("#otherPhotos").on("change", function(e){
                const files = Array.from(e.target.files);
                
                files.forEach(file => {
                    const exists = otherPhotos.some(existing => 
                        existing.name === file.name && existing.size === file.size
                    );
                    
                    if (!exists && otherPhotos.length < 5) {
                        otherPhotos.push(file);
                    } else if (otherPhotos.length >= 5) {
                        alert('Maximum 5 photos allowed. Please remove some photos before adding more.');
                    }
                });
                
                updateOtherPhotosPreview();
                $(this).val('');
            });
            
            function updateOtherPhotosPreview() {
                const preview = $("#otherPhotosPreview");
                preview.empty();
                
                if (otherPhotos.length === 0) {
                    preview.append(`
                        <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: var(--text-light); font-size: 0.9rem;">
                            <i class="fas fa-images" style="font-size: 2rem; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                            No photos selected
                        </div>
                    `);
                    return;
                }
                
                otherPhotos.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const item = $(`
                            <div class="other-photo-item">
                                <img src="${e.target.result}" alt="Preview ${index + 1}">
                                <button type="button" class="remove-photo" data-index="${index}">
                                    <i class="fas fa-times"></i>
                                </button>
                                <div class="photo-number">${index + 1}</div>
                            </div>
                        `);
                        
                        preview.append(item);
                        
                        // Add remove functionality
                        item.find('.remove-photo').on('click', function() {
                            const idx = $(this).data('index');
                            otherPhotos.splice(idx, 1);
                            updateOtherPhotosPreview();
                        });
                    };
                    reader.readAsDataURL(file);
                });
                
                // Update file count display
                $("#otherPreviewContainer").html(`
                    <div style="font-size: 0.85rem; color: var(--text-light); margin-top: 8px; text-align: center;">
                        <i class="fas fa-check-circle me-2" style="color: var(--accent-blue);"></i>
                        ${otherPhotos.length} photo(s) selected (Max: 5)
                    </div>
                `);
            }
            
            // Initialize empty preview
            updateOtherPhotosPreview();
            
            // Mobile duplicate check
            function checkMobile(callback){
                const val = $("input[name='mobile']").val().trim();
                if(!val) { 
                    callback('empty'); 
                    return; 
                }
                $.post('check_mobile.php', {mobile: val}, function(res){ 
                    callback(res.trim()); 
                });
            }
            
            $("input[name='mobile']").on('blur', function(){
                const val = $(this).val().trim();
                if(val.length !== 10) {
                    $('#mobile_err').text('Mobile number must be 10 digits');
                    return;
                }
                
                checkMobile(function(res){
                    if(res === 'exists'){
                        $('#mobile_err').text('This mobile number is already registered');
                    } else if(res === 'ok') {
                        $('#mobile_err').text('');
                    }
                });
            });
            
            // Email duplicate check
            $("input[name='email']").on('blur', function(){
                const email = $(this).val().trim();
                if(email) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if(!emailRegex.test(email)) {
                        $('#email_err').text('Please enter a valid email address');
                        return;
                    }
                    
                    $.post('check_email.php', {email: email}, function(res){
                        if(res.trim() === 'exists'){
                            $('#email_err').text('This email is already registered');
                        } else if(res.trim() === 'ok') {
                            $('#email_err').text('');
                        }
                    });
                }
            });
            
            // Input formatting
            $('input[name="mobile"]').on('input', function() {
                let val = $(this).val().replace(/\D/g, '');
                if (val.length > 10) val = val.substring(0, 10);
                $(this).val(val);
            });
            
            $('input[name="pincode"]').on('input', function() {
                let val = $(this).val().replace(/\D/g, '');
                if (val.length > 6) val = val.substring(0, 6);
                $(this).val(val);
            });
            
          
            
            // Form submission handler
            $('#regForm').on('submit', function(e){
                e.preventDefault();
                
                // Clear previous errors
                $('.error-message').text('');
                $('.alert-custom').remove();
                
                // Show loading state
                const submitBtn = $('#submitBtn');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html(`
                    <i class="fas fa-spinner fa-spin me-2"></i>Processing...
                `);
                
                let hasError = false;
                
                // Validate required fields
                const requiredFields = [
                    { name: 'name', message: 'Full name is required' },
                    { name: 'mobile', message: 'Mobile number is required' },
                    { name: 'gender_id', message: 'Gender is required' },
                    { name: 'dob', message: 'Date of birth is required' },
                    { name: 'community_id', message: 'Community is required' },
                    { name: 'city_id', message: 'City is required' }
                ];
                
                requiredFields.forEach(function(field) {
                    const element = $(`[name="${field.name}"]`);
                    const value = element.val();
                    
                    if(!value || value === '') {
                        element.addClass('is-invalid');
                        element.siblings('.error-message').text(field.message);
                        hasError = true;
                    }
                });
                
                // Validate mobile format
                const mobile = $("input[name='mobile']").val();
                if(mobile && mobile.length !== 10) {
                    $('#mobile_err').text('Mobile number must be 10 digits');
                    hasError = true;
                }
               
                
                // Validate email format if provided
                const email = $("input[name='email']").val();
                if(email) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if(!emailRegex.test(email)) {
                        $('#email_err').text('Please enter a valid email address');
                        hasError = true;
                    }
                }
                
                // Check mobile and email duplicates
                checkMobile(function(mobileRes){
                    if(mobileRes === 'exists'){
                        $('#mobile_err').text('This mobile number is already registered');
                        hasError = true;
                    }
                    
                    if(email) {
                        $.post('check_email.php', {email: email}, function(emailRes){
                            if(emailRes.trim() === 'exists'){
                                $('#email_err').text('This email is already registered');
                                hasError = true;
                            }
                            
                            if(hasError) {
                                // Restore button state
                                submitBtn.prop('disabled', false).html(originalText);
                                
                                // Show error message
                                $('#regForm').prepend(`
                                    <div class="alert-custom">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>Please fix the errors before submitting.</span>
                                    </div>
                                `);
                                
                                // Scroll to top
                                $('.form-right-panel').animate({scrollTop: 0}, 300);
                            } else {
                                // Submit the form
                                $('#regForm')[0].submit();
                            }
                        });
                    } else {
                        if(hasError) {
                            // Restore button state
                            submitBtn.prop('disabled', false).html(originalText);
                            
                            // Show error message
                            $('#regForm').prepend(`
                                <div class="alert-custom">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span>Please fix the errors before submitting.</span>
                                </div>
                            `);
                            
                            // Scroll to top
                            $('.form-right-panel').animate({scrollTop: 0}, 300);
                        } else {
                            // Submit the form
                            $('#regForm')[0].submit();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>