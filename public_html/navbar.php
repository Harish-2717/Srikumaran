<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BOOTSTRAP_LOADED')) {
    define('BOOTSTRAP_LOADED', true);
    ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php
}
?>

<style>
/* ================= NAVBAR SIZE FIX ================= */
.navbar .nav-link {
    padding: 0.65rem 1.1rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    color: #212529 !important;
}

/* Login button look */
.nav-btn-outline {
    border: 1px solid #6c757d;
    border-radius: 6px;
    color: #6c757d !important;
}

.nav-btn-outline:hover {
    background: #f8f9fa;
}

/* Register button look */
.nav-btn-primary {
    background: #0d6efd;
    color: #fff !important;
    border-radius: 6px;
}

.nav-btn-primary:hover {
    background: #0b5ed7;
}
</style>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top">
    <div class="container-fluid px-3 px-md-4">

        <!-- LOGO -->
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/">
            <img src="<?= BASE_URL ?>/logo.png" style="height:75px;" alt="Sri Kumaran Mana Maalai">
        </a>

        <!-- MOBILE TOGGLER -->
        <button class="navbar-toggler d-lg-none" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- ================= DESKTOP MENU ================= -->
        <div class="collapse navbar-collapse d-none d-lg-flex">
            <ul class="navbar-nav ms-auto align-items-center">

                <?php if (isset($_SESSION['user_id'])): ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/profile">
                            <i class="fas fa-user me-1"></i>Profile
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/matches">
                            <i class="fas fa-heart me-1"></i>Matches
                        </a>
                    </li>

                    <li class="nav-item ms-3">
                        <a class="nav-link nav-btn-outline" href="<?= BASE_URL ?>/logout">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/about">
                            <i class="fas fa-info-circle me-1"></i>About
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/gallery">
                            <i class="fas fa-images me-1"></i>Gallery
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/contact">
                            <i class="fas fa-envelope me-1"></i>Contact
                        </a>
                    </li>

                    <li class="nav-item ms-3">
                        <a class="nav-link nav-btn-outline" href="<?= BASE_URL ?>/login">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>

                    <li class="nav-item ms-2">
                        <a class="nav-link nav-btn-primary" href="<?= BASE_URL ?>/register">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>

                <?php endif; ?>

            </ul>
        </div>

        <!-- ================= MOBILE MENU (UNCHANGED) ================= -->
        <div class="offcanvas offcanvas-end d-lg-none w-75" tabindex="-1" id="mobileMenu">
            <div class="offcanvas-header border-bottom">
                <h5 class="mb-0">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>

            <div class="offcanvas-body p-0">
                <ul class="nav flex-column">

                    <?php if (isset($_SESSION['user_id'])): ?>

                        <li class="nav-item border-bottom">
                            <a class="nav-link py-3 px-4" href="<?= BASE_URL ?>/profile">
                                <i class="fas fa-user me-3"></i>Profile
                            </a>
                        </li>

                        <li class="nav-item border-bottom">
                            <a class="nav-link py-3 px-4" href="<?= BASE_URL ?>/matches">
                                <i class="fas fa-heart me-3"></i>Matches
                            </a>
                        </li>

                        <li class="nav-item px-4 mt-3">
                            <a class="btn btn-danger w-100" href="<?= BASE_URL ?>/logout">
                                Logout
                            </a>
                        </li>

                    <?php else: ?>

                        <li class="nav-item border-bottom">
                            <a class="nav-link py-3 px-4" href="<?= BASE_URL ?>/">Home</a>
                        </li>

                        <li class="nav-item border-bottom">
                            <a class="nav-link py-3 px-4" href="<?= BASE_URL ?>/about">About</a>
                        </li>

                        <li class="nav-item border-bottom">
                            <a class="nav-link py-3 px-4" href="<?= BASE_URL ?>/gallery">Gallery</a>
                        </li>

                        <li class="nav-item border-bottom">
                            <a class="nav-link py-3 px-4" href="<?= BASE_URL ?>/contact">Contact</a>
                        </li>

                        <li class="nav-item px-4 mt-3">
                            <a class="btn btn-outline-secondary w-100" href="<?= BASE_URL ?>/login">Login</a>
                        </li>

                        <li class="nav-item px-4 mt-2">
                            <a class="btn btn-primary w-100" href="<?= BASE_URL ?>/register">Register</a>
                        </li>

                    <?php endif; ?>

                </ul>
            </div>
        </div>

    </div>
</nav>

<script>
document.addEventListener('hidden.bs.offcanvas', function () {
    document.body.style.overflow = '';
});
</script>
