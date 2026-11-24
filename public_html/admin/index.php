<?php
require "../config/init.php";

if (isset($_SESSION['admin_id'])) {
    header("Location: " . BASE_URL . "/admin/dashboard");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $res = mysqli_query(
        $conn,
        "SELECT * FROM admins 
         WHERE username='$username' 
         AND status='ACTIVE' 
         LIMIT 1"
    );

    if ($res && mysqli_num_rows($res) === 1) {

        $admin = mysqli_fetch_assoc($res);

        if (password_verify($password, $admin['password'])) {

            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_role'] = $admin['role'];

            mysqli_query(
                $conn,
                "UPDATE admins 
                 SET last_login = NOW() 
                 WHERE id = {$admin['id']}"
            );

            header("Location: " . BASE_URL . "/admin/dashboard");
            exit;
        }
    }

    $error = "Invalid credentials or account blocked";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sri Kumaran Mana Maalai – Admin Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
:root {
    --bg: #f9fafb;
    --card-bg: #ffffff;
    --border: #e5e7eb;
    --text-primary: #111827;
    --text-secondary: #6b7280;
    --primary: #3b82f6;
    --primary-light: #dbeafe;
    --danger-bg: #fee2e2;
    --danger-text: #b91c1c;
    --radius: 8px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

body {
    background: var(--bg);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-primary);
}

/* LOGIN CARD */
.login-card {
    width: 100%;
    max-width: 420px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 28px;
}

/* BRAND */
.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 22px;
}

.brand-logo {
    width: 38px;
    height: 38px;
    background: var(--primary);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
}

.brand-text h1 {
    font-size: 17px;
    font-weight: 600;
}

.brand-text p {
    font-size: 12px;
    color: var(--text-secondary);
}

/* TITLE */
.login-title {
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 14px;
}

/* ERROR */
.error {
    background: var(--danger-bg);
    color: var(--danger-text);
    padding: 10px;
    border-radius: 6px;
    font-size: 13px;
    margin-bottom: 14px;
    text-align: center;
}

/* FORM */
.form-group {
    margin-bottom: 14px;
}

.input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 14px;
}

.input:focus {
    outline: none;
    border-color: var(--primary);
}

.btn {
    width: 100%;
    padding: 10px;
    background: var(--primary);
    color: #fff;
    border: 1px solid var(--primary);
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

.btn:hover {
    opacity: 0.95;
}

/* FOOTER */
.footer {
    margin-top: 18px;
    font-size: 12px;
    color: var(--text-secondary);
    text-align: center;
}

/* MOBILE */
@media (max-width: 480px) {
    .login-card {
        margin: 0 16px;
        padding: 22px;
    }
}
</style>
</head>

<body>

<div class="login-card">

    <!-- BRAND -->
    <div class="brand">
        <div class="brand-logo">SK</div>
        <div class="brand-text">
            <h1>Sri Kumaran</h1>
            <p>Admin Panel</p>
        </div>
    </div>

    <div class="login-title">Admin Login</div>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <input type="text" name="username" class="input" placeholder="Username" required>
        </div>

        <div class="form-group">
            <input type="password" name="password" class="input" placeholder="Password" required>
        </div>

        <button type="submit" class="btn">Sign In</button>
    </form>

    <div class="footer">
        Sri Kumaran Mana Maalai – Administration
    </div>

</div>

</body>
</html>
