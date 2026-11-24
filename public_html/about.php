<?php
require "config/init.php";
require "navbar.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>About Us - Sri Kumaran Manamaalai</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Learn about Sri Kumaran Manamaalai's heritage and commitment to traditional matchmaking values.">

<!-- Use the same fonts and styles from your home page -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Cinzel:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* Use the same CSS variables from your home page */
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
}

/* About Hero Section */
.about-hero {
    background: var(--blue-gradient);
    color: var(--white);
    padding: 100px 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.about-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('<?= BASE_URL ?>/assets/images/pattern.png') repeat;
    opacity: 0.05;
}

.about-hero-content {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

.about-hero h1 {
    font-family: 'Cinzel', serif;
    font-size: clamp(2.5rem, 4vw, 3.5rem);
    margin-bottom: 20px;
    font-weight: 600;
}

.about-hero p {
    font-size: 1.1rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

/* Main Content Sections */
.about-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 20px;
}

.about-section {
    margin-bottom: 60px;
}

.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    color: var(--primary-blue);
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 10px;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: var(--accent-blue);
}

.about-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    align-items: center;
    margin-top: 30px;
}

.about-text {
    font-size: 1rem;
    line-height: 1.8;
    color: var(--text-light);
}

.about-text p {
    margin-bottom: 20px;
}

.about-image {
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}

.about-image img {
    width: 100%;
    height: auto;
    display: block;
}

/* Values Grid */
.values-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 40px;
}

.value-card {
    background: var(--off-white);
    padding: 30px;
    border-radius: var(--radius-lg);
    text-align: center;
    border: 1px solid rgba(10, 31, 68, 0.1);
    transition: var(--transition);
}

.value-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.value-icon {
    width: 70px;
    height: 70px;
    background: var(--blue-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: var(--white);
    font-size: 1.5rem;
}

.value-card h3 {
    font-family: 'Playfair Display', serif;
    color: var(--primary-blue);
    margin-bottom: 15px;
    font-size: 1.3rem;
}

.value-card p {
    color: var(--text-light);
    font-size: 0.95rem;
    line-height: 1.6;
}

/* Mission Statement */
.mission-statement {
    background: var(--off-white);
    padding: 60px;
    border-radius: var(--radius-lg);
    margin: 60px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.mission-statement::before {
    content: '"';
    position: absolute;
    top: 20px;
    left: 40px;
    font-size: 10rem;
    font-family: 'Playfair Display', serif;
    color: var(--accent-blue);
    opacity: 0.1;
}

.mission-statement p {
    font-size: 1.2rem;
    line-height: 1.8;
    color: var(--primary-blue);
    font-style: italic;
    position: relative;
    z-index: 2;
    max-width: 800px;
    margin: 0 auto;
}

/* Contact CTA */
.contact-cta {
    text-align: center;
    padding: 60px 20px;
    background: var(--white);
    border-radius: var(--radius-lg);
    border: 1px solid rgba(10, 31, 68, 0.1);
}

.cta-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-top: 30px;
    flex-wrap: wrap;
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
}

.btn-primary {
    background: var(--accent-blue);
    color: var(--white);
    border: 1px solid var(--accent-blue);
}

.btn-primary:hover {
    background: var(--secondary-blue);
    border-color: var(--secondary-blue);
    transform: translateY(-2px);
}

.btn-secondary {
    background: transparent;
    color: var(--primary-blue);
    border: 1px solid var(--primary-blue);
}

.btn-secondary:hover {
    background: var(--primary-blue);
    color: var(--white);
    transform: translateY(-2px);
}

/* FOOTER - Exactly same as index.php */
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
    .about-content {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .values-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .mission-statement {
        padding: 40px;
    }
    
    .footer-content {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .about-hero {
        padding: 80px 20px;
    }
    
    .values-grid {
        grid-template-columns: 1fr;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
    }
    
    .mission-statement {
        padding: 30px 20px;
    }
    
    .mission-statement::before {
        font-size: 8rem;
        top: 10px;
        left: 20px;
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
}

@media (max-width: 480px) {
    .about-hero h1 {
        font-size: 2rem;
    }
    
    .section-title {
        font-size: 1.8rem;
    }
    
    .value-card {
        padding: 20px;
    }
}
</style>
</head>

<body>

<!-- About Hero Section 
<section class="about-hero">
    <div class="about-hero-content">
        <h1>Our Heritage of Trust</h1>
        <p>Discover the story behind Sri Kumaran Manamaalai - where tradition meets thoughtful matchmaking</p>
    </div>
</section>
-->
<!-- Main About Content -->
<div class="about-container">
    
    <!-- Our Story Section -->
    <section class="about-section">
        <h2 class="section-title">Our Story</h2>
        <div class="about-content">
            <div class="about-text">
                <p>Sri Kumaran Manamaalai was born from a deep understanding that marriage is more than just a union of two individuals - it's the coming together of families, values, and traditions.</p>
                <p>For generations, the Kumaran family has served as a pillar of wisdom and guidance in our community. Recognizing the need for a trustworthy platform that honors cultural values while embracing modern perspectives, we established this service in 2020.</p>
                <p>What began as a local initiative has grown into a respected matchmaking service trusted by families across Tamilnadu. Our journey reflects our unwavering commitment to creating meaningful connections that stand the test of time.</p>
            </div>
            <div class="about-image">
                <img src="<?= BASE_URL ?>/assets/about-story.jpg" alt="Our Story">
            </div>
        </div>
    </section>

    <!-- Our Mission Section -->
    <section class="about-section">
        <h2 class="section-title">Our Mission</h2>
        <div class="about-content">
            <div class="about-image">
                <img src="<?= BASE_URL ?>/assets/about-mission.jpg" alt="Our Mission">
            </div>
            <div class="about-text">
                <p>Our mission is simple yet profound: to facilitate marriages built on shared values, mutual respect, and cultural harmony.</p>
                <p>We believe that successful marriages are those where families find common ground in tradition, education, lifestyle, and aspirations. Our careful vetting process ensures that every match we suggest has the potential for long-term happiness and compatibility.</p>
                <p>We're not just creating matches - we're helping build families that will carry forward our rich cultural heritage while embracing progressive values.</p>
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="about-section">
        <h2 class="section-title">Our Core Values</h2>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-hands-helping"></i>
                </div>
                <h3>Trust & Integrity</h3>
                <p>Every profile is verified, every detail is confidential, and every match is made with complete transparency.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>Respect for Tradition</h3>
                <p>We honor cultural values while understanding modern perspectives, creating bridges between generations.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Family-Centric Approach</h3>
                <p>We involve families in the matchmaking process while respecting individual preferences and compatibility.</p>
            </div>
        </div>
    </section>

    <!-- Mission Statement -->
    <section class="mission-statement">
        <p>"At Sri Kumaran Manamaalai, we don't just make matches - we help build families. Our commitment is to create unions that bring joy to individuals, harmony to families, and continuity to our rich cultural traditions."</p>
    </section>

    <!-- Why Choose Us -->
    <section class="about-section">
        <h2 class="section-title">Why Families Trust Us</h2>
        <div class="about-content">
            <div class="about-text">
                <p><strong>Personalized Approach:</strong> Unlike automated platforms, we provide personalized guidance throughout the matchmaking journey.</p>
                <p><strong>Verified Profiles:</strong> Every member undergoes a verification process to ensure authenticity and sincerity.</p>
                <p><strong>Cultural Understanding:</strong> Our deep understanding of traditions ensures matches honor cultural values.</p>
                <p><strong>Proven Success:</strong> With 500+ successful matches, we have a track record of creating lasting alliances.</p>
                <p><strong>Ongoing Support:</strong> We remain available for guidance even after the match is made.</p>
            </div>
            <div class="about-image">
                <img src="<?= BASE_URL ?>/assets/about-trust.jpg" alt="Why Trust Us">
            </div>
        </div>
    </section>

    <!-- Contact CTA -->
    <section class="contact-cta">
        <h2 class="section-title">Begin Your Journey With Us</h2>
        <p style="color: var(--text-light); margin-bottom: 20px; max-width: 600px; margin-left: auto; margin-right: auto;">
            Whether you're seeking a life partner or looking for a suitable match for your loved one, 
            we're here to guide you with care, respect, and cultural sensitivity.
        </p>
        <div class="cta-buttons">
            <a href="<?= BASE_URL ?>/register" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Register Now
            </a>
            <a href="tel:+918754032742" class="btn btn-secondary">
                <i class="fas fa-phone-alt"></i> Call Us: +91 87540 32742
            </a>
        </div>
    </section>

</div>

<!-- FOOTER - Exactly same as index.php -->
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
                <p><i class="fas fa-user-tie"></i> <a href="tel:+918754032742" class="phone-link">+91 87540 32742</a></p>
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