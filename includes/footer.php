<?php
require_once __DIR__ . '/../admin/includes/db.php';
$f_phone = get_setting('concierge_phone', '+91 923 456 7890');
$f_whatsapp = get_setting('concierge_whatsapp', '919234567890');
$f_email = get_setting('concierge_email', 'concierge@foodforestkanthalloor.com');
$f_location = get_setting('location', 'Kanthalloor, Marayoor Valley, Idukki District, Kerala 685620');
$f_estate_name = get_setting('estate_name', 'Food Forest Sanctuary Kanthalloor');
?>
        <!-- Ultra-Luxury Footer Section -->
        <footer id="contact" class="main-footer section-padding">
            <div class="footer-bg-glow"></div>
            
            <!-- Sustainability & Accolades Banner -->
            <div class="container footer-badges-banner">
                <div class="footer-badge-item">
                    <i class="fa-solid fa-seedling"></i>
                    <div>
                        <strong class="font-serif">100% Organic Soil</strong>
                        <p class="font-sans">Zero synthetic pesticides or fertilizers</p>
                    </div>
                </div>
                <div class="footer-badge-item">
                    <i class="fa-solid fa-house-chimney"></i>
                    <div>
                        <strong class="font-serif">Vernacular Cob Clay</strong>
                        <p class="font-sans">Traditional low-carbon architecture</p>
                    </div>
                </div>
                <div class="footer-badge-item">
                    <i class="fa-solid fa-droplet"></i>
                    <div>
                        <strong class="font-serif">Mountain Spring Water</strong>
                        <p class="font-sans">Filtered natural water, zero single-use plastic</p>
                    </div>
                </div>
                <div class="footer-badge-item">
                    <i class="fa-solid fa-people-roof"></i>
                    <div>
                        <strong class="font-serif">Local Community First</strong>
                        <p class="font-sans">Crafted & staffed by native artisans</p>
                    </div>
                </div>
            </div>

            <div class="container footer-grid">
                <!-- Brand Bio -->
                <div class="footer-brand">
                    <a href="#" class="logo font-serif">
                        <span class="logo-main">FOOD FOREST</span>
                        <span class="logo-sub font-sans">KANTHALLOOR • ECO SANCTUARY</span>
                    </a>
                    <p class="footer-tagline font-sans">
                        An intimate sanctuary where ancestral architecture meets untamed nature. Rediscover silence, wholesome farm-to-table flavors, and deep mountain tranquility.
                    </p>
                    <div class="footer-concierge-badge font-sans">
                        <span class="status-indicator"></span>
                        <span>Estate Concierge Available • 08:00 AM – 09:00 PM</span>
                    </div>
                    <div class="social-links">
                        <a href="#" class="social-icon magnetic" data-strength="10" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="social-icon magnetic" data-strength="10" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="social-icon magnetic" data-strength="10" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                        <a href="https://wa.me/<?php echo htmlspecialchars($f_whatsapp); ?>" target="_blank" class="social-icon magnetic" data-strength="10" aria-label="WhatsApp Concierge"><i class="fa-brands fa-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="footer-links">
                    <h4 class="footer-title font-serif">The Sanctuary</h4>
                    <ul class="font-sans">
                        <li><a href="#welcome">Our Story & Ethos</a></li>
                        <li><a href="#rooms-experience">Canopy Treehouse</a></li>
                        <li><a href="#rooms-experience">Earthen Mudhouse</a></li>
                        <li><a href="#experiences">Activities</a></li>
                        <li><a href="#gallery">Visual Gallery</a></li>
                        <li><a href="#sanctuary">Estate Landscape</a></li>
                        <li><a href="#testimonials">Guest Stories</a></li>
                    </ul>
                </div>

                <!-- Contact & Location -->
                <div class="footer-contact">
                    <h4 class="footer-title font-serif">Direct Concierge</h4>
                    <ul class="font-sans">
                        <li><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($f_location); ?></li>
                        <li><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($f_phone); ?></li>
                        <li><i class="fa-brands fa-whatsapp"></i> +<?php echo htmlspecialchars($f_whatsapp); ?> (Instant Concierge)</li>
                        <li><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($f_email); ?></li>
                    </ul>
                    <button type="button" class="btn-primary footer-reserve-btn open-booking-modal-btn font-sans" style="margin-top: 20px;">
                        <span>Reserve Your Sanctuary</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>

                <!-- Newsletter Subscription -->
                <div class="footer-newsletter">
                    <h4 class="footer-title font-serif">Sanctuary Gazette</h4>
                    <p class="font-sans">Receive private seasonal bulletins on apple harvests, wild honey collection, and intimate villa releases.</p>
                    <form class="newsletter-form" id="sanctuary-newsletter-form">
                        <div class="newsletter-input-group">
                            <input type="email" placeholder="Enter your email address" required class="font-sans">
                            <button type="submit" class="newsletter-submit" aria-label="Subscribe to Gazette">
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                        <span class="newsletter-msg font-sans" id="newsletter-msg" style="display: none;">Thank you for subscribing to our Gazette.</span>
                    </form>
                </div>
            </div>
            
            <div class="footer-bottom container">
                <p class="copyright font-sans">&copy; <?php echo date('Y'); ?> Food Forest Sanctuary Kanthalloor. Crafted for conscious travelers.</p>
                <div class="footer-meta font-sans">
                    <a href="#">Privacy Charter</a>
                    <a href="#">Sustainability Policy</a>
                    <a href="#">Guest Etiquette</a>
                    <a href="admin/" title="Authorized Concierge & Estate Staff Access" style="opacity: 0.75;"><i class="fa-solid fa-lock" style="font-size: 10px; margin-right: 3px;"></i> Staff Portal</a>
                </div>
            </div>
        </footer>

    </div> <!-- End .smooth-scroll-wrapper -->

    <!-- Load JavaScript Assets -->
    <script src="assets/js/webgl.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>

    <?php if (get_setting('content_protection_enabled', '0') === '1'): ?>
    <!-- Content & Image Protection Shield (Configured in Admin Settings) -->
    <script>
    (function() {
        function showProtectionNotice(msg) {
            let toast = document.getElementById('adm-prot-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'adm-prot-toast';
                toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#0d1b12;color:#c5a059;border:1px solid #c5a059;padding:12px 20px;border-radius:10px;font-size:12px;font-family:sans-serif;font-weight:600;box-shadow:0 8px 30px rgba(0,0,0,0.6);z-index:999999;transition:all 0.3s;display:none;';
                document.body.appendChild(toast);
            }
            toast.textContent = msg;
            toast.style.display = 'block';
            toast.style.opacity = '1';
            clearTimeout(window.__protToastTimer);
            window.__protToastTimer = setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => { toast.style.display = 'none'; }, 300);
            }, 2500);
        }

        // Disable right-click context menu
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            showProtectionNotice('🔒 Visual assets & content are protected at Food Forest Sanctuary.');
            return false;
        });

        // Block image dragging
        document.addEventListener('dragstart', function(e) {
            if (e.target.nodeName === 'IMG') {
                e.preventDefault();
                return false;
            }
        });

        // Block screenshot / devtools keys
        document.addEventListener('keydown', function(e) {
            if (e.key === 'PrintScreen' ||
                (e.ctrlKey && (e.key === 's' || e.key === 'u' || e.key === 'p')) ||
                (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C')) ||
                e.key === 'F12') {
                e.preventDefault();
                showProtectionNotice('🔒 Protected content shortcut disabled.');
                return false;
            }
        });
    })();
    </script>
    <?php endif; ?>
</body>
</html>
