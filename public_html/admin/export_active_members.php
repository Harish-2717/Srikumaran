<?php
require "../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

/* ================= FILTERS ================= */
$search = trim($_GET['q'] ?? '');
$phone  = trim($_GET['phone'] ?? '');
$community = (int)($_GET['community'] ?? 0);
$gender    = (int)($_GET['gender'] ?? 0);

$where = "(urs.status = 'active' OR up.approval_status = 'approved' OR (up.payment_method IS NOT NULL AND up.payment_method != 'not_paid'))";

if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND u.name LIKE '%$s%'";
}
if ($phone) {
    $p = mysqli_real_escape_string($conn, $phone);
    $where .= " AND u.mobile LIKE '%$p%'";
}
if ($community) $where .= " AND u.community_id = $community";
if ($gender) $where .= " AND u.gender_id = $gender";

/* ================= DATA ================= */
$sql = "
    SELECT DISTINCT
        u.id,
        u.name,
        u.username,
        u.mobile,
        u.email,
        mc.community_name,
        mg.gender_name,
        urs.status,
        COALESCE(up.created_at, urs.updated_at) AS start_date
    FROM users u
    LEFT JOIN user_registration_status urs ON urs.user_id = u.id
    LEFT JOIN user_payments up ON up.user_id = u.id
    LEFT JOIN master_community mc ON mc.id = u.community_id
    LEFT JOIN master_gender mg ON mg.id = u.gender_id
    WHERE $where
      AND urs.status = 'active'
    ORDER BY start_date DESC
";

$rows = mysqli_query($conn, $sql);

/* ================= CSV OUTPUT ================= */
$filename = "active_members_" . date('Y-m-d_H-i-s') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Add BOM for Excel compatibility with UTF-8
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Headers
fputcsv($output, [
    'ID',
    'Name',
    'Username',
    'Gender',
    'Community',
    'Mobile',
    'Email',
    'Status',
    'Start Date'
]);

// Data
while ($r = mysqli_fetch_assoc($rows)) {
    fputcsv($output, [
        $r['id'],
        $r['name'],
        $r['username'],
        $r['gender_name'],
        $r['community_name'],
        $r['mobile'],
        $r['email'],
        $r['status'],
        $r['start_date']
    ]);
}

fclose($output);
exit;
