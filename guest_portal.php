<?php
// =========================================================================
// Food Forest Sanctuary — Client Portal & Guest Reservation Hub
// =========================================================================

require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/client_auth.php';

client_session_start();

$is_user = is_client_user_logged_in();
$is_guest = is_guest_booking_session_active();
$current_user = $is_user ? get_logged_in_client_user() : null;
$guest_booking = $is_guest ? get_active_guest_booking() : null;

// Pre-fill ref if redirected
$prefill_ref = strtoupper(trim($_GET['ref'] ?? ''));

// Fetch bookings if authenticated
$bookings_list = [];
if ($is_user && $current_user) {
    $bookings_list = get_client_bookings($current_user['id']);
} elseif ($is_guest && $guest_booking) {
    $bookings_list = [$guest_booking];
}

$currency = get_setting('currency_symbol', '₹');
$concierge_wa = get_setting('concierge_whatsapp', '919234567890');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Portal & Reservations | Food Forest Sanctuary Kanthalloor</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .portal-wrapper {
            min-height: 100vh;
            background-color: #0d1a12;
            background-image: radial-gradient(circle at 10% 20%, rgba(20, 42, 29, 0.8) 0%, rgba(13, 26, 18, 1) 90%);
            color: #EAEFED;
            padding: 120px 20px 80px;
        }

        .portal-container {
            max-width: 1060px;
            margin: 0 auto;
        }

        .portal-brand-top {
            text-align: center;
            margin-bottom: 40px;
        }

        .portal-brand-top .logo-main {
            font-size: 32px;
            letter-spacing: 3px;
            color: #C5A059;
            text-decoration: none;
            display: block;
        }

        .portal-brand-top .logo-sub {
            font-size: 11px;
            letter-spacing: 4px;
            color: #A1B5A9;
            text-transform: uppercase;
            margin-top: 4px;
        }

        /* Authentication Card */
        .portal-auth-card {
            max-width: 520px;
            margin: 0 auto;
            background: rgba(16, 31, 21, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(197, 160, 89, 0.25);
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
        }

        .auth-nav-tabs {
            display: flex;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 28px;
            gap: 4px;
        }

        .auth-tab-btn {
            flex: 1;
            padding: 10px 8px;
            background: transparent;
            border: none;
            color: #A1B5A9;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }

        .auth-tab-btn.active {
            background: #C5A059;
            color: #101F15;
        }

        .auth-pane {
            display: none;
        }

        .auth-pane.active {
            display: block;
        }

        .auth-pane-title {
            font-size: 24px;
            color: #EAEFED;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .auth-pane-sub {
            font-size: 13px;
            color: #A1B5A9;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .portal-field {
            margin-bottom: 18px;
        }

        .portal-field label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #C5A059;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .portal-field input {
            width: 100%;
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(197, 160, 89, 0.3);
            border-radius: 6px;
            color: #FFFFFF;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .portal-field input:focus {
            border-color: #C5A059;
            background: rgba(255, 255, 255, 0.08);
        }

        .btn-portal-submit {
            width: 100%;
            padding: 14px;
            background: #C5A059;
            color: #101F15;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .btn-portal-submit:hover {
            background: #dfc289;
            transform: translateY(-1px);
        }

        .portal-alert {
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
            display: none;
        }

        .portal-alert.error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #FCA5A5;
            display: block;
        }

        .portal-alert.success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #6EE7B7;
            display: block;
        }

        /* Logged In Dashboard Styles */
        .dashboard-header {
            background: rgba(16, 31, 21, 0.7);
            border: 1px solid rgba(197, 160, 89, 0.2);
            border-radius: 12px;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 36px;
        }

        .user-welcome-group {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-avatar-circle {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #C5A059, #8C6615);
            color: #101F15;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
        }

        .user-title-box h2 {
            font-size: 24px;
            font-weight: 500;
            color: #EAEFED;
            line-height: 1.2;
        }

        .badge-membership {
            display: inline-block;
            padding: 3px 10px;
            background: rgba(197, 160, 89, 0.15);
            border: 1px solid rgba(197, 160, 89, 0.4);
            color: #C5A059;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .dashboard-top-actions {
            display: flex;
            gap: 12px;
        }

        .btn-dash {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .btn-dash-primary {
            background: #C5A059;
            color: #101F15;
        }

        .btn-dash-secondary {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
            color: #EAEFED;
        }

        .btn-dash-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Guest Expiry Notice Banner */
        .guest-expiry-banner {
            background: linear-gradient(90deg, rgba(180, 83, 9, 0.2), rgba(16, 31, 21, 0.8));
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 10px;
            padding: 18px 24px;
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .banner-text-group h4 {
            color: #FBBF24;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .banner-text-group p {
            font-size: 13px;
            color: #D1D5DB;
        }

        /* Bookings List Cards */
        .bookings-grid {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .booking-card {
            background: rgba(16, 31, 21, 0.6);
            border: 1px solid rgba(197, 160, 89, 0.2);
            border-radius: 12px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 260px 1fr;
            transition: border-color 0.2s;
        }

        .booking-card:hover {
            border-color: #C5A059;
        }

        .booking-card-thumb {
            width: 100%;
            height: 100%;
            min-height: 200px;
            object-fit: cover;
        }

        .booking-card-body {
            padding: 24px 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .booking-villa-title {
            font-size: 22px;
            font-weight: 600;
            color: #C5A059;
            line-height: 1.2;
        }

        .card-ref-badge {
            background: rgba(255, 255, 255, 0.08);
            color: #A1B5A9;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-family: monospace;
            letter-spacing: 1px;
        }

        .booking-stay-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 16px;
            font-size: 13.5px;
            color: #CBD5E1;
        }

        .booking-stay-details span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .booking-food-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(245, 158, 11, 0.12);
            color: #FCD34D;
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 16px;
            align-self: flex-start;
        }

        .card-footer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .booking-amount-box .amt-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94A3B8;
        }

        .booking-amount-box .amt-val {
            font-size: 22px;
            font-weight: 700;
            color: #EAEFED;
        }

        .btn-view-receipt {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #C5A059;
            color: #101F15;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-view-receipt:hover {
            background: #dfc289;
            transform: translateY(-1px);
        }

        .no-bookings-box {
            text-align: center;
            padding: 60px 20px;
            background: rgba(16, 31, 21, 0.4);
            border: 1px dashed rgba(197, 160, 89, 0.3);
            border-radius: 12px;
        }

        @media (max-width: 768px) {
            .booking-card {
                grid-template-columns: 1fr;
            }
            .booking-card-thumb {
                height: 180px;
            }
            .card-footer-row {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

    <div class="portal-wrapper font-sans">
        <div class="portal-container">
            
            <!-- Brand Top -->
            <div class="portal-brand-top">
                <a href="index.php" class="logo-main font-serif">FOOD FOREST</a>
                <span class="logo-sub font-sans">KANTHALLOOR • GUEST RESERVATION PORTAL</span>
            </div>

            <?php if (!$is_user && !$is_guest): ?>
                <!-- ========================================================= -->
                <!-- AUTHENTICATION PANELS (Permanent, 30-Day Guest, Register) -->
                <!-- ========================================================= -->
                <div class="portal-auth-card">
                    
                    <div class="auth-nav-tabs font-sans">
                        <button type="button" class="auth-tab-btn active" data-target="pane-signin">Sign In</button>
                        <button type="button" class="auth-tab-btn" data-target="pane-guest">30-Day Guest Pass</button>
                        <button type="button" class="auth-tab-btn" data-target="pane-signup">New Account</button>
                    </div>

                    <div id="auth-alert-box" class="portal-alert"></div>

                    <!-- Pane 1: Permanent Member Sign In -->
                    <div class="auth-pane active" id="pane-signin">
                        <h3 class="auth-pane-title font-serif">Sanctuary Member Sign In</h3>
                        <p class="auth-pane-sub">Access your personal booking history, archived receipts, and food selections.</p>
                        
                        <form id="form-portal-login" onsubmit="event.preventDefault(); submitLogin();">
                            <div class="portal-field">
                                <label for="login-email">Registered Email Address</label>
                                <input type="email" id="login-email" required placeholder="e.g. guest@example.com">
                            </div>
                            <div class="portal-field">
                                <label for="login-password">Password</label>
                                <input type="password" id="login-password" required placeholder="••••••••">
                            </div>
                            <button type="submit" class="btn-portal-submit" id="btn-login-submit">
                                <span>Sign In to Sanctuary Account</span>
                            </button>
                        </form>
                    </div>

                    <!-- Pane 2: 30-Day Quick Guest Access -->
                    <div class="auth-pane" id="pane-guest">
                        <h3 class="auth-pane-title font-serif">30-Day Guest Booking Pass</h3>
                        <p class="auth-pane-sub">Enter your Booking Reference and secret Passcode (or phone) provided at reservation time.</p>
                        
                        <form id="form-portal-guest" onsubmit="event.preventDefault(); submitGuestAccess();">
                            <div class="portal-field">
                                <label for="guest-ref">Reservation Reference / Guest ID</label>
                                <input type="text" id="guest-ref" required placeholder="e.g. FF-4829" value="<?php echo htmlspecialchars($prefill_ref); ?>">
                            </div>
                            <div class="portal-field">
                                <label for="guest-passcode">Access Passcode or Contact Phone</label>
                                <input type="text" id="guest-passcode" required placeholder="e.g. 4-digit Passcode or WhatsApp Phone">
                            </div>
                            <button type="submit" class="btn-portal-submit" id="btn-guest-submit">
                                <span>Access My Reservation</span>
                            </button>
                        </form>
                    </div>

                    <!-- Pane 3: Register New Permanent Account -->
                    <div class="auth-pane" id="pane-signup">
                        <h3 class="auth-pane-title font-serif">Create Sanctuary Account</h3>
                        <p class="auth-pane-sub">Keep all your estate stays, dining choices, and receipts permanently archived.</p>
                        
                        <form id="form-portal-register" onsubmit="event.preventDefault(); submitRegister();">
                            <div class="portal-field">
                                <label for="reg-name">Full Name *</label>
                                <input type="text" id="reg-name" required placeholder="e.g. Ananya Nair">
                            </div>
                            <div class="portal-field">
                                <label for="reg-email">Email Address *</label>
                                <input type="email" id="reg-email" required placeholder="ananya@example.com">
                            </div>
                            <div class="portal-field">
                                <label for="reg-phone">WhatsApp Contact Number *</label>
                                <input type="tel" id="reg-phone" required placeholder="+91 98765 43210">
                            </div>
                            <div class="portal-field">
                                <label for="reg-password">Create Secret Password *</label>
                                <input type="password" id="reg-password" required placeholder="Minimum 6 characters">
                            </div>
                            <button type="submit" class="btn-portal-submit" id="btn-reg-submit">
                                <span>Create Permanent Account</span>
                            </button>
                        </form>
                    </div>

                </div>

            <?php else: ?>
                <!-- ========================================================= -->
                <!-- AUTHENTICATED GUEST DASHBOARD -->
                <!-- ========================================================= -->
                <div class="dashboard-header">
                    <div class="user-welcome-group">
                        <div class="user-avatar-circle font-serif">
                            <?php 
                            $disp_name = $is_user ? $current_user['full_name'] : ($guest_booking['guest_name'] ?? 'Guest');
                            echo strtoupper(substr($disp_name, 0, 1)); 
                            ?>
                        </div>
                        <div class="user-title-box">
                            <h2>Welcome, <?php echo htmlspecialchars($disp_name); ?></h2>
                            <?php if ($is_user): ?>
                                <span class="badge-membership"><i class="fa-solid fa-crown"></i> Permanent Sanctuary Member</span>
                            <?php else: ?>
                                <span class="badge-membership" style="color: #F59E0B; border-color: rgba(245, 158, 11, 0.4);"><i class="fa-solid fa-key"></i> 30-Day Guest Pass</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dashboard-top-actions">
                        <a href="index.php" class="btn-dash btn-dash-secondary">
                            <i class="fa-solid fa-house"></i> Sanctuary Home
                        </a>
                        <button type="button" onclick="portalLogout()" class="btn-dash btn-dash-secondary">
                            <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                        </button>
                    </div>
                </div>

                <!-- If Guest: Upgrade Prompt Banner -->
                <?php if ($is_guest && !empty($guest_booking)): ?>
                    <div class="guest-expiry-banner">
                        <div class="banner-text-group">
                            <h4 class="font-serif"><i class="fa-solid fa-hourglass-half"></i> 30-Day Temporary Access Pass</h4>
                            <p>This reservation pass will auto-destruct on <strong><?php echo !empty($guest_booking['expires_at']) ? date('d M Y', strtotime($guest_booking['expires_at'])) : '30 days from booking'; ?></strong>. Convert to a permanent account now to preserve your booking history indefinitely.</p>
                        </div>
                        <div>
                            <button type="button" onclick="promptUpgradeAccount('<?php echo htmlspecialchars($guest_booking['reference_code']); ?>')" class="btn-dash btn-dash-primary">
                                <i class="fa-solid fa-crown"></i> Upgrade to Permanent (Free)
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Bookings Showcase -->
                <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="font-serif" style="font-size: 26px; color: #C5A059;">Your Sanctuary Reservations</h3>
                    <a href="https://wa.me/<?php echo htmlspecialchars($concierge_wa); ?>?text=Hello%20Master%20Concierge,%20I%20am%20enquiring%20about%20my%20reservations." target="_blank" class="btn-dash btn-dash-secondary">
                        <i class="fa-brands fa-whatsapp"></i> Master Concierge
                    </a>
                </div>

                <?php if (!empty($bookings_list)): ?>
                    <div class="bookings-grid">
                        <?php foreach ($bookings_list as $bk): 
                            $b_image = $bk['room_image'] ?? ($bk['villa_type'] === 'treehouse' ? 'assets/images/treehouse_exterior.png' : 'assets/images/mudhouse_exterior.png');
                            $b_title = $bk['room_title'] ?? ($bk['villa_type'] === 'treehouse' ? 'Luxury Canopy Treehouse' : 'Traditional Earthen Mudhouse');
                            $b_food_count = !empty($bk['food_items_list']) ? count($bk['food_items_list']) : 0;
                            $receipt_url = "receipt.php?ref=" . urlencode($bk['reference_code']);
                            if (!empty($bk['guest_access_token'])) {
                                $receipt_url .= "&passcode=" . urlencode($bk['guest_access_token']);
                            }
                        ?>
                            <div class="booking-card">
                                <img src="<?php echo htmlspecialchars($b_image); ?>" 
                                     alt="<?php echo htmlspecialchars($b_title); ?>" 
                                     class="booking-card-thumb"
                                     onerror="this.src='assets/images/treehouse_exterior.png'">
                                
                                <div class="booking-card-body">
                                    <div>
                                        <div class="card-header-row">
                                            <h4 class="booking-villa-title font-serif"><?php echo htmlspecialchars($b_title); ?></h4>
                                            <span class="card-ref-badge font-sans"><?php echo htmlspecialchars($bk['reference_code']); ?></span>
                                        </div>

                                        <div class="booking-stay-details font-sans">
                                            <span><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y', strtotime($bk['checkin_date'])); ?> — <?php echo date('d M Y', strtotime($bk['checkout_date'])); ?></span>
                                            <span><i class="fa-regular fa-moon"></i> <?php echo (int)$bk['nights']; ?> Night<?php echo $bk['nights'] > 1 ? 's' : ''; ?></span>
                                            <span><i class="fa-solid fa-users"></i> <?php echo (int)$bk['guests_count']; ?> Guests</span>
                                        </div>

                                        <?php if ($b_food_count > 0): ?>
                                            <div class="booking-food-pill font-sans">
                                                <i class="fa-solid fa-utensils"></i>
                                                <span>Gastronomy Pre-selected: <?php echo $b_food_count; ?> Dish Set<?php echo $b_food_count > 1 ? 's' : ''; ?> (<?php echo $currency . number_format((float)($bk['food_amount'] ?? 0), 0); ?>)</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="booking-food-pill font-sans" style="background: rgba(255, 255, 255, 0.05); color: #94A3B8; border-color: rgba(255, 255, 255, 0.1);">
                                                <i class="fa-solid fa-seedling"></i>
                                                <span>Gastronomy: Farm À La Carte on Arrival</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="card-footer-row font-sans">
                                        <div class="booking-amount-box">
                                            <span class="amt-label">Estimated Total:</span>
                                            <div class="amt-val font-serif" style="color: #C5A059;"><?php echo $currency . number_format((float)$bk['total_amount'], 2); ?></div>
                                        </div>
                                        <a href="<?php echo htmlspecialchars($receipt_url); ?>" class="btn-view-receipt font-sans">
                                            <i class="fa-solid fa-file-invoice"></i>
                                            <span>View / Download Receipt (PDF)</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-bookings-box font-sans">
                        <i class="fa-regular fa-calendar-xmark" style="font-size: 36px; color: #C5A059; margin-bottom: 12px; display: block;"></i>
                        <h4 class="font-serif" style="font-size: 20px; color: #EAEFED;">No Reservations Found</h4>
                        <p style="color: #A1B5A9; max-width: 440px; margin: 8px auto 20px;">You do not have any active reservations under this account yet.</p>
                        <a href="index.php#rooms-experience" class="btn-dash btn-dash-primary">
                            <span>Explore Stays & Reserve</span>
                        </a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>

    <!-- Interactive Client Auth Script -->
    <script>
    // Tab switching in auth box
    document.querySelectorAll('.auth-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.auth-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.auth-pane').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            var target = document.getElementById(this.getAttribute('data-target'));
            if (target) target.classList.add('active');
            clearAlert();
        });
    });

    function showAlert(msg, isError) {
        var box = document.getElementById('auth-alert-box');
        if (!box) return;
        box.className = 'portal-alert ' + (isError ? 'error' : 'success');
        box.innerHTML = msg;
    }

    function clearAlert() {
        var box = document.getElementById('auth-alert-box');
        if (box) {
            box.className = 'portal-alert';
            box.innerHTML = '';
        }
    }

    async function submitLogin() {
        var email = document.getElementById('login-email').value.trim();
        var password = document.getElementById('login-password').value;
        var btn = document.getElementById('btn-login-submit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Signing In...';

        try {
            var res = await fetch('api/guest_auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'login', email: email, password: password })
            });
            var data = await res.json();
            if (data.success) {
                showAlert(data.message, false);
                setTimeout(function() { window.location.reload(); }, 600);
            } else {
                showAlert(data.message || 'Login failed.', true);
                btn.disabled = false;
                btn.innerHTML = 'Sign In to Sanctuary Account';
            }
        } catch (err) {
            showAlert('Network error. Please try again.', true);
            btn.disabled = false;
            btn.innerHTML = 'Sign In to Sanctuary Account';
        }
    }

    async function submitGuestAccess() {
        var ref = document.getElementById('guest-ref').value.trim();
        var passcode = document.getElementById('guest-passcode').value.trim();
        var btn = document.getElementById('btn-guest-submit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying Passcode...';

        try {
            var res = await fetch('api/guest_auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'guest_access', reference_code: ref, passcode: passcode })
            });
            var data = await res.json();
            if (data.success) {
                showAlert(data.message, false);
                setTimeout(function() { window.location.reload(); }, 600);
            } else {
                showAlert(data.message || 'Verification failed.', true);
                btn.disabled = false;
                btn.innerHTML = 'Access My Reservation';
            }
        } catch (err) {
            showAlert('Network error. Please try again.', true);
            btn.disabled = false;
            btn.innerHTML = 'Access My Reservation';
        }
    }

    async function submitRegister() {
        var name = document.getElementById('reg-name').value.trim();
        var email = document.getElementById('reg-email').value.trim();
        var phone = document.getElementById('reg-phone').value.trim();
        var password = document.getElementById('reg-password').value;
        var btn = document.getElementById('btn-reg-submit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating Account...';

        try {
            var res = await fetch('api/guest_auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'register', full_name: name, email: email, phone: phone, password: password })
            });
            var data = await res.json();
            if (data.success) {
                showAlert(data.message, false);
                setTimeout(function() { window.location.reload(); }, 600);
            } else {
                showAlert(data.message || 'Registration failed.', true);
                btn.disabled = false;
                btn.innerHTML = 'Create Permanent Account';
            }
        } catch (err) {
            showAlert('Network error. Please try again.', true);
            btn.disabled = false;
            btn.innerHTML = 'Create Permanent Account';
        }
    }

    async function portalLogout() {
        try {
            await fetch('api/guest_auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' })
            });
            window.location.href = 'guest_portal.php';
        } catch (err) {
            window.location.reload();
        }
    }

    async function promptUpgradeAccount(refCode) {
        var pwd = prompt("Create a password to convert this reservation into a permanent account:");
        if (!pwd || pwd.length < 4) {
            if (pwd !== null) alert("Password must be at least 4 characters.");
            return;
        }

        try {
            var res = await fetch('api/guest_auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'upgrade', reference_code: refCode, password: pwd })
            });
            var data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || "Failed to upgrade account.");
            }
        } catch (err) {
            alert("Error upgrading account. Please contact concierge.");
        }
    }
    </script>

</body>
</html>
