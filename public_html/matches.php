<?php
require "config/init.php";

// Matching profiles page - for logged-in users to view compatible matches
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// First, fetch the current user's details to use for matching
$current_user = null;
$current_user_sql = "
SELECT 
    u.*,
    urs.status AS registration_status,
    mg.gender_name,
    mc.community_name,
    msc.sub_community_name
FROM users u
LEFT JOIN user_registration_status urs ON urs.user_id = u.id
LEFT JOIN master_gender mg ON mg.id = u.gender_id
LEFT JOIN master_community mc ON mc.id = u.community_id
LEFT JOIN master_sub_community msc ON msc.id = u.sub_community_id
WHERE u.id = $user_id
LIMIT 1
";

$current_user_res = mysqli_query($conn, $current_user_sql);
$current_user = mysqli_fetch_assoc($current_user_res);

if (!$current_user) {
    die("User not found");
}

// Determine opposite gender for matching
$opposite_gender_id = null;
if ($current_user['gender_id'] == 1) { // Male
    $opposite_gender_id = 2; // Female
} elseif ($current_user['gender_id'] == 2) { // Female
    $opposite_gender_id = 1; // Male
}

// Build the matching query with filters
$where_conditions = ["u.id != $user_id"];
$params = [];

// Exclude already married users
$where_conditions[] = "urs.status IN ('active', 'paid')";

// Only show profiles with opposite gender
if ($opposite_gender_id) {
    $where_conditions[] = "u.gender_id = $opposite_gender_id";
}

// Filter by age range (optional, can be adjusted)
$min_age = 18;
$max_age = 50;
if ($current_user['dob']) {
    $current_age = (new DateTime($current_user['dob']))->diff(new DateTime())->y;
    $min_age = max(18, $current_age - 5); // 5 years younger
    $max_age = min(50, $current_age + 5); // 5 years older
    
    $min_dob = date('Y-m-d', strtotime("-$max_age years"));
    $max_dob = date('Y-m-d', strtotime("-$min_age years"));
    $where_conditions[] = "(u.dob IS NOT NULL AND u.dob BETWEEN '$min_dob' AND '$max_dob')";
}

// Optional: Filter by community (can comment out for wider matches)
if ($current_user['community_id']) {
    $where_conditions[] = "(u.community_id = " . (int)$current_user['community_id'] . " OR u.community_id IS NULL)";
}

// Check if user has requested filters from form
$filters = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Age range filter
    if (!empty($_GET['min_age'])) {
        $filters['min_age'] = (int)$_GET['min_age'];
        $min_age = $filters['min_age'];
        $max_dob = date('Y-m-d', strtotime("-$min_age years"));
        $where_conditions[] = "(u.dob IS NOT NULL AND u.dob <= '$max_dob')";
    }
    
    if (!empty($_GET['max_age'])) {
        $filters['max_age'] = (int)$_GET['max_age'];
        $max_age = $filters['max_age'];
        $min_dob = date('Y-m-d', strtotime("-$max_age years"));
        $where_conditions[] = "(u.dob IS NOT NULL AND u.dob >= '$min_dob')";
    }
    
    // Community filter
    if (!empty($_GET['community_id'])) {
        $filters['community_id'] = (int)$_GET['community_id'];
        $where_conditions[] = "u.community_id = " . $filters['community_id'];
    }
    
    // Sub-community filter
    if (!empty($_GET['sub_community_id'])) {
        $filters['sub_community_id'] = (int)$_GET['sub_community_id'];
        $where_conditions[] = "u.sub_community_id = " . $filters['sub_community_id'];
    }
    
    // Qualification filter
    if (!empty($_GET['qualification_id'])) {
        $filters['qualification_id'] = (int)$_GET['qualification_id'];
        $where_conditions[] = "u.qualification_id = " . $filters['qualification_id'];
    }
    
    // City filter
    if (!empty($_GET['city_id'])) {
        $filters['city_id'] = (int)$_GET['city_id'];
        $where_conditions[] = "u.city_id = " . $filters['city_id'];
    }
    
    // Marital status filter
    if (!empty($_GET['marital_status'])) {
        $filters['marital_status'] = mysqli_real_escape_string($conn, $_GET['marital_status']);
        $where_conditions[] = "u.marital_status = '" . $filters['marital_status'] . "'";
    }
    
    // Raasi/Star filter
    if (!empty($_GET['raasi_id'])) {
        $filters['raasi_id'] = (int)$_GET['raasi_id'];
        $where_conditions[] = "u.raasi_id = " . $filters['raasi_id'];
    }
    
    if (!empty($_GET['star_id'])) {
        $filters['star_id'] = (int)$_GET['star_id'];
        $where_conditions[] = "u.star_id = " . $filters['star_id'];
    }
}

// Get total count for pagination
$count_sql = "
SELECT COUNT(DISTINCT u.id) as total 
FROM users u
LEFT JOIN user_registration_status urs ON urs.user_id = u.id
WHERE " . implode(' AND ', $where_conditions);

$count_res = mysqli_query($conn, $count_sql);
$total_count = mysqli_fetch_assoc($count_res)['total'];

// Pagination
$per_page = 12;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_count / $per_page);

// Fetch matching profiles with pagination
$sql = "
SELECT DISTINCT
    u.id,
    u.name,
    u.username,
    u.profile_photo,
    u.dob,
    u.mobile,
    u.email,
    u.monthly_income,
    u.marital_status,
    u.address,
    mg.gender_name,
    mc.community_name,
    msc.sub_community_name,
    mq.qualification_name,
    mct.city_name,
    mr.raasi_name,
    ms.star_name,
    urs.status AS registration_status,
    DATEDIFF(CURDATE(), u.dob)/365.25 as age_years
FROM users u
LEFT JOIN user_registration_status urs ON urs.user_id = u.id
LEFT JOIN master_gender mg ON mg.id = u.gender_id
LEFT JOIN master_community mc ON mc.id = u.community_id
LEFT JOIN master_sub_community msc ON msc.id = u.sub_community_id
LEFT JOIN master_qualification mq ON mq.id = u.qualification_id
LEFT JOIN master_city mct ON mct.id = u.city_id
LEFT JOIN master_raasi mr ON mr.id = u.raasi_id
LEFT JOIN master_star ms ON ms.id = u.star_id
WHERE " . implode(' AND ', $where_conditions) . "
ORDER BY u.created_at DESC
LIMIT $per_page OFFSET $offset
";

$result = mysqli_query($conn, $sql);
$profiles = [];
while ($row = mysqli_fetch_assoc($result)) {
    $profiles[] = $row;
}

// Fetch filter options
$filter_options = [
    'community' => [],
    'sub_community' => [],
    'qualification' => [],
    'city' => [],
    'raasi' => [],
    'star' => [],
    'marital_status' => [
        'unmarried' => 'Unmarried',
        'divorced' => 'Divorced',
        'widowed' => 'Widowed',
        'separated' => 'Separated',
        'annulled' => 'Annulled'
    ]
];

// Community options
$com_res = mysqli_query($conn, "SELECT id, community_name FROM master_community ORDER BY community_name");
while ($row = mysqli_fetch_assoc($com_res)) {
    $filter_options['community'][$row['id']] = $row['community_name'];
}

// Sub-community options
$sub_res = mysqli_query($conn, "SELECT id, sub_community_name FROM master_sub_community ORDER BY sub_community_name");
while ($row = mysqli_fetch_assoc($sub_res)) {
    $filter_options['sub_community'][$row['id']] = $row['sub_community_name'];
}

// Qualification options
$qual_res = mysqli_query($conn, "SELECT id, qualification_name FROM master_qualification ORDER BY qualification_name");
while ($row = mysqli_fetch_assoc($qual_res)) {
    $filter_options['qualification'][$row['id']] = $row['qualification_name'];
}

// City options
$city_res = mysqli_query($conn, "SELECT id, city_name FROM master_city ORDER BY city_name");
while ($row = mysqli_fetch_assoc($city_res)) {
    $filter_options['city'][$row['id']] = $row['city_name'];
}

// Raasi options
$raasi_res = mysqli_query($conn, "SELECT id, raasi_name FROM master_raasi ORDER BY raasi_name");
while ($row = mysqli_fetch_assoc($raasi_res)) {
    $filter_options['raasi'][$row['id']] = $row['raasi_name'];
}

// Star options
$star_res = mysqli_query($conn, "SELECT id, star_name FROM master_star ORDER BY star_name");
while ($row = mysqli_fetch_assoc($star_res)) {
    $filter_options['star'][$row['id']] = $row['star_name'];
}

// Calculate age range for display
$default_min_age = 18;
$default_max_age = 50;
if ($current_user['dob']) {
    $current_age = (new DateTime($current_user['dob']))->diff(new DateTime())->y;
    $default_min_age = max(18, $current_age - 5);
    $default_max_age = min(50, $current_age + 5);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matching Profiles - Matrimony</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-dark: #3730a3;
            --secondary-color: #f9fafb;
            --text-color: #333;
            --text-light: #6b7280;
            --border-color: #e5e7eb;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            color: var(--text-color);
            background-color: #f8f9fa;
        }
        
        .container-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .filter-bar {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary-dark);
            white-space: nowrap;
            margin: 0;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        .filter-label {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 500;
            min-width: fit-content;
        }
        
        .filter-select {
            padding: 6px 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 13px;
            min-width: 140px;
            background-color: white;
            outline: none;
        }
        
        .filter-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
        }
        
        .filter-input {
            padding: 6px 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 13px;
            width: 70px;
            text-align: center;
        }
        
        .filter-actions {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }
        
        .filter-btn {
            padding: 6px 16px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .filter-btn-primary {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .filter-btn:hover {
            opacity: 0.9;
        }
        
        .results-count {
            background-color: white;
            border-radius: 8px;
            padding: 12px 20px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .results-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-color);
            margin: 0;
        }
        
        .results-total {
            font-size: 14px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        .profiles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }
        
        .profile-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 520px;
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
        }
        
        /* Image section - 75% - Clean image only */
        .profile-card-header {
            position: relative;
            height: 390px;
            overflow: hidden;
        }
        
        .profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .profile-card:hover .profile-photo {
            transform: scale(1.05);
        }
        
        .photo-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
            text-transform: uppercase;
        }
        
        /* Top right age indicator */
        .profile-age {
            position: absolute;
            top: 18px;
            right: 18px;
            background-color: rgba(79, 70, 229, 0.9);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            z-index: 2;
        }
        
        /* Bottom data area - 25% - All data goes here */
        .profile-card-footer {
            padding: 1px;
            height: 150px;
            display: flex;
            flex-direction: column;
            background-color: white;
            overflow: hidden;
        }
        
        /* Row 1: Name and ID in same row */
        .profile-name-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background-color: var(--primary-dark);
            color: white;
        }
        
        .profile-name-footer {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }
        
        .profile-id-footer {
            font-size: 11px;
            font-weight: 700;
            color: #c7d2fe;
            letter-spacing: 1px;
            margin-left: 10px;
            white-space: nowrap;
        }
        
        /* Details grid - 5 rows with 2 columns each */
        .profile-details-grid-footer {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(5, 1fr);
            gap: 6px;
            flex-grow: 1;
            padding: 8px;
            overflow: hidden;
        }
        
        .profile-detail-item-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 11px;
            line-height: 1.3;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-color);
            letter-spacing: 0.2px;
            padding: 5px;
            background-color: #f8f9fa;
            border-radius: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Row 2: Qualification and Salary */
        .profile-detail-item-footer:nth-child(1) {
            background-color: #eef2ff;
            color: var(--primary-dark);
            font-weight: 800;
        }
        
        .profile-detail-item-footer:nth-child(2) {
            background-color: #fef3c7;
            color: #92400e;
            font-weight: 800;
        }
        
        /* Row 3: Phone and City */
        .profile-detail-item-footer:nth-child(3) {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .profile-detail-item-footer:nth-child(4) {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        /* Row 4: Caste and Sub-caste */
        .profile-detail-item-footer:nth-child(5) {
            background-color: #f3f4f6;
        }
        
        .profile-detail-item-footer:nth-child(6) {
            background-color: #fce7f3;
            color: #831843;
        }
        
        /* Row 5: Dhosam Type and Dhosam */
        .profile-detail-item-footer:nth-child(7) {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .profile-detail-item-footer:nth-child(8) {
            background-color: #dcfce7;
            color: #166534;
        }
        
        /* Row 6: Raasi and Star (full width) */
        .profile-detail-item-footer:nth-child(9) {
            grid-column: span 2;
            background-color: var(--primary-color);
            color: white;
            font-weight: 800;
            font-size: 12px;
        }
        
        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }
        
        .pagination {
            display: flex;
            gap: 8px;
        }
        
        .page-link {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .page-link:hover {
            background-color: var(--secondary-color);
            border-color: var(--primary-color);
        }
        
        .page-link.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .page-link.disabled {
            color: var(--text-light);
            pointer-events: none;
            opacity: 0.6;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }
        
        .empty-state i {
            font-size: 64px;
            color: var(--border-color);
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 22px;
            color: var(--text-light);
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto 20px;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .filter-bar {
                gap: 12px;
            }
            
            .filter-select {
                min-width: 120px;
            }
            
            .profiles-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 992px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .filter-group {
                width: 100%;
                justify-content: space-between;
            }
            
            .filter-actions {
                margin-left: 0;
                justify-content: flex-end;
            }
            
            .profiles-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
            
            .profile-card {
                height: 500px;
            }
            
            .profile-card-header {
                height: 375px;
            }
            
            .profile-card-footer {
                height: 125px;
            }
            
            .profile-name-footer {
                font-size: 14px;
            }
            
            .profile-id-footer {
                font-size: 10px;
            }
            
            .profile-detail-item-footer {
                font-size: 10px;
                padding: 4px;
            }
        }
        
        @media (max-width: 768px) {
            .container-main {
                padding: 15px;
            }
            
            .profile-card {
                height: 480px;
            }
            
            .profile-card-header {
                height: 360px;
            }
            
            .profile-card-footer {
                height: 120px;
            }
            
            .profiles-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-name-footer {
                font-size: 13px;
            }
            
            .profile-id-footer {
                font-size: 10px;
            }
            
            .profile-details-grid-footer {
                gap: 5px;
            }
            
            .profile-detail-item-footer {
                font-size: 9px;
            }
        }
        
        @media (max-width: 576px) {
            .filter-group {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .filter-select {
                min-width: 100%;
            }
            
            .filter-input {
                width: 100%;
            }
            
            .results-count {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .profile-card {
                height: 460px;
            }
            
            .profile-card-header {
                height: 345px;
            }
            
            .profile-card-footer {
                height: 115px;
            }
            
            .profile-name-footer {
                font-size: 12px;
            }
            
            .profile-id-footer {
                font-size: 9px;
            }
            
            .profile-detail-item-footer {
                font-size: 8px;
                padding: 3px;
            }
        }
    </style>
</head>
<body>
    <?php require "navbar.php"; ?>
    
    <div class="container-main">
        <form method="GET" action="" id="filterForm" class="filter-bar">
            
            <div class="filter-group">
                <span class="filter-label">Age:</span>
                <input type="number" class="filter-input" name="min_age" min="18" max="70" 
                       value="<?php echo htmlspecialchars($filters['min_age'] ?? $default_min_age); ?>" placeholder="Min">
                <span>-</span>
                <input type="number" class="filter-input" name="max_age" min="18" max="70" 
                       value="<?php echo htmlspecialchars($filters['max_age'] ?? $default_max_age); ?>" placeholder="Max">
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Community:</span>
                <select class="filter-select" name="community_id">
                    <option value="">All</option>
                    <?php 
                    if (isset($filter_options['community'])) {
                        foreach ($filter_options['community'] as $id => $name) {
                            $selected = (isset($filters['community_id']) && $filters['community_id'] == $id) ? 'selected' : '';
                            echo '<option value="' . $id . '" ' . $selected . '>' . htmlspecialchars($name) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">City:</span>
                <select class="filter-select" name="city_id">
                    <option value="">All</option>
                    <?php 
                    if (isset($filter_options['city'])) {
                        foreach ($filter_options['city'] as $id => $name) {
                            $selected = (isset($filters['city_id']) && $filters['city_id'] == $id) ? 'selected' : '';
                            echo '<option value="' . $id . '" ' . $selected . '>' . htmlspecialchars($name) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Qualification:</span>
                <select class="filter-select" name="qualification_id">
                    <option value="">All</option>
                    <?php 
                    if (isset($filter_options['qualification'])) {
                        foreach ($filter_options['qualification'] as $id => $name) {
                            $selected = (isset($filters['qualification_id']) && $filters['qualification_id'] == $id) ? 'selected' : '';
                            echo '<option value="' . $id . '" ' . $selected . '>' . htmlspecialchars($name) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Status:</span>
                <select class="filter-select" name="marital_status">
                    <option value="">All</option>
                    <?php 
                    if (isset($filter_options['marital_status'])) {
                        foreach ($filter_options['marital_status'] as $value => $label) {
                            $selected = (isset($filters['marital_status']) && $filters['marital_status'] == $value) ? 'selected' : '';
                            echo '<option value="' . $value . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="reset" class="filter-btn" onclick="resetFilters()">
                    <i class="fas fa-redo"></i> Reset
                </button>
                <button type="submit" class="filter-btn filter-btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>
        
        <?php if (empty($profiles)): ?>
        <div class="empty-state">
            <i class="fas fa-users-slash"></i>
            <h3>No matching profiles found</h3>
            <p>Try adjusting your filters to see more results.</p>
        </div>
        <?php else: ?>
        <div class="profiles-grid">
            <?php 
            foreach ($profiles as $profile) {
                $age = $profile['dob'] ? floor((strtotime('today') - strtotime($profile['dob'])) / 31556926) : 'N/A';
                
                // Format data for display
                $name = htmlspecialchars(strtoupper($profile['name'] ?? 'N/A'));
                $user_id_display = htmlspecialchars(strtoupper($profile['username'] ?? 'N/A'));
                $qualification = htmlspecialchars(strtoupper($profile['qualification_name'] ?? 'N/A'));
                $community = htmlspecialchars(strtoupper($profile['community_name'] ?? 'N/A'));
                $sub_community = htmlspecialchars(strtoupper($profile['sub_community_name'] ?? 'N/A'));
                $city = htmlspecialchars(strtoupper($profile['city_name'] ?? 'N/A'));
                $income = $profile['monthly_income'] ? 'Rs ' . number_format($profile['monthly_income']) : 'N/A';
                $raasi = htmlspecialchars(strtoupper($profile['raasi_name'] ?? 'N/A'));
                $star = htmlspecialchars(strtoupper($profile['star_name'] ?? 'N/A'));
                $phone = "+91 87540 32742 / 0422 2666 505";
                // Assuming you have dhosa_type and dhosa columns in users table
                $dhosa_type = isset($profile['dhosa_type']) ? htmlspecialchars(strtoupper($profile['dhosa_type'])) : 'N/A';
                $dhosa = isset($profile['dhosa']) ? htmlspecialchars(strtoupper($profile['dhosa'])) : 'N/A';
            ?>
            <div class="profile-card" onclick="viewProfile(<?php echo $profile['id']; ?>)">
                <div class="profile-card-header">
                    <?php if (!empty($profile['profile_photo'])): ?>
                    <img src="<?php echo BASE_URL . '/' . htmlspecialchars($profile['profile_photo']); ?>" 
                         alt="<?php echo $name; ?>" 
                         class="profile-photo"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iIzYzNjZmMSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0iY2VudHJhbCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjI0IiBmaWxsPSJ3aGl0ZSI+Tk8gUEhPVE88L3RleHQ+PC9zdmc+'">
                    <?php else: ?>
                    <div class="photo-placeholder">
                        NO PHOTO
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($age !== 'N/A'): ?>
                    <div class="profile-age"><?php echo $age; ?> YRS</div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-card-footer">
                    <!-- Row 1: Name and ID in same row -->
                    <div class="profile-name-row">
                        <div class="profile-name-footer"><?php echo $name; ?></div>
                        <div class="profile-id-footer">ID: <?php echo $user_id_display; ?></div>
                    </div>
                    
                    <!-- Details grid: 6 rows -->
                    <div class="profile-details-grid-footer">
                        <!-- Row 2: Qualification and Salary -->
                        <div class="profile-detail-item-footer"><?php echo $qualification; ?></div>
                        <div class="profile-detail-item-footer"><?php echo $income; ?></div>
                        
                        <!-- Row 3: Phone and City -->
                        <div class="profile-detail-item-footer"><?php echo $phone; ?></div>
                        <div class="profile-detail-item-footer"><?php echo $city; ?></div>
                        
                        <!-- Row 4: Caste and Sub-caste -->
                        <div class="profile-detail-item-footer"><?php echo $community; ?></div>
                        <div class="profile-detail-item-footer"><?php echo $sub_community; ?></div>
                        
                        <!-- Row 5: Dhosam Type and Dhosam -->
                        <div class="profile-detail-item-footer"><?php echo $dhosa_type; ?></div>
                        <div class="profile-detail-item-footer"><?php echo $dhosa; ?></div>
                        
                        <!-- Row 6: Raasi and Star (full width) -->
                        <div class="profile-detail-item-footer"><?php echo $raasi . ' / ' . $star; ?></div>
                    </div>
                </div>
            </div>
            <?php } // end foreach ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i> Prev
                </a>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++): 
                ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function resetFilters() {
        document.getElementById('filterForm').reset();
    }
    
    function sendInterest(event, profileId, profileName) {
        event.stopPropagation(); // Prevent card click
        if (confirm('Send interest request to ' + profileName + '?')) {
            // In a real application, you would make an AJAX request here
            alert('Interest request sent to ' + profileName + '!');
        }
    }
    
    function viewProfile(profileId) {
        window.location.href = 'view_profile.php?id=' + profileId;
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const minAgeInput = document.querySelector('input[name="min_age"]');
        const maxAgeInput = document.querySelector('input[name="max_age"]');
        
        const filterForm = document.getElementById('filterForm');
        filterForm.addEventListener('submit', function(e) {
            const minAge = parseInt(minAgeInput.value) || 0;
            const maxAge = parseInt(maxAgeInput.value) || 0;
            
            if (minAge > maxAge) {
                e.preventDefault();
                alert('Minimum age cannot be greater than maximum age');
                minAgeInput.focus();
                return;
            }
            
            if (minAge < 18 || minAge > 70) {
                e.preventDefault();
                alert('Minimum age must be between 18 and 70');
                minAgeInput.focus();
                return;
            }
            
            if (maxAge < 18 || maxAge > 70) {
                e.preventDefault();
                alert('Maximum age must be between 18 and 70');
                maxAgeInput.focus();
                return;
            }
        });
    });
    </script>
</body>
</html>