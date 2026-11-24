<?php
require "config/init.php";

// PHPMailer for sending registration emails (if available)
$hasPHPMailer = false;
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
    $hasPHPMailer = true;
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function esc($v){
    global $conn;
    return mysqli_real_escape_string($conn, trim($v));
}

$error = "";
$success = "";

/* ================= MASTER DATA ================= */
$communities    = mysqli_query($conn,"SELECT id, community_name FROM master_community WHERE status=1");
$nirams         = mysqli_query($conn,"SELECT id, niram_name FROM master_niram WHERE status=1 ORDER BY id ASC");
$genders        = mysqli_query($conn,"SELECT id, gender_name FROM master_gender WHERE status=1");
$cities         = mysqli_query($conn,"SELECT id, city_name FROM master_city WHERE status=1");
$qualifications = mysqli_query($conn,"SELECT id, qualification_name FROM master_qualification WHERE status=1");
$kulams         = mysqli_query($conn,"SELECT id, kulam_name FROM master_kulam WHERE status=1");
$gothirams      = mysqli_query($conn,"SELECT id, gothiram_name FROM master_gothiram WHERE status=1");
$dhosam_types   = mysqli_query($conn,"SELECT id, dhosam_type FROM master_dhosam_type WHERE status=1 ORDER BY FIELD(dhosam_type, 'Yes', 'No')");
$dhosam_names   = mysqli_query($conn,"SELECT id, dhosam_name FROM master_dhosam_name WHERE status=1 AND LOWER(dhosam_name) != 'no' ORDER BY dhosam_name");
$raasis         = mysqli_query($conn,"SELECT id, raasi_name FROM master_raasi WHERE status=1");
$stars          = mysqli_query($conn,"SELECT id, star_name FROM master_star WHERE status=1");
// Add this line for lagnam
$lagnams        = mysqli_query($conn,"SELECT id, lagnam_name FROM master_lagnam WHERE status=1");

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

        /* ===== HANDLE NIRAM (COLOR) ===== */
        $community_id = (int)$_POST['community_id'];
        $niram_val = esc($_POST['niram'] ?? '');
        $niram_name = null;
        if (!empty($niram_val)) {
            $chk = mysqli_query($conn, "SELECT id, niram_name FROM master_niram WHERE LOWER(niram_name)=LOWER('$niram_val') AND status=1 LIMIT 1");
            if (mysqli_num_rows($chk) > 0) {
                $niram_row = mysqli_fetch_assoc($chk);
                $niram_name = esc($niram_row['niram_name']);
            } else {
                mysqli_query($conn, "INSERT INTO master_niram (niram_name, status) VALUES ('$niram_val', 1)");
                $niram_name = $niram_val;
            }
        }
        $sub_community_id = "NULL";

        /* ===== HANDLE CITY ===== */
        $city_id = "NULL";
        $city_value = esc($_POST['city_id'] ?? '');

        if (!empty($city_value)) {
            if (is_numeric($city_value)) {
                // Existing ID selected
                $city_id = (int)$city_value;
            } else {
                // New value typed - check if exists, otherwise create
                $check_city = mysqli_query($conn, "
                    SELECT id FROM master_city
                    WHERE state_id = 1
                    AND LOWER(city_name) = LOWER('$city_value')
                    AND status = 1
                    LIMIT 1
                ");

                if (mysqli_num_rows($check_city) > 0) {
                    $existing = mysqli_fetch_assoc($check_city);
                    $city_id = $existing['id'];
                } else {
                    if (!mysqli_query($conn, "
                        INSERT INTO master_city (state_id, city_name, status)
                        VALUES (1, '$city_value', 1)
                    ")) {
                        throw new Exception(mysqli_error($conn));
                    }

                    $city_id = mysqli_insert_id($conn);
                }
            }
        }

        /* ===== HANDLE WORKING CITY ===== */
        $working_city_id = "NULL";
        $working_city_value = esc($_POST['working_city_id'] ?? '');

        if (!empty($working_city_value)) {
            if (is_numeric($working_city_value)) {
                // Existing ID selected
                $working_city_id = (int)$working_city_value;
            } else {
                // New value typed - check if exists, otherwise create
                $check_working_city = mysqli_query($conn, "
                    SELECT id FROM master_city
                    WHERE state_id = 1
                    AND LOWER(city_name) = LOWER('$working_city_value')
                    AND status = 1
                    LIMIT 1
                ");

                if (mysqli_num_rows($check_working_city) > 0) {
                    $existing = mysqli_fetch_assoc($check_working_city);
                    $working_city_id = $existing['id'];
                } else {
                    if (!mysqli_query($conn, "
                        INSERT INTO master_city (state_id, city_name, status)
                        VALUES (1, '$working_city_value', 1)
                    ")) {
                        throw new Exception(mysqli_error($conn));
                    }

                    $working_city_id = mysqli_insert_id($conn);
                }
            }
        }

        /* ===== HANDLE KULAM ===== */
        $kulam_id = "NULL";
        $kulam_value = esc($_POST['kulam_id'] ?? '');

        if (!empty($kulam_value)) {
            if (is_numeric($kulam_value)) {
                // Existing ID selected
                $kulam_id = (int)$kulam_value;
            } else {
                // New value typed - check if exists, otherwise create
                $check_kulam = mysqli_query($conn, "
                    SELECT id FROM master_kulam
                    WHERE community_id = $community_id
                    AND LOWER(kulam_name) = LOWER('$kulam_value')
                    AND status = 1
                    LIMIT 1
                ");

                if (mysqli_num_rows($check_kulam) > 0) {
                    $existing = mysqli_fetch_assoc($check_kulam);
                    $kulam_id = $existing['id'];
                } else {
                    if (!mysqli_query($conn, "
                        INSERT INTO master_kulam (community_id, kulam_name, status)
                        VALUES ($community_id, '$kulam_value', 1)
                    ")) {
                        throw new Exception(mysqli_error($conn));
                    }

                    $kulam_id = mysqli_insert_id($conn);
                }
            }
        }

        /* ===== HANDLE GOTHIRAM ===== */
        $gothiram_id = "NULL";
        $gothiram_value = esc($_POST['gothiram_id'] ?? '');

        if (!empty($gothiram_value)) {
            if (is_numeric($gothiram_value)) {
                // Existing ID selected
                $gothiram_id = (int)$gothiram_value;
            } else {
                // New value typed - check if exists, otherwise create
                $check_gothiram = mysqli_query($conn, "
                    SELECT id FROM master_gothiram
                    WHERE LOWER(gothiram_name) = LOWER('$gothiram_value')
                    AND status = 1
                    LIMIT 1
                ");

                if (mysqli_num_rows($check_gothiram) > 0) {
                    $existing = mysqli_fetch_assoc($check_gothiram);
                    $gothiram_id = $existing['id'];
                } else {
                    if (!mysqli_query($conn, "
                        INSERT INTO master_gothiram (gothiram_name, status)
                        VALUES ('$gothiram_value', 1)
                    ")) {
                        throw new Exception(mysqli_error($conn));
                    }

                    $gothiram_id = mysqli_insert_id($conn);
                }
            }
        }

        /* ===== HANDLE QUALIFICATION ===== */
        $qualification_id = "NULL";
        $qualification_value = esc($_POST['qualification_id'] ?? '');

        if (!empty($qualification_value)) {
            if (is_numeric($qualification_value)) {
                // Existing ID selected
                $qualification_id = (int)$qualification_value;
            } else {
                // New value typed - check if exists, otherwise create
                $check_qualification = mysqli_query($conn, "
                    SELECT id FROM master_qualification
                    WHERE LOWER(qualification_name) = LOWER('$qualification_value')
                    AND status = 1
                    LIMIT 1
                ");

                if (mysqli_num_rows($check_qualification) > 0) {
                    $existing = mysqli_fetch_assoc($check_qualification);
                    $qualification_id = $existing['id'];
                } else {
                    if (!mysqli_query($conn, "
                        INSERT INTO master_qualification (qualification_name, status)
                        VALUES ('$qualification_value', 1)
                    ")) {
                        throw new Exception(mysqli_error($conn));
                    }

                    $qualification_id = mysqli_insert_id($conn);
                }
            }
        }

        /* ===== HANDLE STAR ===== */
        $star_id = "NULL";
        $star_value = esc($_POST['star_id'] ?? '');

        if (!empty($star_value)) {
            if (is_numeric($star_value)) {
                // Existing ID selected
                $star_id = (int)$star_value;
            } else {
                // New value typed - check if exists, otherwise create
                $check_star = mysqli_query($conn, "
                    SELECT id FROM master_star
                    WHERE LOWER(star_name) = LOWER('$star_value')
                    AND status = 1
                    LIMIT 1
                ");

                if (mysqli_num_rows($check_star) > 0) {
                    $existing = mysqli_fetch_assoc($check_star);
                    $star_id = $existing['id'];
                } else {
                    if (!mysqli_query($conn, "
                        INSERT INTO master_star (star_name, status)
                        VALUES ('$star_value', 1)
                    ")) {
                        throw new Exception(mysqli_error($conn));
                    }

                    $star_id = mysqli_insert_id($conn);
                }
            }
        }

        /* ===== HANDLE LAGNAM ===== */
        $lagnam_id = "NULL";
        $lagnam_value = esc($_POST['lagnam_id'] ?? '');

        if (!empty($lagnam_value)) {
            if (is_numeric($lagnam_value)) {
                // Existing ID selected
                $lagnam_id = (int)$lagnam_value;
            } else {
                // New value typed - check if exists, otherwise create
                $check_lagnam = mysqli_query($conn, "
                    SELECT id FROM master_lagnam
                    WHERE LOWER(lagnam_name) = LOWER('$lagnam_value')
                    AND status = 1
                    LIMIT 1
                ");

                if (mysqli_num_rows($check_lagnam) > 0) {
                    $existing = mysqli_fetch_assoc($check_lagnam);
                    $lagnam_id = $existing['id'];
                } else {
                    if (!mysqli_query($conn, "
                        INSERT INTO master_lagnam (lagnam_name, status)
                        VALUES ('$lagnam_value', 1)
                    ")) {
                        throw new Exception(mysqli_error($conn));
                    }

                    $lagnam_id = mysqli_insert_id($conn);
                }
            }
        }

        /* ===== HANDLE DHOSAM TYPE ===== */
        $dhosam_type_id = "NULL";
        $dhosam_type_value = esc($_POST['dhosam_type_id'] ?? '');

        if (!empty($dhosam_type_value)) {
            if (is_numeric($dhosam_type_value)) {
                // Existing ID selected
                $dhosam_type_id = (int)$dhosam_type_value;
            } else {
                // New value typed - check if exists, otherwise create
                $check_dhosam_type = mysqli_query($conn, "
                    SELECT id FROM master_dhosam_type
                    WHERE LOWER(dhosam_type) = LOWER('$dhosam_type_value')
                    AND status = 1
                    LIMIT 1
                ");

                if (mysqli_num_rows($check_dhosam_type) > 0) {
                    $existing = mysqli_fetch_assoc($check_dhosam_type);
                    $dhosam_type_id = $existing['id'];
                } else {
                    if (!mysqli_query($conn, "
                        INSERT INTO master_dhosam_type (dhosam_type, status)
                        VALUES ('$dhosam_type_value', 1)
                    ")) {
                        throw new Exception(mysqli_error($conn));
                    }

                    $dhosam_type_id = mysqli_insert_id($conn);
                }
            }
        }

        /* ===== CHECK IF DHOSAM TYPE IS NO ===== */
        $is_no_dhosam = true;
        if ($dhosam_type_id !== "NULL") {
            $chk_dt = mysqli_query($conn, "SELECT dhosam_type FROM master_dhosam_type WHERE id = $dhosam_type_id LIMIT 1");
            if ($row_dt = mysqli_fetch_assoc($chk_dt)) {
                if (strtolower(trim($row_dt['dhosam_type'])) === 'yes') {
                    $is_no_dhosam = false;
                }
            }
        }

        /* ===== HANDLE DHOSAM NAME (Only if Dhosam Type is Yes) ===== */
        $dhosam_name_id = "NULL";
        if (!$is_no_dhosam) {
            $dhosam_name_value = esc($_POST['dhosam_name_id'] ?? '');

            if (!empty($dhosam_name_value)) {
                if (is_numeric($dhosam_name_value)) {
                    // Existing ID selected
                    $dhosam_name_id = (int)$dhosam_name_value;
                } else {
                    // New value typed - check if exists, otherwise create
                    $check_dhosam_name = mysqli_query($conn, "
                        SELECT id FROM master_dhosam_name
                        WHERE LOWER(dhosam_name) = LOWER('$dhosam_name_value')
                        AND status = 1
                        LIMIT 1
                    ");

                    if (mysqli_num_rows($check_dhosam_name) > 0) {
                        $existing = mysqli_fetch_assoc($check_dhosam_name);
                        $dhosam_name_id = $existing['id'];
                    } else {
                        if (!mysqli_query($conn, "
                            INSERT INTO master_dhosam_name (dhosam_type_id, dhosam_name, status)
                            VALUES (".($dhosam_type_id !== "NULL" ? $dhosam_type_id : "6").", '$dhosam_name_value', 1)
                        ")) {
                            throw new Exception(mysqli_error($conn));
                        }

                        $dhosam_name_id = mysqli_insert_id($conn);
                    }
                }
            }
        }

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

        /* ===== VALIDATE OTHER MASTER IDS ===== */
        $field_mappings = [
            'dhosam_type_id'   => 'master_dhosam_type',
            'dhosam_name_id'   => 'master_dhosam_name',
            'raasi_id'         => 'master_raasi',
            'star_id'          => 'master_star',
            'lagnam_id'        => 'master_lagnam'
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

        $monthly_income  = $_POST['monthly_income'] ? "'".esc($_POST['monthly_income'])."'" : "NULL";
        if(isset($_POST['monthly_income_nil']) && $_POST['monthly_income_nil'] == '1') {
            $monthly_income = "'NIL'";
        }

        $marital_status  = $_POST['marital_status'] ? "'".esc($_POST['marital_status'])."'" : "'unmarried'";

        /* ===== FILE UPLOADS ===== */
        $profile_photo_path = null;
        if(!empty($_FILES['profile_photo']['name'])){
            $ext  = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $path = "uploads/profile/".time()."_".rand(1000,9999).".".$ext;
            if(move_uploaded_file($_FILES['profile_photo']['tmp_name'],$path)){
                $profile_photo_path = $path;
            }
        }

        $jathagam_file_path = null;
        if(!empty($_FILES['jathagam_file']['name'])){
            $ext  = strtolower(pathinfo($_FILES['jathagam_file']['name'], PATHINFO_EXTENSION));
            $path = "uploads/jathagam/".time()."_".rand(1000,9999).".".$ext;
            if(move_uploaded_file($_FILES['jathagam_file']['tmp_name'],$path)){
                $jathagam_file_path = $path;
            }
        }

        $payment_proof_path = null;
        if(!empty($_FILES['payment_proof']['name'])){
            $ext  = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            // ensure uploads/payment directory exists
            $payment_dir = "uploads/payment/";
            if(!is_dir($payment_dir)) {
                mkdir($payment_dir, 0755, true);
            }
            $path = $payment_dir.time()."_".rand(1000,9999).".".$ext;
            if(move_uploaded_file($_FILES['payment_proof']['tmp_name'],$path)){
                $payment_proof_path = $path;
            }
        }

        /* ===== INSERT USER ===== */

/* ===== INSERT USER ===== */
/* ===== INSERT USER ===== */
$sql = "
INSERT INTO users (
    name, father_name, mother_name,
    community_id, niram, sub_community_id, gender_id, dob, tob,
    email, mobile, address, city_id, pincode,
    qualification_id, working_city_id, monthly_income, marital_status,
    kulam_id, gothiram_id, dhosam_type_id, dhosam_name_id,
    raasi_id, star_id, lagnam_id,
    profile_photo, jathagam_file,
    password_hash, created_at, other_info, height  -- ADDED height HERE
) VALUES (
    '".esc($_POST['name'])."',
    '".esc($_POST['father_name'])."',
    '".esc($_POST['mother_name'])."',
    $community_id,
    ".($niram_name ? "'$niram_name'" : "NULL").",
    NULL,
    ".(int)$_POST['gender_id'].",
    '{$_POST['dob']}',
    ".($tob ? "'$tob'" : "NULL").",
    ".($email ? "'$email'" : "NULL").",
    '$mobile',
    '".esc($_POST['address'])."',
    ".($city_id !== "NULL" ? $city_id : "NULL").",
    '".esc($_POST['pincode'])."',
    ".($qualification_id !== "NULL" ? $qualification_id : "NULL").",
    $working_city_id,
    $monthly_income,
    $marital_status,
    ".($kulam_id !== "NULL" ? $kulam_id : "NULL").",
    ".($gothiram_id !== "NULL" ? $gothiram_id : "NULL").",
    ".($dhosam_type_id !== "NULL" ? $dhosam_type_id : "NULL").",
    ".($dhosam_name_id !== "NULL" ? $dhosam_name_id : "NULL").",
    ".($processed_values['raasi_id'] !== "NULL" ? $processed_values['raasi_id'] : "NULL").",
    ".($star_id !== "NULL" ? $star_id : "NULL").",
    ".($lagnam_id !== "NULL" ? $lagnam_id : "NULL").",
    '".($profile_photo_path ?: "NULL")."',
    '".($jathagam_file_path ?: "NULL")."',
    '',
    NOW(),
    '".esc($_POST['other_info'] ?? '')."',
    '".esc($_POST['height'] ?? '')."'  -- ADDED height VALUE HERE
)";



        if (!mysqli_query($conn,$sql)) {
            throw new Exception(mysqli_error($conn));
        }
        $uid = mysqli_insert_id($conn);

        /* ===== USERNAME + PASSWORD ===== */
        $username = 'skm' . str_pad($uid, 4, '0', STR_PAD_LEFT);
        $plain_password = substr($username, -4);
        $password_hash = $plain_password;

        if (!mysqli_query($conn,"
            UPDATE users 
            SET username='$username', password_hash='$password_hash' 
            WHERE id=$uid
        ")) {
            throw new Exception(mysqli_error($conn));
        }

        /* ===== OTHER PHOTOS ===== */
        if(isset($_FILES['other_photos']) && is_array($_FILES['other_photos']['name'])) {
            $other_upload_dir = "uploads/other/";
            if(!is_dir($other_upload_dir)) {
                mkdir($other_upload_dir, 0755, true);
            }
            
            for($i = 0; $i < count($_FILES['other_photos']['name']); $i++) {
                if(empty($_FILES['other_photos']['name'][$i]) || 
                   $_FILES['other_photos']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                
                $tmp_name = $_FILES['other_photos']['tmp_name'][$i];
                $file_name = $_FILES['other_photos']['name'][$i];
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if(!in_array($ext, $allowed_extensions)) {
                    continue;
                }
                
                $path = $other_upload_dir . time() . "_" . rand(1000,9999) . "_$uid" . "_$i.$ext";
                
                if(move_uploaded_file($tmp_name, $path)){
                    if (!mysqli_query($conn,"
                        INSERT INTO user_other_photos (user_id, photo_path, created_at)
                        VALUES ($uid, '$path', NOW())
                    ")) {
                        throw new Exception(mysqli_error($conn));
                    }
                }
            }
        }

        mysqli_query($conn,"INSERT INTO user_registration_status (user_id,status,created_at)
                            VALUES ($uid,'new',NOW())");

        mysqli_query($conn,"INSERT INTO user_payments (user_id,payment_method,approval_status,payment_proof_path,created_at)
                            VALUES ($uid,'not_paid','not_paid','".($payment_proof_path ?: "")."',NOW())");

        mysqli_commit($conn);

        // ===================== REGISTRATION SUCCESS EMAIL =====================
        if (!empty($email) && $hasPHPMailer) {
            $mail = new PHPMailer(true);
            try {
                // Gmail SMTP
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'yuva557489334@gmail.com';
                $mail->Password   = 'mvsa ybmc joxi sjwo'; // Gmail app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Sender / recipient (no-reply style)
                $mail->setFrom('yuva557489334@gmail.com', 'Sri Kumaran Manamaalai (No-Reply)');
                $mail->addAddress($email, esc($_POST['name']));
                $mail->addReplyTo('no-reply@srikumaranmanamaalai.com', 'No Reply');

                // Content (includes username and password)
                $mail->isHTML(false);
                $mail->Subject = 'Registration Successful - Sri Kumaran Manamaalai';

                $body  = "Dear " . esc($_POST['name']) . ",\r\n\r\n";
                $body .= "Thank you for registering with Sri Kumaran Manamaalai.\r\n\r\n";
                $body .= "Your login details are:\r\n";
                $body .= "Username: " . $username . "\r\n";
                $body .= "Password: " . $plain_password . "\r\n\r\n";
                $body .= "Please keep this information confidential.\r\n\r\n";
                $body .= "Regards,\r\n";
                $body .= "Sri Kumaran Manamaalai Team\r\n";

                $mail->Body = $body;

                $mail->send();
            } catch (Exception $e) {
                // Optionally log $e->getMessage();
            }
        } elseif (!empty($email)) {
            // Fallback: simple PHP mail() if PHPMailer is not available
            $to      = $email;
            $subject = "Registration Successful - Sri Kumaran Manamaalai";
            $message = "Dear " . esc($_POST['name']) . ",\r\n\r\n";
            $message .= "Thank you for registering with Sri Kumaran Manamaalai.\r\n\r\n";
            $message .= "Your login details are:\r\n";
            $message .= "Username: " . $username . "\r\n";
            $message .= "Password: " . $plain_password . "\r\n\r\n";
            $message .= "Please keep this information confidential.\r\n\r\n";
            $message .= "Regards,\r\n";
            $message .= "Sri Kumaran Manamaalai Team\r\n";

            $headers  = "From: Sri Kumaran Manamaalai <no-reply@srikumaranmanamaalai.com>\r\n";
            $headers .= "Reply-To: no-reply@srikumaranmanamaalai.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            @mail($to, $subject, $message, $headers);
        }

        // Redirect to profile view page showing full details after successful registration
        header("Location: view_profile.php?id=" . $uid . "&from=register");
        exit;

    } catch(Exception $e){
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

// Read success flag from query string (for showing success message)
if (isset($_GET['success']) && $_GET['success'] === '1') {
    $success = "Registration successful! A confirmation email has been sent to your registered email address.";
}

$marital_options = [
    'unmarried' => 'Unmarried',
    'married'   => 'Married',
    'divorced'  => 'Divorced',
    'widowed'   => 'Widowed',
    'separated' => 'Separated'
];
?><!DOCTYPE html>
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

        /* Dynamic Select Container */
        .dynamic-select-container {
            position: relative;
        }

        .dynamic-select-container .tom-select-wrapper {
            position: relative;
        }

        .dynamic-select-container .ts-control {
            position: relative;
        }

        .dynamic-select-toggle {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--accent-blue);
            font-size: 1rem;
            padding: 0;
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            line-height: 1;
            margin: 0;
        }

        .dynamic-select-toggle:hover {
            background: var(--light-blue);
            color: var(--accent-blue);
        }

        /* Position the toggle button inside the Tom Select control */
        .dynamic-select-container .ts-control {
            position: relative;
        }

        .dynamic-select-container .dynamic-select-toggle {
            position: absolute;
            right: 8px;
            top: 30%;
            transform: translateY(-50%);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: end;
            line-height: 1;
        }

        .dynamic-select-help {
            color: var(--text-light);
            font-size: 0.8rem;
            margin-top: 5px;
            display: block;
            font-style: italic;
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
            padding: 10px 40px 10px 12px !important;
            height: 42px !important;
            box-shadow: none !important;
            transition: var(--transition) !important;
            font-size: 0.95rem !important;
            position: relative !important;
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
            grid-template-columns: repeat(4, 1fr);
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
            
            .dynamic-select-toggle {
                position: relative;
                margin-top: 8px;
                margin-bottom: 8px;
                width: 100%;
                text-align: center;
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
                <?php if($success): ?>
                    <div class="alert-custom" style="border-left-color:#28a745;">
                        <i class="fas fa-check-circle" style="color:#28a745;"></i>
                        <span><?= $success ?></span>
                    </div>
                <?php endif; ?>

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

                    <!-- Row 4: Niram (Color) -->
                    <div class="form-group">
                        <label class="form-label">Niram</label>
                        <div class="dynamic-select-container">
                            <select class="searchable-select" name="niram" id="niramSelect" autocomplete="off">
                                <option value="">Select or type new Niram</option>
                                <?php mysqli_data_seek($nirams, 0); while($nr = mysqli_fetch_assoc($nirams)): ?>
                                    <option value="<?= htmlspecialchars($nr['niram_name']) ?>"><?= htmlspecialchars($nr['niram_name']) ?></option>
                                <?php endwhile; ?>
                            </select>

                            <button type="button" id="toggleNiramBtn" class="dynamic-select-toggle" title="Type new Niram">
                                <i class="fas fa-plus-circle"></i>
                            </button>

                            <small class="dynamic-select-help">
                                Select from list, type to create, or click the + icon
                            </small>
                        </div>
                    </div>

                    <!-- Row 4: Kulam -->
                    <div class="form-group">
                        <label class="form-label">Kulam</label>
                        <div class="dynamic-select-container">
                            <select class="searchable-select" name="kulam_id" id="kulamSelect" autocomplete="off">
                                <option value="">Select or type new Kulam</option>
                                <?php
                                mysqli_data_seek($kulams, 0);
                                while($k=mysqli_fetch_assoc($kulams)):
                                ?>
                                    <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['kulam_name']) ?></option>
                                <?php endwhile; ?>
                            </select>

                            <button type="button" id="toggleKulamBtn" class="dynamic-select-toggle">
                                <i class="fas fa-plus-circle"></i>
                            </button>

                            <small class="dynamic-select-help">
                                Select from list, type to create, or click the + icon
                            </small>
                        </div>
                    </div>

                    <!-- Row 4: Gothiram -->
                    <div class="form-group">
                        <label class="form-label">Gothiram</label>
                        <div class="dynamic-select-container">
                            <select class="searchable-select" name="gothiram_id" id="gothiramSelect" autocomplete="off">
                                <option value="">Select or type new Gothiram</option>
                                <?php
                                mysqli_data_seek($gothirams, 0);
                                while($gt=mysqli_fetch_assoc($gothirams)):
                                ?>
                                    <option value="<?= $gt['id'] ?>"><?= htmlspecialchars($gt['gothiram_name']) ?></option>
                                <?php endwhile; ?>
                            </select>

                            <button type="button" id="toggleGothiramBtn" class="dynamic-select-toggle">
                                <i class="fas fa-plus-circle"></i>
                            </button>

                            <small class="dynamic-select-help">
                                Select from list, type to create, or click the + icon
                            </small>
                        </div>
                    </div>

                    <!-- Row 5 -->
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

                    <div class="form-group">
                        <label class="form-label">Star</label>
                        <div class="dynamic-select-container">
                            <select name="star_id" id="starSelect" class="searchable-select" autocomplete="off">
                                <option value="">Select or type new Star</option>
                                <?php
                                mysqli_data_seek($stars, 0);
                                while($s=mysqli_fetch_assoc($stars)):
                                ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['star_name']) ?></option>
                                <?php endwhile; ?>
                            </select>

                            <small class="dynamic-select-help">
                                Select from list or type to search
                            </small>
                        </div>
                    </div>
                    <!-- Add this section after the Star field -->
<div class="form-group">
    <label class="form-label">Lagnam</label>
    <div class="dynamic-select-container">
        <select name="lagnam_id" id="lagnamSelect" class="searchable-select" autocomplete="off">
            <option value="">Select or type new Lagnam</option>
            <?php
            mysqli_data_seek($lagnams, 0);
            while($l=mysqli_fetch_assoc($lagnams)):
            ?>
                <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['lagnam_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <small class="dynamic-select-help">
            Select from list or type to search
        </small>
    </div>
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

                    <!-- Dhosam Type with Yes/No dropdown -->
                    <div class="form-group">
                        <label class="form-label">Dhosam Type</label>
                        <select name="dhosam_type_id" id="dhosamTypeSelect" class="form-control" autocomplete="off">
                            <option value="">Select Dhosam Type</option>
                            <?php
                            mysqli_data_seek($dhosam_types, 0);
                            while($dt=mysqli_fetch_assoc($dhosam_types)):
                            ?>
                                <option value="<?= $dt['id'] ?>"><?= htmlspecialchars($dt['dhosam_type']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Dhosam Name with searchable dropdown (hidden by default unless Yes is selected) -->
                    <div class="form-group" id="dhosamNameGroup" style="display: none;">
                        <label class="form-label">Dhosam</label>
                        <div class="dynamic-select-container">
                            <select name="dhosam_name_id" id="dhosamNameSelect" class="searchable-select" autocomplete="off">
                                <option value="">Select or type new Dhosam</option>
                                <?php
                                mysqli_data_seek($dhosam_names, 0);
                                while($dn=mysqli_fetch_assoc($dhosam_names)):
                                ?>
                                    <option value="<?= $dn['id'] ?>"><?= htmlspecialchars($dn['dhosam_name']) ?></option>
                                <?php endwhile; ?>
                            </select>

                            <button type="button" id="toggleDhosamNameBtn" class="dynamic-select-toggle">
                                <i class="fas fa-plus-circle"></i>
                            </button>

                            <small class="dynamic-select-help">
                                Select from list, type to create, or click the + icon
                            </small>
                        </div>
                    </div>

                    <!-- Row 7: Qualification with Type/Select -->
                    <div class="form-group">
                        <label class="form-label">Qualification</label>
                        <div class="dynamic-select-container">
                            <select class="searchable-select" name="qualification_id" id="qualificationSelect" autocomplete="off">
                                <option value="">Select or type new Qualification</option>
                                <?php
                                mysqli_data_seek($qualifications, 0);
                                while($q=mysqli_fetch_assoc($qualifications)):
                                ?>
                                    <option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['qualification_name']) ?></option>
                                <?php endwhile; ?>
                            </select>

                            <button type="button" id="toggleQualificationBtn" class="dynamic-select-toggle">
                                <i class="fas fa-plus-circle"></i>
                            </button>

                            <small class="dynamic-select-help">
                                Select from list, type to create, or click the + icon
                            </small>
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
                            <select name="city_id" id="citySelect" class="searchable-select" required autocomplete="off">
                                <option value="">Select City</option>
                                <?php mysqli_data_seek($cities,0); while($c=mysqli_fetch_assoc($cities)) echo "<option value='{$c['id']}'>{$c['city_name']}</option>"; ?>
                            </select>

                            <button type="button" id="toggleCityBtn" class="dynamic-select-toggle">
                                <i class="fas fa-plus-circle"></i>
                            </button>

                            <small class="dynamic-select-help">
                                Select from list, type to create, or click the + icon
                            </small>
                        </div>
                    </div>

                    <!-- Row 8 -->
                    <div class="form-group">
                        <label class="form-label">Working City</label>
                        <div class="dynamic-select-container">
                            <select name="working_city_id" id="workingCitySelect" class="searchable-select" autocomplete="off">
                                <option value="">Select City</option>
                                <?php mysqli_data_seek($cities,0); while($c=mysqli_fetch_assoc($cities)) echo "<option value='{$c['id']}'>{$c['city_name']}</option>"; ?>
                            </select>

                            <button type="button" id="toggleWorkingCityBtn" class="dynamic-select-toggle">
                                <i class="fas fa-plus-circle"></i>
                            </button>

                            <small class="dynamic-select-help">
                                Select from list, type to create, or click the + icon
                            </small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pincode</label>
                        <input type="text" class="form-control" name="pincode" placeholder="6-digit pincode" autocomplete="off">
                    </div>

<!-- Add this after the "others" textarea -->
<div class="form-group">
    <label class="form-label">Height</label>
    <input type="text" class="form-control" name="height" placeholder="e.g., 5'8\", 170 cm" autocomplete="off">
</div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3" placeholder="Complete address" autocomplete="off"></textarea>
                    </div>
                    
                    <div class="form-group">
    <label class="form-label">Others</label>
    <textarea class="form-control" name="other_info" rows="3" placeholder="Others" autocomplete="off"></textarea>
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

                            <div class="photo-upload-item">
                                <div class="file-upload-container">
                                    <div class="file-upload-icon">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <span class="file-upload-label required">Payment Proof</span>
                                    <input type="file" class="form-control" name="payment_proof" id="paymentProof" accept="image/*,.pdf" required>
                                </div>
                                <div class="preview-container" id="paymentPreview"></div>
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
        $(document).ready(function() {
            // Disable autocomplete on all form fields
            $('input, select, textarea').attr('autocomplete', 'off');

            // Extra: enforce autocomplete="off" whenever a field gets focus
            $(document).on('focus', 'input, select, textarea', function () {
                $(this).attr('autocomplete', 'off');
            });
            
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
            
            // Tom Select configuration for searchable dropdowns
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

            // Tom Select configuration for fields that allow creating new options
            const createableTomSelectConfig = {
                ...tomSelectConfig,
                create: true,
                createOnBlur: true,
                persist: false,
                render: {
                    ...tomSelectConfig.render,
                    no_results: function(data, escape) {
                        return '<div class="no-results">No results found for "' + escape(data.input) + '" - press Enter to add new</div>';
                    }
                }
            };
            
            // Initialize TomSelect for searchable dropdowns
            const searchableDropdowns = [
                { id: 'raasiSelect', name: 'raasi_id', create: false },
                { id: 'starSelect', name: 'star_id', create: false },
                                { id: 'dhosamNameSelect', name: 'dhosam_name_id', create: true },
                 { id: 'lagnamSelect', name: 'lagnam_id', create: false },
                { id: 'citySelect', name: 'city_id', create: true },
                { id: 'workingCitySelect', name: 'working_city_id', create: true },
                { id: 'niramSelect', name: 'niram', create: true },
                { id: 'kulamSelect', name: 'kulam_id', create: true },
                { id: 'gothiramSelect', name: 'gothiram_id', create: true },
                { id: 'qualificationSelect', name: 'qualification_id', create: true }
            ];
            
            window.tomSelectInstances = {};
            
            searchableDropdowns.forEach(function(dropdown) {
                const element = document.getElementById(dropdown.id);
                if (element) {
                    const config = dropdown.create ? createableTomSelectConfig : tomSelectConfig;
                    window.tomSelectInstances[dropdown.id] = new TomSelect(element, config);
                }
            });
            
            // Generic function to handle dynamic select/toggle
            function setupDynamicSelect(fieldName, selectId, toggleBtnId) {
                const selectElement = $(`#${selectId}`);
                const toggleBtn = $(`#${toggleBtnId}`);
                const tomSelectInstance = window.tomSelectInstances[selectId];

                let isCreateMode = false;

                // Toggle between select-only and create modes
                toggleBtn.on('click', function() {
                    if (!isCreateMode) {
                        // Switch to create mode
                        isCreateMode = true;
                        toggleBtn.html('<i class="fas fa-list"></i>');

                        // Update Tom Select to allow creating new options
                        if (tomSelectInstance) {
                            tomSelectInstance.settings.create = true;
                            tomSelectInstance.settings.createOnBlur = true;
                            tomSelectInstance.input.placeholder = 'Type to search or create new...';
                        }

                        // Clear current selection
                        selectElement.val('');
                        if (tomSelectInstance) {
                            tomSelectInstance.clear();
                        }
                    } else {
                        // Switch to select-only mode
                        isCreateMode = false;
                        toggleBtn.html('<i class="fas fa-plus-circle"></i>');

                        // Update Tom Select to disable creating new options
                        if (tomSelectInstance) {
                            tomSelectInstance.settings.create = false;
                            tomSelectInstance.settings.createOnBlur = false;
                            tomSelectInstance.input.placeholder = 'Type to search...';
                        }

                        // Clear current selection
                        selectElement.val('');
                        if (tomSelectInstance) {
                            tomSelectInstance.clear();
                        }
                    }
                });
            }
            
            // Setup dynamic selects
            setupDynamicSelect('niram', 'niramSelect', 'toggleNiramBtn');
            setupDynamicSelect('kulam', 'kulamSelect', 'toggleKulamBtn');
            setupDynamicSelect('gothiram', 'gothiramSelect', 'toggleGothiramBtn');
            setupDynamicSelect('qualification', 'qualificationSelect', 'toggleQualificationBtn');
            setupDynamicSelect('city', 'citySelect', 'toggleCityBtn');
            setupDynamicSelect('working_city', 'workingCitySelect', 'toggleWorkingCityBtn');
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
            setupDynamicSelect('dhosam_name', 'dhosamNameSelect', 'toggleDhosamNameBtn');
            
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

            $("#paymentProof").on("change", function(){
                const file = this.files[0];
                if (!file) return;
                const fileName = file.name || '';
                const fileSize = (file.size / 1024).toFixed(1) + ' KB';

                if (fileName.toLowerCase().endsWith('.pdf')) {
                    $("#paymentPreview").html(`
                        <div style="background: var(--light-blue); padding: 15px; border-radius: var(--radius); font-size: 0.9rem; text-align: center; border: 1px solid rgba(74,111,220,0.2);">
                            <i class="fas fa-file-pdf" style="color: #dc3545; font-size: 2rem; margin-bottom: 8px; display: block;"></i>
                            <strong style="display:block; word-break:break-all;">${fileName}</strong>
                            <small style="color: var(--text-light);">${fileSize} &bull; PDF Ready to Upload</small>
                        </div>
                    `);
                } else {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $("#paymentPreview").html(`
                            <div style="position:relative; width:100%;">
                                <img src="${e.target.result}"
                                     alt="Payment Proof Preview"
                                     style="width:100%; max-height:160px; object-fit:cover; border-radius:var(--radius); border:2px solid rgba(74,111,220,0.4); display:block;">
                                <div style="background:rgba(74,111,220,0.08); border:1px solid rgba(74,111,220,0.15); border-top:none; border-radius:0 0 var(--radius) var(--radius); padding:6px 10px; font-size:0.8rem; display:flex; align-items:center; gap:6px;">
                                    <i class="fas fa-check-circle" style="color:#28a745;"></i>
                                    <span style="color:var(--text-light); word-break:break-all;">${fileName} (${fileSize})</span>
                                </div>
                            </div>
                        `);
                    };
                    reader.readAsDataURL(file);
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
                        
                        item.find('.remove-photo').on('click', function() {
                            const idx = $(this).data('index');
                            otherPhotos.splice(idx, 1);
                            updateOtherPhotosPreview();
                        });
                    };
                    reader.readAsDataURL(file);
                });
                
                $("#otherPreviewContainer").html(`
                    <div style="font-size: 0.85rem; color: var(--text-light); margin-top: 8px; text-align: center;">
                        <i class="fas fa-check-circle me-2" style="color: var(--accent-blue);"></i>
                        ${otherPhotos.length} photo(s) selected (Max: 5)
                    </div>
                `);
            }
            
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
            
            // Close TomSelect dropdowns when entering pincode (prevents City/Working City dropdown staying open)
            function closeTomSelectDropdown(selectId) {
                try {
                    const inst = window.tomSelectInstances ? window.tomSelectInstances[selectId] : null;
                    if (inst) inst.close();
                } catch (e) { /* no-op */ }
            }

            $('input[name="pincode"]').on('input', function() {
                closeTomSelectDropdown('citySelect');
                closeTomSelectDropdown('workingCitySelect');
                let val = $(this).val().replace(/\D/g, '');
                if (val.length > 6) val = val.substring(0, 6);
                $(this).val(val);
            });

            $('input[name="pincode"]').on('focus', function() {
                closeTomSelectDropdown('citySelect');
                closeTomSelectDropdown('workingCitySelect');
            });
            
            $('input[name="monthly_income"]').on('input', function() {
                let val = $(this).val().replace(/[^\d,]/g, '');
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
                                submitBtn.prop('disabled', false).html(originalText);
                                
                                $('#regForm').prepend(`
                                    <div class="alert-custom">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>Please fix the errors before submitting.</span>
                                    </div>
                                `);
                                
                                $('.form-right-panel').animate({scrollTop: 0}, 300);
                            } else {
                                // Re-attach other photos before submitting
                                if (otherPhotos.length > 0) {
                                    const dataTransfer = new DataTransfer();
                                    otherPhotos.forEach(file => dataTransfer.items.add(file));
                                    document.getElementById('otherPhotos').files = dataTransfer.files;
                                }
                                $('#regForm')[0].submit();
                            }
                        });
                    } else {
                        if(hasError) {
                            submitBtn.prop('disabled', false).html(originalText);
                            
                            $('#regForm').prepend(`
                                <div class="alert-custom">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span>Please fix the errors before submitting.</span>
                                </div>
                            `);
                            
                            $('.form-right-panel').animate({scrollTop: 0}, 300);
                        } else {
                            // Re-attach other photos before submitting
                            if (otherPhotos.length > 0) {
                                const dataTransfer = new DataTransfer();
                                otherPhotos.forEach(file => dataTransfer.items.add(file));
                                document.getElementById('otherPhotos').files = dataTransfer.files;
                            }
                            $('#regForm')[0].submit();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>