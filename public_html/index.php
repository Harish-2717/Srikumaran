<?php
require "config/init.php";
require "navbar.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sri Kumaran Manamaalai - Trusted Matrimonial Service</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Sri Kumaran Manamaalai - A trusted matrimonial service blending tradition with modern values.">
<meta name="keywords" content="matrimony, matchmaking, traditional marriage, family alliance">
<meta property="og:title" content="Sri Kumaran Manamaalai - Trusted Matrimonial Service">
<meta property="og:description" content="Experience trusted matchmaking where tradition meets modern values.">
<meta property="og:image" content="<?= BASE_URL ?>/assets/images/hero-banner.jpg">
<meta property="og:type" content="website">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Cinzel:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --radius: 8px;
    --radius-lg: 12px;
    --shadow-sm: 0 2px 4px rgba(10, 31, 68, 0.05);
    --shadow-md: 0 4px 12px rgba(10, 31, 68, 0.08);
    --shadow-lg: 0 8px 24px rgba(10, 31, 68, 0.12);
    --blue-gradient: linear-gradient(135deg, #0A1F44 0%, #2C3E76 100%);
    --accent-gradient: linear-gradient(135deg, #4A6FDC 0%, #2C3E76 100%);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    line-height: 1.6;
    color: var(--text-dark);
    background: var(--white);
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Typography */
h1, h2, h3, h4 {
    font-family: 'Playfair Display', serif;
    font-weight: 600;
    line-height: 1.2;
}

h1 { font-size: clamp(2.5rem, 4vw, 3.2rem); }
h2 { font-size: clamp(2rem, 3.5vw, 2.5rem); }
h3 { font-size: clamp(1.5rem, 3vw, 1.8rem); }

/* Hero Section - With Background Image */
.hero-section {
    min-height: 90vh;
    background: 
        linear-gradient(135deg, rgba(10, 31, 68, 0.85) 0%, rgba(44, 62, 118, 0.75) 100%),
        url('<?= BASE_URL ?>/hero.jpg') center/cover no-repeat,
        var(--blue-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(74, 111, 220, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
    z-index: 1;
}

.hero-container {
    max-width: 1200px;
    width: 100%;
    position: relative;
    z-index: 2;
}

.hero-content {
    max-width: 800px;
    margin: 0 auto;
    color: var(--white);
    text-align: center;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.hero-title {
    font-family: 'Cinzel', serif;
    font-weight: 600;
    margin-bottom: 15px;
    color: var(--white);
    position: relative;
    display: inline-block;
}



.hero-subtitle {
    font-size: clamp(1rem, 2vw, 1.2rem);
    margin: 20px 0 30px;
    opacity: 0.9;
    line-height: 1.6;
    font-weight: 300;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin: 30px 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: var(--radius);
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.stat-number {
    display: block;
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--white);
    margin-bottom: 5px;
    font-family: 'Playfair Display', serif;
}

.stat-label {
    font-size: 0.8rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 400;
}

.hero-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 30px;
}

.btn {
    padding: 12px 32px;
    border-radius: var(--radius);
    font-weight: 500;
    text-decoration: none;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 0.95rem;
    border: none;
    cursor: pointer;
    text-transform: none;
    letter-spacing: 0.5px;
    position: relative;
    overflow: hidden;
}

.btn-primary {
    background: var(--white);
    color: var(--primary-blue);
    border: 1px solid var(--white);
    font-weight: 600;
    box-shadow: var(--shadow-md);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 255, 255, 0.2);
}

.btn-secondary {
    background: transparent;
    color: var(--white);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--white);
    transform: translateY(-2px);
}

/* Compact Sections */
.section {
    padding: 60px 20px;
    position: relative;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
}

.section-title {
    text-align: center;
    margin-bottom: 15px;
    color: var(--primary-blue);
    position: relative;
    padding-bottom: 15px;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: var(--accent-blue);
    border-radius: 2px;
}

.section-subtitle {
    text-align: center;
    font-size: 1rem;
    color: var(--text-light);
    max-width: 600px;
    margin: 0 auto 40px;
    line-height: 1.6;
}

/* About Sections - Compact */
.about-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: center;
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 30px;
    box-shadow: var(--shadow-md);
    border: 1px solid rgba(10, 31, 68, 0.08);
    margin-bottom: 30px;
}

.about-image {
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: var(--transition);
}

.about-image img {
    width: 100%;
    height: 300px;
    object-fit: cover;
    transition: var(--transition);
}

.about-image:hover img {
    transform: scale(1.03);
}

.about-content {
    padding: 10px;
}

.about-content h3 {
    font-family: 'Cinzel', serif;
    font-weight: 500;
    margin-bottom: 15px;
    color: var(--primary-blue);
    position: relative;
    padding-bottom: 10px;
}

.about-content h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 2px;
    background: var(--accent-blue);
}

.about-content p {
    margin-bottom: 15px;
    font-size: 0.95rem;
    line-height: 1.6;
    color: var(--text-light);
}

.highlight-text {
    background: linear-gradient(135deg, rgba(74, 111, 220, 0.05) 0%, rgba(10, 31, 68, 0.03) 100%);
    padding: 20px;
    border-radius: var(--radius);
    border-left: 3px solid var(--accent-blue);
    margin: 20px 0;
}

.highlight-text p {
    font-size: 1rem;
    font-weight: 500;
    color: var(--primary-blue);
    margin: 0;
    line-height: 1.6;
    font-style: italic;
}

/* Registration Section - White Background */
.registration-section {
    background: var(--white);
    padding: 60px 20px;
}

.registration-title {
    color: var(--primary-blue) !important;
}

.registration-title::after {
    background: var(--accent-blue);
}

.registration-subtitle {
    color: var(--text-light) !important;
}

.registration-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: start;
}

.registration-info {
    background: var(--off-white);
    border-radius: var(--radius-lg);
    padding: 25px;
    border: 1px solid rgba(10, 31, 68, 0.1);
    box-shadow: var(--shadow-md);
}

.registration-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(10, 31, 68, 0.1);
}

.registration-icon {
    width: 45px;
    height: 45px;
    background: var(--accent-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: var(--white);
}

.registration-details {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.registration-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid rgba(10, 31, 68, 0.08);
}

.registration-item:last-child {
    border-bottom: none;
}

.registration-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 400;
    color: var(--text-light);
    font-size: 0.9rem;
}

.registration-label i {
    color: var(--accent-blue);
}

.registration-value {
    font-weight: 500;
    color: var(--primary-blue);
    font-size: 0.9rem;
}

.amount-badge {
    background: var(--accent-gradient);
    color: var(--white);
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.qr-container {
    background: var(--off-white);
    border-radius: var(--radius-lg);
    padding: 25px;
    text-align: center;
    box-shadow: var(--shadow-lg);
    border: 1px solid rgba(10, 31, 68, 0.1);
}

.qr-code {
    width: 100%;
    max-width: 250px;
    height: auto;
    margin-bottom: 15px;
    border-radius: var(--radius);
    border: 1px solid rgba(10, 31, 68, 0.1);
}

.payment-methods {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 15px;
}

.payment-method {
    width: 40px;
    height: 40px;
    background: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-blue);
    font-size: 1rem;
    border: 1px solid rgba(10, 31, 68, 0.1);
    transition: var(--transition);
}

.payment-method:hover {
    background: var(--accent-blue);
    color: var(--white);
    transform: translateY(-2px);
    border-color: var(--accent-blue);
}

/* Stats Section - Compact */
.stats-section {
    background: var(--off-white);
    padding: 60px 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.stat-card {
    background: var(--white);
    padding: 25px 20px;
    text-align: center;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.stat-number-lg {
    display: block;
    font-size: 2rem;
    font-weight: 600;
    color: var(--primary-blue);
    margin-bottom: 8px;
    font-family: 'Playfair Display', serif;
}

.stat-number-lg::after {
    content: '+';
    color: var(--accent-blue);
    font-size: 1.5rem;
    margin-left: 2px;
}

.stat-card p {
    color: var(--text-light);
    font-size: 0.9rem;
    font-weight: 400;
}

/* CTA Section - Compact */
.cta-section {
    background: var(--white);
    color: var(--blue-gradient);
    text-align: center;
    padding: 60px 20px;
}

.cta-title {
    margin-bottom: 15px;
}

.cta-text {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 25px;
    line-height: 1.6;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* Footer - Compact */
.footer {
    background: var(--primary-blue);
    color: var(--white);
    padding: 50px 20px 20px;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
    margin-bottom: 30px;
}

.footer-logo-section img {
    max-width: 200px;
    margin-bottom: 15px;
}

.footer-logo-section p {
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.6;
    margin-bottom: 20px;
    font-size: 0.9rem;
}

.social-links {
    display: flex;
    gap: 10px;
}

.social-link {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    text-decoration: none;
    transition: var(--transition);
    font-size: 0.9rem;
}

.social-link:hover {
    background: var(--white);
    color: var(--primary-blue);
    transform: translateY(-2px);
}

.footer-heading {
    font-size: 1.1rem;
    margin-bottom: 20px;
    color: var(--white);
    font-weight: 500;
    position: relative;
    padding-bottom: 8px;
}

.footer-heading::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 30px;
    height: 2px;
    background: var(--white);
    opacity: 0.8;
}

.footer-links ul {
    list-style: none;
}

.footer-links li {
    margin-bottom: 10px;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.footer-links a:hover {
    color: var(--white);
    padding-left: 5px;
}

.footer-contact p {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    gap: 8px;
    line-height: 1.5;
    font-size: 0.9rem;
}

.footer-contact i {
    color: var(--white);
    opacity: 0.9;
    margin-top: 3px;
    font-size: 0.9rem;
}

.copyright {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.8rem;
}

/* Responsive Design */
@media (max-width: 992px) {
    .hero-section {
        min-height: 80vh;
    }
    
    .about-section,
    .registration-grid,
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .footer-content {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .hero-stats {
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }
}

@media (max-width: 768px) {
    .section {
        padding: 40px 20px;
    }
    
    .hero-section {
        min-height: 70vh;
        padding: 30px 20px;
    }
    
    .hero-stats {
        grid-template-columns: 1fr;
        max-width: 300px;
    }
    
    .hero-buttons {
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
    
    .btn {
        width: 100%;
        max-width: 280px;
    }
    
    .about-section,
    .registration-info,
    .qr-container {
        padding: 20px;
    }
    
    .stat-card {
        padding: 20px 15px;
    }
    
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 30px;
    }
    
    .footer-heading::after {
        left: 50%;
        transform: translateX(-50%);
    }
    
    .footer-links a {
        justify-content: center;
    }
    
    .social-links {
        justify-content: center;
    }
    
    .payment-methods {
        flex-wrap: wrap;
    }
}

@media (max-width: 480px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .section-title {
        font-size: 1.8rem;
    }
    
    .stat-number-lg {
        font-size: 1.8rem;
    }
    
    .about-image img {
        height: 250px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Phone number handler
    document.querySelectorAll('.phone-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const phone = this.textContent.replace(/\D/g, '');
            window.location.href = `tel:+91${phone}`;
        });
    });

    // Animate stats on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const stats = entry.target.querySelectorAll('.stat-number-lg');
                stats.forEach(stat => {
                    const target = parseInt(stat.textContent);
                    let current = 0;
                    const increment = target / 30;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            stat.textContent = target + '+';
                            clearInterval(timer);
                        } else {
                            stat.textContent = Math.floor(current) + '+';
                        }
                    }, 30);
                });
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    // Observe stats section
    const statsSection = document.querySelector('.stats-section');
    if (statsSection) observer.observe(statsSection);

    // Add hover effects
    const cards = document.querySelectorAll('.stat-card, .about-section, .registration-info');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });
    
    // Parallax effect for hero background
    const hero = document.querySelector('.hero-section');
    let isScrolling = false;
    
    window.addEventListener('scroll', function() {
        if (!isScrolling) {
            isScrolling = true;
            requestAnimationFrame(function() {
                const scrolled = window.pageYOffset;
                const rate = scrolled * 0.3;
                hero.style.backgroundPosition = `center ${rate}px`;
                isScrolling = false;
            });
        }
    });
    
    // Initialize background position
    hero.style.backgroundPosition = 'center 0px';
});
</script>
</head>

<body>

<!-- HERO SECTION WITH BACKGROUND IMAGE -->
<section class="hero-section">
    <div class="hero-container">
        <div class="hero-content">
            <h1 class="hero-title">Welcome to</h1>
            <h1 class="hero-title">Sri Kumaran Manamaalai</h1>
            
               <div class="hero-buttons">
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>  Registration
                </a>
                <a href="<?= BASE_URL ?>/login" class="btn btn-secondary">
                    <i class="fas fa-sign-in-alt"></i> Member Login
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ABOUT SECTION -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Our Heritage of Trust</h2>
        <p class="section-subtitle">Where tradition meets thoughtful matchmaking for all families</p>
        
        <div class="about-section">
            <div class="about-image">
                <img src="<?= BASE_URL ?>/assets/kumaran.jpg" alt="Sri Kumaran Heritage">
            </div>
            <div class="about-content">
                <h3>The Kumaran Legacy</h3>
                <p>For generations, the Kumaran name has stood as a beacon of wisdom, integrity, and cultural values. Our service draws from this rich heritage.</p>
                <div class="highlight-text">
                    <p>"True happiness flows from families built on shared values, mutual respect, and cultural continuity."</p>
                </div>
                <p>This philosophy is woven into every aspect of Sri Kumaran Manamaalai, ensuring each match reflects our heritage and commitment.</p>
            </div>
        </div>

        <div class="about-section">
            <div class="about-content">
                <h3>Sri Murugan Manamaalai</h3>
                <p><strong>Trusted Matchmaking Service</strong></p>
                <p>Since 2020, we've established ourselves as a trusted service for families. Our network spans Tamilnadu's diverse communities.</p>
                <p>500+ successful alliances, each celebrated for harmony and shared values. Our careful approach ensures quality and cultural integrity for all.</p>
            </div>
            <div class="about-image">
                <img src="<?= BASE_URL ?>/assets/murugan.jpg" alt="Sri Murugan Service">
            </div>
        </div>
    </div>
</section>

<!-- REGISTRATION SECTION -->
<section class="section registration-section">
    <div class="container">
        <h2 class="section-title registration-title">Membership Registration</h2>
        <p class="section-subtitle registration-subtitle">Join our trusted matchmaking community</p>
        
        <div class="registration-grid">
            <div class="registration-info">
                <div class="registration-header">
                    <div class="registration-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h3 style="color: var(--primary-blue); margin: 0; font-size: 1.3rem;">Membership Details</h3>
                        <p style="color: var(--text-light); margin-top: 5px; font-size: 0.9rem;">Your gateway to trusted matchmaking</p>
                    </div>
                </div>
                
                <div class="registration-details">
                    <div class="registration-item">
                        <div class="registration-label">
                            <i class="fas fa-award"></i>
                            <span>Registration</span>
                        </div>
                        <div class="registration-value">
                            <span class="amount-badge">RS 3000</span>
                        </div>
                    </div>
                    
                    <div class="registration-item">
                        <div class="registration-label">
                            <i class="fas fa-landmark"></i>
                            <span>Account Holder</span>
                        </div>
                        <div class="registration-value">SRI KUMARAN MANAMAALAI</div>
                    </div>
                    
                    <div class="registration-item">
                        <div class="registration-label">
                            <i class="fas fa-user-tie"></i>
                            <span>Primary Contact</span>
                        </div>
                        <div class="registration-value">
                            <a href="tel:+919894732742" class="phone-link">
                                +91 98947 32742
                            </a>
                        </div>
                    </div>
                    
                    <a href="<?= BASE_URL ?>/register" class="btn btn-primary" style="background: var(--accent-gradient); color: var(--white); border: none; margin-top: 20px; padding: 10px 25px;">
                        <i class="fas fa-user-plus"></i> Start Registration
                    </a>
                </div>
            </div>
            
            <div class="qr-container">
                <h3 style="color: var(--primary-blue); margin-bottom: 15px; font-size: 1.2rem;">
                     Do Easy Payment
                </h3>
                <img src="<?= BASE_URL ?>/qr-code.png" alt="QR Code" class="qr-code">
              
                
                <div class="payment-methods">
                    <div class="payment-method">
                        <i class="fab fa-google-pay"></i>
                    </div>
                    <div class="payment-method">
                        <i class="fas fa-university"></i>
                    </div>
                    <div class="payment-method">
                        <i class="fas fa-credit-card"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATS SECTION -->
<section class="section stats-section">
    <div class="container">
        <h2 class="section-title">Our Journey of Trust</h2>
        <p class="section-subtitle">Numbers that reflect our commitment to meaningful matchmaking</p>
        
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number-lg">5,000</span>
                <p>Registered Members</p>
            </div>
            <div class="stat-card">
                <span class="stat-number-lg">500</span>
                <p>Successful Matches</p>
            </div>
            <div class="stat-card">
                <span class="stat-number-lg">4</span>
                <p>Years of Service</p>
            </div>
            <div class="stat-card">
                <span class="stat-number-lg">100</span>
                <p>Satisfaction Rate</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA SECTION -->
<section class="section cta-section">
    <div class="container">
        <h2 class="cta-title">Begin Your Journey With Us</h2>
        <p class="cta-text">
            Join our trusted matrimonial service where tradition, respect, 
            and meaningful connections come together to create lasting alliances.
        </p>
        <a href="<?= BASE_URL ?>/register" class="btn btn-primary" style="font-size: 1rem; padding: 12px 35px;">
            <i class="fas fa-user-plus"></i> Register Now
        </a>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-logo-section">
                <img src="<?= BASE_URL ?>/logo.png" alt="Sri Kumaran Manamaalai">
                <p>A trusted matchmaking service committed to cultural values, respect, and meaningful connections.</p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            
            <div class="footer-links">
                <h4 class="footer-heading">Services</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>/"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="<?= BASE_URL ?>/about"><i class="fas fa-history"></i> Our Heritage</a></li>
                    <li><a href="<?= BASE_URL ?>/register"><i class="fas fa-user-plus"></i> Registration</a></li>
                    <li><a href="<?= BASE_URL ?>/login"><i class="fas fa-sign-in-alt"></i> Member Login</a></li>
                </ul>
            </div>
            
            <div class="footer-contact">
                <h4 class="footer-heading">Contact Us</h4>
                <p><i class="fas fa-map-marker-alt"></i> 79, North Vinayagapuram, 8th Street, Saravanampatti, Coimbatore - 641035</p>
                <p><i class="fas fa-user-tie"></i> <a href="tel:+919894732742" class="phone-link">+91 98947 32742</a></p>
                <p><i class="fas fa-building"></i> <a href="tel:+919894732742" class="phone-link">+91 98947 32742</a></p>
                <p><i class="fas fa-building"></i> <a href="tel:+914222666505" class="phone-link">+91 422 2666505</a></p>
                
                
                <p><i class="fas fa-envelope"></i> <a href="mailto:info@kumaranmaamalai.com" style="color: rgba(255,255,255,0.7); text-decoration: none;" onmouseover="this.style.color='var(--white)'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">info@kumaranmaamalai.com</a></p>
            </div>
        </div>
        
        <div class="copyright">
            © <?= date('Y') ?> Sri Kumaran Manamaalai. All rights reserved.
        </div>
    </div>
</footer>

</body>
</html>