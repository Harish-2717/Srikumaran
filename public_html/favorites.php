<?php
require "config/init.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle add to favorites
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_favorite'])) {
    $favorite_user_id = (int)$_POST['favorite_user_id'];
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    // Check if already in favorites
    $check = mysqli_query($conn, "SELECT id FROM user_favorites WHERE user_id = $user_id AND favorite_user_id = $favorite_user_id");
    
    if (mysqli_num_rows($check) == 0) {
        $sql = "INSERT INTO user_favorites (user_id, favorite_user_id, notes, status) 
                VALUES ($user_id, $favorite_user_id, '$notes', 'active')";
        mysqli_query($conn, $sql);
        $success = "Added to favorites successfully!";
    } else {
        $error = "Already in your favorites!";
    }
}

// Handle remove from favorites
if (isset($_GET['remove'])) {
    $favorite_id = (int)$_GET['remove'];
    $sql = "UPDATE user_favorites SET status = 'removed' WHERE id = $favorite_id AND user_id = $user_id";
    mysqli_query($conn, $sql);
    header("Location: favorites.php?removed=1");
    exit;
}

// Get all favorites with user details
$favorites_sql = "
    SELECT 
        uf.*,
        u.id as user_id,
        u.name,
        u.email,
        u.mobile,
        u.profile_photo,
        u.dob,
        u.qualification_id,
        u.city_id,
        u.monthly_income,
        g.gender_name,
        q.qualification_name,
        c.city_name,
        r.raasi_name,
        s.star_name
    FROM user_favorites uf
    JOIN users u ON uf.favorite_user_id = u.id
    LEFT JOIN master_gender g ON u.gender_id = g.id
    LEFT JOIN master_qualification q ON u.qualification_id = q.id
    LEFT JOIN master_city c ON u.city_id = c.id
    LEFT JOIN master_raasi r ON u.raasi_id = r.id
    LEFT JOIN master_star s ON u.star_id = s.id
    WHERE uf.user_id = $user_id 
    AND uf.status = 'active'
    ORDER BY uf.created_at DESC
";

$favorites_result = mysqli_query($conn, $favorites_sql);
$total_favorites = mysqli_num_rows($favorites_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - Sri Kumaran Mana Maalai</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --success-green: #10B981;
            --error-red: #EF4444;
            --transition: all 0.3s ease;
            --radius: 8px;
            --shadow-sm: 0 2px 8px rgba(10, 31, 68, 0.05);
            --shadow-md: 0 4px 16px rgba(10, 31, 68, 0.08);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 0;
        }

        .favorites-wrapper {
            width: 100%;
            min-height: calc(100vh - 80px);
            padding: 20px;
            box-sizing: border-box;
        }

        .favorites-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header Section */
        .header-section {
            margin-bottom: 30px;
        }

        .header-card {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
        }

        .header-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }

        .stats-badge {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 6px 15px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 2px dashed rgba(10, 31, 68, 0.1);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--accent-blue);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .empty-text {
            color: var(--text-light);
            max-width: 400px;
            margin: 0 auto 25px;
        }

        /* Favorites Grid */
        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .favorite-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(10, 31, 68, 0.05);
            transition: var(--transition);
            overflow: hidden;
            position: relative;
        }

        .favorite-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent-blue);
        }

        .favorite-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            position: relative;
        }

        .favorite-actions {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 10px;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .favorite-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .profile-meta {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Content Section */
        .favorite-content {
            padding: 20px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .detail-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--light-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-blue);
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .detail-label {
            color: var(--text-light);
            min-width: 80px;
        }

        .detail-value {
            color: var(--text-dark);
            font-weight: 500;
        }

        .notes-section {
            background: var(--off-white);
            border-radius: var(--radius);
            padding: 15px;
            margin-top: 20px;
        }

        .notes-label {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .notes-text {
            font-size: 0.9rem;
            color: var(--text-dark);
            line-height: 1.5;
        }

        /* Footer Actions */
        .favorite-footer {
            padding: 15px 20px;
            border-top: 1px solid rgba(10, 31, 68, 0.05);
            display: flex;
            gap: 10px;
        }

        .footer-btn {
            flex: 1;
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-view {
            background: var(--accent-blue);
            color: white;
        }

        .btn-view:hover {
            background: var(--secondary-blue);
        }

        .btn-message {
            background: var(--light-blue);
            color: var(--accent-blue);
            border: 1px solid var(--light-blue);
        }

        .btn-message:hover {
            background: var(--accent-blue);
            color: white;
        }

        /* Filter Section */
        .filter-section {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 25px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            margin-bottom: 0;
        }

        .filter-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: block;
        }

        .filter-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(10, 31, 68, 0.15);
            border-radius: var(--radius);
            font-size: 0.9rem;
            background: var(--off-white);
        }

        .filter-btn {
            background: var(--accent-blue);
            color: white;
            border: none;
            border-radius: var(--radius);
            padding: 10px 20px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn:hover {
            background: var(--secondary-blue);
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .pagination {
            display: flex;
            gap: 8px;
        }

        .page-item {
            list-style: none;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: var(--radius);
            border: 1px solid rgba(10, 31, 68, 0.1);
            background: var(--white);
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .page-link:hover,
        .page-item.active .page-link {
            background: var(--accent-blue);
            color: white;
            border-color: var(--accent-blue);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .favorites-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .favorites-wrapper {
                padding: 15px;
                min-height: calc(100vh - 70px);
            }

            .header-card {
                padding: 25px;
            }

            .header-title {
                font-size: 1.5rem;
            }

            .favorites-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .filter-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .favorite-card {
                margin-bottom: 15px;
            }
        }

        @media (max-width: 576px) {
            .favorites-wrapper {
                padding: 12px;
            }

            .header-card {
                padding: 20px;
            }

            .header-title {
                font-size: 1.3rem;
            }

            .favorite-profile {
                flex-direction: column;
                text-align: center;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .favorite-footer {
                flex-direction: column;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="favorites-wrapper">
        <div class="favorites-container">
            <!-- Header -->
            <div class="header-section fade-in">
                <div class="header-card">
                    <h1 class="header-title">
                        <i class="fas fa-heart"></i>
                        My Favorites
                    </h1>
                    <p class="header-subtitle">
                        Manage and view all your saved profiles in one place
                    </p>
                    <div class="stats-badge">
                        <i class="fas fa-user-friends"></i>
                        <span><?= $total_favorites ?> Saved Profiles</span>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_GET['removed'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    Profile removed from favorites successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filter Section -->
            <div class="filter-section fade-in">
                <form method="GET" action="" class="filter-grid">
                    <div class="filter-group">
                        <label class="filter-label">Gender</label>
                        <select class="filter-control" name="gender">
                            <option value="">All Genders</option>
                            <?php 
                            $genders = mysqli_query($conn, "SELECT * FROM master_gender WHERE status=1");
                            while($g = mysqli_fetch_assoc($genders)): 
                            ?>
                                <option value="<?= $g['id'] ?>" <?= isset($_GET['gender']) && $_GET['gender'] == $g['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['gender_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Qualification</label>
                        <select class="filter-control" name="qualification">
                            <option value="">All Qualifications</option>
                            <?php 
                            $quals = mysqli_query($conn, "SELECT * FROM master_qualification WHERE status=1");
                            while($q = mysqli_fetch_assoc($quals)): 
                            ?>
                                <option value="<?= $q['id'] ?>" <?= isset($_GET['qualification']) && $_GET['qualification'] == $q['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($q['qualification_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">City</label>
                        <select class="filter-control" name="city">
                            <option value="">All Cities</option>
                            <?php 
                            $cities = mysqli_query($conn, "SELECT * FROM master_city WHERE status=1");
                            while($c = mysqli_fetch_assoc($cities)): 
                            ?>
                                <option value="<?= $c['id'] ?>" <?= isset($_GET['city']) && $_GET['city'] == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['city_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <button type="submit" class="filter-btn">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <?php if (isset($_GET['gender']) || isset($_GET['qualification']) || isset($_GET['city'])): ?>
                            <a href="favorites.php" class="filter-btn" style="background: var(--text-light); margin-top: 10px;">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Favorites List -->
            <?php if ($total_favorites > 0): ?>
                <div class="favorites-grid">
                    <?php while ($favorite = mysqli_fetch_assoc($favorites_result)): 
                        // Calculate age from DOB
                        $birthDate = new DateTime($favorite['dob']);
                        $today = new DateTime();
                        $age = $birthDate->diff($today)->y;
                    ?>
                        <div class="favorite-card fade-in">
                            <div class="favorite-header">
                                <div class="favorite-actions">
                                    <button class="action-btn remove-favorite" 
                                            data-id="<?= $favorite['id'] ?>" 
                                            data-name="<?= htmlspecialchars($favorite['name']) ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="action-btn edit-notes" 
                                            data-id="<?= $favorite['id'] ?>"
                                            data-notes="<?= htmlspecialchars($favorite['notes'] ?? '') ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                                
                                <div class="favorite-profile">
                                    <img src="<?= !empty($favorite['profile_photo']) ? BASE_URL . '/' . $favorite['profile_photo'] : 'https://via.placeholder.com/80?text=' . urlencode(substr($favorite['name'], 0, 1)) ?>" 
                                         alt="<?= htmlspecialchars($favorite['name']) ?>" 
                                         class="profile-image">
                                    <div class="profile-info">
                                        <h3 class="profile-name"><?= htmlspecialchars($favorite['name']) ?></h3>
                                        <div class="profile-meta">
                                            <span><?= $age ?> years • <?= htmlspecialchars($favorite['gender_name']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="favorite-content">
                                <div class="details-grid">
                                    <div class="detail-item">
                                        <div class="detail-icon">
                                            <i class="fas fa-graduation-cap"></i>
                                        </div>
                                        <span class="detail-label">Qualification:</span>
                                        <span class="detail-value"><?= htmlspecialchars($favorite['qualification_name'] ?? 'Not specified') ?></span>
                                    </div>

                                    <div class="detail-item">
                                        <div class="detail-icon">
                                            <i class="fas fa-city"></i>
                                        </div>
                                        <span class="detail-label">Location:</span>
                                        <span class="detail-value"><?= htmlspecialchars($favorite['city_name'] ?? 'Not specified') ?></span>
                                    </div>

                                    <div class="detail-item">
                                        <div class="detail-icon">
                                            <i class="fas fa-rupee-sign"></i>
                                        </div>
                                        <span class="detail-label">Income:</span>
                                        <span class="detail-value"><?= !empty($favorite['monthly_income']) ? '₹' . $favorite['monthly_income'] : 'Not specified' ?></span>
                                    </div>

                                    <div class="detail-item">
                                        <div class="detail-icon">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <span class="detail-label">Star:</span>
                                        <span class="detail-value"><?= htmlspecialchars($favorite['star_name'] ?? 'Not specified') ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($favorite['notes'])): ?>
                                    <div class="notes-section">
                                        <div class="notes-label">
                                            <i class="fas fa-sticky-note"></i>
                                            Your Notes:
                                        </div>
                                        <div class="notes-text">
                                            <?= nl2br(htmlspecialchars($favorite['notes'])) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="favorite-footer">
                                <a href="view_profile.php?id=<?= $favorite['user_id'] ?>" class="footer-btn btn-view">
                                    <i class="fas fa-eye"></i> View Profile
                                </a>
                                <button class="footer-btn btn-message send-message" 
                                        data-user-id="<?= $favorite['user_id'] ?>"
                                        data-user-name="<?= htmlspecialchars($favorite['name']) ?>">
                                    <i class="fas fa-comment"></i> Message
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state fade-in">
                    <div class="empty-icon">
                        <i class="fas fa-heart-broken"></i>
                    </div>
                    <h3 class="empty-title">No Favorites Yet</h3>
                    <p class="empty-text">
                        You haven't added any profiles to your favorites yet. Start browsing matches to save your favorite profiles!
                    </p>
                    <a href="matches.php" class="btn btn-primary" style="padding: 10px 30px;">
                        <i class="fas fa-search me-2"></i> Browse Matches
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Remove Confirmation Modal -->
    <div class="modal fade" id="removeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Remove from Favorites</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to remove <span id="removeUserName" class="fw-bold"></span> from your favorites?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmRemove">Yes, Remove</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="notesForm" method="POST">
                    <input type="hidden" name="favorite_id" id="favoriteId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Add or edit notes for this profile:</label>
                            <textarea class="form-control" id="notesText" name="notes" rows="4" 
                                      placeholder="Add your private notes here (only you can see these)..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Notes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="messageForm" method="POST" action="send_message.php">
                    <input type="hidden" name="receiver_id" id="receiverId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">To: <span id="receiverName" class="fw-bold"></span></label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message:</label>
                            <textarea class="form-control" name="message" rows="4" 
                                      placeholder="Type your message here..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Remove favorite confirmation
            let currentFavoriteId = null;
            const removeModal = new bootstrap.Modal(document.getElementById('removeModal'));
            const notesModal = new bootstrap.Modal(document.getElementById('notesModal'));
            const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));

            $('.remove-favorite').on('click', function() {
                const favoriteId = $(this).data('id');
                const userName = $(this).data('name');
                
                currentFavoriteId = favoriteId;
                $('#removeUserName').text(userName);
                removeModal.show();
            });

            $('#confirmRemove').on('click', function() {
                if (currentFavoriteId) {
                    window.location.href = `favorites.php?remove=${currentFavoriteId}`;
                }
            });

            // Edit notes
            $('.edit-notes').on('click', function() {
                const favoriteId = $(this).data('id');
                const notes = $(this).data('notes');
                
                $('#favoriteId').val(favoriteId);
                $('#notesText').val(notes);
                notesModal.show();
            });

            // Handle notes form submission
            $('#notesForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = $(this).serialize();
                
                $.ajax({
                    url: 'update_favorite_notes.php',
                    type: 'POST',
                    data: formData + '&action=update_notes',
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            location.reload();
                        } else {
                            alert(result.message || 'Error updating notes');
                        }
                    },
                    error: function() {
                        alert('Error updating notes. Please try again.');
                    }
                });
            });

            // Send message
            $('.send-message').on('click', function() {
                const userId = $(this).data('user-id');
                const userName = $(this).data('user-name');
                
                $('#receiverId').val(userId);
                $('#receiverName').text(userName);
                messageModal.show();
            });

            // Filter form submission with loading
            $('.filter-btn[type="submit"]').on('click', function(e) {
                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Filtering...');
                
                setTimeout(() => {
                    $btn.prop('disabled', false).html('<i class="fas fa-filter"></i> Filter');
                }, 1000);
            });

            // Add hover effects
            $('.favorite-card').hover(
                function() {
                    $(this).addClass('hover');
                },
                function() {
                    $(this).removeClass('hover');
                }
            );

            // Search functionality
            $('#searchInput').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                $('.favorite-card').each(function() {
                    const cardText = $(this).text().toLowerCase();
                    if (cardText.indexOf(searchTerm) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Sort functionality
            $('#sortSelect').on('change', function() {
                const sortBy = $(this).val();
                // Implement sorting logic here
                // You can reload the page with sort parameter or use AJAX
                if (sortBy) {
                    window.location.href = `favorites.php?sort=${sortBy}`;
                }
            });
        });
    </script>
</body>
</html>