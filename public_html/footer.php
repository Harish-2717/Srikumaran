<!-- Footer -->
<footer class="bg-dark text-white pt-5 pb-4 position-relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="footer-pattern position-absolute top-0 left-0 w-100 h-100">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="footerPattern" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse">
                    <path d="M0 0L60 0L60 60Z" fill="rgba(255,255,255,0.01)" />
                </pattern>
            </defs>
            <rect x="0" y="0" width="100%" height="100%" fill="url(#footerPattern)" />
        </svg>
    </div>
    
    <div class="container position-relative">
        <div class="row">
            <!-- Brand Info -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="footer-brand mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="footer-logo bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-gem text-white fs-5"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold text-white mb-0 fs-4">Sri Kumaran</h3>
                            <p class="text-primary mb-0">Mana Maalai</p>
                        </div>
                    </div>
                    <p class="text-white-60 mb-4">
                        Crafting timeless jewelry with passion and precision since 1995. 
                        Where every piece tells a story of heritage and elegance.
                    </p>
                </div>
                
                <!-- Newsletter -->
                <div class="newsletter-form mb-4">
                    <h6 class="fw-bold mb-3">Stay Updated</h6>
                    <div class="input-group">
                        <input type="email" class="form-control bg-dark border-secondary text-white" placeholder="Your email">
                        <button class="btn btn-primary" type="button">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Social -->
                <div class="social-links">
                    <h6 class="fw-bold mb-3">Follow Us</h6>
                    <div class="d-flex gap-3">
                        <a href="#" class="social-icon bg-facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon bg-instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon bg-whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="#" class="social-icon bg-twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h5 class="fw-bold mb-4 text-primary">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <a href="<?= BASE_URL ?>/" class="text-white-60 text-decoration-none d-flex align-items-center hover-lift">
                            <i class="fas fa-chevron-right me-2 small text-primary"></i>Home
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?= BASE_URL ?>/about.php" class="text-white-60 text-decoration-none d-flex align-items-center hover-lift">
                            <i class="fas fa-chevron-right me-2 small text-primary"></i>About Us
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?= BASE_URL ?>/contact.php" class="text-white-60 text-decoration-none d-flex align-items-center hover-lift">
                            <i class="fas fa-chevron-right me-2 small text-primary"></i>Contact
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="#" class="text-white-60 text-decoration-none d-flex align-items-center hover-lift">
                            <i class="fas fa-chevron-right me-2 small text-primary"></i>Collections
                        </a>
                    </li>
                    <li>
                        <a href="#" class="text-white-60 text-decoration-none d-flex align-items-center hover-lift">
                            <i class="fas fa-chevron-right me-2 small text-primary"></i>Testimonials
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="fw-bold mb-4 text-primary">Contact Us</h5>
                <ul class="list-unstyled">
                    <li class="mb-4">
                        <div class="d-flex">
                            <div class="contact-icon bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="fas fa-envelope text-primary"></i>
                            </div>
                            <div>
                                <span class="text-white-60 small d-block">Email</span>
                                <a href="mailto:srikumaranmanamaalai@gmail.com" class="text-white text-decoration-none fw-semibold">
                                    srikumaranmanamaalai@gmail.com
                                </a>
                            </div>
                        </div>
                    </li>
                    <li class="mb-4">
                        <div class="d-flex">
                            <div class="contact-icon bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="fas fa-phone-alt text-primary"></i>
                            </div>
                            <div>
                                <span class="text-white-60 small d-block">Phone</span>
                                <div>
                                    <a href="tel:+919894732742" class="text-white text-decoration-none d-block">+91 87540 32742</a>
                                    <a href="tel:+914222666505" class="text-white text-decoration-none d-block">+91 422 2666505</a>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="d-flex">
                            <div class="contact-icon bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="fas fa-map-marker-alt text-primary"></i>
                            </div>
                            <div>
                                <span class="text-white-60 small d-block">Address</span>
                                <span class="text-white">
                                    79, North Vinayagapuram,<br>
                                    8th Street, Saravanampatti,<br>
                                    Coimbatore - 641035.
                                </span>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Business Hours -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="hours-card bg-dark bg-opacity-50 p-4 rounded-3 border border-secondary">
                    <h5 class="fw-bold mb-4 text-primary">Business Hours</h5>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-3 d-flex justify-content-between">
                            <span class="text-white-60">Mon - Fri</span>
                            <span class="text-white fw-semibold">9:00 AM - 8:00 PM</span>
                        </li>
                        <li class="mb-3 d-flex justify-content-between">
                            <span class="text-white-60">Saturday</span>
                            <span class="text-white fw-semibold">10:00 AM - 6:00 PM</span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span class="text-white-60">Sunday</span>
                            <span class="text-white fw-semibold">By Appointment</span>
                        </li>
                    </ul>
                    <a href="<?= BASE_URL ?>/contact.php" class="btn btn-primary w-100 py-2 fw-semibold rounded-pill">
                        <i class="fas fa-calendar-check me-2"></i>Book Appointment
                    </a>
                </div>
            </div>
        </div>

        <hr class="border-secondary my-4">

        <!-- Copyright -->
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <p class="mb-0 text-white-60">
                    &copy; <?= date('Y') ?> Sri Kumaran Mana Maalai. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="d-flex justify-content-center justify-content-md-end gap-4">
                    <a href="#" class="text-white-60 text-decoration-none small">Privacy Policy</a>
                    <a href="#" class="text-white-60 text-decoration-none small">Terms of Service</a>
                    <a href="#" class="text-white-60 text-decoration-none small">Cookie Policy</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
/* Footer Styles */
footer {
    background: linear-gradient(135deg, #0c0c0c 0%, #1a1a2e 100%);
    position: relative;
}

.text-white-60 {
    color: rgba(255, 255, 255, 0.6) !important;
}

/* Social Icons */
.social-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
    color: white;
}

.bg-facebook { background: #3b5998; }
.bg-instagram { background: linear-gradient(45deg, #405de6, #5851db, #833ab4, #c13584, #e1306c, #fd1d1d); }
.bg-whatsapp { background: #25D366; }
.bg-twitter { background: #1DA1F2; }

.social-icon:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

/* Contact Icons */
.contact-icon {
    transition: all 0.3s ease;
}

/* Hover Effects */
.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    color: #fff !important;
    transform: translateX(5px);
}

/* Newsletter */
.newsletter-form .form-control {
    border-color: #444 !important;
    border-right: 0;
}

.newsletter-form .form-control:focus {
    box-shadow: none;
    border-color: #0d6efd;
}

.newsletter-form .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

/* Hours Card */
.hours-card {
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

/* Footer Logo */
.footer-logo {
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .footer-brand {
        text-align: center;
    }
    
    .footer-brand .d-flex {
        justify-content: center;
    }
}
</style>