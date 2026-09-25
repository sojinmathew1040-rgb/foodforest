<?php
require_once __DIR__ . '/../admin/includes/db.php';
$f_phone = get_setting('concierge_phone', '+91 92345 67890');
$f_whatsapp = get_setting('concierge_whatsapp', '919234567890');
$f_whatsapp_label = get_setting('footer_whatsapp_label', '(Instant Concierge)');
$f_email = get_setting('concierge_email', 'concierge@foodforestkanthalloor.com');
$f_location = get_setting('location', 'Kanthalloor High Range, Idukki, Kerala - 685620');
$f_estate_name = get_setting('estate_name', 'Food Forest Sanctuary Kanthalloor');
$f_tagline = get_setting('footer_tagline', 'An intimate sanctuary where ancestral architecture meets untamed nature. Rediscover silence, wholesome farm-to-table flavors, and deep mountain tranquility.');
$f_hours = get_setting('concierge_hours', '08:00 AM – 09:00 PM');
$f_concierge_prefix = get_setting('footer_concierge_badge_text', 'Estate Concierge Available');
$f_instagram = get_setting('instagram_url', '#');
$f_facebook = get_setting('facebook_url', '#');
$f_youtube = get_setting('youtube_url', '#');
$site_name = get_setting('site_name', 'FOOD FOREST');
$site_tagline = get_setting('site_tagline', 'KANTHALLOOR • ECO SANCTUARY');

// Top Badges
$b1_icon = get_setting('footer_badge1_icon', 'fa-solid fa-seedling');
$b1_title = get_setting('footer_badge1_title', '100% Organic Soil');
$b1_desc = get_setting('footer_badge1_desc', 'Zero synthetic pesticides or fertilizers');

$b2_icon = get_setting('footer_badge2_icon', 'fa-solid fa-house-chimney');
$b2_title = get_setting('footer_badge2_title', 'Vernacular Cob Clay');
$b2_desc = get_setting('footer_badge2_desc', 'Traditional low-carbon architecture');

$b3_icon = get_setting('footer_badge3_icon', 'fa-solid fa-droplet');
$b3_title = get_setting('footer_badge3_title', 'Mountain Spring Water');
$b3_desc = get_setting('footer_badge3_desc', 'Filtered natural water, zero single-use plastic');

$b4_icon = get_setting('footer_badge4_icon', 'fa-solid fa-people-roof');
$b4_title = get_setting('footer_badge4_title', 'Local Community First');
$b4_desc = get_setting('footer_badge4_desc', 'Crafted & staffed by native artisans');

// Column 2 Navigation Links
$f_nav_title = get_setting('footer_nav_title', 'The Sanctuary');
$raw_nav_links = get_setting('footer_nav_links', "Our Story & Ethos|#welcome\nCanopy Treehouse|#rooms-experience\nEarthen Mudhouse|#rooms-experience\nActivities|#experiences\nFood Menu & Hearth|#dining\nGuest Portal & Receipts|guest_portal.php|fa-solid fa-key|1\nVisual Gallery|#gallery\nEstate Landscape|#sanctuary\nGuest Stories|#testimonials");

$nav_lines = array_filter(array_map('trim', explode("\n", $raw_nav_links)));
$parsed_links = [];
foreach ($nav_lines as $line) {
    $parts = explode('|', $line);
    if (count($parts) >= 2) {
        $parsed_links[] = [
            'label' => trim($parts[0]),
            'url' => trim($parts[1]),
            'icon' => isset($parts[2]) ? trim($parts[2]) : '',
            'highlight' => isset($parts[3]) && trim($parts[3]) === '1'
        ];
    }
}

// Column 3 Contact
$f_contact_title = get_setting('footer_contact_title', 'Direct Concierge');
$f_reserve_btn_text = get_setting('footer_reserve_btn_text', 'Reserve Your Sanctuary');

// Column 4 Newsletter
$f_gazette_title = get_setting('footer_gazette_title', 'Sanctuary Gazette');
$f_gazette_desc = get_setting('footer_gazette_desc', 'Receive private seasonal bulletins on apple harvests, wild honey collection, and intimate villa releases.');
$f_gazette_placeholder = get_setting('footer_gazette_placeholder', 'Enter your email address');
$f_gazette_msg = get_setting('footer_gazette_msg', 'Thank you for subscribing to our Gazette.');

// Bottom Bar & Legal
$f_copyright_tpl = get_setting('footer_copyright_text', '© {year} Food Forest Sanctuary Kanthalloor. Crafted for conscious travelers.');
$f_copyright = str_replace(['{year}', '{estate_name}'], [date('Y'), $f_estate_name], $f_copyright_tpl);

$f_legal1_title = get_setting('footer_legal1_title', 'Privacy Charter');
$f_legal1_url = get_setting('footer_legal1_url', '#');
$f_legal2_title = get_setting('footer_legal2_title', 'Sustainability Policy');
$f_legal2_url = get_setting('footer_legal2_url', '#');
$f_legal3_title = get_setting('footer_legal3_title', 'Guest Etiquette');
$f_legal3_url = get_setting('footer_legal3_url', '#');
$f_staff_label = get_setting('footer_staff_label', 'Staff Portal');
$f_staff_url = get_setting('footer_staff_url', 'admin/');
?>
        <!-- Ultra-Luxury Footer Section -->
        <footer id="contact" class="main-footer section-padding">
            <div class="footer-bg-glow"></div>
            
            <!-- Sustainability & Accolades Banner -->
            <div class="container footer-badges-banner">
                <div class="footer-badge-item">
                    <i class="<?php echo htmlspecialchars($b1_icon); ?>"></i>
                    <div>
                        <strong class="font-serif"><?php echo htmlspecialchars($b1_title); ?></strong>
                        <p class="font-sans"><?php echo htmlspecialchars($b1_desc); ?></p>
                    </div>
                </div>
                <div class="footer-badge-item">
                    <i class="<?php echo htmlspecialchars($b2_icon); ?>"></i>
                    <div>
                        <strong class="font-serif"><?php echo htmlspecialchars($b2_title); ?></strong>
                        <p class="font-sans"><?php echo htmlspecialchars($b2_desc); ?></p>
                    </div>
                </div>
                <div class="footer-badge-item">
                    <i class="<?php echo htmlspecialchars($b3_icon); ?>"></i>
                    <div>
                        <strong class="font-serif"><?php echo htmlspecialchars($b3_title); ?></strong>
                        <p class="font-sans"><?php echo htmlspecialchars($b3_desc); ?></p>
                    </div>
                </div>
                <div class="footer-badge-item">
                    <i class="<?php echo htmlspecialchars($b4_icon); ?>"></i>
                    <div>
                        <strong class="font-serif"><?php echo htmlspecialchars($b4_title); ?></strong>
                        <p class="font-sans"><?php echo htmlspecialchars($b4_desc); ?></p>
                    </div>
                </div>
            </div>

            <div class="container footer-grid">
                <!-- Brand Bio -->
                <div class="footer-brand">
                    <a href="#" class="logo font-serif">
                        <span class="logo-main"><?php echo htmlspecialchars($site_name); ?></span>
                        <span class="logo-sub font-sans"><?php echo htmlspecialchars($site_tagline); ?></span>
                    </a>
                    <p class="footer-tagline font-sans">
                        <?php echo htmlspecialchars($f_tagline); ?>
                    </p>
                    <div class="footer-concierge-badge font-sans">
                        <span class="status-indicator"></span>
                        <span><?php echo htmlspecialchars($f_concierge_prefix); ?><?php echo !empty($f_hours) ? ' • ' . htmlspecialchars($f_hours) : ''; ?></span>
                    </div>
                    <div class="social-links">
                        <?php if (!empty($f_instagram) && $f_instagram !== '#'): ?>
                            <a href="<?php echo htmlspecialchars($f_instagram); ?>" class="social-icon magnetic" data-strength="10" aria-label="Instagram" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                        <?php else: ?>
                            <a href="#" class="social-icon magnetic" data-strength="10" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <?php endif; ?>

                        <?php if (!empty($f_facebook) && $f_facebook !== '#'): ?>
                            <a href="<?php echo htmlspecialchars($f_facebook); ?>" class="social-icon magnetic" data-strength="10" aria-label="Facebook" target="_blank"><i class="fa-brands fa-facebook-f"></i></a>
                        <?php else: ?>
                            <a href="#" class="social-icon magnetic" data-strength="10" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <?php endif; ?>

                        <?php if (!empty($f_youtube) && $f_youtube !== '#'): ?>
                            <a href="<?php echo htmlspecialchars($f_youtube); ?>" class="social-icon magnetic" data-strength="10" aria-label="YouTube" target="_blank"><i class="fa-brands fa-youtube"></i></a>
                        <?php else: ?>
                            <a href="#" class="social-icon magnetic" data-strength="10" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                        <?php endif; ?>

                        <a href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/[^0-9]/', '', $f_whatsapp)); ?>" target="_blank" class="social-icon magnetic" data-strength="10" aria-label="WhatsApp Concierge"><i class="fa-brands fa-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="footer-links">
                    <h4 class="footer-title font-serif"><?php echo htmlspecialchars($f_nav_title); ?></h4>
                    <ul class="font-sans">
                        <?php foreach ($parsed_links as $pl): ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($pl['url']); ?>" <?php echo $pl['highlight'] ? 'style="color: #C5A059;"' : ''; ?>>
                                    <?php if (!empty($pl['icon'])): ?>
                                        <i class="<?php echo htmlspecialchars($pl['icon']); ?>" style="font-size: 11px; margin-right: 4px;"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($pl['label']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Contact & Location -->
                <div class="footer-contact">
                    <h4 class="footer-title font-serif"><?php echo htmlspecialchars($f_contact_title); ?></h4>
                    <ul class="font-sans">
                        <?php if (!empty($f_location)): ?>
                            <li><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($f_location); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($f_phone)): ?>
                            <li><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($f_phone); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($f_whatsapp)): ?>
                            <li><i class="fa-brands fa-whatsapp"></i> +<?php echo htmlspecialchars(ltrim($f_whatsapp, '+')); ?> <?php echo htmlspecialchars($f_whatsapp_label); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($f_email)): ?>
                            <li><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($f_email); ?></li>
                        <?php endif; ?>
                    </ul>
                    <button type="button" class="btn-primary footer-reserve-btn open-booking-modal-btn font-sans" style="margin-top: 20px;">
                        <span><?php echo htmlspecialchars($f_reserve_btn_text); ?></span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>

                <!-- Newsletter Subscription -->
                <div class="footer-newsletter">
                    <h4 class="footer-title font-serif"><?php echo htmlspecialchars($f_gazette_title); ?></h4>
                    <p class="font-sans"><?php echo htmlspecialchars($f_gazette_desc); ?></p>
                    <form class="newsletter-form" id="sanctuary-newsletter-form">
                        <div class="newsletter-input-group">
                            <input type="email" placeholder="<?php echo htmlspecialchars($f_gazette_placeholder); ?>" required class="font-sans">
                            <button type="submit" class="newsletter-submit" aria-label="Subscribe to Gazette">
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                        <span class="newsletter-msg font-sans" id="newsletter-msg" style="display: none;"><?php echo htmlspecialchars($f_gazette_msg); ?></span>
                    </form>
                </div>
            </div>
            
            <div class="footer-bottom container">
                <p class="copyright font-sans"><?php echo htmlspecialchars($f_copyright); ?></p>
                <div class="footer-meta font-sans">
                    <?php if (!empty($f_legal1_title)): ?>
                        <a href="<?php echo htmlspecialchars($f_legal1_url); ?>"><?php echo htmlspecialchars($f_legal1_title); ?></a>
                    <?php endif; ?>
                    <?php if (!empty($f_legal2_title)): ?>
                        <a href="<?php echo htmlspecialchars($f_legal2_url); ?>"><?php echo htmlspecialchars($f_legal2_title); ?></a>
                    <?php endif; ?>
                    <?php if (!empty($f_legal3_title)): ?>
                        <a href="<?php echo htmlspecialchars($f_legal3_url); ?>"><?php echo htmlspecialchars($f_legal3_title); ?></a>
                    <?php endif; ?>
                    <?php if (!empty($f_staff_label)): ?>
                        <a href="<?php echo htmlspecialchars($f_staff_url); ?>" title="Authorized Concierge & Estate Staff Access" style="opacity: 0.75;"><i class="fa-solid fa-lock" style="font-size: 10px; margin-right: 3px;"></i> <?php echo htmlspecialchars($f_staff_label); ?></a>
                    <?php endif; ?>
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
