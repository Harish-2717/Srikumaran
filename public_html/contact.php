<?php
require "config/init.php";
require "navbar.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Us - Sri Kumaran Mana Maalai</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Contact Sri Kumaran Mana Maalai for trusted matrimonial services and matchmaking guidance.">

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

.container {
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
}

/* Contact Hero Section */
.contact-hero {
    background: var(--blue-gradient);
    color: var(--white);
    padding: 100px 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.contact-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('<?= BASE_URL ?>/assets/images/pattern.png') repeat;
    opacity: 0.05;
}

.contact-hero-content {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

.contact-hero h1 {
    font-family: 'Cinzel', serif;
    font-size: clamp(2.5rem, 4vw, 3.5rem);
    margin-bottom: 20px;
    font-weight: 600;
}

.contact-hero p {
    font-size: 1.1rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

/* Contact Main Section */
.contact-main {
    padding: 60px 20px;
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

/* Contact Grid */
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    margin-bottom: 60px;
}

/* Contact Info */
.contact-info {
    background: var(--off-white);
    padding: 40px;
    border-radius: var(--radius-lg);
    border: 1px solid rgba(10, 31, 68, 0.1);
}

.contact-method {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid rgba(10, 31, 68, 0.1);
}

.contact-method:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.contact-icon {
    width: 60px;
    height: 60px;
    background: var(--blue-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    color: var(--white);
    font-size: 1.3rem;
}

.contact-method h3 {
    font-family: 'Playfair Display', serif;
    color: var(--primary-blue);
    margin-bottom: 10px;
    font-size: 1.3rem;
}

.contact-method p {
    color: var(--text-light);
    line-height: 1.6;
    margin-bottom: 5px;
}

.contact-link {
    color: var(--accent-blue);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
    display: inline-block;
    margin-top: 5px;
}

.contact-link:hover {
    color: var(--secondary-blue);
    text-decoration: underline;
}

/* Contact Hours */
.contact-hours {
    background: var(--white);
    padding: 30px;
    border-radius: var(--radius-lg);
    border: 1px solid rgba(10, 31, 68, 0.1);
    margin-top: 30px;
}

.contact-hours h3 {
    font-family: 'Playfair Display', serif;
    color: var(--primary-blue);
    margin-bottom: 20px;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.contact-hours h3 i {
    color: var(--accent-blue);
}

.hours-list {
    list-style: none;
}

.hours-list li {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid rgba(10, 31, 68, 0.1);
    color: var(--text-light);
}

.hours-list li:last-child {
    border-bottom: none;
}

.hours-list .day {
    font-weight: 500;
    color: var(--primary-blue);
}

/* Contact Form */
.contact-form-section {
    background: var(--white);
    padding: 40px;
    border-radius: var(--radius-lg);
    border: 1px solid rgba(10, 31, 68, 0.1);
    box-shadow: var(--shadow-sm);
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--primary-blue);
    font-size: 0.95rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid rgba(10, 31, 68, 0.2);
    border-radius: var(--radius);
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    color: var(--text-dark);
    background: var(--white);
    transition: var(--transition);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--accent-blue);
    box-shadow: 0 0 0 3px rgba(74, 111, 220, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 150px;
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
    font-family: 'Inter', sans-serif;
}

.btn-primary {
    background: var(--accent-blue);
    color: var(--white);
    border: 1px solid var(--accent-blue);
    font-weight: 600;
}

.btn-primary:hover {
    background: var(--secondary-blue);
    border-color: var(--secondary-blue);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-block {
    width: 100%;
    padding: 14px;
}

/* Map Section */
.map-section {
    margin: 60px 0;
}

.map-container {
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    height: 400px;
    border: 1px solid rgba(10, 31, 68, 0.1);
}

.map-container iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* Quick Contact */
.quick-contact {
    background: var(--off-white);
    padding: 60px 20px;
    border-radius: var(--radius-lg);
    margin: 60px 0;
    text-align: center;
}

.quick-contact h2 {
    font-family: 'Playfair Display', serif;
    color: var(--primary-blue);
    margin-bottom: 15px;
    font-size: 2rem;
}

.quick-contact p {
    color: var(--text-light);
    margin-bottom: 30px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

.contact-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
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
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .footer-content {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .contact-hero {
        padding: 80px 20px;
    }
    
    .contact-info,
    .contact-form-section {
        padding: 30px;
    }
    
    .contact-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
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
    
    .map-container {
        height: 300px;
    }
}

@media (max-width: 480px) {
    .contact-hero h1 {
        font-size: 2rem;
    }
    
    .section-title {
        font-size: 1.8rem;
    }
    
    .contact-info,
    .contact-form-section {
        padding: 20px;
    }
    
    .contact-icon {
        width: 50px;
        height: 50px;
        font-size: 1.1rem;
    }
}
</style>
</head>

<body>

<!-- Contact Hero Section
<section class="contact-hero">
    <div class="contact-hero-content">
        <h1>Contact Us</h1>
        <p>We're here to guide you through your matchmaking journey. Get in touch with our team today.</p>
    </div>
</section>
 -->

<!-- Contact Main Section -->
<div class="container">
    <section class="contact-main">
        <h2 class="section-title">Get In Touch</h2>
        
        <div class="contact-grid">
            <!-- Contact Information -->
            <div class="contact-info">
                <div class="contact-method">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Visit Our Office</h3>
                    <p>79, North Vinayagapuram, 8th Street</p>
                    <p>Saravanampatti, Coimbatore - 641035</p>
                    <p>Tamil Nadu, India</p>
                </div>
                
                <div class="contact-method">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h3>Call Us</h3>
                    <p>Primary Contact:</p>
                    <a href="tel:+918754032742" class="contact-link">+91 87540 32742</a><br>
                    <a href="tel:+919894732742" class="contact-link">+91 98947 32742</a>
                    <p>Office Line:</p>
                    <a href="tel:+914222666505" class="contact-link">+91 422 2666505</a>
                </div>
                
                <div class="contact-method">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email Us</h3>
                    <p>For general inquiries:</p>
                    <a href="mailto:info@kumaranmaamalai.com" class="contact-link">info@kumaranmaamalai.com</a>
                    <p>For registration support:</p>
                    <a href="mailto:support@kumaranmaamalai.com" class="contact-link">support@kumaranmaamalai.com</a>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="contact-form-section">
                <h3 style="color: var(--primary-blue); margin-bottom: 25px; font-family: 'Playfair Display', serif; font-size: 1.5rem;">Send Us a Message</h3>
                <form action="<?= BASE_URL ?>/submit-contact" method="POST">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required placeholder="Enter your full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address">
                    </div>
                    
                    <div class="form-group">
                        <label for="purpose">Purpose of Contact *</label>
                        <select id="purpose" name="purpose" required>
                            <option value="" disabled selected>Select a purpose</option>
                            <option value="registration">Registration Inquiry</option>
                            <option value="matchmaking">Matchmaking Service</option>
                            <option value="support">Technical Support</option>
                            <option value="feedback">Feedback/Suggestion</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required placeholder="Please share your inquiry or message here..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Contact Hours -->
        <div class="contact-hours">
            <h3><i class="fas fa-clock"></i> Office Hours</h3>
            <ul class="hours-list">
                <li>
                    <span class="day">Monday - Friday</span>
                    <span>9:00 AM - 6:00 PM</span>
                </li>
                <li>
                    <span class="day">Saturday</span>
                    <span>9:00 AM - 2:00 PM</span>
                </li>
                <li>
                    <span class="day">Sunday</span>
                    <span>By Appointment Only</span>
                </li>
                <li>
                    <span class="day">Public Holidays</span>
                    <span>Closed</span>
                </li>
            </ul>
        </div>
        
        <!-- Map Section -->
        <div class="map-section">
            <h2 class="section-title">Find Our Location</h2>
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3916.6837143409346!2d76.963292375955!3d10.998430089195028!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ba8585b8b2c5e7d%3A0xcb06845e2e05a1d3!2sSaravanampatti%2C%20Coimbatore%2C%20Tamil%20Nadu%20641035!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
        
        <!-- Quick Contact -->
        <div class="quick-contact">
            <h2>Need Immediate Assistance?</h2>
            <p>For urgent inquiries or to schedule a personal consultation, feel free to call us directly.</p>
            <div class="contact-buttons">
                <a href="tel:+918754032742" class="btn btn-primary">
                    <i class="fas fa-phone-alt"></i> Call Now: +91 87540 32742
                </a>
                <a href="<?= BASE_URL ?>/register" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i> Register Online
                </a>
            </div>
        </div>
        
    </section>
</div>

<!-- FOOTER - Exactly same as index.php -->
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
    document.querySelectorAll('.phone-link, .contact-link[href^="tel:"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const phone = this.textContent.replace(/\D/g, '');
            window.location.href = `tel:+91${phone}`;
        });
    });

    // Form submission handler
    const contactForm = document.querySelector('form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            const name = formData.get('name');
            const phone = formData.get('phone');
            
            // Simple validation
            if (!name || !phone) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Here you would typically send the data to your server
            // For now, we'll show a success message
            alert(`Thank you ${name}! Your message has been received. We will contact you at ${phone} shortly.`);
            
            // Reset form
            this.reset();
        });
    }

    // Smooth scroll for anchor links
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
});
</script>

</body>
</html>