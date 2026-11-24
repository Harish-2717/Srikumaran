<?php
require "config/init.php";
require "navbar.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gallery - Recent Marriages | Sri Kumaran Mana Maalai</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Gallery of recent successful marriages and happy couples through Sri Kumaran Mana Maalai matchmaking service.">

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

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Gallery Hero Section */
.gallery-hero {
    background: var(--blue-gradient);
    color: var(--white);
    padding: 80px 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.gallery-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('<?= BASE_URL ?>/assets/images/pattern.png') repeat;
    opacity: 0.05;
}

.gallery-hero-content {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

.gallery-hero h1 {
    font-family: 'Cinzel', serif;
    font-size: clamp(2.5rem, 4vw, 3.5rem);
    margin-bottom: 20px;
    font-weight: 600;
}

.gallery-hero p {
    font-size: 1.1rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

/* Gallery Main Section */
.gallery-main {
    padding: 60px 0;
}

.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    color: var(--primary-blue);
    margin-bottom: 40px;
    position: relative;
    padding-bottom: 10px;
    text-align: center;
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
}

/* Gallery Grid */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
}

.gallery-item {
    background: var(--white);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: var(--transition);
    border: 1px solid rgba(10, 31, 68, 0.1);
}

.gallery-item:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.gallery-image {
    position: relative;
    overflow: hidden;
    height: 250px;
}

.placeholder-image {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--white);
    padding: 20px;
    text-align: center;
    transition: var(--transition);
}

.gallery-item:hover .placeholder-image {
    transform: scale(1.05);
}

.gallery-info {
    padding: 20px;
}

.gallery-info h3 {
    font-family: 'Playfair Display', serif;
    color: var(--primary-blue);
    margin-bottom: 10px;
    font-size: 1.2rem;
}

.couple-names {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(10, 31, 68, 0.1);
}

.couple-names span {
    font-weight: 500;
    color: var(--text-dark);
}

.couple-names .groom {
    color: var(--accent-blue);
}

.couple-names .heart {
    color: #D53F8C;
    margin: 0 10px;
    font-size: 1.2rem;
}

.couple-names .bride {
    color: #D53F8C;
}

.gallery-details {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    color: var(--text-light);
    margin-bottom: 15px;
}

.gallery-details i {
    margin-right: 5px;
    color: var(--accent-blue);
}

.gallery-testimonial {
    font-style: italic;
    color: var(--text-light);
    font-size: 0.9rem;
    line-height: 1.5;
    padding-top: 15px;
    border-top: 1px solid rgba(10, 31, 68, 0.1);
}

.gallery-testimonial::before {
    content: '"';
    color: var(--accent-blue);
    font-size: 1.2rem;
    margin-right: 3px;
}

/* Stats Banner */
.stats-banner {
    background: var(--blue-gradient);
    color: var(--white);
    padding: 40px;
    border-radius: var(--radius-lg);
    margin: 60px 0;
    text-align: center;
}

.stats-banner h2 {
    font-family: 'Playfair Display', serif;
    font-size: 1.8rem;
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.stat-item {
    padding: 20px;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 5px;
    font-family: 'Playfair Display', serif;
}

.stat-number::after {
    content: '+';
    color: var(--accent-blue);
    margin-left: 2px;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

/* FOOTER */
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
    .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .footer-content {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .gallery-hero {
        padding: 60px 20px;
    }
    
    .gallery-grid {
        grid-template-columns: 1fr;
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
    
    .stats-banner {
        padding: 30px 20px;
    }
}

@media (max-width: 480px) {
    .gallery-hero h1 {
        font-size: 2rem;
    }
    
    .section-title {
        font-size: 1.8rem;
    }
    
    .gallery-image {
        height: 200px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<!-- Gallery Main Section -->
<div class="container">
    <section class="gallery-main">
        <h2 class="section-title">Recent Marriages</h2>
        
        <!-- Gallery Grid -->
        <div class="gallery-grid">
            <!-- Marriage 1 -->
            <div class="gallery-item">
                <div class="gallery-image">
                    <div class="placeholder-image" style="background: linear-gradient(135deg, #0A1F44, #2C3E76);">
                        <div style="font-family: 'Cinzel', serif; font-size: 2rem; margin-bottom: 10px; font-weight: 600;">
                            Arun ♥ Priya
                        </div>
                        <div style="font-size: 1rem; opacity: 0.9; margin-bottom: 15px;">
                            Jan 15, 2024
                        </div>
                        <div style="font-size: 1.2rem; font-family: 'Playfair Display', serif;">
                            💑
                        </div>
                    </div>
                </div>
                <div class="gallery-info">
                    <h3>Arun & Priya</h3>
                    <div class="couple-names">
                        <span class="groom">Arun Kumar</span>
                        <span class="heart">♥</span>
                        <span class="bride">Priya Shankar</span>
                    </div>
                    <div class="gallery-details">
                        <span><i class="fas fa-calendar"></i> Jan 15, 2024</span>
                        <span><i class="fas fa-map-marker-alt"></i> Coimbatore</span>
                    </div>
                    <p class="gallery-testimonial">"Sri Kumaran Mana Maalai helped our families find the perfect match. We couldn't be happier!"</p>
                </div>
            </div>
            
            <!-- Marriage 2 -->
            <div class="gallery-item">
                <div class="gallery-image">
                    <div class="placeholder-image" style="background: linear-gradient(135deg, #D53F8C, #B83280);">
                        <div style="font-family: 'Cinzel', serif; font-size: 2rem; margin-bottom: 10px; font-weight: 600;">
                            Karthik ♥ Meena
                        </div>
                        <div style="font-size: 1rem; opacity: 0.9; margin-bottom: 15px;">
                            Feb 22, 2024
                        </div>
                        <div style="font-size: 1.2rem; font-family: 'Playfair Display', serif;">
                            💐
                        </div>
                    </div>
                </div>
                <div class="gallery-info">
                    <h3>Karthik & Meena</h3>
                    <div class="couple-names">
                        <span class="groom">Karthik Rajan</span>
                        <span class="heart">♥</span>
                        <span class="bride">Meena Devi</span>
                    </div>
                    <div class="gallery-details">
                        <span><i class="fas fa-calendar"></i> Feb 22, 2024</span>
                        <span><i class="fas fa-map-marker-alt"></i> Ooty</span>
                    </div>
                    <p class="gallery-testimonial">"A beautiful destination wedding made possible by the perfect match."</p>
                </div>
            </div>
            
            <!-- Marriage 3 -->
            <div class="gallery-item">
                <div class="gallery-image">
                    <div class="placeholder-image" style="background: linear-gradient(135deg, #0A1F44, #2C5282);">
                        <div style="font-family: 'Cinzel', serif; font-size: 2rem; margin-bottom: 10px; font-weight: 600;">
                            Suresh ♥ Anjali
                        </div>
                        <div style="font-size: 1rem; opacity: 0.9; margin-bottom: 15px;">
                            Dec 10, 2023
                        </div>
                        <div style="font-size: 1.2rem; font-family: 'Playfair Display', serif;">
                            👰‍♀️🤵‍♂️
                        </div>
                    </div>
                </div>
                <div class="gallery-info">
                    <h3>Suresh & Anjali</h3>
                    <div class="couple-names">
                        <span class="groom">Suresh Venkat</span>
                        <span class="heart">♥</span>
                        <span class="bride">Anjali Iyer</span>
                    </div>
                    <div class="gallery-details">
                        <span><i class="fas fa-calendar"></i> Dec 10, 2023</span>
                        <span><i class="fas fa-map-marker-alt"></i> Chennai</span>
                    </div>
                    <p class="gallery-testimonial">"Traditional values, modern compatibility - perfect match!"</p>
                </div>
            </div>
            
            <!-- Marriage 4 -->
            <div class="gallery-item">
                <div class="gallery-image">
                    <div class="placeholder-image" style="background: linear-gradient(135deg, #2D3748, #4A5568);">
                        <div style="font-family: 'Cinzel', serif; font-size: 2rem; margin-bottom: 10px; font-weight: 600;">
                            Rajesh ♥ Deepa
                        </div>
                        <div style="font-size: 1rem; opacity: 0.9; margin-bottom: 15px;">
                            Nov 5, 2023
                        </div>
                        <div style="font-size: 1.2rem; font-family: 'Playfair Display', serif;">
                            💒
                        </div>
                    </div>
                </div>
                <div class="gallery-info">
                    <h3>Rajesh & Deepa</h3>
                    <div class="couple-names">
                        <span class="groom">Rajesh Kumar</span>
                        <span class="heart">♥</span>
                        <span class="bride">Deepa Srinivasan</span>
                    </div>
                    <div class="gallery-details">
                        <span><i class="fas fa-calendar"></i> Nov 5, 2023</span>
                        <span><i class="fas fa-map-marker-alt"></i> Madurai</span>
                    </div>
                    <p class="gallery-testimonial">"Our families found cultural harmony through this match."</p>
                </div>
            </div>
            
            <!-- Marriage 5 -->
            <div class="gallery-item">
                <div class="gallery-image">
                    <div class="placeholder-image" style="background: linear-gradient(135deg, #553C9A, #6B46C1);">
                        <div style="font-family: 'Cinzel', serif; font-size: 2rem; margin-bottom: 10px; font-weight: 600;">
                            Mohan ♥ Lakshmi
                        </div>
                        <div style="font-size: 1rem; opacity: 0.9; margin-bottom: 15px;">
                            Oct 18, 2022
                        </div>
                        <div style="font-size: 1.2rem; font-family: 'Playfair Display', serif;">
                            💝
                        </div>
                    </div>
                </div>
                <div class="gallery-info">
                    <h3>Mohan & Lakshmi</h3>
                    <div class="couple-names">
                        <span class="groom">Mohan Das</span>
                        <span class="heart">♥</span>
                        <span class="bride">Lakshmi Menon</span>
                    </div>
                    <div class="gallery-details">
                        <span><i class="fas fa-calendar"></i> Oct 18, 2022</span>
                        <span><i class="fas fa-map-marker-alt"></i> Trichy</span>
                    </div>
                    <p class="gallery-testimonial">"Two years of marital bliss, thanks to the perfect match."</p>
                </div>
            </div>
            
            <!-- Marriage 6 -->
            <div class="gallery-item">
                <div class="gallery-image">
                    <div class="placeholder-image" style="background: linear-gradient(135deg, #2C5282, #4299E1);">
                        <div style="font-family: 'Cinzel', serif; font-size: 2rem; margin-bottom: 10px; font-weight: 600;">
                            Vikram ♥ Shalini
                        </div>
                        <div style="font-size: 1rem; opacity: 0.9; margin-bottom: 15px;">
                            Sep 8, 2022
                        </div>
                        <div style="font-size: 1.2rem; font-family: 'Playfair Display', serif;">
                            🏔️
                        </div>
                    </div>
                </div>
                <div class="gallery-info">
                    <h3>Vikram & Shalini</h3>
                    <div class="couple-names">
                        <span class="groom">Vikram Raj</span>
                        <span class="heart">♥</span>
                        <span class="bride">Shalini Kumar</span>
                    </div>
                    <div class="gallery-details">
                        <span><i class="fas fa-calendar"></i> Sep 8, 2022</span>
                        <span><i class="fas fa-map-marker-alt"></i> Kodaikanal</span>
                    </div>
                    <p class="gallery-testimonial">"A dream wedding in the hills, just as we imagined."</p>
                </div>
            </div>
        </div>
        
   
    </section>
</div>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-logo-section">
                <img src="<?= BASE_URL ?>/logo.png" alt="Sri Kumaran Mana Maalai">
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
            © <?= date('Y') ?> Sri Kumaran Mana Maalai. All rights reserved.
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
                const stats = entry.target.querySelectorAll('.stat-number');
                stats.forEach(stat => {
                    const currentText = stat.textContent;
                    const target = parseInt(currentText.replace('+', ''));
                    let current = 0;
                    const increment = target / 50;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            stat.textContent = target + '+';
                            clearInterval(timer);
                        } else {
                            stat.textContent = Math.floor(current) + '+';
                        }
                    }, 20);
                });
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    // Observe stats banner
    const statsBanner = document.querySelector('.stats-banner');
    if (statsBanner) observer.observe(statsBanner);
});
</script>

</body>
</html>