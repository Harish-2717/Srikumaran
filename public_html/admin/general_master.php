<?php
require "../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin");
    exit;
}

$page_title = "General Masters";
$page_icon  = "layer-group";
$active     = "masters";

$masters = [
    ["title" => "Country",       "file" => "country"],
    ["title" => "State",         "file" => "state"],
    ["title" => "City",          "file" => "city"],
    ["title" => "Community",     "file" => "community"],
    ["title" => "Sub Community",     "file" => "sub_community"],
    ["title" => "Kulam",         "file" => "kulam"],
    ["title" => "Gothiram",      "file" => "gothiram"],
    ["title" => "Qualification", "file" => "qualification"],
    ["title" => "Dhosam Type",   "file" => "dhosam_type"],
    ["title" => "Dhosam Name",   "file" => "dhosam_name"],
    ["title" => "Raasi",         "file" => "raasi"],
    ["title" => "Star",          "file" => "star"],
    ["title" => "Lagnam",        "file" => "lagnam"]
];

ob_start();
?>

<style>
.master-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 22px;
}

/* BIG CARD */
.master-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 28px 26px;
    min-height: 120px;
    text-decoration: none;
    color: #111827;
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: all .2s ease;
}

.master-card:hover {
    background: #f9fafb;
    box-shadow: 0 10px 28px rgba(0,0,0,.08);
    transform: translateY(-2px);
}

/* TEXT */
.master-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
}

.master-sub {
    font-size: 14px;
    color: #6b7280;
}

/* MOBILE */
@media (max-width: 768px) {
    .master-grid {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
    }

    .master-card {
        padding: 24px;
        min-height: 110px;
    }

    .master-title {
        font-size: 17px;
    }
}
</style>

<div class="master-grid">
    <?php foreach ($masters as $m): ?>
        <a 
            class="master-card"
            href="<?= BASE_URL ?>/admin/generalmaster/<?= $m['file'] ?>"
        >
            <div class="master-title"><?= $m['title'] ?></div>
            <div class="master-sub">Manage <?= $m['title'] ?> Master</div>
        </a>
    <?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
require "layout.php";
