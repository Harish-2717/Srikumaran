<?php
require "config/init.php";

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/profile");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login    = trim($_POST['name']); // can be username OR phone
    $password = $_POST['password'];

    $sql = "
        SELECT 
            u.id,
            u.username,
            u.name,
            u.email,
            u.mobile
        FROM users u
        INNER JOIN user_registration_status urs 
            ON urs.user_id = u.id
        WHERE 
            (u.username = ? OR u.mobile = ?)
            AND u.password_hash = ?
            AND urs.status = 'active'
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $login, $login, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        $_SESSION['user_id']    = $row['id'];
        $_SESSION['user_name']  = $row['name'];
        $_SESSION['username']   = $row['username'];
        $_SESSION['user_email'] = $row['email'];
        $_SESSION['user_mobile']= $row['mobile'];

        header("Location: " . BASE_URL . "/profile");
        exit;

    } else {
        $error = "Invalid credentials or account not active";
    }

    mysqli_stmt_close($stmt);
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sri Kumaran Mana Maalai</title>
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
            --transition: all 0.3s ease;
            --radius: 6px;
            --shadow-sm: 0 2px 8px rgba(10, 31, 68, 0.05);
            --shadow-md: 0 4px 16px rgba(10, 31, 68, 0.08);
            --error-red: #DC3545;
            --success-green: #198754;
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
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--text-dark);
            -webkit-box-shadow: 0 0 0px 1000px var(--off-white) inset !important;
            transition: background-color 5000s ease-in-out 0s;
            background-color: var(--off-white) !important;
        }

        .login-container-wrapper {
            width: 100%;
            height: calc(100vh - 80px);
            padding: 15px;
            margin: 0;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .compact-login-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 0;
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            height: 500px;
            min-height: 500px;
            width: 100%;
            max-width: 1000px;
        }

        .login-left-panel {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            padding: 30px 25px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .login-left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: cover;
            opacity: 0.1;
        }

        .login-logo {
            height: 40px;
            margin-bottom: 20px;
            justify-self: center;
            position: relative;
        }

        .login-left-content h1 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .login-left-content p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 25px;
            line-height: 1.5;
            position: relative;
            z-index: 1;
        }

        .features-list {
            list-style: none;
            padding: 0;
            margin: 25px 0;
        }

        .features-list li {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.85rem;
            position: relative;
            z-index: 1;
        }

        .features-list i {
            color: var(--accent-blue);
            margin-right: 10px;
            font-size: 0.9rem;
            background: rgba(255,255,255,0.1);
            padding: 6px;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .register-link {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.85rem;
            opacity: 0.8;
            position: relative;
            z-index: 1;
        }

        .register-link a {
            color: white;
            text-decoration: underline;
            margin-left: 5px;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: none;
            opacity: 0.9;
        }

        /* LOGIN RIGHT PANEL */
        .login-right-panel {
            padding: 40px 35px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }

        .login-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .login-header h2 {
            color: var(--primary-blue);
            font-size: 1.6rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .login-header p {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* FORM STYLES */
        .login-form {
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
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
            color: var(--error-red);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(10, 31, 68, 0.15);
            border-radius: var(--radius);
            font-size: 0.95rem;
            transition: var(--transition);
            background: var(--off-white);
            height: 46px;
            box-sizing: border-box;
        }

        .form-control:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px var(--off-white) inset !important;
            box-shadow: 0 0 0 30px var(--off-white) inset !important;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(74, 111, 220, 0.1);
            background: var(--white);
        }

        /* PASSWORD FIELD */
        .password-field {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            z-index: 2;
        }

        .toggle-password:hover {
            color: var(--accent-blue);
        }

        /* ALERT */
        .alert-custom {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: var(--error-red);
            padding: 12px 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            animation: fadeIn 0.3s ease;
        }

        .alert-custom.success {
            background: rgba(25, 135, 84, 0.1);
            border-color: rgba(25, 135, 84, 0.2);
            color: var(--success-green);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* REMEMBER ME & FORGOT PASSWORD */
        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 0.85rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-light);
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            border: 1px solid rgba(10, 31, 68, 0.2);
            cursor: pointer;
        }

        .forgot-password {
            color: var(--accent-blue);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .forgot-password:hover {
            text-decoration: underline;
            color: var(--secondary-blue);
        }

        /* SUBMIT BUTTON */
        .submit-btn {
            background: linear-gradient(135deg, var(--accent-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            padding: 14px 0;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: block;
            width: 100%;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.7s ease;
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .submit-btn:hover:not(:disabled)::before {
            left: 100%;
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .submit-btn i {
            margin-right: 8px;
        }

        /* ADDITIONAL LINKS */
        .additional-links {
            margin-top: 25px;
            text-align: center;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .additional-links a {
            color: var(--accent-blue);
            text-decoration: none;
            font-weight: 500;
            margin: 0 5px;
        }

        .additional-links a:hover {
            text-decoration: underline;
        }

        /* SCROLLBAR */
        .login-right-panel::-webkit-scrollbar {
            width: 4px;
        }
        
        .login-right-panel::-webkit-scrollbar-track {
            background: rgba(10, 31, 68, 0.05);
            border-radius: 10px;
        }
        
        .login-right-panel::-webkit-scrollbar-thumb {
            background: rgba(10, 31, 68, 0.15);
            border-radius: 10px;
        }
        
        .login-right-panel::-webkit-scrollbar-thumb:hover {
            background: rgba(10, 31, 68, 0.25);
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .login-container-wrapper {
                padding: 10px;
                height: calc(100vh - 70px);
            }
            
            .compact-login-container {
                grid-template-columns: 1fr;
                height: auto;
                min-height: auto;
                max-width: 100%;
            }
            
            .login-left-panel {
                padding: 25px 20px;
            }
            
            .login-right-panel {
                padding: 30px 25px;
            }
            
            .login-header h2 {
                font-size: 1.4rem;
            }
            
            .login-form {
                max-width: 100%;
            }
        }

        @media (max-width: 576px) {
            .login-container-wrapper {
                padding: 8px;
            }
            
            .compact-login-container {
                border-radius: 8px;
            }
            
            .login-left-panel,
            .login-right-panel {
                padding: 20px;
            }
            
            .login-header h2 {
                font-size: 1.3rem;
            }
            
            .login-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        /* LOADING ANIMATION */
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Include Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- LOGIN CONTAINER -->
    <div class="login-container-wrapper">
        <div class="compact-login-container">
            <!-- Left Panel -->
            <div class="login-left-panel">
                <div class="login-left-content">
                    <img src="<?= BASE_URL ?>/logo.png" alt="Logo" class="login-logo">
                    <h1>Welcome Back</h1>
                    <p>Sign in to your account to continue your journey in finding your perfect match.</p>
                  
                    <div class="register-link">
                        New to our platform? 
                        <a href="<?= BASE_URL ?>/register">Create an account</a>
                    </div>
                </div>
            </div>

            <!-- Right Panel (Login Form) -->
            <div class="login-right-panel">
                <div class="login-header">
                    <h2>Sign In to Your Account</h2>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="" class="login-form" autocomplete="off" id="loginForm">
                    <div class="form-group">
                        <label class="form-label required">Username</label>
                        <input type="text" 
                               name="name" 
                               class="form-control" 
                               placeholder="Enter your username" 
                               required
                               autocomplete="off"
                               autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Password</label>
                        <div class="password-field">
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="form-control" 
                                   placeholder="Enter your password" 
                                   required
                                   autocomplete="off">
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                  

                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-sign-in-alt"></i>Sign In
                    </button>

                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Disable autocomplete
            $('input, select, textarea').attr('autocomplete', 'off');
            
            // Toggle password visibility
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });
            
            // Check if remember me was previously checked
            const savedUsername = localStorage.getItem('rememberedUsername');
            if (savedUsername) {
                $('input[name="name"]').val(savedUsername);
                $('#remember').prop('checked', true);
            }
            
            // Form submission handler
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                // Clear previous error
                $('.alert-custom').remove();
                
                // Show loading state
                const submitBtn = $('#submitBtn');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html(`
                    <span class="loading-spinner"></span>Signing In...
                `);
                
                // Validate inputs
                const username = $('input[name="name"]').val().trim();
                const password = $('#password').val();
                
                if (!username || !password) {
                    showError('Please fill in all required fields');
                    submitBtn.prop('disabled', false).html(originalText);
                    return;
                }
                
                // Save username if remember me is checked
                if ($('#remember').is(':checked')) {
                    localStorage.setItem('rememberedUsername', username);
                } else {
                    localStorage.removeItem('rememberedUsername');
                }
                
                // Submit the form
                setTimeout(() => {
                    this.submit();
                }, 500);
            });
            
            function showError(message) {
                $('#loginForm').prepend(`
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>${message}</span>
                    </div>
                `);
                
                // Scroll to error
                $('.login-right-panel').animate({scrollTop: 0}, 300);
            }
            
            // Add animation to form elements
            $('.form-control').on('focus', function() {
                $(this).parent().css('transform', 'translateY(-2px)');
            }).on('blur', function() {
                $(this).parent().css('transform', 'translateY(0)');
            });
            
            // Adjust container height on window resize
            function adjustContainerHeight() {
                const navbarHeight = $('nav').outerHeight() || 60;
                const windowHeight = $(window).height();
                const containerWrapper = $('.login-container-wrapper');
                
                containerWrapper.css('height', (windowHeight - navbarHeight - 10) + 'px');
            }
            
            adjustContainerHeight();
            $(window).resize(adjustContainerHeight);
            
            // Add keypress handler for Enter key
            $('input').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    $('#loginForm').submit();
                }
            });
        });
    </script>
</body>
</html>