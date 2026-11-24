<?php
require "config/init.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Matrimony</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .dashboard-container { max-width: 1200px; margin: 40px auto; }
        .welcome-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .welcome-title { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .welcome-subtitle { font-size: 16px; opacity: 0.9; }
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .action-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); text-align: center; transition: transform 0.2s; }
        .action-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.12); }
        .action-icon { font-size: 32px; margin-bottom: 10px; }
        .action-title { font-weight: 600; color: #333; margin-bottom: 10px; }
    </style>
</head>
<body>
    <?php require "navbar.php"; ?>
    
    <div class="dashboard-container">
        <div class="welcome-card">
            <div class="welcome-title">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>! 👋</div>
            <div class="welcome-subtitle">Manage your matrimony profile and search for matches</div>
        </div>
        
        <div class="action-grid">
            <div class="action-card">
                <div class="action-icon">👤</div>
                <div class="action-title">My Profile</div>
                <a href="<?= BASE_URL ?>/profile" class="btn btn-sm btn-outline-primary">View Profile</a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">🔍</div>
                <div class="action-title">Browse Matches</div>
                <a href="<?= BASE_URL ?>/matches" class="btn btn-sm btn-outline-primary">Search</a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">❤️</div>
                <div class="action-title">My Favorites</div>
                <a href="<?= BASE_URL ?>/favorites" class="btn btn-sm btn-outline-primary">View</a>
            </div>
            
            <div class="action-card">
                <div class="action-icon">⚙️</div>
                <div class="action-title">Settings</div>
                <a href="<?= BASE_URL ?>/settings" class="btn btn-sm btn-outline-primary">Manage</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
