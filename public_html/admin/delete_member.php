<?php
require "../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: active_members.php");
    exit;
}

$user_id = (int)$_GET['id'];

if ($user_id <= 0) {
    header("Location: active_members.php");
    exit;
}

/* ============================================================
   HARD DELETE — permanently removes the member and all related
   data from every table that references users.id
   ============================================================ */
$ok   = true;
$err  = '';

// 1. Delete other photos records (and physical files)
$photos_res = mysqli_query($conn, "SELECT photo_path FROM user_other_photos WHERE user_id = $user_id");
while ($photo = mysqli_fetch_assoc($photos_res)) {
    $file_path = dirname(__DIR__) . '/' . $photo['photo_path'];
    if (file_exists($file_path)) {
        @unlink($file_path);
    }
}
mysqli_query($conn, "DELETE FROM user_other_photos WHERE user_id = $user_id");

// 2. Delete payment records
mysqli_query($conn, "DELETE FROM user_payments WHERE user_id = $user_id");

// 3. Delete registration status
mysqli_query($conn, "DELETE FROM user_registration_status WHERE user_id = $user_id");

// 4. Delete favorites (both directions)
mysqli_query($conn, "DELETE FROM user_favorites WHERE user_id = $user_id OR favorite_user_id = $user_id");

// 5. Delete stats rows if present
mysqli_query($conn, "DELETE FROM user_stats WHERE user_id = $user_id");
mysqli_query($conn, "DELETE FROM user_daily_stats WHERE user_id = $user_id");
mysqli_query($conn, "DELETE FROM user_monthly_stats WHERE user_id = $user_id");

// 6. Delete the user row itself (profile photo & jathagam file — remove physical files too)
$user_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT profile_photo, jathagam_file FROM users WHERE id = $user_id LIMIT 1"));
if ($user_row) {
    foreach (['profile_photo', 'jathagam_file'] as $field) {
        if (!empty($user_row[$field])) {
            $file_path = dirname(__DIR__) . '/' . $user_row[$field];
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }
    }
}

$res = mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
if (!$res) {
    $ok  = false;
    $err = mysqli_error($conn);
}

/* Determine where to send the admin back */
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// Strip query-string so we redirect to the list page cleanly
$redirect = 'active_members.php'; // default fallback
if (strpos($referer, 'inactive_members') !== false) {
    $redirect = 'inactive_members.php';
} elseif (strpos($referer, 'paid_members') !== false) {
    $redirect = 'paid_members.php';
} elseif (strpos($referer, 'new_registration') !== false) {
    $redirect = 'new_registration.php';
} elseif (strpos($referer, 'active_members') !== false) {
    $redirect = 'active_members.php';
}

if ($ok) {
    header("Location: " . $redirect . "?deleted=1");
} else {
    // Show a simple error page rather than a blank screen
    header("Content-Type: text/html; charset=utf-8");
    echo "<p style='color:red;font-family:sans-serif;padding:20px;'>
            <strong>Delete failed:</strong> " . htmlspecialchars($err) . "
            <br><a href='" . $redirect . "'>← Go back</a>
          </p>";
}
exit;
