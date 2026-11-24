<?php
require "../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

$page_title = "Add New Member";
$active     = "add_new_member";

/* ===================== HELPER ===================== */
function esc_val($v) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($v ?? ''));
}

/* ===================== MASTER DATA ===================== */
$communities    = mysqli_query($conn, "SELECT id, community_name FROM master_community WHERE status=1 ORDER BY community_name");
$nirams         = mysqli_query($conn, "SELECT id, niram_name FROM master_niram WHERE status=1 ORDER BY id ASC");
$genders        = mysqli_query($conn, "SELECT id, gender_name FROM master_gender WHERE status=1");
$cities         = mysqli_query($conn, "SELECT id, city_name FROM master_city WHERE status=1 ORDER BY city_name");
$qualifications = mysqli_query($conn, "SELECT id, qualification_name FROM master_qualification WHERE status=1 ORDER BY qualification_name");
$kulams         = mysqli_query($conn, "SELECT id, kulam_name FROM master_kulam WHERE status=1 ORDER BY kulam_name");
$gothirams      = mysqli_query($conn, "SELECT id, gothiram_name FROM master_gothiram WHERE status=1 ORDER BY gothiram_name");
$dhosam_types   = mysqli_query($conn, "SELECT id, dhosam_type FROM master_dhosam_type WHERE status=1 ORDER BY FIELD(dhosam_type, 'Yes', 'No')");
$dhosam_names   = mysqli_query($conn, "SELECT id, dhosam_name FROM master_dhosam_name WHERE status=1 AND LOWER(dhosam_name) != 'no' ORDER BY dhosam_name");
$raasis         = mysqli_query($conn, "SELECT id, raasi_name FROM master_raasi WHERE status=1");
$stars          = mysqli_query($conn, "SELECT id, star_name FROM master_star WHERE status=1 ORDER BY star_name");
$lagnams        = mysqli_query($conn, "SELECT id, lagnam_name FROM master_lagnam WHERE status=1 ORDER BY lagnam_name");

$marital_options = [
    'unmarried' => 'Unmarried',
    'married'   => 'Married',
    'divorced'  => 'Divorced',
    'widowed'   => 'Widowed',
    'separated' => 'Separated',
];

$error   = "";
$success = "";

/* ===================== FORM SUBMIT ===================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        mysqli_begin_transaction($conn);

        $mobile = esc_val($_POST['mobile']);
        $email  = esc_val($_POST['email']);

        if (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE mobile='$mobile' LIMIT 1")))
            throw new Exception("Mobile already registered");

        if ($email && mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE email='$email' LIMIT 1")))
            throw new Exception("Email already registered");

        /* --- Community --- */
        $community_id = (int)$_POST['community_id'];

        /* --- Niram (Color Details) --- */
        $niram_val = esc_val($_POST['niram'] ?? '');
        $niram_name = null;
        if (!empty($niram_val)) {
            $chk = mysqli_query($conn, "SELECT id, niram_name FROM master_niram WHERE LOWER(niram_name)=LOWER('$niram_val') AND status=1 LIMIT 1");
            if (mysqli_num_rows($chk) > 0) {
                $niram_row = mysqli_fetch_assoc($chk);
                $niram_name = esc_val($niram_row['niram_name']);
            } else {
                mysqli_query($conn, "INSERT INTO master_niram (niram_name, status) VALUES ('$niram_val', 1)");
                $niram_name = $niram_val;
            }
        }
        $sub_community_id = "NULL";

        /* --- City --- */
        $city_id    = "NULL";
        $city_value = esc_val($_POST['city_id'] ?? '');
        if (!empty($city_value)) {
            if (is_numeric($city_value)) { $city_id = (int)$city_value; }
            else {
                $chk = mysqli_query($conn, "SELECT id FROM master_city WHERE state_id=1 AND LOWER(city_name)=LOWER('$city_value') AND status=1 LIMIT 1");
                if (mysqli_num_rows($chk) > 0) { $city_id = mysqli_fetch_assoc($chk)['id']; }
                else { mysqli_query($conn, "INSERT INTO master_city (state_id,city_name,status) VALUES (1,'$city_value',1)"); $city_id = mysqli_insert_id($conn); }
            }
        }

        /* --- Working City --- */
        $working_city_id    = "NULL";
        $working_city_value = esc_val($_POST['working_city_id'] ?? '');
        if (!empty($working_city_value)) {
            if (is_numeric($working_city_value)) { $working_city_id = (int)$working_city_value; }
            else {
                $chk = mysqli_query($conn, "SELECT id FROM master_city WHERE state_id=1 AND LOWER(city_name)=LOWER('$working_city_value') AND status=1 LIMIT 1");
                if (mysqli_num_rows($chk) > 0) { $working_city_id = mysqli_fetch_assoc($chk)['id']; }
                else { mysqli_query($conn, "INSERT INTO master_city (state_id,city_name,status) VALUES (1,'$working_city_value',1)"); $working_city_id = mysqli_insert_id($conn); }
            }
        }

        /* --- Kulam --- */
        $kulam_id    = "NULL";
        $kulam_value = esc_val($_POST['kulam_id'] ?? '');
        if (!empty($kulam_value)) {
            if (is_numeric($kulam_value)) { $kulam_id = (int)$kulam_value; }
            else {
                $chk = mysqli_query($conn, "SELECT id FROM master_kulam WHERE community_id=$community_id AND LOWER(kulam_name)=LOWER('$kulam_value') AND status=1 LIMIT 1");
                if (mysqli_num_rows($chk) > 0) { $kulam_id = mysqli_fetch_assoc($chk)['id']; }
                else { mysqli_query($conn, "INSERT INTO master_kulam (community_id,kulam_name,status) VALUES ($community_id,'$kulam_value',1)"); $kulam_id = mysqli_insert_id($conn); }
            }
        }

        /* --- Gothiram --- */
        $gothiram_id    = "NULL";
        $gothiram_value = esc_val($_POST['gothiram_id'] ?? '');
        if (!empty($gothiram_value)) {
            if (is_numeric($gothiram_value)) { $gothiram_id = (int)$gothiram_value; }
            else {
                $chk = mysqli_query($conn, "SELECT id FROM master_gothiram WHERE LOWER(gothiram_name)=LOWER('$gothiram_value') AND status=1 LIMIT 1");
                if (mysqli_num_rows($chk) > 0) { $gothiram_id = mysqli_fetch_assoc($chk)['id']; }
                else { mysqli_query($conn, "INSERT INTO master_gothiram (gothiram_name,status) VALUES ('$gothiram_value',1)"); $gothiram_id = mysqli_insert_id($conn); }
            }
        }

        /* --- Qualification --- */
        $qualification_id    = "NULL";
        $qualification_value = esc_val($_POST['qualification_id'] ?? '');
        if (!empty($qualification_value)) {
            if (is_numeric($qualification_value)) { $qualification_id = (int)$qualification_value; }
            else {
                $chk = mysqli_query($conn, "SELECT id FROM master_qualification WHERE LOWER(qualification_name)=LOWER('$qualification_value') AND status=1 LIMIT 1");
                if (mysqli_num_rows($chk) > 0) { $qualification_id = mysqli_fetch_assoc($chk)['id']; }
                else { mysqli_query($conn, "INSERT INTO master_qualification (qualification_name,status) VALUES ('$qualification_value',1)"); $qualification_id = mysqli_insert_id($conn); }
            }
        }

        /* --- Star --- */
        $star_id    = "NULL";
        $star_value = esc_val($_POST['star_id'] ?? '');
        if (!empty($star_value)) {
            if (is_numeric($star_value)) { $star_id = (int)$star_value; }
            else {
                $chk = mysqli_query($conn, "SELECT id FROM master_star WHERE LOWER(star_name)=LOWER('$star_value') AND status=1 LIMIT 1");
                if (mysqli_num_rows($chk) > 0) { $star_id = mysqli_fetch_assoc($chk)['id']; }
                else { mysqli_query($conn, "INSERT INTO master_star (star_name,status) VALUES ('$star_value',1)"); $star_id = mysqli_insert_id($conn); }
            }
        }

        /* --- Lagnam --- */
        $lagnam_id    = "NULL";
        $lagnam_value = esc_val($_POST['lagnam_id'] ?? '');
        if (!empty($lagnam_value)) {
            if (is_numeric($lagnam_value)) { $lagnam_id = (int)$lagnam_value; }
            else {
                $chk = mysqli_query($conn, "SELECT id FROM master_lagnam WHERE LOWER(lagnam_name)=LOWER('$lagnam_value') AND status=1 LIMIT 1");
                if (mysqli_num_rows($chk) > 0) { $lagnam_id = mysqli_fetch_assoc($chk)['id']; }
                else { mysqli_query($conn, "INSERT INTO master_lagnam (lagnam_name,status) VALUES ('$lagnam_value',1)"); $lagnam_id = mysqli_insert_id($conn); }
            }
        }

        /* --- Dhosam Type --- */
        $dhosam_type_id    = "NULL";
        $dhosam_type_value = esc_val($_POST['dhosam_type_id'] ?? '');
        if (!empty($dhosam_type_value)) {
            if (is_numeric($dhosam_type_value)) { $dhosam_type_id = (int)$dhosam_type_value; }
            else {
                $chk = mysqli_query($conn, "SELECT id FROM master_dhosam_type WHERE LOWER(dhosam_type)=LOWER('$dhosam_type_value') AND status=1 LIMIT 1");
                if (mysqli_num_rows($chk) > 0) { $dhosam_type_id = mysqli_fetch_assoc($chk)['id']; }
                else { mysqli_query($conn, "INSERT INTO master_dhosam_type (dhosam_type,status) VALUES ('$dhosam_type_value',1)"); $dhosam_type_id = mysqli_insert_id($conn); }
            }
        }

        /* --- Check if Dhosam Type is No --- */
        $is_no_dhosam = true;
        if ($dhosam_type_id !== "NULL") {
            $chk_dt = mysqli_query($conn, "SELECT dhosam_type FROM master_dhosam_type WHERE id = $dhosam_type_id LIMIT 1");
            if ($row_dt = mysqli_fetch_assoc($chk_dt)) {
                if (strtolower(trim($row_dt['dhosam_type'])) === 'yes') {
                    $is_no_dhosam = false;
                }
            }
        }

        /* --- Dhosam Name (Only if Yes) --- */
        $dhosam_name_id    = "NULL";
        if (!$is_no_dhosam) {
            $dhosam_name_value = esc_val($_POST['dhosam_name_id'] ?? '');
            if (!empty($dhosam_name_value)) {
                if (is_numeric($dhosam_name_value)) { $dhosam_name_id = (int)$dhosam_name_value; }
                else {
                    $chk = mysqli_query($conn, "SELECT id FROM master_dhosam_name WHERE LOWER(dhosam_name)=LOWER('$dhosam_name_value') AND status=1 LIMIT 1");
                    if (mysqli_num_rows($chk) > 0) { $dhosam_name_id = mysqli_fetch_assoc($chk)['id']; }
                    else {
                        $dt_ref = ($dhosam_type_id !== "NULL") ? $dhosam_type_id : 6;
                        mysqli_query($conn, "INSERT INTO master_dhosam_name (dhosam_type_id,dhosam_name,status) VALUES ($dt_ref,'$dhosam_name_value',1)");
                        $dhosam_name_id = mysqli_insert_id($conn);
                    }
                }
            }
        }

        /* --- Raasi (select only) --- */
        $raasi_id  = "NULL";
        $raasi_val = $_POST['raasi_id'] ?? '';
        if ($raasi_val && is_numeric($raasi_val)) {
            $chk = mysqli_query($conn, "SELECT id FROM master_raasi WHERE id=" . (int)$raasi_val . " LIMIT 1");
            if (mysqli_num_rows($chk)) $raasi_id = (int)$raasi_val;
        }

        /* --- Time of Birth --- */
        $tob = '';
        if (!empty($_POST['tob_hours']) && !empty($_POST['tob_minutes'])) {
            $h  = (int)$_POST['tob_hours'];
            $m  = (int)$_POST['tob_minutes'];
            $ap = $_POST['tob_ampm'] ?? 'AM';
            if ($ap === 'PM' && $h < 12) $h += 12;
            if ($ap === 'AM' && $h == 12) $h  = 0;
            $tob = sprintf("%02d:%02d", $h, $m);
        }

        /* --- Monthly Income --- */
        $monthly_income = "NULL";
        if (isset($_POST['monthly_income_nil']) && $_POST['monthly_income_nil'] == '1') {
            $monthly_income = "'NIL'";
        } elseif (!empty($_POST['monthly_income'])) {
            $monthly_income = "'" . esc_val($_POST['monthly_income']) . "'";
        }

        $marital_status = !empty($_POST['marital_status'])
            ? "'" . esc_val($_POST['marital_status']) . "'"
            : "'unmarried'";

        /* --- Account Status --- */
        $active_status = esc_val($_POST['active_status'] ?? 'inactive');
        if (!in_array($active_status, ['active', 'inactive'])) $active_status = 'inactive';

        /* --- Payment Status --- */
        $payment_status_val = esc_val($_POST['payment_status'] ?? 'unpaid');
        if (!in_array($payment_status_val, ['paid', 'unpaid', 'pending'])) $payment_status_val = 'unpaid';

        /* --- Map to reg_status for DB compatibility --- */
        if ($active_status === 'active' && $payment_status_val === 'paid') {
            $reg_status = 'active';
        } elseif ($payment_status_val === 'paid') {
            $reg_status = 'paid';
        } elseif ($payment_status_val === 'pending') {
            $reg_status = 'payment_pending';
        } else {
            $reg_status = 'new';
        }

        /* --- File Uploads --- */
        $base_path = realpath(__DIR__ . '/..');

        $profile_photo_path = null;
        if (!empty($_FILES['profile_photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $dir = $base_path . '/uploads/profile/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $path = 'uploads/profile/' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $base_path . '/' . $path))
                $profile_photo_path = $path;
        }

        $jathagam_file_path = null;
        if (!empty($_FILES['jathagam_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['jathagam_file']['name'], PATHINFO_EXTENSION));
            $dir = $base_path . '/uploads/jathagam/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $path = 'uploads/jathagam/' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['jathagam_file']['tmp_name'], $base_path . '/' . $path))
                $jathagam_file_path = $path;
        }

        $payment_proof_path = null;
        if (!empty($_FILES['payment_proof']['name'])) {
            $ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            $dir = $base_path . '/uploads/payment/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $path = 'uploads/payment/' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $base_path . '/' . $path))
                $payment_proof_path = $path;
        }

        /* --- INSERT USER --- */
        $sql = "
        INSERT INTO users (
            name, father_name, mother_name,
            community_id, niram, sub_community_id, gender_id, dob, tob,
            email, mobile, address, city_id, pincode,
            qualification_id, working_city_id, monthly_income, marital_status,
            kulam_id, gothiram_id, dhosam_type_id, dhosam_name_id,
            raasi_id, star_id, lagnam_id,
            profile_photo, jathagam_file,
            password_hash, created_at, other_info, height
        ) VALUES (
            '" . esc_val($_POST['name'])        . "',
            '" . esc_val($_POST['father_name']) . "',
            '" . esc_val($_POST['mother_name']) . "',
            $community_id,
            " . ($niram_name ? "'$niram_name'" : "NULL") . ",
            NULL,
            " . (int)$_POST['gender_id'] . ",
            '" . esc_val($_POST['dob']) . "',
            " . ($tob ? "'$tob'" : "NULL") . ",
            " . ($email ? "'$email'" : "NULL") . ",
            '$mobile',
            '" . esc_val($_POST['address'])    . "',
            " . ($city_id !== "NULL"           ? $city_id           : "NULL") . ",
            '" . esc_val($_POST['pincode'])    . "',
            " . ($qualification_id !== "NULL"  ? $qualification_id  : "NULL") . ",
            $working_city_id,
            $monthly_income,
            $marital_status,
            " . ($kulam_id       !== "NULL" ? $kulam_id       : "NULL") . ",
            " . ($gothiram_id    !== "NULL" ? $gothiram_id    : "NULL") . ",
            " . ($dhosam_type_id !== "NULL" ? $dhosam_type_id : "NULL") . ",
            " . ($dhosam_name_id !== "NULL" ? $dhosam_name_id : "NULL") . ",
            " . ($raasi_id       !== "NULL" ? $raasi_id       : "NULL") . ",
            " . ($star_id        !== "NULL" ? $star_id        : "NULL") . ",
            " . ($lagnam_id      !== "NULL" ? $lagnam_id      : "NULL") . ",
            '" . ($profile_photo_path ?: "") . "',
            '" . ($jathagam_file_path ?: "") . "',
            '',
            NOW(),
            '" . esc_val($_POST['other_info'] ?? '') . "',
            '" . esc_val($_POST['height']     ?? '') . "'
        )";

        if (!mysqli_query($conn, $sql))
            throw new Exception(mysqli_error($conn));

        $uid = mysqli_insert_id($conn);

        /* --- Username + Password --- */
        $username       = 'skm' . str_pad($uid, 4, '0', STR_PAD_LEFT);
        $plain_password = substr($username, -4);
        $password_hash  = $plain_password;

        if (!mysqli_query($conn, "UPDATE users SET username='$username', password_hash='$password_hash' WHERE id=$uid"))
            throw new Exception(mysqli_error($conn));

        /* --- Other Photos --- */
        if (isset($_FILES['other_photos']) && is_array($_FILES['other_photos']['name'])) {
            $dir = $base_path . '/uploads/other/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            for ($i = 0; $i < count($_FILES['other_photos']['name']); $i++) {
                if (empty($_FILES['other_photos']['name'][$i]) || $_FILES['other_photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $ext = strtolower(pathinfo($_FILES['other_photos']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) continue;
                $path = 'uploads/other/' . time() . '_' . rand(1000,9999) . "_$uid" . "_$i.$ext";
                if (move_uploaded_file($_FILES['other_photos']['tmp_name'][$i], $base_path . '/' . $path))
                    mysqli_query($conn, "INSERT INTO user_other_photos (user_id,photo_path,created_at) VALUES ($uid,'$path',NOW())");
            }
        }

        /* --- Registration Status & Payment --- */
        mysqli_query($conn, "INSERT INTO user_registration_status (user_id,status,created_at) VALUES ($uid,'$reg_status',NOW())");

        $pay_method = 'not_paid';
        $pay_status = 'not_paid';
        $pay_proof  = $payment_proof_path ?: '';
        if ($payment_status_val === 'paid') {
            $pay_method = 'offline';
            $pay_status = 'approved';
        } elseif ($payment_status_val === 'pending') {
            $pay_method = 'offline';
            $pay_status = 'pending';
        } elseif (!empty($payment_proof_path)) {
            $pay_method = 'offline';
            $pay_status = 'pending';
        }
        mysqli_query($conn, "INSERT INTO user_payments (user_id,payment_method,approval_status,payment_proof_path,created_at)
                             VALUES ($uid,'$pay_method','$pay_status','$pay_proof',NOW())");

        mysqli_commit($conn);

        $success = "Member added successfully! &nbsp;<strong>Username:</strong> $username &nbsp; <strong>Password:</strong> $plain_password";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

/* ===================== RENDER ===================== */
ob_start();
?>

<!-- ── Same CSS + JS libs as register.php ── -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
/* =========================================================
   REGISTER-PAGE STYLE  —  scoped inside admin content area
   ========================================================= */
:root {
    --primary-blue  : #0A1F44;
    --secondary-blue: #2C3E76;
    --accent-blue   : #4A6FDC;
    --light-blue    : #E8F0FE;
    --white         : #FFFFFF;
    --off-white     : #F8FAFF;
    --text-dark     : #1A1A2E;
    --text-light    : #4A5568;
    --transition    : all 0.3s ease;
    --radius        : 6px;
    --shadow-sm     : 0 2px 8px rgba(10,31,68,.05);
    --shadow-md     : 0 4px 16px rgba(10,31,68,.08);
}

.reg-wrapper {
    background: #f5f7fb;
    padding: 4px 0 24px;
}

/* Single white card — mirrors register.php .compact-form-container right panel */
.reg-card {
    background: var(--white);
    border-radius: 12px;
    padding: 28px 30px;
    box-shadow: var(--shadow-md);
}

/* Alert banners */
.reg-alert {
    padding: 12px 16px;
    border-radius: var(--radius);
    font-size: 0.9rem;
    margin-bottom: 18px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.reg-alert.error   { background: rgba(220,53,69,.1);  border: 1px solid rgba(220,53,69,.2);  color: #DC3545; }
.reg-alert.success { background: rgba(25,135,84,.1);  border: 1px solid rgba(25,135,84,.2);  color: #198754; }

/* ── 3-column grid identical to register.php ── */
.form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}
.form-group          { margin-bottom: 0; }
.form-group.full-width { grid-column: 1 / -1; }

/* Section heading inside the flat grid */
.grid-section-heading {
    grid-column: 1 / -1;
    font-size: 1rem;
    font-weight: 600;
    color: var(--primary-blue);
    padding: 10px 0 8px;
    border-bottom: 1px solid rgba(10,31,68,.08);
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.grid-section-heading i { color: var(--accent-blue); }

/* Labels */
.form-label {
    display: block;
    font-weight: 500;
    color: var(--text-dark);
    margin-bottom: 8px;
    font-size: 0.9rem;
}
.form-label.required::after { content: ' *'; color: #DC3545; }

/* Inputs — exact copy from register.php */
.form-control,
.form-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid rgba(10,31,68,.15);
    border-radius: var(--radius);
    font-size: 0.95rem;
    background: var(--off-white);
    color: var(--text-dark);
    height: 42px;
    box-sizing: border-box;
    transition: var(--transition);
}
.form-control:focus,
.form-select:focus {
    outline: none;
    border-color: var(--accent-blue);
    box-shadow: 0 0 0 3px rgba(74,111,220,.1);
    background: var(--white);
}
textarea.form-control { height: auto; min-height: 80px; resize: vertical; padding: 12px; }

/* Error hint */
.error-message { color: #DC3545; font-size: 0.8rem; margin-top: 5px; display: block; min-height: 16px; }

/* Dynamic select (TomSelect + toggle btn) — same as register.php */
.dynamic-select-container { position: relative; }
.dynamic-select-toggle {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent; border: none;
    color: var(--accent-blue);
    font-size: 1rem; width: 24px; height: 24px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; z-index: 3; border-radius: var(--radius);
    transition: var(--transition);
    line-height: 1; margin: 0;
}
.dynamic-select-container .dynamic-select-toggle {
    top: 30%;
    transform: translateY(-50%);
}
.dynamic-select-toggle:hover { background: var(--light-blue); }
.dynamic-select-help { color: var(--text-light); font-size: 0.8rem; margin-top: 5px; display: block; font-style: italic; }

/* TomSelect — identical to register.php */
.ts-control {
    border: 1px solid rgba(10,31,68,.15) !important;
    border-radius: var(--radius) !important;
    background: var(--off-white) !important;
    padding: 10px 40px 10px 12px !important;
    height: 42px !important;
    box-shadow: none !important;
    transition: var(--transition) !important;
    font-size: 0.95rem !important;
    position: relative !important;
}
.ts-control.focus {
    border-color: var(--accent-blue) !important;
    box-shadow: 0 0 0 3px rgba(74,111,220,.1) !important;
    background: var(--white) !important;
}
.ts-dropdown {
    border: 1px solid rgba(10,31,68,.15) !important;
    border-radius: var(--radius) !important;
    margin-top: 4px !important;
    box-shadow: var(--shadow-md) !important;
    background: var(--white) !important;
    z-index: 1400 !important;
}
.ts-dropdown .option          { padding: 10px 12px !important; font-size: 0.95rem !important; cursor: pointer; }
.ts-dropdown .option:hover    { background: var(--light-blue) !important; }
.ts-dropdown .option.selected { background: var(--accent-blue) !important; color: #fff !important; }
.ts-dropdown .no-results      { padding: 12px !important; color: var(--text-light) !important; font-style: italic !important; font-size: 0.9rem !important; }
.ts-wrapper.single .ts-control input { height: 100% !important; padding: 0 !important; margin: 0 !important; font-size: 0.95rem !important; }

/* TOB row */
.tob-container { display: flex; gap: 8px; align-items: center; }
.tob-input     { width: 70px; text-align: center; }
.tob-separator { color: var(--text-light); }
.tob-ampm      { width: 80px; }

/* Photo upload row — 4 columns, same as register.php */
.photo-upload-row  { display: grid; grid-template-columns: repeat(4,1fr); gap: 20px; margin-bottom: 15px; }
.photo-upload-item { display: flex; flex-direction: column; }
.file-upload-container {
    border: 1px dashed rgba(10,31,68,.2);
    border-radius: var(--radius);
    padding: 15px; text-align: center;
    background: var(--off-white); cursor: pointer;
    transition: var(--transition);
    position: relative; overflow: hidden;
    font-size: 0.9rem; flex: 1;
    min-height: 120px;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
}
.file-upload-container:hover { border-color: var(--accent-blue); background: var(--light-blue); }
.file-upload-container input[type="file"] {
    position: absolute; top: 0; left: 0;
    width: 100%; height: 100%; opacity: 0; cursor: pointer;
}
.file-upload-icon  { font-size: 1.5rem; color: var(--accent-blue); margin-bottom: 10px; }
.file-upload-label { font-size: 0.9rem; color: var(--text-light); display: block; }

.preview-container { margin-top: 10px; }
.preview-image { max-width: 100%; height: 120px; object-fit: cover; border-radius: var(--radius); border: 1px solid rgba(10,31,68,.1); }

/* Other-photos grid — 5 columns */
.other-photos-preview {
    display: grid;
    grid-template-columns: repeat(5,1fr);
    gap: 12px; margin-top: 15px;
}
.other-photo-item  { position: relative; border-radius: var(--radius); overflow: hidden; border: 1px solid rgba(10,31,68,.1); }
.other-photo-item img { width: 100%; height: 90px; object-fit: cover; }
.remove-photo {
    position: absolute; top: 5px; right: 5px;
    background: rgba(220,53,69,.9); color: #fff;
    border: none; border-radius: 50%;
    width: 22px; height: 22px; font-size: 0.8rem;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: var(--transition);
}
.remove-photo:hover { background: #dc3545; transform: scale(1.1); }
.photo-number {
    position: absolute; bottom: 5px; left: 5px;
    background: rgba(10,31,68,.8); color: #fff;
    width: 20px; height: 20px; border-radius: 50%;
    font-size: 0.7rem; display: flex; align-items: center; justify-content: center;
}

/* Submit section */
.submit-section {
    grid-column: 1 / -1;
    margin-top: 30px;
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid rgba(10,31,68,.1);
}
.submit-btn {
    background: linear-gradient(135deg, var(--accent-blue) 0%, var(--secondary-blue) 100%);
    color: #fff; border: none; border-radius: var(--radius);
    padding: 12px 40px; font-size: 1rem; font-weight: 500;
    cursor: pointer; display: inline-flex; align-items: center; gap: 10px;
    box-shadow: var(--shadow-sm); transition: var(--transition);
}
.submit-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.submit-btn:disabled { opacity: .7; cursor: not-allowed; }

/* Alert custom (same class name as register.php) */
.alert-custom {
    grid-column: 1 / -1;
    background: rgba(220,53,69,.1);
    border: 1px solid rgba(220,53,69,.2);
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
    background: rgba(25,135,84,.1);
    border-color: rgba(25,135,84,.2);
    color: #198754;
}

/* Responsive */
@media(max-width:1100px){
    .form-grid        { grid-template-columns: repeat(2,1fr); }
    .photo-upload-row { grid-template-columns: repeat(2,1fr); }
}
@media(max-width:700px){
    .form-grid              { grid-template-columns: 1fr; }
    .photo-upload-row       { grid-template-columns: 1fr; }
    .other-photos-preview   { grid-template-columns: repeat(2,1fr); }
}
</style>

<div class="reg-wrapper">

<?php if ($error): ?>
<div class="reg-alert error"><i class="fas fa-exclamation-circle fa-lg"></i><div><?= htmlspecialchars($error) ?></div></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="reg-alert success"><i class="fas fa-check-circle fa-lg"></i><div><?= $success ?></div></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" id="regForm" autocomplete="off">

    <!-- ══════════════ PERSONAL INFO ══════════════ -->
    <div class="reg-card mb-4">
        <div class="reg-card-title"><i class="fas fa-user"></i> Personal Information</div>
        <div class="form-grid">

            <div class="form-group">
                <label class="form-label required">Full Name</label>
                <input type="text" class="form-control" name="name" required placeholder="Full name" autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label required">Mobile</label>
                <input type="text" class="form-control" name="mobile" id="mobile" required placeholder="10-digit mobile" autocomplete="off">
                <div id="mobile_err" class="err-msg"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" id="email" placeholder="email@example.com" autocomplete="off">
                <div id="email_err" class="err-msg"></div>
            </div>

            <div class="form-group">
                <label class="form-label required">Gender</label>
                <select class="form-select" name="gender_id" required>
                    <option value="">Select Gender</option>
                    <?php while ($g = mysqli_fetch_assoc($genders)): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['gender_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">Date of Birth</label>
                <input type="date" class="form-control" name="dob" id="dobInput" required autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">Time of Birth</label>
                <div class="tob-container">
                    <select class="form-select tob-input" name="tob_hours">
                        <option value="">HH</option>
                        <?php for ($i=1;$i<=12;$i++): ?><option value="<?= sprintf("%02d",$i) ?>"><?= sprintf("%02d",$i) ?></option><?php endfor; ?>
                    </select>
                    <span class="tob-sep">:</span>
                    <select class="form-select tob-input" name="tob_minutes">
                        <option value="">MM</option>
                        <?php for ($i=0;$i<60;$i++): ?><option value="<?= sprintf("%02d",$i) ?>"><?= sprintf("%02d",$i) ?></option><?php endfor; ?>
                    </select>
                    <select class="form-select tob-ampm" name="tob_ampm">
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label required">Community</label>
                <select class="form-select" name="community_id" id="communitySelect" required>
                    <option value="">Select Community</option>
                    <?php while ($cm = mysqli_fetch_assoc($communities)): ?>
                        <option value="<?= $cm['id'] ?>"><?= htmlspecialchars($cm['community_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Niram</label>
                <div class="dynamic-select-container">
                    <select class="searchable-select" name="niram" id="niramSelect">
                        <option value="">Select or type new Niram</option>
                        <?php mysqli_data_seek($nirams, 0); while ($nr = mysqli_fetch_assoc($nirams)): ?>
                            <option value="<?= htmlspecialchars($nr['niram_name']) ?>"><?= htmlspecialchars($nr['niram_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="button" id="toggleNiramBtn" class="dynamic-select-toggle" title="Type new Niram"><i class="fas fa-plus-circle"></i></button>
                    <small class="dynamic-select-help" id="niramHelp">Select from list, type to create, or click + to add new</small>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Father's Name</label>
                <input type="text" class="form-control" name="father_name" placeholder="Father's name" autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">Mother's Name</label>
                <input type="text" class="form-control" name="mother_name" placeholder="Mother's name" autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">Height</label>
                <input type="text" class="form-control" name="height" placeholder="e.g., 5'8&quot;, 170 cm" autocomplete="off">
            </div>

            <div class="form-group">
                <label class="form-label">Kulam</label>
                <div class="dynamic-select-container">
                    <select class="searchable-select" name="kulam_id" id="kulamSelect">
                        <option value="">Select or type new Kulam</option>
                        <?php mysqli_data_seek($kulams,0); while ($k=mysqli_fetch_assoc($kulams)): ?><option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['kulam_name']) ?></option><?php endwhile; ?>
                    </select>
                    <button type="button" id="toggleKulamBtn" class="dynamic-select-toggle"><i class="fas fa-plus-circle"></i></button>
                    <small class="dynamic-select-help">Select from list, type to create, or click +</small>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Gothiram</label>
                <div class="dynamic-select-container">
                    <select class="searchable-select" name="gothiram_id" id="gothiramSelect">
                        <option value="">Select or type new Gothiram</option>
                        <?php mysqli_data_seek($gothirams,0); while ($gt=mysqli_fetch_assoc($gothirams)): ?><option value="<?= $gt['id'] ?>"><?= htmlspecialchars($gt['gothiram_name']) ?></option><?php endwhile; ?>
                    </select>
                    <button type="button" id="toggleGothiramBtn" class="dynamic-select-toggle"><i class="fas fa-plus-circle"></i></button>
                    <small class="dynamic-select-help">Select from list, type to create, or click +</small>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Raasi</label>
                <select name="raasi_id" id="raasiSelect" class="searchable-select">
                    <option value="">Select Raasi</option>
                    <?php mysqli_data_seek($raasis,0); while ($r=mysqli_fetch_assoc($raasis)): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['raasi_name']) ?></option><?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Star</label>
                <div class="dynamic-select-container">
                    <select name="star_id" id="starSelect" class="searchable-select">
                        <option value="">Select or type new Star</option>
                        <?php mysqli_data_seek($stars,0); while ($s=mysqli_fetch_assoc($stars)): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['star_name']) ?></option><?php endwhile; ?>
                    </select>
                    <small class="dynamic-select-help">Select from list or type to search</small>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Lagnam</label>
                <div class="dynamic-select-container">
                    <select name="lagnam_id" id="lagnamSelect" class="searchable-select">
                        <option value="">Select or type new Lagnam</option>
                        <?php mysqli_data_seek($lagnams,0); while ($l=mysqli_fetch_assoc($lagnams)): ?><option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['lagnam_name']) ?></option><?php endwhile; ?>
                    </select>
                    <small class="dynamic-select-help">Select from list or type to search</small>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Dhosam Type</label>
                <select name="dhosam_type_id" id="dhosamTypeSelect" class="form-control">
                    <option value="">Select Dhosam Type</option>
                    <?php mysqli_data_seek($dhosam_types,0); while ($dt=mysqli_fetch_assoc($dhosam_types)): ?><option value="<?= $dt['id'] ?>"><?= htmlspecialchars($dt['dhosam_type']) ?></option><?php endwhile; ?>
                </select>
            </div>

            <div class="form-group" id="dhosamNameGroup" style="display:none;">
                <label class="form-label">Dhosam</label>
                <div class="dynamic-select-container">
                    <select name="dhosam_name_id" id="dhosamNameSelect" class="searchable-select">
                        <option value="">Select or type new Dhosam</option>
                        <?php mysqli_data_seek($dhosam_names,0); while ($dn=mysqli_fetch_assoc($dhosam_names)): ?><option value="<?= $dn['id'] ?>"><?= htmlspecialchars($dn['dhosam_name']) ?></option><?php endwhile; ?>
                    </select>
                    <button type="button" id="toggleDhosamNameBtn" class="dynamic-select-toggle"><i class="fas fa-plus-circle"></i></button>
                    <small class="dynamic-select-help">Select from list, type to create, or click +</small>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Qualification</label>
                <div class="dynamic-select-container">
                    <select class="searchable-select" name="qualification_id" id="qualificationSelect">
                        <option value="">Select or type new Qualification</option>
                        <?php mysqli_data_seek($qualifications,0); while ($q=mysqli_fetch_assoc($qualifications)): ?><option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['qualification_name']) ?></option><?php endwhile; ?>
                    </select>
                    <button type="button" id="toggleQualificationBtn" class="dynamic-select-toggle"><i class="fas fa-plus-circle"></i></button>
                    <small class="dynamic-select-help">Select from list, type to create, or click +</small>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Monthly Income / Annual Income</label>
                <input type="text" class="form-control" name="monthly_income" placeholder="Monthly income" autocomplete="off">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="monthly_income_nil" name="monthly_income_nil" value="1">
                    <label class="form-check-label" for="monthly_income_nil">NIL</label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label required">City</label>
                <div class="dynamic-select-container">
                    <select name="city_id" id="citySelect" class="searchable-select" required>
                        <option value="">Select City</option>
                        <?php mysqli_data_seek($cities,0); while ($c=mysqli_fetch_assoc($cities)): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['city_name']) ?></option><?php endwhile; ?>
                    </select>
                    <button type="button" id="toggleCityBtn" class="dynamic-select-toggle"><i class="fas fa-plus-circle"></i></button>
                    <small class="dynamic-select-help">Select from list, type to create, or click +</small>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Working City</label>
                <div class="dynamic-select-container">
                    <select name="working_city_id" id="workingCitySelect" class="searchable-select">
                        <option value="">Select City</option>
                        <?php mysqli_data_seek($cities,0); while ($c=mysqli_fetch_assoc($cities)): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['city_name']) ?></option><?php endwhile; ?>
                    </select>
                    <button type="button" id="toggleWorkingCityBtn" class="dynamic-select-toggle"><i class="fas fa-plus-circle"></i></button>
                    <small class="dynamic-select-help">Select from list, type to create, or click +</small>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Pincode</label>
                <input type="text" class="form-control" name="pincode" placeholder="6-digit pincode" autocomplete="off">
            </div>

            <div class="form-group full">
                <label class="form-label">Address</label>
                <textarea class="form-control" name="address" rows="3" placeholder="Complete address" autocomplete="off"></textarea>
            </div>
            <div class="form-group full">
                <label class="form-label">Others</label>
                <textarea class="form-control" name="other_info" rows="3" placeholder="Others" autocomplete="off"></textarea>
            </div>

        </div><!-- /form-grid -->
    </div><!-- /reg-card personal -->



    <!-- ══════════════ STATUS SECTION (admin editable) ══════════════ -->
    <div class="reg-card mb-4">
        <div class="reg-card-title"><i class="fas fa-sliders-h"></i> Status</div>
        <div class="form-grid">

            <!-- 1. Account Status -->
            <div class="form-group">
                <label class="form-label required">
                    <i class="fas fa-user-check" style="color:var(--accent-blue);margin-right:5px;"></i>Account Status
                </label>
                <select class="form-select" name="active_status" required>
                    <option value="">— Select —</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <small class="dynamic-select-help">Select account activation state</small>
            </div>

            <!-- 2. Marital Status -->
            <div class="form-group">
                <label class="form-label required">
                    <i class="fas fa-heart" style="color:var(--accent-blue);margin-right:5px;"></i>Marital Status
                </label>
                <select class="form-select" name="marital_status" required>
                    <option value="">— Select —</option>
                    <option value="married">Married</option>
                    <option value="unmarried">Unmarried</option>
                </select>
                <small class="dynamic-select-help">Select current marital status</small>
            </div>

            <!-- 3. Payment Status -->
            <div class="form-group">
                <label class="form-label required">
                    <i class="fas fa-credit-card" style="color:var(--accent-blue);margin-right:5px;"></i>Payment Status
                </label>
                <select class="form-select" name="payment_status" required>
                    <option value="">— Select —</option>
                    <option value="paid">Paid</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="pending">Pending</option>
                </select>
                <small class="dynamic-select-help">Select payment state</small>
            </div>

        </div>
    </div>
    
    <div class="reg-card mb-4">
        <div class="reg-card-title"><i class="fas fa-cloud-upload-alt"></i> Upload Photos</div>

        <div class="photo-upload-row">
            <div class="photo-upload-item">
                <div class="file-upload-container">
                    <div class="file-upload-icon"><i class="fas fa-user-circle"></i></div>
                    <span class="file-upload-label">Profile Photo</span>
                    <input type="file" name="profile_photo" id="profilePhoto" accept="image/*">
                </div>
                <div class="preview-container" id="profilePreview"></div>
            </div>
            <div class="photo-upload-item">
                <div class="file-upload-container">
                    <div class="file-upload-icon"><i class="fas fa-file-alt"></i></div>
                    <span class="file-upload-label">Jathagam File</span>
                    <input type="file" name="jathagam_file" id="jathagamPhoto" accept="image/*,.pdf">
                </div>
                <div class="preview-container" id="jathagamPreview"></div>
            </div>
            <div class="photo-upload-item">
                <div class="file-upload-container">
                    <div class="file-upload-icon"><i class="fas fa-images"></i></div>
                    <span class="file-upload-label">Other Photos</span>
                    <input type="file" name="other_photos[]" id="otherPhotos" multiple accept="image/*">
                </div>
                <div class="preview-container" id="otherPreviewContainer"></div>
            </div>
            <div class="photo-upload-item">
                <div class="file-upload-container">
                    <div class="file-upload-icon"><i class="fas fa-receipt"></i></div>
                    <span class="file-upload-label">Payment Proof</span>
                    <input type="file" name="payment_proof" id="paymentProof" accept="image/*,.pdf">
                </div>
                <div class="preview-container" id="paymentPreview"></div>
            </div>
        </div>

        <!-- Other photos grid preview -->
        <div class="other-photos-preview mt-3" id="otherPhotosPreview"></div>
    </div>

    <!-- ══════════════ SUBMIT ══════════════ -->
    <div class="text-center pb-2">
        <button type="submit" class="submit-btn" id="submitBtn">
            <i class="fas fa-user-plus"></i> Add Member
        </button>
    </div>

</form>
</div><!-- /reg-wrapper -->

<!-- TomSelect JS -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script>
$(document).ready(function () {

    /* --- DOB max (18 yrs) --- */
    const today  = new Date();
    const maxDob = new Date(today.getFullYear()-18, today.getMonth(), today.getDate());
    $('input[name="dob"]').attr('max', maxDob.toISOString().split('T')[0]);

    /* --- Mobile / pincode numeric only --- */
    $('input[name="mobile"]').on('input', function(){ let v=$(this).val().replace(/\D/g,''); if(v.length>10)v=v.substr(0,10); $(this).val(v); });
    $('input[name="pincode"]').on('input', function(){ let v=$(this).val().replace(/\D/g,''); if(v.length>6)v=v.substr(0,6); $(this).val(v); });
    $('input[name="monthly_income"]').on('input', function(){ $(this).val($(this).val().replace(/[^\d,]/g,'')); });

    /* --- TomSelect configs (identical to register.php) --- */
    const tomSelectConfig = {
        maxOptions: 100, maxItems: 1, searchField: 'text',
        allowEmptyOption: true, closeAfterSelect: true, highlight: true,
        render: { no_results: function(d,e){ return '<div class="no-results">No results for "'+e(d.input)+'"</div>'; } }
    };
    const createableTomSelectConfig = {
        ...tomSelectConfig, create: true, createOnBlur: true, persist: false,
        render: { ...tomSelectConfig.render,
            no_results: function(d,e){ return '<div class="no-results">No results for "'+e(d.input)+'" — press Enter to add</div>'; }
        }
    };

    const searchableDropdowns = [
        { id:'raasiSelect',         name:'raasi_id',         create:false },
        { id:'starSelect',          name:'star_id',          create:false },
        { id:'lagnamSelect',        name:'lagnam_id',        create:false },
        { id:'dhosamNameSelect',    name:'dhosam_name_id',   create:true  },
        { id:'citySelect',          name:'city_id',          create:true  },
        { id:'workingCitySelect',   name:'working_city_id',  create:true  },
        { id:'niramSelect',         name:'niram',            create:true  },
        { id:'kulamSelect',         name:'kulam_id',         create:true  },
        { id:'gothiramSelect',      name:'gothiram_id',      create:true  },
        { id:'qualificationSelect', name:'qualification_id', create:true  },
    ];

    window.tomSelectInstances = {};
    searchableDropdowns.forEach(function(d){
        const el = document.getElementById(d.id);
        if (el) window.tomSelectInstances[d.id] = new TomSelect(el, d.create ? createableTomSelectConfig : tomSelectConfig);
    });

    /* --- Toggle create/select mode (+ button) --- */
    function setupDynamicSelect(selectId, toggleBtnId){
        const toggleBtn = $('#'+toggleBtnId);
        const ts = window.tomSelectInstances[selectId];
        let createMode = false;
        toggleBtn.on('click', function(){
            createMode = !createMode;
            toggleBtn.html(createMode ? '<i class="fas fa-list"></i>' : '<i class="fas fa-plus-circle"></i>');
            if(ts){ ts.settings.create = createMode; ts.settings.createOnBlur = createMode; ts.clear(); }
        });
    }
    setupDynamicSelect('niramSelect',         'toggleNiramBtn');
    setupDynamicSelect('kulamSelect',         'toggleKulamBtn');
    setupDynamicSelect('gothiramSelect',      'toggleGothiramBtn');
    setupDynamicSelect('qualificationSelect', 'toggleQualificationBtn');
    setupDynamicSelect('citySelect',          'toggleCityBtn');
    setupDynamicSelect('workingCitySelect',   'toggleWorkingCityBtn');
    // Dhosam Type toggle handling (Yes -> show Dhosam, No -> hide)
    const dhosamTypeSelect = document.getElementById('dhosamTypeSelect');
    const dhosamNameGroup = document.getElementById('dhosamNameGroup');

    function handleDhosamTypeChange() {
        if (!dhosamTypeSelect || !dhosamNameGroup) return;
        const selectedText = dhosamTypeSelect.options[dhosamTypeSelect.selectedIndex]?.text?.trim().toLowerCase();
        if (selectedText === 'yes') {
            dhosamNameGroup.style.display = 'block';
        } else {
            dhosamNameGroup.style.display = 'none';
            const nameSelect = document.getElementById('dhosamNameSelect');
            if (nameSelect && nameSelect.tomselect) {
                nameSelect.tomselect.clear();
            } else if (nameSelect) {
                nameSelect.value = '';
            }
        }
    }

    if (dhosamTypeSelect) {
        dhosamTypeSelect.addEventListener('change', handleDhosamTypeChange);
        handleDhosamTypeChange();
    }
    setupDynamicSelect('dhosamNameSelect',    'toggleDhosamNameBtn');

    /* --- File previews --- */
    function previewSingle(input, target){
        target.html('');
        if(input.files && input.files[0]){
            const r = new FileReader();
            r.onload = e => target.html(`<img src="${e.target.result}" class="preview-image" alt="Preview">`);
            r.readAsDataURL(input.files[0]);
        }
    }

    $('#profilePhoto').on('change', function(){ previewSingle(this, $('#profilePreview')); });

    $('#jathagamPhoto').on('change', function(){
        const fn = this.files[0]?.name || '';
        if(fn.toLowerCase().endsWith('.pdf')){
            $('#jathagamPreview').html(`<div style="background:var(--light-blue);padding:12px;border-radius:var(--radius);text-align:center;font-size:.9rem;"><i class="fas fa-file-pdf" style="color:#dc3545;font-size:2rem;display:block;margin-bottom:6px;"></i><strong>${fn}</strong><br><small style="color:var(--text-light);">PDF Ready</small></div>`);
        } else { previewSingle(this, $('#jathagamPreview')); }
    });

    $('#paymentProof').on('change', function(){
        const file = this.files[0]; if(!file) return;
        const fn = file.name, sz = (file.size/1024).toFixed(1)+' KB';
        if(fn.toLowerCase().endsWith('.pdf')){
            $('#paymentPreview').html(`<div style="background:var(--light-blue);padding:12px;border-radius:var(--radius);text-align:center;font-size:.9rem;"><i class="fas fa-file-pdf" style="color:#dc3545;font-size:2rem;display:block;margin-bottom:6px;"></i><strong>${fn}</strong><br><small style="color:var(--text-light);">${sz}</small></div>`);
        } else {
            const rd = new FileReader();
            rd.onload = e => $('#paymentPreview').html(`<img src="${e.target.result}" class="preview-image" alt="">`);
            rd.readAsDataURL(file);
        }
    });

    /* --- Other photos multi-preview --- */
    let otherPhotos = [];

    $('#otherPhotos').on('change', function(e){
        Array.from(e.target.files).forEach(f=>{
            if(!otherPhotos.some(ex=>ex.name===f.name&&ex.size===f.size) && otherPhotos.length<5)
                otherPhotos.push(f);
            else if(otherPhotos.length>=5) alert('Maximum 5 photos allowed.');
        });
        updateOtherPhotosPreview();
        $(this).val('');
    });

    function updateOtherPhotosPreview(){
        const preview = $('#otherPhotosPreview').empty();
        if(otherPhotos.length===0){
            preview.append(`<div style="grid-column:1/-1;text-align:center;padding:16px;color:var(--text-light);font-size:.9rem;"><i class="fas fa-images" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.5;"></i>No photos selected</div>`);
            return;
        }
        otherPhotos.forEach((file,index)=>{
            const rd = new FileReader();
            rd.onload = e => {
                const item = $(`<div class="other-photo-item"><img src="${e.target.result}" alt=""><button type="button" class="remove-photo" data-index="${index}"><i class="fas fa-times"></i></button><div class="photo-number">${index+1}</div></div>`);
                item.find('.remove-photo').on('click',function(){ otherPhotos.splice($(this).data('index'),1); updateOtherPhotosPreview(); });
                preview.append(item);
            };
            rd.readAsDataURL(file);
        });
        $('#otherPreviewContainer').html(`<div style="font-size:.85rem;color:var(--text-light);margin-top:6px;text-align:center;"><i class="fas fa-check-circle me-1" style="color:var(--accent-blue);"></i>${otherPhotos.length} photo(s) selected (max 5)</div>`);
    }
    updateOtherPhotosPreview();

    /* --- Mobile duplicate check --- */
    $('input[name="mobile"]').on('blur', function(){
        const v = $(this).val().trim();
        if(v.length !== 10){ $('#mobile_err').text('Must be 10 digits'); return; }
        $.post('../check_mobile.php',{mobile:v},function(r){
            $('#mobile_err').text(r.trim()==='exists'?'Already registered':'');
        });
    });

    /* --- Email duplicate check --- */
    $('input[name="email"]').on('blur', function(){
        const e = $(this).val().trim();
        if(!e) return;
        if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)){ $('#email_err').text('Invalid email'); return; }
        $.post('../check_email.php',{email:e},function(r){
            $('#email_err').text(r.trim()==='exists'?'Already registered':'');
        });
    });

    /* --- Form submit with spinner --- */
    $('#regForm').on('submit', function(e){
        e.preventDefault();
        const btn = $('#submitBtn');
        btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
        if(otherPhotos.length>0){
            const dt = new DataTransfer();
            otherPhotos.forEach(f=>dt.items.add(f));
            document.getElementById('otherPhotos').files = dt.files;
        }
        this.submit();
    });
});
</script>

<?php
$content = ob_get_clean();
require "layout.php";
?>
