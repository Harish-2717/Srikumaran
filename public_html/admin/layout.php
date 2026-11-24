<?php
if (!isset($page_title)) $page_title = "Admin Panel";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $page_title ?> - Sri Kumaran</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- BOOTSTRAP CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- FONT AWESOME -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --sidebar-width: 240px;
    --sidebar-bg: #ffffff;
    --content-bg: #f9fafb;
    --border-color: #e5e7eb;
    --text-primary: #111827;
    --text-secondary: #6b7280;
    --primary: #3b82f6;
    --primary-light: #dbeafe;
    --danger: #ef4444;
}

/* RESET */
body {
    margin: 0;
    background-color: var(--content-bg);
    color: var(--text-primary);
    font-size: 14px;
}

/* ================= SIDEBAR ================= */
.sidebar {
    width: var(--sidebar-width);
    background: var(--sidebar-bg);
    border-right: 1px solid var(--border-color);
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    overflow-y: auto;
    z-index: 1200;
    transition: transform .3s ease;
}

.sidebar-top {
    height: 60px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    padding: 0 16px;
    gap: 12px;
}

.sidebar-logo {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--primary), #8b5cf6);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
}

.sidebar-brand h2 {
    font-size: 16px;
    margin: 0;
}

.sidebar-brand p {
    font-size: 11px;
    color: var(--text-secondary);
    margin: 0;
}

/* NAV */
.sidebar-nav {
    padding: 16px 12px;
}

.sidebar-header {
    font-size: 11px;
    text-transform: uppercase;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: 6px;
}

.nav-link:hover {
    background: var(--primary-light);
    color: var(--primary);
}

.nav-link.active {
    background: var(--primary);
    color: white;
}

/* ================= TOPBAR ================= */
.topbar {
    height: 60px;
    background: white;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    position: fixed;
    top: 0;
    left: var(--sidebar-width);
    right: 0;
    z-index: 1100;
}

.page-title {
    font-weight: 600;
    font-size: 16px;
}

/* ================= CONTENT ================= */
.content {
    margin-left: var(--sidebar-width);
    padding: 90px 24px 24px;
    min-height: 100vh;
}

/* ================= OVERLAY ================= */
.sidebar-overlay {
    display: none;
}

/* ================= MOBILE ================= */
@media (max-width: 768px) {

    .sidebar {
        transform: translateX(-100%);
        box-shadow: 4px 0 15px rgba(0,0,0,0.15);
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .topbar {
        left: 0;
    }

    .content {
        margin-left: 0;
        padding-top: 90px;
    }

    .sidebar-overlay.active {
        display: block;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.35);
        z-index: 1000;
    }
}
</style>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
    document.querySelector('.sidebar-overlay').classList.toggle('active');
}
</script>

</head>
<body>

<!-- OVERLAY -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-top">
        <div class="sidebar-logo">SK</div>
        <div class="sidebar-brand">
            <h2>Sri Kumaran</h2>
            <p>Admin Panel</p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-header">Main Menu</div>

        <a href="<?= BASE_URL ?>/admin/dashboard" class="nav-link <?= ($active=='dashboard')?'active':'' ?>">
            <i class="fas fa-chart-bar"></i> Dashboard
        </a>

        <a href="<?= BASE_URL ?>/admin/active_members" class="nav-link <?= ($active=='active_members')?'active':'' ?>">
            <i class="fas fa-check-circle"></i> Active Members
        </a>

        <a href="<?= BASE_URL ?>/admin/new_registration" class="nav-link <?= ($active=='new_registration')?'active':'' ?>">
            <i class="fas fa-user-plus"></i> New Registrations
        </a>

        <a href="<?= BASE_URL ?>/admin/paid_members" class="nav-link <?= ($active=='paid_members')?'active':'' ?>">
            <i class="fas fa-credit-card"></i> Paid Members
        </a>

        <a href="<?= BASE_URL ?>/admin/inactive_members" class="nav-link <?= ($active=='unpaid_members')?'active':'' ?>">
            <i class="fas fa-times-circle"></i> UnPaid Members
        </a>

        <a href="<?= BASE_URL ?>/admin/add_new_member" class="nav-link <?= ($active=='add_new_member')?'active':'' ?>">
            <i class="fas fa-user-edit"></i> Add New Member
        </a>
    </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
    <button class="btn btn-light d-md-none" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="page-title"><?= $page_title ?></div>

    <div class="d-flex align-items-center gap-2">
        <form method="get" action="<?= BASE_URL ?>/admin/view_user" class="d-none d-md-flex gap-2">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Mobile or Username" autocomplete="off" spellcheck="false">
            <button class="btn btn-sm btn-primary">Search</button>
        </form>

        <a href="<?= BASE_URL ?>/admin/logout" class="btn btn-sm btn-outline-danger">Logout</a>
    </div>
</header>

<!-- CONTENT -->
<main class="content">
    <?= $content ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
